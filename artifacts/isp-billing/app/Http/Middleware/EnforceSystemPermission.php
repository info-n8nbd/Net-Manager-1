<?php

namespace App\Http\Middleware;

use App\Services\PermissionRegistryService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceSystemPermission
{
    protected PermissionRegistryService $permissionService;

    public function __construct(PermissionRegistryService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    public function handle(Request $request, Closure $next, string $permissionKey): Response
    {
        $user = $request->user();

        if (!$user) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
            }
            return redirect()->route('login');
        }

        if ($user->hasRole('Super Admin')) {
            return $next($request);
        }

        if (!$this->permissionService->userHasPermission($user->id, $permissionKey)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to access this resource',
                    'required_permission' => $permissionKey,
                ], 403);
            }
            abort(403, 'You do not have permission to access this resource');
        }

        return $next($request);
    }
}
