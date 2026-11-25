<?php

namespace App\Services\Pos\Product;

use App\Models\Pos\Product\ProductVariant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * The price a variant actually sells for, using the same rule as the storefront
 * (tekzo-api VariantDiscount): an active offer with a discount wins over the variant
 * price's own discount, and a percent price discount wins over a fixed one.
 */
class VariantSellingPriceService
{
    /**
     * @param  Collection<int, ProductVariant>  $variants  with their `price` relation loaded
     * @return array<int, float> selling price keyed by variant id
     */
    public function forVariants(Collection $variants): array
    {
        $activeOffers = $this->activeOffers($variants->pluck('id'));

        return $variants->mapWithKeys(function (ProductVariant $variant) use ($activeOffers): array {
            $regularPrice = (float) $variant->price?->max_retail_price;
            $offer = $activeOffers->get($variant->id);

            $discount = $offer
                ? $this->discount($regularPrice, $offer->discount_type === 'percentage' ? 'percent' : 'fixed', (float) $offer->discount_amount)
                : $this->priceDiscount($variant, $regularPrice);

            return [$variant->id => max(0, round($regularPrice - $discount, 2))];
        })->all();
    }

    /**
     * @param  Collection<int, int>  $variantIds
     * @return Collection<int, \stdClass> active offer lines (discount_type, discount_amount) keyed by variant id
     */
    private function activeOffers(Collection $variantIds): Collection
    {
        return DB::table('offer_products')
            ->join('offers', 'offers.id', '=', 'offer_products.offer_id')
            ->whereIn('offer_products.product_variant_id', $variantIds)
            ->where('offer_products.discount_amount', '>', 0)
            ->where('offers.is_active', 1)
            ->where('offers.start_at', '<=', now())
            ->where('offers.end_at', '>=', now())
            ->orderByDesc('offers.priority')
            ->get(['offer_products.product_variant_id', 'offer_products.discount_type', 'offer_products.discount_amount'])
            ->unique('product_variant_id')
            ->keyBy('product_variant_id');
    }

    private function priceDiscount(ProductVariant $variant, float $regularPrice): float
    {
        $discountPercent = (float) $variant->price?->discount_percent;

        if ($discountPercent > 0) {
            return $this->discount($regularPrice, 'percent', $discountPercent);
        }

        return $this->discount($regularPrice, 'fixed', (float) $variant->price?->discount_fixed);
    }

    private function discount(float $regularPrice, string $type, float $amount): float
    {
        return $type === 'percent' ? $regularPrice * $amount / 100 : $amount;
    }
}
