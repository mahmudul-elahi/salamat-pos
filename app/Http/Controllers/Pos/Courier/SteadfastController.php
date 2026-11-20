<?php

namespace App\Http\Controllers\Pos\Courier;

use App\Exceptions\GeneralException;
use App\Filters\Pos\Courier\CourierOrderFilter;
use App\Http\Controllers\Controller;
use App\Models\Pos\Courier\CourierOrder;
use App\Models\Pos\Sale\Sale;
use App\Services\Courier\SteadfastService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Throwable;

class SteadfastController extends Controller
{
    public function __construct(protected SteadfastService $steadfastService, protected CourierOrderFilter $filter) {}

    public function districts()
    {
        $districts = collect($this->steadfastService->getPoliceStations())
            ->map(fn($district) => ['id' => $district['id'], 'name' => $district['name']])
            ->values();

        return response()->json(['data' => $districts]);
    }

    public function thanas(int $districtId)
    {
        $district = collect($this->steadfastService->getPoliceStations())
            ->firstWhere('id', $districtId);

        $thanas = collect($district['policestations'] ?? [])
            ->map(fn($thana) => ['id' => $thana['id'], 'name' => $thana['name']])
            ->values();

        return response()->json(['data' => $thanas]);
    }

    public function index()
    {
        $perPage = request('per_page', 10);

        $baseQuery = CourierOrder::query()
            ->where('provider', 'steadfast')
            ->filters($this->filter);

        $grandTotal = (clone $baseQuery)->sum('amount_to_collect');

        $paginated = $baseQuery
            ->with(['sale:id,full_invoice_number', 'createdBy:id,name'])
            ->orderByRaw('COALESCE(courier_created_at, created_at) DESC')
            ->paginate($perPage)
            ->through(function ($order) {
                return [
                    'id' => $order->id,
                    'invoice_number' => $order->sale?->full_invoice_number ?? '-',
                    'sale_id' => $order->sale_id,
                    'consignment_id' => $order->consignment_id,
                    'tracking_code' => $order->tracking_code,
                    'track_url' => $order->track_url,
                    'recipient_name' => $order->recipient_name,
                    'recipient_phone' => $order->recipient_phone,
                    'recipient_alternative_phone' => $order->recipient_alternative_phone,
                    'recipient_email' => $order->recipient_email,
                    'address' => collect([$order->recipient_address, $order->recipient_thana, $order->recipient_district])->filter()->implode(', '),
                    'recipient_district' => $order->recipient_district,
                    'recipient_thana' => $order->recipient_thana,
                    'amount_to_collect' => $order->amount_to_collect,
                    'delivery_fee' => $order->delivery_fee,
                    'delivery_type' => $order->delivery_type == 1 ? 'Point Delivery' : 'Home Delivery',
                    'item_description' => $order->item_description,
                    'item_weight' => $order->item_weight,
                    'is_exchange' => (bool) $order->is_exchange,
                    'status' => $order->status,
                    'payment_status' => $order->payment_status,
                    'special_instruction' => $order->special_instruction,
                    'created_by_name' => $order->createdBy?->name ?? '-',
                    'created_at' => ($order->courier_created_at ?? $order->created_at)?->format('Y-m-d h:i A'),
                ];
            });

        return [
            ...$paginated->toArray(),
            'grand_total' => [
                'amount_to_collect' => $grandTotal,
            ],
        ];
    }

    /**
     * Public webhook Steadfast calls whenever a delivery status changes.
     * Registered outside the authenticated route groups (see routes/api.php).
     */
    /**
     * Tracking for one order, in the same shape REDX's returns so the frontend
     * can render any courier's timeline with one component.
     *
     * Steadfast's API only reports the *current* status, so the history comes
     * from the webhook events we've recorded; the live status is appended as
     * the most recent entry.
     *
     * @throws GeneralException
     */
    public function track(string $consignmentId)
    {
        $courierOrder = CourierOrder::query()
            ->where('provider', 'steadfast')
            ->where(fn($query) => $query->where('consignment_id', $consignmentId)->orWhere('tracking_code', $consignmentId))
            ->first();

        $events = collect($courierOrder?->raw_response['webhook_events'] ?? [])
            ->map(fn($event) => [
                'message' => $event['payload']['status'] ?? null,
                'time'    => $event['received_at'] ?? null,
            ])
            ->filter(fn($event) => $event['message']);

        // A live read, so the timeline is current even before any webhook lands.
        try {
            $live = $courierOrder?->consignment_id
                ? $this->steadfastService->getStatusByConsignmentId($courierOrder->consignment_id)
                : $this->steadfastService->getStatusByTrackingCode($consignmentId);

            if ($status = $live['delivery_status'] ?? null) {
                $events->push(['message' => $status, 'time' => now()->toDateTimeString()]);

                $courierOrder?->update(['status' => $status]);
            }
        } catch (Throwable $exception) {
            // A courier being unreachable shouldn't blank the stored history.
            Log::warning('Steadfast live status lookup failed: ' . $exception->getMessage());
        }

        return response()->json([
            'data'      => $events->values(),
            'track_url' => $this->steadfastService->trackingUrlFrom($courierOrder?->raw_response),
        ]);
    }

