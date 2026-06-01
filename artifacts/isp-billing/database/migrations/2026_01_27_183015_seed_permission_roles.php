<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $roles = [
            'Super Admin',
            'admin',
            'technician',
            'billman',
            'reseller',
            'Supervisor',
            'Collection Agent',
            'Network Operator',
            'Support Staff',
        ];

        foreach ($roles as $roleName) {
            Role::firstOrCreate(
                ['name' => $roleName, 'guard_name' => 'web']
            );
        }
    }

    public function down(): void
    {
        $roles = [
            'Super Admin',
            'admin',
            'technician',
            'billman',
            'reseller',
            'Supervisor',
            'Collection Agent',
            'Network Operator',
            'Support Staff',
        ];

        foreach ($roles as $roleName) {
            Role::where('name', $roleName)->where('guard_name', 'web')->delete();
        }
    }
};
