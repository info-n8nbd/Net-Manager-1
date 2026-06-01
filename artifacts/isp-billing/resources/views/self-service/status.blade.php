<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1">
    <meta name="theme-color" content="#1e3c72">
    <title>Quick Pay</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        *{margin:0;padding:0;box-sizing:border-box;-webkit-tap-highlight-color:transparent;}
        body{
            font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;
            min-height:100vh;
            background:linear-gradient(160deg,#1e3c72 0%,#2a5298 100%);
            display:flex;align-items:center;justify-content:center;padding:20px;
        }
        .card{
            background:#fff;border-radius:20px;
            width:100%;max-width:340px;overflow:hidden;
            box-shadow:0 20px 60px rgba(0,0,0,0.3);
        }
        /* header */
        .hdr{
            background:linear-gradient(135deg,#1e3c72,#2a5298);
            padding:16px 20px;display:flex;align-items:center;justify-content:space-between;
        }
        .hdr-name{color:#fff;font-size:14px;font-weight:600;}
        .hdr-id{color:rgba(255,255,255,0.65);font-size:11px;margin-top:2px;}
        .pill{font-size:10px;font-weight:700;padding:3px 10px;border-radius:20px;}
        .pill-on{background:rgba(16,185,129,0.25);color:#6ee7b7;}
        .pill-off{background:rgba(239,68,68,0.25);color:#fca5a5;}
        /* due */
        .due-box{padding:24px 20px 18px;text-align:center;border-bottom:1px solid #f1f5f9;}
        .due-lbl{font-size:10px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:1px;margin-bottom:4px;}
        .due-amt{font-size:44px;font-weight:800;color:#dc2626;line-height:1;}
        .due-amt.zero{color:#059669;}
        /* gateway chips */
        .gw-section{padding:16px 20px 0;}
        .gw-lbl{font-size:10px;font-weight:600;color:#94a3b8;text-transform:uppercase;letter-spacing:0.8px;margin-bottom:10px;}
        .gw-chips{display:flex;gap:8px;flex-wrap:wrap;justify-content:center;}
        .gw-chip{display:inline-flex;
            
            padding:8px 12px;border-radius:10px;
            border:2px solid #e2e8f0;
            background:#f8fafc;cursor:pointer;
            display:flex;flex-direction:column;align-items:center;gap:4px;
            transition:all 0.15s;
        }
        .gw-chip input[type=radio]{display:none;}
        .gw-chip .gw-name{font-size:11px;font-weight:700;color:#64748b;}
        .gw-chip i{font-size:18px;}
        /* bKash chip */
        .gw-chip.bkash-chip.selected,.gw-chip.bkash-chip:has(input:checked){
            border-color:#e50695;background:#fff0fb;
        }
        .gw-chip.bkash-chip.selected .gw-name,.gw-chip.bkash-chip:has(input:checked) .gw-name{color:#e50695;}
        .gw-chip.bkash-chip i{color:#e50695;}
        /* SSL chip */
        .gw-chip.ssl-chip.selected,.gw-chip.ssl-chip:has(input:checked){
            border-color:#0066cc;background:#f0f6ff;
        }
        .gw-chip.ssl-chip.selected .gw-name,.gw-chip.ssl-chip:has(input:checked) .gw-name{color:#0066cc;}
        .gw-chip.ssl-chip i{color:#0066cc;}
        /* Nagad chip */
        .gw-chip.nagad-chip.selected,.gw-chip.nagad-chip:has(input:checked){
            border-color:#ef6c00;background:#fff8f0;
        }
        .gw-chip.nagad-chip.selected .gw-name,.gw-chip.nagad-chip:has(input:checked) .gw-name{color:#ef6c00;}
        .gw-chip.nagad-chip i{color:#ef6c00;}
        /* pay form */
        .pay-box{padding:14px 20px 20px;}
        
        .btn-pay{
            width:100%;padding:13px;border:none;border-radius:12px;cursor:pointer;
            background:linear-gradient(135deg,#1e3c72,#2a5298);
            color:#fff;font-size:14px;font-weight:700;
            display:flex;align-items:center;justify-content:center;gap:8px;
            box-shadow:0 4px 14px rgba(30,60,114,0.3);
            transition:transform 0.15s;
        }
        .btn-pay:active{transform:scale(0.97);}
        /* alerts */
        .alert{padding:12px 16px;font-size:12px;display:flex;align-items:flex-start;gap:8px;}
        .alert-success{background:#d1fae5;color:#065f46;}
        .alert-error{background:#fee2e2;color:#dc2626;}
        .alert-warn{background:#fef3c7;color:#92400e;}
        /* success screen */
        .success-box{padding:40px 20px;text-align:center;}
        .success-icon{font-size:60px;color:#10b981;margin-bottom:12px;}
        .success-title{font-size:20px;font-weight:800;color:#065f46;margin-bottom:6px;}
        .success-sub{font-size:13px;color:#6b7280;margin-bottom:20px;}
        /* no due */
        .nodues-box{padding:28px 20px;text-align:center;}
        .nodues-icon{font-size:48px;color:#10b981;margin-bottom:10px;}
        .nodues-title{font-size:16px;font-weight:700;color:#065f46;margin-bottom:4px;}
        .nodues-sub{font-size:12px;color:#6b7280;}
        /* back */
        .back-link{display:block;text-align:center;padding:13px;border-top:1px solid #f1f5f9;color:#94a3b8;font-size:12px;text-decoration:none;}
    </style>
</head>
<body>
<div class="card">

@if(session('success'))
<div class="success-box">
    <div class="success-icon"><i class="fas fa-check-circle"></i></div>
    <div class="success-title">Payment Successful!</div>
    <div class="success-sub">Your connection will be activated shortly.</div>
</div>
<a href="{{ route('self-service.index') }}" class="back-link">← Back</a>

@else
{{-- HEADER --}}
<div class="hdr">
    <div>
        <div class="hdr-name">{{ $maskedClient['name'] }}</div>
        <div class="hdr-id">ID: {{ $maskedClient['client_id'] }}</div>
    </div>
    <span class="pill {{ $maskedClient['status']==='active' ? 'pill-on' : 'pill-off' }}">
        {{ $maskedClient['status']==='active' ? 'ACTIVE' : 'INACTIVE' }}
    </span>
</div>

{{-- ALERTS --}}
@if(session('error'))
<div class="alert alert-error"><i class="fas fa-exclamation-circle"></i>{{ session('error') }}</div>
@endif
@if($maskedClient['status']==='inactive')
<div class="alert alert-warn"><i class="fas fa-exclamation-triangle"></i>Pay due to restore connection.</div>
@endif

{{-- DUE AMOUNT --}}
<div class="due-box">
    <div class="due-lbl">Total Due</div>
    <div class="due-amt {{ $billingData['due']<=0 ? 'zero' : '' }}">৳{{ number_format($billingData['due'],0) }}</div>
</div>

@if($billingData['due'] > 0 && count($enabledGateways) > 0)
<form action="{{ route('self-service.payment.initiate') }}" method="POST">
    @csrf
    <input type="hidden" name="client_code" value="{{ $client->client_id }}">
    <input type="hidden" name="token" value="{{ request('token') }}">

    {{-- GATEWAY CHIPS --}}
    <div class="gw-section">
        <div class="gw-lbl">Choose Payment Method</div>
        <div class="gw-chips">
            @foreach($enabledGateways as $gw)
            @php
                $gwMap = [
                    'bkash'      => ['label'=>'bKash', 'logo'=>'/images/gateways/bkash.png',      'class'=>'bkash-chip'],
                    'sslcommerz' => ['label'=>'SSL',   'logo'=>'/images/gateways/sslcommerz.png', 'class'=>'ssl-chip'],
                    'nagad'      => ['label'=>'Nagad', 'logo'=>'/images/gateways/nagad.png',      'class'=>'nagad-chip'],
                ];
                $info = $gwMap[$gw] ?? ['label'=>strtoupper($gw),'logo'=>null,'class'=>''];
            @endphp
            <label class="gw-chip {{ $info['class'] }} {{ $loop->first ? 'selected' : '' }}" id="chip-{{$gw}}">
                <input type="radio" name="gateway" value="{{ $gw }}" {{ $loop->first ? 'checked' : '' }}>
                @if(!empty($info['logo']))
                    <img src="{{ $info['logo'] }}" alt="{{ $info['label'] }}"
                         style="height:30px;max-width:100%;object-fit:contain;display:block;padding:0;margin:0;">
                @else
                    <i class="fas fa-credit-card" style="font-size:18px;"></i>
                    <span class="gw-name">{{ $info['label'] }}</span>
                @endif
            </label>
            @endforeach
        </div>
    </div>

    {{-- PAY --}}
    <div class="pay-box">
        <input type="hidden" name="amount" value="{{ $billingData['due'] }}">
        <button type="submit" class="btn-pay">
            <i class="fas fa-lock" style="font-size:12px;opacity:0.8;"></i>
            Pay ৳{{ number_format($billingData['due'],0) }} Now
        </button>
    </div>
</form>

@elseif($billingData['due'] > 0)
<div class="nodues-box">
    <div class="nodues-icon"><i class="fas fa-times-circle" style="color:#ef4444;"></i></div>
    <div class="nodues-title" style="color:#991b1b;">Payment Unavailable</div>
    <div class="nodues-sub">No payment gateway is currently active. Please contact support.</div>
</div>
@else
<div class="nodues-box">
    <div class="nodues-icon"><i class="fas fa-check-circle"></i></div>
    <div class="nodues-title">All Clear!</div>
    <div class="nodues-sub">No due amount. Your account is up-to-date.</div>
</div>
@endif

<a href="{{ route('self-service.index') }}" class="back-link">← Check Another Account</a>
@endif

</div>
<script>
(function(){
    // chip selection
    document.querySelectorAll('.gw-chip').forEach(function(chip){
        chip.addEventListener('click',function(){
            document.querySelectorAll('.gw-chip').forEach(function(c){ c.classList.remove('selected'); });
            this.classList.add('selected');
        });
    });
})();
</script>
</body>
</html>