    public function webhook(Request $request)
    {
        $token = $request->header('Authorization');
        $expectedToken = config('services.steadfast.webhook_bearer_token');

        if (!$expectedToken || !$token || !hash_equals('Bearer ' . $expectedToken, $token)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $payload = $request->all();

        // Only consignment_id/invoice are needed to identify which order this
        // is about. status/cod_amount/etc. aren't always present on every
        // webhook call Steadfast makes (e.g. intermediate/test notifications)
        // — hard-rejecting the whole call over those used to silently drop
        // legitimate status updates whenever they were omitted. Apply
        // whatever fields are actually present instead of requiring all of
        // them upfront.
        $consignmentId = $payload['consignment_id'] ?? null;
        $invoice = $payload['invoice'] ?? null;
        $status = $payload['status'] ?? null;

        if (!$consignmentId && !$invoice) {
            Log::warning('Steadfast webhook: missing consignment_id/invoice, cannot identify order', ['payload' => $payload]);
            return response()->json(['error' => 'Missing consignment_id or invoice'], 400);
        }

        $courierOrder = CourierOrder::query()
            ->where('provider', 'steadfast')
            ->when($consignmentId, fn($query) => $query->where('consignment_id', $consignmentId))
            ->when(!$consignmentId && $invoice, fn($query) => $query->where('merchant_order_id', $invoice))
            ->first();

        if (!$courierOrder) {
            return response()->json(['message' => 'Order not found, ignored'], 202);
        }

        $courierOrder->update([
            'status' => $status ?? $courierOrder->status,
            'raw_response' => [
                ...($courierOrder->raw_response ?? []),
                'webhook_events' => [
                    ...(($courierOrder->raw_response['webhook_events'] ?? [])),
                    ['received_at' => now()->toDateTimeString(), 'payload' => $payload],
                ],
            ],
        ]);

        return response()->json(['message' => 'Webhook processed'], 202);
    }

    /**
     * Import historical Steadfast orders from an Excel/CSV export (Steadfast's
     * own dashboard export format), since their API has no "list all orders"
     * endpoint. Matched/upserted by consignment_id (their "Order ID" column).
     *
     * @throws GeneralException
     */
    public function importOrders(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
        ]);

