<?php

namespace App\Http\Controllers\Pos\Courier;

use App\Exceptions\GeneralException;
use App\Filters\Pos\Courier\CourierOrderFilter;
use App\Http\Controllers\Controller;
use App\Models\Pos\Courier\CourierOrder;
use App\Models\Pos\Sale\Sale;
use App\Services\Courier\RedxService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class RedxController extends Controller
{
    private const PROVIDER = 'redx';

    public function __construct(protected RedxService $redxService, protected CourierOrderFilter $filter) {}

    /**
     * REDX's areas double as its district/zone list, so one endpoint feeds the
     * whole address picker.
     *
     * @throws GeneralException
     */
    public function areas()
    {
        $areas = collect($this->redxService->getAreas())
            ->map(fn($area) => [
                'id'            => $area['id'],
                'name'          => $area['name'],
                'post_code'     => $area['post_code'] ?? null,
                'district_name' => $area['district_name'] ?? null,
                'division_name' => $area['division_name'] ?? null,
                'zone_id'       => $area['zone_id'] ?? null,
            ])
            ->values();

        return response()->json(['data' => $areas]);
    }

    /**
     * @throws GeneralException
     */
    public function stores()
    {
        $stores = collect($this->redxService->getPickupStores())
            ->map(fn($store) => [
                'id'        => $store['id'],
                'name'      => $store['name'],
                'address'   => $store['address'] ?? null,
                'area_name' => $store['area_name'] ?? null,
                'phone'     => $store['phone'] ?? null,
            ])
            ->values();

        return response()->json(['data' => $stores]);
    }

    public function index()
    {
        $perPage = request('per_page', 10);

        $baseQuery = CourierOrder::query()
            ->where('provider', self::PROVIDER)
            ->filters($this->filter);

        $grandTotal = (clone $baseQuery)->sum('amount_to_collect');

        $paginated = $baseQuery
            ->with(['sale:id,full_invoice_number', 'createdBy:id,name'])
            ->orderByRaw('COALESCE(courier_created_at, created_at) DESC')
            ->paginate($perPage)
            ->through(fn(CourierOrder $order) => [
                'id'                  => $order->id,
                'invoice_number'      => $order->sale?->full_invoice_number ?? '-',
                'sale_id'             => $order->sale_id,
                'tracking_code'       => $order->tracking_code,
                'track_url'           => $order->track_url,
                'store_name'          => $order->store_name,
                'recipient_name'      => $order->recipient_name,
                'recipient_phone'     => $order->recipient_phone,
                'address'             => collect([$order->recipient_address, $order->recipient_area_name, $order->recipient_district])->filter()->implode(', '),
                'recipient_area_name' => $order->recipient_area_name,
                'amount_to_collect'   => $order->amount_to_collect,
                'item_description'    => $order->item_description,
                'item_weight'         => $order->item_weight,
                'status'              => $order->status,
                'special_instruction' => $order->special_instruction,
                'created_by_name'     => $order->createdBy?->name ?? '-',
                'created_at'          => $order->created_at?->format('d M Y, h:i A'),
            ]);

        return response()->json([
            ...$paginated->toArray(),
            'grand_total' => ['amount_to_collect' => round((float) $grandTotal, 2)],
        ]);
    }

    /**
     * @throws GeneralException
     * @throws Throwable
     */
    public function storeOrder(Request $request, Sale $sale)
    {
        $data = $request->validate([
            'recipient_name'      => ['required', 'string', 'max:100'],
            'recipient_phone'     => ['required', 'string', 'max:20'],
            'recipient_address'   => ['required', 'string', 'max:250'],
            'delivery_area_id'    => ['required', 'integer'],
            'delivery_area'       => ['required', 'string', 'max:100'],
            'pickup_store_id'     => ['nullable', 'integer'],
            'amount_to_collect'   => ['required', 'numeric', 'min:0'],
            'item_description'    => ['nullable', 'string', 'max:255'],
            'item_weight'         => ['nullable', 'numeric', 'min:0'],
            'value'               => ['nullable', 'numeric', 'min:0'],
            'is_closed_box'       => ['nullable', 'boolean'],
            'special_instruction' => ['nullable', 'string'],
        ]);

        // Falls back to the configured store so the common case needs no input.
        $pickupStoreId = $data['pickup_store_id'] ?? config('services.redx.pickup_store_id');

        if (!$pickupStoreId) {
            throw new GeneralException('No REDX pickup store configured. Set REDX_PICKUP_STORE_ID or pass pickup_store_id.', 422);
        }

        try {
            DB::beginTransaction();

            $response = $this->redxService->createParcel([
                'customer_name'         => $data['recipient_name'],
                'customer_phone'        => $data['recipient_phone'],
                'customer_address'      => $data['recipient_address'],
                'delivery_area'         => $data['delivery_area'],
                'delivery_area_id'      => $data['delivery_area_id'],
                'merchant_invoice_id'   => $sale->full_invoice_number,
                // REDX expects the collectable amount as a string.
                'cash_collection_amount' => (string) $data['amount_to_collect'],
                // Grams. Half a kilo is the usual default for small parcels.
                'parcel_weight'         => (int) (($data['item_weight'] ?? 0.5) * 1000),
                'value'                 => (float) ($data['value'] ?? $data['amount_to_collect']),
                'is_closed_box'         => (bool) ($data['is_closed_box'] ?? true),
                'instruction'           => $data['special_instruction'] ?? null,
                'pickup_store_id'       => (int) $pickupStoreId,
                'parcel_details_json'   => [[
                    'name'     => $data['item_description'] ?? ('Order ' . $sale->full_invoice_number),
                    'category' => 'general',
                    'value'    => (float) ($data['value'] ?? $data['amount_to_collect']),
                ]],
            ]);

            // The tracking id is REDX's only handle on the parcel — without it
            // the order can never be tracked again.
            $trackingId = $response['tracking_id'] ?? null;

            if (!$trackingId) {
                throw new GeneralException('REDX did not return a tracking id.', 422);
            }

            $courierOrder = CourierOrder::query()->updateOrCreate(
                ['sale_id' => $sale->id, 'provider' => self::PROVIDER],
                [
                    'created_by'          => auth()->id(),
                    'provider'            => self::PROVIDER,
                    'store_id'            => $pickupStoreId,
                    'tracking_code'       => $trackingId,
                    'merchant_order_id'   => $sale->full_invoice_number,
                    'recipient_name'      => $data['recipient_name'],
                    'recipient_phone'     => $data['recipient_phone'],
                    'recipient_address'   => $data['recipient_address'],
                    'recipient_area_id'   => $data['delivery_area_id'],
                    'recipient_area_name' => $data['delivery_area'],
                    'amount_to_collect'   => $data['amount_to_collect'],
                    'item_description'    => $data['item_description'] ?? null,
                    'item_weight'         => $data['item_weight'] ?? 0.5,
                    'special_instruction' => $data['special_instruction'] ?? null,
                    'status'              => 'pending',
                ]
            );

            DB::commit();

            return successResponse('REDX parcel created successfully', [
                'tracking_code' => $trackingId,
                'track_url'     => $this->redxService->trackingUrl($trackingId),
                'courier_order' => $courierOrder->only(['id', 'sale_id', 'tracking_code', 'status']),
            ]);
        } catch (Throwable $exception) {
            DB::rollBack();
            Log::error('REDX parcel creation failed: ' . $exception->getMessage());

            throw $exception;
        }
    }

    /**
     * Parcel status callbacks from REDX.
     *
     * REDX carries no auth header — per their docs the credential goes in the
     * callback URL's query string (`?token=...`), so that is what's checked. A
     * Bearer header is also accepted in case they ever add one; both compare
     * against the same secret, so accepting either costs nothing.
     *
     * Documented payload:
     *   tracking_number, timestamp, status, message_en, message_bn,
     *   invoice_number, delivery_type
     *
     * Kept forgiving about missing fields: a callback carrying only a status
     * must still be applied rather than rejected wholesale, which is how
     * Steadfast's handler used to drop legitimate updates.
     */
    public function webhook(Request $request)
    {
        $expectedToken = config('services.redx.webhook_bearer_token');

        $token = $request->query('token')
            ?? str_replace('Bearer ', '', (string) $request->header('Authorization'));

        // Fails closed: with no token configured there is no way to tell a real
        // callback from anyone who found the URL.
        if (!$expectedToken || !$token || !hash_equals($expectedToken, (string) $token)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $payload = $request->all();

        // `tracking_number` and `invoice_number` are what the docs specify; the
        // rest are tolerated spellings seen in the wild.
        $trackingId = $payload['tracking_number']
            ?? $payload['trackingId']
            ?? $payload['tracking_id']
            ?? null;

        $merchantOrderId = $payload['invoice_number']
            ?? $payload['invoiceNumber']
            ?? $payload['merchantOrderId']
            ?? $payload['merchant_order_id']
            ?? null;

        $status = $payload['status'] ?? null;

        if (!$trackingId && !$merchantOrderId) {
            Log::warning('REDX webhook: no tracking_number or invoice_number, cannot identify parcel', ['payload' => $payload]);

            return response()->json(['error' => 'Missing tracking_number or invoice_number'], 400);
        }

        $courierOrder = CourierOrder::query()
            ->where('provider', self::PROVIDER)
            ->when($trackingId, fn($query) => $query->where('tracking_code', $trackingId))
            ->when(!$trackingId && $merchantOrderId, fn($query) => $query->where('merchant_order_id', $merchantOrderId))
            ->first();

        // 202, not 404: an unknown parcel is not REDX's fault to retry over.
        if (!$courierOrder) {
            Log::info('REDX webhook: no matching order, ignored', ['tracking_id' => $trackingId, 'merchant_order_id' => $merchantOrderId]);

            return response()->json(['message' => 'Order not found, ignored'], 202);
        }

        $courierOrder->update([
            'status' => $status ?? $courierOrder->status,
            'raw_response' => [
                ...($courierOrder->raw_response ?? []),
                'webhook_events' => [
                    ...($courierOrder->raw_response['webhook_events'] ?? []),
                    [
                        'received_at' => now()->toDateTimeString(),
                        // Pulled out of the payload so a timeline can be built
                        // without re-parsing every raw event.
                        'status'      => $status,
                        'message_en'  => $payload['message_en'] ?? null,
                        'message_bn'  => $payload['message_bn'] ?? null,
                        'timestamp'   => $payload['timestamp'] ?? null,
                        'payload'     => $payload,
                    ],
                ],
            ],
        ]);

        return response()->json(['message' => 'Webhook processed'], 202);
    }

    /**
     * @throws GeneralException
     */
    public function track(string $trackingId)
    {
        $events = collect($this->redxService->trackParcel($trackingId))
            ->map(fn($event) => [
                'message'    => $event['message_en'] ?? null,
                'message_bn' => $event['message_bn'] ?? null,
                'time'       => $event['time'] ?? null,
            ])
            ->values();

        return response()->json([
            'data'      => $events,
            'track_url' => $this->redxService->trackingUrl($trackingId),
        ]);
    }
}
