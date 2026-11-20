<?php

namespace App\Helpers\Notification;

abstract class TagHelper
{
    abstract function tag();

    public function common(): array
    {
        return [
            '{app_name}' => config('app.name'),
            '{app_logo}' => config('settings.application.company_logo')
        ];

    }

}
