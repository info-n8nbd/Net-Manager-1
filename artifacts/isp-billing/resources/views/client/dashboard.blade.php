@extends('layouts.client')
@section('title', 'Dashboard')

@push('styles')
<style>
.db-panel { background:#fff; border-radius:10px; box-shadow:0 1px 5px rgba(0,0,0,0.06); overflow:hidden; }
.bkash-btn-sm { background:linear-gradient(135deg,#e2136e,#c1105c); color:white; border:none; border-radius:6px; padding:6px 12px; font-size:11px; font-weight:600; white-space:nowrap; cursor:pointer; text-decoration:none; display:inline-block; }
.bkash-btn-sm:hover { color:white; text-decoration:none; opacity:.9; }
.db-stat { display:flex; align-items:center; padding:10px 12px; }
.db-stat-icon { width:30px; height:30px; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:12px; flex-shrink:0; margin-right:8px; }
.db-stat-val { font-weight:700; font-size:15px; line-height:1.1; overflow-wrap:break-word; word-break:break-word; min-width:0; }
.db-stat-lbl { font-size:10px; color:#94a3b8; }
.db-qa { display:flex; flex-direction:column; align-items:center; justify-content:center; background:#fff; border-radius:10px; box-shadow:0 1px 5px rgba(0,0,0,0.06); padding:10px 5px; min-height:58px; text-decoration:none; }
.db-qa:hover { text-decoration:none; }
@keyframes bwPulse{0%,100%{opacity:1;transform:scale(1)}50%{opacity:.4;transform:scale(.7)}}
.db-qa i { font-size:17px; margin-bottom:4px; }
.db-qa span { font-size:10px; }
.db-ph { display:flex; justify-content:space-between; align-items:center; padding:8px 12px; border-bottom:1px solid #f1f5f9; }
.db-ph-title { font-size:12px; font-weight:600; }
.db-ph-link { font-size:10px; color:#3b82f6; }
.db-pr { display:flex; justify-content:space-between; align-items:center; padding:7px 12px; border-bottom:1px solid #f8fafc; }
</style>
@endpush

@section('content')
@php
  $billingDay = $client->billing_date ?? $client->payment_deadline ?? null;
  if ($billingDay) {
    $now = \Carbon\Carbon::now();
    $nb = \Carbon\Carbon::create($now->year, $now->month, (int)$billingDay);
    if ($nb->lte($now)) $nb->addMonth();
    $billingStr = $nb->format('d M');
  } else {
    $billingStr = 'N/A';
  }
@endphp

{{-- STATUS BANNER --}}
<div class="status-card {{ ($client->connection_status ?? 'online') == 'online' ? 'online' : 'offline' }} mb-2" style="padding:8px 14px; border-radius:10px;">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <span style="font-weight:700; font-size:14px;"><i class="fas fa-wifi mr-1"></i><span id="connectionStatusText">{{ ucfirst($client->connection_status ?? 'online') }}</span></span>
            <small class="d-block" style="font-size:10px; opacity:.85; margin-top:1px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; max-width:160px;">{{ \Illuminate\Support\Str::limit($client->package->name ?? 'No Package', 20) }} &middot; {{ ($client->package->download_speed ?? '') ? ($client->package->download_speed.'/'.$client->package->upload_speed.' '.($client->package->speed_unit ?? 'Mbps')) : 'N/A' }}</small>
        </div>
        <div class="d-flex align-items-center" style="gap:10px;">
            @if(($client->due_amount ?? 0) > 0)
            <a href="{{ route('client.pay-now') }}" class="bkash-btn-sm d-none d-md-inline-block"><i class="fas fa-credit-card mr-1"></i>Pay ৳{{ number_format($client->due_amount, 0) }}</a>
            @endif
            <div class="text-right">
                <div style="font-size:22px; font-weight:700; line-height:1;">{{ $client->package->download_speed ?? '0' }}</div>
                <small style="font-size:9px; opacity:.8;">Mbps</small>
            </div>
        </div>
    </div>
</div>

{{-- MAIN GRID --}}
<div class="row">
<div class="col-12">

    {{-- STAT CARDS --}}
    <div class="row mb-2">
        {{-- 1. Joined --}}
        <div class="col-6 col-md-3 mb-2 mb-md-0">
            <div class="db-panel db-stat">
                <div class="db-stat-icon" style="background:#ede9fe; color:#7c3aed;"><i class="fas fa-user-plus"></i></div>
                <div style="min-width:0; flex:1;"><div class="db-stat-lbl">Joined</div><div class="db-stat-val" style="font-size:12px; color:#1e3c72;">{{ $client->created_at ? $client->created_at->format('d M Y') : 'N/A' }}</div></div>
            </div>
        </div>
        {{-- 2. Billing Date --}}
        <div class="col-6 col-md-3 mb-2 mb-md-0">
            <div class="db-panel db-stat">
                <div class="db-stat-icon" style="background:#dbeafe; color:#3b82f6;"><i class="fas fa-sync-alt"></i></div>
                <div style="min-width:0; flex:1;"><div class="db-stat-lbl">Billing Date</div><div class="db-stat-val" style="font-size:13px; color:#1e3c72;">{{ $billingStr }}</div></div>
            </div>
        </div>
        {{-- 3. Package (clickable) --}}
        <div class="col-6 col-md-3 mb-2 mb-md-0">
            <div class="db-panel db-stat" onclick="$('#pkgChangeModal').modal('show')" style="cursor:pointer; position:relative;">
                <div class="db-stat-icon" style="background:#d1fae5; color:#10b981;"><i class="fas fa-box"></i></div>
                <div style="min-width:0; flex:1;">
                    <div class="db-stat-lbl">Package <i class="fas fa-exchange-alt" style="font-size:8px; color:#10b981; margin-left:2px;"></i></div>
                    <div class="db-stat-val" style="font-size:12px; color:#1e3c72;">{{ $client->package->name ?? 'N/A' }}</div>
                    @if($pendingPkgRequest)<div style="font-size:8px; color:#f59e0b; margin-top:1px;"><i class="fas fa-clock mr-1"></i>Request Pending</div>@endif
                </div>
            </div>
        </div>
        {{-- 4. Due Amount --}}
        <div class="col-6 col-md-3">
            <div class="db-panel db-stat">
                @if(($client->due_amount ?? 0) > 0)
                <div class="db-stat-icon" style="background:#fee2e2; color:#ef4444;"><i class="fas fa-exclamation-circle"></i></div>
                <div style="min-width:0; flex:1;"><div class="db-stat-lbl">Due Amount</div><div class="db-stat-val" id="dueAmount" style="color:#ef4444;">৳{{ number_format($client->due_amount ?? 0, 0) }}</div></div>
                @else
                <div class="db-stat-icon" style="background:#d1fae5; color:#10b981;"><i class="fas fa-check-circle"></i></div>
                <div style="min-width:0; flex:1;"><div class="db-stat-lbl">Due Amount</div><div class="db-stat-val" id="dueAmount" style="color:#10b981; font-size:12px;">No Due</div></div>
                @endif
            </div>
        </div>
    </div>

    {{-- PAY NOW (mobile only) --}}
    @if(($client->due_amount ?? 0) > 0)
    <div class="d-md-none mb-2">
        <div style="background:#fff; border-left:3px solid #e2136e; border-radius:8px; padding:10px 12px; box-shadow:0 1px 5px rgba(0,0,0,0.06);">
            <div class="d-flex justify-content-between align-items-center">
                <span style="font-weight:600; font-size:12px;"><i class="fas fa-money-bill-wave text-danger mr-1"></i>Payment Due</span>
                <a href="{{ route('client.pay-now') }}" class="bkash-btn-sm"><i class="fas fa-mobile-alt mr-1"></i>Pay ৳{{ number_format($client->due_amount ?? 0, 0) }}</a>
            </div>
        </div>
    </div>
    @endif

    {{-- QUICK ACTIONS --}}
    <div class="row mb-2">
        @foreach([['client.pay-now','fa-credit-card','Pay Bill','#e2136e'],['client.tickets','fa-plus-circle','Ticket','#3b82f6']] as $a)
        <div class="col-6"><a href="{{ route($a[0]) }}" class="db-qa" style="color:{{ $a[3] }};"><i class="fas {{ $a[1] }}"></i><span>{{ $a[2] }}</span></a></div>
        @endforeach
    </div>


    {{-- LIVE BANDWIDTH GRAPH --}}
    <div class="db-panel mb-2">
        <div class="db-ph" style="padding:8px 12px;">
            <span class="db-ph-title">
                <i class="fas fa-chart-area mr-1" style="color:#3b82f6;"></i>Live Bandwidth
                <span style="display:inline-flex;align-items:center;gap:4px;margin-left:6px;font-size:9px;color:#10b981;font-weight:500;">
                    <span style="width:6px;height:6px;background:#10b981;border-radius:50%;display:inline-block;animation:bwPulse 1.5s infinite;"></span>LIVE
                </span>
            </span>
            <span id="bwLastUpdate" style="font-size:9px;color:#94a3b8;"></span>
        </div>
        <div style="padding:4px 10px 8px;">
            <div style="position:relative;height:130px;background:#fff;border:1px solid #e2e8f0;border-radius:8px;overflow:hidden;padding:4px;">
                <canvas id="bwChart"></canvas>
            </div>
            <div style="display:flex;gap:12px;margin-top:6px;padding:0 2px;">
                <div style="display:flex;align-items:center;gap:4px;font-size:10px;font-weight:600;color:#4b5563;">
                    <span style="width:12px;height:12px;border:2px solid #22c55e;background:rgba(34,197,94,0.15);display:inline-block;"></span>Download (Mbps)
                </div>
                <div style="display:flex;align-items:center;gap:4px;font-size:10px;font-weight:600;color:#4b5563;">
                    <span style="width:12px;height:12px;border:2px solid #eab308;background:rgba(234,179,8,0.15);display:inline-block;"></span>Upload (Mbps)
                </div>
            </div>
            <div style="display:flex;margin-top:8px;border-top:1px solid #f1f5f9;padding-top:8px;">
                <div style="flex:1;text-align:center;border-right:1px solid #f1f5f9;padding:0 4px;">
                    <div style="font-size:8px;color:#94a3b8;font-weight:600;text-transform:uppercase;letter-spacing:.4px;">UPTIME</div>
                    <div style="font-size:12px;font-weight:800;color:#1e293b;margin-top:1px;" id="kwUptime">&mdash;</div>
                </div>
                <div style="flex:1;text-align:center;border-right:1px solid #f1f5f9;padding:0 4px;">
                    <div style="font-size:8px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;"><span style="color:#f59e0b;">&#8679;</span> <span style="color:#94a3b8;">UPLOAD</span></div>
                    <div style="font-size:12px;font-weight:800;color:#d97706;margin-top:1px;" id="bwTxKbps">0 <small style="font-size:8px;font-weight:500;color:#94a3b8;">Kbps</small></div>
                </div>
                <div style="flex:1;text-align:center;border-right:1px solid #f1f5f9;padding:0 4px;">
                    <div style="font-size:8px;color:#94a3b8;font-weight:600;text-transform:uppercase;letter-spacing:.4px;">TOTAL UL</div>
                    <div style="font-size:11px;font-weight:800;color:#10b981;margin-top:1px;" id="bwUl">&mdash;</div>
                </div>
                <div style="flex:1;text-align:center;border-right:1px solid #f1f5f9;padding:0 4px;">
                    <div style="font-size:8px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;"><span style="color:#3b82f6;">&#8681;</span> <span style="color:#94a3b8;">DOWNLOAD</span></div>
                    <div style="font-size:12px;font-weight:800;color:#2563eb;margin-top:1px;" id="bwRxKbps">0 <small style="font-size:8px;font-weight:500;color:#94a3b8;">Kbps</small></div>
                </div>
                <div style="flex:1;text-align:center;padding:0 4px;">
                    <div style="font-size:8px;color:#94a3b8;font-weight:600;text-transform:uppercase;letter-spacing:.4px;">TOTAL DL</div>
                    <div style="font-size:11px;font-weight:800;color:#10b981;margin-top:1px;" id="bwDl">&mdash;</div>
                </div>
            </div>
        </div>
    </div>

        {{-- SESSION INFO + SIGNAL --}}
    <div style="display:flex; gap:8px; margin-bottom:8px;">
        <div style="flex:1; min-width:0;">
        <div class="db-panel h-100" style="margin-bottom:0;">
            <div class="db-ph"><span class="db-ph-title"><i class="fas fa-info-circle mr-1 text-primary"></i>Session Info</span></div>
            <div style="padding:8px 12px; font-size:10px;">
                <div class="mb-1"><div class="text-muted" style="font-size:9px;">Uptime</div><div style="font-weight:600; font-size:11px;" id="dbUptime">{{ $client->session_uptime ?? 'N/A' }}</div></div>
                <div class="mb-1"><div class="text-muted" style="font-size:9px;">IP Address</div><div style="font-weight:600; font-size:11px;" id="dbIp">{{ $client->ip_address ?? '—' }}</div></div>
                <div class="mb-1"><div class="text-muted" style="font-size:9px;">MAC</div><div style="font-weight:600; font-size:10px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $client->mac_address ?? 'N/A' }}</div></div>
                <div><div class="text-muted" style="font-size:9px;">Last Logout</div><div style="font-weight:600; font-size:10px;">{{ $client->last_logout ?? 'N/A' }}</div></div>
            </div>
        </div>
        </div>
        <div style="flex:1; min-width:0;">
        <div class="db-panel h-100" style="margin-bottom:0;">
            <div class="db-ph"><span class="db-ph-title"><i class="fas fa-signal mr-1 text-primary"></i>Signal / ONU</span></div>
            <div style="padding:8px 12px; font-size:10px;">
                <div class="mb-1"><div class="text-muted" style="font-size:9px;">ONU Signal (RX)</div><div style="font-weight:600; font-size:11px;">{{ $client->onu_rx_power ?? 'N/A' }} @if($client->onu_rx_power) dBm @endif</div></div>
                <div class="mb-1"><div class="text-muted" style="font-size:9px;">Signal Quality</div>
                    <span class="badge badge-{{ ($client->onu_signal_quality ?? 'unknown') == 'excellent' ? 'success' : (($client->onu_signal_quality ?? 'unknown') == 'good' ? 'info' : 'warning') }}" style="font-size:9px;">{{ ucfirst($client->onu_signal_quality ?? 'Unknown') }}</span>
                </div>
                <div class="mb-1"><div class="text-muted" style="font-size:9px;">ONU Serial</div><div style="font-weight:600; font-size:10px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $client->onu_serial ?? 'N/A' }}</div></div>
                <div><div class="text-muted" style="font-size:9px;">ONU Model</div><div style="font-weight:600; font-size:11px;">{{ $client->onu_model ?? 'N/A' }}</div></div>
            </div>
        </div>
        </div>
    </div>

    {{-- RECENT PAYMENTS --}}
    <div class="db-panel mb-2">
        <div class="db-ph"><span class="db-ph-title"><i class="fas fa-history mr-1 text-success"></i>Recent Payments</span><a href="{{ route('client.payments') }}" class="db-ph-link">View All</a></div>
        <div id="livePaymentsContainer">
            @forelse($recentPayments ?? [] as $payment)
            <div class="db-pr">
                <div><strong style="font-size:13px;">৳{{ number_format($payment->amount ?? 0, 0) }}</strong><small class="text-muted d-block" style="font-size:10px;">{{ $payment->payment_date->format('d-m-Y') }} &middot; {{ $payment->paymentMethod->name ?? 'Cash' }}</small></div>
                <span class="badge badge-success" style="font-size:9px;">Paid</span>
            </div>
            @empty
            <div class="text-center text-muted" style="padding:14px; font-size:12px;"><i class="fas fa-receipt mr-1"></i>No recent payments</div>
            @endforelse
        </div>
    </div>

</div>{{-- /col-md-8 full --}}
</div>{{-- /row --}}

{{-- PACKAGE CHANGE MODAL --}}
<div class="modal fade" id="pkgChangeModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border-radius:14px; border:none;">
            <div class="modal-header" style="background:linear-gradient(135deg,#1e3c72,#2a5298); color:#fff; border-radius:14px 14px 0 0; padding:12px 18px;">
                <h6 class="modal-title mb-0"><i class="fas fa-box mr-2"></i>Package Change Request</h6>
                <button type="button" class="close text-white" data-dismiss="modal" style="opacity:.8;">&times;</button>
            </div>
            <div class="modal-body" style="padding:16px;">
                <div style="background:#f0fdf4; border:1px solid #bbf7d0; border-radius:8px; padding:10px 14px; margin-bottom:14px; font-size:12px;">
                    <div style="color:#15803d; font-weight:600; margin-bottom:2px;"><i class="fas fa-check-circle mr-1"></i>Current Package</div>
                    <div style="font-weight:700; font-size:14px; color:#1e3c72;">{{ $client->package->name ?? 'N/A' }}</div>
                    <div style="color:#64748b;">৳{{ number_format($client->package->price ?? 0, 0) }}/month &middot; {{ $client->package->download_speed ?? '?' }}/{{ $client->package->upload_speed ?? '?' }} {{ $client->package->speed_unit ?? 'Mbps' }}</div>
                </div>
                @if($pendingPkgRequest)
                    <div class="alert alert-warning" style="font-size:12px; padding:10px 14px;"><i class="fas fa-clock mr-1"></i>You already have a pending package change request. Our team will review it shortly.</div>
                @elseif($availablePackages->isEmpty())
                    <div class="alert alert-info" style="font-size:12px; padding:10px 14px;"><i class="fas fa-info-circle mr-1"></i>No other packages available at this time.</div>
                @else
                <form action="{{ route('client.package-change.submit') }}" method="POST">
                    @csrf
                    <div style="font-size:11px; font-weight:600; color:#64748b; margin-bottom:8px; text-transform:uppercase; letter-spacing:.5px;">Select New Package</div>
                    <div class="row">
                        @foreach($availablePackages as $pkg)
                        <div class="col-6 col-md-4 mb-2">
                            <label style="width:100%; cursor:pointer; margin:0;">
                                <input type="radio" name="package_id" value="{{ $pkg->id }}" style="display:none;" class="pkg-radio">
                                <div class="pkg-card" style="border:2px solid #e2e8f0; border-radius:10px; padding:10px; text-align:center; transition:all .18s; background:#fff;">
                                    <div style="font-weight:700; font-size:13px; color:#1e3c72; margin-bottom:2px;">{{ $pkg->name }}</div>
                                    <div style="font-size:16px; font-weight:800; color:#e2136e; margin-bottom:2px;">৳{{ number_format($pkg->price, 0) }}<span style="font-size:10px; font-weight:400; color:#94a3b8;">/mo</span></div>
                                    <div style="font-size:10px; color:#64748b;"><i class="fas fa-tachometer-alt mr-1"></i>{{ $pkg->download_speed }}/{{ $pkg->upload_speed }} {{ $pkg->speed_unit }}</div>
                                </div>
                            </label>
                        </div>
                        @endforeach
                    </div>
                    <div class="mt-2">
                        <label style="font-size:11px; font-weight:600; color:#64748b;">Reason (optional)</label>
                        <textarea name="reason" class="form-control form-control-sm" rows="2" placeholder="Why do you want to change your package?" style="font-size:12px; border-radius:8px; resize:none;"></textarea>
                    </div>
                    <div class="mt-3 d-flex justify-content-end" style="gap:8px;">
                        <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal" style="border-radius:8px; font-size:12px;">Cancel</button>
                        <button type="submit" id="pkgSubmitBtn" class="btn btn-sm" style="background:linear-gradient(135deg,#1e3c72,#2a5298); color:#fff; border-radius:8px; font-size:12px; border:none; opacity:.5; pointer-events:none;" disabled>
                            <i class="fas fa-paper-plane mr-1"></i>Submit Request
                        </button>
                    </div>
                </form>
                @endif
            </div>
        </div>
    </div>
</div>
<style>
.pkg-radio:checked + .pkg-card { border-color:#1e3c72 !important; background:#eff6ff !important; }
.pkg-card:hover { border-color:#93c5fd; }
</style>

@endsection

@push('scripts')
<script>
// ── Live Stats ─────────────────────────────────────────────────────────────
function refreshLiveStats() {
    $.get('{{ route("client.api.live-stats") }}', function(data) {
        if (data.due_amount > 0) {
            $('#dueAmount').text(data.due_formatted).css('color', '#ef4444');
            $('#dueAmount').closest('.db-panel').find('.db-stat-icon').css({'background':'#fee2e2','color':'#ef4444'}).html('<i class="fas fa-exclamation-circle"></i>');
        } else {
            $('#dueAmount').text('No Due').css('color', '#10b981');
            $('#dueAmount').closest('.db-panel').find('.db-stat-icon').css({'background':'#d1fae5','color':'#10b981'}).html('<i class="fas fa-check-circle"></i>');
        }
        if (data.connection_status === 'online') { $('#connectionStatusText').text('Online'); $('.status-card').removeClass('offline').addClass('online'); }
        else { $('#connectionStatusText').text('Offline'); $('.status-card').removeClass('online').addClass('offline'); }
        if(data.recent_payments && data.recent_payments.length > 0) {
            var html = '';
            data.recent_payments.forEach(function(p) {
                html += '<div class="db-pr"><div><strong style="font-size:13px;">'+p.amount_formatted+'</strong><small class="text-muted d-block" style="font-size:10px;">'+p.date+' &middot; '+p.method+'</small></div><span class="badge badge-success" style="font-size:9px;">Paid</span></div>';
            });
            $('#livePaymentsContainer').html(html);
        }
    });
}

function refreshLiveIP() {
    $.get('{{ route("client.live-status") }}', function(d) {
        if (d.ip_address && d.ip_address !== 'Dynamic' && d.ip_address !== '') {
            $('#dbIp').text(d.ip_address);
        } else if (d.ip_address === 'Dynamic' || !d.ip_address) {
            $('#dbIp').text('—');
        }
        if (d.uptime && d.uptime !== 'N/A') $('#dbUptime').text(d.uptime);
    }).fail(function(){});
}

var bwChartObj = null;
var bwLabels = [], bwRxData = [], bwTxData = [];
var BW_MAX = 40;

function initBwChart() {
    var canvas = document.getElementById('bwChart');
    if (!canvas || typeof Chart === 'undefined') return;
    bwChartObj = new Chart(canvas, {
        type: 'line',
        data: {
            labels: bwLabels,
            datasets: [
                {
                    label: 'Download (Mbps)',
                    data: bwRxData,
                    borderColor: '#22c55e',
                    backgroundColor: 'rgba(34,197,94,0.13)',
                    borderWidth: 2,
                    pointRadius: 2,
                    pointBackgroundColor: '#22c55e',
                    tension: 0.4,
                    fill: true
                },
                {
                    label: 'Upload (Mbps)',
                    data: bwTxData,
                    borderColor: '#eab308',
                    backgroundColor: 'rgba(234,179,8,0.10)',
                    borderWidth: 2,
                    pointRadius: 2,
                    pointBackgroundColor: '#eab308',
                    tension: 0.4,
                    fill: true
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: { duration: 300 },
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(255,255,255,0.97)',
                    titleColor: '#374151',
                    bodyColor: '#374151',
                    borderColor: '#e5e7eb',
                    borderWidth: 1,
                    padding: 8,
                    callbacks: {
                        label: function(c) { return ' ' + c.dataset.label + ': ' + c.parsed.y.toFixed(2); }
                    }
                }
            },
            scales: {
                x: {
                    display: true,
                    ticks: {
                        color: '#9ca3af',
                        font: { size: 8 },
                        maxTicksLimit: 8,
                        maxRotation: 0,
                        minRotation: 0,
                        callback: function(val, idx) {
                            var l = this.getLabelForValue(val);
                            return l ? l.substring(0,5) : '';
                        }
                    },
                    grid: { display: false },
                    border: { display: false }
                },
                y: {
                    display: true,
                    min: 0,
                    grid: { color: 'rgba(0,0,0,0.05)', drawBorder: false },
                    ticks: {
                        color: '#9ca3af',
                        font: { size: 8 },
                        maxTicksLimit: 5,
                        callback: function(v) { return v.toFixed(1); }
                    },
                    border: { display: false }
                }
            }
        }
    });
}

function refreshBandwidth() {
    $.get('{{ route("client.api.usage-live") }}', function(d) {
        var now = new Date();
        var ts = now.getHours().toString().padStart(2,'0')+':'+now.getMinutes().toString().padStart(2,'0')+':'+now.getSeconds().toString().padStart(2,'0');
        bwLabels.push(ts);
        bwRxData.push(parseFloat(d.rx_mbps)||0);
        bwTxData.push(parseFloat(d.tx_mbps)||0);
        if (bwLabels.length > BW_MAX) { bwLabels.shift(); bwRxData.shift(); bwTxData.shift(); }
        if (bwChartObj) bwChartObj.update('none');
        var rxK = parseInt(d.rx_kbps)||0;
        var txK = parseInt(d.tx_kbps)||0;
        $('#bwRxKbps').html(rxK+' <small style="font-size:8px;font-weight:500;color:#94a3b8;">Kbps</small>');
        $('#bwTxKbps').html(txK+' <small style="font-size:8px;font-weight:500;color:#94a3b8;">Kbps</small>');
        $('#bwDl').text(parseFloat(d.dl_gb)>0 ? d.dl_gb+' GB' : '—');
        $('#bwUl').text(parseFloat(d.ul_gb)>0 ? d.ul_gb+' GB' : '—');
        if (d.uptime && d.uptime !== 'N/A') $('#kwUptime').text(d.uptime);
        $('#bwLastUpdate').text(ts);
    }).fail(function(){});
}

$(document).ready(function() {
    refreshLiveStats(); setInterval(refreshLiveStats, 30000);
    refreshLiveIP(); setInterval(refreshLiveIP, 60000);
    initBwChart();
    refreshBandwidth(); setInterval(refreshBandwidth, 5000);
});
$(document).on('change','.pkg-radio',function(){
    $('#pkgSubmitBtn').removeAttr('disabled').css({'opacity':'1','pointer-events':'auto'});
});
</script>
@endpush
