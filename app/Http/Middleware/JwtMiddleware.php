<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class JwtMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            // Check JWT Token
            $user = JWTAuth::parseToken()->authenticate();
        } catch (JWTException $e) {
            // If token is invalid
            return response()->json(['message' => 'Token is invalid or missing'], 401);
        }

        return $next($request);
    }
}
