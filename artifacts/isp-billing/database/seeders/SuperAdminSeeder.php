<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $superAdminRole = Role::firstOrCreate(
            ['name' => 'Super Admin', 'guard_name' => 'web']
        );

        $existingSuperAdmin = User::role('Super Admin')->first();
        
        if ($existingSuperAdmin) {
            $this->command->info("Super Admin already exists: {$existingSuperAdmin->email}");
            return;
        }

        $superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@isp.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('SuperAdmin@123'),
                'email_verified_at' => now(),
                'user_type' => 'super_admin',
            ]
        );

        if ($superAdmin->user_type !== 'super_admin') {
            $superAdmin->update(['user_type' => 'super_admin']);
        }

        if (!$superAdmin->hasRole('Super Admin')) {
            $superAdmin->assignRole('Super Admin');
        }

        $this->command->info("Super Admin created successfully!");
        $this->command->info("Email: superadmin@isp.com");
        $this->command->info("Password: SuperAdmin@123");
        $this->command->warn("IMPORTANT: Change this password immediately after first login!");
    }
}