        $reader = IOFactory::createReaderForFile($request->file('file')->getRealPath());
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($request->file('file')->getRealPath());
        $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);

        $headerRow = array_shift($rows);
        $columnMap = [];
        foreach ($headerRow as $letter => $heading) {
            $columnMap[trim((string) $heading)] = $letter;
        }

        $field = function (array $row, string $heading) use ($columnMap) {
            $letter = $columnMap[$heading] ?? null;
            return $letter ? trim((string) ($row[$letter] ?? '')) : null;
        };

        $imported = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            $consignmentId = $field($row, 'Order ID');

            if (!$consignmentId) {
                $skipped++;
                continue;
            }

            $invoice = $field($row, 'Invoice');
            $saleId = $invoice
                ? Sale::query()->where('full_invoice_number', $invoice)->value('id')
                : null;

            $createdAt = $field($row, 'Created At');

            $existing = CourierOrder::query()
                ->where('provider', 'steadfast')
                ->where('consignment_id', $consignmentId)
                ->first();

            CourierOrder::query()->updateOrCreate(
                ['provider' => 'steadfast', 'consignment_id' => $consignmentId],
                [
                    'sale_id' => $saleId,
                    'created_by' => $existing->created_by ?? auth()->id(),
                    'tracking_code' => $field($row, 'Tracking Code') ?: $existing->tracking_code ?? null,
                    'merchant_order_id' => $invoice ?: $existing->merchant_order_id ?? null,
                    'recipient_name' => $field($row, 'Recipient Name') ?: '-',
                    'recipient_phone' => $field($row, 'Recipient Phone') ?: '-',
                    'recipient_address' => $field($row, 'Recipient Address') ?: '-',
                    'amount_to_collect' => (float) ($field($row, 'COD Amount') ?: 0),
                    'delivery_fee' => $field($row, 'Shipping Charge') !== '' ? (float) $field($row, 'Shipping Charge') : null,
                    'status' => $field($row, 'Delivery Status') ?: ($existing->status ?? 'Unknown'),
                    'payment_status' => $field($row, 'Payment Status') ?: $existing->payment_status ?? null,
                    'special_instruction' => $field($row, 'Note') && $field($row, 'Note') !== '-' ? $field($row, 'Note') : null,
                    'courier_created_at' => $createdAt ? Carbon::parse($createdAt) : ($existing->courier_created_at ?? null),
                    'raw_response' => [
                        ...($existing->raw_response ?? []),
                        'imported_from_excel' => array_combine(array_keys($columnMap), array_map(
                            fn($letter) => $row[$letter] ?? null,
                            $columnMap
                        )),
                    ],
                ]
            );

            $existing ? $updated++ : $imported++;
        }

        return successResponse("Imported successfully. New: {$imported}, Updated: {$updated}, Skipped: {$skipped}");
    }

    /**
     * @throws Throwable
     * @throws GeneralException
     */
    public function storeOrder(Request $request, Sale $sale)
    {
        $data = $request->validate([
            'recipient_name' => ['required', 'string', 'max:100'],
            'recipient_phone' => ['required', 'string', 'max:20'],
            'recipient_alternative_phone' => ['nullable', 'string', 'max:20'],
            'recipient_email' => ['nullable', 'email', 'max:255'],
            'recipient_address' => ['required', 'string', 'max:250'],
            'recipient_district' => ['nullable', 'string', 'max:100'],
            'recipient_thana' => ['nullable', 'string', 'max:100'],
            'amount_to_collect' => ['required', 'numeric', 'min:0'],
            'delivery_type' => ['nullable', 'integer', 'in:0,1'],
            'item_description' => ['nullable', 'string', 'max:255'],
            'item_weight' => ['nullable', 'numeric', 'min:0'],
            'is_exchange' => ['nullable', 'boolean'],
            'special_instruction' => ['nullable', 'string'],
        ]);

        $fullAddress = collect([
            $data['recipient_address'],
            $data['recipient_thana'] ?? null,
            $data['recipient_district'] ?? null,
        ])->filter()->implode(', ');

        try {
            DB::beginTransaction();

            $response = $this->steadfastService->createOrder([
                'invoice' => $sale->full_invoice_number,
                'recipient_name' => $data['recipient_name'],
                'recipient_phone' => $data['recipient_phone'],
                'alternative_phone' => $data['recipient_alternative_phone'] ?? null,
                'recipient_email' => $data['recipient_email'] ?? null,
                'recipient_address' => $fullAddress,
                'cod_amount' => $data['amount_to_collect'],
                'note' => $data['special_instruction'] ?? null,
                'item_description' => $data['item_description'] ?? null,
                'delivery_type' => $data['delivery_type'] ?? 0,
                'weight' => $data['item_weight'] ?? null,
                'exchange' => !empty($data['is_exchange']),
            ]);

            $consignment = $response['consignment'] ?? [];

            $courierOrder = CourierOrder::query()->updateOrCreate(
                ['sale_id' => $sale->id, 'provider' => 'steadfast'],
                [
                    'created_by' => auth()->id(),
                    'provider' => 'steadfast',
                    'consignment_id' => $consignment['consignment_id'] ?? null,
                    'tracking_code' => $consignment['tracking_code'] ?? null,
                    'merchant_order_id' => $sale->full_invoice_number,
                    'recipient_name' => $data['recipient_name'],
                    'recipient_phone' => $data['recipient_phone'],
                    'recipient_alternative_phone' => $data['recipient_alternative_phone'] ?? null,
                    'recipient_email' => $data['recipient_email'] ?? null,
                    'recipient_address' => $data['recipient_address'],
                    'recipient_district' => $data['recipient_district'] ?? null,
                    'recipient_thana' => $data['recipient_thana'] ?? null,
                    'amount_to_collect' => $data['amount_to_collect'],
                    'delivery_type' => $data['delivery_type'] ?? 0,
                    'item_description' => $data['item_description'] ?? null,
                    'item_weight' => $data['item_weight'] ?? 0.5,
                    'is_exchange' => !empty($data['is_exchange']),
                    'special_instruction' => $data['special_instruction'] ?? null,
                    'status' => $consignment['status'] ?? 'in_review',
                    'courier_created_at' => now(),
                    'raw_response' => $response,
                ]
            );

            DB::commit();

            return successResponse('Steadfast courier order created successfully', $courierOrder);
        } catch (GeneralException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();
            throw new GeneralException($e->getMessage(), 500);
        }
    }
}
