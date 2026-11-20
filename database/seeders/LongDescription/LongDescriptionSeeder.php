<?php

namespace Database\Seeders\LongDescription;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Pos\Product\Product;
use App\Models\Landlord\Category\Category;
use App\Models\Landlord\Brand\Brand;

class LongDescriptionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tenantIds = 3; // 3: Salamat, 4: Tekzo

        Product::query()
            ->selectRaw('id, long_description')
            ->get()
            ->each(function ($product) use ($tenantIds) {
                $product->longDescriptions()->updateOrCreate(
                    ['tenant_id' => $tenantIds],
                    ['description' => $product->getRawOriginal('long_description')]
                );
            });

        // 2. Migrate Categories
        Category::query()
            ->selectRaw('id, description')
            ->each(function ($category) use ($tenantIds) {
                $category->longDescriptions()->updateOrCreate(
                    ['tenant_id' => $tenantIds],
                    ['description' => $category->getRawOriginal('description')]
                );
            });

        // 3. Migrate Brands
        Brand::query()
            ->selectRaw('id, description')
            ->each(function ($brand) use ($tenantIds) {
                $brand->longDescriptions()->updateOrCreate(
                    ['tenant_id' => $tenantIds],
                    ['description' => $brand->getRawOriginal('description')]
                );
            });

        $this->command->info("Success fully synced description");
    }
}
