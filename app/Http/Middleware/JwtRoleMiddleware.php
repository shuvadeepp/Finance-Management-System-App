<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Exception;

class JwtRoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        try {
            
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user) {
                return response()->json([
                    'message' => 'User not found'
                ], 401);
            }

            $payload = JWTAuth::parseToken()->getPayload();

            $role = $payload->get('role');

            if (!in_array($role, $roles)) {
                return response()->json([
                    'message' => 'Forbidden Access'
                ], 403);
            }

        } catch (Exception $e) {

            return response()->json([
                'message' => 'Invalid or Expired Token'
            ], 401);
        }

        return $next($request);
    }
}