<?php

namespace App\Http\Controllers\Ecommerce\Order;

use App\Enums\OnlinePaymentStatusEnum;
use App\Enums\StatusEnum;
use App\Exceptions\GeneralException;
use App\Exceptions\OrderValidationException;
use App\Filters\Ecommerce\Order\OrderFilter;
use App\Helpers\Traits\S3ToLocalPathTrait;
use App\Http\Controllers\Controller;
use App\Models\Ecommerce\Order\Order;
use App\Models\Pos\Contact\Contact;
use App\Models\Pos\Product\ProductSerial;
use App\Models\Pos\Product\ProductVariant;
use App\Models\Product\GiftProductVariant;
use App\Scopes\TenantScope;
use App\Services\Pos\Report\ProfitLoss\ProfitLossSummaryService;
use App\Services\Pos\Sale\SaleService;
use App\Services\SSCommerz\SSLCommerzPaymentService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Milon\Barcode\DNS1D;

class OrderController extends Controller
{
    use S3ToLocalPathTrait;

    public function __construct(protected OrderFilter $filter, protected SaleService $saleService) {}

    public function index(Request $request): LengthAwarePaginator
    {
        $status = $request->get('status');
        $perPage = $request->get('per_page', 10);

        $statusEnum = $this->getStatusEnum($status);

        return Order::query()
            ->filters($this->filter)
            ->with('customer:id,name,phone')
            ->with('tenant:id,name')
            ->with([
                'items' => function ($query) {
                    $query->select('id', 'order_id', 'product_price', 'discount_amount', 'discount_type', 'product_variant_id', 'gift_product_variant_id', 'quantity')
                        ->with([
                            'variant' => fn ($q) => $q->select('id', 'product_id', 'sku'),
                            'variant.product' => fn ($q) => $q->select('id', 'product_name'),
                            'giftProductVariant' => fn ($q) => $q->select('id', 'product_id', 'product_variant_id', 'qty')
                                ->with([
                                    'product:id,product_name',
                                    'productVariant:id,sku',
                                ]),
                        ]);
                },
            ])
            ->when($statusEnum, fn ($query) => $query->where('status', $statusEnum->value))
            ->orderByDesc('id')
            ->paginate($perPage)
            ->through(fn ($order) => $this->transformOrder($order));
    }

    public function orderDetail(Order $order)
    {
        $pdf = $this->generatePdf($order);

        if (request()->has('preview')) {
            return response()->make($pdf->stream(), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.$order->invoice_number.'.pdf"',
            ]);
        }

