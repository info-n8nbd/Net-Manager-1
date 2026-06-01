<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Hash;

class ProductionSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

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
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        }

        $permissions = [
            'dashboard.view', 'clients.view', 'clients.create', 'clients.edit', 'clients.delete',
            'clients.status', 'clients.payment', 'clients.documents', 'billing.view', 'billing.generate',
            'billing.edit', 'billing.delete', 'billing.payment', 'billing.invoice', 'payments.view',
            'payments.create', 'payments.approve', 'payments.reject', 'payments.export', 'packages.view',
            'packages.create', 'packages.edit', 'packages.delete', 'zones.view', 'zones.create',
            'zones.edit', 'zones.delete', 'areas.view', 'areas.create', 'areas.edit', 'areas.delete',
            'boxes.view', 'boxes.create', 'boxes.edit', 'boxes.delete', 'devices.view', 'devices.create',
            'devices.edit', 'devices.delete', 'devices.sync', 'olt.view', 'olt.create', 'olt.edit',
            'olt.delete', 'olt.sync', 'users.view', 'users.create', 'users.edit', 'users.delete',
            'roles.view', 'roles.create', 'roles.edit', 'roles.delete', 'permissions.view',
            'permissions.assign', 'resellers.view', 'resellers.create', 'resellers.edit', 'resellers.delete',
            'technicians.view', 'technicians.create', 'technicians.edit', 'technicians.delete',
            'technicians.assign', 'applications.view', 'applications.approve', 'applications.reject',
            'tickets.view', 'tickets.create', 'tickets.assign', 'tickets.resolve', 'tickets.close',
            'reports.view', 'reports.collection', 'reports.revenue', 'reports.clients', 'reports.billing',
            'reports.export', 'accounts.view', 'accounts.income', 'accounts.expense', 'accounts.transfer',
            'accounts.loan', 'accounts.approve', 'sms.view', 'sms.send', 'sms.templates', 'sms.settings',
            'settings.view', 'settings.edit', 'settings.payment', 'settings.company', 'access_control.view',
            'access_control.edit', 'technician.dashboard', 'technician.clients', 'technician.collection',
            'technician.tickets', 'technician.new_connection', 'technician.due_report',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $superAdminRole = Role::where('name', 'Super Admin')->first();
        if ($superAdminRole) {
            $superAdminRole->syncPermissions(Permission::all());
        }

        $existingSuperAdmin = User::where('email', 'superadmin@isp.com')->first();
        
        if (!$existingSuperAdmin) {
            $superAdmin = User::create([
                'name' => 'Super Admin',
                'email' => 'superadmin@isp.com',
                'password' => Hash::make('SuperAdmin@123'),
                'user_type' => 'admin',
                'is_active' => true,
                'email_verified_at' => now(),
            ]);
            $superAdmin->assignRole('Super Admin');
            $this->command->info('Super Admin created: superadmin@isp.com / SuperAdmin@123');
        } else {
            $existingSuperAdmin->assignRole('Super Admin');
            $this->command->info('Super Admin already exists, role assigned.');
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        
        $this->command->info('Production setup completed!');
    }
}
