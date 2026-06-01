<?php

namespace App\Services;

use App\Models\ExportJob;
use App\Models\Client;
use App\Models\Bill;
use App\Models\Payment;
use App\Models\Zone;
use App\Models\Area;
use App\Models\Package;
use App\Models\Employee;
use App\Models\User;
use App\Models\Ticket;
use App\Models\LedgerEntry;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use ZipArchive;

class ExportService
{
    protected array $dataTypeExporters = [
        'clients' => 'exportClients',
        'bills' => 'exportBills',
        'payments' => 'exportPayments',
        'zones' => 'exportZones',
        'areas' => 'exportAreas',
        'packages' => 'exportPackages',
        'employees' => 'exportEmployees',
        'tickets' => 'exportTickets',
        'ledger' => 'exportLedger',
    ];

    public function getSupportedDataTypes(): array
    {
        return array_keys($this->dataTypeExporters);
    }

    public function processExport(ExportJob $job): array
    {
        $job->update(['status' => 'processing', 'started_at' => now()]);
        
        try {
            $data = [];
            $summary = [];
            
            foreach ($job->data_types as $dataType) {
                if (isset($this->dataTypeExporters[$dataType])) {
                    $method = $this->dataTypeExporters[$dataType];
                    $exportData = $this->$method();
                    $data[$dataType] = $exportData;
                    $summary[$dataType] = count($exportData);
                }
            }
            
            $filePath = match($job->file_format) {
                'xlsx' => $this->generateExcel($data, $job),
                'csv' => $this->generateCsv($data, $job),
                'json' => $this->generateJson($data, $job),
                'zip' => $this->generateZip($data, $job),
                default => throw new \Exception("Unsupported format: {$job->file_format}"),
            };
            
            $fullPath = Storage::disk('local')->path($filePath);
            $fileSize = file_exists($fullPath) ? filesize($fullPath) : 0;
            
            $job->update([
                'status' => 'completed',
                'file_path' => $filePath,
                'file_name' => basename($filePath),
                'file_size' => $fileSize,
                'completed_at' => now(),
                'expires_at' => now()->addDays(7),
                'export_summary' => $summary,
            ]);
            
            return ['success' => true, 'file_path' => $filePath, 'summary' => $summary];
            
        } catch (\Exception $e) {
            $job->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);
            
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    protected function exportClients(): array
    {
        return Client::with(['zone', 'area', 'package'])->get()->map(function($client) {
            $balance = app(LedgerService::class)->getClientBalance($client->id);
            
            return [
                'id' => $client->id,
                'client_id' => $client->client_id,
                'name' => $client->name,
                'phone' => $client->phone,
                'alt_phone' => $client->alt_phone,
                'email' => $client->email,
                'username' => $client->username,
                'address' => $client->address,
                'nid' => $client->nid,
                'father_husband_name' => $client->father_husband_name,
                'date_of_birth' => $client->date_of_birth,
                'nearby_landmark' => $client->nearby_landmark,
                'zone_name' => $client->zone?->name,
                'area_name' => $client->area?->name,
                'package_name' => $client->package?->name,
                'connection_date' => $client->connection_date,
                'expire_date' => $client->expire_date,
                'status' => $client->status,
                'connection_status' => $client->connection_status,
                'balance' => $balance,
                'due_amount' => $client->due_amount,
                'monthly_fee' => $client->monthly_fee,
                'permanent_discount' => $client->permanent_discount,
                'billing_date' => $client->billing_date,
                'billing_cycle' => $client->billing_cycle,
                'connection_type' => $client->connection_type,
                'pppoe_username' => $client->pppoe_username,
                'pppoe_password' => $client->pppoe_password,
                'mac_address' => $client->mac_address,
                'ip_address' => $client->ip_address,
                'onu_serial' => $client->onu_serial,
                'onu_model' => $client->onu_model,
                'onu_port' => $client->onu_port,
                'olt_port' => $client->olt_port,
                'google_map_location' => $client->google_map_location,
                'google_map_lat' => $client->google_map_lat,
                'google_map_lng' => $client->google_map_lng,
                'installation_charge' => $client->installation_charge,
                'cable_length_meters' => $client->cable_length_meters,
                'preferred_payment_method' => $client->preferred_payment_method,
                'remarks' => $client->remarks,
                'technician_note' => $client->technician_note,
                'created_at' => $client->created_at?->format('Y-m-d H:i:s'),
            ];
        })->toArray();
    }

    protected function exportBills(): array
    {
        return Bill::with(['client'])->get()->map(function($bill) {
            return [
                'id' => $bill->id,
                'client_id' => $bill->client_id,
                'client_name' => $bill->client?->name,
                'client_phone' => $bill->client?->phone,
                'amount' => $bill->amount,
                'discount' => $bill->discount,
                'net_amount' => $bill->net_amount,
                'bill_month' => $bill->bill_month,
                'status' => $bill->status,
                'due_date' => $bill->due_date,
                'notes' => $bill->notes,
                'generated_at' => $bill->generated_at,
                'created_at' => $bill->created_at?->format('Y-m-d H:i:s'),
            ];
        })->toArray();
    }

    protected function exportPayments(): array
    {
        return Payment::with(['client', 'bill', 'collectedBy', 'paymentMethod'])->get()->map(function($payment) {
            return [
                'id' => $payment->id,
                'client_id' => $payment->client_id,
                'client_name' => $payment->client?->name,
                'client_phone' => $payment->client?->phone,
                'bill_id' => $payment->bill_id,
                'bill_month' => $payment->bill?->bill_month,
                'amount' => $payment->amount,
                'payment_method' => $payment->paymentMethod?->name ?? $payment->payment_method,
                'transaction_id' => $payment->transaction_id,
                'payment_date' => $payment->payment_date,
                'collected_by' => $payment->collectedBy?->name,
                'notes' => $payment->notes,
                'status' => $payment->status ?? 'completed',
                'created_at' => $payment->created_at?->format('Y-m-d H:i:s'),
            ];
        })->toArray();
    }

    protected function exportZones(): array
    {
        return Zone::all()->map(function($zone) {
            return [
                'id' => $zone->id,
                'name' => $zone->name,
                'description' => $zone->description,
                'status' => $zone->status,
                'created_at' => $zone->created_at?->format('Y-m-d H:i:s'),
            ];
        })->toArray();
    }

    protected function exportAreas(): array
    {
        return Area::with('zone')->get()->map(function($area) {
            return [
                'id' => $area->id,
                'name' => $area->name,
                'zone_id' => $area->zone_id,
                'zone_name' => $area->zone?->name,
                'description' => $area->description ?? '',
                'status' => $area->status ?? 'active',
                'created_at' => $area->created_at?->format('Y-m-d H:i:s'),
            ];
        })->toArray();
    }

    protected function exportPackages(): array
    {
        return Package::all()->map(function($package) {
            return [
                'id' => $package->id,
                'name' => $package->name,
                'price' => $package->price,
                'bandwidth' => $package->bandwidth,
                'bandwidth_unit' => $package->bandwidth_unit ?? 'Mbps',
                'description' => $package->description,
                'status' => $package->status,
                'created_at' => $package->created_at?->format('Y-m-d H:i:s'),
            ];
        })->toArray();
    }

    protected function exportEmployees(): array
    {
        return Employee::with(['user', 'department', 'designation'])->get()->map(function($employee) {
            return [
                'id' => $employee->id,
                'name' => $employee->name,
                'email' => $employee->email,
                'phone' => $employee->phone,
                'role' => $employee->user?->getRoleNames()->first() ?? '',
                'department' => $employee->department?->name ?? '',
                'designation' => $employee->designation?->name ?? '',
                'status' => $employee->status ?? 'active',
                'created_at' => $employee->created_at?->format('Y-m-d H:i:s'),
            ];
        })->toArray();
    }

    protected function exportTickets(): array
    {
        return Ticket::with(['client', 'assignedTo'])->get()->map(function($ticket) {
            return [
                'id' => $ticket->id,
                'client_id' => $ticket->client_id,
                'client_name' => $ticket->client?->name,
                'subject' => $ticket->subject,
                'description' => $ticket->description,
                'priority' => $ticket->priority,
                'status' => $ticket->status,
                'assigned_to' => $ticket->assignedTo?->name,
                'resolved_at' => $ticket->resolved_at,
                'created_at' => $ticket->created_at?->format('Y-m-d H:i:s'),
            ];
        })->toArray();
    }

    protected function exportLedger(): array
    {
        return LedgerEntry::with('client')->get()->map(function($entry) {
            return [
                'id' => $entry->id,
                'client_id' => $entry->client_id,
                'client_name' => $entry->client?->name,
                'type' => $entry->type,
                'amount' => $entry->amount,
                'entry_type' => $entry->entry_type,
                'description' => $entry->description,
                'bill_id' => $entry->bill_id,
                'payment_id' => $entry->payment_id,
                'created_at' => $entry->created_at?->format('Y-m-d H:i:s'),
            ];
        })->toArray();
    }

    protected function generateExcel(array $data, ExportJob $job): string
    {
        $spreadsheet = new Spreadsheet();
        $sheetIndex = 0;
        
        foreach ($data as $dataType => $rows) {
            if ($sheetIndex > 0) {
                $spreadsheet->createSheet();
            }
            $spreadsheet->setActiveSheetIndex($sheetIndex);
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle(ucfirst($dataType));
            
            if (!empty($rows)) {
                $headers = array_keys($rows[0]);
                $col = 'A';
                foreach ($headers as $header) {
                    $sheet->setCellValue($col . '1', ucfirst(str_replace('_', ' ', $header)));
                    $sheet->getStyle($col . '1')->getFont()->setBold(true);
                    $col++;
                }
                
                $rowNum = 2;
                foreach ($rows as $row) {
                    $col = 'A';
                    foreach ($row as $value) {
                        $sheet->setCellValue($col . $rowNum, $value);
                        $col++;
                    }
                    $rowNum++;
                }
            }
            
            $sheetIndex++;
        }
        
        $spreadsheet->setActiveSheetIndex(0);
        
        $fileName = 'export_' . $job->id . '_' . date('Y-m-d_His') . '.xlsx';
        $filePath = 'exports/' . $fileName;
        
        Storage::disk('local')->makeDirectory('exports');
        $fullPath = Storage::disk('local')->path($filePath);
        
        $writer = new Xlsx($spreadsheet);
        $writer->save($fullPath);
        
        return $filePath;
    }

    protected function generateCsv(array $data, ExportJob $job): string
    {
        $mainType = array_key_first($data);
        $rows = $data[$mainType] ?? [];
        
        $fileName = 'export_' . $job->id . '_' . $mainType . '_' . date('Y-m-d_His') . '.csv';
        $filePath = 'exports/' . $fileName;
        
        Storage::disk('local')->makeDirectory('exports');
        $fullPath = Storage::disk('local')->path($filePath);
        
        $handle = fopen($fullPath, 'w');
        
        if (!empty($rows)) {
            fputcsv($handle, array_keys($rows[0]));
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
        }
        
        fclose($handle);
        
        return $filePath;
    }

    protected function generateJson(array $data, ExportJob $job): string
    {
        $exportData = [
            'metadata' => [
                'exported_at' => now()->toIso8601String(),
                'exported_by' => auth()->user()?->email ?? 'system',
                'system' => 'SOFTYNEX Billing',
                'version' => '1.0',
            ],
            'data' => $data,
        ];
        
        $fileName = 'export_' . $job->id . '_' . date('Y-m-d_His') . '.json';
        $filePath = 'exports/' . $fileName;
        
        Storage::disk('local')->makeDirectory('exports');
        Storage::disk('local')->put($filePath, json_encode($exportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        return $filePath;
    }

    protected function generateZip(array $data, ExportJob $job): string
    {
        $tempDir = 'exports/temp_' . $job->id;
        Storage::disk('local')->makeDirectory($tempDir);
        
        $jsonPath = $tempDir . '/data.json';
        $exportData = [
            'metadata' => [
                'exported_at' => now()->toIso8601String(),
                'exported_by' => auth()->user()?->email ?? 'system',
                'system' => 'SOFTYNEX Billing',
                'version' => '1.0',
            ],
            'data' => $data,
        ];
        Storage::disk('local')->put($jsonPath, json_encode($exportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        foreach ($data as $dataType => $rows) {
            $csvPath = $tempDir . '/' . $dataType . '.csv';
            $fullCsvPath = Storage::disk('local')->path($csvPath);
            
            $handle = fopen($fullCsvPath, 'w');
            if (!empty($rows)) {
                fputcsv($handle, array_keys($rows[0]));
                foreach ($rows as $row) {
                    fputcsv($handle, $row);
                }
            }
            fclose($handle);
        }
        
        $fileName = 'export_' . $job->id . '_' . date('Y-m-d_His') . '.zip';
        $zipPath = 'exports/' . $fileName;
        $fullZipPath = Storage::disk('local')->path($zipPath);
        
        $zip = new ZipArchive();
        if ($zip->open($fullZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \Exception('Failed to create ZIP file');
        }
        
        $tempFullPath = Storage::disk('local')->path($tempDir);
        $files = glob($tempFullPath . '/*');
        foreach ($files as $file) {
            $zip->addFile($file, basename($file));
        }
        
        $zip->close();
        
        Storage::disk('local')->deleteDirectory($tempDir);
        
        return $zipPath;
    }
}
