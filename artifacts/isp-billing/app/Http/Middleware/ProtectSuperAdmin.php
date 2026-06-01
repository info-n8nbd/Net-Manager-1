<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\SuperAdminGuardService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ProtectSuperAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $actor = $request->user();
        
        $targetUserId = $request->route('user') 
            ?? $request->route('userId') 
            ?? $request->input('user_id');
        
        if ($targetUserId) {
            $target = User::find($targetUserId);
            
            if ($target && SuperAdminGuardService::isSuperAdmin($target)) {
                if (!SuperAdminGuardService::canEditUser($actor, $target)) {
                    if ($request->expectsJson()) {
                        return response()->json([
                            'success' => false,
                            'message' => 'You cannot modify a Super Admin account',
                        ], 403);
                    }
                    abort(403, 'You cannot modify a Super Admin account');
                }
            }
        }
        
        $roleToAssign = $request->input('role') ?? $request->input('role_name');
        if ($roleToAssign === 'Super Admin') {
            if (!SuperAdminGuardService::canAssignRole($actor, 'Super Admin')) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Only Super Admin can assign the Super Admin role',
                    ], 403);
                }
                abort(403, 'Only Super Admin can assign the Super Admin role');
            }
        }
        
        return $next($request);
    }
}
