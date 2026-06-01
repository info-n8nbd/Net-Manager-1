<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Cache;

class FixPermissions extends Command
{
    protected $signature = 'permissions:fix {--email= : Email of user to make Super Admin}';

    protected $description = 'Fix permission system by ensuring roles exist and assigning Super Admin to specified user';

    public function handle(): int
    {
        $this->info('=== Permission System Fix ===');
        $this->newLine();

        $this->info('Step 1: Clearing all caches...');
        Cache::forget('spatie.permission.cache');
        $this->callSilent('cache:clear');
        $this->callSilent('config:clear');
        $this->callSilent('view:clear');
        $this->info('All caches cleared.');
        $this->newLine();

        $this->info('Step 2: Ensuring required roles exist...');
        $roles = ['Super Admin', 'Administrator', 'Field Technician', 'Billing Officer', 'Reseller'];
        foreach ($roles as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        }
        $this->info('Roles verified: ' . implode(', ', $roles));
        $this->newLine();

        $email = $this->option('email');
        
        if ($email) {
            $this->info('Step 3: Assigning Super Admin role to ' . $email);
            $user = User::where('email', $email)->first();
            
            if (!$user) {
                $this->error('User not found with email: ' . $email);
                return Command::FAILURE;
            }

            if (!$user->hasRole('Super Admin')) {
                $user->assignRole('Super Admin');
                $this->info('Super Admin role assigned to ' . $user->name);
            } else {
                $this->info('User already has Super Admin role');
            }

            if ($user->user_type !== 'admin') {
                $user->update(['user_type' => 'admin']);
                $this->info('User type updated to admin');
            }
        } else {
            $this->info('Step 3: Checking existing Super Admin users...');
            $superAdmins = User::role('Super Admin')->get();
            
            if ($superAdmins->isEmpty()) {
                $this->warn('No Super Admin users found!');
                $this->info('Use --email=your@email.com to assign Super Admin role to a user');
            } else {
                $this->info('Super Admin users:');
                foreach ($superAdmins as $user) {
                    $this->line("  - {$user->name} ({$user->email})");
                }
            }
        }

        $this->newLine();
        $this->info('Step 4: Final verification...');
        $this->table(
            ['Role', 'Users Count'],
            Role::all()->map(fn($r) => [$r->name, User::role($r->name)->count()])->toArray()
        );

        $this->newLine();
        $this->info('Permission fix complete!');
        $this->warn('Please log out and log back in to see changes.');

        return Command::SUCCESS;
    }
}
