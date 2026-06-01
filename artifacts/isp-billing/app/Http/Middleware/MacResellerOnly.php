<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Reseller;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class MacResellerOnly
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        $reseller = Reseller::where('user_id', $user->id)->first();

        if ($reseller && $reseller->type === 'bandwidth') {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'This feature is only available for MAC binding resellers.'], 403);
            }
            return redirect()->route('reseller.bandwidth-dashboard')
                ->with('error', 'This section is only available for MAC binding resellers.');
        }

        return $next($request);
    }
}
