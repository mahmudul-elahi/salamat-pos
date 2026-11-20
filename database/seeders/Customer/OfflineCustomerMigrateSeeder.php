<?php

namespace Database\Seeders\Customer;

use App\Enums\CustomerTypeEnum;
use App\Models\Customer\Customer;
use App\Models\Pos\Partners\OfflineCustomer;
use Illuminate\Database\Seeder;

class OfflineCustomerMigrateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $offlineCustomers = OfflineCustomer::query()->get();
        $offlineCustomers->each(function ($offlineCustomer) {
            $checkCustomer = Customer::query()
                ->where('email', $offlineCustomer->email)
                ->orWhere('phone', $offlineCustomer->phone)
                ->first();
            if (! $checkCustomer) {
                Customer::query()->create([
                    'name' => $offlineCustomer->name,
                    'email' => $offlineCustomer->email,
                    'phone' => $offlineCustomer->phone,
                    'secondary_phone' => $offlineCustomer->secondary_phone,
                    'customer_type' => $offlineCustomer->customer_type ? $this->getCustomerType($offlineCustomer->customer_type) : 'consumer',
                    'business_name' => $offlineCustomer->business_name,
                    'platform_url' => $offlineCustomer->platform_url,
                    'business_logo' => $offlineCustomer->business_logo,
                    'order_channel' => $offlineCustomer->order_channel,
                    'trade_license_number' => $offlineCustomer->trade_license_number,
                    'tin_number' => $offlineCustomer->tin_number,
                    'area_id' => $offlineCustomer->area_id,
                    'new_district_id' => null,
                    'address' => $offlineCustomer->street_address,
                ]);
            }
        });
    }

    protected function getCustomerType($type)
    {
        if ($type === CustomerTypeEnum::Dealer) {
            return 'dealer';
        } elseif ($type === CustomerTypeEnum::Retailer) {
            return 'retailer';
        } elseif ($type === CustomerTypeEnum::B2B) {
            return 'b2b';
        } elseif ($type === CustomerTypeEnum::Consumer) {
            return 'consumer';
        } elseif ($type === CustomerTypeEnum::Franchise) {
            return 'franchise';
        }
    }
}
