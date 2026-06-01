<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>BTRC Compliance Report</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: sans-serif; font-size: 9px; color: #333; line-height: 1.4; }
        
        .container { max-width: 800px; margin: 0 auto; padding: 15px; }
        
        /* Header */
        .header { display: table; width: 100%; margin-bottom: 15px; border-bottom: 3px solid #1e3a5f; padding-bottom: 12px; }
        .header-left { display: table-cell; width: 50%; vertical-align: middle; }
        .header-right { display: table-cell; width: 50%; vertical-align: middle; text-align: right; }
        .logo { max-height: 45px; max-width: 130px; }
        .company-name { font-size: 16px; font-weight: bold; color: #1e3a5f; }
        .report-title { font-size: 18px; font-weight: bold; color: #1e3a5f; }
        .report-subtitle { font-size: 10px; color: #666; margin-top: 3px; }
        .btrc-badge { display: inline-block; background: #1e3a5f; color: white; padding: 3px 10px; border-radius: 3px; font-size: 8px; margin-top: 5px; }
        
        /* Summary Cards */
        .summary-row { display: table; width: 100%; margin-bottom: 15px; }
        .summary-card { display: table-cell; width: 33.33%; padding: 0 4px; }
        .summary-box { background: linear-gradient(135deg, #1e3a5f 0%, #2c5282 100%); color: white; padding: 12px; border-radius: 6px; text-align: center; }
        .summary-box.success { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); }
        .summary-box.warning { background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%); color: #333; }
        .summary-value { font-size: 18px; font-weight: bold; margin-bottom: 2px; }
        .summary-label { font-size: 8px; text-transform: uppercase; letter-spacing: 1px; opacity: 0.9; }
        
        /* Table */
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        th { background: #1e3a5f; color: white; padding: 8px 5px; text-align: left; font-size: 8px; text-transform: uppercase; letter-spacing: 0.3px; }
        td { padding: 6px 5px; border-bottom: 1px solid #e0e0e0; font-size: 9px; }
        tr:nth-child(even) { background: #f8f9fa; }
        
        /* Status Badge */
        .status-badge { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 8px; font-weight: bold; }
        .status-active { background: #d4edda; color: #155724; }
        .status-inactive { background: #f8d7da; color: #721c24; }
        
        /* Footer */
        .footer { margin-top: 20px; padding-top: 12px; border-top: 2px solid #eee; text-align: center; }
        .footer-text { font-size: 8px; color: #888; }
        .footer-note { font-size: 8px; color: #666; margin-top: 5px; font-style: italic; }
        
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
                <div class="report-title">BTRC Compliance Report</div>
                <div class="report-subtitle">Report Date: {{ $date ?? now()->format('d-m-Y') }}</div>
                <div class="btrc-badge">BTRC Regulatory Submission</div>
            </div>
        </div>
        
        <!-- Summary Cards -->
        <div class="summary-row">
            <div class="summary-card">
                <div class="summary-box">
                    <div class="summary-value">{{ $totalClients ?? 0 }}</div>
                    <div class="summary-label">Total Subscribers</div>
                </div>
            </div>
            <div class="summary-card">
                <div class="summary-box success">
                    <div class="summary-value">{{ $activeClients ?? 0 }}</div>
                    <div class="summary-label">Active Subscribers</div>
                </div>
            </div>
            <div class="summary-card">
                <div class="summary-box warning">
                    <div class="summary-value">{{ $inactiveClients ?? 0 }}</div>
                    <div class="summary-label">Inactive Subscribers</div>
                </div>
            </div>
        </div>
        
        <!-- Data Table -->
        <table>
            <thead>
                <tr>
                    <th style="width: 4%;">#</th>
                    <th style="width: 10%;">Client ID</th>
                    <th style="width: 18%;">Name</th>
                    <th style="width: 14%;">Username</th>
                    <th style="width: 14%;">Package</th>
                    <th style="width: 12%;">Conn. Type</th>
                    <th style="width: 10%; text-align: center;">Status</th>
                    <th style="width: 18%;">Zone</th>
                </tr>
            </thead>
            <tbody>
                @forelse($clients ?? [] as $index => $client)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td><strong>{{ $client->client_id ?? $client->id }}</strong></td>
                    <td>{{ $client->name }}</td>
                    <td>{{ $client->username ?? 'N/A' }}</td>
                    <td>{{ $client->package->name ?? 'N/A' }}</td>
                    <td>{{ ucfirst($client->connection_type ?? 'PPPoE') }}</td>
                    <td class="text-center">
                        @php $status = strtolower($client->status ?? 'active'); @endphp
                        <span class="status-badge status-{{ $status }}">{{ ucfirst($status) }}</span>
                    </td>
                    <td>{{ $client->zone->name ?? 'N/A' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center text-muted">No subscriber data available</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        
        <!-- Footer -->
        <div class="footer">
            <div class="footer-text">Generated on {{ now()->format('d-m-y h:i A') }}</div>
            <div class="footer-note">This report is generated for BTRC (Bangladesh Telecommunication Regulatory Commission) compliance purposes.</div>
            <div class="footer-text" style="margin-top: 5px;">{{ $companyName ?? 'ISP Billing System' }} | Licensed ISP Operator</div>
        </div>
    </div>
</body>
</html>
