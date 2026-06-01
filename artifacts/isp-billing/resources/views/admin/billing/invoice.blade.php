<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $bill->bill_no }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: sans-serif; font-size: 11px; color: #333; line-height: 1.5; }
        
        .invoice-container { max-width: 800px; margin: 0 auto; padding: 20px; }
        
        /* Header */
        .header { display: table; width: 100%; margin-bottom: 30px; border-bottom: 3px solid #1e3a5f; padding-bottom: 20px; }
        .header-left { display: table-cell; width: 60%; vertical-align: middle; }
        .header-right { display: table-cell; width: 40%; vertical-align: middle; text-align: right; }
        .logo { max-height: 60px; max-width: 200px; }
        .company-name { font-size: 22px; font-weight: bold; color: #1e3a5f; margin-bottom: 5px; }
        .company-info { font-size: 10px; color: #666; }
        .invoice-title { font-size: 32px; font-weight: bold; color: #1e3a5f; letter-spacing: 2px; }
        .invoice-number { font-size: 14px; color: #666; margin-top: 5px; }
        
        /* Invoice Meta */
        .invoice-meta { display: table; width: 100%; margin-bottom: 25px; }
        .meta-box { display: table-cell; width: 50%; vertical-align: top; }
        .meta-box.right { text-align: right; }
        .meta-label { font-size: 10px; color: #999; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 3px; }
        .meta-value { font-size: 12px; color: #333; font-weight: 500; }
        
        /* Bill To Section */
        .bill-to { background: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 25px; border-left: 4px solid #1e3a5f; }
        .bill-to-title { font-size: 10px; color: #999; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px; }
        .client-name { font-size: 16px; font-weight: bold; color: #1e3a5f; margin-bottom: 5px; }
        .client-details { font-size: 11px; color: #555; }
        
        /* Items Table */
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 25px; }
        .items-table th { background: #1e3a5f; color: white; padding: 12px 15px; text-align: left; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; }
        .items-table th:last-child { text-align: right; }
        .items-table td { padding: 12px 15px; border-bottom: 1px solid #eee; }
        .items-table td:last-child { text-align: right; }
        .items-table tr:nth-child(even) { background: #fafafa; }
        .items-table .total-row { background: #f0f4f8 !important; font-weight: bold; }
        .items-table .grand-total { background: #1e3a5f !important; color: white; font-size: 14px; }
        
        /* Summary Box */
        .summary-section { display: table; width: 100%; margin-bottom: 30px; }
        .summary-left { display: table-cell; width: 55%; vertical-align: top; }
        .summary-right { display: table-cell; width: 45%; vertical-align: top; }
        .summary-table { width: 100%; border-collapse: collapse; }
        .summary-table td { padding: 8px 12px; border-bottom: 1px solid #eee; }
        .summary-table td:last-child { text-align: right; font-weight: 500; }
        .summary-table .highlight { background: #1e3a5f; color: white; font-size: 14px; font-weight: bold; }
        
        /* Status Badge */
        .status-badge { display: inline-block; padding: 5px 15px; border-radius: 20px; font-size: 11px; font-weight: bold; text-transform: uppercase; }
        .status-paid { background: #d4edda; color: #155724; }
        .status-partial { background: #fff3cd; color: #856404; }
        .status-unpaid { background: #f8d7da; color: #721c24; }
        
        /* Payment Info */
        .payment-info { background: #e8f4fd; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .payment-info-title { font-weight: bold; color: #1e3a5f; margin-bottom: 8px; }
        
        /* Footer */
        .footer { margin-top: 40px; padding-top: 20px; border-top: 2px solid #eee; text-align: center; }
        .footer-text { font-size: 10px; color: #888; margin-bottom: 5px; }
        .footer-note { font-size: 9px; color: #aaa; font-style: italic; }
        
        /* QR/Barcode placeholder */
        .qr-section { text-align: center; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="invoice-container">
        <!-- Header -->
        <div class="header">
            <div class="header-left">
                @if(!empty($logo))
                    <img src="{{ public_path($logo) }}" alt="Logo" class="logo">
                @else
                    <div class="company-name">{{ $companyName ?? 'ISP Billing System' }}</div>
                @endif
                <div class="company-info">
                    {{ $companyAddress ?? '' }}<br>
                    {{ $companyPhone ?? '' }} | {{ $companyEmail ?? '' }}
                </div>
            </div>
            <div class="header-right">
                <div class="invoice-title">INVOICE</div>
                <div class="invoice-number">#{{ $bill->bill_no }}</div>
            </div>
        </div>
        
        <!-- Invoice Meta -->
        <div class="invoice-meta">
            <div class="meta-box">
                <div class="meta-label">Invoice Date</div>
                <div class="meta-value">{{ $bill->bill_date->format('d-m-Y') }}</div>
                <br>
                <div class="meta-label">Due Date</div>
                <div class="meta-value">{{ $bill->due_date->format('d-m-Y') }}</div>
            </div>
            <div class="meta-box right">
                <div class="meta-label">Billing Period</div>
                <div class="meta-value">{{ date('F Y', mktime(0,0,0,$bill->billing_month,1,$bill->billing_year)) }}</div>
                <br>
                <div class="meta-label">Status</div>
                <div class="meta-value">
                    @if($bill->due_amount <= 0)
                        <span class="status-badge status-paid">PAID</span>
                    @elseif($bill->paid_amount > 0)
                        <span class="status-badge status-partial">PARTIAL</span>
                    @else
                        <span class="status-badge status-unpaid">UNPAID</span>
                    @endif
                </div>
            </div>
        </div>
        
        <!-- Bill To -->
        <div class="bill-to">
            <div class="bill-to-title">Bill To</div>
            <div class="client-name">{{ $bill->client->name }}</div>
            <div class="client-details">
                <strong>Client ID:</strong> {{ $bill->client->client_id }}<br>
                @if($bill->client->address)<strong>Address:</strong> {{ $bill->client->address }}<br>@endif
                @if($bill->client->phone)<strong>Phone:</strong> {{ $bill->client->phone }}<br>@endif
                <strong>Package:</strong> {{ $bill->client->package->name ?? 'N/A' }}
            </div>
        </div>
        
        <!-- Items Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 60%;">Description</th>
                    <th style="width: 20%;">Qty</th>
                    <th style="width: 20%;">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <strong>Monthly Internet Subscription</strong><br>
                        <span style="color: #666; font-size: 10px;">{{ $bill->client->package->name ?? 'Standard Package' }} - {{ date('F Y', mktime(0,0,0,$bill->billing_month,1,$bill->billing_year)) }}</span>
                    </td>
                    <td>1</td>
                    <td>BDT {{ number_format($bill->package_amount ?? 0, 2) }}</td>
                </tr>
                @if($bill->previous_due > 0)
                <tr>
                    <td>Previous Outstanding Balance</td>
                    <td>-</td>
                    <td>BDT {{ number_format($bill->previous_due ?? 0, 2) }}</td>
                </tr>
                @endif
                @if($bill->discount > 0)
                <tr>
                    <td><span style="color: #28a745;">Discount Applied</span></td>
                    <td>-</td>
                    <td style="color: #28a745;">-BDT {{ number_format($bill->discount ?? 0, 2) }}</td>
                </tr>
                @endif
            </tbody>
        </table>
        
        <!-- Summary -->
        <div class="summary-section">
            <div class="summary-left">
                @if($bill->due_amount > 0)
                <div class="payment-info">
                    <div class="payment-info-title">Payment Information</div>
                    <div style="font-size: 11px;">
                        Please make payment before the due date to avoid service interruption.<br>
                        For payment inquiries, contact our support team.
                    </div>
                </div>
                @endif
            </div>
            <div class="summary-right">
                <table class="summary-table">
                    <tr>
                        <td>Subtotal</td>
                        <td>BDT {{ number_format(($bill->package_amount ?? 0) + ($bill->previous_due ?? 0), 2) }}</td>
                    </tr>
                    @if($bill->discount > 0)
                    <tr>
                        <td>Discount</td>
                        <td>-BDT {{ number_format($bill->discount ?? 0, 2) }}</td>
                    </tr>
                    @endif
                    <tr style="border-top: 2px solid #1e3a5f;">
                        <td><strong>Total Amount</strong></td>
                        <td><strong>BDT {{ number_format($bill->total_amount ?? 0, 2) }}</strong></td>
                    </tr>
                    <tr>
                        <td>Paid Amount</td>
                        <td style="color: #28a745;">BDT {{ number_format($bill->paid_amount ?? 0, 2) }}</td>
                    </tr>
                    <tr class="highlight">
                        <td>Balance Due</td>
                        <td>BDT {{ number_format($bill->due_amount ?? 0, 2) }}</td>
                    </tr>
                </table>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <div class="footer-text">Thank you for choosing our services!</div>
            <div class="footer-note">This is a computer-generated invoice. No signature required.</div>
            <div class="footer-note" style="margin-top: 10px;">Generated on {{ now()->format('d-m-y h:i A') }}</div>
        </div>
    </div>
</body>
</html>
