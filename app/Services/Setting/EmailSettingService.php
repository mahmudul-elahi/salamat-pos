<?php

namespace App\Services\Setting;

use App\Models\Setting\Setting;
use App\Repositories\Settings\SettingRepository;
use App\Services\BaseService;

class EmailSettingService extends BaseService
{
    protected SettingRepository $settingRepository;

    public function __construct(SettingRepository $settingRepository)
    {
        $this->settingRepository = $settingRepository;
    }

    /**
     * Update the email setting.
     *
     * @param string $key
     * @param mixed $value
     * @param string|null $context
     */
    public function update(string $key, mixed $value, ?string $context = null): void
    {
        $setting = $this->settingRepository->createOrRetrieveSetting($key, $context);

        $setting->fill([
            'name' => $key,
            'value' => $value,
            'context' => $context,
            'settingable_type' =>  null,
            'settingable_id' => null
        ])->save();
    }

    /**
     * Set default email settings.
     *
     * @param string $key
     * @param mixed $value
     * @param string $context
     * @return bool
     */
    public function setDefaultSettings(string $key, mixed $value, string $context = 'mail'): bool
    {
        $setting = $this->settingRepository->createOrRetrieveSetting($key, $context);

        $setting->fill([
            'name' => $key,
            'value' => $value,
            'context' => $context,
            'settingable_type' => null,
            'settingable_id' =>  null
        ]);

        return $setting->save();
    }


    public function getFormattedDeliverySettings($context): array
    {
        return $this->settingRepository->getDeliverySettings($context);
    }

    /**
     * Get the default email settings.
     *
     * @param string $key
     * @return Setting|null
     */
    public function getDefaultSettings(string $key = 'default_mail'): ?Setting
    {
        return $this->settingRepository->getDefaultMailKey($key);
    }
}
