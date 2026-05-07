<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        // must be logged in
        if (!$request->user()) {
            return redirect('/login');
        }

        // check role
        if (!in_array($request->user()->role, $roles)) {
            abort(403, 'Unauthorized');
        }

        return $next($request);
    }
}