<?php

namespace App\Http\Controllers\Pos\StockTransfer;

use App\Exceptions\GeneralException;
use App\Http\Controllers\Controller;
use App\Models\Pos\Product\TenantProductSerial;
use App\Models\Pos\Stock\Stock;
use App\Models\Pos\StockTransfer\StockTransfer;
use App\Models\Pos\StockTransfer\StockTransferItem;
use App\Scopes\TenantScope;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockTransferController extends Controller
{
    public function index()
    {
        return StockTransfer::query()
            ->with(['fromTenant:id,name', 'toTenant:id,name'])
            ->withCount('items')
            ->when(request('status'), fn ($q, $s) => $q->where('status', $s))
            ->when(
                request('search'),
                fn ($q, $search) => $q->where('reference_no', 'LIKE', "%{$search}%")
            )
            ->orderByDesc('id')
            ->paginate(request('per_page', 10))
            ->through(fn ($item) => [
                ...$item->toArray(),
                'created_at' => Carbon::parse($item->created_at)
                    ->timezone('Asia/Dhaka')
                    ->format('d-M-Y h:i A'),
            ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'from_tenant_id' => 'required|integer|exists:tenants,id',
            'to_tenant_id' => 'required|integer|exists:tenants,id|different:from_tenant_id',
            'transfer_date' => 'required|date',
            'note' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.product_serial_id' => 'required|integer|exists:product_serials,id',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.product_variant_id' => 'required|integer|exists:product_variants,id',
        ]);

        // Validate each serial belongs to from_tenant with eligible status
        foreach ($request->items as $index => $item) {
            $exists = TenantProductSerial::withoutGlobalScope(TenantScope::class)
                ->where('tenant_id', $request->from_tenant_id)
                ->where('product_serial_id', $item['product_serial_id'])
                ->whereIn('status', ['purchase', 'sale_return'])
                ->exists();

            if (! $exists) {
                throw new GeneralException(
                    'Serial #'.($index + 1).' is not available in the source shop.',
                    400
                );
            }
        }

        try {
            DB::beginTransaction();

            $transfer = StockTransfer::create([
                'from_tenant_id' => $request->from_tenant_id,
                'to_tenant_id' => $request->to_tenant_id,
                'reference_no' => StockTransfer::generateReferenceNo(),
                'transfer_date' => $request->transfer_date,
                'status' => 'draft',
                'note' => $request->note,
                'created_by' => auth()->id(),
            ]);

            foreach ($request->items as $item) {
                StockTransferItem::create([
                    'stock_transfer_id' => $transfer->id,
                    'product_id' => $item['product_id'],
                    'product_variant_id' => $item['product_variant_id'],
                    'product_serial_id' => $item['product_serial_id'],
                    'quantity' => 1,
                ]);
            }

            DB::commit();

            return successResponse('Stock transfer created successfully', ['id' => $transfer->id]);
        } catch (\Exception $e) {
            DB::rollBack();
            throw new GeneralException($e->getMessage(), 500, $e);
        }
    }

    public function show(StockTransfer $stockTransfer)
    {
        $stockTransfer->load([
            'fromTenant:id,name',
            'toTenant:id,name',
            'createdBy:id,name',
            'approvedBy:id,name',
            'cancelledBy:id,name',
        ]);

        $items = StockTransferItem::query()
            ->where('stock_transfer_id', $stockTransfer->id)
            ->with([
                'product:id,product_name',
                'productVariant:id,sku',
                'productSerial:id,serial_number,max_retail_price',
                'productVariant.attributeValues:id,value',
            ])
            ->get()
            ->map(function ($item) use ($stockTransfer) {
                $attributes = $item->productVariant?->attributeValues
                    ?->pluck('value')->implode(' / ') ?? '';

                // Check if serial still available in from_tenant (only relevant for draft)
                $isAvailable = null;
                if ($stockTransfer->status === 'draft') {
                    $isAvailable = TenantProductSerial::withoutGlobalScope(TenantScope::class)
                        ->where('tenant_id', $stockTransfer->from_tenant_id)
                        ->where('product_serial_id', $item->product_serial_id)
                        ->whereIn('status', ['purchase', 'sale_return'])
                        ->exists();
                }

                return [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'product_variant_id' => $item->product_variant_id,
                    'product_serial_id' => $item->product_serial_id,
                    'serial_number' => $item->productSerial?->serial_number,
                    'product_name' => $item->product?->product_name,
                    'sku' => $item->productVariant?->sku,
                    'attributes' => $attributes,
                    'is_available' => $isAvailable,
                ];
            });

        return [
            'id' => $stockTransfer->id,
            'reference_no' => $stockTransfer->reference_no,
            'from_tenant' => $stockTransfer->fromTenant,
            'to_tenant' => $stockTransfer->toTenant,
            'transfer_date' => $stockTransfer->transfer_date,
            'status' => $stockTransfer->status,
            'note' => $stockTransfer->note,
            'created_by' => $stockTransfer->createdBy?->name,
            'approved_by' => $stockTransfer->approvedBy?->name,
            'cancelled_by' => $stockTransfer->cancelledBy?->name,
            'items' => $items,
        ];
    }

    public function approve(StockTransfer $stockTransfer)
    {
        try {
            DB::beginTransaction();

            $transfer = StockTransfer::whereKey($stockTransfer->id)->lockForUpdate()->first();
            if (! $transfer || $transfer->status !== 'draft') {
                throw new GeneralException('Only draft transfers can be approved.', 400);
            }

            $items = StockTransferItem::where('stock_transfer_id', $transfer->id)->get();

            foreach ($items as $item) {
                $fromSerial = TenantProductSerial::withoutGlobalScope(TenantScope::class)
                    ->where('tenant_id', $transfer->from_tenant_id)
                    ->where('product_serial_id', $item->product_serial_id)
                    ->whereIn('status', ['purchase', 'sale_return'])
                    ->lockForUpdate()
                    ->first();

                if (! $fromSerial) {
                    throw new GeneralException(
                        "Serial {$item->productSerial?->serial_number} is no longer available in the source shop.",
                        400
                    );
                }

                // 1. Mark FROM tenant serial as transferred
                $fromSerial->status = 'transfer';
                $fromSerial->saveQuietly();

                // 2. Create or update serial record for TO tenant
                TenantProductSerial::withoutGlobalScope(TenantScope::class)->updateOrCreate([
                    'tenant_id' => $transfer->to_tenant_id,
                    'product_serial_id' => $item->product_serial_id,
                ], [
                    'status' => 'purchase',
                ]);

                // 3. Decrement stock for FROM tenant
                Stock::withoutGlobalScope(TenantScope::class)
                    ->where('tenant_id', $transfer->from_tenant_id)
                    ->where('product_id', $item->product_id)
                    ->where('product_variant_id', $item->product_variant_id)
                    ->decrement('quantity');

                // 4. Increment stock for TO tenant (create row if not exists)
                $toStock = Stock::withoutGlobalScope(TenantScope::class)
                    ->where('tenant_id', $transfer->to_tenant_id)
                    ->where('product_id', $item->product_id)
                    ->where('product_variant_id', $item->product_variant_id)
                    ->first();

                if ($toStock) {
                    $toStock->increment('quantity');
                } else {
                    DB::table('stocks')->insert([
                        'tenant_id' => $transfer->to_tenant_id,
                        'product_id' => $item->product_id,
                        'product_variant_id' => $item->product_variant_id,
                        'quantity' => 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            $transfer->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
            ]);

            DB::commit();

            return successResponse('Stock transfer approved. Serials moved to destination shop.');
        } catch (\Exception $e) {
            DB::rollBack();
            throw new GeneralException($e->getMessage(), 400, $e);
        }
    }

    public function cancel(StockTransfer $stockTransfer)
    {
        if ($stockTransfer->status !== 'draft') {
            throw new GeneralException('Only draft transfers can be cancelled.', 400);
        }

        $stockTransfer->update(['status' => 'cancelled', 'cancelled_by' => auth()->id()]);

        return successResponse('Stock transfer cancelled.');
    }

    public function destroy(StockTransfer $stockTransfer)
    {
        if ($stockTransfer->status !== 'draft') {
            throw new GeneralException('Only draft transfers can be deleted.', 400);
        }

        $stockTransfer->items()->delete();
        $stockTransfer->delete();

        return successResponse('Stock transfer deleted successfully.');
    }
}
