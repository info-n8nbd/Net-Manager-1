<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Money Receipt</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        @page { margin: 10mm; }
        body { font-family: Arial, 'Helvetica Neue', sans-serif; font-size: 13px; background: #fff; color: #111; }
        .wrapper { max-width: 440px; margin: 0 auto; padding: 14px; }
        .header { text-align: center; padding-bottom: 10px; border-bottom: 2px solid #222; margin-bottom: 10px; }
        .logo img { height: 42px; margin-bottom: 4px; }
        .company-name { font-size: 18px; font-weight: 800; }
        .company-tagline { font-size: 11px; color: #555; margin: 2px 0; }
        .company-info { font-size: 11.5px; color: #444; line-height: 1.6; margin-top: 2px; }
        .receipt-title { text-align: center; font-size: 16px; font-weight: 700; letter-spacing: 0.5px; border-top: 1px solid #bbb; border-bottom: 1px solid #bbb; padding: 6px 0; margin: 10px 0 8px; }
        .info-table { width: 100%; border-collapse: collapse; margin-bottom: 8px; background: #f8f9fa; }
        .info-table td { padding: 5px 10px; font-size: 12px; border-bottom: 1px solid #e9ecef; }
        .info-table tr:last-child td { border-bottom: none; }
        .info-table .lbl { font-weight: 600; width: 42%; color: #444; }
        .meta-table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        .meta-table td { padding: 5px 10px; font-size: 12px; border: 1px solid #dee2e6; }
        .meta-table .lbl { font-weight: 600; width: 38%; background: #f1f3f5; color: #444; }
        .amount-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .amount-table td { padding: 5px 12px; font-size: 12.5px; }
        .amount-table td:last-child { text-align: right; }
        .amount-table .bold-row td { font-weight: 700; border-top: 2px solid #222; padding-top: 7px; }
        .amount-table .sub-row td { color: #555; }
        .received-by { display: flex; align-items: stretch; font-size: 12px; border: 1px solid #dee2e6; margin-bottom: 10px; }
        .received-by .rb-lbl { font-weight: 600; padding: 7px 10px; background: #f1f3f5; border-right: 1px solid #dee2e6; white-space: nowrap; }
        .received-by .rb-val { padding: 7px 10px; }
        .dashed { border-top: 1px dashed #aaa; margin: 8px 0; }
        .footer { text-align: center; font-size: 11px; color: #777; padding: 4px 0; }
        .btn-area { text-align: center; margin-top: 16px; display: flex; gap: 12px; justify-content: center; }
        .btn-print { background: #1565c0; color: #fff; border: none; padding: 11px 32px; font-size: 14px; border-radius: 6px; cursor: pointer; font-weight: 600; flex: 1; max-width: 160px; }
        .btn-cancel { background: #6c757d; color: #fff; border: none; padding: 11px 32px; font-size: 14px; border-radius: 6px; cursor: pointer; font-weight: 600; flex: 1; max-width: 160px; }
        .btn-print:active, .btn-cancel:active { opacity: 0.85; }
        @media print { .btn-area { display: none; } }
    </style>
</head>
<body>
<div class="wrapper">

@php
    $companyLogo    = \App\Models\SystemSetting::get('site_logo', '');
    $companyTagline = \App\Models\SystemSetting::get('company_tagline', '');
    $companyName    = $settings['name'] ?? config('app.name');
    $companyAddr    = $settings['address'] ?? '';
    $companyPhone   = $settings['phone'] ?? '';
    $companyEmail   = $settings['email'] ?? '';

    $bill      = $payment->bill;
    $totalBill = $bill ? $bill->total_amount : $payment->amount;
    $discount  = $bill ? $bill->discount     : 0;
    $due       = $payment->client?->due_amount    ?? 0;
    $advance   = $payment->client?->advance_amount ?? 0;

    $receiptNo  = $payment->payment_no ?? $payment->receipt_no ?? 'RCP-'.$payment->id;
    $payDate    = $payment->payment_date->format('d M Y, h:i A');
    $method     = $payment->paymentMethod?->name ?? 'Cash';
    $collectedBy = $payment->collectedBy?->name ?? 'Office';
@endphp

    {{-- HEADER --}}
    <div class="header">
        @if($companyLogo)
            <div class="logo"><img src="{{ url($companyLogo) }}" alt="Logo"></div>
        @endif
        <div class="company-name">{{ $companyName }}</div>
        @if($companyTagline)
            <div class="company-tagline">{{ $companyTagline }}</div>
        @endif
        @if($companyAddr)
            <div class="company-info">{{ $companyAddr }}</div>
        @endif
        @if($companyEmail)
            <div class="company-info">{{ $companyEmail }}</div>
        @endif
        @if($companyPhone)
            <div class="company-info"><strong>Mobile: {{ $companyPhone }}</strong></div>
        @endif
    </div>

    {{-- TITLE --}}
    <div class="receipt-title">Money Receipt</div>

    {{-- CLIENT INFO --}}
    <table class="info-table">
        <tr><td class="lbl">Client Code</td><td>{{ $payment->client?->client_id }}</td></tr>
        <tr><td class="lbl">Client Name</td><td>{{ $payment->client?->name }}</td></tr>
        <tr><td class="lbl">User Name</td><td>{{ $payment->client?->username ?? $payment->client?->client_id }}</td></tr>
        <tr><td class="lbl">Mobile No.</td><td>{{ $payment->client?->phone }}</td></tr>
    </table>

    {{-- RECEIPT META --}}
    <table class="meta-table">
        <tr><td class="lbl">Receipt No.</td><td>{{ $receiptNo }}</td></tr>
        <tr><td class="lbl">Pay. Date</td><td>{{ $payDate }}</td></tr>
        <tr><td class="lbl">P. Method</td><td>{{ $method }}</td></tr>
    </table>

    {{-- AMOUNTS --}}
    <table class="amount-table">
        <tr class="bold-row"><td>Total Bill:</td><td>{{ number_format((float)$totalBill, 2) }}</td></tr>
        <tr><td>Paid Amount:</td><td><strong>{{ number_format((float)$payment->amount, 2) }}</strong></td></tr>
        <tr class="sub-row"><td>Discount:</td><td>{{ number_format((float)$discount, 2) }}</td></tr>
        <tr class="sub-row"><td>Due Amount:</td><td>{{ number_format((float)$due, 2) }}</td></tr>
        <tr class="sub-row"><td>VAT Amount:</td><td>0.00</td></tr>
        <tr class="sub-row"><td>Advance:</td><td>{{ number_format((float)$advance, 2) }}</td></tr>
    </table>

    {{-- RECEIVED BY --}}
    <div class="received-by">
        <div class="rb-lbl">Payment Received By</div>
        <div class="rb-val">{{ $collectedBy }}</div>
    </div>

    <div class="dashed"></div>
    <div class="footer">Thank you for your payment!</div>

    <div class="btn-area">
        <button class="btn-cancel" onclick="if(window.opener||history.length<=1){window.close();}else{history.back();}">&#8592; Cancel</button>
        <button class="btn-print" onclick="window.print()">&#128438; Print</button>
    </div>

</div>

</body>
</html>
