<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'dashboard.view',
            'clients.view',
            'clients.create',
            'clients.edit',
            'clients.delete',
            'clients.status',
            'clients.payment',
            'clients.documents',
            'billing.view',
            'billing.generate',
            'billing.edit',
            'billing.delete',
            'billing.payment',
            'billing.invoice',
            'payments.view',
            'payments.create',
            'payments.approve',
            'payments.reject',
            'payments.export',
            'packages.view',
            'packages.create',
            'packages.edit',
            'packages.delete',
            'zones.view',
            'zones.create',
            'zones.edit',
            'zones.delete',
            'areas.view',
            'areas.create',
            'areas.edit',
            'areas.delete',
            'boxes.view',
            'boxes.create',
            'boxes.edit',
            'boxes.delete',
            'devices.view',
            'devices.create',
            'devices.edit',
            'devices.delete',
            'devices.sync',
            'olt.view',
            'olt.create',
            'olt.edit',
            'olt.delete',
            'olt.sync',
            'users.view',
            'users.create',
            'users.edit',
            'users.delete',
            'roles.view',
            'roles.create',
            'roles.edit',
            'roles.delete',
            'permissions.view',
            'permissions.assign',
            'resellers.view',
            'resellers.create',
            'resellers.edit',
            'resellers.delete',
            'technicians.view',
            'technicians.create',
            'technicians.edit',
            'technicians.delete',
            'technicians.assign',
            'applications.view',
            'applications.approve',
            'applications.reject',
            'tickets.view',
            'tickets.create',
            'tickets.assign',
            'tickets.resolve',
            'tickets.close',
            'reports.view',
            'reports.collection',
            'reports.revenue',
            'reports.clients',
            'reports.billing',
            'reports.export',
            'accounts.view',
            'accounts.income',
            'accounts.expense',
            'accounts.transfer',
            'accounts.loan',
            'accounts.approve',
            'sms.view',
            'sms.send',
            'sms.templates',
            'sms.settings',
            'settings.view',
            'settings.edit',
            'settings.payment',
            'settings.company',
            'access_control.view',
            'access_control.edit',
            'technician.dashboard',
            'technician.clients',
            'technician.client.create',
            'technician.client.edit',
            'technician.collection',
            'technician.tickets',
            'technician.new_connection',
            'technician.due_report',
            'technician.device_control',
            'reseller.dashboard',
            'reseller.clients',
            'reseller.client_edit',
            'reseller.client_restore',
            'reseller.collection',
            'reseller.due_report',
            'reseller.packages',
            'reseller.tickets',
            'reseller.wallet',
            'reseller.device_control',
            'events.view',
            'events.create',
            'events.edit',
            'events.delete',
            'tasks.view',
            'tasks.create',
            'tasks.edit',
            'tasks.delete',
            'tasks.assign',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $superAdmin = Role::where('name', 'Super Admin')->first();
        if ($superAdmin) {
            $superAdmin->syncPermissions(Permission::all());
        }

        $admin = Role::where('name', 'admin')->first();
        if ($admin) {
            $adminPermissions = Permission::whereNotIn('name', [
                'roles.create', 'roles.edit', 'roles.delete',
                'permissions.assign', 'access_control.edit'
            ])->get();
            $admin->syncPermissions($adminPermissions);
        }

        $billman = Role::where('name', 'billman')->first();
        if ($billman) {
            $billman->syncPermissions([
                'dashboard.view',
                'clients.view',
                'clients.payment',
                'billing.view',
                'billing.generate',
                'billing.payment',
                'billing.invoice',
                'payments.view',
                'payments.create',
                'reports.view',
                'reports.collection',
                'reports.billing',
            ]);
        }

        $technician = Role::where('name', 'technician')->first();
        if ($technician) {
            $technician->syncPermissions([
                'technician.dashboard',
                'technician.clients',
                'technician.client.create',
                'technician.client.edit',
                'technician.collection',
                'technician.tickets',
                'technician.new_connection',
                'technician.due_report',
                'technician.device_control',
                'clients.view',
                'clients.edit',
                'tickets.view',
                'tickets.create',
                'tickets.resolve',
            ]);
        }

        $reseller = Role::where('name', 'reseller')->first();
        if ($reseller) {
            $reseller->syncPermissions([
                'dashboard.view',
                'clients.view',
                'clients.create',
                'clients.edit',
                'clients.payment',
                'billing.view',
                'billing.payment',
                'payments.view',
                'payments.create',
                'reports.view',
                'reports.collection',
                'reseller.dashboard',
                'reseller.clients',
                'reseller.client_edit',
                'reseller.collection',
                'reseller.due_report',
                'reseller.packages',
                'reseller.tickets',
                'reseller.wallet',
                'reseller.device_control',
            ]);
        }

        $supervisor = Role::where('name', 'Supervisor')->first();
        if ($supervisor) {
            $supervisor->syncPermissions([
                'dashboard.view',
                'clients.view',
                'clients.edit',
                'clients.status',
                'billing.view',
                'billing.payment',
                'payments.view',
                'payments.approve',
                'technicians.view',
                'technicians.assign',
                'tickets.view',
                'tickets.assign',
                'tickets.resolve',
                'reports.view',
                'reports.collection',
                'reports.clients',
                'events.view',
                'events.create',
                'tasks.view',
                'tasks.create',
                'tasks.assign',
            ]);
        }

        $collectionAgent = Role::where('name', 'Collection Officer')->first();
        if ($collectionAgent) {
            $collectionAgent->syncPermissions([
                'dashboard.view',
                'clients.view',
                'clients.payment',
                'billing.view',
                'billing.payment',
                'payments.view',
                'payments.create',
                'reports.collection',
            ]);
        }

        $networkOperator = Role::where('name', 'Network Engineer')->first();
        if ($networkOperator) {
            $networkOperator->syncPermissions([
                'dashboard.view',
                'clients.view',
                'clients.status',
                'devices.view',
                'devices.sync',
                'olt.view',
                'olt.sync',
                'zones.view',
                'areas.view',
                'boxes.view',
            ]);
        }

        $supportStaff = Role::where('name', 'Support Executive')->first();
        if ($supportStaff) {
            $supportStaff->syncPermissions([
                'dashboard.view',
                'clients.view',
                'tickets.view',
                'tickets.create',
                'tickets.assign',
                'tickets.resolve',
                'tickets.close',
            ]);
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
