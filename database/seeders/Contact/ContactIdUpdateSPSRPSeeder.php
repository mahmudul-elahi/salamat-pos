<?php

namespace Database\Seeders\Contact;

use App\Exceptions\GeneralException;
use App\Models\Pos\Ledger\Ledger;
use App\Models\Pos\Purchase\Purchase;
use App\Models\Pos\PurchaseReturn\PurchaseReturn;
use App\Models\Pos\Sale\Sale;
use App\Models\Pos\SaleReturn\SaleReturn;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ContactIdUpdateSPSRPSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        try {
            DB::beginTransaction();
            Purchase::query()
                ->withoutGlobalScope(new \App\Scopes\TenantScope())
                ->where('contact_id', 3332)
                ->get()
                ->each(function ($saleReturn) {
                    $saleReturn->update([
                        'contact_id' => 3028
                    ]);
                });

            PurchaseReturn::query()
                ->withoutGlobalScope(new \App\Scopes\TenantScope())
                ->where('contact_id', 3332)
                ->get()
                ->each(function ($saleReturn) {
                    $saleReturn->update([
                        'contact_id' => 3028
                    ]);
                });

            Sale::query()
                ->withoutGlobalScope(new \App\Scopes\TenantScope())
                ->where('contact_id', 3332)
                ->get()
                ->each(function ($saleReturn) {
                    $saleReturn->update([
                        'contact_id' => 3028
                    ]);
                });

            SaleReturn::query()
                ->withoutGlobalScope(new \App\Scopes\TenantScope())
                ->where('contact_id', 3332)
                ->get()
                ->each(function ($saleReturn) {
                    $saleReturn->update([
                        'contact_id' => 3028
                    ]);
                });

            Ledger::query()
                ->where('contact_id', 3332)
                ->get()
                ->each(function ($saleReturn) {
                    $saleReturn->update([
                        'contact_id' => 3028
                    ]);
                });

            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            throw new GeneralException($exception->getMessage(), 500);
        }
    }
}
