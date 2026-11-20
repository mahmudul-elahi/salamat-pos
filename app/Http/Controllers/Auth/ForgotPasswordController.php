<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Jobs\Auth\ForgotPasswordJob;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ForgotPasswordController extends Controller
{
    public function forgotPassword(ForgotPasswordRequest $request): \Illuminate\Http\JsonResponse
    {
        $user = User::query()
            ->where('email', $request->email)
            ->first();

        // Generate a unique token

        $token = Str::random(60) . microtime();

        // Insert or update the password reset token in the database
        DB::table('password_reset_tokens')
            ->updateOrInsert([
                'email' => $user->email,
            ], [
                'token' => $token,
                'created_at' => now(),
            ]);

        dispatch(new ForgotPasswordJob($user, $token))->onQueue('high-priority');

        return successResponse('Forgot password link sent successfully');

    }
}
