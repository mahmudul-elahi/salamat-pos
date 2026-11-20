<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ConfirmPasswordRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ConfirmPasswordController extends Controller
{
    public function confirmPassword(ConfirmPasswordRequest $request): JsonResponse
    {
        // Retrieve the token record from the database
        $resetToken = DB::table('password_reset_tokens')
            ->where('token', $request->get('token'))
            ->first();

        // Check if the token exists
        if (! $resetToken) {
            return errorResponse('Invalid token');
        }

        // Check if the token has expired (20 minutes validity)
        $tokenAgeInMinutes = Carbon::parse($resetToken->created_at)->diffInMinutes(now());
        if ($tokenAgeInMinutes >= 20) {
            return errorResponse('Token has expired');
        }

        // Update the user's password
        User::query()
            ->where('email', $resetToken->email)
            ->update(['password' => Hash::make($request->get('password'))]);

        // Delete the token from the database
        DB::table('password_reset_tokens')
            ->where('token', $resetToken->token)
            ->delete();

        // Return success response
        return successResponse('Password updated successfully');
    }
}
