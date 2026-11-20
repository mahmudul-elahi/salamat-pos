<?php

namespace App\Services\Landlord\User;

use App\Jobs\Landlord\User\InviteUserJob;
use App\Models\Common\User\UserProfile;
use App\Models\User;
use App\Services\BaseService;
use App\Services\Traits\HasWhen;

class UserService extends BaseService
{
    use HasWhen;

    public function __construct(User $model)
    {
        $this->model = $model;
    }

    public function createUser(): static
    {
        $this->model->fill([
            'email' => request('email'),
        ])->save();

        return $this;
    }

    public function createUserProfile(): static
    {
        $this->model->userProfile()->create([
            'invitation_token' => base64_encode(request('email').'-invitation-from-token'),
            'status' => 'invited',
        ]);

        return $this;
    }

    public function assignRole(): static
    {
        $this->model->roles()->attach(request('role'));

        return $this;
    }

    public function sendInvitationMail(): static
    {
        $userProfile = UserProfile::query()
            ->where('user_id', $this->model->id)
            ->first();

        dispatch(new InviteUserJob($this->model, $userProfile))->onQueue('high-priority');

        return $this;
    }
}