        return $pdf->download("{$order->invoice_number}.pdf");
    }

    public function orderInvoiceData(Order $order): array
    {
        $order->load([
            'customer:id,name,phone,email',
            'shippingAddress',
            'area.district',
            'items' => function ($query) {
                $query->with([
                    'preOwned.attribute.attributeValue',
                    'product:id,product_name,product_image,warranty_day',
                    'variant.attributeValues',
                    'variant.attributeValues.attribute',
                    'giftProductVariant' => function ($query) {
                        $query->with(
                            'product:id,product_name,product_image',
                            'productVariant.attributeValues',
                        );
                    },
                    'productCare' => function ($query) {
                        $query->with('care:id,name,value');
                    },
                    'preOwnedCare' => function ($query) {
                        $query->with('care:id,name,value');
                    },
                ])->withCount('giftProductVariant as total_gift_quantity');
            },
        ]);

        $totalDiscount = $order->items->sum(fn ($item) => (float) $item->discount_amount * $item->quantity);
        $totalUnitPrice = $order->items->sum(fn ($item) => $item->product_price * $item->quantity);
        $totalCareCharge = $order->items->sum(function ($item) {
            $chargeValue = $item->productCare ? ($item->productCare->care?->value ?? 0) : 0;

            return ($item->product_price * $item->quantity) * ($chargeValue / 100);
        });
        $totalPreOwnedCharge = $order->items->sum(function ($item) {
            $chargeValue = $item->preOwnedCare ? ($item->preOwnedCare->care?->value ?? 0) : 0;

            return ($item->product_price * $item->quantity) * ($chargeValue / 100);
        });
        $totalPurchasePoint = $order->items->sum(fn ($item) => ($item->product?->purchase_point ?? 0) * $item->quantity);
        $totalPreOwnedPoint = $order->items->sum(fn ($item) => ($item->preOwned?->purchase_point ?? 0) * $item->quantity);
        $totalQuantity = $order->items->sum('quantity');
        $totalGiftQuantity = $order->items->sum('total_gift_quantity');

        $items = $order->items->map(function ($item) {
            $attributes = [];
            if ($item->preOwned) {
                if ($item->preOwned->battery_health) {
                    $attributes[] = $item->preOwned->battery_health.' %';
                }
                foreach ($item->preOwned->attribute as $preAttr) {
                    $attributes[] = $preAttr->attributeValue->value;
                }
            } elseif ($item->variant?->attributeValues) {
                foreach ($item->variant->attributeValues as $attrValue) {
                    $attributes[] = $attrValue->value;
                }
            }

            $gift = null;
            if ($item->giftProductVariant && $item->giftProductVariant->product) {
                $giftAttrs = [];
                foreach ($item->giftProductVariant->productVariant?->attributeValues ?? [] as $gAttr) {
                    $giftAttrs[] = $gAttr->value;
                }
                $gift = [
                    'product_name' => $item->giftProductVariant->product->product_name,
                    'product_image' => to_cdn_url($item->giftProductVariant->product->product_image),
                    'attributes' => $giftAttrs,
                    'quantity' => $item->quantity,
                ];
            }

            return [
                'product_name' => Str::limit($item->product?->product_name, 50),
                'product_image' => to_cdn_url($item->product?->product_image),
                'warranty_day' => $item->product?->warranty_day,
                'attributes' => $attributes,
                'quantity' => $item->quantity,
                'product_price' => $item->product_price,
                'discount_amount' => $item->discount_amount * $item->quantity,
                'line_total' => ($item->product_price * $item->quantity) - ($item->discount_amount * $item->quantity),
                'product_care' => $item->productCare ? $item->productCare->care?->name : null,
                'pre_owned_care' => $item->preOwnedCare ? $item->preOwnedCare->care?->name : null,
                'gift' => $gift,
            ];
        });

        $address = $order->delivery_address;

        return [
            'barcode' => 'data:image/png;base64,'.(new DNS1D)->getBarcodePNG($order->invoice_number, 'C128'),
            'logo' => to_cdn_url('https://salamat-innovation.s3.ap-south-1.amazonaws.com/575/TEKZO-Logo-1000x500.png'),
            'order' => [
                'invoice_number' => $order->invoice_number,
                'date' => $order->created_at->format('Y-m-d H:i'),
                'grand_total' => $order->grand_total,
                'payable_amount' => $order->payable_amount,
                'discount_total' => $order->discount_total,
                'shipping_charge' => $order->shipping_charge,
                'due' => $order->grand_total - $order->payable_amount,
            ],
            'customer' => [
                'name' => $order->customer?->name,
                'phone' => $order->customer?->phone,
                'email' => $order->customer?->email,
                'address' => $address,
            ],
            'items' => $items,
            'total_unit_price' => $totalUnitPrice,
            'total_discount' => $totalDiscount + $order->discount_total,
            'total_care_charge' => $totalCareCharge + $totalPreOwnedCharge,
            'total_purchase_point' => $totalPurchasePoint + $totalPreOwnedPoint,
            'total_quantity' => $totalQuantity,
            'total_gift_quantity' => $totalGiftQuantity,
        ];
    }

    protected function generatePdf($order)
    {
        $order->load([
            'customer:id,name,phone,email',
            'shippingAddress',
            'area.district',
            'items' => function ($query) {
                $query->with([
                    'preOwned.attribute.attributeValue',
                    'product:id,product_name,product_image,warranty_day',
                    'variant.attributeValues',
                    'variant.attributeValues.attribute',
                    'giftProductVariant' => function ($query) {
                        $query->with(
                            'product:id,product_name,product_image',
                            'productVariant.attributeValues',
                        );
                    },
                    'productCare' => function ($query) {
                        $query->with('care:id,name,value');
                    },
                    'preOwnedCare' => function ($query) {
                        $query->with('care:id,name,value');
                    },
                ])->withCount('giftProductVariant as total_gift_quantity');
            },
        ]);

        $totalDiscount = $order->items->sum(fn ($item) => (float) $item->discount_amount * $item->quantity ?? 0);

        $totalUnitPrice = $order->items->sum(fn ($item) => $item->product_price * $item->quantity);

        $totalCareCharge = $order->items->sum(function ($item) {
            $chargeValue = $item->productCare ? ($item->productCare->care?->value ?? 0) : 0;

            return ($item->product_price * $item->quantity) * ($chargeValue / 100);
        });

        $totalPreOwnedCharge = $order->items->sum(function ($item) {
            $chargeValue = $item->preOwnedCare ? ($item->preOwnedCare->care?->value ?? 0) : 0;

            return ($item->product_price * $item->quantity) * ($chargeValue / 100);
        });

        $totalPurchasePoint = $order->items->sum(function ($item) {
            $point = $item->product ? ($item->product->purchase_point ?? 0) : 0;

            return $point * $item->quantity;
        });

        $totalPreOwnedPoint = $order->items->sum(function ($item) {
            $point = $item->preOwned ? ($item->preOwned->purchase_point ?? 0) : 0;

            return $point * $item->quantity;
        });

        $data = [
            'taka' => public_path('assets/images/taka.png'),
            'barcode' => 'data:image/png;base64,'.(new DNS1D)->getBarcodePNG($order->invoice_number, 'C128'),
            'totalUnitPrice' => $totalUnitPrice,
            'totalDiscount' => $totalDiscount + $order->discount_total,
            'totalCareCharge' => $totalCareCharge + $totalPreOwnedCharge,
            'totalPurchasePoint' => $totalPurchasePoint + $totalPreOwnedPoint,
            'order' => $order,
            'customer' => $order->customer,
            'items' => $order->items,
            'logo' => to_cdn_url('https://salamat-innovation.s3.ap-south-1.amazonaws.com/575/TEKZO-Logo-1000x500.png'),
        ];

        return Pdf::loadView('ecommerce.pdf.order_invoice', $data)
            ->setPaper('a4', 'portrait')
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', true);
    }

    // do edit and update methods here
    public function edit(Order $order)
    {
        $order->load('customer:id,name,phone,email', 'items.product', 'items.variant.attributeValues.attribute');

        return response()->json([
            'order' => $order,
        ]);
    }

    public function update(Request $request, Order $order)
    {
        // Validate main order data
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'shipping_address' => 'nullable|string',
            'billing_address' => 'nullable|string',

            // Validate items array and its structure
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.discount_type' => 'nullable|in:fixed,percent',
            'items.*.discount_amount' => 'nullable|numeric|min:0',
        ]);

        // Update order fields
        // $order->customer_id = $request->input('customer_id');
        // $order->shipping_address = $request->input('shipping_address', $order->shipping_address);
        // $order->billing_address = $request->input('billing_address', $order->billing_address);
        // $order->save();

        // Update each order item discount info
        $itemsData = $request->input('items');

        foreach ($itemsData as $itemData) {
            $query = $order->items()->where('product_id', $itemData['product_id']);

            if (! empty($itemData['variant_id'])) {
                $query->where('product_variant_id', $itemData['variant_id']);
            }

            $orderItem = $query->first();

            if ($orderItem) {
                $orderItem->discount_type = $itemData['discount_type'] ?? null;
                $orderItem->discount_value = $itemData['discount_value'] ?? 0;
                $orderItem->discount_amount = $itemData['discount_amount'] ?? 0;
                $orderItem->save();
            }
        }

        return response()->json([
            'message' => 'Order and discounts updated successfully',
            'order' => $order->load('items'),
        ]);
    }

    /**
     * Update one line of an already-placed order — product/variant, quantity,
     * unit price, and a flat per-unit discount. Used both for swaps (e.g. a
     * customer exchanging a white charger for the black one) and for
     * quantity/price/discount corrections (e.g. a customer who accidentally
     * ordered 2 and wants 1, or asks for a bigger discount after ordering).
     * Only allowed while the order hasn't shipped yet.
     *
     * Only a flat (fixed) discount is supported here, not a stored percent —
     * matching how discount_amount is used everywhere else in this codebase
     * (sale_items, purchase_items): always a pre-computed currency value, not
     * derived from a percent at read time. discount_amount below is PER UNIT,
     * consistent with approveOrderWithSerials() which subtracts it directly
     * from product_price per serial (line ~663).
     */
    public function updateItemVariant(Request $request, Order $order)
    {
        // Order::status isn't int-cast on the model, so it comes back as a
        // string from the DB — compare loosely (or cast) rather than with
        // strict in_array, which would always fail against the enum's ints.
        if (! in_array((int) $order->status, [StatusEnum::Pending->value, StatusEnum::Approved->value, StatusEnum::Processing->value], true)) {
            return errorResponse('This order can no longer be updated.');
        }

        $validated = $request->validate([
            'order_list_id' => ['required', 'integer'],
            'product_id' => ['required', 'exists:products,id'],
            'product_variant_id' => ['required', 'exists:product_variants,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'product_price' => ['required', 'numeric', 'min:0'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $orderItem = $order->items()->whereKey($validated['order_list_id'])->first();

        if (! $orderItem) {
            return errorResponse('Order item not found.');
        }

        $variant = ProductVariant::query()
            ->where('id', $validated['product_variant_id'])
            ->where('product_id', $validated['product_id'])
            ->first();

        if (! $variant) {
            return errorResponse('Selected variant does not belong to the selected product.');
        }

        // if ($variant->stock != '1') {
        //     return errorResponse('Selected variant does not have enough stock for the ordered quantity.');
        // }

        $quantity = (int) $validated['quantity'];
        $productPrice = (float) $validated['product_price'];
        $discountAmount = (float) ($validated['discount_amount'] ?? 0);

        if ($discountAmount > $productPrice) {
            return errorResponse('Discount cannot be greater than the unit price.');
        }

        // Reconstructed from the item's CURRENT (pre-edit) fields rather than
        // read from final_price/discount_total — those two columns are never
        // populated at order creation (checkout only ever writes
        // product_price/quantity/discount_amount), so they'd read as 0 and
        // make every first edit look like a brand-new addition to the order
        // total instead of a correction to the existing line.
        $oldLineTotal = ((float) $orderItem->product_price - (float) ($orderItem->discount_amount ?? 0)) * (int) $orderItem->quantity;
        $newLineTotal = ($productPrice - $discountAmount) * $quantity;

        $orderItem->update([
            'product_id' => $variant->product_id,
            'product_variant_id' => $variant->id,
            'quantity' => $quantity,
            'product_price' => $productPrice,
            'discount_type' => $discountAmount > 0 ? 'fixed' : 'none',
            'discount_value' => $discountAmount,
            'discount_amount' => $discountAmount,
            'discount_total' => $discountAmount * $quantity,
            'final_price' => $newLineTotal,
        ]);

        $this->shiftOrderTotals($order, $newLineTotal - $oldLineTotal);

        return successResponse('Order item updated successfully');
    }

    /**
     * Applies a line-item total change straight to the order's own totals
     * without touching coupon/shipping/payment fields — those live in a
     * separate checkout formula (CartService, in the storefront API repo)
     * this controller has no access to reproduce exactly. Shifting by the
     * delta keeps whatever coupon/shipping adjustment was already baked into
     * grand_total intact, rather than risking recomputing it wrong.
     */
    /**
     * Adds one or more products to an order that's already been placed — the
     * customer who ordered A and, on the confirmation call, asks for B and C
     * as well.
     *
     * Deliberately a sibling of updateItemVariant() rather than a branch inside
     * it: same status gate, same price/discount rules, same totals shift, but
     * new lines instead of an edit to an existing one.
     *
     * Every line is checked before anything is written, and the whole batch
     * shares one transaction — a bad row can't leave the order half-updated
     * with totals that no longer match its items.
     */
    public function addItem(Request $request, Order $order)
    {
        // Order::status isn't int-cast on the model, so compare loosely.
        if (! in_array((int) $order->status, [StatusEnum::Pending->value, StatusEnum::Approved->value, StatusEnum::Processing->value], true)) {
            return errorResponse('This order can no longer be updated.');
        }

        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.product_variant_id' => ['required', 'exists:product_variants,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.product_price' => ['required', 'numeric', 'min:0'],
            'items.*.discount_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $rows = [];
        $delta = 0.0;

        foreach ($validated['items'] as $index => $item) {
            $variant = ProductVariant::query()
                ->where('id', $item['product_variant_id'])
                ->where('product_id', $item['product_id'])
                ->first();

            // 1-based so the message matches what the agent sees on screen.
            $line = $index + 1;

            if (! $variant) {
                return errorResponse("Line {$line}: selected variant does not belong to the selected product.");
            }

            $quantity = (int) $item['quantity'];
            $productPrice = (float) $item['product_price'];
            $discountAmount = (float) ($item['discount_amount'] ?? 0);

            if ($discountAmount > $productPrice) {
                return errorResponse("Line {$line}: discount cannot be greater than the unit price.");
            }

            $lineTotal = ($productPrice - $discountAmount) * $quantity;
            $delta += $lineTotal;

            $rows[] = [
                'product_id' => $variant->product_id,
                'product_variant_id' => $variant->id,
                'quantity' => $quantity,
                'product_price' => $productPrice,
                'discount_type' => $discountAmount > 0 ? 'fixed' : 'none',
                'discount_value' => $discountAmount,
                'discount_amount' => $discountAmount,
                'discount_total' => $discountAmount * $quantity,
                'final_price' => $lineTotal,
                // Matches what checkout writes for a live line.
                'status' => 1,
            ];
        }

        DB::transaction(function () use ($order, $rows, $delta) {
            foreach ($rows as $row) {
                $order->items()->create($row);
            }

            $this->shiftOrderTotals($order, $delta);
        });

        $count = count($rows);

        return successResponse($count === 1
            ? 'Product added to the order successfully'
            : "{$count} products added to the order successfully");
    }

    /**
     * Removes a line from a placed order and takes its value back off the
     * totals — the other half of addItem(), for when the customer drops a
     * product on the confirmation call.
     *
     * Refuses to empty an order: an order with no items has nothing to fulfil
     * or invoice, and cancelling is a status change, not a line edit.
     */
    public function deleteItem(Order $order, int $itemId)
    {
        if (! in_array((int) $order->status, [StatusEnum::Pending->value, StatusEnum::Approved->value, StatusEnum::Processing->value], true)) {
            return errorResponse('This order can no longer be updated.');
        }

        $orderItem = $order->items()->whereKey($itemId)->first();

        if (! $orderItem) {
            return errorResponse('Order item not found.');
        }

        if ($order->items()->count() <= 1) {
            return errorResponse('An order must keep at least one product. Cancel the order instead.');
        }

        // Reconstructed from the item's own fields for the same reason
        // updateItemVariant() does: final_price is never populated at checkout,
        // so it would read as 0 and refund nothing.
        $lineTotal = ((float) $orderItem->product_price - (float) ($orderItem->discount_amount ?? 0))
            * (int) $orderItem->quantity;

        DB::transaction(function () use ($order, $orderItem, $lineTotal) {
            $orderItem->delete();

            $this->shiftOrderTotals($order, -$lineTotal);
        });

        return successResponse('Product removed from the order successfully');
    }

    private function shiftOrderTotals(Order $order, float $delta): void
    {
        if ($delta === 0.0) {
            return;
        }

        $order->sub_total = max((float) ($order->sub_total ?? 0) + $delta, 0);
        $order->grand_total = max((float) ($order->grand_total ?? 0) + $delta, 0);
        $order->due_amount = max((float) ($order->due_amount ?? 0) + $delta, 0);
        $order->save();
    }

    /**
     * Swap the free gift attached to one line of an already-placed order —
     * lets an admin upgrade/change the *variant* (e.g. color) of the gift
     * product a customer already received, mirroring updateItemVariant()
     * above. Only allowed while the order hasn't shipped yet, and only into
     * another variant of the SAME gift product (not a different gift
     * product entirely — a GiftProduct config can list several distinct
     * gift products as options, but that's a separate concern from
     * upgrading which variant of the awarded one was picked).
     */
    public function updateGiftItem(Request $request, Order $order)
    {
        if (! in_array((int) $order->status, [StatusEnum::Pending->value, StatusEnum::Approved->value, StatusEnum::Processing->value], true)) {
            return errorResponse('This order can no longer be updated.');
        }

        $validated = $request->validate([
            'order_list_id' => ['required', 'integer'],
            'product_variant_id' => ['required', 'integer', 'exists:product_variants,id'],
        ]);

        $orderItem = $order->items()->whereKey($validated['order_list_id'])->first();

        if (! $orderItem) {
            return errorResponse('Order item not found.');
        }

        $currentGiftVariant = $orderItem->giftProductVariant;

        if (! $currentGiftVariant) {
            return errorResponse('This order item has no gift item to update.');
        }

        $newVariant = ProductVariant::query()
            ->where('id', $validated['product_variant_id'])
            ->where('product_id', $currentGiftVariant->product_id)
            ->first();

        if (! $newVariant) {
            return errorResponse('Selected variant does not belong to the current gift product.');
        }

        $giftProductVariant = GiftProductVariant::query()->firstOrCreate(
            [
                'gift_product_id' => $currentGiftVariant->gift_product_id,
                'product_id' => $currentGiftVariant->product_id,
                'product_variant_id' => $newVariant->id,
            ],
            [
                'qty' => 1,
            ]
        );

        $orderItem->update([
            'gift_product_id' => $currentGiftVariant->gift_product_id,
            'gift_product_variant_id' => $giftProductVariant->id,
        ]);

        return successResponse('Gift item updated successfully');
    }

    /**
     * Map string status to StatusEnum (case-insensitive)
     */
    private function getStatusEnum(?string $status): ?StatusEnum
    {
        return match ($status) {
            'pending' => StatusEnum::Pending,
            'confirmed' => StatusEnum::Confirmed,
            'hold' => StatusEnum::Hold,
            'rejected' => StatusEnum::Rejected,
            'approved' => StatusEnum::Approved,
            'processing' => StatusEnum::Processing,
            'onway' => StatusEnum::OnWay,
            'complete' => StatusEnum::Complete,
            'return' => StatusEnum::Return,
            default => null,
        };
    }

    /**
     * Transform order data for API response
     */
    private function transformOrder(Order $order): array
    {
        $statusEnum = StatusEnum::from($order->status);

        $paymentStatus = OnlinePaymentStatusEnum::tryFrom($order->payment_status);

        return [
            ...$order->toArray(),

            'warehouse_name' => $order->tenant?->name ?? '-',
            'created_at' => Carbon::parse($order->created_at)->timezone('Asia/Dhaka')->format('d-M-Y h:i A'),
            'status' => [
                'name' => $statusEnum->name,
                'color' => $statusEnum->color(),
            ],
            'payment_status' => [
                'name' => $paymentStatus?->name ?? 'Unknown',
                'color' => $paymentStatus?->color() ?? 'secondary',
            ],
        ];
    }

    public function orderStatus(Request $request, Order $order, $action)
    {
        $statusMap = [
            'cancel' => 3,
            'confirm' => 13,
            'hold' => 14,
            'resume' => 2,
            'approved' => 4,
            'processing' => 5,
            'onway' => 6,
            'complete' => 7,
            'return' => 9,
        ];
        if ($action === 'changeamount') {
            $order->payable_amount = $order->grand_total;
            $order->due_amount = 0;
            $order->save();

            return response()->json(['message' => 'Amount status changed successfully!']);
        }

        if (! isset($statusMap[$action])) {
            return response()->json(['message' => 'Invalid status action'], 400);
        }

        if (in_array((int) $order->status, [StatusEnum::Approved->value, StatusEnum::Processing->value, StatusEnum::Complete->value, StatusEnum::OnWay->value], true)) {
            if (in_array($action, ['cancel', 'return'], true)) {
                return response()->json(['message' => 'Cannot cancel or return an already approved order.'], 400);
            }
        }

        $order->status = $statusMap[$action];
        $order->save();

        return response()->json([
            'message' => "Order status updated to $action successfully.",
            'status' => $order->status,
        ]);
    }

    public function receiveAmount(Request $request, Order $order)
    {

        $request->validate([
            'received_amount' => ['required', 'numeric', 'min:0', 'max:'.$order->grand_total],
        ]);
        $order->payable_amount = $request->received_amount;
        $order->due_amount = max(0, $order->grand_total - $request->received_amount);
        $order->save();

        return response()->json(['message' => 'Received amount updated successfully.']);
    }

    public function destroy(Order $order)
    {
        if (in_array((int) $order->status, [StatusEnum::Approved->value, StatusEnum::Processing->value, StatusEnum::Complete->value, StatusEnum::OnWay->value], true)) {
            return errorResponse('Cannot delete an already approved order with fulfilled sales.', null, 400);
        }

        $order->delete();

        return successResponse('Order deleted has been successfully');
    }

    public function approveOrderWithSerials(Request $request, Order $order)
    {
        if (in_array((int) $order->status, [StatusEnum::Approved->value, StatusEnum::Processing->value, StatusEnum::Complete->value, StatusEnum::OnWay->value], true)) {
            throw new OrderValidationException('This order has already been approved.');
        }

        $request->validate([
            'orders' => 'required|array',
            'orders.*.order_list_id' => 'required|integer',
            'orders.*.serials' => 'required|array',
            'orders.*.serials.*.serial_number' => 'required|string',
            'orders.*.serials.*.tenant_id' => 'required|integer|exists:tenants,id',
            'gift_orders' => 'nullable|array',
            'gift_orders.*.order_list_id' => 'required|integer',
            'gift_orders.*.serials' => 'required|array',
            'gift_orders.*.serials.*.serial_number' => 'required|string',
            'gift_orders.*.serials.*.tenant_id' => 'required|integer|exists:tenants,id',
        ], [
            'orders.*.serials.*.tenant_id.required' => 'Warehouse is required',
            'orders.*.serials.*.tenant_id.exists' => 'Selected warehouse does not exist.',
            'gift_orders.*.serials.*.tenant_id.required' => 'Gift item warehouse is required',
            'gift_orders.*.serials.*.tenant_id.exists' => 'Selected gift warehouse does not exist.',
        ]);

        // Key by order_list_id for fast lookup
        $ordersInput = collect($request->input('orders'))->keyBy('order_list_id');

        $checkOrderCustomer = Contact::query()
            ->where('id', $order->contact_id)
            ->first();

        if (! $checkOrderCustomer) {
            throw new OrderValidationException('Customer not found');
        }

        $orderAddress = $order->delivery_address;

        try {
            DB::beginTransaction();

            $order->status = StatusEnum::Approved->value;
            $order->save();

            $order->loadMissing(['items.productCare.care', 'items.preOwnedCare.care']);

            $salesByTenant = [];

            foreach ($order->items as $item) {

                $productId = $item->product_id;
                $productName = $item->variant->product->product_name;
                $discountType = ($item->discount_type === 'none') ? null : $item->discount_type;
                $carePercentage = (float) ($item->productCare?->care?->value ?? $item->preOwnedCare?->care?->value ?? 0);
                $careAmountPerUnit = round($item->product_price * ($carePercentage / 100), 2);

                $inputRow = $ordersInput->get($item->id);

                if (! $inputRow) {
                    throw new OrderValidationException(
                        "Missing serial input for '{$productName}'."
                    );
                }

                $serials = $inputRow['serials'] ?? [];

                if (count($serials) !== (int) $item->quantity) {
                    throw new OrderValidationException(
                        "'{$productName}' requires {$item->quantity} serial number(s), ".count($serials).' provided.'
                    );
                }

                foreach ($serials as $serialData) {
                    $serialNumber = $serialData['serial_number'];
                    $tenantId = (int) $serialData['tenant_id'];

                    $chekSerial = ProductSerial::query()
                        ->where('serial_number', $serialNumber)
                        ->where('product_id', $productId)
                        ->first();

                    if (! $chekSerial) {
                        throw new OrderValidationException(
                            "Serial '{$serialNumber}' for '{$productName}' does not exist."
                        );
                    }

                    $serialExists = ProductSerial::query()
                        ->where('serial_number', $serialNumber)
                        ->where('product_id', $productId)
                        ->where('product_variant_id', $chekSerial->product_variant_id)
                        ->whereHas('tenantProductSerials', function ($query) use ($tenantId) {
                            $query->withoutGlobalScope(TenantScope::class)
                                ->whereIn('status', ['purchase', 'sale_return'])
                                ->where('tenant_id', $tenantId);
                        })
                        ->exists();

                    if (! $serialExists) {
                        throw new OrderValidationException(
                            "Serial '{$serialNumber}' for '{$productName}' is unavailable in the selected warehouse."
                        );
                    }

                    if (! isset($salesByTenant[$tenantId])) {
                        $isFirstTenantSale = $salesByTenant === [];
                        $salesByTenant[$tenantId] = [
                            'contact_id' => $order->contact_id,
                            'tenant_id' => $tenantId,
                            'date' => now()->format('Y-m-d'),
                            'address' => $orderAddress,
                            'sub_total' => 0,
                            'shipping_cost' => $isFirstTenantSale ? ($order->shipping_charge ?? 0) : 0,
                            'order_tax' => 0,
                            'coupon_amount' => $isFirstTenantSale ? ($order->discount_total ?? 0) : 0,
                            'coupon_code' => $isFirstTenantSale ? ($order->coupon_code ?? null) : null,
                            'grand_total' => 0,
                            'items' => [],
                        ];
                    }

                    $itemTotal = $item->product_price - ($item->discount_amount ?? 0);
                    $salesByTenant[$tenantId]['sub_total'] += $item->product_price;
                    $salesByTenant[$tenantId]['grand_total'] += $itemTotal + $careAmountPerUnit;

                    $salesByTenant[$tenantId]['items'][] = [
                        'product_id' => $item->product_id,
                        'variant_id' => $item->product_variant_id,
                        'tenant_id' => $tenantId,
                        'quantity' => 1,
                        'unit_price' => $item->product_price,
                        'discount_type' => $discountType,
                        'discount_amount' => $item->discount_amount ?? 0,
                        'serial_number' => $serialNumber,
                        'product_care_id' => $item->product_care_id,
                        'pre_owned_care_id' => $item->pre_owned_care_id,
                        'care_amount' => $careAmountPerUnit,
                        'note' => null,
                    ];
                }
            }

            // Process gift item serials
            $giftOrdersInput = $request->input('gift_orders', []);

            foreach ($giftOrdersInput as $giftOrder) {
                $orderListId = $giftOrder['order_list_id'];
                $giftSerials = $giftOrder['serials'] ?? [];

                $orderItem = $order->items->firstWhere('id', $orderListId);
                if (! $orderItem) {
                    continue;
                }

                $giftVariant = $orderItem->giftProductVariant;
                if (! $giftVariant) {
                    continue;
                }

                foreach ($giftSerials as $giftSerial) {
                    $serialNumber = $giftSerial['serial_number'];
                    $tenantId = (int) $giftSerial['tenant_id'];

                    $chekSerial = ProductSerial::query()
                        ->where('serial_number', $serialNumber)
                        ->where('product_id', $giftVariant->product_id)
                        ->first();

                    if (! $chekSerial) {
                        throw new OrderValidationException(
                            "Gift serial '{$serialNumber}' does not exist for the gift product."
                        );
                    }

                    $serialExists = ProductSerial::query()
                        ->where('serial_number', $serialNumber)
                        ->where('product_id', $giftVariant->product_id)
                        ->where('product_variant_id', $giftVariant->product_variant_id)
                        ->whereHas('tenantProductSerials', function ($query) use ($tenantId) {
                            $query->whereIn('status', ['purchase', 'sale_return'])
                                ->withoutGlobalScope(TenantScope::class)
                                ->where('tenant_id', $tenantId);
                        })
                        ->exists();

                    if (! $serialExists) {
                        throw new OrderValidationException(
                            "Gift serial '{$serialNumber}' is unavailable in the selected warehouse."
                        );
                    }

                    $salesByTenant[$tenantId] ??= [
                        'contact_id' => $order->contact_id,
                        'tenant_id' => $tenantId,
                        'date' => now()->format('Y-m-d'),
                        'address' => $orderAddress,
                        'sub_total' => 0,
                        'order_tax' => 0,
                        'shipping_cost' => 0,
                        'coupon_code' => null,
                        'coupon_amount' => 0,
                        'grand_total' => 0,
                        'items' => [],
                    ];

                    $salesByTenant[$tenantId]['items'][] = [
                        'product_id' => $giftVariant->product_id,
                        'variant_id' => $giftVariant->product_variant_id,
                        'tenant_id' => $tenantId,
                        'quantity' => 1,
                        'unit_price' => 0,
                        'discount_type' => null,
                        'discount_amount' => 0,
                        'serial_number' => $serialNumber,
                        'note' => 'Gift item',
                    ];
                }
            }

            // Create one invoice per warehouse (tenant)
            $createdSaleIds = [];
            foreach ($salesByTenant as $saleData) {
                $this->saleService
                    ->orderCreateSale($saleData)
                    ->createEcommerceItem($saleData)
                    ->stockUpdate(new Request($saleData));

                $createdSaleIds[] = $this->saleService->getModelId();
            }

            DB::commit();

            // profitLossRecalculate() only recalculates the sale currently
            // held on $this->saleService->model, which orderCreateSale()
            // overwrites on every loop iteration - so a multi-tenant order
            // (items split across warehouses) needs one recalculate call
            // per created sale, not just the last one.
            foreach ($createdSaleIds as $saleId) {
                try {
                    app(ProfitLossSummaryService::class)->recalculate($saleId);
                } catch (\Throwable $plException) {
                    report($plException);
                }
            }

            return successResponse('Order approved has been successfully');
        } catch (OrderValidationException $e) {
            DB::rollBack();
            throw new GeneralException($e->getMessage(), 400, $e);
        } catch (\Exception $e) {
            DB::rollBack();
            if (app()->environment('production')) {
                throw new GeneralException('Failed to approve order', 500, $e);
            }
            throw new GeneralException($e->getMessage(), 500, $e);
        }
    }

    public function checkTransactionStatus(Request $request)
    {
        $request->validate([
            'transaction_id' => ['required', 'string'],
        ]);

        $order = Order::query()
            ->where('transaction_id', $request->transaction_id)
            ->where('payment_method', 'online')
            ->whereNotNull('transaction_id')
            ->first();

        if (! $order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found.',
            ], 404);
        }

        if (in_array((int) $order->payment_status, [
            OnlinePaymentStatusEnum::Successful->value,
            OnlinePaymentStatusEnum::PartiallyPaid->value,
        ], true)) {
            return response()->json([
                'success' => true,
                'message' => 'Payment already processed.',
            ]);
        }

        $result = (new SSLCommerzPaymentService)->checkTransactionStatus($order);

        return response()->json($result);
    }
}
