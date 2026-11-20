<?php

namespace Database\Seeders\Seo;

use App\Models\Landlord\Brand\Brand;
use App\Models\Landlord\Category\Category;
use App\Models\Pos\Seo\Seo;
use Illuminate\Database\Seeder;

class CategorySeoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tenantId = 3;
        $categories = Category::all();

        // Existing SEO records → tenant assign
        Seo::query()->where('model_type', "App\Models\Category")
            ->whereNull('tenant_id')
            ->update([
                'tenant_id' => $tenantId,
                'model_type' => Category::class,
            ]);

        foreach ($categories as $category) {

            $seo = Seo::query()->firstOrNew([
                'model_type' => Category::class,
                'model_id' => $category->id,
                'tenant_id' => $tenantId,
            ]);

            // Only these fields will update
            $seo->tags = $category->tags;
            $seo->title = $category->name;
            $seo->save();
        }
    }
}
