<?php

namespace Database\Seeders\Seo;

use App\Models\Pos\Product\Product;
use App\Models\Pos\Seo\Seo;
use Illuminate\Database\Seeder;

class ProductSeoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tenantId = 3;
        $products = Product::all();

        // 1️⃣ Update existing SEO records: assign tenant_id and update model_type
        Seo::query()
            ->where('model_type', "App\Models\Product") // old namespace
            ->whereNull('tenant_id')
            ->update([
                'tenant_id' => $tenantId,
                'model_type' => Product::class, // new namespace
            ]);

        // 2️⃣ Seed/update SEO records for all products
        foreach ($products as $product) {

            $seo = Seo::query()->firstOrNew([
                'model_type' => Product::class,  // ✅ corrected namespace
                'model_id' => $product->id,
                'tenant_id' => $tenantId,
            ]);

            // Only update tags (title/description untouched)
            $seo->tags = $product->tags;

            $seo->save();
        }
    }
}
