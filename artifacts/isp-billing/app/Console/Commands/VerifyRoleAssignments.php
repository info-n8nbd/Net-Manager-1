<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Spatie\Permission\Models\Role;

class VerifyRoleAssignments extends Command
{
    protected $signature = 'roles:verify {--fix : Automatically fix missing role assignments}';

    protected $description = 'Verify and optionally fix user role assignments for proper access control';

    public function handle(): int
    {
        $this->info('=== Role Assignment Verification ===');
        $this->newLine();

        $roles = Role::all();
        $this->info('Available Roles: ' . $roles->pluck('name')->implode(', '));
        $this->newLine();

        $superAdminRole = Role::where('name', 'Super Admin')->first();
        if (!$superAdminRole) {
            $this->error('Super Admin role does not exist!');
            if ($this->option('fix')) {
                Role::create(['name' => 'Super Admin', 'guard_name' => 'web']);
                $this->info('Created Super Admin role');
            }
        }

        $adminRole = Role::where('name', 'Administrator')->first();
        if (!$adminRole) {
            $this->error('Administrator role does not exist!');
            if ($this->option('fix')) {
                Role::create(['name' => 'Administrator', 'guard_name' => 'web']);
                $this->info('Created Administrator role');
            }
        }

        $superAdmins = User::role('Super Admin')->get();
        $this->info('Super Admin users: ' . $superAdmins->count());
        foreach ($superAdmins as $user) {
            $this->line("  - {$user->name} ({$user->email})");
        }
        $this->newLine();

        if ($superAdmins->count() === 0) {
            $this->warn('No Super Admin users found!');
            
            if ($this->option('fix')) {
                $firstAdmin = User::where('user_type', 'admin')->first();
                if ($firstAdmin && !$firstAdmin->hasRole('Super Admin')) {
                    $firstAdmin->assignRole('Super Admin');
                    $this->info('Assigned Super Admin role to existing admin user: ' . $firstAdmin->email);
                } else {
                    $this->error('No admin user found to assign Super Admin role.');
                    $this->info('Use php artisan permissions:fix --email=your@email.com to assign Super Admin to a specific user.');
                }
            }
        }

        $admins = User::role('Administrator')->get();
        $this->info('Administrator users: ' . $admins->count());
        foreach ($admins as $user) {
            $this->line("  - {$user->name} ({$user->email})");
        }
        $this->newLine();

        $usersWithoutRoles = User::where('user_type', 'admin')
            ->whereDoesntHave('roles')
            ->get();

        if ($usersWithoutRoles->count() > 0) {
            $this->warn('Admin users without any role assigned: ' . $usersWithoutRoles->count());
            foreach ($usersWithoutRoles as $user) {
                $this->line("  - {$user->name} ({$user->email})");
                
                if ($this->option('fix')) {
                    $user->assignRole('Administrator');
                    $this->info("    Assigned Administrator role to {$user->email}");
                }
            }
        } else {
            $this->info('All admin users have roles assigned.');
        }

        $this->newLine();
        $this->info('=== Summary ===');
        $this->table(
            ['Role', 'Users Count'],
            Role::all()->map(fn($r) => [$r->name, User::role($r->name)->count()])->toArray()
        );

        if (!$this->option('fix')) {
            $this->newLine();
            $this->comment('Run with --fix to automatically fix any issues');
        }

        return Command::SUCCESS;
    }
}
