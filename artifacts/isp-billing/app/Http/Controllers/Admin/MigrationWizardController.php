<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MigrationRun;
use App\Models\MigrationRouterSnapshot;
use App\Models\MikrotikDevice;
use App\Models\Client;
use App\Services\MigrationService;
use App\Services\ImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MigrationWizardController extends Controller
{
    protected MigrationService $migrationService;
    protected ImportService $importService;

    public function __construct(MigrationService $migrationService, ImportService $importService)
    {
        $this->migrationService = $migrationService;
        $this->importService = $importService;
    }

    public function index()
    {
        $runs = MigrationRun::orderBy('created_at', 'desc')->paginate(in_array((int)request()->per_page,[20,50,100,200])?(int)request()->per_page:10);
        return view('admin.migration.index', compact('runs'));
    }

    public function create()
    {
        return view('admin.migration.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $run = $this->migrationService->createRun($request->name, Auth::id());
        return redirect()->route('admin.migration.phase1', $run)->with('success', 'Migration started. Upload your client data file.');
    }

    public function show(MigrationRun $run)
    {
        return view('admin.migration.show', compact('run'));
    }

    public function phase1(MigrationRun $run)
    {
        if ($run->isPhaseComplete(1)) {
            return redirect()->route('admin.migration.phase2', $run)->with('info', 'Phase 1 already completed.');
        }
        return view('admin.migration.phase1-upload', compact('run'));
    }

    public function phase1Upload(Request $request, MigrationRun $run)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv,json|max:51200',
        ]);

        $file = $request->file('file');
        $extension = $file->getClientOriginalExtension();
        $fileName = Str::uuid() . '.' . $extension;
        $filePath = $file->storeAs('migrations', $fileName, 'local');

        session([
            "migration_{$run->id}_file" => $filePath,
            "migration_{$run->id}_file_type" => $extension,
            "migration_{$run->id}_original_name" => $file->getClientOriginalName(),
        ]);

        return redirect()->route('admin.migration.phase1-mapping', $run);
    }

    public function phase1Mapping(MigrationRun $run)
    {
        $filePath = session("migration_{$run->id}_file");
        $fileType = session("migration_{$run->id}_file_type");

        if (!$filePath) {
            return redirect()->route('admin.migration.phase1', $run)->with('error', 'Please upload a file first.');
        }

        $parsedData = $this->importService->parseFile($filePath, $fileType);
        $sourceHeaders = $parsedData['headers'];
        $sampleRows = array_slice($parsedData['rows'], 0, 5);
        $targetFields = $this->migrationService->getMigrationFields();
        $autoMapping = $this->migrationService->autoMapFields($sourceHeaders);

        return view('admin.migration.phase1-mapping', compact('run', 'sourceHeaders', 'sampleRows', 'targetFields', 'autoMapping'));
    }

    public function phase1SaveMapping(Request $request, MigrationRun $run)
    {
        $mapping = $request->input('mapping', []);
        $mapping = array_filter($mapping);

        if (empty($mapping['name']) || empty($mapping['phone'])) {
            return back()->with('error', 'Name and Phone fields must be mapped.');
        }

        session(["migration_{$run->id}_mapping" => $mapping]);

        return redirect()->route('admin.migration.phase1-preview', $run);
    }

    public function phase1Preview(MigrationRun $run)
    {
        $filePath = session("migration_{$run->id}_file");
        $fileType = session("migration_{$run->id}_file_type");
        $mapping = session("migration_{$run->id}_mapping");

        if (!$filePath || !$mapping) {
            return redirect()->route('admin.migration.phase1', $run)->with('error', 'Please upload and map fields first.');
        }

        $parsedData = $this->importService->parseFile($filePath, $fileType);
        $validation = $this->migrationService->validateMigrationData($parsedData['rows'], $mapping);

        session(["migration_{$run->id}_validation" => $validation]);

        return view('admin.migration.phase1-preview', compact('run', 'validation', 'mapping'));
    }

    public function phase1Confirm(Request $request, MigrationRun $run)
    {
        $filePath = session("migration_{$run->id}_file");
        $fileType = session("migration_{$run->id}_file_type");
        $mapping = session("migration_{$run->id}_mapping");

        if (!$filePath || !$mapping) {
            return redirect()->route('admin.migration.phase1', $run)->with('error', 'Session expired. Please re-upload.');
        }

        $parsedData = $this->importService->parseFile($filePath, $fileType);
        $validation = $this->migrationService->validateMigrationData($parsedData['rows'], $mapping);

        $includeDuplicates = $request->boolean('include_duplicates');
        $validRows = $validation['valid_rows'];
        if ($includeDuplicates) {
            $validRows = array_merge($validRows, $validation['duplicate_rows']);
        }

        try {
            $results = $this->migrationService->executeMigrationImport($run, $validRows, Auth::id());

            session()->forget([
                "migration_{$run->id}_file",
                "migration_{$run->id}_file_type",
                "migration_{$run->id}_original_name",
                "migration_{$run->id}_mapping",
                "migration_{$run->id}_validation",
            ]);

            return redirect()->route('admin.migration.phase2', $run)
                ->with('success', "Phase 1 complete! {$results['clients_created']} clients imported successfully.");

        } catch (\Exception $e) {
            return back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    public function phase2(MigrationRun $run)
    {
        if (!$run->canAccessPhase(2)) {
            return redirect()->route('admin.migration.phase1', $run)->with('error', 'Complete Phase 1 first.');
        }

        $devices = MikrotikDevice::where('is_active', true)->orderBy('name')->get();
        $snapshots = MigrationRouterSnapshot::where('migration_run_id', $run->id)->get();

        return view('admin.migration.phase2-discovery', compact('run', 'devices', 'snapshots'));
    }

    public function phase2Scan(Request $request, MigrationRun $run)
    {
        $request->validate([
            'device_ids' => 'required|array|min:1',
            'device_ids.*' => 'exists:mikrotik_devices,id',
        ]);

        try {
            $results = $this->migrationService->runDiscovery($run, $request->device_ids);
            return redirect()->route('admin.migration.phase2', $run)
                ->with('success', "Discovery complete! Found {$results['total_users_found']} users across {$results['routers_scanned']} routers.");
        } catch (\Exception $e) {
            return back()->with('error', 'Discovery failed: ' . $e->getMessage());
        }
    }

    public function phase2Skip(MigrationRun $run)
    {
        $run->update([
            'phase2_status' => 'completed',
            'phase2_summary' => ['skipped' => true, 'reason' => 'Admin chose to skip router discovery'],
            'current_phase' => 3,
        ]);
        return redirect()->route('admin.migration.phase3', $run)->with('info', 'Phase 2 skipped. Proceeding to reconciliation.');
    }

    public function phase3(MigrationRun $run)
    {
        if (!$run->canAccessPhase(3)) {
            return redirect()->route('admin.migration.phase2', $run)->with('error', 'Complete Phase 2 first.');
        }

        $snapshots = MigrationRouterSnapshot::where('migration_run_id', $run->id)
            ->orderByRaw("CASE match_status WHEN 'ghost_user' THEN 1 WHEN 'profile_mismatch' THEN 2 WHEN 'status_mismatch' THEN 3 WHEN 'unmatched' THEN 4 WHEN 'matched' THEN 5 WHEN 'synced' THEN 6 WHEN 'skipped' THEN 7 ELSE 8 END")
            ->paginate(in_array((int)request()->per_page,[20,50,100,200])?(int)request()->per_page:50);

        $summary = [
            'total' => MigrationRouterSnapshot::where('migration_run_id', $run->id)->count(),
            'matched' => MigrationRouterSnapshot::where('migration_run_id', $run->id)->where('match_status', 'matched')->count(),
            'ghost_users' => MigrationRouterSnapshot::where('migration_run_id', $run->id)->where('match_status', 'ghost_user')->count(),
            'profile_mismatch' => MigrationRouterSnapshot::where('migration_run_id', $run->id)->where('match_status', 'profile_mismatch')->count(),
            'status_mismatch' => MigrationRouterSnapshot::where('migration_run_id', $run->id)->where('match_status', 'status_mismatch')->count(),
            'synced' => MigrationRouterSnapshot::where('migration_run_id', $run->id)->where('match_status', 'synced')->count(),
            'skipped' => MigrationRouterSnapshot::where('migration_run_id', $run->id)->where('match_status', 'skipped')->count(),
            'unmatched' => MigrationRouterSnapshot::where('migration_run_id', $run->id)->where('match_status', 'unmatched')->count(),
        ];

        $billingOnlyClients = [];
        if ($run->isPhaseComplete(3)) {
            $routerUsernames = MigrationRouterSnapshot::where('migration_run_id', $run->id)
                ->pluck('username')->map(fn($u) => strtolower($u))->toArray();

            $billingOnlyClients = Client::where('status', '!=', 'deleted')
                ->whereNotNull('username')->where('username', '!=', '')
                ->get()
                ->filter(fn($c) => !in_array(strtolower($c->username), $routerUsernames))
                ->values();
        }

        return view('admin.migration.phase3-reconciliation', compact('run', 'snapshots', 'summary', 'billingOnlyClients'));
    }

    public function phase3Run(MigrationRun $run)
    {
        try {
            $results = $this->migrationService->runReconciliation($run);
            return redirect()->route('admin.migration.phase3', $run)
                ->with('success', "Reconciliation complete! Matched: {$results['matched']}, Ghost users: {$results['ghost_users']}, Profile mismatches: {$results['profile_mismatch']}");
        } catch (\Exception $e) {
            return back()->with('error', 'Reconciliation failed: ' . $e->getMessage());
        }
    }

    public function phase3Skip(MigrationRun $run)
    {
        $run->update([
            'phase3_status' => 'completed',
            'phase3_summary' => ['skipped' => true],
            'current_phase' => 4,
        ]);
        return redirect()->route('admin.migration.phase4', $run)->with('info', 'Phase 3 skipped.');
    }

    public function phase4(MigrationRun $run)
    {
        if (!$run->canAccessPhase(4)) {
            return redirect()->route('admin.migration.phase3', $run)->with('error', 'Complete Phase 3 first.');
        }

        $snapshots = MigrationRouterSnapshot::where('migration_run_id', $run->id)
            ->whereIn('match_status', ['matched', 'profile_mismatch', 'status_mismatch', 'ghost_user'])
            ->paginate(in_array((int)request()->per_page,[20,50,100,200])?(int)request()->per_page:50);

        $summary = [
            'actionable' => MigrationRouterSnapshot::where('migration_run_id', $run->id)
                ->whereIn('match_status', ['matched', 'profile_mismatch', 'status_mismatch', 'ghost_user'])->count(),
            'synced' => MigrationRouterSnapshot::where('migration_run_id', $run->id)->where('match_status', 'synced')->count(),
            'total' => MigrationRouterSnapshot::where('migration_run_id', $run->id)->count(),
        ];

        return view('admin.migration.phase4-sync', compact('run', 'snapshots', 'summary'));
    }

    public function phase4ApplyAction(Request $request, MigrationRun $run, int $snapshotId)
    {
        $request->validate(['action' => 'required|in:link,create_in_billing,update_profile,update_status,skip']);

        $result = $this->migrationService->applySyncAction($run, $snapshotId, $request->action);

        if ($result['success']) {
            return back()->with('success', $result['message']);
        }
        return back()->with('error', $result['message']);
    }

    public function phase4ApplyAll(MigrationRun $run)
    {
        try {
            $results = $this->migrationService->applyAllSyncActions($run);
            return redirect()->route('admin.migration.phase4', $run)
                ->with('success', "Sync complete! Applied: {$results['applied']}, Failed: {$results['failed']}");
        } catch (\Exception $e) {
            return back()->with('error', 'Sync failed: ' . $e->getMessage());
        }
    }

    public function phase4Skip(MigrationRun $run)
    {
        $run->update([
            'phase4_status' => 'completed',
            'phase4_summary' => ['skipped' => true],
            'current_phase' => 5,
        ]);
        return redirect()->route('admin.migration.phase5', $run)->with('info', 'Phase 4 skipped.');
    }

    public function phase5(MigrationRun $run)
    {
        if (!$run->canAccessPhase(5)) {
            return redirect()->route('admin.migration.phase4', $run)->with('error', 'Complete Phase 4 first.');
        }

        $report = $this->migrationService->getGoLiveReport($run);
        return view('admin.migration.phase5-golive', compact('run', 'report'));
    }

    public function phase5Complete(MigrationRun $run)
    {
        $this->migrationService->markGoLive($run);
        return redirect()->route('admin.migration.show', $run)
            ->with('success', 'Migration completed! Billing is now the master system.');
    }

    public function downloadSample(Request $request)
    {
        $format = $request->query('format', 'csv');

        $headers = [
            'name', 'phone', 'username', 'pppoe_password', 'email', 'address',
            'nid', 'package_name', 'monthly_fee', 'due_amount', 'advance_balance',
            'status', 'connection_type', 'zone_name', 'area_name', 'router_name',
            'connection_date', 'mac_address', 'ip_address', 'billing_date', 'remarks',
        ];

        $sampleRows = [
            ['Mohammad Rahim', '01712345678', 'rahim_pppoe', 'pass123', 'rahim@email.com', 'House 10, Road 5, Dhanmondi', '1990123456789', '10Mbps-Home', '500', '0', '300', 'active', 'pppoe', 'Dhanmondi', 'Road 5', 'MK-Router-01', '2024-01-15', 'AA:BB:CC:DD:EE:01', '192.168.1.100', '1', 'Regular customer'],
            ['Fatema Begum', '01898765432', 'fatema_net', 'abc456', 'fatema@email.com', 'Flat 3B, Mirpur DOHS', '1985987654321', '20Mbps-Premium', '800', '1600', '0', 'active', 'pppoe', 'Mirpur', 'Section 10', 'MK-Router-02', '2023-06-20', 'AA:BB:CC:DD:EE:02', '192.168.2.50', '1', 'Business client'],
            ['Abdul Karim', '01612345000', 'karim123', 'karim@99', '', 'Shop 12, Rajlaxmi Market, Uttara', '1988112233445', '5Mbps-Basic', '400', '800', '0', 'inactive', 'pppoe', 'Uttara', 'Sector 7', 'MK-Router-01', '2022-11-01', 'AA:BB:CC:DD:EE:03', '', '5', 'Due inactive'],
            ['Nusrat Jahan', '01512340000', 'nusrat_ppp', 'nj2024', 'nusrat@gmail.com', 'House 22, Gulshan 2', '1995556677889', '30Mbps-Business', '1500', '0', '1500', 'active', 'pppoe', 'Gulshan', 'Block A', 'MK-Router-03', '2024-03-10', 'AA:BB:CC:DD:EE:04', '10.0.0.25', '1', 'VIP client'],
            ['Hasan Ali', '01912340000', 'hasan_ali', 'hali55', '', 'Flat 5A, Green Tower, Dhanmondi', '1992334455667', '10Mbps-Home', '500', '500', '0', 'inactive', 'pppoe', 'Dhanmondi', 'Road 27', 'MK-Router-01', '2023-09-05', 'AA:BB:CC:DD:EE:05', '', '10', 'Left the area'],
        ];

        if ($format === 'xls' || $format === 'xlsx') {
            return $this->downloadXls($headers, $sampleRows);
        }

        return $this->downloadCsv($headers, $sampleRows);
    }

    protected function downloadCsv(array $headers, array $rows): StreamedResponse
    {
        $callback = function () use ($headers, $rows) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($file, $headers);
            foreach ($rows as $row) {
                fputcsv($file, $row);
            }
            fclose($file);
        };

        return response()->streamDownload($callback, 'migration_sample.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    protected function downloadXls(array $headers, array $rows): StreamedResponse
    {
        $callback = function () use ($headers, $rows) {
            $xmlHeader = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
            $xmlHeader .= '<?mso-application progid="Excel.Sheet"?>' . "\n";
            $xmlHeader .= '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"' . "\n";
            $xmlHeader .= ' xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">' . "\n";
            $xmlHeader .= '<Styles><Style ss:ID="header"><Font ss:Bold="1"/><Interior ss:Color="#4472C4" ss:Pattern="Solid"/><Font ss:Color="#FFFFFF" ss:Bold="1"/></Style>';
            $xmlHeader .= '<Style ss:ID="req"><Interior ss:Color="#FFF2CC" ss:Pattern="Solid"/></Style></Styles>' . "\n";
            $xmlHeader .= '<Worksheet ss:Name="Migration Sample">' . "\n";
            $xmlHeader .= '<Table>' . "\n";

            echo $xmlHeader;

            echo '<Row>';
            foreach ($headers as $header) {
                echo '<Cell ss:StyleID="header"><Data ss:Type="String">' . htmlspecialchars($header) . '</Data></Cell>';
            }
            echo '</Row>' . "\n";

            foreach ($rows as $row) {
                echo '<Row>';
                foreach ($row as $i => $cell) {
                    $type = is_numeric($cell) && $cell !== '' ? 'Number' : 'String';
                    $style = $i < 2 ? ' ss:StyleID="req"' : '';
                    echo '<Cell' . $style . '><Data ss:Type="' . $type . '">' . htmlspecialchars($cell) . '</Data></Cell>';
                }
                echo '</Row>' . "\n";
            }

            echo '</Table>' . "\n";
            echo '</Worksheet>' . "\n";
            echo '</Workbook>' . "\n";
        };

        return response()->streamDownload($callback, 'migration_sample.xls', [
            'Content-Type' => 'application/vnd.ms-excel',
        ]);
    }
}
