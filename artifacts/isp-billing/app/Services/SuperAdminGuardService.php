<?php

namespace App\Services;

use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Log;

class SuperAdminGuardService
{
    public static function isSuperAdmin(?User $user): bool
    {
        if (!$user) {
            return false;
        }
        if ($user->user_type === 'super_admin') {
            return true;
        }
        return $user->hasRole('Super Admin');
    }

    public static function canManageSuperAdmin(?User $actor): bool
    {
        return self::isSuperAdmin($actor);
    }

    public static function canCreateSuperAdmin(?User $actor): bool
    {
        return self::isSuperAdmin($actor);
    }

    public static function canDowngradeSuperAdmin(?User $actor, User $target): bool
    {
        if (!self::isSuperAdmin($target)) {
            return true;
        }
        
        if (!self::isSuperAdmin($actor)) {
            return false;
        }
        
        $superAdminCount = User::role('Super Admin')->count();
        if ($superAdminCount <= 1 && $target->hasRole('Super Admin')) {
            return false;
        }
        
        return true;
    }

    public static function canDeleteUser(?User $actor, User $target): bool
    {
        if (self::isSuperAdmin($target)) {
            if (!self::isSuperAdmin($actor)) {
                return false;
            }
            
            $superAdminCount = User::role('Super Admin')->count();
            if ($superAdminCount <= 1) {
                return false;
            }
        }
        
        return true;
    }

    public static function canEditUser(?User $actor, User $target): bool
    {
        if (self::isSuperAdmin($target) && !self::isSuperAdmin($actor)) {
            return false;
        }
        
        return true;
    }

    public static function canAssignRole(?User $actor, string $roleName): bool
    {
        if ($roleName === 'Super Admin') {
            return self::isSuperAdmin($actor);
        }
        
        return true;
    }

    public static function canRevokeRole(?User $actor, User $target, string $roleName): bool
    {
        if ($roleName === 'Super Admin') {
            if (!self::isSuperAdmin($actor)) {
                return false;
            }
            
            $superAdminCount = User::role('Super Admin')->count();
            if ($superAdminCount <= 1 && $target->hasRole('Super Admin')) {
                return false;
            }
        }
        
        return true;
    }

    public static function getSuperAdminCount(): int
    {
        return User::role('Super Admin')->count();
    }

    public static function getFirstSuperAdmin(): ?User
    {
        return User::role('Super Admin')->oldest()->first();
    }

    public static function ensureAtLeastOneSuperAdmin(): void
    {
        if (self::getSuperAdminCount() === 0) {
            $firstAdmin = User::whereHas('roles', function ($q) {
                $q->where('name', 'admin');
            })->oldest()->first();
            
            if ($firstAdmin) {
                $firstAdmin->assignRole('Super Admin');
                Log::warning('SuperAdminGuard: Auto-assigned Super Admin to first admin user', [
                    'user_id' => $firstAdmin->id,
                    'email' => $firstAdmin->email,
                ]);
            }
        }
    }

    public static function logCriticalAction(User $actor, string $action, array $data = []): void
    {
        Log::channel('security')->info("SUPER_ADMIN_ACTION: {$action}", [
            'actor_id' => $actor->id,
            'actor_email' => $actor->email,
            'action' => $action,
            'data' => $data,
            'ip' => request()->ip(),
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
