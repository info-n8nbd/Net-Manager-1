<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Payment Receipt - {{ $payment->receipt_no ?? 'RCP-' . str_pad($payment->id, 6, '0', STR_PAD_LEFT) }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', Arial, sans-serif; 
            font-size: 12px; 
            line-height: 1.4;
            background: #f5f5f5;
            padding: 20px;
        }
        .receipt-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #0066cc;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .company-logo { max-height: 60px; margin-bottom: 10px; }
        .company-name { font-size: 24px; font-weight: bold; color: #0066cc; margin: 5px 0; }
        .company-info { font-size: 11px; color: #666; }
        .receipt-title {
            font-size: 18px;
            font-weight: bold;
            color: #333;
            text-align: center;
            margin: 15px 0;
            padding: 10px;
            background: #e8f4fc;
            border-radius: 4px;
        }
        .receipt-number {
            font-size: 14px;
            color: #0066cc;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        .info-section {
            padding: 15px;
            background: #f9f9f9;
            border-radius: 4px;
        }
        .info-section h4 {
            font-size: 12px;
            color: #0066cc;
            margin-bottom: 10px;
            text-transform: uppercase;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 4px 0;
        }
        .info-label { color: #666; }
        .info-value { font-weight: 600; color: #333; }
        .amount-section {
            text-align: center;
            padding: 25px;
            background: linear-gradient(135deg, #0066cc 0%, #0052a3 100%);
            color: white;
            border-radius: 8px;
            margin: 20px 0;
        }
        .amount-label { font-size: 14px; opacity: 0.9; }
        .amount-value { font-size: 36px; font-weight: bold; margin: 5px 0; }
        .amount-words { font-size: 12px; opacity: 0.8; font-style: italic; }
        .status-badge {
            display: inline-block;
            padding: 8px 20px;
            border-radius: 20px;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 12px;
        }
        .status-approved { background: #28a745; color: white; }
        .status-pending { background: #ffc107; color: #333; }
        .balance-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-top: 20px;
        }
        .balance-box {
            text-align: center;
            padding: 15px;
            border-radius: 4px;
        }
        .balance-due { background: #fff3cd; border: 1px solid #ffc107; }
        .balance-advance { background: #d4edda; border: 1px solid #28a745; }
        .balance-label { font-size: 11px; color: #666; }
        .balance-value { font-size: 20px; font-weight: bold; }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px dashed #ddd;
            color: #666;
            font-size: 11px;
        }
        .timestamp { font-size: 10px; color: #999; margin-top: 10px; }
        .print-buttons {
            text-align: center;
            margin-bottom: 20px;
        }
        .print-btn {
            display: inline-block;
            padding: 10px 25px;
            margin: 0 5px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
        }
        .btn-print { background: #0066cc; color: white; }
        .btn-download { background: #28a745; color: white; }
        .btn-close { background: #6c757d; color: white; }
        @media print {
            body { background: white; padding: 0; }
            .receipt-container { box-shadow: none; padding: 15px; }
            .print-buttons { display: none; }
        }
    </style>
</head>
<body>
    <div class="print-buttons">
        <button class="print-btn btn-print" onclick="window.print()">
            <i class="fas fa-print"></i> Print Receipt
        </button>
        <a href="{{ route('admin.payments.receipt.download', $payment) }}" class="print-btn btn-download">
            <i class="fas fa-download"></i> Download PDF
        </a>
        <button class="print-btn btn-close" onclick="window.close()">
            <i class="fas fa-times"></i> Close
        </button>
    </div>

    <div class="receipt-container">
        <div class="header">
            @if($settings['logo'])
            <img src="{{ asset('storage/' . $settings['logo']) }}" alt="Logo" class="company-logo">
            @endif
            <div class="company-name">{{ $settings['name'] }}</div>
            <div class="company-info">
                @if($settings['address']){{ $settings['address'] }}<br>@endif
                @if($settings['phone'])Phone: {{ $settings['phone'] }}@endif
                @if($settings['email']) | Email: {{ $settings['email'] }}@endif
            </div>
        </div>

        <div class="receipt-title">
            PAYMENT RECEIPT
            <div class="receipt-number">{{ $payment->receipt_no ?? 'RCP-' . str_pad($payment->id, 6, '0', STR_PAD_LEFT) }}</div>
        </div>

        <div class="info-grid">
            <div class="info-section">
                <h4>Customer Information</h4>
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
            </div>

            <div class="info-section">
                <h4>Payment Details</h4>
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
            </div>
        </div>

        <div class="amount-section">
            <div class="amount-label">Amount Paid</div>
            <div class="amount-value">৳ {{ number_format((float)$payment->amount, 0) }}</div>
            <div class="amount-words">{{ ucwords(\App\Helpers\NumberToWords::convert((float)$payment->amount)) }} Taka Only</div>
            <div style="margin-top: 15px;">
                <span class="status-badge status-{{ $payment->status ?? 'approved' }}">
                    {{ ucfirst($payment->status ?? 'Approved') }}
                </span>
            </div>
        </div>

        <div class="balance-info">
            <div class="balance-box balance-due">
                <div class="balance-label">Remaining Due</div>
                <div class="balance-value" style="color: #856404;">৳ {{ number_format((float)($payment->client?->due_amount ?? 0), 0) }}</div>
            </div>
            <div class="balance-box balance-advance">
                <div class="balance-label">Advance Balance</div>
                <div class="balance-value" style="color: #155724;">৳ {{ number_format((float)($payment->client?->advance_amount ?? 0), 0) }}</div>
            </div>
        </div>

        @if($payment->notes)
        <div style="margin-top: 15px; padding: 10px; background: #f0f0f0; border-radius: 4px;">
            <strong>Notes:</strong> {{ $payment->notes }}
        </div>
        @endif

        <div class="footer">
            <p>{{ $settings['footer_text'] }}</p>
            <p class="timestamp">Generated on {{ now()->format('d-m-y h:i:s A') }} (BDT)</p>
            <p style="font-size: 10px; margin-top: 5px;">This is a computer-generated receipt.</p>
        </div>
    </div>

    <script>
        window.onload = function() {
            window.print();
        };
    </script>
</body>
</html>
