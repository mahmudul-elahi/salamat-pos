<?php

namespace App\Http\Controllers\Pos\Sale;

use App\Exceptions\GeneralException;
use App\Filters\Pos\Sale\SaleFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Pos\Sale\SaleRequest;
use App\Models\Pos\Purchase\PurchaseItem;
use App\Models\Pos\Sale\Sale;
use App\Services\Pos\Sale\SaleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class SaleController extends Controller
{
    public function __construct(protected SaleService $service, protected SaleFilter $filter) {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $perPage = request('per_page', 10);

        /**
         * ----------------------------------------------------
         * BASE QUERY (Used for pagination)
         * ----------------------------------------------------
         */
        $baseQuery = $this->service
            ->filters($this->filter)
            ->with(['contact:id,name,profile_picture,phone', 'seller:id,name', 'courierOrder:id,sale_id,consignment_id,status', 'payments.paymentMethod'])
            ->withCount('items as total_item')
            ->where('status', '!=', 'return')
            ->orderByDesc('id');

        /**
         * ----------------------------------------------------
         * AGGREGATES (Run separate optimized queries)
         * ----------------------------------------------------
         */

        // Total invoices
        $totalInvoiceCount = (clone $baseQuery)->count('id');

        $saleIds = $baseQuery->pluck('id')->toArray();

        // Total items (fastest way)
        $totalItem = $this->service
            ->filters($this->filter)
            ->where('status', '!=', 'return')
            ->join('sale_items', 'sales.id', '=', 'sale_items.sale_id')
            ->sum('sale_items.quantity');

        // Grand totals (sum only)
        $grandTotal = $this->service
            ->filters($this->filter)
            ->where('status', '!=', 'return')
            ->sum('grand_total');

        // Payments received
        $receivedAmount = Sale::totalReceivedAmount($saleIds);

        $dueAmount = max(0, $grandTotal - $receivedAmount);

        /**
         * ----------------------------------------------------
         * PAGINATION + FORMAT RESPONSE
         * ----------------------------------------------------
         */
        $counter = 0;

        $paginated = $baseQuery
            ->paginate($perPage)
            ->through(function ($item) use (&$counter) {

                $counter++;
                $received = $item->getTransactionAmountAttribute();

                return [
                    ...$item->toArray(),
                    'contact' => $item->contact ? [
                        'id' => $item->contact->id,
                        'name' => $item->contact->name,
                        'profile_picture' => $item->contact->profile_picture,
                        'phone' => $item->contact->phone,
                    ] : null,
                    'courier_order' => $item->courierOrder ? [
                        'consignment_id' => $item->courierOrder->consignment_id,
                        'status' => $item->courierOrder->status,
                    ] : null,
                    'seller' => $item->seller ? [
                        'id' => $item->seller->id,
                        'name' => $item->seller->name,
                        'profile_picture' => $item->seller->profile_picture,
                    ] : null,
                    'payment_methods' => $item->payments->map(fn ($p) => $p->paymentMethod)->filter()->unique('id')->values()->map(fn ($pm) => ['name' => $pm->name, 'logo' => $pm->logo])->toArray(),
                    'total_invoice' => $counter,
                    'total_item' => $item->total_item,
                    'received_amount' => $received,
                    'due_amount' => max(0, abs($item->grand_total) - $received),
                ];
            });

        /**
         * ----------------------------------------------------
         * FINAL RESPONSE
         * ----------------------------------------------------
         */
        return [
            'grand_total' => [
                'total_invoice' => $totalInvoiceCount,
                'total_item' => $totalItem,
                'grand_total' => $grandTotal,
                'received_amount' => $receivedAmount,
                'due_amount' => $dueAmount,
            ],
            ...$paginated->toArray(),
        ];
    }

    /**
     * Store a newly created resource in storage.
     *
     * @throws GeneralException|Throwable
     */
    public function store(SaleRequest $request)
    {
        $isAppAdmin = auth()->user()->isAppAdmin();

        try {
            DB::beginTransaction();

            $this->service
                ->tenantProductSerialLog($request->all(), $isAppAdmin)
                ->create($request->validated())
                ->createItem($request->validated())
                ->createPurchaseForProductItems($request->all(), $isAppAdmin)
                ->saleTransaction($request)
                ->stockUpdate($request);

            DB::commit();

            // Run after commit, not inside the transaction above -
            // ProfitLossSummaryService::recalculate() opens its own nested
            // DB::transaction() internally, and running that as a savepoint
            // under a still-open outer transaction is what intermittently
            // left sales with zero profit_loss rows (same fix already
            // applied in PurchaseController::store()).
            try {
                $this->service->profitLossRecalculate();
            } catch (Throwable $plException) {
                report($plException);
            }

            return response()->json([
                'message' => 'Sale created successfully',
                'id' => $this->service->getModelId(),
            ]);
        } catch (\Exception $exception) {
            DB::rollBack();
            if ($exception->getCode() === 400) {
                throw new GeneralException($exception->getMessage(), 500, $exception);
            }
            if (app()->environment('production')) {
                throw new GeneralException('Sale creation failed', 500, $exception);
            }
            throw new GeneralException($exception->getMessage(), 500, $exception);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Sale $sale): array
    {
        // $isAdmin = auth()->user()->isAppAdmin();
        return [
            ...$sale->load('contact:id,name')->toArray(),
            'invoice_bill' => $sale->getSaleBillAttachmentAttribute(),
            'items' => $sale->items()->with([
                'product:id,product_name',
                'product.serial:product_id,serial_number,status',
                'productVariant:id,sku',
            ])
                ->get()
                ->map(
                    //                    $matchedSerial = $item->product?->serial
                    //                        ->firstWhere('serial_number', $item->serial_number);
                    //                    $productSerialStatus = TenantProductSerial::query()
                    //                        ->when($isAdmin, fn($query) => $query->where('tenant_id', $sale->tenant_id))
                    //                        ->where('product_serial_id', $matchedSerial?->id)
                    //                        ->first();
                    fn ($item) => [
                        'id' => $item->id,
                        'product_id' => $item->product_id,
                        'variant_id' => $item->product_variant_id,
                        'name' => $item->product?->product_name,
                        'sku' => $item->productVariant?->sku,
                        'serial_number' => $item->serial_number,
                        'quantity' => $item->quantity,
                        'price' => $item->unit_price,
                        'purchase_price' => $this->getPurchasesPrice($item),
                        'note' => $item->note,
                        'discount_type' => $item->discount_type,
                        'discount_amount' => $item->discount_amount,
                        //                        'status' => $productSerialStatus?->status,
                        'attribute_values' => $item->productVariant?->attributeValues->pluck('value')->implode('/'),
                    ]),
        ];
    }

    /**
     * Update the specified resource in storage.
     *
     * @throws GeneralException|Throwable
     */
    public function update(SaleRequest $request, Sale $sale)
    {
        $isAppAdmin = auth()->user()->isAppAdmin();
        try {
            DB::beginTransaction();

            $this->service
                ->setModel($sale)
                ->updateSale($request->all())
                ->updateSaleItems($request->all(), $isAppAdmin)
                ->saleTransaction($request);

            DB::commit();

            // See the matching comment in store() - must run after commit.
            try {
                $this->service->profitLossRecalculate();
            } catch (Throwable $plException) {
                report($plException);
            }

            return successResponse('Sale updated successfully');
        } catch (\Exception $exception) {
            DB::rollBack();
            if ($exception->getCode() === 400) {
                throw new GeneralException($exception->getMessage(), 500, $exception);
            }
            if (app()->environment('production')) {
                throw new GeneralException('Sale update failed', 500, $exception);
            }
            throw new GeneralException($exception->getMessage(), 500, $exception);
        }
    }

    /**
     * Quick update of just the seller on a sale (from the list row action).
     */
    public function updateSeller(Request $request, Sale $sale)
    {
        $request->validate([
            'seller_id' => ['nullable', 'exists:users,id'],
        ]);

        $sale->update([
            'seller_id' => $request->input('seller_id'),
        ]);

        return successResponse('Seller updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Sale $sale)
    {
        $isAppAdmin = auth()->user()->isAppAdmin();
        try {
            DB::beginTransaction();

            $this->service->setModel($sale)
                ->stockIncrementAndStatusUpdate($isAppAdmin)
                ->itemsDelete()
                ->paymentDelete()
                ->saleDelete();

            DB::commit();

            // See the matching comment in store() - must run after commit.
            try {
                $this->service->profitLossRecalculate();
            } catch (Throwable $plException) {
                report($plException);
            }

            return successResponse('Sale deleted successfully');
        } catch (\Exception $exception) {
            DB::rollBack();
            if (app()->environment('production')) {
                throw new GeneralException('Sale deletion failed', 500, $exception);
            }
            throw new GeneralException($exception->getMessage(), 500, $exception);
        }
    }

    private function getPurchasesPrice($serial)
    {
        $purchases = PurchaseItem::query()
            ->where('product_id', $serial->product_id)
            ->where('product_variant_id', $serial?->product_variant_id)
            ->where('serial_number', $serial->serial_number)
            ->first();
        if (! $purchases) {
            return 0;
        }

        return $purchases->unit_price;
    }
}
