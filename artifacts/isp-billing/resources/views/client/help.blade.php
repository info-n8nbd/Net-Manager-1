@extends('layouts.client')
@section('title', 'Self-Help Guide')

@section('content')
<div class="row">
<div class="col-md-6 mb-2">

<div style="background:#fff; border-radius:10px; box-shadow:0 1px 5px rgba(0,0,0,0.06); margin-bottom:10px; overflow:hidden;">
    <div style="display:flex; align-items:center; padding:8px 14px; border-bottom:1px solid #f1f5f9; font-size:12px; font-weight:600; color:#1e3c72;">
        <i class="fas fa-wifi-slash mr-2 text-danger"></i>No Internet?
    </div>
    <div style="padding:10px 14px;">
        @foreach([
            ['1','Check router power','Ensure the router is on and all indicator lights are normal'],
            ['2','Restart your router','Turn off, wait 30 seconds, then power back on'],
            ['3','Check cable connections','Make sure fiber/LAN cables are securely plugged in'],
            ['4','Check the LOS light','Red LOS light = fiber cable issue — call support'],
        ] as $step)
        <div class="d-flex align-items-start mb-2">
            <span class="badge badge-primary mr-2 flex-shrink-0" style="font-size:10px; padding:3px 7px; margin-top:2px;">{{ $step[0] }}</span>
            <div><div style="font-size:12px; font-weight:600;">{{ $step[1] }}</div><small class="text-muted" style="font-size:10px;">{{ $step[2] }}</small></div>
        </div>
        @endforeach
    </div>
</div>

<div style="background:#fff; border-radius:10px; box-shadow:0 1px 5px rgba(0,0,0,0.06); margin-bottom:10px; overflow:hidden;">
    <div style="display:flex; align-items:center; padding:8px 14px; border-bottom:1px solid #f1f5f9; font-size:12px; font-weight:600; color:#1e3c72;">
        <i class="fas fa-tachometer-alt mr-2 text-warning"></i>Slow Speed?
    </div>
    <div style="padding:10px 14px;">
        @foreach([
            ['1','Run a speed test','Use our Speed Test tool to check current speed'],
            ['2','Restart router','Often resolves slow speed issues'],
            ['3','Disconnect other devices','Less devices = more bandwidth for you'],
            ['4','Contact support','If speed is consistently below package speed'],
        ] as $step)
        <div class="d-flex align-items-start mb-2">
            <span class="badge badge-warning mr-2 flex-shrink-0" style="font-size:10px; padding:3px 7px; margin-top:2px;">{{ $step[0] }}</span>
            <div><div style="font-size:12px; font-weight:600;">{{ $step[1] }}</div><small class="text-muted" style="font-size:10px;">{{ $step[2] }}</small></div>
        </div>
        @endforeach
    </div>
</div>

</div>
<div class="col-md-6 mb-2">

<div style="background:#fff; border-radius:10px; box-shadow:0 1px 5px rgba(0,0,0,0.06); margin-bottom:10px; overflow:hidden;">
    <div style="display:flex; align-items:center; padding:8px 14px; border-bottom:1px solid #f1f5f9; font-size:12px; font-weight:600; color:#1e3c72;">
        <i class="fas fa-phone-alt mr-2 text-success"></i>Contact Support
    </div>
    <div style="padding:10px 14px;">
        @php $phone = \App\Models\SystemSetting::get('support_phone', '+880 1XXXXXXXXX'); @endphp
        @php $email = \App\Models\SystemSetting::get('support_email', 'support@softynex.me'); @endphp
        <div class="mb-2"><i class="fas fa-phone mr-2 text-success"></i><a href="tel:{{ $phone }}" style="font-size:13px; font-weight:600; color:#1e3c72;">{{ $phone }}</a></div>
        <div class="mb-2"><i class="fas fa-envelope mr-2 text-primary"></i><a href="mailto:{{ $email }}" style="font-size:12px; color:#1e3c72;">{{ $email }}</a></div>
        <div class="mt-3">
            <a href="{{ route('client.tickets') }}" class="btn btn-primary btn-sm" style="border-radius:6px; font-size:11px;"><i class="fas fa-headset mr-1"></i>Submit a Ticket</a>
        </div>
    </div>
</div>

<div style="background:#fff; border-radius:10px; box-shadow:0 1px 5px rgba(0,0,0,0.06); overflow:hidden;">
    <div style="display:flex; align-items:center; padding:8px 14px; border-bottom:1px solid #f1f5f9; font-size:12px; font-weight:600; color:#1e3c72;">
        <i class="fas fa-question-circle mr-2 text-info"></i>FAQ
    </div>
    <div style="padding:10px 14px;">
        @foreach([
            ['How do I pay my bill?','Use the Pay Bill button on the dashboard or go to Pay Now section.'],
            ['When is my billing date?','Check your billing date on the dashboard.'],
            ['How to change my package?','Contact support or visit our office to request a package change.'],
            ['Why is my connection slow at night?','Peak hours (8-11 PM) may cause congestion. Contact support if persistent.'],
        ] as $faq)
        <div class="mb-2">
            <div style="font-size:12px; font-weight:600; color:#1e293b;">{{ $faq[0] }}</div>
            <div style="font-size:11px; color:#64748b;">{{ $faq[1] }}</div>
        </div>
        @endforeach
    </div>
</div>

</div>
</div>
@endsection
