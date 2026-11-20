<?php

namespace App\Mail\Tags\Landlord;

use App\Helpers\Notification\TagHelper;

class UserInvitationTag extends TagHelper
{
    public function __construct(protected $user, protected $userProfile)
    {

    }

    function tag(): array
    {
        return [
            '{app_name}' => config('app.name'),
            '{app_logo}' => env('APP_URL') . config('settings.application.company_logo'),
            '{button_url}' => env('APP_URL') . "/user-join?token={$this->userProfile->invitation_token}"
        ];
    }

}
