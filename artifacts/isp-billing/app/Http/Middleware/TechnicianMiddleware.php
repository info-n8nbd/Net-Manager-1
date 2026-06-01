<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class TechnicianMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->check()) {
            if ($request->ajax()) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
            return redirect()->route('login')->with('error', 'Access denied. Technician access required.');
        }

        $user = auth()->user();
        $techRoles = ['technician', 'Field Technician', 'Network Engineer'];
        $isTechnician = $user->user_type === 'technician'
            || collect($techRoles)->contains(fn($r) => $user->hasRole($r));
        if (!$isTechnician) {
            if ($request->ajax()) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
            return redirect()->route('login')->with('error', 'Access denied. Technician access required.');
        }

        return $next($request);
    }
}
