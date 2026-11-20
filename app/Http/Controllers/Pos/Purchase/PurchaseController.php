<?php

namespace App\Http\Controllers\Pos\Purchase;

use App\Models\Pos\Product\TenantProductSerial;
use Throwable;
use Illuminate\Support\Facades\DB;
use App\Exceptions\GeneralException;
use App\Http\Controllers\Controller;
use App\Models\Pos\Purchase\Purchase;
use App\Filters\Pos\Purchase\PurchaseFilter;
use App\Services\Pos\Purchase\PurchaseService;
use App\Http\Requests\Pos\Purchase\PurchaseRequest;

class PurchaseController extends Controller
{
    public function __construct(protected PurchaseService $service, protected PurchaseFilter $filter) {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $perPage = request('per_page', 10);

        /**
         * ----------------------------------------------------
         * BASE QUERY (used for pagination only)
         * ----------------------------------------------------
         */
        $baseQuery = $this->service
            ->filters($this->filter)
            ->with(['contact:id,name,business_name,profile_picture', 'tenant:id,name', 'createdBy:id,name'])
            ->withCount('items as total_item')
            ->where('status', '!=', 'return')
            ->orderByDesc('id');

        /**
         * ----------------------------------------------------
         * AGGREGATE CALCULATIONS (lightweight queries)
         * ----------------------------------------------------
         */

        // Total invoices
        $totalInvoiceCount = (clone $baseQuery)->count('id');

        $purchaseIds = $baseQuery->pluck('id')->toArray();

        // Total items (sum of purchase_items.quantity)
        $totalItem = $this->service
            ->filters($this->filter)
            ->where('status', '!=', 'return')
            ->join('purchase_items', 'purchases.id', '=', 'purchase_items.purchase_id')
            ->sum('purchase_items.quantity');

        // Grand total of all purchases
        $grandTotal = $this->service
            ->filters($this->filter)
            ->where('status', '!=', 'return')
            ->sum('grand_total');

        // Payments made
        $paidAmount = Purchase::totalPaidAmount($purchaseIds);

        $dueAmount = $grandTotal - $paidAmount;

        /**
         * ----------------------------------------------------
         * PAGINATED ITEMS
         * ----------------------------------------------------
         */
        $counter = 0;

        $paginated = $baseQuery
            ->paginate($perPage)
            ->through(function ($item) use (&$counter) {

                $counter++;

                $paid = $item->getTransactionAmountAttribute();

                return [
                    ...$item->toArray(),
                    'total_invoice' => $counter,
                    'total_item' => $item->total_item,
                    'paid_amount' => $paid,
                    'due_amount' => $item->grand_total - $paid,
                ];
            });

        /**
         * ----------------------------------------------------
         * RESPONSE FORMAT
         * ----------------------------------------------------
         */
        return [
            'grand_total' => [
                'total_invoice' => $totalInvoiceCount,
                'total_item' => $totalItem,
                'grand_total' => $grandTotal,
                'paid_amount' => $paidAmount,
                'due_amount' => $dueAmount,
            ],
            ...$paginated->toArray(),
        ];
    }

    /**
     * Store a newly created resource in storage.
     * @throws GeneralException|Throwable
     */
    public function store(PurchaseRequest $request)
    {
        $isAppAdmin = auth()->user()->isAppAdmin();
        try {
            DB::beginTransaction();

            $this->service
                ->tenantProductSerialLog($request, $isAppAdmin)
                ->create($request->validated())
                ->createItem($request->validated())
                ->processSaleProductTemps($request->all())
                ->createInvoiceBill($request)
                ->updateStock($request);

            DB::commit();

            // Run after commit, not inside the transaction above —
            // ProfitLossSummaryService::recalculate() opens its own nested
            // DB::transaction() internally, and running that as a savepoint
            // under a still-open outer transaction is what caused sale
            // creation to intermittently end up with zero profit_loss rows
            // elsewhere in this app (see SaleController::store()). Wrapped
            // separately so a recalculation failure here — the purchase
            // already committed successfully — never turns into a "Purchase
            // creation failed" error.
            try {
                $this->service->recalculateProfitLossForFulfilledSales();
            } catch (\Throwable $plException) {
                report($plException);
            }

            return successResponse('Purchase created successfully');
        } catch (\Exception $exception) {
            DB::rollBack();
            if ($exception->getCode() === 400) {
                throw new GeneralException($exception->getMessage(), 500);
            }
            if (app()->environment('production')) {
                throw new GeneralException('Purchase creation failed', 500);
            }
            throw new GeneralException($exception->getMessage(), 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Purchase $purchase): array
    {
        $isAdmin = auth()->user()->isAppAdmin();
        return [
            ...$purchase->load('contact:id,name')->toArray(),
            'invoice_bill' => $purchase->getPurchaseBillAttachmentAttribute(),
            'items' => $purchase->items()
                ->with(['product:id,product_name', 'product.serial:id,product_id', 'productVariant:id,sku'])
                ->get()
                ->map(function ($item) use ($isAdmin, $purchase) {
                    $matchedSerial = $item->product?->serial
                        ->firstWhere('serial_number', $item->serial_number);

                    $productSerialStatus = TenantProductSerial::query()
                        ->when($isAdmin, fn($query) => $query->where('tenant_id', $purchase->tenant_id))
                        ->where('product_serial_id', $matchedSerial?->id)
                        ->first();

                    return [
                        'id' => $item->id,
                        'product_id' => $item->product_id,
                        'variant_id' => $item->product_variant_id,
                        'name' => $item->product?->product_name,
                        'sku' => $item->productVariant?->sku,
                        'serial_number' => $item->serial_number,
                        'quantity' => $item->quantity,
                        'price' => $item->unit_price,
                        'note' => $item->note,
                        'status' => $productSerialStatus?->status,
                        'attribute_values' => $item->productVariant?->attributeValues->pluck('value')->implode('/'),
                    ];
                }),
        ];
    }

    /**
     * Update the specified resource in storage.
     * @throws GeneralException|Throwable
     */
    public function update(PurchaseRequest $request, Purchase $purchase)
    {
        $isAppAdmin = auth()->user()->isAppAdmin();

        try {
            DB::beginTransaction();

            $this->service
                ->setModel($purchase)
                //                ->assignSupplerToTenant($request)
                ->updatePurchase($request->validated())
                ->createInvoiceBill($request)
                ->updateItems($request->all(), $isAppAdmin);

            DB::commit();

            return successResponse('Purchase updated successfully');
        } catch (\Exception $exception) {
            DB::rollBack();
            if ($exception->getCode() === 400) {
                throw new GeneralException($exception->getMessage(), 500);
            }
            if (app()->environment('production')) {
                throw new GeneralException('Purchase update failed', 500);
            }
            throw new GeneralException($exception->getMessage(), 500);
        }
    }


    /**
     * Remove the specified resource from storage.
     * @throws GeneralException|Throwable
     */
    public function destroy(Purchase $purchase)
    {
        $isAppAdmin = auth()->user()->isAppAdmin();

        try {
            DB::beginTransaction();

            $this->service
                ->setModel($purchase)
                ->stockAndSerialNumberUpdate($isAppAdmin)
                ->itemsDelete()
                ->transactionsDelete()
                ->purchaseDelete();

            DB::commit();

            return successResponse('Purchase deleted successfully');
        } catch (\Exception $exception) {
            DB::rollBack();
            if (app()->environment('production')) {
                throw new GeneralException('Purchase deletion failed', 500);
            }
            throw new GeneralException($exception->getMessage(), 500);
        }
    }
}
