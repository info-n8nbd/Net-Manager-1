<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use App\Models\Reseller;
use Symfony\Component\HttpFoundation\Response;

class ResellerMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        $isReseller = $user->user_type === 'reseller' || $user->hasRole('reseller');
        $isImpersonating = Session::has('impersonate_original_user_id');

        if (!$isReseller && !$isImpersonating) {
            Log::warning('ResellerMiddleware: Blocked non-reseller', [
                'user_id'   => $user->id,
                'user_type' => $user->user_type,
                'url'       => $request->fullUrl(),
            ]);
            abort(403, 'Access denied. Reseller account required.');
        }

        $reseller = Reseller::withoutGlobalScope('tenant')
            ->where('user_id', $user->id)
            ->first();

        if (!$reseller) {
            if ($isImpersonating) {
                $originalUserId = Session::get('impersonate_original_user_id');
                Session::forget(['impersonate_original_user_id', 'impersonate_original_user_name', 'impersonate_reseller_id']);
                $originalUser = \App\Models\User::find($originalUserId);
                if ($originalUser) {
                    Auth::login($originalUser);
                    return redirect()->route('admin.dashboard')->with('error', 'Reseller account not found. Returned to admin.');
                }
            }
            Auth::logout();
            return redirect()->route('login')->withErrors(['email' => 'Reseller account not configured.']);
        }

        if ($reseller->status !== 'active') {
            if ($isImpersonating) {
                $originalUserId = Session::get('impersonate_original_user_id');
                Session::forget(['impersonate_original_user_id', 'impersonate_original_user_name', 'impersonate_reseller_id']);
                $originalUser = \App\Models\User::find($originalUserId);
                if ($originalUser) {
                    Auth::login($originalUser);
                    return redirect()->route('admin.dashboard')->with('error', 'Reseller not active. Returned to admin.');
                }
            }
            Auth::logout();
            return redirect()->route('login')->withErrors(['email' => 'Reseller account is inactive.']);
        }

        $request->attributes->set('reseller', $reseller);

        return $next($request);
    }
}
