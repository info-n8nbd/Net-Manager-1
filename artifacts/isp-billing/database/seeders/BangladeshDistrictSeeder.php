<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BangladeshDistrictSeeder extends Seeder
{
    public function run(): void
    {
        if (!Schema::hasTable('districts')) {
            return;
        }

        $districtsByDivision = [
            'Barishal'    => ['Barguna', 'Barishal', 'Bhola', 'Jhalokati', 'Patuakhali', 'Pirojpur'],
            'Chattogram'  => ['Bandarban', 'Brahmanbaria', 'Chandpur', 'Chattogram', 'Comilla', "Cox's Bazar", 'Feni', 'Khagrachhari', 'Lakshmipur', 'Noakhali', 'Rangamati'],
            'Dhaka'       => ['Dhaka', 'Faridpur', 'Gazipur', 'Gopalganj', 'Kishoreganj', 'Madaripur', 'Manikganj', 'Munshiganj', 'Narayanganj', 'Narsingdi', 'Rajbari', 'Shariatpur', 'Tangail'],
            'Khulna'      => ['Bagerhat', 'Chuadanga', 'Jessore', 'Jhenaidah', 'Khulna', 'Kushtia', 'Magura', 'Meherpur', 'Narail', 'Satkhira'],
            'Mymensingh'  => ['Jamalpur', 'Mymensingh', 'Netrokona', 'Sherpur'],
            'Rajshahi'    => ['Bogura', 'Chapainawabganj', 'Joypurhat', 'Naogaon', 'Natore', 'Nawabganj', 'Pabna', 'Rajshahi', 'Sirajganj'],
            'Rangpur'     => ['Dinajpur', 'Gaibandha', 'Kurigram', 'Lalmonirhat', 'Nilphamari', 'Panchagarh', 'Rangpur', 'Thakurgaon'],
            'Sylhet'      => ['Habiganj', 'Moulvibazar', 'Sunamganj', 'Sylhet'],
        ];

        $now = now();
        $hasIsConfigured = Schema::hasColumn('districts', 'is_configured');

        foreach ($districtsByDivision as $division => $districts) {
            foreach ($districts as $districtName) {
                if (!DB::table('districts')->where('name', $districtName)->exists()) {
                    $row = [
                        'name'       => $districtName,
                        'division'   => $division,
                        'is_active'  => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                    if ($hasIsConfigured) {
                        $row['is_configured'] = true;
                    }
                    DB::table('districts')->insert($row);
                }
            }
        }

        // Ensure all existing active districts are marked as configured
        if ($hasIsConfigured) {
            DB::table('districts')->where('is_active', true)->where('is_configured', false)->update(['is_configured' => true]);
        }
    }
}
