<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use App\Models\User;

class JwtAuthMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ?string $role = null): Response
    {
        $token = $request->header('x-auth-token');
        if (!$token) {
            $authHeader = $request->header('Authorization');
            if ($authHeader && str_starts_with($authHeader, 'Bearer ')) {
                $token = substr($authHeader, 7);
            }
        }

        if (!$token) {
            if ($role === 'maybe') {
                return $next($request);
            }
            return response()->json(['message' => 'No token, authorization denied'], 401);
        }

        try {
            JWTAuth::setToken($token);
            $payload = JWTAuth::getPayload();
            $user = JWTAuth::authenticate($token);

            if (!$user) {
                return response()->json(['message' => 'User not found'], 401);
            }

            // Check if token is stale (password changed after token issue)
            $iat = $payload->get('iat');
            if ($user->passwordChangedAt && $iat) {
                $passwordChangedTime = $user->passwordChangedAt->getTimestamp();
                if ($passwordChangedTime > $iat) {
                    return response()->json(['message' => 'Token is no longer valid. Please log in again.'], 401);
                }
            }

            // Check if user status is active
            if ($user->status !== 'active') {
                $status = $user->status;
                $reason = $status === 'rejected' ? $user->rejectionReason : $user->violationReason;

                if ($status === 'blocked') {
                    $payloadRes = [
                        'message' => 'Account Terminated',
                        'status' => $status,
                        'reason' => $reason ?: 'Account terminated by administrator.'
                    ];
                } elseif ($status === 'rejected') {
                    $payloadRes = [
                        'message' => 'Registration Rejected',
                        'status' => $status,
                        'reason' => $reason ?: 'Application does not meet community standards.'
                    ];
                } else {
                    $payloadRes = [
                        'message' => 'Violation Detected',
                        'status' => $status,
                        'reason' => $reason ?: 'Account suspended for policy violations.'
                    ];
                }

                return response()->json($payloadRes, 401);
            }

            // Authenticate the user in Laravel request context
            \Illuminate\Support\Facades\Auth::setUser($user);

            // Role authorizations
            if ($role && $role !== 'maybe') {
                if ($user->role !== $role) {
                    return response()->json(['message' => 'Access denied: Unauthorized role'], 403);
                }

                if ($role === 'seller' && !$user->isVerified) {
                    return response()->json([
                        'message' => 'Account pending approval',
                        'status' => 'pending_verification'
                    ], 403);
                }
            }

        } catch (TokenExpiredException $e) {
            if ($role === 'maybe') {
                return $next($request);
            }
            return response()->json(['message' => 'Session expired. Please log in again.'], 401);
        } catch (TokenInvalidException|\Exception $e) {
            if ($role === 'maybe') {
                return $next($request);
            }
            return response()->json(['message' => 'Token is not valid'], 401);
        }

        return $next($request);
    }
}
