<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Ensure the authenticated user has an admin role.
 * Returns 403 when the user is not an admin.
 */
class EnsureAdmin
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user || ($user->role ?? 'member') !== 'admin') {
            return response()->json([
                'message' => 'Forbidden.',
            ], 403);
        }

        return $next($request);
    }
}

