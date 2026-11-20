<?php

namespace App\Services\Setting;

use App\Models\Tenant\Tenant;
use App\Repositories\Settings\SettingRepository;
use App\Services\BaseService;
use Illuminate\Http\UploadedFile;

class SettingService extends BaseService
{
    public function update($context = 'app', $tenantId = null)
    {
        $settings = request()->except('allowed_resource', 'permissions');

        return collect(array_keys($settings))->map(function ($key) use ($context, $tenantId, $settings) {

            if ($tenantId) {
                $setting = resolve(SettingRepository::class)
                    ->createSettingInstance($key, $context, Tenant::class, $tenantId);
            } else {
                $setting = resolve(SettingRepository::class)
                    ->createSettingInstance($key, $context);
            }

            if (request()->file($key)) {


                $setting->clearMediaCollection($key);

                $media = $setting->addMedia(request()->file($key))->toMediaCollection($key);
                $settings[$key] = $media?->getFullUrl();
            }

            $this->setModel($setting);

            return parent::save([
                'name' => $key,
                'value' => $settings[$key],
                'context' => $context,
                'settingable_type' => $tenantId ? Tenant::class : null,
                'settingable_id' => $tenantId ?? null
            ]);
        });
    }

    public function getFormattedSettings($context = 'app', $tenantId = null)
    {
        if ($tenantId) {
            return resolve(SettingRepository::class)
                ->getFormattedSettings($context, Tenant::class, $tenantId);
        }
        return resolve(SettingRepository::class)
            ->getFormattedSettings($context);
    }

    public function setDefaultSettings($key, $value, $context = 'mail', $settingAbleType = null, $settingAbleId = null)
    {
        $setting = resolve(SettingRepository::class)
            ->createSettingInstance($key, $context, $settingAbleType, $settingAbleId);

        $setting->fill([
            'name' => $key,
            'value' => $value,
            'context' => $context,
            'settingable_type' => $settingAbleType,
            'settingable_id' => $settingAbleId
        ]);

        return $setting->save();
    }
}
