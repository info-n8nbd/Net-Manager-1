<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Client;
use App\Models\Zone;

class BandwidthReportController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        $companyId = auth()->user()->company_id;
        $zones = Zone::orderBy('name')->get();
        $dateFrom = $request->date_from ?? now()->subDays(7)->format('Y-m-d');
        $dateTo = $request->date_to ?? now()->format('Y-m-d');

        $topClients = DB::table('bandwidth_logs')
            ->join('clients', 'bandwidth_logs.client_id', '=', 'clients.id')
            ->where('clients.company_id', $companyId)
            ->whereBetween('log_date', [$dateFrom, $dateTo])
            ->select('clients.id', 'clients.name', 'clients.client_id as client_code',
                     DB::raw('SUM(rx_bytes) as total_rx'), DB::raw('SUM(tx_bytes) as total_tx'),
                     DB::raw('MAX(rx_rate) as peak_rx'), DB::raw('MAX(tx_rate) as peak_tx'))
            ->groupBy('clients.id', 'clients.name', 'clients.client_id')
            ->orderByDesc('total_rx')
            ->limit(50)
            ->get();

        $dailyTotals = DB::table('bandwidth_logs')
            ->join('clients', 'bandwidth_logs.client_id', '=', 'clients.id')
            ->where('clients.company_id', $companyId)
            ->whereBetween('log_date', [$dateFrom, $dateTo])
            ->select('log_date', DB::raw('SUM(rx_bytes) as total_rx'), DB::raw('SUM(tx_bytes) as total_tx'))
            ->groupBy('log_date')
            ->orderBy('log_date')
            ->get();

        $zoneStats = DB::table('bandwidth_logs')
            ->join('clients', 'bandwidth_logs.client_id', '=', 'clients.id')
            ->join('zones', 'clients.zone_id', '=', 'zones.id')
            ->where('clients.company_id', $companyId)
            ->whereBetween('log_date', [$dateFrom, $dateTo])
            ->select('zones.name as zone_name', DB::raw('SUM(rx_bytes) as total_rx'), DB::raw('SUM(tx_bytes) as total_tx'))
            ->groupBy('zones.name')
            ->orderByDesc('total_rx')
            ->get();

        return view('admin.reports.bandwidth', compact('zones', 'dateFrom', 'dateTo', 'topClients', 'dailyTotals', 'zoneStats'));
    }

    public function clientDetail(Client $client, Request $request)
    {
        if ($client->company_id !== auth()->user()->company_id) {
            abort(403);
        }

        $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        $dateFrom = $request->date_from ?? now()->subDays(30)->format('Y-m-d');
        $dateTo = $request->date_to ?? now()->format('Y-m-d');

        $dailyUsage = DB::table('bandwidth_logs')
            ->where('client_id', $client->id)
            ->whereBetween('log_date', [$dateFrom, $dateTo])
            ->select('log_date', DB::raw('SUM(rx_bytes) as rx'), DB::raw('SUM(tx_bytes) as tx'),
                     DB::raw('MAX(rx_rate) as peak_rx'), DB::raw('MAX(tx_rate) as peak_tx'))
            ->groupBy('log_date')
            ->orderBy('log_date')
            ->get();

        $hourlyUsage = DB::table('bandwidth_logs')
            ->where('client_id', $client->id)
            ->whereBetween('log_date', [$dateFrom, $dateTo])
            ->select('log_hour', DB::raw('AVG(rx_rate) as avg_rx'), DB::raw('AVG(tx_rate) as avg_tx'))
            ->groupBy('log_hour')
            ->orderBy('log_hour')
            ->get();

        return view('admin.reports.bandwidth-client', compact('client', 'dateFrom', 'dateTo', 'dailyUsage', 'hourlyUsage'));
    }

    public function export(Request $request)
    {
        $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        $companyId = auth()->user()->company_id;
        $dateFrom = $request->date_from;
        $dateTo = $request->date_to;

        $data = DB::table('bandwidth_logs')
            ->join('clients', 'bandwidth_logs.client_id', '=', 'clients.id')
            ->where('clients.company_id', $companyId)
            ->whereBetween('log_date', [$dateFrom, $dateTo])
            ->select('clients.client_id', 'clients.name', 'log_date', 'rx_bytes', 'tx_bytes', 'rx_rate', 'tx_rate')
            ->orderBy('log_date')
            ->get();

        $csv = "Client ID,Name,Date,Download (bytes),Upload (bytes),Peak Download (bps),Peak Upload (bps)\n";
        foreach ($data as $row) {
            $safeName = $row->name;
            if (preg_match('/^[=+\-@\t\r]/', $safeName)) {
                $safeName = "'" . $safeName;
            }
            $escapedName = '"' . str_replace('"', '""', $safeName) . '"';
            $csv .= "{$row->client_id},{$escapedName},{$row->log_date},{$row->rx_bytes},{$row->tx_bytes},{$row->rx_rate},{$row->tx_rate}\n";
        }

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=bandwidth_report_{$dateFrom}_to_{$dateTo}.csv"
        ]);
    }
}
