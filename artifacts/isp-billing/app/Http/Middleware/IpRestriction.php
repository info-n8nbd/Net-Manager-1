<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\IpRestriction as IpRestrictionModel;
use Symfony\Component\HttpFoundation\Response;

class IpRestriction
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();
        if (!$user) {
            return $next($request);
        }

        $clientIp = $request->ip();
        $userRole = $user->role ?? $user->user_type ?? null;

        $blacklisted = IpRestrictionModel::where('type', 'blacklist')
            ->where('is_active', true)
            ->where(function ($query) use ($user, $userRole, $clientIp) {
                $query->where('ip_address', $clientIp)
                    ->where(function ($q) use ($user, $userRole) {
                        $q->whereNull('user_id')
                            ->orWhere('user_id', $user->id);
                    })
                    ->where(function ($q) use ($userRole) {
                        $q->whereNull('role')
                            ->orWhere('role', $userRole);
                    });
            })
            ->exists();

        if ($blacklisted) {
            Log::warning('IP blacklisted access attempt', [
                'user_id' => $user->id,
                'ip' => $clientIp,
                'role' => $userRole,
            ]);

            auth()->logout();
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied from this IP address.',
                ], 403);
            }

            return redirect()->route('login')->with('error', 'Access denied from this IP address.');
        }

        $hasWhitelist = IpRestrictionModel::where('type', 'whitelist')
            ->where('is_active', true)
            ->where(function ($query) use ($user, $userRole) {
                $query->where('user_id', $user->id)
                    ->orWhere('role', $userRole);
            })
            ->exists();

        if ($hasWhitelist) {
            $isWhitelisted = IpRestrictionModel::where('type', 'whitelist')
                ->where('is_active', true)
                ->where('ip_address', $clientIp)
                ->where(function ($query) use ($user, $userRole) {
                    $query->where('user_id', $user->id)
                        ->orWhere('role', $userRole);
                })
                ->exists();

            if (!$isWhitelisted) {
                Log::warning('IP not in whitelist', [
                    'user_id' => $user->id,
                    'ip' => $clientIp,
                    'role' => $userRole,
                ]);

                auth()->logout();
                
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Access denied. IP not in allowed list.',
                    ], 403);
                }

                return redirect()->route('login')->with('error', 'Access denied. IP not in allowed list.');
            }
        }

        return $next($request);
    }
}
