<?php

namespace Database\Seeders\Seo;

use App\Models\Landlord\Brand\Brand;
use App\Models\Pos\Seo\Seo;
use Illuminate\Database\Seeder;

class BrandSeoSeeder extends Seeder
{
    public function run(): void
    {
        $tenantId = 3;
        $brands = Brand::all();

        // Existing SEO records → tenant assign
        Seo::query()->where('model_type', "App\Models\Brand")
            ->whereNull('tenant_id')
            ->update([
                'tenant_id' => $tenantId,
                'model_type' => Brand::class,
            ]);

        foreach ($brands as $brand) {

            $seo = Seo::query()->firstOrNew([
                'model_type' => Brand::class,
                'model_id' => $brand->id,
                'tenant_id' => $tenantId,
            ]);

            // Only these fields will update
            $seo->tags = $brand->tags;
            $seo->title = $brand->name;
            $seo->save();
        }
    }
}
