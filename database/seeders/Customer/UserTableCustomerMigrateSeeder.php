<?php

namespace Database\Seeders\Customer;

use App\Models\Customer\Customer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserTableCustomerMigrateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = DB::table('users')
            ->where('role', 'user')
            ->get();

        foreach ($users as $user) {
            Customer::query()->create([
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'password' => $user->password,
                'status' => 'active',
                'email_verified_at' => $user->email_verified_at,
                'last_login_at' => null,
            ]);
        }
    }
}
