<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Reseller;
use Symfony\Component\HttpFoundation\Response;

class SubResellerMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        if ($user->user_type !== 'sub_reseller' && !$user->hasRole('sub-reseller')) {
            abort(403, 'Access denied. Sub-Reseller account required.');
        }

        $reseller = Reseller::where('user_id', $user->id)->first();

        if (!$reseller || $reseller->status !== 'active') {
            Auth::logout();
            return redirect()->route('login')->withErrors(['email' => 'Your sub-reseller account is inactive.']);
        }

        return $next($request);
    }
}
