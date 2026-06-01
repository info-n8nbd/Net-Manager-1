<?php

namespace App\Services;

use App\Models\ImportJob;
use App\Models\ImportLog;
use App\Models\FieldMappingTemplate;
use App\Models\Client;
use App\Models\Bill;
use App\Models\Payment;
use App\Models\Zone;
use App\Models\Area;
use App\Models\Package;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use ZipArchive;

class ImportService
{
    protected array $dataTypeFields = [
        'clients' => [
            'required' => ['name', 'phone'],
            'optional' => [
                'client_id', 'email', 'username', 'alt_phone', 'address', 'nid', 
                'father_husband_name', 'date_of_birth', 'nearby_landmark',
                'zone_name', 'area_name', 'package_name', 'connection_date', 'expire_date',
                'status', 'connection_status', 'balance', 'due_amount', 'monthly_fee', 
                'permanent_discount', 'billing_date', 'billing_cycle', 'connection_type',
                'pppoe_username', 'pppoe_password', 'mac_address', 'ip_address',
                'onu_serial', 'onu_model', 'onu_port', 'olt_port',
                'google_map_location', 'google_map_lat', 'google_map_lng',
                'installation_charge', 'cable_length_meters', 'preferred_payment_method',
                'remarks', 'technician_note',
            ],
        ],
        'bills' => [
            'required' => ['client_identifier', 'amount', 'bill_month'],
            'optional' => ['discount', 'net_amount', 'status', 'due_date', 'notes', 'generated_at'],
        ],
        'payments' => [
            'required' => ['client_identifier', 'amount', 'payment_date'],
            'optional' => ['payment_method', 'transaction_id', 'bill_month', 'notes', 'collected_by'],
        ],
        'zones' => [
            'required' => ['name'],
            'optional' => ['description', 'status'],
        ],
        'areas' => [
            'required' => ['name', 'zone_name'],
            'optional' => ['description', 'status'],
        ],
        'packages' => [
            'required' => ['name', 'price', 'bandwidth'],
            'optional' => ['description', 'status', 'bandwidth_unit'],
        ],
    ];

    public function getSupportedDataTypes(): array
    {
        return array_keys($this->dataTypeFields);
    }

    public function getFieldsForDataType(string $dataType): array
    {
        return $this->dataTypeFields[$dataType] ?? [];
    }

    public function parseFile(string $filePath, string $fileType): array
    {
        return match(strtolower($fileType)) {
            'xlsx', 'xls' => $this->parseExcel($filePath),
            'csv' => $this->parseCsv($filePath),
            'json' => $this->parseJson($filePath),
            'zip' => $this->parseZip($filePath),
            default => throw new \Exception("Unsupported file type: {$fileType}"),
        };
    }

    protected function parseExcel(string $filePath): array
    {
        $fullPath = Storage::disk('local')->path($filePath);
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($fullPath);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = [];
        $headers = [];
        
        foreach ($worksheet->getRowIterator() as $rowIndex => $row) {
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);
            
            $rowData = [];
            foreach ($cellIterator as $cell) {
                $rowData[] = $cell->getValue();
            }
            
            if ($rowIndex === 1) {
                $headers = array_map(fn($h) => strtolower(trim($h ?? '')), $rowData);
                $headers = array_filter($headers, fn($h) => $h !== '');
            } else {
                if (array_filter($rowData)) {
                    $rowSlice = array_slice($rowData, 0, count($headers));
                    while (count($rowSlice) < count($headers)) {
                        $rowSlice[] = null;
                    }
                    $rows[] = array_combine($headers, $rowSlice);
                }
            }
        }
        
