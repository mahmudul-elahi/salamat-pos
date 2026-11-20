<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\ProfileRequest;
use App\Http\Resources\Profile\ProfileResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProfileController extends Controller
{
    public function index(): \Illuminate\Http\JsonResponse
    {

        $userId = auth()->id();

        $user = $this->getUserProfile($userId);

        return successResponse('Profile data successfully', new ProfileResource($user));
    }

    public function update(ProfileRequest $request): \Illuminate\Http\JsonResponse
    {

        try {
            DB::beginTransaction();
            $user = auth()->user();
            $user->update([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
            ]);

            DB::commit();
            return successResponse('Profile updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            if (app()->environment('production')) {
                return errorResponse('Profile update has been failed', null, 500);
            }
            return errorResponse($e->getMessage(), null, 500);
        }
    }

    public function avatar(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'profile_picture' => 'required|image|mimes:jpeg,png,jpg,svg|max:2048',
        ]);

        $user = auth()->user();

        $user->clearMediaCollection('profile_picture');
        $media = $user->addMediaFromRequest('profile_picture')->toMediaCollection('profile_picture');

        return successResponse('Profile picture updated successfully', [
            'profile_picture' => $media->getFullUrl(),
        ]);
    }

    public function timezones(): \Illuminate\Http\JsonResponse
    {
        $timezones = collect(\DateTimeZone::listIdentifiers())
            ->map(fn(string $tz) => ['value' => $tz, 'label' => $tz])
            ->values();

        return successResponse('Timezones fetched successfully', $timezones);
    }

    public function updateTimezone(Request $request): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'timezone' => ['required', 'string', 'timezone'],
        ]);

        auth()->user()->update(['timezone' => $validated['timezone']]);

        return successResponse('Timezone updated successfully');
    }

    public function logout(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return successResponse('Logout successfully');
    }


    private function getUserProfile(int $userId): null|object
    {
        return User::query()
            ->where('id', $userId)
            ->first();
    }
}
