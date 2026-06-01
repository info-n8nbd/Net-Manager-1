<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\TwoFactorService;

class TwoFactorMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        $twoFactorService = app(TwoFactorService::class);

        if ($twoFactorService->hasTwoFactor($user) && !session('2fa_verified')) {
            if ($request->is('admin/2fa/verify') || $request->is('admin/2fa/verify-code') || $request->is('logout')) {
                return $next($request);
            }

            return redirect()->route('admin.2fa.verify');
        }

        return $next($request);
    }
}
