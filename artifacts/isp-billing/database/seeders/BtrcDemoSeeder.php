<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BtrcDemoSeeder extends Seeder
{
    public function run(): void
    {
        $zones = DB::table('zones')->where('is_active', true)->pluck('id')->toArray();
        $packages = DB::table('packages')->pluck('id', 'price')->toArray();
        $packageIds = array_values($packages);
        $areas = DB::table('areas')->pluck('id')->toArray();

        $bdNames = [
            'Md. Rahim Uddin', 'Fatema Begum', 'Kamal Hossain', 'Nasreen Akter', 'Jahangir Alam',
            'Sultana Razia', 'Abul Kalam', 'Mst. Halima Khatun', 'Mizanur Rahman', 'Taslima Akhter',
            'Shahjahan Miah', 'Rehana Parvin', 'Nurul Islam', 'Shamima Nasrin', 'Abdul Mannan',
            'Roksana Begum', 'Hafizur Rahman', 'Jesmin Akter', 'Monir Hossain', 'Rasheda Khatun',
            'Shafiqul Islam', 'Morium Begum', 'Azizul Haque', 'Kulsum Akhter', 'Delwar Hossain',
            'Amena Begum', 'Farid Ahmed', 'Bilkis Begum', 'Rafiqul Islam', 'Salma Akhter',
            'Tariqul Islam', 'Mahmuda Begum', 'Shamsul Haque', 'Nasima Akhter', 'Joynal Abedin',
            'Shahida Begum', 'Monirul Islam', 'Parveen Akhter', 'Aminul Haque', 'Rokeya Begum',
            'Zahirul Islam', 'Hasina Begum', 'Bazlur Rahman', 'Momtaz Begum', 'Saidur Rahman',
            'Rabeya Khatun', 'Mahbubul Alam', 'Tahmina Akhter', 'Anwarul Islam', 'Jahanara Begum',
        ];

        $fatherNames = [
            'Md. Abdur Rahim', 'Late Shamsul Haque', 'Abdul Karim', 'Md. Nurul Islam', 'Late Hafiz Ahmed',
            'Md. Jalil Miah', 'Abdul Gafur', 'Md. Siddiq Hossain', 'Late Abdul Hamid', 'Md. Kashem Ali',
        ];

        $addresses = [
            'House 12, Road 5, Mirpur-10, Dhaka', 'Flat B3, Khan Tower, Uttara Sector 7, Dhaka',
            'Plot 45, Block D, Bashundhara R/A, Dhaka', 'House 8, Road 3, Dhanmondi 27, Dhaka',
            'Flat A2, Green Residency, Gulshan-2, Dhaka', 'House 22, Sector 3, Uttara, Dhaka',
            'Plot 18, Road 8, Banani DOHS, Dhaka', 'House 5, Block F, Mohammadpur, Dhaka',
            'Flat 4C, Amin Tower, Motijheel, Dhaka', 'House 33, Road 11, Pallabi, Dhaka',
            'Plot 7, Lane 3, Badda, Dhaka', 'House 15, Road 2, Rampura, Dhaka',
            'Flat 6A, City Center, Khilgaon, Dhaka', 'House 9, Block B, Shewrapara, Dhaka',
            'Plot 25, Road 4, Mohakhali, Dhaka', 'House 11, Sector 11, Uttara, Dhaka',
            'Flat 3B, Hasan Bhaban, Tejgaon, Dhaka', 'House 7, Road 6, Kafrul, Dhaka',
            'Plot 14, Lane 2, Shahbag, Dhaka', 'House 20, Road 9, Jatrabari, Dhaka',
        ];

        $connectionTypes = ['pppoe', 'pppoe', 'pppoe', 'pppoe', 'queue', 'pppoe', 'invoice', 'pppoe'];
        $now = Carbon::now();

        $clientsData = [];
        $billsData = [];
        $paymentsData = [];

        for ($i = 0; $i < 50; $i++) {
            $connDate = Carbon::create(2025, rand(1, 12), rand(1, 28));
            $zoneId = $zones[array_rand($zones)];
            $packageId = $packageIds[array_rand($packageIds)];
            $areaId = !empty($areas) ? $areas[array_rand($areas)] : null;
            $pkgPrice = DB::table('packages')->where('id', $packageId)->value('price') ?? 800;
            $connType = $connectionTypes[array_rand($connectionTypes)];
            $status = $i < 42 ? 'active' : ($i < 47 ? 'inactive' : 'suspended');
            $clientIdStr = 'ISP-' . str_pad($i + 1, 5, '0', STR_PAD_LEFT);
            $nid = (string)(1990000000000 + rand(100000, 999999));
            $phone = '01' . rand(3, 9) . str_pad(rand(0, 99999999), 8, '0', STR_PAD_LEFT);
            $ipBase = '10.' . $zoneId . '.' . rand(1, 254);
            $ipAddr = ($i % 5 === 0) ? $ipBase . '.' . rand(2, 254) : null;
            $macAddr = implode(':', array_map(fn() => strtoupper(str_pad(dechex(rand(0, 255)), 2, '0', STR_PAD_LEFT)), range(1, 6)));
            $username = strtolower(str_replace(' ', '.', str_replace(['Md. ', 'Mst. '], '', $bdNames[$i]))) . rand(10, 99);

            $clientsData[] = [
                'client_id' => $clientIdStr,
                'name' => $bdNames[$i],
                'father_husband_name' => $fatherNames[array_rand($fatherNames)],
                'email' => strtolower(str_replace([' ', '.'], '', $bdNames[$i])) . '@gmail.com',
                'phone' => $phone,
                'address' => $addresses[array_rand($addresses)],
                'nid' => $nid,
                'zone_id' => $zoneId,
                'area_id' => $areaId,
                'package_id' => $packageId,
                'username' => $username,
                'pppoe_password' => 'pass' . rand(1000, 9999),
                'mac_address' => $macAddr,
                'ip_address' => $ipAddr,
                'connection_date' => $connDate->format('Y-m-d'),
                'expire_date' => $connDate->copy()->addYear()->format('Y-m-d'),
                'billing_date' => 1,
                'monthly_fee' => $pkgPrice,
                'balance' => 0,
                'due_amount' => $status === 'active' ? 0 : rand(500, 3000),
                'status' => $status,
                'connection_status' => $status === 'active' ? 'online' : 'offline',
                'connection_type' => $connType,
                'connection_medium' => 'fiber',
                'billing_cycle' => 'monthly',
                'bill_calculation_type' => 'auto',
                'payment_deadline' => 10,
                'auto_disconnect_days' => 15,
                'is_locked' => false,
                'mac_binding_enabled' => false,
                'signup_fee' => 1000,
                'signup_fee_paid' => true,
                'installation_charge' => 0,
                'cable_length_meters' => 0,
                'onu_required' => false,
                'router_required' => false,
                'permanent_discount' => 0,
                'internet_quality_score' => rand(60, 100),
                'churn_risk_score' => rand(0, 50) / 100.0,
                'lifetime_value' => $pkgPrice * rand(6, 24),
                'today_usage_bytes' => 0,
                'month_usage_bytes' => 0,
                'total_upload_bytes' => 0,
                'total_download_bytes' => 0,
                'onu_status' => 'unknown',
                'sms_opt_out' => false,
                'is_free' => false,
                'onu_monthly_charge' => 0,
                'company_id' => 1,
                'created_at' => $connDate,
                'updated_at' => $now,
            ];
        }

        DB::table('clients')->insert($clientsData);

        $clients = DB::table('clients')->select('id', 'client_id', 'monthly_fee', 'connection_date', 'status')->get();

        $billNo = 1;
        $payNo = 1;
        foreach ($clients as $client) {
            $connDate = Carbon::parse($client->connection_date);
            $months = $connDate->diffInMonths($now);
            $months = min($months, 6);

            for ($m = 0; $m < $months; $m++) {
                $billMonth = $connDate->copy()->addMonths($m);
                $billStatus = ($client->status === 'active' || $m < $months - 1) ? 'paid' : 'unpaid';
                $paidAmt = $billStatus === 'paid' ? $client->monthly_fee : 0;

                $billId = DB::table('bills')->insertGetId([
                    'bill_no' => 'BILL-' . str_pad($billNo++, 6, '0', STR_PAD_LEFT),
                    'client_id' => $client->id,
                    'billing_month' => $billMonth->month,
                    'billing_year' => $billMonth->year,
                    'bill_date' => $billMonth->startOfMonth()->format('Y-m-d'),
                    'due_date' => $billMonth->copy()->startOfMonth()->addDays(10)->format('Y-m-d'),
                    'package_amount' => $client->monthly_fee,
                    'previous_due' => 0,
                    'adjustment' => 0,
                    'discount' => 0,
                    'total_amount' => $client->monthly_fee,
                    'paid_amount' => $paidAmt,
                    'due_amount' => $client->monthly_fee - $paidAmt,
                    'status' => $billStatus,
                    'advance_used' => 0,
                    'auto_reconnect' => false,
                    'late_fee_applied' => false,
                    'is_prorated' => false,
                    'is_corrected' => false,
                    'is_cancelled' => false,
                    'is_replaced' => false,
                    'company_id' => 1,
                    'created_at' => $billMonth->startOfMonth(),
                    'updated_at' => $now,
                ]);

                if ($billStatus === 'paid') {
                    $payMethods = [1, 1, 1, 2, 2, 3, 4, 5];
                    $payMethod = $payMethods[array_rand($payMethods)];
                    $payDate = $billMonth->copy()->startOfMonth()->addDays(rand(1, 8));

                    DB::table('payments')->insert([
                        'payment_no' => 'PAY-' . str_pad($payNo++, 6, '0', STR_PAD_LEFT),
                        'client_id' => $client->id,
                        'bill_id' => $billId,
                        'amount' => $client->monthly_fee,
                        'payment_method_id' => $payMethod,
                        'payment_date' => $payDate->format('Y-m-d'),
                        'transaction_id' => 'TXN' . strtoupper(substr(md5(rand()), 0, 10)),
                        'status' => 'approved',
                        'source' => 'admin',
                        'submitted_to_admin' => true,
                        'is_reversed' => false,
                        'is_duplicate' => false,
                        'company_id' => 1,
                        'created_at' => $payDate,
                        'updated_at' => $now,
                    ]);
                }
            }
        }
    }
}
