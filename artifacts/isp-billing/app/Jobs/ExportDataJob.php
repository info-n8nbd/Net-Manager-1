<?php

namespace App\Jobs;

use App\Models\ExportJob;
use App\Models\Client;
use App\Models\Bill;
use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use App\Services\TenantContext;

class ExportDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;
    public int $tries = 3;

    public function __construct(
        protected int $exportJobId
    ) {}

    public function handle(): void
    {
        TenantContext::setSuperAdminMode(true);
        $exportJob = ExportJob::find($this->exportJobId);
        
        if (!$exportJob) {
            Log::error("Export job not found: {$this->exportJobId}");
            return;
        }

        try {
            $exportJob->update([
                'status' => 'processing',
                'started_at' => now(),
            ]);

            $dataTypes = $exportJob->data_types ?? [];
            $exportType = is_array($dataTypes) ? ($dataTypes[0] ?? 'data') : $dataTypes;
            $filters = $exportJob->export_options ?? [];

            $data = $this->fetchData($exportType, $filters);
            $filePath = $this->generateFile($exportJob, $exportType, $data);

            $fullPath = Storage::disk('local')->path($filePath);
            $fileSize = file_exists($fullPath) ? filesize($fullPath) : 0;

            $exportJob->update([
                'status' => 'completed',
                'file_path' => $filePath,
                'file_name' => basename($filePath),
                'file_size' => $fileSize,
                'export_summary' => [$exportType => count($data)],
                'completed_at' => now(),
                'expires_at' => now()->addDays(7),
            ]);

        } catch (\Exception $e) {
            Log::error("Export job failed: " . $e->getMessage(), [
                'job_id' => $this->exportJobId,
                'trace' => $e->getTraceAsString(),
            ]);

            $exportJob->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);
        }
    }

    protected function fetchData(string $type, array $filters): array
    {
        return match ($type) {
            'clients' => $this->fetchClients($filters),
            'bills' => $this->fetchBills($filters),
            'payments' => $this->fetchPayments($filters),
            'due_clients' => $this->fetchDueClients($filters),
            'collection_report' => $this->fetchCollectionReport($filters),
            default => [],
        };
    }

    protected function fetchClients(array $filters): array
    {
        $query = Client::with(['zone', 'area', 'package']);

        if (!empty($filters['zone_id'])) {
            $query->where('zone_id', $filters['zone_id']);
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['package_id'])) {
            $query->where('package_id', $filters['package_id']);
        }

        return $query->get()->map(function ($client) {
            return [
                'Client ID' => $client->client_id,
                'Name' => $client->name,
                'Phone' => $client->phone,
                'Address' => $client->address,
                'Zone' => $client->zone?->name,
                'Area' => $client->area?->name,
                'Package' => $client->package?->name,
                'Monthly Fee' => $client->monthly_fee,
                'Due Amount' => $client->due_amount,
                'Status' => $client->status,
                'Connection Date' => $client->connection_date,
            ];
        })->toArray();
    }

    protected function fetchBills(array $filters): array
    {
        $query = Bill::with(['client.zone', 'client.package']);

        if (!empty($filters['month'])) {
            $query->where('bill_month', $filters['month']);
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['zone_id'])) {
            $query->whereHas('client', fn($q) => $q->where('zone_id', $filters['zone_id']));
        }

        return $query->get()->map(function ($bill) {
            return [
                'Bill No' => $bill->bill_no,
                'Client ID' => $bill->client?->client_id,
                'Client Name' => $bill->client?->name,
                'Zone' => $bill->client?->zone?->name,
                'Bill Month' => $bill->bill_month,
                'Amount' => $bill->total_amount,
                'Paid' => $bill->paid_amount,
                'Due' => $bill->due_amount,
                'Status' => $bill->status,
                'Created' => $bill->created_at->format('Y-m-d'),
            ];
        })->toArray();
    }

    protected function fetchPayments(array $filters): array
    {
        $query = Payment::with(['client.zone', 'collectedBy']);

        if (!empty($filters['from_date'])) {
            $query->where('payment_date', '>=', $filters['from_date']);
        }
        if (!empty($filters['to_date'])) {
            $query->where('payment_date', '<=', $filters['to_date']);
        }
        if (!empty($filters['zone_id'])) {
            $query->whereHas('client', fn($q) => $q->where('zone_id', $filters['zone_id']));
        }

        return $query->get()->map(function ($payment) {
            return [
                'Receipt No' => $payment->receipt_no,
                'Client ID' => $payment->client?->client_id,
                'Client Name' => $payment->client?->name,
                'Zone' => $payment->client?->zone?->name,
                'Amount' => $payment->amount,
                'Payment Date' => $payment->payment_date,
                'Method' => $payment->payment_method,
                'Collected By' => $payment->collectedBy?->name,
                'Status' => $payment->status,
            ];
        })->toArray();
    }

    protected function fetchDueClients(array $filters): array
    {
        $query = Client::with(['zone', 'package'])
            ->where('due_amount', '>', 0);

        if (!empty($filters['zone_id'])) {
            $query->where('zone_id', $filters['zone_id']);
        }
        if (!empty($filters['min_due'])) {
            $query->where('due_amount', '>=', $filters['min_due']);
        }

        return $query->orderByDesc('due_amount')->get()->map(function ($client) {
            return [
                'Client ID' => $client->client_id,
                'Name' => $client->name,
                'Phone' => $client->phone,
                'Zone' => $client->zone?->name,
                'Package' => $client->package?->name,
                'Monthly Fee' => $client->monthly_fee,
                'Due Amount' => $client->due_amount,
                'Status' => $client->status,
            ];
        })->toArray();
    }

    protected function fetchCollectionReport(array $filters): array
    {
        $query = Payment::with(['client.zone', 'collectedBy'])
            ->where('status', 'approved');

        if (!empty($filters['from_date'])) {
            $query->where('payment_date', '>=', $filters['from_date']);
        }
        if (!empty($filters['to_date'])) {
            $query->where('payment_date', '<=', $filters['to_date']);
        }

        return $query->get()
            ->groupBy(fn($p) => $p->collectedBy?->name ?? 'Direct')
            ->map(function ($payments, $collector) {
                return [
                    'Collector' => $collector,
                    'Total Collections' => $payments->count(),
                    'Total Amount' => $payments->sum('amount'),
                ];
            })->values()->toArray();
    }

    protected function generateFile(ExportJob $job, string $exportType, array $data): string
    {
        if (empty($data)) {
            throw new \Exception('No data to export');
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $headers = array_keys($data[0]);
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $sheet->getColumnDimension($col)->setAutoSize(true);
            $col++;
        }

        $row = 2;
        foreach ($data as $record) {
            $col = 'A';
            foreach ($record as $value) {
                $sheet->setCellValue($col . $row, $value);
                $col++;
            }
            $row++;
        }

        $lastCol = chr(ord('A') + count($headers) - 1);
        $sheet->getStyle("A1:{$lastCol}1")->getFont()->setBold(true);
        $sheet->getStyle("A1:{$lastCol}1")->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('E9ECEF');

        $format = $job->file_format ?? 'xlsx';
        $filename = $exportType . '_' . date('Ymd_His') . '.' . $format;
        $path = 'exports/' . $filename;

        Storage::disk('local')->makeDirectory('exports');

        $writer = $format === 'csv'
            ? new Csv($spreadsheet)
            : new Xlsx($spreadsheet);

        $tempPath = storage_path('app/' . $path);
        $dir = dirname($tempPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $writer->save($tempPath);

        return $path;
    }
}
