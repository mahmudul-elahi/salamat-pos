<?php

namespace App\Http\Controllers\Pos\SaleReturn;

use App\Exceptions\GeneralException;
use App\Filters\Pos\Sale\SaleFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Pos\SaleReturn\SaleReturnRequest;
use App\Models\Pos\Purchase\PurchaseItem;
use App\Models\Pos\SaleReturn\SaleReturn;
use App\Models\Pos\SaleReturn\SaleReturnItem;
use App\Services\Pos\SaleReturn\SaleReturnService;
use Illuminate\Support\Facades\DB;
use Throwable;

class SaleReturnController extends Controller
{
    public function __construct(protected SaleReturnService $service, protected SaleFilter $filter) {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $perPage = request('per_page', 10);

        $baseQuery = $this->service
            ->filters($this->filter)
            ->with(['contact:id,name,profile_picture', 'tenant:id,name', 'createdBy:id,name'])
            ->withCount('items as total_items')
            ->orderByDesc('id')
            ->paginate($perPage);

        $grandTotalSum = $this->service
            ->filters($this->filter)
            ->sum('grand_total');

        // A plain COUNT() against the items table, scoped to a subquery of
        // filtered sale_return ids - stays a single aggregate query in SQL
        // instead of pulling every filtered row into PHP just to add up a
        // per-row count() column.
        $totalItemsSum = SaleReturnItem::query()
            ->whereIn('sale_return_id', $this->service->filters($this->filter)->select('sale_returns.id'))
            ->count();

        // Final response with totals
        return [
            'grand_total' => [
                'grand_total' => $grandTotalSum,
                'total_items' => $totalItemsSum,
            ],
            ...$baseQuery->toArray(),
        ];
    }

    /**
     * Store a newly created resource in storage.
     *
     * @throws GeneralException|Throwable
     */
    public function store(SaleReturnRequest $request)
    {
        $isAppAdmin = auth()->user()->isAppAdmin();
        try {
            DB::beginTransaction();

            $this->service
                ->tenantProductSerialLog($request->all(), $isAppAdmin)
                ->create($request->validated())
                ->createItem($request->validated())
                ->saleReturnTransaction($request)
                ->updateStock($request, $isAppAdmin);

            DB::commit();

            return successResponse('Sale return created successfully');
        } catch (\Exception $exception) {
            DB::rollBack();
            if ($exception->getCode() == 400) {
                throw new GeneralException($exception->getMessage(), 500, $exception);
            }
            if (app()->environment('production')) {
                throw new GeneralException('Sale return creation failed', 500, $exception);
            }
            throw new GeneralException($exception->getMessage(), 500, $exception);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(SaleReturn $saleReturn): array
    {
        return [
            ...$saleReturn->load('contact:id,name')->toArray(),
            'invoice_bill' => $saleReturn->getSaleReturnBillAttachmentAttribute(),
            'items' => $saleReturn->items()->with(['product:id,product_name', 'product.serial:product_id,serial_number,status', 'productVariant:id,sku'])->get()->map(function ($item) {
                $matchedSerial = $item->product?->serial
                    ->firstWhere('serial_number', $item->serial_number);

                return [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'variant_id' => $item->product_variant_id,
                    'sale_id' => $item->sale_id,
                    'name' => $item->product?->product_name,
                    'sku' => $item->productVariant?->sku,
                    'serial_number' => $item->serial_number,
                    'quantity' => $item->quantity,
                    'price' => $item->unit_price,
                    'purchase_price' => $this->getPurchasesPrice($item),
                    'note' => $item->note,
                    'discount_type' => $item->discount_type,
                    'discount_amount' => $item->discount_amount,
                    'status' => $matchedSerial?->status,
                    'attribute_values' => $item->productVariant?->attributeValues->pluck('value')->implode('/'),
                ];
            }),
        ];
    }

    /**
     * Update the specified resource in storage.
     *
     * @throws Throwable
     */
    public function update(SaleReturnRequest $request, SaleReturn $sale_return)
    {
        $isAppAdmin = auth()->user()->isAppAdmin();
        try {
            DB::beginTransaction();

            $this->service
                ->setModel($sale_return)
                ->updateSaleReturn($request->all())
                ->updateSaleReturnItems($request->all(), $isAppAdmin)
                ->saleReturnTransaction($request);

            DB::commit();

            return successResponse('Sale Return updated successfully');
        } catch (\Exception $exception) {
            DB::rollBack();
            if ($exception->getCode() == 400) {
                throw new GeneralException($exception->getMessage(), 500, $exception);
            }
            if (app()->environment('production')) {
                throw new GeneralException('Sale Return update failed', 500, $exception);
            }
            throw new GeneralException($exception->getMessage(), 500, $exception);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @throws Throwable
     */
    public function destroy(SaleReturn $sale_return)
    {
        $isAppAdmin = auth()->user()->isAppAdmin();

        try {
            DB::beginTransaction();

            $this->service
                ->setModel($sale_return)
                ->stockIncrementAndStatusUpdate($isAppAdmin)
                ->itemsDelete()
                ->saleReturnTransactionsDelete()
                ->saleReturnDelete();

            DB::commit();

            return successResponse('Sale return deleted successfully');
        } catch (\Exception $exception) {
            DB::rollBack();
            if (app()->environment('production')) {
                throw new GeneralException('Sale return deletion failed', 500, $exception);
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
