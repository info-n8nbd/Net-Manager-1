<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();

        if ($user->user_type === 'super_admin' || $user->hasRole('Super Admin')) {
            return $next($request);
        }

        try {
            if ($user->hasPermissionTo($permission)) {
                return $next($request);
            }
        } catch (\Spatie\Permission\Exceptions\PermissionDoesNotExist $e) {
            // Permission not defined in DB — deny access
        }

        if ($request->ajax()) {
            return response()->json(['error' => 'You do not have permission to access this feature.'], 403);
        }

        abort(403, 'You do not have permission to access this feature.');
    }
}
