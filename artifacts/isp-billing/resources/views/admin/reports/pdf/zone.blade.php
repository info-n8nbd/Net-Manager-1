<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Zone-wise Revenue Report</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: sans-serif; font-size: 10px; color: #333; line-height: 1.4; }
        
        .container { max-width: 800px; margin: 0 auto; padding: 15px; }
        
        /* Header */
        .header { display: table; width: 100%; margin-bottom: 20px; border-bottom: 3px solid #6f42c1; padding-bottom: 15px; }
        .header-left { display: table-cell; width: 50%; vertical-align: middle; }
        .header-right { display: table-cell; width: 50%; vertical-align: middle; text-align: right; }
        .logo { max-height: 50px; max-width: 150px; }
        .company-name { font-size: 18px; font-weight: bold; color: #1e3a5f; }
        .report-title { font-size: 20px; font-weight: bold; color: #6f42c1; }
        .report-subtitle { font-size: 11px; color: #666; margin-top: 3px; }
        
        /* Summary Cards */
        .summary-row { display: table; width: 100%; margin-bottom: 20px; }
        .summary-card { display: table-cell; width: 33.33%; padding: 0 5px; }
        .summary-box { background: linear-gradient(135deg, #6f42c1 0%, #8969d3 100%); color: white; padding: 15px; border-radius: 8px; text-align: center; }
        .summary-box.success { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); }
        .summary-box.info { background: linear-gradient(135deg, #17a2b8 0%, #3498db 100%); }
        .summary-value { font-size: 20px; font-weight: bold; margin-bottom: 3px; }
        .summary-label { font-size: 9px; text-transform: uppercase; letter-spacing: 1px; opacity: 0.9; }
        
        /* Table */
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th { background: #6f42c1; color: white; padding: 10px 8px; text-align: left; font-size: 9px; text-transform: uppercase; letter-spacing: 0.5px; }
        td { padding: 8px; border-bottom: 1px solid #e0e0e0; font-size: 10px; }
        tr:nth-child(even) { background: #f8f9fa; }
        .total-row { background: #6f42c1 !important; color: white; font-weight: bold; }
        .total-row td { border: none; padding: 12px 8px; }
        
        /* Progress Bar */
        .progress-bar { background: #e9ecef; border-radius: 4px; height: 8px; width: 100%; overflow: hidden; }
        .progress-fill { background: linear-gradient(90deg, #6f42c1, #8969d3); height: 100%; border-radius: 4px; }
        
        /* Footer */
        .footer { margin-top: 30px; padding-top: 15px; border-top: 2px solid #eee; text-align: center; }
        .footer-text { font-size: 9px; color: #888; }
        .page-info { font-size: 8px; color: #aaa; margin-top: 5px; }
        
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-muted { color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-left">
                @if(!empty($logo))
                    <img src="{{ public_path($logo) }}" alt="Logo" class="logo">
                @else
                    <div class="company-name">{{ $companyName ?? 'ISP Billing System' }}</div>
                @endif
            </div>
            <div class="header-right">
                <div class="report-title">Zone-wise Revenue Report</div>
                <div class="report-subtitle">Period: {{ isset($startDate) ? $startDate->format('d-m-Y') : now()->format('d-m-Y') }} - {{ isset($endDate) ? $endDate->format('d-m-Y') : now()->format('d-m-Y') }}</div>
            </div>
        </div>
        
        <!-- Summary Cards -->
        @php
            $totalRevenue = collect($report ?? [])->sum('total_revenue');
            $totalClients = collect($report ?? [])->sum('client_count');
            $zoneCount = count($report ?? []);
        @endphp
        <div class="summary-row">
            <div class="summary-card">
                <div class="summary-box success">
                    <div class="summary-value">BDT {{ number_format($totalRevenue ?? 0, 0) }}</div>
                    <div class="summary-label">Total Revenue</div>
                </div>
            </div>
            <div class="summary-card">
                <div class="summary-box">
                    <div class="summary-value">{{ $zoneCount }}</div>
                    <div class="summary-label">Total Zones</div>
                </div>
            </div>
            <div class="summary-card">
                <div class="summary-box info">
                    <div class="summary-value">{{ $totalClients }}</div>
                    <div class="summary-label">Total Clients</div>
                </div>
            </div>
        </div>
        
        <!-- Data Table -->
        <table>
            <thead>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 30%;">Zone Name</th>
                    <th style="width: 12%; text-align: center;">Clients</th>
                    <th style="width: 23%; text-align: right;">Revenue</th>
                    <th style="width: 15%; text-align: center;">Share</th>
                    <th style="width: 15%; text-align: center;">Visual</th>
                </tr>
            </thead>
            <tbody>
                @forelse($report ?? [] as $index => $row)
                @php
                    $percentage = $totalRevenue > 0 ? ($row['total_revenue'] / $totalRevenue) * 100 : 0;
                @endphp
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td><strong>{{ $row['zone_name'] }}</strong></td>
                    <td class="text-center">{{ $row['client_count'] }}</td>
                    <td class="text-right">BDT {{ number_format($row['total_revenue'] ?? 0, 2) }}</td>
                    <td class="text-center">{{ number_format($percentage, 1) }}%</td>
                    <td>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: {{ $percentage }}%;"></div>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center text-muted">No zone data available</td>
                </tr>
                @endforelse
                @if(count($report ?? []) > 0)
                <tr class="total-row">
                    <td colspan="2">Total</td>
                    <td class="text-center">{{ $totalClients }}</td>
                    <td class="text-right">BDT {{ number_format($totalRevenue ?? 0, 2) }}</td>
                    <td class="text-center">100%</td>
                    <td></td>
                </tr>
                @endif
            </tbody>
        </table>
        
        <!-- Footer -->
        <div class="footer">
            <div class="footer-text">Generated on {{ now()->format('d-m-y h:i A') }}</div>
            <div class="page-info">{{ $companyName ?? 'ISP Billing System' }} | Zone Performance Analysis</div>
        </div>
    </div>
</body>
</html>
