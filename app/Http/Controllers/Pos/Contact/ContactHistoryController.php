<?php

namespace App\Http\Controllers\Pos\Contact;

use App\Enums\OnlinePaymentStatusEnum;
use App\Enums\StatusEnum;
use App\Http\Controllers\Controller;
use App\Models\Ecommerce\Order\Order;
use App\Models\Pos\Contact\Contact;
use App\Models\Pos\OrderConfirmation\OrderConfirmation;
use App\Models\Pos\Purchase\Purchase;
use App\Models\Pos\PurchaseReturn\PurchaseReturn;
use App\Models\Pos\Sale\Sale;
use App\Models\Pos\SaleReturn\SaleReturn;
use App\Models\Pos\Telesale\Telesale;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * One contact's activity is scattered across seven otherwise-unrelated
 * modules — this merges them into a single chronological "Contact History"
 * timeline, not to own any of that data.
 *
 * Sale/SaleReturn/Purchase/PurchaseReturn are tenant-scoped (TenantScope);
 * Order/Telesale/OrderConfirmation are landlord-global. For an app admin,
 * TenantScope is a no-op, so a contact's history spans every shop — for a
 * shop-level user it naturally narrows to their own tenant, which is the
 * correct behavior there too.
 */
class ContactHistoryController extends Controller
{
    /**
     * All seven sources merged into one chronological feed for the timeline
     * UI. There's no field-level change audit anywhere in this app, so each
     * entry is a single "created" snapshot (invoice, amount, status) rather
     * than a true before/after diff — that would need a dedicated audit-log
     * table capturing every update going forward, which doesn't exist yet.
     *
     * True cross-model pagination (UNION across seven differently-shaped
     * tables) isn't practical, so this over-fetches the latest 200 rows per
     * source, merges + sorts in memory, then paginates the merged list. A
     * single contact's activity realistically never approaches 7×200 rows,
     * so the cap is generous headroom, not a real limit in practice.
     */
    public function timeline(Contact $contact)
    {
        $user = auth()->user();
        $isAdmin = $user->isAppAdmin();
        $can = fn(string $permission) => $isAdmin || $user->can($permission);

        $entries = collect();

        if ($can('view_sales')) {
            $entries = $entries->merge(
                Sale::query()->where('contact_id', $contact->id)
                    ->with(['tenant:id,name', 'createdBy:id,name,profile_photo'])
                    ->orderByDesc('id')->limit(200)->get()
                    ->map(fn(Sale $sale) => $this->entry(
                        'sale-' . $sale->id, 'sale', $sale->created_at,
                        "Sale {$sale->full_invoice_number} created",
                        [
                            ['label' => 'Amount', 'value' => number_format((float) $sale->grand_total, 2)],
                            ['label' => 'Status', 'value' => $this->humanize($sale->status)],
                            ['label' => 'Shop', 'value' => $sale->tenant?->name],
                        ],
                        $sale->createdBy
                    ))
            );
        }

        if ($can('view_sale_returns')) {
            $entries = $entries->merge(
                SaleReturn::query()->where('contact_id', $contact->id)
                    ->with(['tenant:id,name', 'createdBy:id,name,profile_photo'])
                    ->orderByDesc('id')->limit(200)->get()
                    ->map(fn(SaleReturn $return) => $this->entry(
                        'sale_return-' . $return->id, 'sale_return', $return->created_at,
                        "Sale return {$return->full_invoice_number} created",
                        [
                            ['label' => 'Amount', 'value' => number_format((float) $return->grand_total, 2)],
                            ['label' => 'Status', 'value' => $this->humanize($return->status)],
                            ['label' => 'Shop', 'value' => $return->tenant?->name],
                        ],
                        $return->createdBy
                    ))
            );
        }

        if ($can('view_purchases')) {
            $entries = $entries->merge(
                Purchase::query()->where('contact_id', $contact->id)
                    ->with(['tenant:id,name', 'createdBy:id,name,profile_photo'])
                    ->orderByDesc('id')->limit(200)->get()
                    ->map(fn(Purchase $purchase) => $this->entry(
                        'purchase-' . $purchase->id, 'purchase', $purchase->created_at,
                        "Purchase {$purchase->full_invoice_number} created",
                        [
                            ['label' => 'Amount', 'value' => number_format((float) $purchase->grand_total, 2)],
                            ['label' => 'Status', 'value' => $this->humanize($purchase->status)],
                            ['label' => 'Shop', 'value' => $purchase->tenant?->name],
                        ],
                        $purchase->createdBy
                    ))
            );
        }

        if ($can('view_purchase_returns')) {
            $entries = $entries->merge(
                PurchaseReturn::query()->where('contact_id', $contact->id)
                    ->with(['tenant:id,name', 'createdBy:id,name,profile_photo'])
                    ->orderByDesc('id')->limit(200)->get()
                    ->map(fn(PurchaseReturn $return) => $this->entry(
                        'purchase_return-' . $return->id, 'purchase_return', $return->created_at,
                        "Purchase return {$return->full_invoice_number} created",
                        [
                            ['label' => 'Amount', 'value' => number_format((float) $return->grand_total, 2)],
                            ['label' => 'Status', 'value' => $this->humanize($return->status)],
                            ['label' => 'Shop', 'value' => $return->tenant?->name],
                        ],
                        $return->createdBy
                    ))
            );
        }

        if ($can('view_orders')) {
            $entries = $entries->merge(
                Order::query()->where('contact_id', $contact->id)
                    ->orderByDesc('id')->limit(200)->get()
                    ->map(function (Order $order) {
                        $status = StatusEnum::tryFrom($order->status);
                        $paymentStatus = OnlinePaymentStatusEnum::tryFrom($order->payment_status);

                        return $this->entry(
                            'order-' . $order->id, 'order', $order->created_at,
                            "Order {$order->invoice_number} placed",
                            [
                                ['label' => 'Amount', 'value' => number_format((float) ($order->payable_amount ?? $order->grand_total), 2)],
                                ['label' => 'Status', 'value' => $status?->name],
                                ['label' => 'Payment', 'value' => $paymentStatus?->name],
                            ],
                            null
                        );
                    })
            );
        }

        if ($can('view_telesales')) {
            $entries = $entries->merge(
                Telesale::query()->where('contact_id', $contact->id)
                    ->with(['result:id,name', 'createdBy:id,name,profile_photo'])
                    ->orderByDesc('id')->limit(200)->get()
                    ->map(fn(Telesale $telesale) => $this->entry(
                        'telesale-' . $telesale->id, 'telesale', $telesale->created_at,
                        'Telesales call logged',
                        [
                            ['label' => 'Demand', 'value' => $telesale->demand_product],
                            ['label' => 'Media', 'value' => $telesale->media],
                            ['label' => 'Result', 'value' => $telesale->result?->name],
                        ],
                        $telesale->createdBy
                    ))
            );
        }

        if ($can('view_order_confirmations')) {
            $entries = $entries->merge(
                OrderConfirmation::query()->where('contact_id', $contact->id)
                    ->with(['deliveryStatus:id,name', 'createdBy:id,name,profile_photo'])
                    ->orderByDesc('id')->limit(200)->get()
                    ->map(fn(OrderConfirmation $order) => $this->entry(
                        'order_confirmation-' . $order->id, 'order_confirmation', $order->created_at,
                        'Order confirmation ' . ($order->invoice_no ?: '#' . $order->id) . ' created',
                        [
                            ['label' => 'Product', 'value' => $order->product_name],
                            ['label' => 'Amount', 'value' => number_format((float) $order->selling_price, 2)],
                            ['label' => 'Delivery', 'value' => $order->deliveryStatus?->name ?? ($order->is_confirmed ? 'Confirmed' : 'Pending')],
                        ],
                        $order->createdBy
                    ))
            );
        }

        $sorted = $entries->sortByDesc('timestamp')->values();

        $perPage = (int) request('per_page', 20);
        $page = (int) request('page', 1);

        $paginator = new LengthAwarePaginator(
            $sorted->forPage($page, $perPage)->values(),
            $sorted->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return response()->json([
            'contact' => [
                'id' => $contact->id,
                'name' => $contact->name,
                'business_name' => $contact->business_name,
                'phone' => $contact->phone,
                'email' => $contact->email,
                'avatar' => $contact->profile_picture,
            ],
            ...$paginator->toArray(),
        ]);
    }

    private function entry(string $id, string $type, $timestamp, string $title, array $meta, $actor): array
    {
        return [
            'id' => $id,
            'type' => $type,
            'timestamp' => $timestamp,
            'title' => $title,
            'meta' => array_values(array_filter($meta, fn($item) => filled($item['value'] ?? null))),
            'actor' => $actor ? ['name' => $actor->name, 'avatar' => $actor->profile_photo] : null,
        ];
    }

    private function humanize(?string $value): ?string
    {
        return $value ? ucwords(str_replace('_', ' ', $value)) : null;
    }
}
