<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DepartmentDesignationSeeder extends Seeder
{
    public function run(): void
    {
        if (!Schema::hasTable('departments') || !Schema::hasTable('designations')) {
            return;
        }

        $departmentsWithDesignations = [
            'Network Operations Department (NOC)' => [
                'NOC Engineer',
                'Senior Network Engineer',
                'Network Engineer',
                'NOC Technician',
            ],
            'Technical Support Department' => [
                'Senior Technical Support Engineer',
                'Technical Support Engineer',
                'Support Technician',
            ],
            'Customer Support / Customer Service' => [
                'Customer Service Executive',
                'Call Center Executive',
            ],
            'Sales & Marketing Department' => [
                'Sales Manager',
                'Sales Executive',
                'Marketing Executive',
            ],
            'Billing & Accounts Department' => [
                'Accounts Manager',
                'Accounts Executive',
                'Collection Executive',
            ],
            'System / IT Department' => [
                'System Administrator',
                'DevOps Engineer',
                'Software Developer',
            ],
            'Management / Administration' => [
                'Chief Executive Officer (CEO)',
                'Chief Technology Officer (CTO)',
                'Operations Manager',
                'HR Manager',
                'Administrative Officer',
            ],
        ];

        $now = now();
        $hasDeptId = Schema::hasColumn('designations', 'department_id');

        foreach ($departmentsWithDesignations as $deptName => $designations) {
            $dept = DB::table('departments')->where('name', $deptName)->first();
            if (!$dept) {
                $deptId = DB::table('departments')->insertGetId([
                    'name'       => $deptName,
                    'is_active'  => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            } else {
                $deptId = $dept->id;
            }

            foreach ($designations as $desigName) {
                $query = DB::table('designations')->where('name', $desigName);
                if ($hasDeptId) {
                    $query->where('department_id', $deptId);
                }
                if (!$query->exists()) {
                    $row = [
                        'name'       => $desigName,
                        'is_active'  => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                    if ($hasDeptId) {
                        $row['department_id'] = $deptId;
                    }
                    DB::table('designations')->insert($row);
                }
            }
        }
    }
}
