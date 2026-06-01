<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\ApiToken;
use App\Models\User;
use Symfony\Component\HttpFoundation\Response;

class ApiTokenMiddleware
{
    public function handle(Request $request, Closure $next, string $ability = null): Response
    {
        $rawToken = $request->bearerToken();

        if (!$rawToken) {
            return response()->json(['success' => false, 'error' => 'API token missing. Use: Authorization: Bearer {token}'], 401);
        }

        $hashedToken = hash('sha256', $rawToken);

        $apiToken = ApiToken::where('token', $hashedToken)
            ->where('is_active', true)
            ->first();

        if (!$apiToken) {
            return response()->json(['success' => false, 'error' => 'Invalid API token'], 401);
        }

        if ($apiToken->isExpired()) {
            return response()->json(['success' => false, 'error' => 'API token has expired'], 401);
        }

        // Check ability if specified
        if ($ability && !$apiToken->hasAbility($ability)) {
            return response()->json(['success' => false, 'error' => "Token lacks '{$ability}' permission"], 403);
        }

        $user = User::find($apiToken->user_id);
        if (!$user || !$user->is_active) {
            return response()->json(['success' => false, 'error' => 'User not found or inactive'], 401);
        }

        $apiToken->update(['last_used_at' => now()]);
        auth()->login($user);

        // Attach token to request for controllers
        $request->attributes->set('api_token', $apiToken);

        return $next($request);
    }
}
