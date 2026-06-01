<?php

namespace App\Services;

use App\Models\MigrationRun;
use App\Models\MigrationRouterSnapshot;
use App\Models\MikrotikDevice;
use App\Models\MikrotikSyncRun;
use App\Models\MikrotikSyncItem;
use App\Models\MikrotikActionLog;
use App\Models\Client;
use App\Models\Bill;
use App\Models\Payment;
use App\Models\Package;
use App\Models\Zone;
use App\Models\Area;
use App\Models\ImportJob;
use App\Models\ImportLog;
use App\Models\PaymentMethod;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class MigrationService
{
    protected ImportService $importService;
    protected BillingService $billingService;

    public function __construct(ImportService $importService, BillingService $billingService)
    {
        $this->importService = $importService;
        $this->billingService = $billingService;
    }

    public function createRun(string $name, int $userId): MigrationRun
    {
        return MigrationRun::create([
            'name' => $name,
            'status' => 'in_progress',
            'current_phase' => 1,
            'initiated_by' => $userId,
            'started_at' => now(),
        ]);
    }

    public function getMigrationFields(): array
    {
        return [
            'required' => ['name', 'phone'],
            'optional' => [
                'client_id', 'username', 'pppoe_password', 'email', 'alt_phone',
                'address', 'nid', 'father_husband_name', 'nearby_landmark',
                'zone_name', 'area_name', 'package_name', 'monthly_fee',
                'connection_date', 'expire_date', 'status', 'connection_type',
                'due_amount', 'advance_balance', 'router_name',
                'mac_address', 'ip_address', 'billing_date', 'billing_cycle',
                'permanent_discount', 'remarks', 'box_name',
                'onu_serial', 'onu_model', 'google_map_lat', 'google_map_lng',
            ],
        ];
    }

    public function getMigrationFieldAliases(): array
    {
        return [
            'name' => ['name', 'client_name', 'customer_name', 'full_name', 'fullname'],
            'phone' => ['phone', 'mobile', 'phone_number', 'contact', 'cell'],
            'username' => ['username', 'pppoe_username', 'pppoe_user', 'user_name', 'login'],
            'pppoe_password' => ['pppoe_password', 'password', 'pass', 'ppp_pass'],
            'email' => ['email', 'email_address', 'mail'],
            'alt_phone' => ['alt_phone', 'alternate_phone', 'secondary_phone'],
            'address' => ['address', 'full_address', 'location'],
            'nid' => ['nid', 'national_id', 'id_number'],
            'father_husband_name' => ['father_husband_name', 'father_name', 'husband_name', 'guardian'],
            'nearby_landmark' => ['nearby_landmark', 'landmark'],
            'zone_name' => ['zone_name', 'zone', 'region'],
            'area_name' => ['area_name', 'area', 'subzone', 'sub_area'],
            'package_name' => ['package_name', 'package', 'plan', 'plan_name', 'internet_package', 'profile'],
            'monthly_fee' => ['monthly_fee', 'fee', 'monthly', 'rent', 'monthly_rent', 'price'],
            'connection_date' => ['connection_date', 'start_date', 'activation_date', 'join_date', 'created_at'],
            'expire_date' => ['expire_date', 'expiry_date', 'expiry'],
            'status' => ['status', 'state', 'client_status', 'account_status'],
            'connection_type' => ['connection_type', 'conn_type', 'type'],
            'due_amount' => ['due_amount', 'due', 'outstanding', 'balance_due', 'arrears'],
            'advance_balance' => ['advance_balance', 'advance', 'credit', 'wallet', 'prepaid_balance', 'advance_amount'],
            'router_name' => ['router_name', 'router', 'mikrotik', 'device', 'pop'],
            'mac_address' => ['mac_address', 'mac', 'onu_mac'],
            'ip_address' => ['ip_address', 'ip', 'static_ip'],
            'billing_date' => ['billing_date', 'bill_day', 'billing_day'],
            'billing_cycle' => ['billing_cycle', 'cycle'],
            'permanent_discount' => ['permanent_discount', 'discount'],
            'remarks' => ['remarks', 'notes', 'comment'],
            'box_name' => ['box_name', 'box', 'tj', 'tj_box', 'junction_box'],
            'client_id' => ['client_id', 'customer_id', 'id', 'sl', 'serial'],
            'onu_serial' => ['onu_serial', 'onu_sn', 'serial_number'],
            'onu_model' => ['onu_model', 'onu_type'],
            'google_map_lat' => ['google_map_lat', 'latitude', 'lat'],
            'google_map_lng' => ['google_map_lng', 'longitude', 'lng'],
        ];
    }

    public function autoMapFields(array $sourceHeaders): array
    {
        $mapping = [];
        $usedHeaders = [];
        $fields = $this->getMigrationFields();
        $aliases = $this->getMigrationFieldAliases();
        $allFields = array_merge($fields['required'], $fields['optional']);

        foreach ($allFields as $targetField) {
            $fieldAliases = $aliases[$targetField] ?? [$targetField];
            foreach ($sourceHeaders as $header) {
                if (in_array($header, $usedHeaders)) continue;
                $normalizedHeader = strtolower(preg_replace('/[\s\-_]+/', '', trim($header)));
                foreach ($fieldAliases as $alias) {
                    $normalizedAlias = strtolower(preg_replace('/[\s\-_]+/', '', $alias));
                    if ($normalizedHeader === $normalizedAlias) {
                        $mapping[$targetField] = $header;
                        $usedHeaders[] = $header;
                        break 2;
                    }
                }
            }
        }

        return $mapping;
    }

    public function validateMigrationData(array $rows, array $fieldMapping): array
    {
        $validRows = [];
        $invalidRows = [];
        $duplicateRows = [];
        $warnings = [];
        $existingUsernames = Client::whereNotNull('username')->pluck('username')->map(fn($u) => strtolower($u))->toArray();
        $existingPhones = Client::whereNotNull('phone')->pluck('phone')->toArray();
        $seenUsernames = [];
        $seenPhones = [];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;
            $mapped = $this->applyMapping($row, $fieldMapping);
            $errors = [];

            if (empty($mapped['name'])) $errors[] = 'Missing required field: name';
            if (empty($mapped['phone'])) $errors[] = 'Missing required field: phone';

            $username = strtolower($mapped['username'] ?? '');
            $phone = $mapped['phone'] ?? '';

            $isDuplicate = false;
            if ($username && in_array($username, $existingUsernames)) {
                $isDuplicate = true;
            }
            if ($username && in_array($username, $seenUsernames)) {
                $isDuplicate = true;
            }
            if ($phone && in_array($phone, $existingPhones)) {
                $isDuplicate = true;
            }
            if ($phone && in_array($phone, $seenPhones)) {
                $isDuplicate = true;
            }

            if ($username) $seenUsernames[] = $username;
            if ($phone) $seenPhones[] = $phone;

            if (!empty($mapped['package_name'])) {
                $pkg = Package::where('name', $mapped['package_name'])->first();
                if (!$pkg) {
                    $warnings[] = "Row {$rowNumber}: Package '{$mapped['package_name']}' not found - will be auto-created";
                }
            }

            if (!empty($mapped['monthly_fee']) && !is_numeric($mapped['monthly_fee'])) {
                $errors[] = 'Invalid monthly fee format';
            }
            if (!empty($mapped['due_amount']) && !is_numeric($mapped['due_amount'])) {
                $errors[] = 'Invalid due amount format';
            }
            if (!empty($mapped['advance_balance']) && !is_numeric($mapped['advance_balance'])) {
                $errors[] = 'Invalid advance balance format';
            }

            if ($isDuplicate) {
                $duplicateRows[] = ['row' => $rowNumber, 'data' => $mapped, 'errors' => $errors];
            } elseif (!empty($errors)) {
                $invalidRows[] = ['row' => $rowNumber, 'data' => $mapped, 'errors' => $errors];
            } else {
                $validRows[] = ['row' => $rowNumber, 'data' => $mapped];
            }
        }

        return [
            'total' => count($rows),
            'valid' => count($validRows),
            'invalid' => count($invalidRows),
            'duplicate' => count($duplicateRows),
            'valid_rows' => $validRows,
            'invalid_rows' => $invalidRows,
            'duplicate_rows' => $duplicateRows,
            'warnings' => $warnings,
        ];
    }

    public function executeMigrationImport(MigrationRun $run, array $validRows, int $userId): array
    {
        $results = [
            'clients_created' => 0,
            'clients_failed' => 0,
            'bills_created' => 0,
            'errors' => [],
        ];

        DB::beginTransaction();
        try {
            foreach ($validRows as $item) {
                $data = $item['data'];
                try {
                    $spName = 'import_row_' . $item['row'];
                    DB::statement("SAVEPOINT {$spName}");

                    $client = $this->createMigrationClient($data, $userId);
                    $results['clients_created']++;

                    $dueAmount = floatval($data['due_amount'] ?? 0);
                    $advanceBalance = floatval($data['advance_balance'] ?? 0);

                    if ($dueAmount > 0) {
                        $this->createDueBill($client, $dueAmount, $userId);
                        $results['bills_created']++;
                    }

                    if ($advanceBalance > 0) {
                        $this->createAdvancePayment($client, $advanceBalance, $userId);
                    }

                    $this->billingService->recalculateClientBalance($client);

                    DB::statement("RELEASE SAVEPOINT {$spName}");
                } catch (\Exception $e) {
                    try { DB::statement("ROLLBACK TO SAVEPOINT import_row_" . $item['row']); } catch (\Exception $ignore) {}
                    $results['clients_failed']++;
                    $results['errors'][] = "Row {$item['row']}: " . $e->getMessage();
                    Log::warning("Migration import failed for row {$item['row']}: " . $e->getMessage());
                }
            }

            $run->update([
                'total_clients_imported' => $results['clients_created'],
                'phase1_status' => 'completed',
                'phase1_summary' => $results,
                'current_phase' => 2,
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $run->update([
                'phase1_status' => 'failed',
                'phase1_summary' => ['error' => $e->getMessage()],
            ]);
            throw $e;
        }

        return $results;
    }

    protected function createMigrationClient(array $data, int $userId): Client
    {
        $zone = null;
        if (!empty($data['zone_name'])) {
            $zone = Zone::firstOrCreate(
                ['name' => $data['zone_name']],
                ['status' => 'active']
            );
        }

        $area = null;
        if (!empty($data['area_name']) && $zone) {
            $area = Area::firstOrCreate(
                ['name' => $data['area_name'], 'zone_id' => $zone->id],
                ['status' => 'active']
            );
        }

        $package = null;
        if (!empty($data['package_name'])) {
            $package = Package::where('name', $data['package_name'])->first();
            if (!$package) {
                $code = strtoupper(\Illuminate\Support\Str::slug($data['package_name'], '_'));
                $codeBase = $code;
                $counter = 1;
                while (Package::where('code', $code)->exists()) {
                    $code = $codeBase . '_' . $counter++;
                }
                $fee = floatval($data['monthly_fee'] ?? 0);
                $package = Package::create([
                    'name' => $data['package_name'],
                    'code' => $code,
                    'price' => $fee,
                    'bandwidth' => $data['package_name'],
                    'download_speed' => 0,
                    'upload_speed' => 0,
                    'is_active' => true,
                ]);
            }
        }

        $mikrotikDevice = null;
        if (!empty($data['router_name'])) {
            $mikrotikDevice = MikrotikDevice::where('name', $data['router_name'])->first();
            if (!$mikrotikDevice) {
                $mikrotikDevice = MikrotikDevice::where('host', $data['router_name'])->first();
            }
        }

        $status = $this->normalizeStatus($data['status'] ?? 'active');

        $importMode = SystemSetting::get('import_client_code_mode', 'auto');
        $clientId = null;

        if ($importMode === 'use_imported' && !empty($data['client_id'])) {
            $clientId = $data['client_id'];
        } elseif ($importMode === 'manual_start') {
            $manualStart = (int) SystemSetting::get('import_code_start_number', 1000);
            $maxNumeric = Client::whereRaw("client_id ~ '^[0-9]+$'")
                ->selectRaw("MAX(CAST(client_id AS BIGINT)) as max_num")
                ->value('max_num') ?? 0;
            $nextId = max($manualStart, $maxNumeric + 1);
            $clientId = (string) $nextId;
        } else {
            $clientId = Client::generateClientId();
        }

        if (Client::where('client_id', $clientId)->exists()) {
            $clientId = Client::generateClientId();
        }

        $connectionDate = null;
        if (!empty($data['connection_date'])) {
            try { $connectionDate = \Carbon\Carbon::parse($data['connection_date']); } catch (\Exception $e) {}
        }

        $client = Client::create([
            'client_id' => $clientId,
            'name' => $data['name'],
            'phone' => $data['phone'],
            'email' => $data['email'] ?? null,
            'alt_phone' => $data['alt_phone'] ?? null,
            'address' => $data['address'] ?? '',
            'nid' => $data['nid'] ?? null,
            'father_husband_name' => $data['father_husband_name'] ?? null,
            'nearby_landmark' => $data['nearby_landmark'] ?? null,
            'zone_id' => $zone?->id,
            'area_id' => $area?->id,
            'package_id' => $package?->id,
            'username' => $data['username'] ?? $clientId,
            'pppoe_password' => $data['pppoe_password'] ?? null,
            'mac_address' => $data['mac_address'] ?? null,
            'ip_address' => $data['ip_address'] ?? null,
            'connection_date' => $connectionDate ?? now(),
            'monthly_fee' => floatval($data['monthly_fee'] ?? ($package ? $package->price : 0)),
            'billing_date' => $data['billing_date'] ?? 1,
            'billing_cycle' => $data['billing_cycle'] ?? 'monthly',
            'status' => $status,
            'connection_status' => 'offline',
            'connection_type' => $data['connection_type'] ?? 'pppoe',
            'permanent_discount' => floatval($data['permanent_discount'] ?? 0),
            'remarks' => $data['remarks'] ?? 'Migrated from old billing system',
            'mikrotik_device_id' => $mikrotikDevice?->id,
            'onu_serial' => $data['onu_serial'] ?? null,
            'onu_model' => $data['onu_model'] ?? null,
            'google_map_lat' => $data['google_map_lat'] ?? null,
            'google_map_lng' => $data['google_map_lng'] ?? null,
            'balance' => 0,
            'due_amount' => 0,
        ]);

        if (!$client->user_id) {
            $email = $client->email ?? $client->phone . '@client.local';
            $user = \App\Models\User::where('email', $email)->first();
            if (!$user) {
                $user = \App\Models\User::create([
                    'name' => $client->name,
                    'email' => $email,
                    'password' => Hash::make($data['pppoe_password'] ?? 'changeme123'),
                    'role' => 'client',
                ]);
            }
            $client->update(['user_id' => $user->id]);
        }

        return $client;
    }

    protected function createDueBill(Client $client, float $dueAmount, int $userId = 1): Bill
    {
        $now = now();
        return Bill::create([
            'bill_no' => 'MIG-' . $client->client_id . '-' . $now->format('Ymd'),
            'client_id' => $client->id,
            'billing_month' => $now->month,
            'billing_year' => $now->year,
            'bill_date' => $now,
            'due_date' => $now->copy()->addDays(15),
            'package_amount' => $dueAmount,
            'previous_due' => 0,
            'adjustment' => 0,
            'discount' => 0,
            'total_amount' => $dueAmount,
            'paid_amount' => 0,
            'due_amount' => $dueAmount,
            'status' => 'unpaid',
            'remarks' => 'Migrated due from old billing system',
            'generated_by' => $userId,
        ]);
    }

    protected function createAdvancePayment(Client $client, float $amount, int $userId): Payment
    {
        $cashMethod = PaymentMethod::where('name', 'Cash')->first();
        return Payment::create([
            'payment_no' => 'MIG-ADV-' . $client->client_id . '-' . now()->format('Ymd'),
            'client_id' => $client->id,
            'amount' => $amount,
            'payment_method_id' => $cashMethod?->id ?? 1,
            'payment_date' => now(),
            'remarks' => 'Advance balance migrated from old billing system',
            'collected_by' => $userId,
            'status' => 'approved',
            'source' => 'migration',
        ]);
    }

    protected function normalizeStatus(string $status): string
    {
        $status = strtolower(trim($status));
        $map = [
            'active' => 'active', 'enabled' => 'active', 'on' => 'active', '1' => 'active', 'yes' => 'active',
            'inactive' => 'inactive', 'disabled' => 'inactive', 'off' => 'inactive', '0' => 'inactive', 'no' => 'inactive',
            'suspended' => 'inactive', 'suspend' => 'inactive', 'due' => 'inactive',
            'expired' => 'expired', 'expire' => 'expired',
        ];
        return $map[$status] ?? 'active';
    }

    protected function applyMapping(array $row, array $mapping): array
    {
        $mapped = [];
        foreach ($mapping as $targetField => $sourceField) {
            if (!empty($sourceField) && isset($row[$sourceField])) {
                $mapped[$targetField] = trim($row[$sourceField]);
            }
        }
        return $mapped;
    }

    public function runDiscovery(MigrationRun $run, array $deviceIds): array
    {
        $results = [
            'routers_scanned' => 0,
            'routers_failed' => 0,
            'total_users_found' => 0,
            'per_router' => [],
        ];

        MigrationRouterSnapshot::where('migration_run_id', $run->id)->delete();

        foreach ($deviceIds as $deviceId) {
            $device = MikrotikDevice::find($deviceId);
            if (!$device) continue;

            try {
                $mtService = MikroTikService::fromDevice($device);
                $secrets = $mtService->getAllPPPoESecrets();

                $snapshots = [];
                foreach ($secrets as $secret) {
                    $username = $secret['name'] ?? null;
                    if (!$username) continue;

                    $snapshots[] = [
                        'migration_run_id' => $run->id,
                        'device_id' => $device->id,
                        'device_name' => $device->name,
                        'device_host' => $device->host ?? $device->ip_address,
                        'username' => $username,
                        'profile' => $secret['profile'] ?? 'default',
                        'password' => $secret['password'] ?? null,
                        'service' => $secret['service'] ?? 'pppoe',
                        'disabled' => ($secret['disabled'] ?? 'false') === 'true',
                        'comment' => $secret['comment'] ?? null,
                        'remote_address' => $secret['remote-address'] ?? null,
                        'match_status' => 'unmatched',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                foreach (array_chunk($snapshots, 500) as $chunk) {
                    MigrationRouterSnapshot::insert($chunk);
                }

                $results['routers_scanned']++;
                $results['total_users_found'] += count($snapshots);
                $results['per_router'][] = [
                    'device_id' => $device->id,
                    'name' => $device->name,
                    'host' => $device->host ?? $device->ip_address,
                    'users_found' => count($snapshots),
                    'status' => 'success',
                ];

            } catch (\Exception $e) {
                $results['routers_failed']++;
                $results['per_router'][] = [
                    'device_id' => $device->id,
                    'name' => $device->name,
                    'host' => $device->host ?? $device->ip_address,
                    'users_found' => 0,
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                ];
                Log::error("Migration discovery failed for {$device->name}: " . $e->getMessage());
            }
        }

        $run->update([
            'total_routers_scanned' => $results['routers_scanned'],
            'total_router_users_found' => $results['total_users_found'],
            'phase2_status' => 'completed',
            'phase2_summary' => $results,
            'current_phase' => 3,
        ]);

        return $results;
    }

    public function runReconciliation(MigrationRun $run): array
    {
        $snapshots = MigrationRouterSnapshot::where('migration_run_id', $run->id)->get();
        $clients = Client::whereNotNull('username')
            ->where('username', '!=', '')
            ->where('status', '!=', 'deleted')
            ->get()
            ->keyBy(fn($c) => strtolower($c->username));

        $results = [
            'matched' => 0,
            'ghost_users' => 0,
            'billing_only' => 0,
            'profile_mismatch' => 0,
            'status_mismatch' => 0,
        ];

        foreach ($snapshots as $snapshot) {
            $username = strtolower($snapshot->username);
            $client = $clients->get($username);

            if ($client) {
                $snapshot->billing_client_id = $client->id;
                $snapshot->match_status = 'matched';
                $snapshot->match_type = 'username';

                $clientProfile = $client->package ? strtolower($client->package->name) : '';
                $routerProfile = strtolower($snapshot->profile);

                $shouldBeDisabled = in_array($client->status, ['inactive', 'expired']);

                if ($clientProfile && $routerProfile && $clientProfile !== $routerProfile) {
                    $snapshot->match_status = 'profile_mismatch';
                    $results['profile_mismatch']++;
                } elseif ($snapshot->disabled !== $shouldBeDisabled) {
                    $snapshot->match_status = 'status_mismatch';
                    $results['status_mismatch']++;
                } else {
                    $results['matched']++;
                }

                $snapshot->save();
                $clients->forget($username);
            } else {
                $snapshot->match_status = 'ghost_user';
                $snapshot->save();
                $results['ghost_users']++;
            }
        }

        $results['billing_only'] = $clients->count();

        $run->update([
            'total_reconciled' => $results['matched'] + $results['profile_mismatch'] + $results['status_mismatch'],
            'phase3_status' => 'completed',
            'phase3_summary' => $results,
            'current_phase' => 4,
        ]);

        return $results;
    }

    public function applySyncAction(MigrationRun $run, int $snapshotId, string $action): array
    {
        $snapshot = MigrationRouterSnapshot::where('migration_run_id', $run->id)
            ->where('id', $snapshotId)->firstOrFail();

        $device = MikrotikDevice::findOrFail($snapshot->device_id);

        DB::beginTransaction();
        try {
            $mtService = new SimpleMikroTikService();
            $connected = $mtService->connectFromDevice($device);
            if (!$connected) {
                throw new \Exception("Cannot connect to router {$device->name}");
            }

            $result = ['success' => false, 'message' => ''];

            switch ($action) {
                case 'link':
                    if ($snapshot->billing_client_id) {
                        $client = Client::find($snapshot->billing_client_id);
                        if ($client) {
                            $client->update(['mikrotik_device_id' => $device->id]);
                            $snapshot->update(['match_status' => 'synced']);
                            $result = ['success' => true, 'message' => "Linked {$snapshot->username} to client {$client->client_id}"];
                        }
                    }
                    break;

                case 'create_in_billing':
                    $client = $this->createClientFromSnapshot($snapshot);
                    $snapshot->update([
                        'billing_client_id' => $client->id,
                        'match_status' => 'synced',
                    ]);
                    $result = ['success' => true, 'message' => "Created billing client for {$snapshot->username}"];
                    break;

                case 'update_profile':
                    if ($snapshot->billing_client_id) {
                        $client = Client::find($snapshot->billing_client_id);
                        if ($client && $client->package) {
                            $mtService->updatePPPoEProfile($snapshot->username, $client->package->name);
                            $snapshot->update(['match_status' => 'synced', 'profile' => $client->package->name]);
                            $result = ['success' => true, 'message' => "Updated router profile for {$snapshot->username}"];
                        }
                    }
                    break;

                case 'update_status':
                    if ($snapshot->billing_client_id) {
                        $client = Client::find($snapshot->billing_client_id);
                        if ($client) {
                            $shouldBeDisabled = in_array($client->status, ['inactive', 'expired']);
                            if ($shouldBeDisabled && !$snapshot->disabled) {
                                $mtService->disablePPPoESecret($snapshot->username);
                            } elseif (!$shouldBeDisabled && $snapshot->disabled) {
                                $mtService->enablePPPoESecret($snapshot->username);
                            }
                            $snapshot->update(['match_status' => 'synced', 'disabled' => $shouldBeDisabled]);
                            $result = ['success' => true, 'message' => "Updated router status for {$snapshot->username}"];
                        }
                    }
                    break;

                case 'skip':
                    $snapshot->update(['match_status' => 'skipped']);
                    $result = ['success' => true, 'message' => "Skipped {$snapshot->username}"];
                    break;
            }

            MikrotikActionLog::create([
                'device_id' => $device->id,
                'action' => "migration_{$action}",
                'action_type' => 'migration_sync',
                'username' => $snapshot->username,
                'router_ip' => $device->host ?? $device->ip_address,
                'trigger_source' => 'migration_wizard',
                'triggered_by' => $run->initiated_by,
                'status' => $result['success'] ? 'success' : 'failed',
                'request_data' => json_encode(['snapshot_id' => $snapshotId, 'action' => $action]),
                'response_data' => json_encode($result),
                'executed_at' => now(),
            ]);

            $mtService->disconnect();
            DB::commit();

            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Migration sync action failed: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function applyAllSyncActions(MigrationRun $run): array
    {
        $results = ['applied' => 0, 'failed' => 0, 'skipped' => 0];

        $snapshots = MigrationRouterSnapshot::where('migration_run_id', $run->id)
            ->whereIn('match_status', ['matched', 'profile_mismatch', 'status_mismatch'])
            ->get();

        foreach ($snapshots as $snapshot) {
            $action = match($snapshot->match_status) {
                'matched' => 'link',
                'profile_mismatch' => 'update_profile',
                'status_mismatch' => 'update_status',
                default => 'skip',
            };

            $result = $this->applySyncAction($run, $snapshot->id, $action);
            if ($result['success']) {
                $results['applied']++;
            } else {
                $results['failed']++;
            }
        }

        $run->update([
            'total_synced' => $results['applied'],
            'total_errors' => $results['failed'],
            'phase4_status' => 'completed',
            'phase4_summary' => $results,
            'current_phase' => 5,
        ]);

        return $results;
    }

    public function getGoLiveReport(MigrationRun $run): array
    {
        $totalClients = Client::where('status', '!=', 'deleted')->count();
        $totalWithUsername = Client::where('status', '!=', 'deleted')
            ->whereNotNull('username')->where('username', '!=', '')->count();
        $totalWithRouter = Client::where('status', '!=', 'deleted')
            ->whereNotNull('mikrotik_device_id')->count();

        $snapshots = MigrationRouterSnapshot::where('migration_run_id', $run->id);
        $totalSnapshots = $snapshots->count();
        $syncedSnapshots = (clone $snapshots)->where('match_status', 'synced')->count();
        $ghostUsers = (clone $snapshots)->where('match_status', 'ghost_user')->count();
        $unmatched = (clone $snapshots)->where('match_status', 'unmatched')->count();
        $skipped = (clone $snapshots)->where('match_status', 'skipped')->count();

        $billingOnlyClients = Client::where('status', '!=', 'deleted')
            ->whereNotNull('username')->where('username', '!=', '')
            ->whereNotIn('username', MigrationRouterSnapshot::where('migration_run_id', $run->id)->pluck('username'))
            ->count();

        $readiness = [
            'all_clients_imported' => $run->total_clients_imported > 0,
            'router_scanned' => $run->total_routers_scanned > 0,
            'reconciliation_done' => $run->isPhaseComplete(3),
            'sync_applied' => $run->isPhaseComplete(4),
            'unresolved_issues' => $ghostUsers + $unmatched,
        ];

        $overallScore = 0;
        if ($readiness['all_clients_imported']) $overallScore += 25;
        if ($readiness['router_scanned']) $overallScore += 25;
        if ($readiness['reconciliation_done']) $overallScore += 25;
        if ($readiness['sync_applied']) $overallScore += 25;

        return [
            'total_clients' => $totalClients,
            'clients_with_username' => $totalWithUsername,
            'clients_with_router' => $totalWithRouter,
            'router_snapshots_total' => $totalSnapshots,
            'router_snapshots_synced' => $syncedSnapshots,
            'ghost_users' => $ghostUsers,
            'unmatched' => $unmatched,
            'skipped' => $skipped,
            'billing_only_clients' => $billingOnlyClients,
            'migration_run' => $run,
            'readiness' => $readiness,
            'readiness_score' => $overallScore,
        ];
    }

    public function markGoLive(MigrationRun $run): void
    {
        $run->update([
            'phase5_status' => 'completed',
            'status' => 'completed',
            'completed_at' => now(),
            'phase5_summary' => $this->getGoLiveReport($run),
        ]);
    }

    protected function createClientFromSnapshot(MigrationRouterSnapshot $snapshot): Client
    {
        $package = Package::where('name', $snapshot->profile)->first();

        $maxId = Client::max('id') ?? 0;
        $clientId = 'C' . str_pad($maxId + 1, 5, '0', STR_PAD_LEFT);

        $client = Client::create([
            'client_id' => $clientId,
            'name' => $snapshot->comment ?: $snapshot->username,
            'phone' => '01XXXXXXXXX',
            'username' => $snapshot->username,
            'pppoe_password' => $snapshot->password,
            'package_id' => $package?->id,
            'monthly_fee' => $package?->price ?? 0,
            'status' => $snapshot->disabled ? 'inactive' : 'active',
            'connection_status' => $snapshot->disabled ? 'inactive' : 'active',
            'connection_type' => $snapshot->service ?? 'pppoe',
            'connection_date' => now(),
            'billing_date' => 1,
            'mikrotik_device_id' => $snapshot->device_id,
            'remarks' => 'Created from router discovery (ghost user)',
            'balance' => 0,
            'due_amount' => 0,
        ]);

        $email = $client->phone . '@client.local';
        $user = \App\Models\User::where('email', $email)->first();
        if (!$user) {
            $user = \App\Models\User::create([
                'name' => $client->name,
                'email' => $email,
                'password' => Hash::make($snapshot->password ?? 'changeme123'),
                'role' => 'client',
            ]);
        }
        $client->update(['user_id' => $user->id]);

        return $client;
    }
}
