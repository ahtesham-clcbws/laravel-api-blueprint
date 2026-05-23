<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Register a new user.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $token = \Illuminate\Support\Str::random(60);
        $user = User::create([
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'password' => Hash::make($request->input('password')),
        ]);

        $user->forceFill(['api_token' => $token])->save();

        Auth::login($user);

        return response()->json([
            'message' => 'User registered and authenticated successfully.',
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    /**
     * Authenticate and login a user.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->only('email', 'password');

        if (!Auth::attempt($credentials)) {
            return response()->json([
                'message' => 'Invalid authentication credentials.',
            ], 401);
        }

        $user = Auth::user();
        $token = \Illuminate\Support\Str::random(60);
        $user->forceFill(['api_token' => $token])->save();

        return response()->json([
            'message' => 'Authenticated successfully.',
            'user' => $user,
            'token' => $token,
        ]);
    }

    /**
     * Get the authenticated user details.
     */
    public function user(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        return response()->json([
            'user' => $user->load('orders'),
        ]);
    }

    /**
     * Logout the authenticated user.
     */
    public function logout(Request $request): JsonResponse
    {
        $user = Auth::user();
        if ($user) {
            $user->forceFill(['api_token' => null])->save();
        }
        Auth::logout();

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }
}
