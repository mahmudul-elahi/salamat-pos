<?php

namespace Database\Seeders\Template;

use App\Models\Template\TemplateType;
use Illuminate\Database\Seeder;

class TemplateTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $templateTypes = [
            [
                'name' => 'forget_password',
                'display_name' => 'Forgot password',
                'group_name' => 'password',
            ],
            [
                'name' => 'password_change_confirmation',
                'display_name' => 'Password change confirmation',
                'group_name' => 'password',
            ],
            [
                'name' => 'user_invitation',
                'display_name' => 'User invitation',
                'group_name' => 'users',
            ],
            [
                'name' => 'user_joined',
                'display_name' => 'User joined',
                'group_name' => 'users',
            ],

        ];

        foreach ($templateTypes as $templateType) {
            TemplateType::query()->create($templateType);
        }
    }
}
