<?php

namespace Database\Seeders\Sync;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SyncPartyMapperSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('suppliers')->orderBy('id')->chunk(500, function ($suppliers) {
            foreach ($suppliers as $supplier) {

                $customer = DB::table('customers')
                    ->where('phone', $supplier->phone)
//                    ->where(function ($query) use ($supplier) {
//                        $query->where('phone', $supplier->phone);
//                        if ($supplier->email) {
//                            $query->orWhere('email', $supplier->email);
//                        }
//                    })
                    ->first();

                if ($customer) {
                    $tenants = DB::table('supplier_tenant')
                        ->where('supplier_id', $supplier->id)
                        ->pluck('tenant_id');

                    foreach ($tenants as $tenantId) {
                        DB::table('party_mappings')->updateOrInsert(
                            [
                                'customer_id' => $customer->id,
                                'supplier_id' => $supplier->id,
                                'tenant_id' => $tenantId,
                            ],
                            [
                                'updated_at' => now(),
                                'created_at' => now(),
                            ]
                        );
                    }
                }
            }
        });
    }
}