        return ['headers' => $headers, 'rows' => $rows];
    }

    protected function parseCsv(string $filePath): array
    {
        $fullPath = Storage::disk('local')->path($filePath);
        $content = file_get_contents($fullPath);
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);
        
        $handle = fopen('php://temp', 'r+');
        fwrite($handle, $content);
        rewind($handle);
        
        $headers = [];
        $rows = [];
        $rowIndex = 0;
        
        while (($data = fgetcsv($handle, 0, ',', '"', '')) !== false) {
            if ($rowIndex === 0) {
                $headers = array_map(fn($h) => strtolower(trim($h ?? '')), $data);
                $headers = array_filter($headers, fn($h) => $h !== '');
                $headers = array_values($headers);
            } else {
                if (array_filter($data)) {
                    $rowSlice = array_slice($data, 0, count($headers));
                    while (count($rowSlice) < count($headers)) {
                        $rowSlice[] = null;
                    }
                    $rows[] = array_combine($headers, $rowSlice);
                }
            }
            $rowIndex++;
        }
        
        fclose($handle);
        return ['headers' => $headers, 'rows' => $rows];
    }

    protected function parseJson(string $filePath): array
    {
        $fullPath = Storage::disk('local')->path($filePath);
        $content = file_get_contents($fullPath);
        $data = json_decode($content, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Invalid JSON file: ' . json_last_error_msg());
        }
        
        if (empty($data)) {
            return ['headers' => [], 'rows' => []];
        }
        
        $headers = array_keys($data[0] ?? []);
        $headers = array_map(fn($h) => strtolower(trim($h)), $headers);
        
        $rows = array_map(function($row) use ($headers) {
            $normalizedRow = [];
            foreach ($row as $key => $value) {
                $normalizedRow[strtolower(trim($key))] = $value;
            }
            return $normalizedRow;
        }, $data);
        
        return ['headers' => $headers, 'rows' => $rows];
    }

    protected function parseZip(string $filePath): array
    {
        $fullPath = Storage::disk('local')->path($filePath);
        $extractPath = Storage::disk('local')->path('imports/extracted/' . Str::uuid());
        
        $zip = new ZipArchive();
        if ($zip->open($fullPath) !== true) {
            throw new \Exception('Failed to open ZIP file');
        }
        
        $zip->extractTo($extractPath);
        $zip->close();
        
        $dataFile = null;
        foreach (['data.json', 'data.csv', 'data.xlsx'] as $possibleFile) {
            if (file_exists($extractPath . '/' . $possibleFile)) {
                $dataFile = $extractPath . '/' . $possibleFile;
                break;
            }
        }
        
        if (!$dataFile) {
            $files = glob($extractPath . '/*.{json,csv,xlsx}', GLOB_BRACE);
            $dataFile = $files[0] ?? null;
        }
        
        if (!$dataFile) {
            throw new \Exception('No data file found in ZIP archive');
        }
        
        $extension = pathinfo($dataFile, PATHINFO_EXTENSION);
        $relativePath = str_replace(Storage::disk('local')->path(''), '', $dataFile);
        
        return $this->parseFile($relativePath, $extension);
    }

    public function autoMapFields(array $sourceHeaders, array $targetFields): array
    {
        $mapping = [];
        $usedHeaders = [];
        $allFields = array_merge($targetFields['required'] ?? [], $targetFields['optional'] ?? []);
        
        $fieldAliases = [
            'zone_name' => ['zone name', 'zone_name', 'zonename', 'zone', 'region'],
            'area_name' => ['area name', 'area_name', 'areaname', 'area', 'subzone', 'subzone_name', 'sub_zone', 'sub_area'],
            'package_name' => ['package name', 'package_name', 'packagename', 'package', 'plan', 'plan_name', 'internet_package'],
            'pppoe_username' => ['pppoe username', 'pppoe_username', 'pppoeusername', 'pppoe_user', 'ppp_user'],
            'pppoe_password' => ['pppoe password', 'pppoe_password', 'pppoepassword', 'pppoe_pass', 'ppp_pass'],
            'connection_date' => ['connection date', 'connection_date', 'connectiondate', 'start_date', 'activation_date', 'join_date'],
            'expire_date' => ['expire date', 'expire_date', 'expiredate', 'expiry_date', 'expiry'],
            'monthly_fee' => ['monthly fee', 'monthly_fee', 'monthlyfee', 'fee', 'monthly', 'rent', 'monthly_rent'],
            'billing_date' => ['billing date', 'billing_date', 'billingdate', 'bill_day'],
            'billing_cycle' => ['billing cycle', 'billing_cycle', 'billingcycle'],
            'connection_type' => ['connection type', 'connection_type', 'connectiontype', 'conn_type'],
            'connection_status' => ['connection status', 'connection_status', 'connectionstatus', 'online_status'],
            'due_amount' => ['due amount', 'due_amount', 'dueamount', 'outstanding'],
            'permanent_discount' => ['permanent discount', 'permanent_discount', 'permanentdiscount', 'discount'],
            'father_husband_name' => ['father husband name', 'father_husband_name', 'fatherhusband', 'father name', 'father_name'],
            'date_of_birth' => ['date of birth', 'date_of_birth', 'dateofbirth', 'dob', 'birth_date'],
            'alt_phone' => ['alt phone', 'alt_phone', 'altphone', 'alternate_phone', 'secondary_phone'],
            'nearby_landmark' => ['nearby landmark', 'nearby_landmark', 'nearbylandmark', 'landmark'],
            'mac_address' => ['mac address', 'mac_address', 'macaddress', 'mac', 'onu_mac', 'onu mac'],
            'ip_address' => ['ip address', 'ip_address', 'ipaddress', 'ip', 'static_ip'],
            'onu_serial' => ['onu serial', 'onu_serial', 'onuserial', 'onu_sn', 'serial'],
            'onu_model' => ['onu model', 'onu_model', 'onumodel'],
            'onu_port' => ['onu port', 'onu_port', 'onuport'],
            'olt_port' => ['olt port', 'olt_port', 'oltport'],
            'google_map_location' => ['google map location', 'google_map_location', 'googlemaplocation', 'map_url', 'gmap'],
            'google_map_lat' => ['google map lat', 'google_map_lat', 'googlemaplat', 'latitude', 'lat'],
            'google_map_lng' => ['google map lng', 'google_map_lng', 'googlemaplng', 'longitude', 'lng'],
            'installation_charge' => ['installation charge', 'installation_charge', 'installationcharge', 'install_fee'],
            'cable_length_meters' => ['cable length meters', 'cable_length_meters', 'cablelength', 'cable_length'],
            'preferred_payment_method' => ['preferred payment method', 'preferred_payment_method', 'preferredpayment', 'pay_method'],
            'technician_note' => ['technician note', 'technician_note', 'techniciannote', 'tech_note'],
            'client_id' => ['client id', 'client_id', 'clientid', 'id'],
            'name' => ['name', 'client_name', 'customer_name', 'full_name', 'fullname'],
            'phone' => ['phone', 'mobile', 'phone_number', 'contact', 'cell', 'telephone'],
            'email' => ['email', 'email_address', 'mail', 'e-mail'],
            'address' => ['address', 'full_address', 'location', 'addr'],
            'username' => ['username', 'user_name', 'login', 'user'],
            'nid' => ['nid', 'national_id', 'id_number', 'identity'],
            'status' => ['status', 'state', 'active', 'is_active'],
            'balance' => ['balance', 'due', 'advance'],
            'remarks' => ['remarks', 'notes', 'comment', 'description'],
            'client_identifier' => ['client_id', 'customer_id', 'client', 'customer', 'phone', 'mobile', 'username'],
            'amount' => ['amount', 'total', 'sum', 'bill_amount', 'payment_amount', 'price'],
            'bill_month' => ['bill_month', 'month', 'billing_month', 'period', 'invoice_month'],
            'payment_date' => ['payment_date', 'date', 'paid_date', 'transaction_date'],
            'payment_method' => ['payment_method', 'method', 'pay_method', 'type'],
            'transaction_id' => ['transaction_id', 'trx_id', 'ref', 'reference'],
            'bandwidth' => ['bandwidth', 'speed', 'mbps'],
            'price' => ['price', 'rate', 'amount', 'fee', 'cost'],
        ];
        
        // First pass: exact matches only (prioritize longer/more specific field names)
        foreach ($allFields as $targetField) {
            $aliases = $fieldAliases[$targetField] ?? [$targetField];
            
            foreach ($sourceHeaders as $header) {
                if (in_array($header, $usedHeaders)) continue;
                
                $normalizedHeader = strtolower(str_replace(['-', '_'], ' ', trim($header)));
                
                foreach ($aliases as $alias) {
                    $normalizedAlias = strtolower(str_replace(['-', '_'], ' ', $alias));
                    
                    // Exact match only
                    if ($normalizedHeader === $normalizedAlias) {
                        $mapping[$targetField] = $header;
                        $usedHeaders[] = $header;
                        break 2;
                    }
                }
            }
        }
        
        // Second pass: normalized match (remove all spaces/separators)
        foreach ($allFields as $targetField) {
            if (isset($mapping[$targetField])) continue;
            
            $aliases = $fieldAliases[$targetField] ?? [$targetField];
            
            foreach ($sourceHeaders as $header) {
                if (in_array($header, $usedHeaders)) continue;
                
                $normalizedHeader = strtolower(preg_replace('/[\s\-_]+/', '', trim($header)));
                
                foreach ($aliases as $alias) {
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

    public function validateData(ImportJob $job, array $rows, array $fieldMapping): array
    {
        $validRows = [];
        $invalidRows = [];
        $duplicateRows = [];
        $warnings = [];
        
        $dataType = $job->data_type;
        $requiredFields = $this->dataTypeFields[$dataType]['required'] ?? [];
        
        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;
            $mappedData = $this->applyFieldMapping($row, $fieldMapping);
            $errors = [];
            $isDuplicate = false;
            
            foreach ($requiredFields as $field) {
                if (empty($mappedData[$field])) {
                    $errors[] = "Missing required field: {$field}";
                }
            }
            
            if ($dataType === 'clients') {
                $isDuplicate = $this->checkClientDuplicate($mappedData);
            } elseif ($dataType === 'bills') {
                $isDuplicate = $this->checkBillDuplicate($mappedData);
            } elseif ($dataType === 'payments') {
                $isDuplicate = $this->checkPaymentDuplicate($mappedData);
            }
            
            $formatErrors = $this->validateFormats($dataType, $mappedData);
            $errors = array_merge($errors, $formatErrors);
            
            ImportLog::create([
                'import_job_id' => $job->id,
                'row_number' => $rowNumber,
                'original_data' => $row,
                'mapped_data' => $mappedData,
                'status' => $isDuplicate ? 'duplicate' : (empty($errors) ? 'valid' : 'invalid'),
                'validation_errors' => empty($errors) ? null : $errors,
            ]);
            
            if ($isDuplicate) {
                $duplicateRows[] = ['row' => $rowNumber, 'data' => $mappedData];
            } elseif (!empty($errors)) {
                $invalidRows[] = ['row' => $rowNumber, 'errors' => $errors, 'data' => $mappedData];
            } else {
                $validRows[] = ['row' => $rowNumber, 'data' => $mappedData];
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

    protected function applyFieldMapping(array $row, array $mapping): array
    {
        $mapped = [];
        foreach ($mapping as $targetField => $sourceField) {
            if (!empty($sourceField) && isset($row[$sourceField])) {
                $mapped[$targetField] = $row[$sourceField];
            }
        }
        return $mapped;
    }

    protected function checkClientDuplicate(array $data): bool
    {
        $query = Client::query();
        
        if (!empty($data['phone'])) {
            if (Client::where('phone', $data['phone'])->exists()) {
                return true;
            }
        }
        
        if (!empty($data['nid'])) {
            if (Client::where('nid', $data['nid'])->exists()) {
                return true;
            }
        }
        
        if (!empty($data['username'])) {
            if (Client::where('username', $data['username'])->exists()) {
                return true;
            }
        }
        
        return false;
    }

    protected function checkBillDuplicate(array $data): bool
    {
        if (empty($data['client_identifier']) || empty($data['bill_month'])) {
            return false;
        }
        
        $client = $this->findClient($data['client_identifier']);
        if (!$client) return false;
        
        return Bill::where('client_id', $client->id)
            ->where('bill_month', $data['bill_month'])
            ->exists();
    }

    protected function checkPaymentDuplicate(array $data): bool
    {
        if (!empty($data['transaction_id'])) {
            return Payment::where('transaction_id', $data['transaction_id'])->exists();
        }
        return false;
    }

    protected function validateFormats(string $dataType, array $data): array
    {
        $errors = [];
        
        if (isset($data['email']) && !empty($data['email'])) {
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Invalid email format';
            }
        }
        
        if (isset($data['amount']) && !is_numeric($data['amount'])) {
            $errors[] = 'Amount must be numeric';
        }
        
        if (isset($data['monthly_fee']) && !is_numeric($data['monthly_fee'])) {
            $errors[] = 'Monthly fee must be numeric';
        }
        
        $dateFields = ['connection_date', 'payment_date', 'due_date', 'generated_at', 'bill_month'];
        foreach ($dateFields as $field) {
            if (isset($data[$field]) && !empty($data[$field])) {
                if (!$this->isValidDate($data[$field])) {
                    $errors[] = "Invalid date format for {$field}";
                }
            }
        }
        
        return $errors;
    }

    protected function isValidDate($date): bool
    {
        if ($date instanceof \DateTime) return true;
        
        $formats = ['Y-m-d', 'Y-m', 'd/m/Y', 'm/d/Y', 'Y/m/d', 'd-m-Y'];
        foreach ($formats as $format) {
            $d = \DateTime::createFromFormat($format, $date);
            if ($d && $d->format($format) === $date) {
                return true;
            }
        }
        
        return strtotime($date) !== false;
    }

    public function processImport(ImportJob $job): array
    {
        $job->update(['status' => 'importing', 'started_at' => now()]);
        
        $logs = ImportLog::where('import_job_id', $job->id)
            ->where('status', 'valid')
            ->get();
        
        $imported = 0;
        $failed = 0;
        $skipped = 0;
        $errors = [];
        
        foreach ($logs as $log) {
            try {
                $entity = $this->importRow($job->data_type, $log->mapped_data);

                $log->update([
                    'status' => 'imported',
                    'entity_type' => get_class($entity),
                    'entity_id' => $entity->id,
                ]);

                $imported++;
            } catch (\Exception $e) {
                $log->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                ]);
                $failed++;
                $errors[] = "Row {$log->row_number}: " . $e->getMessage();
            }
        }
        
        $duplicateLogs = ImportLog::where('import_job_id', $job->id)->where('status', 'duplicate')->count();
        $invalidLogs = ImportLog::where('import_job_id', $job->id)->where('status', 'invalid')->count();
        $skipped = $duplicateLogs + $invalidLogs;
        
        $job->update([
            'status' => 'completed',
            'imported_rows' => $imported,
            'failed_rows' => $failed,
            'skipped_rows' => $skipped,
            'completed_at' => now(),
            'import_summary' => [
                'imported' => $imported,
                'failed' => $failed,
                'skipped' => $skipped,
                'errors' => array_slice($errors, 0, 50),
            ],
        ]);
        
        return [
            'imported' => $imported,
            'failed' => $failed,
            'skipped' => $skipped,
            'errors' => $errors,
        ];
    }

    protected function importRow(string $dataType, array $data)
    {
        return match($dataType) {
            'clients' => $this->importClient($data),
            'bills' => $this->importBill($data),
            'payments' => $this->importPayment($data),
            'zones' => $this->importZone($data),
            'areas' => $this->importArea($data),
            'packages' => $this->importPackage($data),
            default => throw new \Exception("Unsupported data type: {$dataType}"),
        };
    }

    protected function importClient(array $data): Client
    {
        $zoneId = null;
        $areaId = null;
        $packageId = null;
        $package = null;
        
        // Try to find zone by name first
        if (!empty($data['zone_name'])) {
            $zone = Zone::firstOrCreate(['name' => $data['zone_name']]);
            $zoneId = $zone->id;
            
            if (!empty($data['area_name'])) {
                $area = Area::firstOrCreate(
                    ['name' => $data['area_name'], 'zone_id' => $zoneId]
                );
                $areaId = $area->id;
            }
        }
        
        // Auto-detect zone/area from address if not provided
        if (empty($zoneId) && !empty($data['address'])) {
            $detectedZoneArea = $this->detectZoneAreaFromAddress($data['address']);
            if ($detectedZoneArea['zone_id']) {
                $zoneId = $detectedZoneArea['zone_id'];
                $areaId = $detectedZoneArea['area_id'];
            }
        }
        
        if (!empty($data['package_name'])) {
            $package = Package::where('name', $data['package_name'])->first();
            if (!$package) {
                $code = strtoupper(\Illuminate\Support\Str::slug($data['package_name'], '_'));
                $codeBase = $code;
                $counter = 1;
                while (Package::where('code', $code)->exists()) {
                    $code = $codeBase . '_' . $counter++;
                }
                $package = Package::create([
                    'name' => $data['package_name'],
                    'code' => $code,
                    'price' => $data['monthly_fee'] ?? 0,
                    'bandwidth' => $data['package_name'],
                    'download_speed' => 0,
                    'upload_speed' => 0,
                    'is_active' => true,
                ]);
            }
            $packageId = $package->id;
        }
        
        $clientData = [
            'name' => $data['name'],
            'phone' => $data['phone'],
            'username' => $data['username'] ?? $data['pppoe_username'] ?? $this->generateUsername($data['name']),
            'address' => $data['address'] ?? 'N/A',
            'status' => $data['status'] ?? 'active',
            'connection_status' => $data['connection_status'] ?? 'offline',
            'monthly_fee' => $data['monthly_fee'] ?? ($package ? $package->price : 0),
            'permanent_discount' => $data['permanent_discount'] ?? $data['discount'] ?? 0,
            'billing_date' => $data['billing_date'] ?? 1,
            'billing_cycle' => $data['billing_cycle'] ?? 'monthly',
            'connection_type' => $data['connection_type'] ?? 'pppoe',
            'connection_date' => $this->parseDate($data['connection_date'] ?? null) ?? now()->format('Y-m-d'),
            'installation_charge' => $data['installation_charge'] ?? 0,
            'cable_length_meters' => $data['cable_length_meters'] ?? 0,
            'zone_id' => $zoneId,
            'area_id' => $areaId,
            'package_id' => $packageId,
        ];

        $optionalFields = [
            'alt_phone', 'email', 'nid', 'father_husband_name', 'nearby_landmark',
            'pppoe_password', 'mac_address', 'ip_address',
            'onu_serial', 'onu_model', 'onu_port', 'olt_port',
            'google_map_location', 'google_map_lat', 'google_map_lng',
            'preferred_payment_method', 'technician_note',
        ];

        foreach ($optionalFields as $field) {
            if (!empty($data[$field])) {
                $clientData[$field] = $data[$field];
            }
        }

        if (!empty($data['pppoe_username'])) {
            $clientData['username'] = $data['pppoe_username'];
        }

        if (!empty($data['remarks'] ?? $data['notes'] ?? null)) {
            $clientData['remarks'] = $data['remarks'] ?? $data['notes'];
        }

        $dateOfBirth = $this->parseDate($data['date_of_birth'] ?? null);
        if ($dateOfBirth) {
            $clientData['date_of_birth'] = $dateOfBirth;
        }

        $expireDate = $this->parseDate($data['expire_date'] ?? null);
        if ($expireDate) {
            $clientData['expire_date'] = $expireDate;
        }

        $client = Client::create($clientData);
        
        // Set due_amount if provided
        if (!empty($data['due_amount']) && is_numeric($data['due_amount'])) {
            $client->update(['due_amount' => floatval($data['due_amount'])]);
        }
        
        // Create opening balance entry if balance provided
        if (!empty($data['balance']) && is_numeric($data['balance'])) {
            $balance = floatval($data['balance']);
            if ($balance != 0) {
                app(LedgerService::class)->createEntry(
                    $client->id,
                    $balance > 0 ? 'debit' : 'credit',
                    abs($balance),
                    'opening_balance',
                    'Imported opening balance',
                    null,
                    null,
                    auth()->id()
                );
                
                // Also set due_amount if not already set
                if (empty($data['due_amount']) && $balance > 0) {
                    $client->update(['due_amount' => $balance]);
                }
            }
        }
        
        return $client;
    }

    protected function importBill(array $data): Bill
    {
        $client = $this->findClient($data['client_identifier']);
        if (!$client) {
            throw new \Exception("Client not found: {$data['client_identifier']}");
        }
        
        $amount = floatval($data['amount']);
        $discount = floatval($data['discount'] ?? 0);
        $netAmount = isset($data['net_amount']) ? floatval($data['net_amount']) : ($amount - $discount);
        
        $bill = Bill::create([
            'client_id' => $client->id,
            'amount' => $amount,
            'discount' => $discount,
            'net_amount' => $netAmount,
            'bill_month' => $this->parseBillMonth($data['bill_month']),
            'status' => $data['status'] ?? 'unpaid',
            'due_date' => $this->parseDate($data['due_date'] ?? null),
            'notes' => $data['notes'] ?? null,
            'generated_at' => $this->parseDate($data['generated_at'] ?? null) ?? now(),
        ]);
        
        app(LedgerService::class)->createEntry(
            $client->id,
            'debit',
            $netAmount,
            'bill',
            "Imported bill for {$data['bill_month']}",
            $bill->id,
            null,
            auth()->id()
        );
        
        return $bill;
    }

    protected function importPayment(array $data): Payment
    {
        $client = $this->findClient($data['client_identifier']);
        if (!$client) {
            throw new \Exception("Client not found: {$data['client_identifier']}");
        }
        
        $amount = floatval($data['amount']);
        $billId = null;
        
        if (!empty($data['bill_month'])) {
            $bill = Bill::where('client_id', $client->id)
                ->where('bill_month', $this->parseBillMonth($data['bill_month']))
                ->first();
            $billId = $bill?->id;
        }
        
        $collectedBy = null;
        if (!empty($data['collected_by'])) {
            $employee = Employee::where('name', 'like', "%{$data['collected_by']}%")->first();
            $collectedBy = $employee?->id;
        }
        
        $payment = Payment::create([
            'client_id' => $client->id,
            'bill_id' => $billId,
            'amount' => $amount,
            'payment_method' => $data['payment_method'] ?? 'cash',
            'transaction_id' => $data['transaction_id'] ?? null,
            'payment_date' => $this->parseDate($data['payment_date']) ?? now(),
            'notes' => $data['notes'] ?? null,
            'collected_by' => $collectedBy,
            'status' => 'completed',
        ]);
        
        app(LedgerService::class)->createEntry(
            $client->id,
            'credit',
            $amount,
            'payment',
            "Imported payment",
            null,
            $payment->id,
            auth()->id()
        );
        
        if ($billId) {
            $this->updateBillStatus($billId);
        }
        
        return $payment;
    }

    protected function importZone(array $data): Zone
    {
        return Zone::firstOrCreate(
            ['name' => $data['name']],
            ['description' => $data['description'] ?? null, 'status' => $data['status'] ?? 'active']
        );
    }

    protected function importArea(array $data): Area
    {
        $zone = Zone::firstOrCreate(['name' => $data['zone_name']]);
        
        return Area::firstOrCreate(
            ['name' => $data['name'], 'zone_id' => $zone->id],
            ['description' => $data['description'] ?? null, 'status' => $data['status'] ?? 'active']
        );
    }

    protected function importPackage(array $data): Package
    {
        return Package::firstOrCreate(
            ['name' => $data['name']],
            [
                'price' => floatval($data['price']),
                'bandwidth' => $data['bandwidth'],
                'bandwidth_unit' => $data['bandwidth_unit'] ?? 'Mbps',
                'description' => $data['description'] ?? null,
                'status' => $data['status'] ?? 'active',
            ]
        );
    }

    protected function findClient(string $identifier): ?Client
    {
        return Client::where('id', $identifier)
            ->orWhere('phone', $identifier)
            ->orWhere('username', $identifier)
            ->orWhere('nid', $identifier)
            ->first();
    }

    protected function generateUsername(string $name): string
    {
        $base = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $name));
        $base = substr($base, 0, 10);
        $username = $base . rand(100, 999);
        
        while (Client::where('username', $username)->exists()) {
            $username = $base . rand(100, 999);
        }
        
        return $username;
    }

    protected function detectZoneAreaFromAddress(string $address): array
    {
        $result = ['zone_id' => null, 'area_id' => null];
        $addressLower = strtolower($address);
        
        // First try to match areas (more specific)
        $areas = Area::with('zone')->get();
        foreach ($areas as $area) {
            $areaNameLower = strtolower($area->name);
            if (strpos($addressLower, $areaNameLower) !== false) {
                $result['zone_id'] = $area->zone_id;
                $result['area_id'] = $area->id;
                return $result;
            }
        }
        
        // Then try to match zones
        $zones = Zone::all();
        foreach ($zones as $zone) {
            $zoneNameLower = strtolower($zone->name);
            if (strpos($addressLower, $zoneNameLower) !== false) {
                $result['zone_id'] = $zone->id;
                
                // Try to find a matching area within this zone
                $zoneAreas = Area::where('zone_id', $zone->id)->get();
                foreach ($zoneAreas as $area) {
                    if (strpos($addressLower, strtolower($area->name)) !== false) {
                        $result['area_id'] = $area->id;
                        break;
                    }
                }
                return $result;
            }
        }
        
        return $result;
    }

    protected function parseDate($date): ?string
    {
        if (empty($date)) return null;
        if ($date instanceof \DateTime) return $date->format('Y-m-d');
        
        $timestamp = strtotime($date);
        if ($timestamp === false) return null;
        
        return date('Y-m-d', $timestamp);
    }

    protected function parseBillMonth($month): string
    {
        if (empty($month)) return date('Y-m');
        if (preg_match('/^\d{4}-\d{2}$/', $month)) return $month;
        
        $timestamp = strtotime($month);
        if ($timestamp === false) return date('Y-m');
        
        return date('Y-m', $timestamp);
    }

    protected function updateBillStatus(int $billId): void
    {
        $bill = Bill::find($billId);
        if (!$bill) return;
        
        $totalPaid = Payment::where('bill_id', $billId)->sum('amount');
        
        if ($totalPaid >= $bill->net_amount) {
            $bill->update(['status' => 'paid']);
        } elseif ($totalPaid > 0) {
            $bill->update(['status' => 'partial']);
        }
    }
}
