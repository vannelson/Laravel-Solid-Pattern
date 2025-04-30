<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Auth0\SDK\JWTVerifier;

class Auth0Middleware
{
    public function handle(Request $request, Closure $next)
    {
        $verifier = new JWTVerifier([
            'authorized_iss' => [env('AUTH0_DOMAIN')],
            'supported_algs' => ['RS256'],
            'valid_audiences' => [env('AUTH0_AUDIENCE')],
        ]);

        try {
            $token = $request->bearerToken();
            $decoded = $verifier->verify($token);
            $request->auth = $decoded;
        } catch (\Exception $e) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}
