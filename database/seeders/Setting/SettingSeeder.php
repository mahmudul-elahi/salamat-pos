<?php

namespace Database\Seeders\Setting;

use App\Models\Setting\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Setting::query()->insert(config('settings.app'));

        // Store logo and update 'value' with media URL
        $logo = Setting::query()->create([
            'name' => 'company_logo',
        ]);
        $logo->addMediaFromUrl(url('assets/images/logo/logo.svg'))
            ->toMediaCollection('company_logo');

        $logo->update([
            'value' => $logo->getFirstMediaUrl('company_logo'),
        ]);


        // Store icon and update
        $icon = Setting::query()->create([
            'name' => 'company_icon',
        ]);
        $icon->addMediaFromUrl(url('assets/images/icons/icon.svg'))
            ->toMediaCollection('company_icon');

        $icon->update([
            'value' => $icon->getFirstMediaUrl('company_icon'),
        ]);

        // Store banner and update
        $banner = Setting::query()->create([
            'name' => 'company_banner',
        ]);
        $banner->addMediaFromUrl(url('assets/images/banner/banner.png'))
            ->toMediaCollection('company_banner');

        $banner->update([
            'value' => $banner->getFirstMediaUrl('company_banner'),
        ]);
    }
}
