<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Advance Liability Report</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: sans-serif; font-size: 10px; color: #333; line-height: 1.4; }
        
        .container { max-width: 800px; margin: 0 auto; padding: 15px; }
        
        /* Header */
        .header { display: table; width: 100%; margin-bottom: 15px; border-bottom: 3px solid #17a2b8; padding-bottom: 12px; }
        .header-left { display: table-cell; width: 50%; vertical-align: middle; }
        .header-right { display: table-cell; width: 50%; vertical-align: middle; text-align: right; }
        .logo { max-height: 45px; max-width: 130px; }
        .company-name { font-size: 16px; font-weight: bold; color: #1e3a5f; }
        .report-title { font-size: 18px; font-weight: bold; color: #17a2b8; }
        .report-subtitle { font-size: 10px; color: #666; margin-top: 3px; }
        
        /* Warning Box */
        .warning-box { background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%); border-left: 4px solid #ffc107; padding: 12px 15px; margin-bottom: 15px; border-radius: 0 6px 6px 0; }
        .warning-icon { color: #856404; font-weight: bold; margin-right: 5px; }
        .warning-text { color: #856404; font-size: 10px; }
        
        /* Summary Cards */
        .summary-row { display: table; width: 100%; margin-bottom: 15px; }
        .summary-card { display: table-cell; width: 25%; padding: 0 4px; }
        .summary-box { background: linear-gradient(135deg, #17a2b8 0%, #20c997 100%); color: white; padding: 12px 8px; border-radius: 6px; text-align: center; }
        .summary-box.success { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); }
        .summary-box.danger { background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); }
        .summary-box.primary { background: linear-gradient(135deg, #1e3a5f 0%, #2c5282 100%); }
        .summary-value { font-size: 16px; font-weight: bold; margin-bottom: 2px; }
        .summary-label { font-size: 7px; text-transform: uppercase; letter-spacing: 0.5px; opacity: 0.9; }
        
        /* Table */
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        th { background: #17a2b8; color: white; padding: 8px 6px; text-align: left; font-size: 8px; text-transform: uppercase; letter-spacing: 0.3px; }
        td { padding: 7px 6px; border-bottom: 1px solid #e0e0e0; font-size: 9px; }
        tr:nth-child(even) { background: #f8f9fa; }
        .total-row { background: #f0f9fa !important; font-weight: bold; border-top: 2px solid #17a2b8; }
        
        /* Footer */
        .footer { margin-top: 20px; padding-top: 12px; border-top: 2px solid #eee; text-align: center; }
        .footer-text { font-size: 8px; color: #888; }
        .footer-note { font-size: 8px; color: #dc3545; margin-top: 5px; font-weight: bold; }
        
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-muted { color: #666; }
        .text-success { color: #28a745; }
        .text-danger { color: #dc3545; }
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
                <div class="report-title">Advance Liability Report</div>
                <div class="report-subtitle">Period: {{ $summary['period']['start'] ?? ($date ?? now()->format('d-m-Y')) }} - {{ $summary['period']['end'] ?? ($date ?? now()->format('d-m-Y')) }}</div>
            </div>
        </div>
        
        <!-- Warning Box -->
        <div class="warning-box">
            <span class="warning-icon">!</span>
            <span class="warning-text"><strong>Important:</strong> Advance balance is a LIABILITY to the company. This amount must NOT be counted as revenue until bills are settled against it.</span>
        </div>
        
        <!-- Summary Cards -->
        <div class="summary-row">
            <div class="summary-card">
                <div class="summary-box danger">
                    <div class="summary-value">{{ $currency ?? 'BDT ' }}{{ number_format($summary['total_advance_balance'] ?? 0, 0) }}</div>
                    <div class="summary-label">Total Liability</div>
                </div>
            </div>
            <div class="summary-card">
                <div class="summary-box success">
                    <div class="summary-value">{{ $currency ?? 'BDT ' }}{{ number_format($summary['advance_received'] ?? 0, 0) }}</div>
                    <div class="summary-label">Advance Received</div>
                </div>
            </div>
            <div class="summary-card">
                <div class="summary-box">
                    <div class="summary-value">{{ $currency ?? 'BDT ' }}{{ number_format($summary['advance_used'] ?? 0, 0) }}</div>
                    <div class="summary-label">Advance Used</div>
                </div>
            </div>
            <div class="summary-card">
                <div class="summary-box primary">
                    <div class="summary-value">{{ $summary['clients_with_advance'] ?? 0 }}</div>
                    <div class="summary-label">Clients w/ Advance</div>
                </div>
            </div>
        </div>
        
        <!-- Section Title -->
        <div style="background: #17a2b8; color: white; padding: 8px 12px; margin-bottom: 0; border-radius: 6px 6px 0 0; font-weight: bold; font-size: 11px;">
            Clients with Advance Balance
        </div>
        
        <!-- Data Table -->
        <table style="margin-top: 0;">
            <thead>
                <tr>
                    <th style="width: 12%;">Client ID</th>
                    <th style="width: 22%;">Name</th>
                    <th style="width: 18%;">Zone</th>
                    <th style="width: 16%; text-align: right;">Advance Balance</th>
                    <th style="width: 16%; text-align: right;">Due Amount</th>
                    <th style="width: 16%; text-align: right;">Net Position</th>
                </tr>
            </thead>
            <tbody>
                @forelse($clients ?? [] as $client)
                <tr>
                    <td><strong>{{ $client['client_id'] }}</strong></td>
                    <td>{{ $client['name'] }}</td>
                    <td>{{ $client['zone'] }}</td>
                    <td class="text-right text-success">{{ $currency ?? 'BDT ' }}{{ number_format($client['advance_balance'] ?? 0, 2) }}</td>
                    <td class="text-right text-danger">{{ $currency ?? 'BDT ' }}{{ number_format($client['due_amount'] ?? 0, 2) }}</td>
                    <td class="text-right" style="font-weight: bold;">{{ $currency ?? 'BDT ' }}{{ number_format($client['net_position'] ?? 0, 2) }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center text-muted" style="padding: 20px;">No clients with advance balance</td>
                </tr>
                @endforelse
                @if(count($clients ?? []) > 0)
                <tr class="total-row">
                    <td colspan="3" class="text-right"><strong>Total:</strong></td>
                    <td class="text-right text-success"><strong>{{ $currency ?? 'BDT ' }}{{ number_format(collect($clients)->sum('advance_balance'), 2) }}</strong></td>
                    <td class="text-right text-danger"><strong>{{ $currency ?? 'BDT ' }}{{ number_format(collect($clients)->sum('due_amount'), 2) }}</strong></td>
                    <td class="text-right"><strong>{{ $currency ?? 'BDT ' }}{{ number_format(collect($clients)->sum('net_position'), 2) }}</strong></td>
                </tr>
                @endif
            </tbody>
        </table>
        
        <!-- Footer -->
        <div class="footer">
            <div class="footer-text">Generated on {{ now()->format('d-m-y h:i A') }}</div>
            <div class="footer-note">Advance is a LIABILITY - NOT revenue until service is delivered</div>
            <div class="footer-text" style="margin-top: 5px;">{{ $companyName ?? 'ISP Billing System' }} | Financial Compliance Report</div>
        </div>
    </div>
</body>
</html>
