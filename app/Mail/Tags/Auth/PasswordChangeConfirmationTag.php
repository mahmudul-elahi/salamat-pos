<?php

namespace App\Mail\Tags\Auth;

use App\Helpers\Notification\TagHelper;
use App\Models\Common\User\UserProfile;

class PasswordChangeConfirmationTag extends TagHelper
{

    public function __construct(protected $user)
    {

    }

    function tag(): array
    {
        return array_merge($this->common(), [
            '{receiver_name}' => $this->getUserName(),
        ]);
    }

    private function getUserName()
    {
        $userProfile = UserProfile::query()
            ->where('user_id', $this->user->id)
            ->first();

        return $userProfile?->full_name;
    }
}
