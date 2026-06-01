<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\BtrcReportService;
use App\Exports\BtrcReportExport;
use App\Models\Zone;
use App\Models\District;
use App\Models\Upazila;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

class BtrcReportController extends Controller
{
    protected BtrcReportService $reportService;

    public function __construct(BtrcReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    protected function getCompanyInfo(): array
    {
        $company = \App\Models\Company::find(auth()->user()->company_id);
        return [
            'name' => $company->name ?? 'SOFTYNEX',
            'address' => $company->address ?? 'Dhaka, Bangladesh',
            'license' => $company->btrc_license ?? '',
            'phone' => $company->phone ?? '',
        ];
    }

    public function index()
    {
        $statistics = $this->reportService->getSubscriberStatistics();
        $zones = Zone::where('is_active', true)->get();
        
        return view('admin.reports.btrc.index', compact('statistics', 'zones'));
    }

    public function subscriberList(Request $request)
    {
        $filters = $request->only(['zone_id', 'from_date', 'to_date']);
        $data = $this->reportService->getSubscriberListReport($filters);
        $zones = Zone::where('is_active', true)->get();

        if ($request->has('export')) {
            $filename = 'btrc_subscriber_list_' . date('Y-m-d');
            
            if ($request->export === 'excel') {
                $export = new BtrcReportExport($data, 'subscriber_list', 'BTRC Subscriber List Report', $this->getCompanyInfo());
                return Excel::download($export, $filename . '.xlsx');
            }

            $csv = $this->reportService->exportToCsv($data, 'subscriber_list');
            return Response::make($csv, 200, [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '.csv"',
            ]);
        }

        return view('admin.reports.btrc.subscriber-list', compact('data', 'zones', 'filters'));
    }

    public function newConnections(Request $request)
    {
        $fromDate = $request->from_date ?? Carbon::now()->startOfMonth()->format('Y-m-d');
        $toDate = $request->to_date ?? Carbon::now()->format('Y-m-d');
        
        $data = $this->reportService->getNewConnectionReport($fromDate, $toDate);

        if ($request->has('export')) {
            $filename = 'btrc_new_connections_' . date('Y-m-d');

            if ($request->export === 'excel') {
                $export = new BtrcReportExport($data, 'new_connections', "BTRC New Connection Report ({$fromDate} to {$toDate})", $this->getCompanyInfo());
                return Excel::download($export, $filename . '.xlsx');
            }

            $csv = $this->reportService->exportToCsv($data, 'new_connections');
            return Response::make($csv, 200, [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '.csv"',
            ]);
        }

        return view('admin.reports.btrc.new-connections', compact('data', 'fromDate', 'toDate'));
    }

    public function disconnections(Request $request)
    {
        $fromDate = $request->from_date ?? Carbon::now()->startOfMonth()->format('Y-m-d');
        $toDate = $request->to_date ?? Carbon::now()->format('Y-m-d');
        
        $data = $this->reportService->getDisconnectionReport($fromDate, $toDate);

        if ($request->has('export')) {
            $filename = 'btrc_disconnections_' . date('Y-m-d');

            if ($request->export === 'excel') {
                $export = new BtrcReportExport($data, 'disconnections', "BTRC Disconnection Report ({$fromDate} to {$toDate})", $this->getCompanyInfo());
                return Excel::download($export, $filename . '.xlsx');
            }

            $csv = $this->reportService->exportToCsv($data, 'disconnections');
            return Response::make($csv, 200, [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '.csv"',
            ]);
        }

        return view('admin.reports.btrc.disconnections', compact('data', 'fromDate', 'toDate'));
    }

    public function ipAssignment(Request $request)
    {
        $filters = $request->only(['zone_id']);
        $data = $this->reportService->getIpAssignmentReport($filters);
        $zones = Zone::where('is_active', true)->get();

        if ($request->has('export')) {
            $filename = 'btrc_ip_assignment_' . date('Y-m-d');

            if ($request->export === 'excel') {
                $export = new BtrcReportExport($data, 'ip_assignment', 'BTRC IP Assignment Report', $this->getCompanyInfo());
                return Excel::download($export, $filename . '.xlsx');
            }

            $csv = $this->reportService->exportToCsv($data, 'ip_assignment');
            return Response::make($csv, 200, [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '.csv"',
            ]);
        }

        return view('admin.reports.btrc.ip-assignment', compact('data', 'zones', 'filters'));
    }

    public function monthlyRevenue(Request $request)
    {
        $year = $request->year ?? Carbon::now()->year;
        $month = $request->month ?? Carbon::now()->month;
        
        $data = $this->reportService->getMonthlyRevenueReport($year, $month);

        return view('admin.reports.btrc.monthly-revenue', compact('data', 'year', 'month'));
    }

    public function btrcOfficialFormat(Request $request)
    {
        $filters = $request->only([
            'zone_id', 'from_date', 'to_date', 'status',
            'client_source', 'specific_reseller_id',
            'district_id', 'upazila_id',
        ]);
        $zones     = \App\Models\Zone::where('is_active', true)->get();
        $resellers  = \App\Models\Reseller::whereNotIn('status', ['terminated'])
            ->orderByRaw("type ASC")->orderBy('name')
            ->get(['id', 'name', 'reseller_id', 'type']);
        $districts  = District::orderBy('name')->get(['id','name','division']);
        $upazilas   = Upazila::orderBy('name')->get(['id','name','district_id']);

        if ($request->has('export')) {
            $data     = $this->reportService->getBtrcOfficialFormatData($filters);
            $source   = $filters['client_source'] ?? 'all';
            $srcLabel = match($source) {
                'own'       => 'Own_Clients',
                'mac'       => 'MAC_Reseller',
                'bandwidth' => 'BW_Reseller',
                'specific'  => 'Reseller_' . ($filters['specific_reseller_id'] ?? 'X'),
                default     => 'All',
            };
            $filename = 'BTRC_Clients_List_' . $srcLabel . '_' . date('Y-m-d');

            if ($request->export === 'excel') {
                $export = new BtrcReportExport(
                    $data,
                    'btrc_official',
                    'BTRC Clients List',
                    $this->getCompanyInfo()
                );
                return Excel::download($export, $filename . '.xlsx');
            }

            // CSV export — plain format matching official BTRC template
            $headers = [
                'client_type', 'connection_type', 'client_name',
                'bandwidth_distribution_point', 'connectivity_type',
                'activation_date', 'bandwidth_allocation', 'allocated_ip',
                'division', 'district', 'thana', 'address',
                'client_mobile', 'client_email', 'selling_price_bdt_excluding_vat',
            ];

            $output = fopen('php://temp', 'r+');
            fputcsv($output, $headers);
            foreach ($data as $row) {
                fputcsv($output, array_values($row));
            }
            rewind($output);
            $csv = stream_get_contents($output);
            fclose($output);

            return Response::make($csv, 200, [
                'Content-Type'        => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '.csv"',
            ]);
        }

        // Preview — pass empty data so the blade view can show filter form
        $data = $request->anyFilled(['zone_id', 'from_date', 'to_date', 'status', 'client_source', 'specific_reseller_id'])
            ? $this->reportService->getBtrcOfficialFormatData($filters)
            : [];

        return view('admin.reports.btrc.official-format', compact('data', 'zones', 'filters', 'resellers', 'districts', 'upazilas'));
    }

}