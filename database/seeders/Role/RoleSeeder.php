<?php

namespace Database\Seeders\Role;

use App\Models\Role\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Role::query()->create([
            'name' => 'Administrator',
            'alias' => null,
            'is_admin' => 1,
            'is_default' => 1
        ]);
    }
}
