<?php

declare(strict_types=1);

namespace App\Mail\Tags\Landlord;

use App\Helpers\Notification\TagHelper;

class UserInvitationTag extends TagHelper
{
    public function __construct(protected $user, protected $userProfile) {}

    public function tag(): array
    {
        return [
            '{app_name}' => config('app.name'),
            '{app_logo}' => config('app.url').config('settings.application.company_logo'),
            '{button_url}' => config('app.url')."/user-join?token={$this->userProfile->invitation_token}",
        ];
    }
}
