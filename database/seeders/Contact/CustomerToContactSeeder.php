<?php

namespace Database\Seeders\Contact;

use App\Models\Customer\Customer;
use App\Models\Pos\Contact\Contact;
use Illuminate\Database\Seeder;

class CustomerToContactSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $customers = Customer::query()
            ->get();


        $customers->each(function ($customer) {
                Contact::query()
                    ->create([
                        'name' => $customer->name,
                        'email' => $customer->email,
                        'phone' => $customer->phone,
                        'secondary_phone' => $customer->secondary_phone,
                        'address' => $customer->address,
                        'new_district_id' => $customer->new_district_id,
                        'area_id' => $customer->area_id,
                        'business_name' => $customer->business_name,
                        'type' => $customer->customer_type,
                        'status' => $customer->status,
                        'platform_url' => $customer->platform_url,
                        'business_logo' => $customer->business_logo,
                        'order_channel' => $customer->order_channel,
                        'trade_license_number' => $customer->trade_license_number,
                        'tin_number' => $customer->tin_number,
                        'password' => $customer->password,
                        'email_verified_at' => $customer->email_verified_at,
                        'last_login_at' => $customer->last_login_at,
                    ]);
        });
    }
}
