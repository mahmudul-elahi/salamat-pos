<?php

namespace App\Http\Controllers\Pos\Courier;

use App\Exceptions\GeneralException;
use App\Filters\Pos\Courier\CourierOrderFilter;
use App\Http\Controllers\Controller;
use App\Models\Pos\Courier\CourierOrder;
use App\Models\Pos\Sale\Sale;
use App\Services\Courier\PathaoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class PathaoController extends Controller
{
    public function __construct(protected PathaoService $pathaoService, protected CourierOrderFilter $filter) {}

    public function index()
    {
        $perPage = request('per_page', 10);

        $baseQuery = CourierOrder::query()
            ->where('provider', 'pathao')
            ->filters($this->filter);

        $grandTotal = (clone $baseQuery)->sum('amount_to_collect');
        $grandTotalDiscount = (clone $baseQuery)->sum('discount');

        $paginated = $baseQuery
            ->with(['sale:id,full_invoice_number', 'createdBy:id,name'])
            ->orderByRaw('COALESCE(courier_created_at, created_at) DESC')
            ->paginate($perPage)
            ->through(function ($order) {
                return [
                    'id' => $order->id,
                    'invoice_number' => $order->sale?->full_invoice_number ?? '-',
                    'sale_id' => $order->sale_id,
                    'store_name' => $order->store_name,
                    'consignment_id' => $order->consignment_id,
                    'track_url' => $order->track_url,
                    'recipient_name' => $order->recipient_name,
                    'recipient_phone' => $order->recipient_phone,
                    'recipient_address' => $order->recipient_address,
                    'address' => collect([
                        $order->recipient_address,
                        $order->recipient_area_name,
                        $order->recipient_zone_name,
                        $order->recipient_city_name,
                    ])->filter()->implode(', '),
                    'city' => $order->recipient_city_name,
                    'zone' => $order->recipient_zone_name,
                    'area' => $order->recipient_area_name,
                    'item_type' => $order->item_type == 1 ? 'Document' : 'Parcel',
                    'delivery_type' => $order->delivery_type == 12 ? 'On Demand' : 'Normal',
                    'amount_to_collect' => $order->amount_to_collect,
                    'delivery_fee' => $order->delivery_fee,
                    'discount' => $order->discount,
                    'status' => $order->status,
                    'payment_status' => $order->payment_status,
                    'created_by_name' => $order->createdBy?->name ?? '-',
                    'created_at' => ($order->courier_created_at ?? $order->created_at)?->format('Y-m-d h:i A'),
                ];
            });

        return [
            ...$paginated->toArray(),
            'grand_total' => [
                'amount_to_collect' => $grandTotal,
                'discount' => $grandTotalDiscount,
            ],
        ];
    }

    /**
     * Public webhook Pathao calls whenever an order's status changes.
     * Registered outside the authenticated route groups (see routes/api.php).
     */
    /**
     * Tracking for one order, in the same shape REDX's returns so the frontend
     * can render any courier's timeline with one component.
     *
     * Pathao's API only reports the *current* status, so the history comes from
     * the webhook events we've recorded; the live status is appended as the
     * most recent entry.
     *
     * @throws GeneralException
     */
    public function track(string $consignmentId)
    {
        $courierOrder = CourierOrder::query()
            ->where('provider', 'pathao')
            ->where('consignment_id', $consignmentId)
            ->first();

        $events = collect($courierOrder?->raw_response['webhook_events'] ?? [])
            ->map(fn($event) => [
                'message' => $event['payload']['order_status'] ?? $event['payload']['status'] ?? null,
                'time'    => $event['payload']['updated_at'] ?? $event['received_at'] ?? null,
            ])
            ->filter(fn($event) => $event['message']);

        // A live read, so the timeline is current even before any webhook lands.
        try {
            $info = $this->pathaoService->getOrderInfo($consignmentId);

            if ($status = $info['order_status'] ?? null) {
                $events->push(['message' => $status, 'time' => now()->toDateTimeString()]);

                $courierOrder?->update(['status' => $status]);
            }
        } catch (Throwable $exception) {
            // A courier being unreachable shouldn't blank the stored history.
            Log::warning('Pathao live status lookup failed: ' . $exception->getMessage());
        }

        return response()->json([
            'data'      => $events->values(),
            'track_url' => $this->pathaoService->trackingUrl($consignmentId),
        ]);
    }

    public function webhook(Request $request)
    {
        $payload = $request->all();

        $secret    = config('services.pathao.webhook_secret');
        $signature = $request->header('x-pathao-signature');

        if (!$secret || !$signature) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        if (!hash_equals((string) $secret, (string) $signature)) {
            Log::warning('Pathao webhook: invalid signature');
            return response()->json(['error' => 'Invalid signature'], 403);
        }

        $consignmentId = $payload['consignment_id'] ?? null;
        $merchantOrderId = $payload['merchant_order_id'] ?? $payload['invoice_id'] ?? null;
        $status = $payload['order_status'] ?? $payload['status'] ?? $payload['order_status_slug'] ?? null;
        $paymentStatus = $payload['billing_status'] ?? $payload['payment_status'] ?? null;
        $discount = $payload['discount'] ?? null;
        $promoDiscount = $payload['promo_discount'] ?? null;

        if (!$consignmentId && !$merchantOrderId) {
            return $this->webhookResponse(['message' => 'Order not found, ignored']);
        }

        $courierOrder = CourierOrder::query()
            ->when($consignmentId, fn($query) => $query->where('consignment_id', $consignmentId))
            ->when(!$consignmentId && $merchantOrderId, fn($query) => $query->where('merchant_order_id', $merchantOrderId))
            ->first();

        if (!$courierOrder) {
            return $this->webhookResponse(['message' => 'Order not found, ignored']);
        }

        $courierOrder->update([
            'status' => $status ?? $courierOrder->status,
            'payment_status' => $paymentStatus ?? $courierOrder->payment_status,
            'delivery_fee' => $payload['delivery_fee'] ?? $courierOrder->delivery_fee,
            'discount' => is_null($discount) && is_null($promoDiscount)
                ? $courierOrder->discount
                : (float) ($discount ?? 0) + (float) ($promoDiscount ?? 0),
            'raw_response' => [
                ...($courierOrder->raw_response ?? []),
                'webhook_events' => [
                    ...(($courierOrder->raw_response['webhook_events'] ?? [])),
                    ['received_at' => now()->toDateTimeString(), 'payload' => $payload],
                ],
            ],
        ]);

        return $this->webhookResponse(['message' => 'Webhook processed']);
    }

    /**
     * Pathao requires every webhook response to carry this header (proving we
     * control the endpoint) and expects a 202 Accepted for processed events.
     */
    private function webhookResponse(array $data, int $status = 202)
    {
        return response()->json($data, $status)->withHeaders([
            'X-Pathao-Merchant-Webhook-Integration-Secret' => config('services.pathao.webhook_integration_secret'),
        ]);
    }

    /**
     * Pull historical orders from Pathao (for the given store) into our local
     * courier_orders table, so orders placed before/outside this integration
     * still show up in the Pathao Orders list.
     *
     * @throws GeneralException
     */
    public function syncOrders(Request $request)
    {
        $data = $request->validate([
            'store_id' => ['required'],
        ]);

        $storeId = (int) $data['store_id'];
        $page = 1;
        $lastPage = 1;
        $imported = 0;
        $updated = 0;
        $maxPages = 200;

        do {
            $result = $this->pathaoService->getOrdersPage($storeId, $page);
            $orders = $result['data'] ?? [];
            $lastPage = $result['last_page'] ?? 1;

            foreach ($orders as $order) {
                $consignmentId = $order['order_consignment_id'] ?? null;
                if (!$consignmentId) {
                    continue;
                }

                $saleId = Sale::query()
                    ->where('full_invoice_number', $order['merchant_order_id'] ?? null)
                    ->value('id');

                $itemType = ($order['item_type'] ?? null) === 'Document' ? 1 : 2;

                $existing = CourierOrder::query()->where('consignment_id', $consignmentId)->first();

                CourierOrder::query()->updateOrCreate(
                    ['consignment_id' => $consignmentId],
                    [
                        'sale_id' => $saleId,
                        'created_by' => $existing->created_by ?? auth()->id(),
                        'store_id' => $storeId,
                        'store_name' => $order['store_name'] ?? $existing->store_name ?? null,
                        'merchant_order_id' => $order['merchant_order_id'] ?? null,
                        'recipient_name' => $order['recipient_name'] ?? '-',
                        'recipient_phone' => $order['recipient_phone'] ?? '-',
                        'recipient_address' => $order['recipient_address'] ?? '-',
                        'item_type' => $itemType,
                        'delivery_type' => $existing->delivery_type ?? 48,
                        'item_quantity' => $existing->item_quantity ?? 1,
                        'item_weight' => $existing->item_weight ?? 0.5,
                        'amount_to_collect' => $order['order_amount'] ?? 0,
                        'status' => $order['order_status'] ?? 'Unknown',
                        'payment_status' => $order['billing_status'] ?? $existing->payment_status ?? null,
                        'delivery_fee' => $order['delivery_fee'] ?? null,
                        'discount' => ($order['discount'] ?? 0) + ($order['promo_discount'] ?? 0),
                        'courier_created_at' => $order['order_created_at'] ?? $existing->pathao_created_at ?? null,
                        'courier_updated_at' => $order['order_updated_at'] ?? $existing->pathao_updated_at ?? null,
                        'raw_response' => [
                            ...($existing->raw_response ?? []),
                            'synced_from_pathao' => $order,
                        ],
                    ]
                );

                $existing ? $updated++ : $imported++;
            }

            $page++;
        } while ($page <= $lastPage && $page <= $maxPages);

        return successResponse("Synced successfully. New: {$imported}, Updated: {$updated}");
    }

    public function stores()
    {
        return response()->json(['data' => $this->pathaoService->getStores()]);
    }

    public function cities()
    {
        return response()->json(['data' => $this->pathaoService->getCities()]);
    }

    public function zones(int $cityId)
    {
        return response()->json(['data' => $this->pathaoService->getZones($cityId)]);
    }

    public function areas(int $zoneId)
    {
        return response()->json(['data' => $this->pathaoService->getAreas($zoneId)]);
    }

    /**
     * @throws Throwable
     * @throws GeneralException
     */
    public function storeOrder(Request $request, Sale $sale)
    {
        $data = $request->validate([
            'store_id' => ['required'],
            'store_name' => ['nullable', 'string', 'max:255'],
            'recipient_name' => ['required', 'string', 'max:255'],
            'recipient_phone' => ['required', 'string', 'max:20'],
            'recipient_address' => ['required', 'string'],
            'recipient_city_id' => ['required'],
            'recipient_city_name' => ['required', 'string'],
            'recipient_zone_id' => ['required'],
            'recipient_zone_name' => ['required', 'string'],
            'recipient_area_id' => ['nullable'],
            'recipient_area_name' => ['nullable', 'string'],
            'item_type' => ['required', 'integer'],
            'delivery_type' => ['required', 'integer'],
            'item_quantity' => ['required', 'integer', 'min:1'],
            'item_weight' => ['required', 'numeric', 'min:0.1'],
            'amount_to_collect' => ['required', 'numeric', 'min:0'],
            'special_instruction' => ['nullable', 'string'],
        ]);


      
        try {
            DB::beginTransaction();

            $response = $this->pathaoService->createOrder([
                'store_id' => (int) $data['store_id'],
                'merchant_order_id' => $sale->full_invoice_number,
                'recipient_name' => $data['recipient_name'],
                'recipient_phone' => $data['recipient_phone'],
                'recipient_address' => $data['recipient_address'],
                'recipient_city' => (int) $data['recipient_city_id'],
                'recipient_zone' => (int) $data['recipient_zone_id'],
                'recipient_area' => $data['recipient_area_id'] ? (int) $data['recipient_area_id'] : null,
                'delivery_type' => $data['delivery_type'],
                'item_type' => $data['item_type'],
                'special_instruction' => $data['special_instruction'] ?? null,
                'item_quantity' => $data['item_quantity'],
                'item_weight' => $data['item_weight'],
                'amount_to_collect' => $data['amount_to_collect'],
            ]);

            $courierOrder = CourierOrder::query()->updateOrCreate(
                ['sale_id' => $sale->id],
                [
                    'created_by' => auth()->id(),
                    'store_id' => $data['store_id'],
                    'store_name' => $data['store_name'] ?? null,
                    'consignment_id' => $response['data']['consignment_id'] ?? null,
                    'merchant_order_id' => $sale->full_invoice_number,
                    'recipient_name' => $data['recipient_name'],
                    'recipient_phone' => $data['recipient_phone'],
                    'recipient_address' => $data['recipient_address'],
                    'recipient_city_id' => $data['recipient_city_id'],
                    'recipient_city_name' => $data['recipient_city_name'],
                    'recipient_zone_id' => $data['recipient_zone_id'],
                    'recipient_zone_name' => $data['recipient_zone_name'],
                    'recipient_area_id' => $data['recipient_area_id'] ?? null,
                    'recipient_area_name' => $data['recipient_area_name'] ?? null,
                    'item_type' => $data['item_type'],
                    'delivery_type' => $data['delivery_type'],
                    'item_quantity' => $data['item_quantity'],
                    'item_weight' => $data['item_weight'],
                    'amount_to_collect' => $data['amount_to_collect'],
                    'special_instruction' => $data['special_instruction'] ?? null,
                    'status' => $response['data']['order_status'] ?? 'pending',
                    'delivery_fee' => $response['data']['delivery_fee'] ?? null,
                    'payment_status' => $response['data']['billing_status'] ?? null,
                    'courier_created_at' => now(),
                    'raw_response' => $response,
                ]
            );

            DB::commit();

            return successResponse('Pathao courier order created successfully', $courierOrder);
        } catch (GeneralException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();
            throw new GeneralException($e->getMessage(), 500);
        }
    }
}
