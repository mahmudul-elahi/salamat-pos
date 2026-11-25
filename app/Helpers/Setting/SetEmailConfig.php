<?php

namespace App\Helpers\Setting;

use App\Repositories\Settings\SettingRepository;
use App\Services\Setting\EmailSettingService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;

class SetEmailConfig
{
    public function set(): void
    {
        $this->clearConfigCache();
        $mailSettings = $this->getMailSettings();

        $this->setConfig($mailSettings);
    }

    private function clearConfigCache(): void
    {
        Artisan::call('config:clear');
        Artisan::call('config:cache');
    }

    private function getMailSettings(): array
    {
        return resolve(SettingRepository::class)
            ->getDeliverySettings([
                optional(resolve(EmailSettingService::class)
                    ->getDefaultSettings('default_mail'))->value,
                'default_mail_email_name',
            ]
            );
    }

    private function setConfig(array $mailSettings): void
    {
        if ($mailSettings !== []) {
            Config::set('mail.default', $mailSettings['provider']);
            Config::set('mail.from.address', $mailSettings['from_email']);
            Config::set('mail.from.name', $mailSettings['from_name']);

            if ($mailSettings['provider'] == 'smtp') {
                Config::set('mail.mailers.smtp.host', $mailSettings['smtp_host']);
                Config::set('mail.mailers.smtp.port', $mailSettings['smtp_port']);
                Config::set('mail.mailers.smtp.encryption', $mailSettings['encryption_type']);
                Config::set('mail.mailers.smtp.username', $mailSettings['smtp_username']);
                Config::set('mail.mailers.smtp.password', $mailSettings['email_password']);
            }
        }
    }
}
