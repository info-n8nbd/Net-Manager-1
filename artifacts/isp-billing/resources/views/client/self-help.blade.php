@extends('layouts.client')
@section('title', 'Self-Help Guide')
@push('styles')
<style>
.sh-box{background:#fff;border-radius:12px;box-shadow:0 1px 6px rgba(0,0,0,0.07);margin-bottom:10px;overflow:hidden;}
.sh-hd{padding:10px 14px;border-bottom:1px solid #f1f5f9;font-size:13px;font-weight:700;color:#1e3c72;display:flex;align-items:center;gap:8px;cursor:pointer;}
.sh-hd i.toggle-icon{margin-left:auto;font-size:11px;color:#94a3b8;transition:transform .2s;}
.sh-body{padding:12px 14px;font-size:12px;color:#374151;display:none;}
.sh-body.open{display:block;}
.sh-step{display:flex;gap:10px;margin-bottom:10px;align-items:flex-start;}
.sh-step-num{width:22px;height:22px;border-radius:50%;background:linear-gradient(135deg,#1e3c72,#2a5298);color:#fff;font-size:10px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px;}
.sh-step-txt{flex:1;min-width:0;line-height:1.5;}
.sh-tip{background:#f0fdf4;border-left:3px solid #22c55e;border-radius:0 8px 8px 0;padding:8px 12px;margin-bottom:8px;font-size:11px;color:#15803d;}
.sh-warn{background:#fff7ed;border-left:3px solid #f59e0b;border-radius:0 8px 8px 0;padding:8px 12px;margin-bottom:8px;font-size:11px;color:#92400e;}
.sh-hero{background:linear-gradient(135deg,#1e3c72,#2a5298);border-radius:12px;padding:14px;color:#fff;margin-bottom:10px;text-align:center;}
.sh-contact-btn{display:flex;align-items:center;justify-content:center;gap:8px;background:#fff;border:2px solid #1e3c72;border-radius:10px;padding:10px;color:#1e3c72;font-size:12px;font-weight:600;text-decoration:none;margin-bottom:8px;}
.sh-contact-btn:hover{background:#eff6ff;color:#1e3c72;text-decoration:none;}
</style>
@endpush
@section('content')

{{-- HERO --}}
<div class="sh-hero">
    <i class="fas fa-book-open" style="font-size:28px;margin-bottom:6px;opacity:.9;"></i>
    <div style="font-size:16px;font-weight:700;">Self-Help Guide</div>
    <div style="font-size:11px;opacity:.8;margin-top:2px;">Solve common issues without calling support</div>
</div>

{{-- 1. No Internet --}}
<div class="sh-box">
    <div class="sh-hd" onclick="toggleSection(this)">
        <i class="fas fa-wifi-slash text-danger"></i> No Internet Connection
        <i class="fas fa-chevron-down toggle-icon"></i>
    </div>
    <div class="sh-body">
        <div class="sh-step"><div class="sh-step-num">1</div><div class="sh-step-txt"><strong>Restart your Router.</strong> Turn off the router power switch, wait 10 seconds, then turn it on again.</div></div>
        <div class="sh-step"><div class="sh-step-num">2</div><div class="sh-step-txt"><strong>Check cable connections.</strong> Make sure all cables are properly connected to the router.</div></div>
        <div class="sh-step"><div class="sh-step-num">3</div><div class="sh-step-txt"><strong>Restart your device.</strong> Turn off WiFi on your PC/Mobile and turn it back on.</div></div>
        <div class="sh-step"><div class="sh-step-num">4</div><div class="sh-step-txt"><strong>Check your payment.</strong> If there is any due in bills, the service may be suspended.</div></div>
        <div class="sh-tip"><i class="fas fa-lightbulb mr-1"></i>If the issue persists, submit a Support Ticket or call us.</div>
    </div>
</div>

{{-- 2. Slow Internet --}}
<div class="sh-box">
    <div class="sh-hd" onclick="toggleSection(this)">
        <i class="fas fa-tachometer-alt text-warning"></i> If Internet is Slow
        <i class="fas fa-chevron-down toggle-icon"></i>
    </div>
    <div class="sh-body">
        <div class="sh-step"><div class="sh-step-num">1</div><div class="sh-step-txt"><strong>Close background apps.</strong> Check if YouTube, Netflix, or any downloads are running.</div></div>
        <div class="sh-step"><div class="sh-step-num">2</div><div class="sh-step-txt"><strong>Check connected devices.</strong> Speed decreases when too many devices are connected to WiFi.</div></div>
        <div class="sh-step"><div class="sh-step-num">3</div><div class="sh-step-txt"><strong>Move closer to the router.</strong> WiFi signal weakens with distance. Move closer to test.</div></div>
        <div class="sh-step"><div class="sh-step-num">4</div><div class="sh-step-txt"><strong>Run a speed test.</strong> <a href="https://www.speedtest.net" target="_blank">speedtest.net</a> to check your speed and compare with your package speed.</div></div>
        <div class="sh-warn"><i class="fas fa-exclamation-triangle mr-1"></i>If speed is much lower than your package, submit a Support Ticket.</div>
    </div>
</div>

{{-- 3. WiFi Password --}}
<div class="sh-box">
    <div class="sh-hd" onclick="toggleSection(this)">
        <i class="fas fa-key text-primary"></i> Change WiFi Password
        <i class="fas fa-chevron-down toggle-icon"></i>
    </div>
    <div class="sh-body">
        <div class="sh-step"><div class="sh-step-num">1</div><div class="sh-step-txt">Open <strong>192.168.0.1</strong> or <strong>192.168.1.1</strong> in your browser.</div></div>
        <div class="sh-step"><div class="sh-step-num">2</div><div class="sh-step-txt">Login to the router admin panel. (Usually user: <strong>admin</strong>, pass: <strong>admin</strong>)</div></div>
        <div class="sh-step"><div class="sh-step-num">3</div><div class="sh-step-txt">Go to <strong>Wireless / WiFi Settings</strong> and change the password.</div></div>
        <div class="sh-step"><div class="sh-step-num">4</div><div class="sh-step-txt">Save and reconnect all devices with the new password.</div></div>
    </div>
</div>

{{-- 4. Payment --}}
<div class="sh-box">
    <div class="sh-hd" onclick="toggleSection(this)">
        <i class="fas fa-money-bill-wave text-success"></i> Payment Related
        <i class="fas fa-chevron-down toggle-icon"></i>
    </div>
    <div class="sh-body">
        <div class="sh-step"><div class="sh-step-num">1</div><div class="sh-step-txt">To make an <strong>Online payment</strong>, "Pay" go to the Pay button and choose your preferred gateway.</div></div>
        <div class="sh-step"><div class="sh-step-num">2</div><div class="sh-step-txt"><strong>Manual payment</strong>: Send money via bKash/Nagad/Rocket and submit the TrxID.</div></div>
        <div class="sh-step"><div class="sh-step-num">3</div><div class="sh-step-txt">After payment submission, your account will be updated once the admin verifies it.</div></div>
        <div class="sh-tip"><i class="fas fa-lightbulb mr-1"></i>You can make advance payments — payment is allowed even without any due.</div>
    </div>
</div>

{{-- 5. Account / Profile --}}
<div class="sh-box">
    <div class="sh-hd" onclick="toggleSection(this)">
        <i class="fas fa-user-cog text-purple" style="color:#6366f1;"></i> Account & Profile
        <i class="fas fa-chevron-down toggle-icon"></i>
    </div>
    <div class="sh-body">
        <div class="sh-step"><div class="sh-step-num">1</div><div class="sh-step-txt"><strong>Forgot your password:</strong> Click on "Forgot Password" on the login page.</div></div>
        <div class="sh-step"><div class="sh-step-num">2</div><div class="sh-step-txt"><strong>To change package:</strong> Click on the Package card on the dashboard and submit a request.</div></div>
        <div class="sh-step"><div class="sh-step-num">3</div><div class="sh-step-txt"><strong>To update contact info:</strong> Go to the Profile page and change your information.</div></div>
    </div>
</div>

{{-- CONTACT SUPPORT --}}
<div class="sh-box">
    <div class="sh-hd" style="cursor:default;"><i class="fas fa-headset text-primary"></i> Still having issues?</div>
    <div class="sh-body" style="display:block;">
        <a href="{{ route('client.tickets') }}" class="sh-contact-btn">
            <i class="fas fa-ticket-alt"></i> Submit Support Ticket
        </a>
        <div style="text-align:center;font-size:11px;color:#94a3b8;">Our team is always ready to help</div>
    </div>
</div>

@endsection
@push('scripts')
<script>
function toggleSection(header){
    var body = header.nextElementSibling;
    var icon = header.querySelector('.toggle-icon');
    if(body.classList.contains('open')){
        body.classList.remove('open');
        icon.style.transform='rotate(0deg)';
    } else {
        body.classList.add('open');
        icon.style.transform='rotate(180deg)';
    }
}
</script>
@endpush
