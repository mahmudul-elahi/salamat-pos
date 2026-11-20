<?php

namespace App\Http\Controllers\Landlord\User;

use App\Http\Controllers\Controller;
use App\Services\Landlord\User\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Mailer\Exception\TransportException;

class UserInviteController extends Controller
{
    public function __construct(protected UserService $service) {}

    public function invite(UserInviteRequest $request): JsonResponse
    {
        $email = $request->get('email');

        try {
            $existingUser = $this->checkExistingUser($email);

            if ($existingUser) {
                return errorResponse('User already exists');
            }

            return $this->handleUserInvitation($email, $request->get('role'));

        } catch (TransportException) {
            DB::rollBack();

            return errorResponse('Email setup is not correct');
        } catch (\Exception $exception) {
            DB::rollBack();
            if (app()->environment('production')) {
                return errorResponse('User invitation has been failed', null, 500);
            }

            return errorResponse($exception->getMessage(), null, 500);
        }
    }

    private function checkExistingUser(string $email): ?object
    {
        return $this->service
            ->where('email', $email)
            ->first();

    }

    private function handleUserInvitation(string $email, ?string $role): JsonResponse
    {
        $user = $this->service->where('email', $email)->first();

        if ($user) {
            $this->inviteExistingUser($user);
        } else {
            $this->createAndInviteNewUser($role);
        }

        return successResponse('User invited successfully');

    }

    private function inviteExistingUser($user): void
    {
        DB::transaction(function () use ($user) {
            $this->service
                ->setModel($user)
                ->createUserProfile()
                ->assignRole()
                ->sendInvitationMail();
        });
    }

    private function createAndInviteNewUser(?string $role): void
    {
        DB::transaction(function () use ($role) {
            $this->service
                ->createUser()
                ->createUserProfile()
                ->when($role, fn (UserService $service) => $service->assignRole())
                ->sendInvitationMail();
        });
    }
}
