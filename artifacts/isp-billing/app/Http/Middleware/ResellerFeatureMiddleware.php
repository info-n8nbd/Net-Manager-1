<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Reseller;
use Symfony\Component\HttpFoundation\Response;

class ResellerFeatureMiddleware
{
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        $reseller = Reseller::withoutGlobalScope('tenant')->where('user_id', $user->id)->first();
        if (!$reseller) {
            abort(403, 'Reseller account not found.');
        }

        if (!$reseller->hasFeature($feature)) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'This feature is not enabled for your account.'], 403);
            }
            return redirect()->route('reseller.dashboard')
                ->with('error', 'This feature is not enabled for your account. Please contact your administrator.');
        }

        return $next($request);
    }
}
