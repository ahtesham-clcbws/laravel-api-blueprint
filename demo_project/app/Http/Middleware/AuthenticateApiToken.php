<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiToken
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if ($token) {
            $user = User::where('api_token', $token)->first();
            if ($user) {
                Auth::login($user);
            }
        }

        // For protected endpoints, check if the user is authenticated
        if ($request->is('api/auth/user') || $request->is('api/auth/logout') || $request->is('api/orders*')) {
            if (!Auth::check()) {
                return response()->json([
                    'message' => 'Unauthenticated.',
                ], 401);
            }
        }

        return $next($request);
    }
}
