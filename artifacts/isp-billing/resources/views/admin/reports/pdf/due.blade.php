<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Due Report</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: sans-serif; font-size: 10px; color: #333; line-height: 1.4; }
        
        .container { max-width: 800px; margin: 0 auto; padding: 15px; }
        
        /* Header */
        .header { display: table; width: 100%; margin-bottom: 20px; border-bottom: 3px solid #dc3545; padding-bottom: 15px; }
        .header-left { display: table-cell; width: 50%; vertical-align: middle; }
        .header-right { display: table-cell; width: 50%; vertical-align: middle; text-align: right; }
        .logo { max-height: 50px; max-width: 150px; }
        .company-name { font-size: 18px; font-weight: bold; color: #1e3a5f; }
        .report-title { font-size: 20px; font-weight: bold; color: #dc3545; }
        .report-subtitle { font-size: 11px; color: #666; margin-top: 3px; }
        
        /* Summary Cards */
        .summary-row { display: table; width: 100%; margin-bottom: 20px; }
        .summary-card { display: table-cell; width: 33.33%; padding: 0 5px; }
        .summary-box { background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); color: white; padding: 15px; border-radius: 8px; text-align: center; }
        .summary-box.warning { background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%); color: #333; }
        .summary-box.info { background: linear-gradient(135deg, #17a2b8 0%, #3498db 100%); }
        .summary-value { font-size: 20px; font-weight: bold; margin-bottom: 3px; }
        .summary-label { font-size: 9px; text-transform: uppercase; letter-spacing: 1px; opacity: 0.9; }
        
        /* Table */
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th { background: #dc3545; color: white; padding: 10px 8px; text-align: left; font-size: 9px; text-transform: uppercase; letter-spacing: 0.5px; }
        th:last-child { text-align: right; }
        td { padding: 8px; border-bottom: 1px solid #e0e0e0; font-size: 10px; }
        td:last-child { text-align: right; }
        tr:nth-child(even) { background: #f8f9fa; }
        .total-row { background: #dc3545 !important; color: white; font-weight: bold; }
        .total-row td { border: none; padding: 12px 8px; }
        
        /* Footer */
        .footer { margin-top: 30px; padding-top: 15px; border-top: 2px solid #eee; text-align: center; }
        .footer-text { font-size: 9px; color: #888; }
        .page-info { font-size: 8px; color: #aaa; margin-top: 5px; }
        
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-muted { color: #666; }
        .text-danger { color: #dc3545; font-weight: bold; }
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
                <div class="report-title">Outstanding Due Report</div>
                <div class="report-subtitle">Generated: {{ $date ?? now()->format('d-m-Y') }}</div>
            </div>
        </div>
        
        <!-- Summary Cards -->
        @php
            $totalDueCalc = isset($clients) ? $clients->sum('due_amount') : ($totalDue ?? 0);
            $clientCount = isset($clients) ? count($clients) : 0;
        @endphp
        <div class="summary-row">
            <div class="summary-card">
                <div class="summary-box">
                    <div class="summary-value">{{ $currency ?? 'BDT ' }}{{ number_format($totalDueCalc, 0) }}</div>
                    <div class="summary-label">Total Outstanding</div>
                </div>
            </div>
            <div class="summary-card">
                <div class="summary-box warning">
                    <div class="summary-value">{{ $clientCount }}</div>
                    <div class="summary-label">Clients with Due</div>
                </div>
            </div>
            <div class="summary-card">
                <div class="summary-box info">
                    <div class="summary-value">{{ $currency ?? 'BDT ' }}{{ $clientCount > 0 ? number_format($totalDueCalc / $clientCount, 0) : 0 }}</div>
                    <div class="summary-label">Avg. Due Amount</div>
                </div>
            </div>
        </div>
        
        <!-- Data Table -->
        <table>
            <thead>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 12%;">Client ID</th>
                    <th style="width: 20%;">Name</th>
                    <th style="width: 13%;">Phone</th>
                    <th style="width: 18%;">Zone</th>
                    <th style="width: 15%;">Package</th>
                    <th style="width: 17%;">Due Amount</th>
                </tr>
            </thead>
            <tbody>
                @forelse($clients ?? [] as $index => $client)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td><strong>{{ $client->client_id }}</strong></td>
                    <td>{{ $client->name }}</td>
                    <td>{{ $client->phone ?? 'N/A' }}</td>
                    <td>{{ $client->zone->name ?? 'N/A' }}</td>
                    <td>{{ $client->package->name ?? 'N/A' }}</td>
                    <td class="text-danger">{{ $currency ?? 'BDT ' }}{{ number_format($client->due_amount ?? 0, 2) }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center text-muted">No outstanding dues</td>
                </tr>
                @endforelse
                @if(count($clients ?? []) > 0)
                <tr class="total-row">
                    <td colspan="6">Total Outstanding Due</td>
                    <td>{{ $currency ?? 'BDT ' }}{{ number_format($totalDueCalc, 2) }}</td>
                </tr>
                @endif
            </tbody>
        </table>
        
        <!-- Footer -->
        <div class="footer">
            <div class="footer-text">Generated on {{ now()->format('d-m-y h:i A') }}</div>
            <div class="page-info">{{ $companyName ?? 'ISP Billing System' }} | Confidential Report</div>
        </div>
    </div>
</body>
</html>
