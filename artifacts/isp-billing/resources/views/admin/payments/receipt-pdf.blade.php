<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Payment Receipt - {{ $payment->receipt_no ?? 'RCP-' . str_pad($payment->id, 6, '0', STR_PAD_LEFT) }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: DejaVu Sans, Arial, sans-serif; 
            font-size: 11px; 
            line-height: 1.4;
            padding: 20px;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #0066cc;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .company-name { font-size: 22px; font-weight: bold; color: #0066cc; }
        .company-info { font-size: 10px; color: #666; margin-top: 5px; }
        .receipt-title {
            font-size: 16px;
            font-weight: bold;
            text-align: center;
            margin: 15px 0;
            padding: 8px;
            background: #e8f4fc;
        }
        .receipt-number { font-size: 12px; color: #0066cc; }
        .info-table { width: 100%; margin-bottom: 15px; border-collapse: collapse; }
        .info-table td { padding: 8px; vertical-align: top; }
        .info-table .section-header {
            font-size: 11px;
            font-weight: bold;
            color: #0066cc;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
            margin-bottom: 8px;
        }
        .info-row { margin: 4px 0; }
        .info-label { color: #666; display: inline-block; width: 100px; }
        .info-value { font-weight: 600; }
        .amount-box {
            text-align: center;
            padding: 20px;
            background: #0066cc;
            color: white;
            margin: 15px 0;
        }
        .amount-label { font-size: 12px; }
        .amount-value { font-size: 28px; font-weight: bold; margin: 5px 0; }
        .amount-words { font-size: 10px; font-style: italic; }
        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            background: #28a745;
            color: white;
            font-weight: bold;
            font-size: 10px;
            margin-top: 10px;
        }
        .balance-table { width: 100%; margin-top: 15px; }
        .balance-table td { 
            width: 50%; 
            text-align: center; 
            padding: 10px;
        }
        .balance-due { background: #fff3cd; border: 1px solid #ffc107; }
        .balance-advance { background: #d4edda; border: 1px solid #28a745; }
        .balance-label { font-size: 10px; color: #666; }
        .balance-value { font-size: 16px; font-weight: bold; }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px dashed #ddd;
            color: #666;
            font-size: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">{{ $settings['name'] }}</div>
        <div class="company-info">
            @if($settings['address']){{ $settings['address'] }} | @endif
            @if($settings['phone'])Phone: {{ $settings['phone'] }}@endif
            @if($settings['email']) | {{ $settings['email'] }}@endif
        </div>
    </div>

    <div class="receipt-title">
        PAYMENT RECEIPT
        <div class="receipt-number">{{ $payment->receipt_no ?? 'RCP-' . str_pad($payment->id, 6, '0', STR_PAD_LEFT) }}</div>
    </div>

    <table class="info-table">
        <tr>
            <td width="50%" style="background: #f9f9f9; padding: 15px;">
                <div class="section-header">Customer Information</div>
                <div class="info-row">
                    <span class="info-label">Client ID:</span>
                    <span class="info-value">{{ $payment->client?->client_id }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Name:</span>
                    <span class="info-value">{{ $payment->client?->name }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Phone:</span>
                    <span class="info-value">{{ $payment->client?->phone }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Zone:</span>
                    <span class="info-value">{{ $payment->client?->zone?->name ?? '-' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Package:</span>
                    <span class="info-value">{{ $payment->client?->package?->name ?? '-' }}</span>
                </div>
            </td>
            <td width="50%" style="background: #f9f9f9; padding: 15px;">
                <div class="section-header">Payment Details</div>
                <div class="info-row">
                    <span class="info-label">Payment Date:</span>
                    <span class="info-value">{{ $payment->payment_date->format('d-m-Y') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Payment Time:</span>
                    <span class="info-value">{{ $payment->created_at->format('h:i A') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Method:</span>
                    <span class="info-value">{{ $payment->paymentMethod->name ?? 'Cash' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Collected By:</span>
                    <span class="info-value">{{ $payment->collectedBy?->name ?? $payment->receiver_name ?? 'Office' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">For Month:</span>
                    <span class="info-value">{{ $payment->for_month ?? $payment->payment_date->format('F Y') }}</span>
                </div>
            </td>
        </tr>
    </table>

    <div class="amount-box">
        <div class="amount-label">Amount Paid</div>
        <div class="amount-value">৳ {{ number_format((float)$payment->amount, 0) }}</div>
        <div class="amount-words">{{ ucwords(\App\Helpers\NumberToWords::convert((float)$payment->amount)) }} Taka Only</div>
        <div class="status-badge">{{ strtoupper($payment->status ?? 'APPROVED') }}</div>
    </div>

    <table class="balance-table">
        <tr>
            <td class="balance-due">
                <div class="balance-label">Remaining Due</div>
                <div class="balance-value" style="color: #856404;">৳ {{ number_format((float)($payment->client?->due_amount ?? 0), 0) }}</div>
            </td>
            <td class="balance-advance">
                <div class="balance-label">Advance Balance</div>
                <div class="balance-value" style="color: #155724;">৳ {{ number_format((float)($payment->client?->advance_amount ?? 0), 0) }}</div>
            </td>
        </tr>
    </table>

    @if($payment->notes)
    <div style="margin-top: 15px; padding: 10px; background: #f0f0f0;">
        <strong>Notes:</strong> {{ $payment->notes }}
    </div>
    @endif

    <div class="footer">
        <p>{{ $settings['footer_text'] }}</p>
        <p style="margin-top: 5px;">Generated on {{ now()->format('d-m-y h:i:s A') }} (BDT)</p>
        <p>This is a computer-generated receipt and does not require a signature.</p>
    </div>
</body>
</html>
