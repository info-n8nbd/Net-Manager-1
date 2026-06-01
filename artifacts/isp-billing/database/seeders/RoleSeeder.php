<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            'Super Admin',
            'Administrator',
            'Admin Support',
            'Branch Manager',
            'Assistant Manager',
            'Supervisor',
            'Field Technician',
            'Billing Officer',
            'Collection Officer',
            'Network Engineer',
            'Support Executive',
            'Card Agent',
            'Reseller',
            'Sub Reseller',
            'client',
        ];

        foreach ($roles as $roleName) {
            Role::firstOrCreate(
                ['name' => $roleName, 'guard_name' => 'web']
            );
        }

        $this->command->info('Roles created: ' . implode(', ', $roles));
    }
}
