<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\ProfilePasswordRequest;
use App\Jobs\Auth\PasswordChangeConfirmationJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ProfilePasswordController extends Controller
{
    /**
     * @throws ValidationException
     */
    public function password(ProfilePasswordRequest $request): JsonResponse
    {
        $user = auth()->user();

        if (! Hash::check($request->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => 'The provided password was incorrect.',
            ]);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        PasswordChangeConfirmationJob::dispatch($user);

        return successResponse('Password updated successfully');
    }
}
