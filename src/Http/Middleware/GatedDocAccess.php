<?php

declare(strict_types=1);

namespace LaravelApiBlueprint\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class GatedDocAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Check custom Gate authorization if configured
        $gateName = config('api-blueprint.auth.gate');
        if ($gateName) {
            if (!Gate::allows($gateName)) {
                return response('Unauthorized Access to API Blueprint Specification (Gate Checked).', 403);
            }
            return $next($request);
        }

        // 2. Fall back to Basic Auth
        $expectedUser = config('api-blueprint.auth.username');
        $expectedPass = config('api-blueprint.auth.password');

        if ($expectedUser && $expectedPass) {
            if ($request->getUser() !== $expectedUser || $request->getPassword() !== $expectedPass) {
                return response('Unauthorized Access to API Blueprint Specification.', 401, [
                    'WWW-Authenticate' => 'Basic realm="API Blueprint Docs", charset="UTF-8"'
                ]);
            }
        }

        return $next($request);
    }
}
