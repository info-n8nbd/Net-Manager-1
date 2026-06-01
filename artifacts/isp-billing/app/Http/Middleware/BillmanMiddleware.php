<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class BillmanMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->check()) {
            return redirect()->route('login')->with('error', 'Access denied. Billman access required.');
        }

        $user = auth()->user();
        if ($user->user_type !== 'billman' && !$user->hasRole('billman')) {
            if ($request->ajax()) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
            return redirect()->route('login')->with('error', 'Access denied. Billman access required.');
        }

        return $next($request);
    }
}
