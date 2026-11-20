<?php

namespace Database\Seeders\Seo;

use App\Models\Pos\Product\Product;
use App\Models\Pos\Seo\Seo;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductSeoTagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = Product::query()
            ->select(['id', 'tag'])
            ->get()
            ->each(function ($product){

                $seo = Seo::query()
                    ->where('tenant_id', 3)
                    ->where('model_id', $product->id)
                    ->where('model_type', Product::class)
                    ->first();
                if ($seo){
                    $seo->update([
                        'tags' => $product->tag
                    ]);
                }
            });
    }
}
