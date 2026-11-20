<?php

namespace Database\Seeders;

use Database\Seeders\Country\CountrySeeder;
use Database\Seeders\Customer\UserTableCustomerMigrateSeeder;
use Database\Seeders\Permission\PermissionSeeder;
use Database\Seeders\Role\RoleSeeder;
use Database\Seeders\Setting\SettingSeeder;
use Database\Seeders\Template\TemplateSeeder;
use Database\Seeders\Template\TemplateTypeSeeder;
use Illuminate\Database\Seeder;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            SettingSeeder::class,
            CountrySeeder::class,
            RoleSeeder::class,
            PermissionSeeder::class,
            TemplateTypeSeeder::class,
            TemplateSeeder::class,

            // User to customer table data move seeder

            UserTableCustomerMigrateSeeder::class,

        ]);

    }
}
