<?php

namespace Database\Seeders\Contact;

use App\Models\Pos\Contact\Contact;
use App\Models\Pos\PurchaseReturn\PurchaseReturn;
use Illuminate\Database\Seeder;

class PurchaseReturnSupplierToContactSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $purchases = PurchaseReturn::withTrashed()
            ->with('supplier:id,phone')
            ->get();

        $purchases->each(function (PurchaseReturn $purchase) {
            if ($purchase->supplier && $purchase->supplier->phone) {
                $contact = Contact::query()
                    ->where('phone', $purchase->supplier->phone)
                    ->first();

                if ($contact) {
                    $purchase->update([
                        'contact_id' => $contact->id,
                    ]);
                }
            }

        });
    }
}
