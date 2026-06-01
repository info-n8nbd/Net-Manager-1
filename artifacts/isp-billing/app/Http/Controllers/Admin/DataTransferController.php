<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ImportJob;
use App\Models\ImportLog;
use App\Models\ExportJob;
use App\Models\FieldMappingTemplate;
use App\Services\ImportService;
use App\Services\ExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DataTransferController extends Controller
{
    protected ImportService $importService;
    protected ExportService $exportService;

    public function __construct(ImportService $importService, ExportService $exportService)
    {
        $this->importService = $importService;
        $this->exportService = $exportService;
    }

    public function index()
    {
        $recentImports = ImportJob::with('user')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
        
        $recentExports = ExportJob::with('user')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
        
        $templates = FieldMappingTemplate::orderBy('usage_count', 'desc')->limit(5)->get();
        
        return view('admin.data-transfer.index', compact('recentImports', 'recentExports', 'templates'));
    }

    public function importIndex()
    {
        $imports = ImportJob::with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(in_array((int)request()->per_page,[20,50,100,200])?(int)request()->per_page:20);
        
        $dataTypes = $this->importService->getSupportedDataTypes();
        
        return view('admin.data-transfer.import.index', compact('imports', 'dataTypes'));
    }

    public function importCreate()
    {
        $dataTypes = $this->importService->getSupportedDataTypes();
        $templates = FieldMappingTemplate::orderBy('name')->get();
        
        return view('admin.data-transfer.import.create', compact('dataTypes', 'templates'));
    }

    public function importUpload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv,json,zip|max:51200',
            'data_type' => 'required|string',
            'name' => 'required|string|max:255',
            'source_software' => 'nullable|string|max:255',
        ]);
        
        $file = $request->file('file');
        $extension = $file->getClientOriginalExtension();
        $fileName = Str::uuid() . '.' . $extension;
        $filePath = $file->storeAs('imports', $fileName, 'local');
        
        $job = ImportJob::create([
            'name' => $request->name,
            'source_software' => $request->source_software,
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $filePath,
            'file_type' => $extension,
            'data_type' => $request->data_type,
            'status' => 'pending',
            'user_id' => auth()->id(),
        ]);
        
        return redirect()->route('admin.data-transfer.import.mapping', $job->id)
            ->with('success', 'File uploaded successfully. Please map the fields.');
    }

    public function importMapping(ImportJob $job)
    {
        $parsedData = $this->importService->parseFile($job->file_path, $job->file_type);
        $sourceHeaders = $parsedData['headers'];
        $sampleRows = array_slice($parsedData['rows'], 0, 5);
        
        $targetFields = $this->importService->getFieldsForDataType($job->data_type);
        $templates = FieldMappingTemplate::where('data_type', $job->data_type)->get();
        
        $totalRows = count($parsedData['rows']);
        $job->update(['total_rows' => $totalRows]);
        
        return view('admin.data-transfer.import.mapping', compact(
            'job', 'sourceHeaders', 'sampleRows', 'targetFields', 'templates', 'totalRows'
        ));
    }

    public function importSaveMapping(Request $request, ImportJob $job)
    {
        $request->validate([
            'mapping' => 'required|array',
            'save_template' => 'nullable|boolean',
            'template_name' => 'nullable|required_if:save_template,1|string|max:255',
        ]);
        
        $mapping = $request->mapping;
        
        $job->update([
            'field_mapping' => $mapping,
            'status' => 'validating',
        ]);
        
        if ($request->save_template && $request->template_name) {
            FieldMappingTemplate::create([
                'name' => $request->template_name,
                'source_software' => $job->source_software,
                'data_type' => $job->data_type,
                'mapping' => $mapping,
                'created_by' => auth()->id(),
            ]);
        }
        
        $parsedData = $this->importService->parseFile($job->file_path, $job->file_type);
        $validation = $this->importService->validateData($job, $parsedData['rows'], $mapping);
        
        $job->update([
            'status' => 'validated',
            'valid_rows' => $validation['valid'],
            'invalid_rows' => $validation['invalid'],
            'duplicate_rows' => $validation['duplicate'],
            'validation_summary' => $validation,
        ]);
        
        return redirect()->route('admin.data-transfer.import.preview', $job->id)
            ->with('success', 'Field mapping saved. Please review the validation results.');
    }

    public function importPreview(ImportJob $job)
    {
        $validLogs = ImportLog::where('import_job_id', $job->id)
            ->where('status', 'valid')
            ->limit(20)
            ->get();
        
        $invalidLogs = ImportLog::where('import_job_id', $job->id)
            ->where('status', 'invalid')
            ->limit(20)
            ->get();
        
        $duplicateLogs = ImportLog::where('import_job_id', $job->id)
            ->where('status', 'duplicate')
            ->limit(20)
            ->get();
        
        return view('admin.data-transfer.import.preview', compact('job', 'validLogs', 'invalidLogs', 'duplicateLogs'));
    }

    public function importConfirm(ImportJob $job)
    {
        if ($job->status !== 'validated') {
            return redirect()->route('admin.data-transfer.import.preview', $job->id)
                ->with('error', 'Import has not been validated yet.');
        }
        
        $result = $this->importService->processImport($job);
        
        return redirect()->route('admin.data-transfer.import.result', $job->id)
            ->with('success', "Import completed. {$result['imported']} records imported successfully.");
    }

    public function importResult(ImportJob $job)
    {
        $stats = [
            'total' => $job->total_rows,
            'imported' => $job->imported_rows,
            'failed' => $job->failed_rows,
            'skipped' => $job->skipped_rows,
        ];
        
        $failedLogs = ImportLog::where('import_job_id', $job->id)
            ->where('status', 'failed')
            ->limit(50)
            ->get();
        
        return view('admin.data-transfer.import.result', compact('job', 'stats', 'failedLogs'));
    }

    public function importCancel(ImportJob $job)
    {
        $job->update(['status' => 'cancelled']);
        
        if ($job->file_path && Storage::disk('local')->exists($job->file_path)) {
            Storage::disk('local')->delete($job->file_path);
        }
        
        return redirect()->route('admin.data-transfer.import.index')
            ->with('success', 'Import cancelled successfully.');
    }

    public function exportIndex()
    {
        $exports = ExportJob::with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(in_array((int)request()->per_page,[20,50,100,200])?(int)request()->per_page:20);
        
        $dataTypes = $this->exportService->getSupportedDataTypes();
        
        return view('admin.data-transfer.export.index', compact('exports', 'dataTypes'));
    }

    public function exportCreate()
    {
        $dataTypes = $this->exportService->getSupportedDataTypes();
        
        return view('admin.data-transfer.export.create', compact('dataTypes'));
    }

    public function exportProcess(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'data_types' => 'required|array|min:1',
            'file_format' => 'required|in:xlsx,csv,json,zip',
        ]);
        
        $job = ExportJob::create([
            'name' => $request->name,
            'data_types' => $request->data_types,
            'file_format' => $request->file_format,
            'status' => 'pending',
            'user_id' => auth()->id(),
            'export_options' => $request->options ?? [],
        ]);
        
        $result = $this->exportService->processExport($job);
        
        if ($result['success']) {
            return redirect()->route('admin.data-transfer.export.download', $job->id)
                ->with('success', 'Export completed successfully.');
        }
        
        return redirect()->route('admin.data-transfer.export.index')
            ->with('error', 'Export failed: ' . ($result['error'] ?? 'Unknown error'));
    }

    public function exportDownload(ExportJob $job)
    {
        if ($job->status !== 'completed' || !$job->file_path) {
            return redirect()->route('admin.data-transfer.export.index')
                ->with('error', 'Export file not available.');
        }
        
        if ($job->isExpired()) {
            return redirect()->route('admin.data-transfer.export.index')
                ->with('error', 'Export file has expired. Please create a new export.');
        }
        
        $fullPath = Storage::disk('local')->path($job->file_path);
        
        if (!file_exists($fullPath)) {
            return redirect()->route('admin.data-transfer.export.index')
                ->with('error', 'Export file not found.');
        }
        
        return response()->download($fullPath, $job->file_name);
    }

    public function templatesIndex()
    {
        $templates = FieldMappingTemplate::with('creator')
            ->orderBy('created_at', 'desc')
            ->paginate(in_array((int)request()->per_page,[20,50,100,200])?(int)request()->per_page:20);
        
        return view('admin.data-transfer.templates.index', compact('templates'));
    }

    public function templateDelete(FieldMappingTemplate $template)
    {
        $template->delete();
        
        return redirect()->route('admin.data-transfer.templates.index')
            ->with('success', 'Template deleted successfully.');
    }

    public function getTemplateMapping(FieldMappingTemplate $template)
    {
        return response()->json($template->mapping);
    }

    public function downloadSampleTemplate(string $dataType)
    {
        $templates = [
            'clients' => [
                'headers' => ['Name', 'Phone', 'Email', 'Address', 'Username', 'PPPoE Password', 'Zone Name', 'Area Name', 'Package Name', 'Monthly Fee', 'Connection Type', 'Connection Date', 'Billing Date', 'Status', 'NID', 'Due Amount', 'MAC Address', 'IP Address', 'ONU Serial', 'ONU Model', 'OLT Port', 'Connection Medium', 'Installation Charge', 'Cable Length (meters)', 'Remarks'],
                'sample' => [
                    ['Karim Ahmed', '01711111111', 'karim@email.com', 'House 10, Road 5, Mirpur-1, Dhaka', 'karim_ahmed', 'MyPass123', 'Zone A', 'Mirpur-1', 'Basic Home', 500, 'pppoe', '2026-01-15', 1, 'active', '1234567890123', 0, '', '', '', '', '', 'fiber', 0, 0, ''],
                    ['Fatema Begum', '01722222222', 'fatema@email.com', 'House 22, Road 3, Uttara-2, Dhaka', 'fatema_b', 'SecureP@ss', 'Zone B', 'Uttara-2', 'Standard Home', 800, 'pppoe', '2026-01-20', 5, 'active', '9876543210123', 100, 'AA:BB:CC:DD:EE:FF', '192.168.1.50', '', '', '', 'utp', 500, 100, 'New customer'],
                    ['Rahim Uddin', '01733333333', '', 'Flat 5B, Block C, Dhanmondi-3', 'rahim_u', 'Pass789!', 'Zone A', 'Dhanmondi-3', 'Premium Home', 1200, 'pppoe', '2026-02-01', 10, 'active', '3456789012345', 0, '', '10.0.0.25', 'ZTEG-C320-001', 'ZTE F660', '0/1/3', 'fiber', 1000, 250, 'ONU installed'],
                    ['Nasrin Akter', '01744444444', '', 'Village: Shilmara, Sylhet', 'nasrin_a', 'SylhetNet', 'Zone C', 'Sylhet-1', 'Business Basic', 2000, 'pppoe', '2025-12-01', 15, 'active', '', 500, '', '', '', '', '', 'utp', 0, 50, ''],
                    ['Mohammad Hasan', '01755555555', 'hasan@test.com', 'House 8, Road 12, Gulshan-1', 'hasan_m', 'GulPro99', 'Zone B', 'Gulshan-1', 'Business Pro', 5000, 'pppoe', '2026-01-10', 1, 'active', '5678901234567', 0, '11:22:33:44:55:66', '172.16.0.100', '', '', '', 'fiber', 2000, 500, 'Corporate client'],
                ],
                'field_guide' => [
                    ['Name', 'YES', 'Full name of the client', 'Text', 'Karim Ahmed'],
                    ['Phone', 'YES', 'Primary mobile number (11 digits)', 'Text', '01711111111'],
                    ['Email', 'No', 'Email address', 'Text', 'karim@email.com'],
                    ['Address', 'No', 'Full address of the client', 'Text', 'House 10, Road 5, Mirpur'],
                    ['Username', 'No', 'PPPoE/Login username - EXACTLY what you type will be used (no auto-modification)', 'Text', 'karim_ahmed'],
                    ['PPPoE Password', 'No', 'PPPoE login password', 'Text', 'MyPass123'],
                    ['Zone Name', 'No', 'Zone name (must match existing zone or will be created)', 'Text', 'Zone A'],
                    ['Area Name', 'No', 'Area/Subzone name (must match existing area or will be created)', 'Text', 'Mirpur-1'],
                    ['Package Name', 'No', 'Package name (must match existing package exactly)', 'Text', 'Basic Home'],
                    ['Monthly Fee', 'No', 'Monthly bill amount in BDT (auto-filled from package if empty)', 'Number', '500'],
                    ['Connection Type', 'No', 'pppoe, hotspot, static, or dhcp (default: pppoe)', 'Text', 'pppoe'],
                    ['Connection Date', 'No', 'Date when connection was activated (YYYY-MM-DD)', 'Date', '2026-01-15'],
                    ['Billing Date', 'No', 'Day of month for billing (1-28, default: 1)', 'Number', '1'],
                    ['Status', 'No', 'active, pending, inactive, or expired (default: active)', 'Text', 'active'],
                    ['NID', 'No', 'National ID number', 'Text', '1234567890123'],
                    ['Due Amount', 'No', 'Current outstanding due in BDT (default: 0)', 'Number', '0'],
                    ['MAC Address', 'No', 'Device MAC address (format: AA:BB:CC:DD:EE:FF)', 'Text', 'AA:BB:CC:DD:EE:FF'],
                    ['IP Address', 'No', 'Static IP address if assigned', 'Text', '192.168.1.50'],
                    ['ONU Serial', 'No', 'ONU device serial number', 'Text', 'ZTEG-C320-001'],
                    ['ONU Model', 'No', 'ONU device model', 'Text', 'ZTE F660'],
                    ['OLT Port', 'No', 'OLT port assignment', 'Text', '0/1/3'],
                    ['Connection Medium', 'No', 'utp or fiber (default: utp)', 'Text', 'fiber'],
                    ['Installation Charge', 'No', 'One-time installation fee in BDT (default: 0)', 'Number', '0'],
                    ['Cable Length (meters)', 'No', 'Cable length used in meters (default: 0)', 'Number', '100'],
                    ['Remarks', 'No', 'Any notes about the client', 'Text', 'New customer'],
                ],
            ],
            'bills' => [
                'headers' => ['Client ID', 'Amount', 'Bill Month', 'Discount', 'Net Amount', 'Status', 'Due Date', 'Generated At', 'Notes'],
                'sample' => [
                    ['CLT-000001', 500, '2026-01', 0, 500, 'unpaid', '2026-01-15', '2026-01-01', 'January bill'],
                    ['CLT-000002', 800, '2026-01', 50, 750, 'paid', '2026-01-15', '2026-01-01', ''],
                    ['CLT-000003', 1200, '2026-02', 0, 1200, 'unpaid', '2026-02-15', '2026-02-01', 'February bill'],
                    ['karim_ahmed', 500, '2026-02', 100, 400, 'partial', '2026-02-10', '2026-02-01', 'Discount applied'],
                    ['01711111111', 500, '2026-03', 0, 500, 'unpaid', '2026-03-15', '2026-03-01', 'March bill'],
                ],
                'field_guide' => [
                    ['Client ID', 'YES', 'Client identifier - can be Client ID (CLT-000001), Username, or Phone number', 'Text', 'CLT-000001 or karim_ahmed or 01711111111'],
                    ['Amount', 'YES', 'Bill amount in BDT before discount', 'Number', '500'],
                    ['Bill Month', 'YES', 'Billing month in YYYY-MM format', 'Text', '2026-01'],
                    ['Discount', 'No', 'Discount amount in BDT (default: 0)', 'Number', '0'],
                    ['Net Amount', 'No', 'Final amount after discount (auto-calculated if empty)', 'Number', '500'],
                    ['Status', 'No', 'unpaid, paid, partial, or overdue (default: unpaid)', 'Text', 'unpaid'],
                    ['Due Date', 'No', 'Payment due date (YYYY-MM-DD)', 'Date', '2026-01-15'],
                    ['Generated At', 'No', 'Date when bill was generated (YYYY-MM-DD)', 'Date', '2026-01-01'],
                    ['Notes', 'No', 'Any notes about the bill', 'Text', 'January bill'],
                ],
            ],
            'payments' => [
                'headers' => ['Client ID', 'Amount', 'Payment Date', 'Payment Method', 'Transaction ID', 'Bill Month', 'Collected By', 'Notes'],
                'sample' => [
                    ['CLT-000001', 500, '2026-01-10', 'cash', '', '2026-01', 'admin', 'January payment'],
                    ['CLT-000002', 800, '2026-01-12', 'bkash', 'TXN123456', '2026-01', '', 'Mobile payment'],
                    ['karim_ahmed', 500, '2026-02-05', 'bank', 'BNK-2026-001', '2026-02', '', 'Bank transfer'],
                    ['01722222222', 400, '2026-02-08', 'nagad', 'NGD789', '2026-02', '', ''],
                    ['CLT-000003', 1200, '2026-02-10', 'cash', '', '2026-02', 'collector1', 'Collected at door'],
                ],
                'field_guide' => [
                    ['Client ID', 'YES', 'Client identifier - can be Client ID (CLT-000001), Username, or Phone number', 'Text', 'CLT-000001 or karim_ahmed or 01711111111'],
                    ['Amount', 'YES', 'Payment amount in BDT', 'Number', '500'],
                    ['Payment Date', 'YES', 'Date of payment (YYYY-MM-DD)', 'Date', '2026-01-10'],
                    ['Payment Method', 'No', 'cash, bkash, nagad, rocket, bank, online, or check (default: cash)', 'Text', 'cash'],
                    ['Transaction ID', 'No', 'Transaction reference number for digital payments', 'Text', 'TXN123456'],
                    ['Bill Month', 'No', 'Which month this payment is for (YYYY-MM)', 'Text', '2026-01'],
                    ['Collected By', 'No', 'Username of person who collected the payment', 'Text', 'admin'],
                    ['Notes', 'No', 'Any notes about the payment', 'Text', 'January payment'],
                ],
            ],
            'zones' => [
                'headers' => ['Name', 'Description', 'Status'],
                'sample' => [
                    ['Zone A', 'North Dhaka Zone covering Mirpur, Dhanmondi, Mohammadpur areas', 'active'],
                    ['Zone B', 'East Dhaka Zone covering Uttara, Gulshan, Banani areas', 'active'],
                    ['Zone C', 'Sylhet Division Zone', 'active'],
                    ['Zone D', 'Chittagong Division Zone', 'inactive'],
                ],
                'field_guide' => [
                    ['Name', 'YES', 'Unique zone name', 'Text', 'Zone A'],
                    ['Description', 'No', 'Description of the zone coverage area', 'Text', 'North Dhaka Zone'],
                    ['Status', 'No', 'active or inactive (default: active)', 'Text', 'active'],
                ],
            ],
            'areas' => [
                'headers' => ['Name', 'Zone Name', 'Description', 'Status'],
                'sample' => [
                    ['Mirpur-1', 'Zone A', 'Mirpur Section 1 area', 'active'],
                    ['Dhanmondi-3', 'Zone A', 'Dhanmondi residential area', 'active'],
                    ['Uttara-2', 'Zone B', 'Uttara Sector 2', 'active'],
                    ['Gulshan-1', 'Zone B', 'Gulshan commercial area', 'active'],
                    ['Sylhet-1', 'Zone C', 'Sylhet city center', 'active'],
                ],
                'field_guide' => [
                    ['Name', 'YES', 'Unique area/subzone name', 'Text', 'Mirpur-1'],
                    ['Zone Name', 'YES', 'Parent zone name (must match existing zone)', 'Text', 'Zone A'],
                    ['Description', 'No', 'Description of the area', 'Text', 'Mirpur Section 1 area'],
                    ['Status', 'No', 'active or inactive (default: active)', 'Text', 'active'],
                ],
            ],
            'packages' => [
                'headers' => ['Name', 'Price', 'Bandwidth', 'Bandwidth Unit', 'Description', 'Status'],
                'sample' => [
                    ['Basic Home', 500, 5, 'Mbps', 'Basic home internet 5 Mbps', 'active'],
                    ['Standard Home', 800, 10, 'Mbps', 'Standard home internet 10 Mbps', 'active'],
                    ['Premium Home', 1200, 20, 'Mbps', 'Premium home internet 20 Mbps', 'active'],
                    ['Business Basic', 2000, 30, 'Mbps', 'Business internet 30 Mbps with static IP', 'active'],
                    ['Business Pro', 5000, 50, 'Mbps', 'Business pro internet 50 Mbps dedicated', 'active'],
                    ['Enterprise', 10000, 100, 'Mbps', 'Enterprise grade 100 Mbps dedicated line', 'active'],
                ],
                'field_guide' => [
                    ['Name', 'YES', 'Package name (MUST match MikroTik PPP profile name exactly)', 'Text', 'Basic Home'],
                    ['Price', 'YES', 'Monthly price in BDT', 'Number', '500'],
                    ['Bandwidth', 'YES', 'Download speed value', 'Number', '10'],
                    ['Bandwidth Unit', 'No', 'Mbps or Kbps (default: Mbps)', 'Text', 'Mbps'],
                    ['Description', 'No', 'Description of the package', 'Text', 'Basic home internet 5 Mbps'],
                    ['Status', 'No', 'active or inactive (default: active)', 'Text', 'active'],
                ],
            ],
        ];

        if (!isset($templates[$dataType])) {
            return redirect()->back()->with('error', 'Invalid data type for template.');
        }

        $template = $templates[$dataType];
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();

        $dataSheet = $spreadsheet->getActiveSheet();
        $dataSheet->setTitle(ucfirst($dataType) . ' Data');

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
            'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]],
        ];

        $requiredStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'C00000']],
            'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]],
        ];

        $requiredFields = $this->getImportService()->getFieldsForDataType($dataType);
        $requiredFieldNames = array_map('strtolower', $requiredFields['required'] ?? []);

        foreach ($template['headers'] as $i => $header) {
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1);
            $dataSheet->setCellValue($col . '1', $header);
            $headerKey = strtolower(str_replace(' ', '_', $header));
            $isRequired = in_array($headerKey, $requiredFieldNames) || in_array(strtolower($header), $requiredFieldNames);
            $dataSheet->getStyle($col . '1')->applyFromArray($isRequired ? $requiredStyle : $headerStyle);
            $dataSheet->getColumnDimension($col)->setAutoSize(true);
        }

        foreach ($template['sample'] as $ri => $row) {
            foreach ($row as $ci => $val) {
                $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($ci + 1);
                $dataSheet->setCellValue($col . ($ri + 2), $val);
            }
        }

        if (!empty($template['field_guide'])) {
            $guideSheet = $spreadsheet->createSheet();
            $guideSheet->setTitle('Field Guide');

            $guideHeaders = ['Column Name', 'Required?', 'Description', 'Data Type', 'Example'];
            foreach ($guideHeaders as $i => $h) {
                $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1);
                $guideSheet->setCellValue($col . '1', $h);
                $guideSheet->getStyle($col . '1')->applyFromArray($headerStyle);
                $guideSheet->getColumnDimension($col)->setAutoSize(true);
            }

            foreach ($template['field_guide'] as $ri => $row) {
                foreach ($row as $ci => $val) {
                    $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($ci + 1);
                    $guideSheet->setCellValue($col . ($ri + 2), $val);
                    if ($ci === 1 && strtoupper($val) === 'YES') {
                        $guideSheet->getStyle($col . ($ri + 2))->getFont()->setBold(true)->getColor()->setRGB('C00000');
                    }
                }
            }

            $noteRow = count($template['field_guide']) + 3;
            $guideSheet->setCellValue('A' . $noteRow, 'IMPORTANT NOTES:');
            $guideSheet->getStyle('A' . $noteRow)->getFont()->setBold(true)->setSize(12);
            $notes = $this->getImportNotes($dataType);
            foreach ($notes as $ni => $note) {
                $guideSheet->setCellValue('A' . ($noteRow + $ni + 1), $note);
            }
        }

        $spreadsheet->setActiveSheetIndex(0);

        $fileName = "{$dataType}_import_template.xlsx";
        $tempPath = storage_path("app/{$fileName}");
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($tempPath);

        return response()->download($tempPath, $fileName)->deleteFileAfterSend(true);
    }

    protected function getImportService(): \App\Services\ImportService
    {
        return $this->importService;
    }

    protected function getImportNotes(string $dataType): array
    {
        $common = [
            '- Supported file formats: Excel (.xlsx, .xls), CSV (.csv), JSON (.json), ZIP (.zip)',
            '- First row MUST be headers (column names)',
            '- Date format: YYYY-MM-DD (example: 2026-01-15)',
            '- Red header columns are REQUIRED fields - data will fail validation without them',
            '- Blue header columns are OPTIONAL - leave empty if not needed',
            '- Duplicate records are automatically detected and skipped',
            '- Maximum file size: 50MB',
        ];

        $specific = match($dataType) {
            'clients' => [
                '',
                'CLIENT-SPECIFIC NOTES:',
                '- Username: Whatever you type becomes the MikroTik PPPoE username - NO auto-modification',
                '- Zone Name: If zone does not exist, it will be created automatically',
                '- Area Name: If area does not exist under the zone, it will be created automatically',
                '- Package Name: MUST match an existing package name exactly',
                '- Connection Type: pppoe (default), hotspot, static, or dhcp',
                '- Connection Medium: utp (default) or fiber',
                '- Billing Date: Day of month (1-28) when monthly bill is generated',
                '- Status: active (default), pending, inactive, or expired',
                '- Client ID column: Can be used to find existing clients for updates',
                '- Phone number: Used for duplicate detection - same phone = duplicate',
            ],
            'bills' => [
                '',
                'BILL-SPECIFIC NOTES:',
                '- Client ID: Can use Client ID (CLT-000001), Username (karim_ahmed), or Phone (01711111111)',
                '- Bill Month: Format YYYY-MM (example: 2026-01 for January 2026)',
                '- Duplicate detection: Same client + same bill month = duplicate (skipped)',
                '- Net Amount: If empty, auto-calculated as Amount minus Discount',
            ],
            'payments' => [
                '',
                'PAYMENT-SPECIFIC NOTES:',
                '- Client ID: Can use Client ID (CLT-000001), Username (karim_ahmed), or Phone (01711111111)',
                '- Payment Method: cash, bkash, nagad, rocket, bank, online, or check',
                '- Bill Month: Links payment to a specific bill (format: YYYY-MM)',
                '- Transaction ID: Reference number for digital payments (bKash, Nagad, etc.)',
            ],
            'zones' => [
                '',
                'ZONE-SPECIFIC NOTES:',
                '- Zone name must be unique - duplicate zone names will be skipped',
                '- Import zones BEFORE importing areas or clients that reference them',
            ],
            'areas' => [
                '',
                'AREA-SPECIFIC NOTES:',
                '- Zone Name: Parent zone MUST exist before importing areas',
                '- Import order: Zones first, then Areas, then Clients',
                '- Area name must be unique within its zone',
            ],
            'packages' => [
                '',
                'PACKAGE-SPECIFIC NOTES:',
                '- Package Name MUST match MikroTik PPP profile/queue name exactly for auto-provisioning',
                '- Import packages BEFORE importing clients that reference them',
                '- Price is in BDT (Bangladeshi Taka)',
                '- Bandwidth value without unit (unit goes in separate column)',
            ],
            default => [],
        };

        return array_merge($common, $specific);
    }

    public function importGuide()
    {
        return view('admin.data-transfer.import.guide');
    }

    public function downloadImportReport(ImportJob $job)
    {
        $logs = ImportLog::where('import_job_id', $job->id)->get();
        
        $report = "Import Report\n";
        $report .= "==============\n\n";
        $report .= "Job: {$job->name}\n";
        $report .= "Date: {$job->created_at}\n";
        $report .= "Source: {$job->source_software}\n";
        $report .= "File: {$job->file_name}\n";
        $report .= "Status: {$job->status}\n\n";
        $report .= "Summary:\n";
        $report .= "- Total Rows: {$job->total_rows}\n";
        $report .= "- Imported: {$job->imported_rows}\n";
        $report .= "- Failed: {$job->failed_rows}\n";
        $report .= "- Skipped: {$job->skipped_rows}\n\n";
        
        $failedLogs = $logs->where('status', 'failed');
        if ($failedLogs->count() > 0) {
            $report .= "Failed Rows:\n";
            foreach ($failedLogs as $log) {
                $report .= "- Row {$log->row_number}: {$log->error_message}\n";
            }
        }
        
        return response($report)
            ->header('Content-Type', 'text/plain')
            ->header('Content-Disposition', 'attachment; filename="import_report_' . $job->id . '.txt"');
    }

    public function quickIndex()
    {
        $dataTypes = $this->importService->getSupportedDataTypes();
        
        $stats = [
            'clients' => \App\Models\Client::count(),
            'bills' => \App\Models\Bill::count(),
            'payments' => \App\Models\Payment::count(),
            'zones' => \App\Models\Zone::count(),
            'packages' => \App\Models\Package::count(),
        ];
        
        return view('admin.data-transfer.quick', compact('dataTypes', 'stats'));
    }

    public function quickImport(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv,json|max:51200',
            'data_type' => 'required|string',
        ]);
        
        $file = $request->file('file');
        $extension = $file->getClientOriginalExtension();
        $fileName = Str::uuid() . '.' . $extension;
        $filePath = $file->storeAs('imports', $fileName, 'local');
        
        $job = ImportJob::create([
            'name' => 'Quick Import - ' . ucfirst($request->data_type) . ' - ' . now()->format('Y-m-d H:i'),
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $filePath,
            'file_type' => $extension,
            'data_type' => $request->data_type,
            'status' => 'pending',
            'user_id' => auth()->id(),
        ]);
        
        try {
            $parsedData = $this->importService->parseFile($filePath, $extension);
            $sourceHeaders = $parsedData['headers'];
            $targetFields = $this->importService->getFieldsForDataType($request->data_type);
            
            $autoMapping = $this->importService->autoMapFields($sourceHeaders, $targetFields);
            
            $job->update(['field_mapping' => $autoMapping]);
            
            $validationResult = $this->importService->validateData($job, $parsedData['rows'], $autoMapping);
            
            $job->update([
                'total_rows' => $validationResult['total'],
                'valid_rows' => $validationResult['valid'],
                'invalid_rows' => $validationResult['invalid'],
                'duplicate_rows' => $validationResult['duplicate'] ?? 0,
                'status' => 'validated',
            ]);
            
            $options = [
                'skip_duplicates' => $request->has('skip_duplicates'),
                'create_missing' => $request->has('create_missing'),
            ];
            
            $result = $this->importService->processImport($job);
            
            $job->update([
                'imported_rows' => $result['imported'],
                'failed_rows' => $result['failed'],
                'skipped_rows' => $result['skipped'],
                'status' => 'completed',
                'completed_at' => now(),
            ]);
            
            return redirect()->route('admin.data-transfer.quick')
                ->with('success', "Import completed! {$result['imported']} records imported, {$result['skipped']} skipped, {$result['failed']} failed.");
                
        } catch (\Exception $e) {
            $job->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
            
            return redirect()->route('admin.data-transfer.quick')
                ->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    public function quickExport(string $format)
    {
        $validFormats = ['xlsx', 'csv', 'json', 'zip'];
        if (!in_array($format, $validFormats)) {
            return redirect()->route('admin.data-transfer.quick')
                ->with('error', 'Invalid export format.');
        }
        
        $allDataTypes = ['clients', 'bills', 'payments', 'zones', 'subzones', 'packages'];
        
        $job = ExportJob::create([
            'name' => 'Quick Export - ' . strtoupper($format) . ' - ' . now()->format('Y-m-d H:i'),
            'data_types' => $allDataTypes,
            'file_format' => $format,
            'status' => 'processing',
            'user_id' => auth()->id(),
            'expires_at' => now()->addDays(7),
        ]);
        
        try {
            $result = $this->exportService->generateExport($job);
            
            $job->update([
                'file_path' => $result['file_path'],
                'file_size' => $result['file_size'],
                'status' => 'completed',
                'completed_at' => now(),
            ]);
            
            return response()->download(
                storage_path('app/' . $result['file_path']),
                $job->name . '.' . $format
            );
            
        } catch (\Exception $e) {
            $job->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
            
            return redirect()->route('admin.data-transfer.quick')
                ->with('error', 'Export failed: ' . $e->getMessage());
        }
    }
}
