<?php

namespace App\Http\Controllers\Pos\PurchaseReturn;

use App\Exceptions\GeneralException;
use App\Filters\Pos\Purchase\PurchaseFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Pos\PurchaseReturn\PurchaseReturnRequest;
use App\Models\Pos\Purchase\Purchase;
use App\Models\Pos\PurchaseReturn\PurchaseReturn;
use App\Services\Pos\PurchaseReturn\PurchaseReturnService;
use Illuminate\Support\Facades\DB;
use Throwable;

class PurchaseReturnController extends Controller
{
    public function __construct(protected PurchaseReturnService $service, protected PurchaseFilter $filter) {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $perPage = request('per_page', 10);

        $baseQuery = $this->service
            ->filters($this->filter)
            ->withCount('items as total_item')
            ->with(['contact:id,name,business_name,profile_picture', 'createdBy:id,name'])
            ->orderByDesc('id');

        // Calculate totals across all matching purchase returns
        $totals = (clone $baseQuery)->get()->reduce(function ($carry, $item) {
            $paid = $item->getTransactionAmountAttribute();
            $carry['grand_total'] += $item->grand_total;
            $carry['paid_amount'] += $paid;
            $carry['due_amount'] += $item->grand_total - $paid;

            return $carry;
        }, ['grand_total' => 0, 'paid_amount' => 0, 'due_amount' => 0]);

        // Paginate results
        $paginated = $baseQuery
            ->paginate($perPage)
            ->through(function ($item) {
                $paid = $item->getTransactionAmountAttribute();

                return [
                    ...$item->toArray(),
                    'invoice_bill' => $item->getPurchaseReturnBillAttachmentAttribute(),
                    'paid_amount' => $paid,
                    'due_amount' => $item->grand_total - $paid,
                ];
            });

        // Return paginated results + grand totals
        return [
            'grand_total' => [
                'grand_total' => floor($totals['grand_total']),
                'paid_amount' => floor($totals['paid_amount']),
                'due_amount' => floor($totals['due_amount']),
            ],
            ...$paginated->toArray(),
        ];
    }

    /**
     * Store a newly created resource in storage.
     *
     * @throws GeneralException|Throwable
     */
    public function store(PurchaseReturnRequest $request)
    {

        $isAppAdmin = auth()->user()->isAppAdmin();
        try {
            DB::beginTransaction();

            $this->service
                ->tenantProductSerialLog($request->all(), $isAppAdmin)
                ->create($request->validated())
                ->createInvoiceBill($request)
                ->createItem($request->validated())
                ->updateStock($request->all(), $isAppAdmin);

            DB::commit();

            return successResponse('Purchase return created successfully');
        } catch (\Exception $exception) {
            DB::rollBack();
            if (app()->environment('production')) {
                throw new GeneralException('Purchase return creation failed', 500, $exception);
            }
            throw new GeneralException($exception->getMessage(), 500, $exception);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(PurchaseReturn $purchaseReturn): array
    {
        return [
            ...$purchaseReturn->load('contact:id,name')->toArray(),
            'invoice_bill' => $purchaseReturn->getPurchaseReturnBillAttachmentAttribute(),
            'items' => $purchaseReturn->items()->with(['product:id,product_name', 'product.serial:product_id,serial_number,status', 'productVariant:id,sku'])->get()->map(function ($item) {
                $matchedSerial = $item->product?->serial
                    ->firstWhere('serial_number', $item->serial_number);

                return [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'purchase_id' => $item->purchase_id,
                    'variant_id' => $item->product_variant_id,
                    'name' => $item->product?->product_name,
                    'sku' => $item->productVariant?->sku,
                    'serial_number' => $item->serial_number,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'note' => $item->note,
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
    public function update(PurchaseReturnRequest $request, PurchaseReturn $purchase_return)
    {
        $isAppAdmin = auth()->user()->isAppAdmin();

        try {
            DB::beginTransaction();
            $this->service
                ->setModel($purchase_return)
                ->updatePurchaseReturn($request->all())
                ->createInvoiceBill($request)
                ->updatePurchaseReturnItem($request->all(), $isAppAdmin);

            DB::commit();

            return successResponse('Purchase Return updated successfully');
        } catch (\Exception $exception) {
            DB::rollBack();
            if (app()->environment('production')) {
                throw new GeneralException('Purchase Return update failed', 500, $exception);
            }
            throw new GeneralException($exception->getMessage(), 500, $exception);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @throws GeneralException
     * @throws Throwable
     */
    public function destroy(PurchaseReturn $purchase_return)
    {
        $isAppAdmin = auth()->user()->isAppAdmin();

        try {
            DB::beginTransaction();

            $this->service
                ->setModel($purchase_return)
                ->stockDecrementAndStatusUpdate($isAppAdmin)
                ->itemsDelete()
                ->transactionDelete()
                ->purchaseReturnDelete();

            DB::commit();

            return successResponse('Purchase return deleted successfully');
        } catch (\Exception $exception) {
            DB::rollBack();
            if (app()->environment('production')) {
                throw new GeneralException('Purchase return deletion failed', 500, $exception);
            }
            throw new GeneralException($exception->getMessage(), 500, $exception);
        }
    }

    /**
     * @throws Throwable
     * @throws GeneralException
     */
    public function transactionDelete(PurchaseReturn $purchaseReturn)
    {
        if ($purchaseReturn->status === 'due') {
            throw new GeneralException('Purchase return due cannot be deleted');
        }
        try {
            DB::beginTransaction();

            $purchaseReturn->payments()->delete();
            $purchaseReturn->update([
                'status' => 'due',
            ]);
            DB::commit();

            return successResponse('Purchase return status updated successfully');
        } catch (\Exception $exception) {
            DB::rollBack();
            if (app()->environment('production')) {
                throw new GeneralException('Purchase return status update failed', 500, $exception);
            }
            throw new GeneralException($exception->getMessage(), 500, $exception);
        }
    }
}
