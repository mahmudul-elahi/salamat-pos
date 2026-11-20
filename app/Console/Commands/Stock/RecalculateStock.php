<?php

namespace App\Console\Commands\Stock;

use App\Models\Pos\Stock\Stock;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RecalculateStock extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stock:recalculate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculate stock from all transaction';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Stock recalculation started...');

        $stocks = [];

        /*
        |--------------------------------------------------------------------------
        | 1️⃣ Purchase (+)
        |--------------------------------------------------------------------------
        */
        DB::table('purchase_items')
            ->join('purchases', 'purchases.id', '=', 'purchase_items.purchase_id')
            ->selectRaw('purchases.tenant_id, purchase_items.product_id, purchase_items.product_variant_id, SUM(purchase_items.quantity) as qty')
            ->groupBy('purchases.tenant_id', 'purchase_items.product_id', 'purchase_items.product_variant_id')
            ->chunkById(200, function ($items) use (&$stocks) {
                foreach ($items as $item) {
                    $key = $item->tenant_id.'-'.$item->product_id.'-'.$item->product_variant_id;

                    $stocks[$key]['tenant_id'] = $item->tenant_id;
                    $stocks[$key]['product_id'] = $item->product_id;
                    $stocks[$key]['product_variant_id'] = $item->product_variant_id;
                    $stocks[$key]['quantity'] = ($stocks[$key]['quantity'] ?? 0) + $item->qty;
                }
            }, 'product_id');

        /*
        |--------------------------------------------------------------------------
        | 2️⃣ Purchase Return (-)
        |--------------------------------------------------------------------------
        */
        DB::table('purchase_return_items')
            ->join('purchase_returns', 'purchase_returns.id', '=', 'purchase_return_items.purchase_return_id')
            ->selectRaw('purchase_returns.tenant_id, purchase_return_items.product_id, purchase_return_items.product_variant_id, SUM(purchase_return_items.quantity) as qty')
            ->groupBy('purchase_returns.tenant_id', 'purchase_return_items.product_id', 'purchase_return_items.product_variant_id')
            ->chunkById(200, function ($items) use (&$stocks) {
                foreach ($items as $item) {

                    $key = $item->tenant_id.'-'.$item->product_id.'-'.$item->product_variant_id;

                    // always assign keys
                    $stocks[$key]['tenant_id'] = $item->tenant_id;
                    $stocks[$key]['product_id'] = $item->product_id;
                    $stocks[$key]['product_variant_id'] = $item->product_variant_id;

                    $stocks[$key]['quantity'] = ($stocks[$key]['quantity'] ?? 0) - $item->qty;
                }
            }, 'product_id');

        /*
        |--------------------------------------------------------------------------
        | 3️⃣ Sale (-)
        |--------------------------------------------------------------------------
        */
        DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->selectRaw('sales.tenant_id, sale_items.product_id, sale_items.product_variant_id, SUM(sale_items.quantity) as qty')
            ->groupBy('sales.tenant_id', 'sale_items.product_id', 'sale_items.product_variant_id')
            ->chunkById(200, function ($items) use (&$stocks) {
                foreach ($items as $item) {
                    $key = $item->tenant_id.'-'.$item->product_id.'-'.$item->product_variant_id;

                    $stocks[$key]['tenant_id'] = $item->tenant_id;
                    $stocks[$key]['product_id'] = $item->product_id;
                    $stocks[$key]['product_variant_id'] = $item->product_variant_id;

                    $stocks[$key]['quantity'] = ($stocks[$key]['quantity'] ?? 0) - $item->qty;
                }
            }, 'product_id');

        /*
        |--------------------------------------------------------------------------
        | 4️⃣ Sale Return (+)
        |--------------------------------------------------------------------------
        */
        DB::table('sale_return_items')
            ->join('sale_returns', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
            ->selectRaw('sale_returns.tenant_id, sale_return_items.product_id, sale_return_items.product_variant_id, SUM(sale_return_items.quantity) as qty')
            ->groupBy('sale_returns.tenant_id', 'sale_return_items.product_id', 'sale_return_items.product_variant_id')
            ->chunkById(200, function ($items) use (&$stocks) {
                foreach ($items as $item) {
                    $key = $item->tenant_id.'-'.$item->product_id.'-'.$item->product_variant_id;

                    $stocks[$key]['tenant_id'] = $item->tenant_id;
                    $stocks[$key]['product_id'] = $item->product_id;
                    $stocks[$key]['product_variant_id'] = $item->product_variant_id;

                    $stocks[$key]['quantity'] = ($stocks[$key]['quantity'] ?? 0) + $item->qty;
                }
            }, 'product_id');

        /*
        |--------------------------------------------------------------------------
        | 5️⃣ Update Stock Table
        |--------------------------------------------------------------------------
        */
        foreach ($stocks as $stock) {
            //            $this->info($stock['tenant_id'],  $stock['quantity'], $stock['product_variant_id']);
            Stock::updateOrCreate(
                [
                    'tenant_id' => $stock['tenant_id'],
                    'product_variant_id' => $stock['product_variant_id'],
                ],
                [
                    'product_id' => $stock['product_id'],
                    'quantity' => $stock['quantity'],
                ]
            );
        }

        $this->info('Stock recalculation completed successfully.');
    }
}
