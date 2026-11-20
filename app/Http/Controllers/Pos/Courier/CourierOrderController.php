<?php

namespace App\Http\Controllers\Pos\Courier;

use App\Filters\Pos\Courier\CourierOrderFilter;
use App\Http\Controllers\Controller;
use App\Models\Pos\Courier\CourierOrder;

class CourierOrderController extends Controller
{
    public function __construct(protected CourierOrderFilter $filter) {}

    /**
     * Combined list across every courier provider (Pathao, Steadfast, ...),
     * filterable by provider. Provider-specific lists (with their own
     * provider-only columns) still live under PathaoController/SteadfastController.
     */
    public function index()
    {
        $perPage = request('per_page', 10);

        $baseQuery = CourierOrder::query()->filters($this->filter);

        $grandTotal = (clone $baseQuery)->sum('amount_to_collect');

        $paginated = $baseQuery
            ->with(['sale:id,full_invoice_number', 'createdBy:id,name'])
            ->orderByRaw('COALESCE(courier_created_at, created_at) DESC')
            ->paginate($perPage)
            ->through(function ($order) {
                return [
                    'id' => $order->id,
                    'provider' => $order->provider,
                    'invoice_number' => $order->sale?->full_invoice_number ?? '-',
                    'sale_id' => $order->sale_id,
                    'store_name' => $order->store_name,
                    'consignment_id' => $order->consignment_id,
                    'tracking_code' => $order->tracking_code,
                    'track_url' => $order->track_url,
                    'recipient_name' => $order->recipient_name,
                    'recipient_phone' => $order->recipient_phone,
                    'address' => collect([
                        $order->recipient_address,
                        $order->recipient_area_name,
                        $order->recipient_zone_name,
                        $order->recipient_city_name,
                        $order->recipient_thana,
                        $order->recipient_district,
                    ])->filter()->implode(', '),
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
            ],
        ];
    }
}
