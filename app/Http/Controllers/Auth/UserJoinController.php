<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\UserJoinRequest;
use App\Models\Common\User\UserProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserJoinController extends Controller
{
    public function availableToken($token): \Illuminate\Http\JsonResponse
    {
        $userProfile = UserProfile::query()
            ->where('invitation_token', $token)
            ->where('status', 'invited')
            ->first();
        if (!$userProfile) {
            return errorResponse('User not found', null, 404);
        }

        if (now()->diffInDays($userProfile->created_at) > 3) {
            return errorResponse('Invitation expired');
        }

        return successResponse('Token is available');
    }

    public function join(UserJoinRequest $request)
    {
        $userProfile = UserProfile::query()
            ->where('invitation_token', $request->get('token'))
            ->where('status', 'invited')
            ->first();
        if (!$userProfile) {
            return errorResponse('User not found', null, 404);
        }
        // 3 days invitation period

        if (now()->diffInDays($userProfile->created_at) > 3) {
            return errorResponse('Invitation expired');
        }

        try {
            DB::beginTransaction();
            User::query()
                ->where('id', $userProfile->user_id)
                ->update([
                    'password' => bcrypt($request->get('password')),
                ]);
            $userProfile->update([
                'status' => 'active',
                'first_name' => $request->get('first_name'),
                'last_name' => $request->get('last_name'),
            ]);
            DB::commit();

            return successResponse('User joined successfully');
        } catch (\Exception $exception) {
            DB::rollBack();
            if (app()->environment('production')) {
                return errorResponse("User joined has been failed", null, 500);
            }
            return errorResponse($exception->getMessage(), null, 500);
        }
    }
}
