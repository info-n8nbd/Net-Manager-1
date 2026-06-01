<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\MenuPermissionService;

class CheckMenuAccess
{
    public function handle(Request $request, Closure $next, string $menuKey, ?string $submenuKey = null)
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        if ($user->hasRole('Super Admin')) {
            return $next($request);
        }

        if (!MenuPermissionService::canAccessMenu($user, $menuKey, $submenuKey)) {
            abort(403, 'You do not have permission to access this menu.');
        }

        return $next($request);
    }
}
