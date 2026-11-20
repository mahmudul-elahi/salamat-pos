<?php

namespace Database\Seeders\Contact;

use App\Models\Pos\Contact\Contact;
use App\Models\Pos\Supplier\Supplier;
use Illuminate\Database\Seeder;

class SupplierToContactSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Supplier::query()->chunk(500, function ($suppliers) {

            foreach ($suppliers as $supplier) {

                $contact = Contact::query()
                    ->where('phone', $supplier->phone)
                    ->first();

                if ($contact) {
                    $contact->update([
                        'name' => $supplier->name,
                        'business_name' => $supplier->company_name,
                        'type' => 'supplier',
                        'country_id' => $supplier->country_id,
                        'business_logo' => $supplier->business_logo,
                    ]);

                } else {
                    Contact::query()->create([
                        'name' => $supplier->name,
                        'email' => $supplier->email,
                        'phone' => $supplier->phone,
                        'secondary_phone' => $supplier->secondary_phone,
                        'new_district_id' => $supplier->new_district_id,
                        'area_id' => $supplier->area_id,
                        'address' => $supplier->address,
                        'business_name' => $supplier->company_name,
                        'type' => 'supplier',
                        'status' => $supplier->status,
                        'country_id' => $supplier->country_id,
                        'business_logo' => $supplier->business_logo,
                    ]);
                }
            }
        });
    }
}
