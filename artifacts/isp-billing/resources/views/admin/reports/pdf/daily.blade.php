<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Daily Collection Report</title>
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
        .summary-box { background: linear-gradient(135deg, #1e3a5f 0%, #2c5282 100%); color: white; padding: 15px; border-radius: 8px; text-align: center; }
        .summary-box.success { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); }
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
        tr:hover { background: #e8f4fd; }
        .total-row { background: #1e3a5f !important; color: white; font-weight: bold; }
        .total-row td { border: none; padding: 12px 8px; }
        
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
                <div class="report-title">Daily Collection Report</div>
                <div class="report-subtitle">Date: {{ \Carbon\Carbon::parse($date)->format('d-m-Y') }}</div>
            </div>
        </div>
        
        <!-- Summary Cards -->
        <div class="summary-row">
            <div class="summary-card">
                <div class="summary-box success">
                    <div class="summary-value">{{ $currency ?? 'BDT ' }}{{ number_format($totalCollection ?? 0, 0) }}</div>
                    <div class="summary-label">Total Collection</div>
                </div>
            </div>
            <div class="summary-card">
                <div class="summary-box info">
                    <div class="summary-value">{{ count($payments ?? []) }}</div>
                    <div class="summary-label">Transactions</div>
                </div>
            </div>
            <div class="summary-card">
                <div class="summary-box">
                    <div class="summary-value">{{ $currency ?? 'BDT ' }}{{ count($payments ?? []) > 0 ? number_format(($totalCollection ?? 0) / count($payments), 0) : 0 }}</div>
                    <div class="summary-label">Avg. Payment</div>
                </div>
            </div>
        </div>
        
        <!-- Data Table -->
        <table>
            <thead>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 25%;">Client</th>
                    <th style="width: 15%;">Bill No</th>
                    <th style="width: 20%;">Payment Method</th>
                    <th style="width: 20%;">Collected By</th>
                    <th style="width: 15%;">Amount</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payments ?? [] as $index => $payment)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>
                        <strong>{{ $payment->client->name ?? 'N/A' }}</strong><br>
                        <span class="text-muted">{{ $payment->client->client_id ?? '' }}</span>
                    </td>
                    <td>{{ $payment->bill->bill_no ?? 'N/A' }}</td>
                    <td>{{ $payment->paymentMethod->name ?? 'Cash' }}</td>
                    <td>{{ $payment->collectedBy->name ?? 'Admin' }}</td>
                    <td>{{ $currency ?? 'BDT ' }}{{ number_format($payment->amount ?? 0, 2) }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center text-muted">No payments recorded for this date</td>
                </tr>
                @endforelse
                @if(count($payments ?? []) > 0)
                <tr class="total-row">
                    <td colspan="5">Total Collection</td>
                    <td>{{ $currency ?? 'BDT ' }}{{ number_format($totalCollection ?? 0, 2) }}</td>
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
