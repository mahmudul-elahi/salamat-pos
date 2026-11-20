<?php

namespace App\Http\Controllers\Pos\DamageProduct;

use App\Exceptions\GeneralException;
use App\Http\Controllers\Controller;
use App\Models\Pos\DamageProduct\DamageProduct;
use App\Models\Pos\DamageProduct\DamageProductItem;
use App\Models\Pos\Product\TenantProductSerial;
use App\Models\Pos\Stock\Stock;
use App\Scopes\TenantScope;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DamageProductController extends Controller
{
    public function index()
    {
        return DamageProduct::query()
            ->with(['tenant:id,name'])
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
            'tenant_id' => 'required|integer|exists:tenants,id',
            'damage_date' => 'required|date',
            'note' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.product_serial_id' => 'required|integer|exists:product_serials,id',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.product_variant_id' => 'required|integer|exists:product_variants,id',
        ]);

        // Validate each serial belongs to this shop with an eligible status
        foreach ($request->items as $index => $item) {
            $exists = TenantProductSerial::withoutGlobalScope(TenantScope::class)
                ->where('tenant_id', $request->tenant_id)
                ->where('product_serial_id', $item['product_serial_id'])
                ->whereIn('status', ['purchase', 'sale_return'])
                ->exists();

            if (! $exists) {
                throw new GeneralException(
                    'Serial #'.($index + 1).' is not available in this shop.',
                    400
                );
            }
        }

        try {
            DB::beginTransaction();

            $damageProduct = DamageProduct::create([
                'tenant_id' => $request->tenant_id,
                'reference_no' => DamageProduct::generateReferenceNo(),
                'damage_date' => $request->damage_date,
                'status' => 'draft',
                'note' => $request->note,
                'created_by' => auth()->id(),
            ]);

            foreach ($request->items as $item) {
                DamageProductItem::create([
                    'damage_product_id' => $damageProduct->id,
                    'product_id' => $item['product_id'],
                    'product_variant_id' => $item['product_variant_id'],
                    'product_serial_id' => $item['product_serial_id'],
                    'quantity' => 1,
                ]);
            }

            DB::commit();

            return successResponse('Damage product recorded successfully', ['id' => $damageProduct->id]);
        } catch (\Exception $e) {
            DB::rollBack();
            throw new GeneralException($e->getMessage(), 500, $e);
        }
    }

    public function show(DamageProduct $damageProduct)
    {
        $damageProduct->load([
            'tenant:id,name',
            'createdBy:id,name',
            'approvedBy:id,name',
            'cancelledBy:id,name',
        ]);

        $items = DamageProductItem::query()
            ->where('damage_product_id', $damageProduct->id)
            ->with([
                'product:id,product_name',
                'productVariant:id,sku',
                'productSerial:id,serial_number,max_retail_price',
                'productVariant.attributeValues:id,value',
            ])
            ->get()
            ->map(function ($item) use ($damageProduct) {
                $attributes = $item->productVariant?->attributeValues
                    ?->pluck('value')->implode(' / ') ?? '';

                // Check if serial still available in this shop (only relevant for draft)
                $isAvailable = null;
                if ($damageProduct->status === 'draft') {
                    $isAvailable = TenantProductSerial::withoutGlobalScope(TenantScope::class)
                        ->where('tenant_id', $damageProduct->tenant_id)
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
            'id' => $damageProduct->id,
            'reference_no' => $damageProduct->reference_no,
            'tenant' => $damageProduct->tenant,
            'damage_date' => $damageProduct->damage_date,
            'status' => $damageProduct->status,
            'note' => $damageProduct->note,
            'created_by' => $damageProduct->createdBy?->name,
            'approved_by' => $damageProduct->approvedBy?->name,
            'cancelled_by' => $damageProduct->cancelledBy?->name,
            'items' => $items,
        ];
    }

    public function approve(DamageProduct $damageProduct)
    {
        if ($damageProduct->status !== 'draft') {
            throw new GeneralException('Only draft records can be approved.', 400);
        }

        $items = DamageProductItem::where('damage_product_id', $damageProduct->id)->get();

        try {
            DB::beginTransaction();

            foreach ($items as $item) {
                $serial = TenantProductSerial::withoutGlobalScope(TenantScope::class)
                    ->where('tenant_id', $damageProduct->tenant_id)
                    ->where('product_serial_id', $item->product_serial_id)
                    ->whereIn('status', ['purchase', 'sale_return'])
                    ->first();

                if (! $serial) {
                    throw new GeneralException(
                        "Serial {$item->productSerial?->serial_number} is no longer available in this shop.",
                        400
                    );
                }

                // 1. Mark this shop's serial as damaged
                $serial->status = 'damage';
                $serial->saveQuietly();

                // 2. Decrement stock for this shop
                Stock::withoutGlobalScope(TenantScope::class)
                    ->where('tenant_id', $damageProduct->tenant_id)
                    ->where('product_id', $item->product_id)
                    ->where('product_variant_id', $item->product_variant_id)
                    ->decrement('quantity');
            }

            $damageProduct->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
            ]);

            DB::commit();

            return successResponse('Damage product approved. Stock updated.');
        } catch (\Exception $e) {
            DB::rollBack();
            throw new GeneralException($e->getMessage(), 400, $e);
        }
    }

    public function cancel(DamageProduct $damageProduct)
    {
        if ($damageProduct->status !== 'draft') {
            throw new GeneralException('Only draft records can be cancelled.', 400);
        }

        $damageProduct->update(['status' => 'cancelled', 'cancelled_by' => auth()->id()]);

        return successResponse('Damage product cancelled.');
    }

    public function destroy(DamageProduct $damageProduct)
    {
        if ($damageProduct->status !== 'draft') {
            throw new GeneralException('Only draft records can be deleted.', 400);
        }

        $damageProduct->items()->delete();
        $damageProduct->delete();

        return successResponse('Damage product deleted successfully.');
    }
}
