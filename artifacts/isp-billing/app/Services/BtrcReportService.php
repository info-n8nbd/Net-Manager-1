<?php
/**
 * BTRC Compliance Report Service
 * 
 * Generates reports required by Bangladesh Telecommunication Regulatory Commission (BTRC)
 * 
 * Required Reports:
 * 1. Subscriber List Report - All active subscribers with NID, address, connection details
 * 2. New Connection Report - New connections in a period
 * 3. Disconnection Report - Disconnected/cancelled subscribers
 * 4. IP Assignment Report - IP address assignments
 * 5. Traffic/Usage Report - Bandwidth usage per subscriber
 */

namespace App\Services;

use App\Models\Client;
use App\Models\Bill;
use App\Models\Payment;
use App\Models\Zone;
use App\Models\Package;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class BtrcReportService
{
    /**
     * Get Subscriber List Report (All Active Subscribers)
     * Required by BTRC for compliance
     */
    public function getSubscriberListReport(array $filters = []): array
    {
        $query = Client::with(['zone', 'area', 'box', 'package'])
            ->whereIn('status', ['active', 'inactive']);

        if (!empty($filters['zone_id'])) {
            $query->where('zone_id', $filters['zone_id']);
        }

        if (!empty($filters['from_date'])) {
            $query->where('connection_date', '>=', $filters['from_date']);
        }

        if (!empty($filters['to_date'])) {
            $query->where('connection_date', '<=', $filters['to_date']);
        }

        $clients = $query->orderBy('client_id')->get();

        return $clients->map(function ($client) {
            return [
                'sl_no' => $client->id,
                'client_id' => $client->client_id,
                'name' => $client->name,
                'father_name' => $client->father_husband_name ?? '',
                'nid' => $client->nid ?? 'N/A',
                'phone' => $client->phone,
                'email' => $client->email ?? '',
                'address' => $client->address,
                'zone' => $client->zone?->name ?? '',
                'area' => $client->area?->name ?? '',
                'connection_type' => strtoupper($client->connection_type ?? 'PPPOE'),
                'username' => $client->username ?? $client->pppoe_username,
                'ip_address' => $client->ip_address ?? 'Dynamic',
                'mac_address' => $client->mac_address ?? 'N/A',
                'package' => $client->package?->name ?? '',
                'bandwidth' => $this->formatBandwidth($client->package),
                'monthly_fee' => $client->monthly_fee,
                'connection_date' => $client->connection_date?->format('d-m-Y') ?? '',
                'status' => ucfirst($client->status),
            ];
        })->toArray();
    }

    /**
     * Get New Connection Report
     */
    public function getNewConnectionReport(string $fromDate, string $toDate): array
    {
        $clients = Client::with(['zone', 'package'])
            ->whereBetween('connection_date', [$fromDate, $toDate])
            ->orderBy('connection_date')
            ->get();

        return $clients->map(function ($client) {
            return [
                'client_id' => $client->client_id,
                'name' => $client->name,
                'nid' => $client->nid ?? 'N/A',
                'phone' => $client->phone,
                'address' => $client->address,
                'zone' => $client->zone?->name ?? '',
                'connection_type' => strtoupper($client->connection_type ?? 'PPPOE'),
                'package' => $client->package?->name ?? '',
                'bandwidth' => $this->formatBandwidth($client->package),
                'connection_date' => $client->connection_date?->format('d-m-Y'),
            ];
        })->toArray();
    }

    /**
     * Get Disconnection Report
     */
    public function getDisconnectionReport(string $fromDate, string $toDate): array
    {
        $clients = Client::with(['zone', 'package'])
            ->where('status', 'inactive')
            ->whereBetween('updated_at', [$fromDate, $toDate])
            ->orderBy('updated_at')
            ->get();

        return $clients->map(function ($client) {
            return [
                'client_id' => $client->client_id,
                'name' => $client->name,
                'nid' => $client->nid ?? 'N/A',
                'phone' => $client->phone,
                'address' => $client->address,
                'zone' => $client->zone?->name ?? '',
                'connection_date' => $client->connection_date?->format('d-m-Y'),
                'disconnection_date' => ($client->last_inactive_date ? \Carbon\Carbon::parse($client->last_inactive_date)->format('d-m-Y') : $client->updated_at?->format('d-m-Y')),
                'reason' => $client->auto_inactive_note ?? 'Customer Request',
            ];
        })->toArray();
    }

    /**
     * Get IP Assignment Report
     */
    public function getIpAssignmentReport(array $filters = []): array
    {
        $query = Client::with(['zone', 'package'])
            ->whereNotNull('ip_address')
            ->whereIn('status', ['active', 'inactive']);

        if (!empty($filters['zone_id'])) {
            $query->where('zone_id', $filters['zone_id']);
        }

        $clients = $query->orderBy('ip_address')->get();

        return $clients->map(function ($client) {
            return [
                'client_id' => $client->client_id,
                'name' => $client->name,
                'username' => $client->username ?? $client->pppoe_username,
                'ip_address' => $client->ip_address,
                'mac_address' => $client->mac_address ?? 'N/A',
                'connection_type' => strtoupper($client->connection_type ?? 'PPPOE'),
                'zone' => $client->zone?->name ?? '',
                'assignment_date' => $client->connection_date?->format('d-m-Y'),
                'status' => ucfirst($client->status),
            ];
        })->toArray();
    }

    /**
     * Get Monthly Revenue Report
     */
    public function getMonthlyRevenueReport(int $year, int $month): array
    {
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $payments = Payment::with('paymentMethod')
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->whereIn('status', ['completed', 'approved'])
            ->get();

        $methodNames = \App\Models\PaymentMethod::pluck('name', 'id')->toArray();

        $summary = [
            'period' => $startDate->format('F Y'),
            'total_collection' => $payments->sum('amount'),
            'total_transactions' => $payments->count(),
            'by_method' => $payments->groupBy('payment_method_id')->mapWithKeys(function ($group, $methodId) use ($methodNames) {
                $name = $methodNames[$methodId] ?? 'Other';
                return [$name => [
                    'count' => $group->count(),
                    'amount' => $group->sum('amount'),
                ]];
            })->toArray(),
        ];

        // Zone-wise collection
        $zoneCollection = Payment::join('clients', 'payments.client_id', '=', 'clients.id')
            ->join('zones', 'clients.zone_id', '=', 'zones.id')
            ->whereBetween('payments.payment_date', [$startDate, $endDate])
            ->whereIn('payments.status', ['completed', 'approved'])
            ->select('zones.name as zone_name', DB::raw('SUM(payments.amount) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('zones.id', 'zones.name')
            ->get()
            ->toArray();

        $summary['by_zone'] = $zoneCollection;

        return $summary;
    }

    /**
     * Get Subscriber Statistics Summary
     */
    public function getSubscriberStatistics(): array
    {
        return [
            'total_subscribers' => Client::count(),
            'active_subscribers' => Client::where('status', 'active')->count(),
            'inactive_subscribers' => Client::where('status', 'inactive')->count(),
            'new_this_month' => Client::whereMonth('connection_date', now()->month)
                ->whereYear('connection_date', now()->year)
                ->count(),
            'by_connection_type' => Client::select('connection_type', DB::raw('COUNT(*) as count'))
                ->groupBy('connection_type')
                ->pluck('count', 'connection_type')
                ->toArray(),
            'by_zone' => Client::join('zones', 'clients.zone_id', '=', 'zones.id')
                ->select('zones.name', DB::raw('COUNT(*) as count'))
                ->groupBy('zones.id', 'zones.name')
                ->pluck('count', 'name')
                ->toArray(),
            'by_package' => Client::join('packages', 'clients.package_id', '=', 'packages.id')
                ->where('clients.status', 'active')
                ->select('packages.name', DB::raw('COUNT(*) as count'))
                ->groupBy('packages.id', 'packages.name')
                ->pluck('count', 'name')
                ->toArray(),
        ];
    }

    /**
     * Export report to CSV format
     */
    public function exportToCsv(array $data, string $filename): string
    {
        if (empty($data)) {
            return '';
        }

        $output = fopen('php://temp', 'r+');
        
        // Header row
        fputcsv($output, array_keys($data[0]));
        
        // Data rows
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        return $csv;
    }

    /**
     * Format bandwidth from package
     */
    protected function formatBandwidth($package): string
    {
        if (!$package) {
            return 'N/A';
        }

        $download = $package->download_speed ?? $package->bandwidth ?? 0;
        $upload = $package->upload_speed ?? $package->bandwidth ?? 0;
        $unit = $package->speed_unit ?? 'Mbps';

        return "{$download}/{$upload} {$unit}";
    }

    /**
     * Get BTRC Official Format Data (as per BTRC submission template)
     * Columns match the official BTRC XLS submission format
     */
    public function getBtrcOfficialFormatData(array $filters = []): array
    {
        $query = Client::with(['zone', 'area', 'package', 'district', 'upazila', 'reseller'])
            ->whereIn('status', ['active', 'inactive']);

        // ── Client Source filter ─────────────────────────────────────
        $clientSource       = $filters['client_source']        ?? 'all';
        $specificResellerId = $filters['specific_reseller_id'] ?? null;

        if ($clientSource === 'own') {
            // Only company's own direct clients
            $query->whereNull('reseller_id');
        } elseif ($clientSource === 'mac') {
            // Only clients under MAC-type resellers
            $query->whereNotNull('reseller_id')
                  ->whereHas('reseller', fn($q) => $q->where('type', 'mac'));
        } elseif ($clientSource === 'bandwidth') {
            // Only clients under Bandwidth-type resellers
            $query->whereNotNull('reseller_id')
                  ->whereHas('reseller', fn($q) => $q->where('type', 'bandwidth'));
        } elseif ($clientSource === 'specific' && $specificResellerId) {
            // Only clients belonging to a specific reseller
            $query->where('reseller_id', $specificResellerId);
        }
        // 'all' = no extra filter

        if (!empty($filters['zone_id'])) {
            $query->where('zone_id', $filters['zone_id']);
        }
        if (!empty($filters['district_id'])) {
            $query->where('district_id', $filters['district_id']);
        }
        if (!empty($filters['upazila_id'])) {
            $query->where('upazila_id', $filters['upazila_id']);
        }
        if (!empty($filters['from_date'])) {
            $query->where('connection_date', '>=', $filters['from_date']);
        }
        if (!empty($filters['to_date'])) {
            $query->where('connection_date', '<=', $filters['to_date']);
        }
        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        $clients = $query->orderBy('connection_date')->get();

        return $clients->map(function ($client) {
            // Map client_type
            $clientTypeRaw = strtolower($client->client_type ?? 'regular');
            $clientType = in_array($clientTypeRaw, ['corporate', 'business', 'office', 'commercial'])
                ? 'Corporate' : 'Home';

            // Map connection_type: hotspot -> Wireless, others -> Wired
            $connTypeRaw = strtolower($client->connection_type ?? 'pppoe');
            $connectionType = ($connTypeRaw === 'hotspot') ? 'Wireless' : 'Wired';

            // Bandwidth in Mbps
            $bandwidth = 0;
            if ($client->package) {
                $bandwidth = (int)($client->package->download_speed ?? 0);
                if (($client->package->speed_unit ?? 'Mbps') === 'Kbps') {
                    $bandwidth = (int)round($bandwidth / 1024);
                }
            }

            // Division and district from district relation
            $division = '';
            $district = '';
            if ($client->district) {
                $district = $client->district->name ?? '';
                $division = $client->district->division ?? '';
            }

            // Thana from upazila, fallback to area name
            $thana = $client->upazila?->name ?? '';
            if (empty($thana)) {
                $thana = $client->area?->name ?? '';
            }

            // Phone: normalize to 880XXXXXXXXXX format
            $phone = preg_replace('/[^0-9]/', '', $client->phone ?? '');
            if (strlen($phone) === 11 && $phone[0] === '0') {
                $phone = '880' . substr($phone, 1);
            } elseif (strlen($phone) === 10) {
                $phone = '880' . $phone;
            }

            return [
                'client_type'                     => $clientType,
                'connection_type'                 => $connectionType,
                'client_name'                     => $client->name ?? '',
                'bandwidth_distribution_point'    => 'NOC',
                'connectivity_type'               => 'Shared',
                'activation_date'                 => $client->connection_date
                                                      ? $client->connection_date->format('Y-m-d') : '',
                'bandwidth_allocation'            => (string)$bandwidth,
                'allocated_ip'                    => $client->username ?? '',
                'division'                        => $division,
                'district'                        => $district,
                'thana'                           => $thana,
                'address'                         => $client->address ?? '',
                'client_mobile'                   => $phone,
                'client_email'                    => $client->email ?? '',
                'selling_price_bdt_excluding_vat' => number_format((float)($client->package?->btrc_amount ?? $client->monthly_fee), 2, '.', ''),
            ];
        })->toArray();
    }

}