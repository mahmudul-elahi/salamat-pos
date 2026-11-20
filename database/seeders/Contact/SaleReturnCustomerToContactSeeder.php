<?php

namespace Database\Seeders\Contact;

use App\Models\Pos\Contact\Contact;
use App\Models\Pos\SaleReturn\SaleReturn;
use Illuminate\Database\Seeder;

class SaleReturnCustomerToContactSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sales = SaleReturn::withTrashed()
            ->with('customer:id,phone')
            ->get();

        $sales->each(function (SaleReturn $sale) {
            if ($sale->customer && $sale->customer->phone) {
                $contact = Contact::query()
                    ->where('phone', $sale->customer->phone)
                    ->first();

                if ($contact) {
                    $sale->update([
                        'contact_id' => $contact->id,
                    ]);
                }
            }

        });

    }
}
