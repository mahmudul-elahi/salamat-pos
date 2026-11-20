<?php

namespace App\Http\Controllers\Ecommerce\PreOwned;

use App\Exceptions\GeneralException;
use App\Filters\Pos\PreOwned\PreOwnedFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\PreOwned\PreOwnedRequest;
use App\Models\Pos\Product\ProductSerial;
use App\Models\PreOwned\PreOwned;
use App\Models\PreOwned\PreOwnedAttribute;
use App\Models\PreOwned\PreOwnedImage;
use App\Scopes\TenantScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PreOwnedProductController extends Controller
{
    public function index()
    {
        return PreOwned::query()
            ->filters(new PreOwnedFilter())
            ->with(['product:id,product_name', 'variant.attributeValues',])
            // Available (is_sold = 0) items always sort above sold ones.
            ->orderByRaw('CASE WHEN is_sold = 0 THEN 0 ELSE 1 END')
            ->orderByDesc('id')
            ->paginate(request('per_page', 10))
            ->through(function ($item) {
                return [
                    ...$item->toArray(),
                    'product_name' => $item?->product->product_name,
                    'product_image' => $item?->thumbnail_image,
                    'attributes' => $item->variant?->attributeValues ? implode('/', $item->variant->attributeValues->pluck('value')->toArray()) : [],
                ];
            });
    }

    public function store(PreOwnedRequest $request)
    {

        $checkSerialNumber = ProductSerial::query()
            ->where('serial_number', $request->serial_number)
            ->where('product_id', $request->product_id)
            ->whereHas('tenantProductSerials', function ($query) {
                $query->whereIn('status', ['purchase', 'sale_return'])
                    ->withoutGlobalScope(TenantScope::class);
            })
            ->exists();

        if (!$checkSerialNumber) {
            throw new GeneralException('The provided serial number does not exist in the system');
        }

        try {
            DB::beginTransaction();
            $preOwned = PreOwned::query()->create($request->only([
                'product_id',
                'product_variant_id',
                'price',
                'booking_amount',
                'purchase_point',
                'serial_number',
                'battery_health',
                'discount_type',
                'discount_amount',
                'box',
                'charging_time',
                'official_service_warranty',
                'thumbnail_image',
                'description',
            ]));


            foreach ($request->variant_attributes as $attr) {
                PreOwnedAttribute::create([
                    'pre_owned_id' => $preOwned->id,
                    'attribute_id' => $attr['attribute_id'],
                    'attribute_value_id' => $attr['attribute_value_id'],
                ]);
            }

            if (count($request->attachments) > 0) {
                foreach ($request->attachments as $index => $photo) {
                    PreOwnedImage::query()->create([
                        'pre_owned_id' => $preOwned->id,
                        'image' => $photo,
                        'is_primary' => $index === 0, // First photo is primary
                    ]);
                }
            }
            DB::commit();
            return successResponse('Pre-owned product created successfully',);
        } catch (\Exception $e) {
            DB::rollBack();
            throw new GeneralException(app()->environment('production') ? 'Failed to create pre-owned product' : $e->getMessage(), 500);
        }
    }

    public function show(PreOwned $preOwnedProduct)
    {
        return $preOwnedProduct->load(['images', 'attribute.attributeValue']);
    }

    public function update(PreOwnedRequest $request, PreOwned $preOwnedProduct)
    {
        $checkSerialNumber = ProductSerial::query()
            ->where('serial_number', $request->serial_number)
            ->where('product_id', $request->product_id)
            ->whereHas('tenantProductSerials', function ($query) {
                $query->whereIn('status', ['purchase', 'sale_return'])
                    ->withoutGlobalScope(TenantScope::class);
            })
            ->exists();

        if (!$checkSerialNumber) {
            throw new GeneralException('The provided serial number does not exist in the system');
        }

        try {
            DB::beginTransaction();
            $preOwnedProduct->update($request->only([
                'price',
                'booking_amount',
                'purchase_point',
                'product_variant_id',
                'serial_number',
                'discount_type',
                'discount_amount',
                'box',
                'charging_time',
                'official_service_warranty',
                'battery_health',
                'thumbnail_image',
                'description',
            ]));

            // Update attributes
            $existingCollection = $preOwnedProduct->attribute()->get();

            $newCollection = collect($request->variant_attributes);

            // Delete removed ones
            foreach ($existingCollection as $old) {
                $exists = $newCollection->first(
                    fn($n) =>
                    $n['attribute_id'] == $old->attribute_id &&
                        $n['attribute_value_id'] == $old->attribute_value_id
                );

                if (!$exists) {
                    $old->delete();
                }
            }

            // Insert new ones
            foreach ($newCollection as $attr) {
                $exists = $existingCollection->first(
                    fn($old) =>
                    $old->attribute_id == $attr['attribute_id'] &&
                        $old->attribute_value_id == $attr['attribute_value_id']
                );

                if (!$exists) {
                    PreOwnedAttribute::create([
                        'pre_owned_id' => $preOwnedProduct->id,
                        'attribute_id' => $attr['attribute_id'],
                        'attribute_value_id' => $attr['attribute_value_id'],
                    ]);
                }
            }
            if (count($request->attachments) > 0) {
                foreach ($request->attachments as $index => $photo) {
                    PreOwnedImage::query()->create([
                        'pre_owned_id' => $preOwnedProduct->id,
                        'image' => $photo,
                        'is_primary' => $index === 0, // First photo is primary
                    ]);
                }
            }

            if (count($request->deletable_attachment_ids) > 0) {
                PreOwnedImage::query()->whereIn('id', $request->deletable_attachment_ids)->delete();
            }

            DB::commit();
            return successResponse('Pre-owned product updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            throw new GeneralException(app()->environment('production') ? 'Failed to update pre-owned product' : $e->getMessage(), 500);
        }
    }

    public function destroy(PreOwned $preOwnedProduct)
    {
        try {
            DB::beginTransaction();
            $preOwnedProduct->delete();
            DB::commit();
            return successResponse('Pre-owned product deleted successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            throw new GeneralException(app()->environment('production') ? 'Failed to delete pre-owned product' : $e->getMessage(), 500);
        }
    }

    public function statusUpdate(Request $request, PreOwned $preOwned)
    {
        $request->validate([
            'status' => 'required|boolean',
        ]);


        if ($preOwned->is_sold == $request->status) {
            throw new GeneralException('Pre-owned product status is already in use');
        }

        $preOwned->update(['is_sold' => $request->status]);
        return successResponse('Pre-owned product status updated successfully');
    }

    /**
     * Focused price/discount/box update for the "Update Price" action —
     * unlike update(), it doesn't re-validate the serial number or touch
     * attributes/attachments, since those aren't changing here.
     */
    public function updatePrice(Request $request, PreOwned $preOwned)
    {
        $validated = $request->validate([
            'price' => ['required', 'numeric', 'min:0'],
            'discount_type' => ['nullable', 'string', 'in:fixed,percent'],
            'discount_amount' => [
                'nullable', 'numeric', 'min:0', 'required_with:discount_type',
                Rule::when($request->input('discount_type') === 'percent', ['max:100']),
                // A fixed discount can't exceed the price itself.
                Rule::when($request->input('discount_type') === 'fixed', ['lte:price']),
            ],
            'box' => ['required', 'boolean'],
        ]);

        // discount_amount is NOT NULL at the DB level (defaults to 0) — clearing
        // the discount type without this would fail with an integrity error.
        $validated['discount_amount'] = $validated['discount_amount'] ?? 0;

        $preOwned->update($validated);

        return successResponse('Pre-owned product price updated successfully');
    }
}
