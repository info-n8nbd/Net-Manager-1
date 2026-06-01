<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Monthly Revenue Report</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: sans-serif; font-size: 10px; color: #333; line-height: 1.4; }
        
        .container { max-width: 800px; margin: 0 auto; padding: 15px; }
        
        /* Header */
        .header { display: table; width: 100%; margin-bottom: 20px; border-bottom: 3px solid #1e3a5f; padding-bottom: 15px; }
        .header-left { display: table-cell; width: 50%; vertical-align: middle; }
        .header-right { display: table-cell; width: 50%; vertical-align: middle; text-align: right; }
        .logo { max-height: 50px; max-width: 150px; }
        .company-name { font-size: 18px; font-weight: bold; color: #1e3a5f; }
        .report-title { font-size: 20px; font-weight: bold; color: #1e3a5f; }
        .report-subtitle { font-size: 11px; color: #666; margin-top: 3px; }
        
        /* Summary Cards */
        .summary-row { display: table; width: 100%; margin-bottom: 20px; }
        .summary-card { display: table-cell; width: 33.33%; padding: 0 5px; }
        .summary-box { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; padding: 15px; border-radius: 8px; text-align: center; }
        .summary-box.primary { background: linear-gradient(135deg, #1e3a5f 0%, #2c5282 100%); }
        .summary-box.info { background: linear-gradient(135deg, #17a2b8 0%, #3498db 100%); }
        .summary-value { font-size: 20px; font-weight: bold; margin-bottom: 3px; }
        .summary-label { font-size: 9px; text-transform: uppercase; letter-spacing: 1px; opacity: 0.9; }
        
        /* Table */
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th { background: #1e3a5f; color: white; padding: 10px 8px; text-align: left; font-size: 9px; text-transform: uppercase; letter-spacing: 0.5px; }
        th:last-child { text-align: right; }
        td { padding: 8px; border-bottom: 1px solid #e0e0e0; font-size: 10px; }
        td:last-child { text-align: right; }
        tr:nth-child(even) { background: #f8f9fa; }
        .total-row { background: #1e3a5f !important; color: white; font-weight: bold; }
        .total-row td { border: none; padding: 12px 8px; }
        
        /* Footer */
        .footer { margin-top: 30px; padding-top: 15px; border-top: 2px solid #eee; text-align: center; }
        .footer-text { font-size: 9px; color: #888; }
        .page-info { font-size: 8px; color: #aaa; margin-top: 5px; }
        
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-muted { color: #666; }
        .text-success { color: #28a745; font-weight: bold; }
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
                <div class="report-title">Monthly Revenue Report</div>
                <div class="report-subtitle">Period: {{ $date ?? now()->format('F Y') }}</div>
            </div>
        </div>
        
        <!-- Summary Cards -->
        @php
            $dayCount = isset($dailyData) ? count($dailyData) : 0;
            $avgDaily = $dayCount > 0 ? ($revenue ?? 0) / $dayCount : 0;
        @endphp
        <div class="summary-row">
            <div class="summary-card">
                <div class="summary-box">
                    <div class="summary-value">{{ $currency ?? 'BDT ' }}{{ number_format($revenue ?? 0, 0) }}</div>
                    <div class="summary-label">Total Revenue</div>
                </div>
            </div>
            <div class="summary-card">
                <div class="summary-box info">
                    <div class="summary-value">{{ $dayCount }}</div>
                    <div class="summary-label">Collection Days</div>
                </div>
            </div>
            <div class="summary-card">
                <div class="summary-box primary">
                    <div class="summary-value">{{ $currency ?? 'BDT ' }}{{ number_format($avgDaily, 0) }}</div>
                    <div class="summary-label">Daily Average</div>
                </div>
            </div>
        </div>
        
        <!-- Data Table -->
        <table>
            <thead>
                <tr>
                    <th style="width: 10%;">#</th>
                    <th style="width: 50%;">Date</th>
                    <th style="width: 40%;">Collection Amount</th>
                </tr>
            </thead>
            <tbody>
                @forelse($dailyData ?? [] as $index => $row)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ \Carbon\Carbon::parse($row->date)->format('d-m-y (l)') }}</td>
                    <td class="text-success">{{ $currency ?? 'BDT ' }}{{ number_format($row->total ?? 0, 2) }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="3" class="text-center text-muted">No collection data for this period</td>
                </tr>
                @endforelse
                @if(count($dailyData ?? []) > 0)
                <tr class="total-row">
                    <td colspan="2">Total Monthly Revenue</td>
                    <td>{{ $currency ?? 'BDT ' }}{{ number_format($revenue ?? 0, 2) }}</td>
                </tr>
                @endif
            </tbody>
        </table>
        
        <!-- Footer -->
        <div class="footer">
            <div class="footer-text">Generated on {{ now()->format('d-m-y h:i A') }}</div>
            <div class="page-info">{{ $companyName ?? 'ISP Billing System' }} | Confidential Financial Report</div>
        </div>
    </div>
</body>
</html>
