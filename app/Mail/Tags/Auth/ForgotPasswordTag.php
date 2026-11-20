<?php

namespace App\Mail\Tags\Auth;

use App\Helpers\Notification\TagHelper;
use App\Models\Common\User\UserProfile;

class ForgotPasswordTag extends TagHelper
{
    public function __construct(protected $user, protected $token)
    {

    }

    function tag(): array
    {
        return array_merge($this->common(), [
            '{receiver_name}' => $this->getUserName(),
            '{button_url}' => env('APP_URL') . "/confirm-password?token=" . $this->token
        ]);
    }

    private function getUserName(): string
    {
        $profile = UserProfile::query()
            ->where('user_id', $this->user->id)
            ->first();

        return ucfirst($profile?->full_name);
    }
}
