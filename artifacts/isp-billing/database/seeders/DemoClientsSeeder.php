<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Client;
use App\Models\Reseller;
use App\Models\MikrotikDevice;
use App\Models\Zone;
use App\Models\Area;
use App\Models\Package;
use App\Services\TenantContext;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Carbon\Carbon;

class DemoClientsSeeder extends Seeder
{
    public function run(): void
    {
        TenantContext::setSuperAdminMode(true);

        // ─── MikroTik Real Device ────────────────────────────────────────────
        $mikrotik = MikrotikDevice::firstOrCreate(
            ['host' => '103.85.182.246'],
            [
                'name'             => 'MikroTik RB751G-2HnD',
                'port'             => 8090,
                'www_port'         => 6070,
                'username'         => 'smartbilling',
                'password'         => 'smart.billing@2026',
                'is_active'        => true,
                'router_type'      => 'noc',
                'overwrite_ppp_comment' => true,
                'status'           => 'online',
                'zone_id'          => Zone::withoutGlobalScopes()->where('company_id', 1)->value('id'),
                'company_id'       => 1,
            ]
        );

        $this->command->info('  MikroTik: 103.85.182.246:8090 (smartbilling)');

        // ─── Sajib Ahmed — Reseller ──────────────────────────────────────────
        $sajibUser = User::firstOrCreate(
            ['email' => 'sajib@reseller.com'],
            [
                'name'              => 'Sajib Ahmed',
                'password'          => Hash::make('Test@123'),
                'phone'             => '01978930588',
                'user_type'         => 'reseller',
                'is_active'         => true,
                'email_verified_at' => now(),
                'company_id'        => 1,
            ]
        );

        Role::firstOrCreate(['name' => 'Reseller', 'guard_name' => 'web']);
        if (!$sajibUser->hasRole('Reseller')) {
            $sajibUser->assignRole('Reseller');
        }

        $sajibReseller = Reseller::firstOrCreate(
            ['email' => 'sajib@reseller.com'],
            [
                'name'             => 'Sajib Ahmed',
                'phone'            => '01978930588',
                'user_id'          => $sajibUser->id,
                'company_id'       => 1,
                'commission_rate'  => 0.00,
                'status'           => 'active',
                'allowed_features' => ['clients','client_create','client_edit','collection','billing','due_report','reports','packages','packages_manage','wallet','device_control','zones','tickets','online_clients','network','sub_resellers','sms'],
            ]
        );

        $this->command->info('  Reseller: sajib@reseller.com / Test@123 (Sajib Ahmed)');

        // ─── Helpers ─────────────────────────────────────────────────────────
        $zoneA   = Zone::withoutGlobalScopes()->where('company_id', 1)->first() ?? Zone::withoutGlobalScopes()->first();
        $zoneB   = Zone::withoutGlobalScopes()->where('company_id', 1)->skip(1)->first() ?? $zoneA;
        $areaA   = $zoneA ? Area::withoutGlobalScopes()->where('zone_id', $zoneA->id)->first() : null;
        $areaB   = $zoneB ? (Area::withoutGlobalScopes()->where('zone_id', $zoneB->id)->first() ?? $areaA) : $areaA;
        $package = Package::withoutGlobalScopes()->where('company_id', 1)->first() ?? Package::withoutGlobalScopes()->first();

        // ─── Client 1 — Aashiq Yeasin ────────────────────────────────────────
        $client1 = Client::withoutGlobalScopes()->updateOrCreate(
            ['username' => 'adrianasalazar'],
            [
                'client_id'            => '00001',
                'name'                 => 'Aashiq Yeasin',
                'phone'                => '01978930588',
                'address'              => '28 Dhakashwari Road',
                'nid'                  => '123456',
                'zone_id'              => $zoneA->id,
                'area_id'              => $areaA?->id,
                'package_id'           => $package->id,
                'pppoe_password'       => '1234',
                'mac_address'          => 'F0:A7:31:AB:A2:F9',
                'connection_type'      => 'pppoe',
                'connection_date'      => Carbon::now()->subDays(1),
                'expire_date'          => Carbon::now()->addDays(7),
                'billing_date'         => 20,
                'payment_deadline'     => 20,
                'monthly_fee'          => 525.00,
                'status'               => 'active',
                'connection_status'    => 'online',
                'mikrotik_device_id'   => $mikrotik->id,
                'session_ip'           => '10.10.10.254',
                'session_mac'          => 'F0:A7:31:AB:A2:F9',
                'router_brand'         => 'TP-Link',
                'connection_medium'    => 'fiber',
                'fiber_code'           => 'H2345',
                'fiber_color'          => 'Blue',
                'splitter_number'      => '4',
                'splitter_color'       => 'Green',
                'onu_mac'              => '56FD',
                'fiber_vendor'         => 'RF Gold',
                'fiber_core_type'      => '2',
                'onu_brand'            => 'DBC',
                'mikrotik_profile_name'=> 'SMART-3',
                'onu_required'         => true,
                'fiber_required'       => true,
                'gender'               => 'male',
                'district_id'          => 18,
                'upazila_id'           => 1,
                'company_id'           => 1,
                'signup_fee'           => 1000.00,
                'installation_charge'  => 1000.00,
                'cable_length_meters'  => 40.00,
                'auto_disconnect_days' => 15,
            ]
        );

        $this->command->info('  Client: Aashiq Yeasin (adrianasalazar) — 10.10.10.254 — TP-Link');

        // ─── Client 2 — Hivar Jom (Reseller's Client) ───────────────────────
        $hivarUser = User::firstOrCreate(
            ['email' => 'hivarjom@gmail.com'],
            [
                'name'              => 'Hivar Jom',
                'password'          => Hash::make('Test@123'),
                'phone'             => '01536060706',
                'user_type'         => 'client',
                'is_active'         => true,
                'email_verified_at' => now(),
                'company_id'        => 1,
            ]
        );

        $client2 = Client::withoutGlobalScopes()->updateOrCreate(
            ['username' => 'Sajib_15'],
            [
                'client_id'            => 'sajib0003',
                'user_id'              => $hivarUser->id,
                'name'                 => 'Hivar Jom',
                'email'                => 'hivarjom@gmail.com',
                'phone'                => '01536060706',
                'alt_phone'            => '01978930588',
                'address'              => '135 boithakhali',
                'nid'                  => '55645666576',
                'zone_id'              => $zoneB->id,
                'area_id'              => $areaB?->id,
                'package_id'           => $package->id,
                'pppoe_password'       => '1234',
                'mac_address'          => '58:D5:6E:AB:D0:55',
                'connection_type'      => 'pppoe',
                'connection_date'      => Carbon::now()->subDays(1),
                'expire_date'          => Carbon::now()->subDays(1),
                'billing_date'         => 1,
                'payment_deadline'     => 10,
                'monthly_fee'          => 525.00,
                'due_amount'           => 200.00,
                'status'               => 'active',
                'connection_status'    => 'online',
                'reseller_id'          => $sajibReseller->id,
                'mikrotik_device_id'   => $mikrotik->id,
                'session_ip'           => '10.10.10.253',
                'session_mac'          => '58:D5:6E:AB:D0:55',
                'router_brand'         => 'D-Link',
                'connection_medium'    => 'fiber',
                'fiber_code'           => 'H2416',
                'fiber_color'          => 'Blue',
                'splitter_number'      => '1',
                'splitter_color'       => 'Blue',
                'onu_mac'              => 'G6H7',
                'fiber_vendor'         => 'RF GOLD',
                'fiber_core_type'      => '2',
                'onu_brand'            => 'HUAWEI',
                'mikrotik_profile_name'=> 'SMART-3',
                'father_husband_name'  => 'David Law',
                'gender'               => 'male',
                'occupation'           => 'Job',
                'district_id'          => 18,
                'upazila_id'           => 1,
                'company_id'           => 1,
                'fiber_required'       => true,
                'auto_disconnect_days' => 15,
            ]
        );

        $this->command->info('  Client: Hivar Jom (Sajib_15) — 10.10.10.253 — D-Link — Reseller: Sajib Ahmed');

        // Fix NULL company_id on bills and payments so TenantAware scope doesn't hide them
        \DB::statement('UPDATE bills SET company_id = (SELECT company_id FROM clients WHERE clients.id = bills.client_id) WHERE bills.company_id IS NULL');
        \DB::statement('UPDATE payments SET company_id = (SELECT company_id FROM clients WHERE clients.id = payments.client_id) WHERE payments.company_id IS NULL');
        $this->command->info('  Fixed NULL company_id on bills and payments.');

        $this->command->info('DemoClientsSeeder completed!');
    }
}
