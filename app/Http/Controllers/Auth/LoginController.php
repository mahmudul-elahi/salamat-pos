<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\Auth\AuthUserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function login(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
            'password' => 'required'
        ], [
            'email.exists' => 'User not found'
        ]);

        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            $user = Auth::user()->load('tenant');

            if ($user->status !== 'active') {
                Auth::logout();
                return errorResponse('Your account is inactive. Please contact the administrator.');
            }

            $token = $user->createToken("{$user->email}-AuthToken")->plainTextToken;

            return successResponse('Login successfully',
                [
                    'access_token' => $token,
                    'user' => new AuthUserResource($user),
                ]);
        }

        return errorResponse('Invalid credentials');
    }
}
