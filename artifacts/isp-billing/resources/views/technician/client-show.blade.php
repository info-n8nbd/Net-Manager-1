@extends('layouts.technician')

@section('title', 'Client Profile — ' . $client->name)
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('technician.clients') }}">Clients</a></li>
<li class="breadcrumb-item active">{{ $client->name }}</li>
@endsection

@push('styles')
<style>
:root {
    --cp-primary: #007bff;
    --cp-success: #28a745;
    --cp-danger: #dc3545;
    --cp-warning: #ffc107;
    --cp-info: #17a2b8;
    --cp-dark: #343a40;
    --cp-light: #f8f9fa;
    --cp-border: #e9ecef;
    --cp-text: #495057;
    --cp-text-light: #8898aa;
    --cp-radius: 12px;
    --cp-shadow: 0 2px 12px rgba(0,0,0,0.08);
    --cp-shadow-hover: 0 6px 20px rgba(0,0,0,0.12);
}

.cp-hero {
    background: linear-gradient(135deg, #2a5298 0%, #1e3c72 100%);
    border-radius: var(--cp-radius);
    padding: 18px 22px;
    color: #fff;
    margin-bottom: 14px;
    position: relative;
    overflow: hidden;
    box-shadow: var(--cp-shadow);
}
.cp-hero::before { content: ''; position: absolute; top: -50%; right: -20%; width: 400px; height: 400px; background: rgba(255,255,255,0.05); border-radius: 50%; }
.cp-hero::after { content: ''; position: absolute; bottom: -30%; left: -10%; width: 250px; height: 250px; background: rgba(255,255,255,0.03); border-radius: 50%; }
.cp-hero-content { position: relative; z-index: 1; }
.cp-avatar { width: 56px; height: 56px; border-radius: 50%; border: 3px solid rgba(255,255,255,0.3); display: flex; align-items: center; justify-content: center; font-size: 22px; font-weight: 600; background: rgba(255,255,255,0.15); color: #fff; flex-shrink: 0; }
.cp-avatar img { width: 100%; height: 100%; border-radius: 50%; object-fit: cover; }
.cp-hero-name { font-size: 19px; font-weight: 700; margin-bottom: 1px; }
.cp-hero-meta { font-size: 12px; opacity: 0.85; }
.cp-hero-badges { display: flex; gap: 6px; flex-wrap: wrap; margin-top: 5px; }
.cp-badge { display: inline-flex; align-items: center; gap: 5px; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
.cp-badge-active { background: rgba(40,167,69,0.15); color: #28a745; }
.cp-badge-inactive { background: rgba(220,53,69,0.15); color: #dc3545; }
.cp-badge-online { background: rgba(23,162,184,0.15); color: #17a2b8; }
.cp-badge-offline { background: rgba(255,255,255,0.15); color: rgba(255,255,255,0.7); }
.cp-badge-free { background: rgba(255,193,7,0.2); color: #d39e00; }
.cp-hero-actions { display: flex; gap: 6px; flex-wrap: wrap; margin-top: 8px; }
.cp-hero-actions .btn { border-radius: 6px; font-size: 12px; padding: 4px 10px; border: 1px solid rgba(255,255,255,0.25); color: #fff; background: rgba(255,255,255,0.1); transition: all 0.2s; }
.cp-hero-actions .btn:hover { background: rgba(255,255,255,0.2); color: #fff; transform: translateY(-1px); }

.cp-card { background: #fff; border-radius: 10px; box-shadow: var(--cp-shadow); margin-bottom: 14px; border: 1px solid var(--cp-border); overflow: hidden; }
.cp-card-header { padding: 10px 16px; border-bottom: 1px solid var(--cp-border); display: flex; align-items: center; justify-content: space-between; background: #fff; }
.cp-card-title { font-size: 14px; font-weight: 700; color: var(--cp-dark); display: flex; align-items: center; gap: 6px; margin: 0; }
.cp-card-title i { width: 24px; height: 24px; border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; font-size: 12px; }
.cp-card-body { padding: 12px 16px; }
.cp-card-header.cp-collapsible { cursor: pointer; user-select: none; }
.cp-card-header.cp-collapsible:hover { background: #f8f9fc; }
.cp-collapse-icon { transition: transform 0.25s ease; font-size: 12px; color: var(--cp-text-light); margin-left: 8px; }
.cp-collapse-icon.collapsed { transform: rotate(-90deg); }
.cp-card-collapse { transition: max-height 0.3s ease, opacity 0.2s ease; overflow: hidden; }
.cp-card-collapse.collapsed { max-height: 0 !important; opacity: 0; }

.cp-info-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 0; }
.cp-info-item { display: flex; align-items: flex-start; padding: 6px 12px; border-bottom: 1px solid #f1f3f5; transition: background 0.15s; }
.cp-info-item:hover { background: #f8f9fa; }
.cp-info-label { font-size: 13px; color: var(--cp-text-light); font-weight: 500; min-width: 120px; flex-shrink: 0; }
.cp-info-value { font-size: 13px; color: var(--cp-dark); font-weight: 500; word-break: break-word; }
.cp-info-value a { color: var(--cp-primary); text-decoration: none; }
.cp-info-value a:hover { text-decoration: underline; }
.cp-info-value code { background: #f1f3f5; padding: 2px 8px; border-radius: 4px; font-size: 12px; color: var(--cp-dark); }

.cp-conn-pills { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 12px; }
.cp-conn-pill { flex: 1; min-width: 100px; background: linear-gradient(135deg, #f8f9fa 0%, #fff 100%); border: 1px solid var(--cp-border); border-radius: 8px; padding: 8px 10px; text-align: center; transition: all 0.2s; }
.cp-conn-pill:hover { border-color: var(--cp-primary); box-shadow: 0 2px 8px rgba(0,123,255,0.1); }
.cp-conn-pill-label { font-size: 11px; color: var(--cp-text-light); text-transform: uppercase; letter-spacing: 0.3px; font-weight: 600; }
.cp-conn-pill-value { font-size: 14px; font-weight: 700; color: var(--cp-dark); margin-top: 3px; }

.cp-device-btn { display: flex; align-items: center; gap: 8px; padding: 7px 12px; border-radius: 8px; border: 1px solid var(--cp-border); background: #fff; width: 100%; font-size: 13px; font-weight: 600; transition: all 0.2s; cursor: pointer; color: var(--cp-dark); margin-bottom: 6px; }
.cp-device-btn:hover { border-color: currentColor; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
.cp-device-btn i { font-size: 16px; width: 20px; text-align: center; }
.cp-device-btn.danger { color: var(--cp-danger); }
.cp-device-btn.warning { color: #e67700; }
.cp-device-btn.success { color: #0ca678; }
.cp-device-btn.primary { color: var(--cp-primary); }
.cp-device-btn.secondary { color: #868e96; }
.cp-device-btn.info { color: var(--cp-info); }

.cp-bill-item { border: 1px solid var(--cp-border); border-radius: 8px; overflow: hidden; margin-bottom: 8px; transition: box-shadow 0.2s; }
.cp-bill-item:hover { box-shadow: var(--cp-shadow); }
.cp-bill-header { display: flex; justify-content: space-between; align-items: center; padding: 10px 16px; background: #f8f9fa; border-bottom: 1px solid var(--cp-border); font-size: 13px; }
.cp-bill-payment { display: flex; justify-content: space-between; align-items: center; padding: 8px 16px; border-bottom: 1px solid #f1f3f5; font-size: 12px; }
.cp-bill-payment:last-child { border-bottom: none; }
.cp-bill-paid { border-left: 4px solid var(--cp-success) !important; }
.cp-bill-unpaid { border-left: 4px solid var(--cp-danger) !important; }
.cp-bill-status { font-size: 11px; font-weight: 700; padding: 2px 10px; border-radius: 12px; text-transform: uppercase; letter-spacing: 0.5px; }

.cp-status-timeline { position: relative; padding-left: 24px; }
.cp-status-timeline::before { content: ''; position: absolute; left: 7px; top: 0; bottom: 0; width: 2px; background: #e9ecef; }
.cp-timeline-item { position: relative; padding: 10px 0 10px 16px; }
.cp-timeline-dot { position: absolute; left: -20px; top: 14px; width: 16px; height: 16px; border-radius: 50%; border: 3px solid #fff; box-shadow: 0 0 0 2px #e9ecef; background: #adb5bd; }
.cp-timeline-dot.active { background: var(--cp-success); box-shadow: 0 0 0 2px var(--cp-success); }
.cp-timeline-dot.inactive { background: var(--cp-danger); box-shadow: 0 0 0 2px var(--cp-danger); }

.cp-fiber-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 8px; }
.cp-fiber-item { display: flex; justify-content: space-between; padding: 8px 12px; background: #f8f9fa; border-radius: 6px; font-size: 13px; }
.cp-fiber-label { color: var(--cp-text-light); font-weight: 500; }
.cp-fiber-value { color: var(--cp-dark); font-weight: 600; }

.cp-note-box { padding: 8px 12px; border-radius: 6px; margin-top: 8px; font-size: 12px; display: flex; align-items: flex-start; gap: 10px; }
.cp-note-box i { margin-top: 2px; }
.cp-note-box.warning { background: #fff8e1; border-left: 3px solid var(--cp-warning); }
.cp-note-box.danger { background: #fdf0f0; border-left: 3px solid var(--cp-danger); }
.cp-note-box.default { background: #f8f9fa; border-left: 3px solid #adb5bd; }

.cp-sidebar { position: sticky; top: 80px; }

.cp-sticky-bar {
    position: fixed; top: 0; left: 250px; right: 0; z-index: 1030;
    background: linear-gradient(135deg, #2a5298 0%, #1e3c72 100%);
    color: #fff; padding: 8px 24px; display: flex; align-items: center; justify-content: space-between;
    box-shadow: 0 4px 20px rgba(0,0,0,0.2); transform: translateY(-100%); transition: transform 0.3s ease;
}
.cp-sticky-bar.visible { transform: translateY(0); }
.cp-sticky-bar .sticky-left { display: flex; align-items: center; gap: 12px; }
.cp-sticky-bar .sticky-avatar { width: 36px; height: 36px; border-radius: 50%; background: rgba(255,255,255,0.15); display: flex; align-items: center; justify-content: center; font-size: 15px; font-weight: 600; flex-shrink: 0; }
.cp-sticky-bar .sticky-avatar img { width: 100%; height: 100%; border-radius: 50%; object-fit: cover; }
.cp-sticky-bar .sticky-name { font-size: 15px; font-weight: 700; }
.cp-sticky-bar .sticky-meta { font-size: 11px; opacity: 0.8; }
.cp-sticky-bar .sticky-right { display: flex; align-items: center; gap: 14px; }
.cp-sticky-bar .sticky-stat { text-align: center; }
.cp-sticky-bar .sticky-stat-label { font-size: 9px; text-transform: uppercase; opacity: 0.7; letter-spacing: 0.5px; }
.cp-sticky-bar .sticky-stat-value { font-size: 16px; font-weight: 700; }
.cp-sticky-bar .sticky-actions { display: flex; gap: 6px; margin-left: 10px; }
.cp-sticky-bar .sticky-actions .btn { font-size: 11px; padding: 4px 10px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.25); color: #fff; background: rgba(255,255,255,0.1); }
.cp-sticky-bar .sticky-actions .btn:hover { background: rgba(255,255,255,0.2); color: #fff; }
.sidebar-collapse .cp-sticky-bar { left: 4.6rem; }
@media (max-width: 991px) {
    .cp-sticky-bar { left: 0; }
    .cp-info-grid { grid-template-columns: 1fr; }
    .cp-sidebar { position: static; }
    .cp-fiber-grid { grid-template-columns: 1fr; }
}
@media (max-width: 767px) {
    .cp-sticky-bar { left: 0; padding: 8px 12px; }
    .cp-sticky-bar .sticky-stat { display: none; }
    .cp-sticky-bar .sticky-actions { display: none; }
}
@media (max-width: 575px) {
    .cp-conn-pills { flex-direction: column; }
    .cp-hero-actions { flex-direction: column; }
}
</style>
@endpush

@section('content')
@php
    $billing = $client->getBillingData();
    $clientTotalDue = $billing['due'];
    $remainingAdvance = $billing['advance'];
    $diffObj = $client->connection_date ? $client->connection_date->diff(now()) : null;
    $ageParts = [];
    if ($diffObj) {
        if ($diffObj->y > 0) $ageParts[] = $diffObj->y . 'y';
        if ($diffObj->m > 0) $ageParts[] = $diffObj->m . 'm';
        if ($diffObj->d > 0) $ageParts[] = $diffObj->d . 'd';
    }
    $connectionAge = count($ageParts) > 0 ? implode(' ', $ageParts) : ($diffObj ? 'Today' : 'N/A');
@endphp

<!-- Hero Header -->
<div class="cp-hero">
    <div class="cp-hero-content">
        <div class="d-flex align-items-start flex-wrap" style="gap: 20px;">
            <div class="cp-avatar">
                @if($client->profile_picture)
                    <img src="{{ asset('storage/' . $client->profile_picture) }}" alt="{{ $client->name }}">
                @else
                    {{ strtoupper(substr($client->name, 0, 1)) }}
                @endif
            </div>
            <div class="flex-grow-1">
                <div class="cp-hero-name">{{ $client->name }}</div>
                <div class="cp-hero-meta">
                    <span><i class="fas fa-id-badge mr-1"></i>{{ $client->client_id }}</span>
                    <span class="mx-2">|</span>
                    <span><i class="fas fa-user mr-1"></i>{{ $client->username ?? '-' }}</span>
                    @if($client->phone)
                    <span class="mx-2">|</span>
                    <span><i class="fas fa-phone mr-1"></i>{{ $client->phone }}</span>
                    @endif
                    <span class="mx-2">|</span>
                    @if(isset($isAssignedZone) && $isAssignedZone)
                    <span style="background: rgba(40,167,69,0.2); color: #69db7c; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 600;"><i class="fas fa-map-marked-alt mr-1"></i>Your Zone</span>
                    @else
                    <span style="background: rgba(255,193,7,0.2); color: #e6a817; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 600;"><i class="fas fa-map-marker-alt mr-1"></i>Other Zone — View Only</span>
                    @endif
                </div>
                <div class="cp-hero-badges">
                    <span class="cp-badge {{ $client->status == 'active' ? 'cp-badge-active' : 'cp-badge-inactive' }}">
                        <i class="fas fa-{{ $client->status == 'active' ? 'check-circle' : 'times-circle' }}"></i>
                        {{ ucfirst($client->status) }}
                    </span>
                    <span id="connectionStatusBadge" class="cp-badge {{ $client->connection_status == 'online' ? 'cp-badge-online' : 'cp-badge-offline' }}">
                        <i class="fas fa-wifi"></i>
                        <span id="connectionStatusText">{{ ucfirst($client->connection_status ?? 'offline') }}</span>
                    </span>
                    @if($client->is_free)
                    <span class="cp-badge cp-badge-free"><i class="fas fa-gift"></i>Free Client</span>
                    @endif
                </div>
            </div>
            <div class="ml-auto text-right d-none d-lg-block" style="min-width: 160px;">
                <div style="font-size: 11px; opacity: 0.7; text-transform: uppercase; letter-spacing: 0.5px;">Monthly Bill</div>
                @if($client->is_free)
                <div style="font-size: 22px; font-weight: 700; opacity: 0.9;">Free</div>
                @else
                <div style="font-size: 26px; font-weight: 700;">৳{{ number_format($client->monthly_fee ?? 0) }}</div>
                @endif
                <div style="font-size: 11px; opacity: 0.7;">{{ $client->package->name ?? 'N/A' }} @if($client->package && $client->package->bandwidth)<span style="opacity:0.9;">| <i class="fas fa-tachometer-alt" style="font-size:10px;"></i> {{ $client->package->bandwidth }}</span>@endif</div>
                <div style="margin-top: 6px; font-size: 11px; opacity: 0.8;">
                    <i class="fas fa-calendar-check mr-1"></i>Age: {{ $connectionAge }}
                    <span class="mx-1">|</span>
                    <i class="fas fa-clock mr-1"></i>{{ $client->expire_date ? $client->expire_date->format('d M Y') : 'N/A' }}
                </div>
            </div>
        </div>
        <div class="cp-hero-actions">
            @if(isset($canCollectPayment) && $canCollectPayment && !$client->is_free)
            <button type="button" class="btn" data-toggle="modal" data-target="#billSubmitModal"><i class="fas fa-money-bill-wave mr-1"></i>Collect Payment</button>
            @endif
            @if(($client->google_map_lat && $client->google_map_lng) || $client->google_map_location)
            <a href="{{ $client->google_map_lat ? 'https://www.google.com/maps?q='.$client->google_map_lat.','.$client->google_map_lng : $client->google_map_location }}" target="_blank" class="btn"><i class="fas fa-map-marker-alt mr-1"></i>Map</a>
            @endif
        </div>
    </div>
</div>

<!-- Sticky Bar -->
<div class="cp-sticky-bar" id="cpStickyBar">
    <div class="sticky-left">
        <div class="sticky-avatar">
            @if($client->profile_picture)
                <img src="{{ asset('storage/' . $client->profile_picture) }}" alt="{{ $client->name }}">
            @else
                {{ strtoupper(substr($client->name, 0, 1)) }}
            @endif
        </div>
        <div>
            <div class="sticky-name">{{ $client->name }}</div>
            <div class="sticky-meta">{{ $client->client_id }} | {{ $client->username ?? '-' }}@if($client->phone) | {{ $client->phone }}@endif</div>
        </div>
    </div>
    <div class="sticky-right">
        <div class="sticky-stat">
            <div class="sticky-stat-label">Package</div>
            <div class="sticky-stat-value" style="font-size: 13px;">{{ $client->package->name ?? 'N/A' }}</div>
        </div>
        <div class="sticky-stat">
            <div class="sticky-stat-label">Due</div>
            @if($client->is_free)
            <div class="sticky-stat-value" style="color: #17a2b8;">Free</div>
            @else
            <div class="sticky-stat-value" style="color: {{ $clientTotalDue > 0 ? '#ff6b6b' : '#69db7c' }};">৳{{ number_format($clientTotalDue) }}</div>
            @endif
        </div>
        <div class="sticky-stat">
            <div class="sticky-stat-label">Monthly</div>
            <div class="sticky-stat-value">{{ $client->is_free ? 'Free' : '৳' . number_format($client->monthly_fee ?? 0) }}</div>
        </div>
        <div class="sticky-actions">
            @if(isset($canCollectPayment) && $canCollectPayment && !$client->is_free)
            <button type="button" class="btn" data-toggle="modal" data-target="#billSubmitModal" title="Collect Payment"><i class="fas fa-money-bill-wave"></i></button>
            @endif
        </div>
    </div>
</div>

<div class="row">
    <!-- Left Column -->
    <div class="col-lg-8">
        <!-- Live Monitoring -->
        <div class="cp-card" id="liveMonitorCard">
            <div class="cp-card-header">
                <h3 class="cp-card-title"><i class="fas fa-chart-line" style="background: rgba(40,167,69,0.1); color: var(--cp-success);"></i>Live Monitoring</h3>
                <span id="liveStatus" class="cp-badge cp-badge-offline" style="font-size: 11px;"><i class="fas fa-circle" style="font-size: 8px;"></i> Initializing...</span>
            </div>
            <div class="cp-card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div style="text-align: center; padding: 10px; background: #f8f9fa; border-radius: 8px;">
                            <i class="fas fa-satellite-dish fa-lg mb-1" style="color: var(--cp-info);"></i>
                            <div style="font-size: 11px; color: var(--cp-text-light); margin-bottom: 4px;">IP: <code id="pingIP">{{ $client->session_ip ?: ($client->ip_address ?? 'N/A') }}</code></div>
                            <button class="btn btn-sm" style="background: var(--cp-info); color: #fff; border-radius: 6px; font-size: 11px; padding: 3px 10px;" data-toggle="modal" data-target="#pingModal">
                                <i class="fas fa-satellite-dish mr-1"></i>Open Ping Test
                            </button>
                            <div style="margin-top: 8px; padding-top: 8px; border-top: 1px solid var(--cp-border);">
                                <div style="font-size: 10px; color: var(--cp-text-light); text-transform: uppercase;">Last Disconnect</div>
                                <div style="font-size: 11px; font-weight: 600; color: #e67700; margin-top: 1px;">
                                    {{ $client->last_logout ? \Carbon\Carbon::parse($client->last_logout)->format('d M Y h:i A') : 'Never' }}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <canvas id="bandwidthChart" height="80"></canvas>
                        <div class="d-flex flex-wrap justify-content-around mt-2 text-center" style="gap: 4px;">
                            <div style="min-width: 60px;"><div style="font-size: 10px; color: var(--cp-text-light); text-transform: uppercase;">Uptime</div><div style="font-size: 13px; font-weight: 700;" id="sessionUptime">--</div></div>
                            <div style="min-width: 60px;"><div style="font-size: 10px; color: var(--cp-text-light); text-transform: uppercase;"><i class="fas fa-arrow-up text-warning"></i> Upload</div><div style="font-size: 13px; font-weight: 700; color: #e67700;" id="currentTxDisplay">0 Kbps</div></div>
                            <div style="min-width: 60px;"><div style="font-size: 10px; color: var(--cp-text-light); text-transform: uppercase;">Total UL</div><div style="font-size: 13px; font-weight: 700; color: #e67700;" id="totalTx">0 KB</div></div>
                            <div style="min-width: 60px;"><div style="font-size: 10px; color: var(--cp-text-light); text-transform: uppercase;"><i class="fas fa-arrow-down text-success"></i> Download</div><div style="font-size: 13px; font-weight: 700; color: var(--cp-success);" id="currentRxDisplay">0 Kbps</div></div>
                            <div style="min-width: 60px;"><div style="font-size: 10px; color: var(--cp-text-light); text-transform: uppercase;">Total DL</div><div style="font-size: 13px; font-weight: 700; color: var(--cp-success);" id="totalRx">0 KB</div></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- OLT / ONU Diagnostics -->
        <div class="cp-card">
            <div class="cp-card-header cp-collapsible" onclick="cpToggle(this)">
                <h3 class="cp-card-title"><i class="fas fa-network-wired" style="background: rgba(0,123,255,0.1); color: var(--cp-primary);"></i>OLT / ONU Diagnostics</h3>
                <div style="display:flex;align-items:center;gap:6px;">
                @if($onuRecord ?? null)
                <span id="onuDiagSourceLabel" style="font-size:11px;display:none;"></span>
                <button type="button" class="btn btn-success" id="refreshOnuDiag" style="font-size:11px;padding:2px 10px;font-weight:600;border-radius:6px;"
                    data-onu-id="{{ $onuRecord->id ?? '' }}"
                    onclick="event.stopPropagation();"
                    title="Sync live ONU data from OLT"
                    style="font-size:11px; padding:2px 7px; font-weight:600;">
                    <i class="fas fa-sync-alt" id="refreshOnuIcon"></i><span class="d-none d-md-inline ml-1">ONU Sync</span>
                </button>
                @endif
                <i class="fas fa-chevron-down cp-collapse-icon"></i>
                </div>
            </div>
            <div class="cp-card-collapse collapsed">
            <div class="cp-card-body" style="padding: 0;">
                @if(!$client->onu_serial && !$client->onu_mac && !$client->olt_device_id && !($onuRecord ?? null))
                <div class="text-center py-4" style="color: #999;">
                    <i class="fas fa-network-wired fa-2x mb-2 d-block" style="color: #ccc;"></i>
                    <p class="mb-1" style="font-size: 13px;">No OLT/ONU configured for this client</p>
                    <p class="mb-0" style="font-size: 12px; color: #bbb;">Contact admin to assign OLT/ONU</p>
                </div>
                @else
                @php
                    $ponPort2 = $client->olt_port ?? ($onuRecord->pon_port ?? null);
                    $onuNum2  = $onuRecord->onu_number ?? null;
                    $rxP2 = $onuRecord->rx_power ?? $client->onu_rx_power ?? null;
                    $rxColor2 = $rxP2 !== null ? ($rxP2 >= -15 ? '#28a745' : ($rxP2 >= -20 ? '#17a2b8' : ($rxP2 >= -25 ? '#ffc107' : '#dc3545'))) : '#555';
                    $drText2 = $onuRecord->down_reason ?? null;
                    if($drText2) {
                        $drText2 = strtoupper(str_replace('-', ' ', $drText2));
                        $drBg2 = str_contains($drText2,'POWER') ? '#dc3545' : (str_contains($drText2,'WIRE') ? '#fd7e14' : (str_contains($drText2,'LOS') ? '#e83e8c' : '#6c757d'));
                        $drIcon2 = str_contains($drText2,'POWER') ? 'fa-power-off' : (str_contains($drText2,'WIRE') ? 'fa-cut' : (str_contains($drText2,'LOS') ? 'fa-signal' : 'fa-exclamation-triangle'));
                    }
                @endphp
                <div id="onuDiagTable" style="padding:0;">
                    <div style="display:flex;align-items:center;gap:12px;padding:14px 18px;border-bottom:1px solid #edf2f7;background:#fafbfc;">
                        <div id="onu_status_badge" style="display:inline-flex;align-items:center;gap:7px;padding:6px 16px;border-radius:50px;font-size:12px;font-weight:700;background:{{ ($onuRecord->status ?? null) === 'online' ? 'rgba(40,167,69,0.12)' : (($onuRecord ?? null) ? 'rgba(220,53,69,0.12)' : 'rgba(108,117,125,0.1)') }};color:{{ ($onuRecord->status ?? null) === 'online' ? '#1e7e34' : (($onuRecord ?? null) ? '#b21f2d' : '#6c757d') }};border:1.5px solid {{ ($onuRecord->status ?? null) === 'online' ? 'rgba(40,167,69,0.3)' : (($onuRecord ?? null) ? 'rgba(220,53,69,0.3)' : 'rgba(108,117,125,0.2)') }};">
                            <i class="fas fa-circle" id="onu_status_icon" style="font-size:7px;"></i>
                            <span id="onu_status_val">{{ ($onuRecord->status ?? null) === 'online' ? 'ONLINE' : (($onuRecord ?? null) ? 'OFFLINE' : '—') }}</span>
                        </div>
                        <div style="flex:1;min-width:0;">
                            <div style="font-size:9px;color:#a0aec0;font-weight:700;text-transform:uppercase;letter-spacing:.8px;margin-bottom:2px;">OLT Device</div>
                            <div id="onu_olt_name" style="font-size:15px;font-weight:700;color:#1a202c;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ ($oltDevice->name ?? null) ?: '—' }}</div>
                        </div>
                        @if($onuRecord->updated_at ?? null)
                        <div style="text-align:right;flex-shrink:0;background:#fff;border:1px solid #e2e8f0;border-radius:8px;padding:6px 12px;">
                            <div style="font-size:9px;color:#a0aec0;font-weight:700;text-transform:uppercase;letter-spacing:.6px;">Last Sync</div>
                            <div style="font-size:11px;color:#4a5568;font-weight:600;">{{ \Carbon\Carbon::parse($onuRecord->updated_at)->diffForHumans() }}</div>
                        </div>
                        @endif
                    </div>
                    <div style="display:grid;grid-template-columns:2fr 1.5fr 1fr;border-bottom:1px solid #edf2f7;">
                        <div style="padding:13px 18px;border-right:1px solid #edf2f7;">
                            <div style="font-size:9px;color:#a0aec0;font-weight:700;text-transform:uppercase;letter-spacing:.8px;margin-bottom:5px;display:flex;align-items:center;gap:5px;"><i class="fas fa-microchip" style="color:#cbd5e0;font-size:9px;"></i> MAC / Serial</div>
                            <code id="onu_mac_val" style="font-size:12.5px;color:#2d3748;font-weight:600;background:none;padding:0;word-break:break-all;">{{ $client->onu_mac ?? ($onuRecord->mac_address ?? '—') }}</code>
                        </div>
                        <div style="padding:13px 18px;border-right:1px solid #edf2f7;">
                            <div style="font-size:9px;color:#a0aec0;font-weight:700;text-transform:uppercase;letter-spacing:.8px;margin-bottom:5px;display:flex;align-items:center;gap:5px;"><i class="fas fa-plug" style="color:#cbd5e0;font-size:9px;"></i> PON Port</div>
                            <code id="onu_port_val" style="font-size:13px;color:#553c9a;font-weight:800;background:none;padding:0;">{{ $ponPort2 ?? '—' }}{{ $onuNum2 ? ':' . $onuNum2 : '' }}</code>
                        </div>
                        <div style="padding:13px 18px;">
                            <div style="font-size:9px;color:#a0aec0;font-weight:700;text-transform:uppercase;letter-spacing:.8px;margin-bottom:5px;display:flex;align-items:center;gap:5px;"><i class="fas fa-tag" style="color:#cbd5e0;font-size:9px;"></i> VLan</div>
                            <span id="onu_vlan_val" style="font-size:13px;font-weight:700;color:#2d3748;">{{ ($onuRecord->vlan ?? null) ?: '—' }}</span>
                        </div>
                    </div>
                    @if($drText2 ?? null)
                    <div id="onu_down_reason_val" style="display:flex;align-items:center;gap:10px;padding:10px 18px;background:#fff5f5;border-bottom:1px solid #fed7d7;">
                        <div style="width:30px;height:30px;border-radius:8px;background:rgba(220,53,69,0.12);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="fas {{ $drIcon2 }}" style="color:{{ $drBg2 }};font-size:12px;"></i>
                        </div>
                        <div>
                            <div style="font-size:9px;color:#fc8181;font-weight:700;text-transform:uppercase;letter-spacing:.8px;">Down Reason</div>
                            <div style="font-size:13px;font-weight:700;color:{{ $drBg2 }};">{{ $drText2 }}</div>
                        </div>
                    </div>
                    @else
                    <div id="onu_down_reason_val" style="display:none;"></div>
                    @endif
                    <div style="padding:14px 16px 10px;">
                        <div style="font-size:9px;color:#a0aec0;font-weight:700;text-transform:uppercase;letter-spacing:.8px;margin-bottom:10px;display:flex;align-items:center;gap:6px;"><i class="fas fa-wave-square" style="color:#cbd5e0;"></i> Signal Metrics</div>
                        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-bottom:8px;">
                            <div style="background:#fff;border:1.5px solid {{ $rxColor2 }}30;border-top:3px solid {{ $rxColor2 }};border-radius:10px;padding:12px 10px;text-align:center;">
                                <div style="font-size:9px;color:#a0aec0;font-weight:700;text-transform:uppercase;letter-spacing:.6px;margin-bottom:6px;"><i class="fas fa-arrow-down" style="font-size:8px;"></i> RX Power</div>
                                <div id="onu_rx_val" style="font-size:16px;font-weight:800;color:{{ $rxColor2 }};letter-spacing:-.3px;">{{ $rxP2 !== null ? $rxP2.' dBm' : '—' }}</div>
                            </div>
                            <div style="background:#fff;border:1.5px solid rgba(23,162,184,0.25);border-top:3px solid #17a2b8;border-radius:10px;padding:12px 10px;text-align:center;">
                                <div style="font-size:9px;color:#a0aec0;font-weight:700;text-transform:uppercase;letter-spacing:.6px;margin-bottom:6px;"><i class="fas fa-arrow-up" style="font-size:8px;"></i> TX Power</div>
                                <div id="onu_tx_val" style="font-size:16px;font-weight:800;color:#17a2b8;letter-spacing:-.3px;">{{ ($onuRecord->tx_power ?? null) ? $onuRecord->tx_power.' dBm' : '—' }}</div>
                            </div>
                            <div style="background:#fff;border:1.5px solid rgba(108,117,125,0.2);border-top:3px solid #6c757d;border-radius:10px;padding:12px 10px;text-align:center;">
                                <div style="font-size:9px;color:#a0aec0;font-weight:700;text-transform:uppercase;letter-spacing:.6px;margin-bottom:6px;"><i class="fas fa-bolt" style="font-size:8px;"></i> Bias</div>
                                <div id="onu_txbias_val" style="font-size:16px;font-weight:800;color:#6c757d;letter-spacing:-.3px;">{{ ($onuRecord->tx_bias ?? null) ? $onuRecord->tx_bias.' mA' : '—' }}</div>
                            </div>
                        </div>
                        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;">
                            <div style="background:#fff;border:1.5px solid rgba(74,85,104,0.15);border-top:3px solid #718096;border-radius:10px;padding:12px 10px;text-align:center;">
                                <div style="font-size:9px;color:#a0aec0;font-weight:700;text-transform:uppercase;letter-spacing:.6px;margin-bottom:6px;"><i class="fas fa-ruler" style="font-size:8px;"></i> Distance</div>
                                <div id="onu_distance_val" style="font-size:16px;font-weight:800;color:#4a5568;letter-spacing:-.3px;">{{ ($onuRecord->distance ?? null) ? $onuRecord->distance.' m' : '—' }}</div>
                            </div>
                            <div style="background:#fff;border:1.5px solid rgba(253,126,20,0.25);border-top:3px solid #fd7e14;border-radius:10px;padding:12px 10px;text-align:center;">
                                <div style="font-size:9px;color:#a0aec0;font-weight:700;text-transform:uppercase;letter-spacing:.6px;margin-bottom:6px;"><i class="fas fa-thermometer-half" style="font-size:8px;"></i> Temperature</div>
                                <div id="onu_temp_val" style="font-size:16px;font-weight:800;color:#fd7e14;letter-spacing:-.3px;">{{ ($onuRecord->temperature ?? null) ? $onuRecord->temperature.' °C' : '—' }}</div>
                            </div>
                            <div style="background:#fff;border:1.5px solid rgba(111,66,193,0.2);border-top:3px solid #6f42c1;border-radius:10px;padding:12px 10px;text-align:center;">
                                <div style="font-size:9px;color:#a0aec0;font-weight:700;text-transform:uppercase;letter-spacing:.6px;margin-bottom:6px;"><i class="fas fa-plug" style="font-size:8px;"></i> Voltage</div>
                                <div id="onu_voltage_val" style="font-size:16px;font-weight:800;color:#6f42c1;letter-spacing:-.3px;">{{ ($onuRecord->voltage ?? null) ? $onuRecord->voltage.' V' : '—' }}</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="text-center py-1" style="font-size:11px; color:#999;">
                    <span id="onuDiagSource">{{ ($onuRecord ?? null) ? 'Last cached data' : 'Static data from client record' }}</span>
                    <span id="onuDiagLoading" style="display:none;"><i class="fas fa-spinner fa-spin mr-1"></i>Fetching live data from OLT...</span>
                </div>
                @endif
            </div>
            </div>
        </div>

        <!-- Billing History -->
        <div class="cp-card">
            <div class="cp-card-header cp-collapsible" onclick="cpToggle(this)">
                <h3 class="cp-card-title"><i class="fas fa-file-invoice-dollar" style="background: rgba(40,167,69,0.1); color: var(--cp-success);"></i>Billing History</h3>
                <div style="display: flex; align-items: center; gap: 6px;">
                    <a href="{{ route('technician.clients.payment-history', $client) }}" style="font-size: 12px; color: var(--cp-primary); font-weight: 600; text-decoration: none;">View All <i class="fas fa-arrow-right" style="font-size: 10px;"></i></a>
                    <i class="fas fa-chevron-down cp-collapse-icon collapsed"></i>
                </div>
            </div>
            <div class="cp-card-collapse collapsed">
            <div class="cp-card-body">
                @php
                    $historyData = $client->getBillingData();
                    $bills = $historyData['bills'];
                    $billDetails = $historyData['bill_details'];
                    $totalBills = $historyData['total_bills'];
                    $totalPayments = $historyData['total_payments'];
                    $totalDue = $historyData['due'];
                    $remainingAdv = $historyData['advance'];
                    $advancePayments = $historyData['advance_payments'] ?? collect();
                @endphp
                <div class="cp-conn-pills" style="margin-bottom: 16px;">
                    <div class="cp-conn-pill"><div class="cp-conn-pill-label">Total Bills</div><div class="cp-conn-pill-value">৳{{ number_format($totalBills ?? 0) }}</div></div>
                    <div class="cp-conn-pill"><div class="cp-conn-pill-label">Total Paid</div><div class="cp-conn-pill-value" style="color: var(--cp-success);">৳{{ number_format($totalPayments ?? 0) }}</div></div>
                    <div class="cp-conn-pill"><div class="cp-conn-pill-label">Due</div><div class="cp-conn-pill-value" style="color: var(--cp-danger);">৳{{ number_format($totalDue ?? 0) }}</div></div>
                    <div class="cp-conn-pill">
                        <div class="cp-conn-pill-label">Last Payment</div>
                        @php $lastPay = $client->payments->sortByDesc('created_at')->first(); @endphp
                        <div class="cp-conn-pill-value">{{ $lastPay ? $lastPay->created_at->format('d M Y') : 'N/A' }}</div>
                    </div>
                    @if($remainingAdv > 0)
                    <div class="cp-conn-pill"><div class="cp-conn-pill-label">Advance</div><div class="cp-conn-pill-value" style="color: var(--cp-success);">৳{{ number_format($remainingAdv ?? 0) }}</div></div>
                    @endif
                </div>

                @if($bills->count() > 0)
                    @foreach($bills as $bill)
                    @php
                        $detail = $billDetails[$bill->id] ?? null;
                        $advAlloc = (float) ($bill->advance_used ?? ($detail ? $detail['advance_allocated'] : 0));
                        $isPaid = $detail ? $detail['is_paid'] : ($bill->status == 'paid');
                    @endphp
                    <div class="cp-bill-item {{ $isPaid ? 'cp-bill-paid' : 'cp-bill-unpaid' }}">
                        <div class="cp-bill-header">
                            <div>
                                <i class="fas fa-file-invoice mr-1" style="color: var(--cp-primary);"></i>
                                <strong>{{ $bill->bill_month }}</strong>
                                <span style="color: var(--cp-text-light); margin-left: 8px;">৳{{ number_format($bill->total_amount ?? 0) }}</span>
                                <span style="color: var(--cp-text-light); font-size: 11px; margin-left: 8px;">{{ $bill->created_at->format('d M Y h:i A') }}</span>
                            </div>
                            @if($isPaid)
                            <span class="cp-bill-status" style="background: rgba(40,167,69,0.15); color: var(--cp-success);">Paid</span>
                            @else
                            <span class="cp-bill-status" style="background: rgba(220,53,69,0.1); color: var(--cp-danger);">Unpaid</span>
                            @endif
                        </div>
                        @foreach($bill->payments as $payment)
                        <div class="cp-bill-payment">
                            <div>
                                <i class="fas fa-check-circle mr-1" style="color: var(--cp-success);"></i>
                                {{ $payment->payment_date->format('d M Y') }} {{ $payment->created_at->format('h:i A') }}
                                <span style="color: var(--cp-text-light);">{{ $payment->paymentMethod->name ?? 'Cash' }}</span>
                                @if($payment->collectedBy)<span style="color: var(--cp-text-light);">by {{ $payment->collectedBy->name }}</span>@endif
                            </div>
                            <strong style="color: var(--cp-success);">৳{{ number_format($payment->amount ?? 0) }}</strong>
                        </div>
                        @if($payment->transaction_id || $payment->money_receipt_no || $payment->remarks)
                        <div style="padding: 2px 16px 6px 32px; font-size: 11px; display: flex; flex-wrap: wrap; gap: 6px; align-items: center;">
                            @if($payment->money_receipt_no)<span style="background: rgba(108,117,125,0.12); color: #495057; padding: 1px 8px; border-radius: 4px;"><i class="fas fa-receipt"></i> {{ $payment->money_receipt_no }}</span>@endif
                            @if($payment->transaction_id)<span style="background: rgba(23,162,184,0.15); color: var(--cp-info); padding: 1px 8px; border-radius: 4px;"><i class="fas fa-hashtag"></i> {{ $payment->transaction_id }}</span>@endif
                            @if($payment->remarks)<span style="color: var(--cp-text-light);"><i class="fas fa-info-circle mr-1"></i>{{ $payment->remarks }}</span>@endif
                        </div>
                        @endif
                        @endforeach
                        @if($advAlloc > 0)
                        <div class="cp-bill-payment" style="background: #f0f9ff;">
                            <div><i class="fas fa-wallet mr-1" style="color: var(--cp-info);"></i>Advance Applied @if($bill->advance_applied_at)<span style="color: var(--cp-text-light); margin-left: 6px;">({{ $bill->advance_applied_at->format('d M Y h:i A') }})</span>@endif</div>
                            <strong style="color: var(--cp-info);">৳{{ number_format($advAlloc ?? 0) }}</strong>
                        </div>
                        @endif
                        @if($bill->payments->count() == 0 && $advAlloc == 0)
                        <div class="cp-bill-payment" style="color: var(--cp-danger);"><i class="fas fa-exclamation-circle mr-1"></i>No payment yet</div>
                        @endif
                    </div>
                    @endforeach
                @else
                <div style="text-align: center; padding: 30px; color: var(--cp-text-light);">
                    <i class="fas fa-file-invoice fa-2x mb-2" style="opacity: 0.4;"></i>
                    <p style="margin: 0; font-size: 14px;">No bills generated yet</p>
                </div>
                @endif

                @if($advancePayments->count() > 0)
                <div class="cp-bill-item" style="border-left: 4px solid var(--cp-info) !important; margin-top: 16px;">
                    <div class="cp-bill-header">
                        <div><i class="fas fa-wallet mr-1" style="color: var(--cp-info);"></i><strong>Advance Payments</strong> <span style="color: var(--cp-text-light);">(Not linked to any bill)</span></div>
                        <span class="cp-bill-status" style="background: rgba(23,162,184,0.15); color: var(--cp-info);">{{ $advancePayments->count() }} payment(s)</span>
                    </div>
                    @foreach($advancePayments as $payment)
                    <div class="cp-bill-payment">
                        <div>
                            <i class="fas fa-check-circle mr-1" style="color: var(--cp-info);"></i>
                            {{ $payment->payment_date->format('d M Y') }} {{ $payment->created_at->format('h:i A') }}
                            <span style="color: var(--cp-text-light);">{{ $payment->paymentMethod->name ?? 'Cash' }}</span>
                            @if($payment->collectedBy) <span style="color: var(--cp-text-light);">by {{ $payment->collectedBy->name }}</span>@endif
                        </div>
                        <strong style="color: var(--cp-info);">৳{{ number_format($payment->amount ?? 0) }}</strong>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
            </div>
        </div>

        <!-- Status History -->
        <div class="cp-card">
            <div class="cp-card-header cp-collapsible" onclick="cpToggle(this)">
                <h3 class="cp-card-title"><i class="fas fa-history" style="background: rgba(255,193,7,0.15); color: var(--cp-warning);"></i>Status History</h3>
                <i class="fas fa-chevron-down cp-collapse-icon collapsed"></i>
            </div>
            <div class="cp-card-collapse collapsed">
            <div class="cp-card-body">
                @php $statusLogs = $client->statusLogs()->with('changedBy')->orderBy('created_at', 'desc')->take(5)->get(); @endphp
                @if($statusLogs->count() > 0)
                <div class="cp-status-timeline">
                    @foreach($statusLogs as $log)
                    @php
                        $isActive = $log->new_status === 'active';
                        $isInactive = $log->new_status === 'inactive';
                        $isPackageChange = !$isActive && !$isInactive;
                    @endphp
                    <div class="cp-timeline-item">
                        <div class="cp-timeline-dot {{ $isActive ? 'active' : ($isInactive ? 'inactive' : '') }}" @if($isPackageChange) style="background: var(--cp-info);" @endif></div>
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                @if($isPackageChange)
                                <span style="font-size: 13px; font-weight: 700; color: var(--cp-info);"><i class="fas fa-exchange-alt" style="font-size: 11px;"></i> {{ $log->old_status }} → {{ $log->new_status }}</span>
                                @else
                                <span style="font-size: 13px; font-weight: 700; color: {{ $isActive ? 'var(--cp-success)' : 'var(--cp-danger)' }};">{{ ucfirst($log->new_status) }}</span>
                                @endif
                                @if($log->reason)<span style="font-size: 12px; color: var(--cp-text-light); margin-left: 8px;">{{ $log->reason }}</span>@endif
                                <div style="font-size: 11px; color: var(--cp-text-light); margin-top: 2px;">
                                    {{ $log->created_at->format('d M Y, h:i A') }}
                                    <span style="margin-left: 6px;">
                                        @if(($log->source ?? 'manual') === 'auto')
                                            <span style="background: rgba(23,162,184,0.15); color: var(--cp-info); padding: 1px 8px; border-radius: 4px; font-size: 10px; font-weight: 600;">System</span>
                                        @else
                                            by {{ $log->changedBy->name ?? 'Unknown' }}
                                        @endif
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <div style="text-align: center; padding: 30px; color: var(--cp-text-light);">
                    <i class="fas fa-history fa-2x mb-2" style="opacity: 0.4;"></i>
                    <p style="margin: 0; font-size: 14px;">No status changes recorded</p>
                </div>
                @endif
            </div>
            </div>
        </div>

        <!-- Personal & Contact -->
        <div class="cp-card">
            <div class="cp-card-header cp-collapsible" onclick="cpToggle(this)">
                <h3 class="cp-card-title"><i class="fas fa-user" style="background: rgba(0,123,255,0.1); color: var(--cp-primary);"></i>Personal & Contact</h3>
                <i class="fas fa-chevron-down cp-collapse-icon collapsed"></i>
            </div>
            <div class="cp-card-collapse collapsed">
            <div class="cp-info-grid">
                @php $clientNameParts = explode(' ', $client->name, 2); @endphp
                <div class="cp-info-item"><span class="cp-info-label">First Name</span><span class="cp-info-value">{{ $clientNameParts[0] ?? '—' }}</span></div>
                <div class="cp-info-item"><span class="cp-info-label">Last Name</span><span class="cp-info-value">{{ $clientNameParts[1] ?? '—' }}</span></div>
                <div class="cp-info-item"><span class="cp-info-label">Mobile</span><span class="cp-info-value"><a href="tel:{{ $client->phone }}">{{ $client->phone }}</a></span></div>
                <div class="cp-info-item"><span class="cp-info-label">Father's Name</span><span class="cp-info-value">{{ $client->father_husband_name ?? '—' }}</span></div>
                <div class="cp-info-item"><span class="cp-info-label">Alt. Mobile</span><span class="cp-info-value">{{ $client->alt_phone ?? '—' }}</span></div>
                <div class="cp-info-item"><span class="cp-info-label">Email</span><span class="cp-info-value">{{ $client->email ?? '—' }}</span></div>
                <div class="cp-info-item"><span class="cp-info-label">Occupation</span><span class="cp-info-value">{{ $client->occupation ?? '—' }}</span></div>
                <div class="cp-info-item"><span class="cp-info-label">NID Number</span><span class="cp-info-value">{{ $client->nid ?? '—' }}</span></div>
                <div class="cp-info-item"><span class="cp-info-label">Zone</span><span class="cp-info-value">{{ $client->zone->name ?? '—' }}</span></div>
                <div class="cp-info-item"><span class="cp-info-label">Sub Zone</span><span class="cp-info-value">{{ $client->area->name ?? '—' }}</span></div>
                <div class="cp-info-item"><span class="cp-info-label">District</span><span class="cp-info-value">{{ $client->district->name ?? '—' }}</span></div>
                <div class="cp-info-item"><span class="cp-info-label">Thana/Upazila</span><span class="cp-info-value">{{ $client->upazila->name ?? '—' }}</span></div>
                <div class="cp-info-item"><span class="cp-info-label">Full Address</span><span class="cp-info-value">{{ $client->address ?? '—' }}</span></div>
                <div class="cp-info-item"><span class="cp-info-label">Landmark</span><span class="cp-info-value">{{ $client->nearby_landmark ?? '—' }}</span></div>
                <div class="cp-info-item">
                    <span class="cp-info-label">Location</span>
                    <span class="cp-info-value">
                        @if($client->google_map_lat && $client->google_map_lng)
                            <a href="https://www.google.com/maps?q={{ $client->google_map_lat }},{{ $client->google_map_lng }}" target="_blank" style="background: rgba(0,123,255,0.1); color: var(--cp-primary); padding: 3px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 4px; text-decoration: none;"><i class="fas fa-map-marker-alt"></i> View on Map</a>
                        @elseif($client->google_map_location)
                            <a href="{{ $client->google_map_location }}" target="_blank" style="background: rgba(0,123,255,0.1); color: var(--cp-primary); padding: 3px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 4px; text-decoration: none;"><i class="fas fa-map-marker-alt"></i> View on Map</a>
                        @else
                            <span style="color: var(--cp-text-light);">No location set</span>
                        @endif
                    </span>
                </div>
                @if($client->assigned_to)
                <div class="cp-info-item"><span class="cp-info-label">Assigned To</span><span class="cp-info-value">{{ $client->assignedTo->name ?? '—' }}</span></div>
                @endif
            </div>
            @if($client->nid_front_image || $client->nid_back_image)
            <div style="border-top: 1px solid var(--cp-border); padding: 12px 16px;">
                <div style="font-size: 12px; color: var(--cp-text-light); font-weight: 700; text-transform: uppercase; margin-bottom: 10px;"><i class="fas fa-id-card mr-1"></i> NID Documents</div>
                <div class="row">
                    @if($client->nid_front_image)
                    <div class="col-6">
                        <div style="font-size: 11px; color: var(--cp-text-light); font-weight: 600; margin-bottom: 4px;">FRONT SIDE</div>
                        <a href="{{ asset('storage/' . $client->nid_front_image) }}" target="_blank">
                            <img src="{{ asset('storage/' . $client->nid_front_image) }}" class="img-fluid" style="border-radius: 8px; border: 1px solid var(--cp-border); max-height: 140px;">
                        </a>
                    </div>
                    @endif
                    @if($client->nid_back_image)
                    <div class="col-6">
                        <div style="font-size: 11px; color: var(--cp-text-light); font-weight: 600; margin-bottom: 4px;">BACK SIDE</div>
                        <a href="{{ asset('storage/' . $client->nid_back_image) }}" target="_blank">
                            <img src="{{ asset('storage/' . $client->nid_back_image) }}" class="img-fluid" style="border-radius: 8px; border: 1px solid var(--cp-border); max-height: 140px;">
                        </a>
                    </div>
                    @endif
                </div>
            </div>
            @endif
            </div>
        </div>

        <!-- Connection Details -->
        <div class="cp-card">
            <div class="cp-card-header cp-collapsible" onclick="cpToggle(this)">
                <h3 class="cp-card-title"><i class="fas fa-network-wired" style="background: rgba(23,162,184,0.1); color: var(--cp-info);"></i>Connection Details</h3>
                <i class="fas fa-chevron-down cp-collapse-icon collapsed"></i>
            </div>
            <div class="cp-card-collapse collapsed">
            <div class="cp-card-body">
                <div class="cp-conn-pills">
                    <div class="cp-conn-pill">
                        <div class="cp-conn-pill-label">Package</div>
                        <div class="cp-conn-pill-value" style="color: var(--cp-primary);">{{ $client->package->name ?? 'N/A' }}</div>
                    </div>
                    @if($client->package && $client->package->bandwidth)
                    <div class="cp-conn-pill">
                        <div class="cp-conn-pill-label">Bandwidth</div>
                        <div class="cp-conn-pill-value" style="color: var(--cp-info);"><i class="fas fa-tachometer-alt" style="font-size: 11px;"></i> {{ $client->package->bandwidth }}</div>
                    </div>
                    @endif
                    <div class="cp-conn-pill">
                        <div class="cp-conn-pill-label">Type</div>
                        <div class="cp-conn-pill-value">{{ strtoupper($client->connection_type ?? 'PPPoE') }}</div>
                    </div>
                    <div class="cp-conn-pill">
                        <div class="cp-conn-pill-label">Install Date</div>
                        <div class="cp-conn-pill-value">{{ $client->connection_date ? $client->connection_date->format('d M Y') : 'N/A' }}</div>
                    </div>
                    <div class="cp-conn-pill">
                        <div class="cp-conn-pill-label">Billing Date</div>
                        <div class="cp-conn-pill-value">{{ $client->billing_date ?? 1 }}</div>
                    </div>
                    <div class="cp-conn-pill">
                        <div class="cp-conn-pill-label">Pay Deadline</div>
                        @php
            $tcAiDay = min($client->payment_deadline ?? 15, 28);
            $tcNow = \Carbon\Carbon::now('Asia/Dhaka');
            $tcAiDate = $tcNow->day <= $tcAiDay
                ? \Carbon\Carbon::create($tcNow->year, $tcNow->month, $tcAiDay, 0, 0, 0, 'Asia/Dhaka')
                : \Carbon\Carbon::create($tcNow->year, $tcNow->month, $tcAiDay, 0, 0, 0, 'Asia/Dhaka')->addMonthNoOverflow();
            $tcAiFinal = $client->temp_deadline_date
                ? \Carbon\Carbon::parse($client->temp_deadline_date, 'Asia/Dhaka')
                : $tcAiDate;
        @endphp
        <div class="cp-conn-pill-value">{{ $tcAiFinal->format('d-M-Y') }} <small style="opacity:0.7;">(day {{ $client->payment_deadline ?? 15 }})</small></div>
                    </div>
                </div>
                <div class="cp-info-grid">
                    <div class="cp-info-item"><span class="cp-info-label">Username</span><span class="cp-info-value"><code>{{ $client->username }}</code></span></div>
                    <div class="cp-info-item"><span class="cp-info-label">ONU MAC</span><span class="cp-info-value"><code>{{ $client->onu_mac ?? '—' }}</code></span></div>
                    <div class="cp-info-item">
                        <span class="cp-info-label">Password</span>
                        <span class="cp-info-value">
                            <code id="cp-password-masked">••••••••</code>
                            <code id="cp-password-plain" style="display:none;">{{ $client->pppoe_password ?? '—' }}</code>
                            <button type="button" class="btn btn-xs btn-outline-secondary ml-1 toggle-cp-password" style="padding:1px 6px;font-size:11px;" title="Show/Hide Password"><i class="fas fa-eye"></i></button>
                        </span>
                    </div>
                    <div class="cp-info-item"><span class="cp-info-label">ONU Model</span><span class="cp-info-value">{{ $client->onu_model ?? '—' }}</span></div>
                    <div class="cp-info-item"><span class="cp-info-label">MAC Address</span><span class="cp-info-value"><code>{{ $client->mac_address ?? '—' }}</code></span></div>
                    <div class="cp-info-item"><span class="cp-info-label">OLT Port</span><span class="cp-info-value">{{ $client->olt_port ?? '—' }}</span></div>
                    <div class="cp-info-item"><span class="cp-info-label">IP Address</span><span class="cp-info-value"><code>{{ $client->session_ip ?: ($client->ip_address ?? 'Dynamic') }}</code></span></div>
                    <div class="cp-info-item"><span class="cp-info-label">ONU Serial</span><span class="cp-info-value"><code>{{ $client->onu_serial ?? '—' }}</code></span></div>
                    @if($client->router_brand)
                    <div class="cp-info-item"><span class="cp-info-label">Router Brand</span><span class="cp-info-value"><i class="fas fa-wifi mr-1" style="color: var(--cp-info);"></i>{{ $client->router_brand }}</span></div>
                    @endif
                    <div class="cp-info-item"><span class="cp-info-label">RX Power</span><span class="cp-info-value">{{ $client->onu_rx_power ? $client->onu_rx_power . ' dBm' : '—' }}</span></div>
                </div>
            </div>
            </div>
        </div>

        <!-- Installation Details -->
        <div class="cp-card">
            <div class="cp-card-header cp-collapsible" onclick="cpToggle(this)">
                <h3 class="cp-card-title"><i class="fas fa-tools" style="background: rgba(0,123,255,0.1); color: var(--cp-primary);"></i>Installation Details</h3>
                <i class="fas fa-chevron-down cp-collapse-icon collapsed"></i>
            </div>
            <div class="cp-card-collapse collapsed">
            <div class="cp-card-body">
                <div class="cp-conn-pills">
                    <div class="cp-conn-pill">
                        <div class="cp-conn-pill-label">Install Charge</div>
                        <div class="cp-conn-pill-value" style="color: var(--cp-primary);">৳{{ number_format($client->installation_charge ?? 0) }}</div>
                    </div>
                    <div class="cp-conn-pill">
                        <div class="cp-conn-pill-label">Cable Length</div>
                        <div class="cp-conn-pill-value">{{ $client->cable_length_meters ? intval($client->cable_length_meters) . ' m' : '—' }}</div>
                    </div>
                    <div class="cp-conn-pill">
                        <div class="cp-conn-pill-label">ONU Source</div>
                        <div class="cp-conn-pill-value">{{ $client->onu_required ? 'Company' : 'Client' }}</div>
                    </div>
                    <div class="cp-conn-pill">
                        <div class="cp-conn-pill-label">Fiber Source</div>
                        <div class="cp-conn-pill-value">{{ ($client->fiber_required ?? true) ? 'Company' : 'Client' }}</div>
                    </div>
                    <div class="cp-conn-pill">
                        <div class="cp-conn-pill-label">Router Source</div>
                        <div class="cp-conn-pill-value">{{ $client->router_required ? 'Company' : 'Client' }}</div>
                    </div>
                    <div class="cp-conn-pill">
                        <div class="cp-conn-pill-label">Cable Type</div>
                        <div class="cp-conn-pill-value">{{ $client->connection_medium == 'fiber' ? 'Fiber' : 'UTP' }}</div>
                    </div>
                    <div class="cp-conn-pill">
                        <div class="cp-conn-pill-label">Assigned Tech</div>
                        <div class="cp-conn-pill-value">{{ $client->assignedTo->name ?? '—' }}</div>
                    </div>
                </div>

                <div style="font-size: 13px; font-weight: 700; color: var(--cp-dark); margin-bottom: 10px;"><i class="fas fa-project-diagram mr-1" style="color: var(--cp-primary);"></i>Fiber & ONU Info</div>
                <div class="cp-fiber-grid">
                    <div class="cp-fiber-item"><span class="cp-fiber-label">Fiber Brand</span><span class="cp-fiber-value">{{ $client->fiber_vendor ?: '—' }}</span></div>
                    <div class="cp-fiber-item"><span class="cp-fiber-label">Fiber Code</span><span class="cp-fiber-value">{{ $client->fiber_code ?: '—' }}</span></div>
                    <div class="cp-fiber-item"><span class="cp-fiber-label">Core Type</span><span class="cp-fiber-value">{{ $client->fiber_core_type ?: '—' }}</span></div>
                    <div class="cp-fiber-item"><span class="cp-fiber-label">Fiber Color</span>@php $__fcm2=["Blue"=>"#0057B8","Orange"=>"#FF8C00","Green"=>"#28a745","Brown"=>"#8B4513","Slate"=>"#708090","Gray"=>"#808080","White"=>"#e9ecef","Red"=>"#dc3545","Black"=>"#222222","Yellow"=>"#ffc107","Violet"=>"#6f42c1","Rose"=>"#e83e8c","Aqua"=>"#20c997"]; $__fih=$__fcm2[$client->fiber_color]??null; @endphp<span class="cp-fiber-value">@if($__fih)<span style="display:inline-block;width:13px;height:13px;border-radius:3px;border:1px solid rgba(0,0,0,0.2);vertical-align:middle;margin-right:3px;background:{{$__fih}};"></span>@endif{{ $client->fiber_color ?: '—' }}</span></div>
                    <div class="cp-fiber-item"><span class="cp-fiber-label">Splitter No</span><span class="cp-fiber-value">{{ $client->splitter_number ?: '—' }}</span></div>
                    <div class="cp-fiber-item"><span class="cp-fiber-label">Splitter Color</span>@php $__scm2=["Blue"=>"#0057B8","Orange"=>"#FF8C00","Green"=>"#28a745","Brown"=>"#8B4513","Slate"=>"#708090","Gray"=>"#808080","White"=>"#e9ecef","Red"=>"#dc3545","Black"=>"#222222","Yellow"=>"#ffc107","Violet"=>"#6f42c1","Rose"=>"#e83e8c","Aqua"=>"#20c997"]; $__sph=$__scm2[$client->splitter_color]??null; @endphp<span class="cp-fiber-value">@if($__sph)<span style="display:inline-block;width:13px;height:13px;border-radius:3px;border:1px solid rgba(0,0,0,0.2);vertical-align:middle;margin-right:3px;background:{{$__sph}};"></span>@endif{{ $client->splitter_color ?: '—' }}</span></div>
                    <div class="cp-fiber-item"><span class="cp-fiber-label">ONU Brand</span><span class="cp-fiber-value">{{ $client->onu_brand ?: '—' }}</span></div>
                    <div class="cp-fiber-item"><span class="cp-fiber-label">ONU MAC/Serial</span><span class="cp-fiber-value">{{ $client->onu_mac ?: '—' }}</span></div>
                </div>

            </div>
            </div>
        </div>

        <!-- Assigned Products / Fiber / Cable -->
        <div class="cp-card">
            <div class="cp-card-header cp-collapsible" onclick="cpToggle(this)">
                <h3 class="cp-card-title"><i class="fas fa-boxes" style="background: rgba(108,99,255,0.1); color: #6c63ff;"></i>Assigned Products / Fiber / Cable</h3>
                <div style="display: flex; align-items: center; gap: 6px;">
                    <span class="badge badge-primary badge-pill" style="font-size: 11px;">{{ ($assignedProducts ?? collect())->count() + ($fiberUsages ?? collect())->count() + ($fiberOutLogs ?? collect())->count() }}</span>
                    <i class="fas fa-chevron-down cp-collapse-icon"></i>
                </div>
            </div>
            <div class="cp-card-collapse collapsed">
            <div class="cp-card-body">
                @if(($assignedProducts ?? collect())->count() > 0)
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0" style="font-size: 12px;">
                        <thead style="background: #f8f9fa;">
                            <tr><th>#</th><th>Product</th><th>Serial/MAC</th><th class="text-center">Qty</th><th>Date</th><th>Note</th><th>Installed By</th></tr>
                        </thead>
                        <tbody>
                            @foreach($assignedProducts as $idx => $prod)
                            <tr>
                                <td>{{ $idx + 1 }}</td>
                                <td>
                                    <strong>{{ $prod->category }}</strong>
                                    @if($prod->sub_category)
                                    <span class="text-muted">- {{ $prod->sub_category }}</span>
                                    @endif
                                    @if($prod->brand)
                                    <br><small class="text-muted">{{ $prod->brand }} {{ $prod->model }}</small>
                                    @endif
                                </td>
                                <td>
                                    @if($prod->serial_number)
                                    <code class="text-primary">{{ $prod->serial_number }}</code>
                                    @else
                                    <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-center"><span class="badge badge-warning badge-pill">{{ $prod->quantity ?? 1 }}</span></td>
                                <td><small>{{ $prod->updated_at ? $prod->updated_at->format('d M Y') : '—' }}</small></td>
                                <td><small class="text-muted">{{ $prod->notes ?: '—' }}</small></td>
                                <td><small>{{ ($installedByMap[$prod->id] ?? null) ?: '—' }}</small></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div style="text-align: center; padding: 20px; color: var(--cp-text-light);">
                    <i class="fas fa-box-open fa-2x mb-2" style="opacity: 0.4;"></i>
                    <p style="margin: 0; font-size: 13px;">No products assigned</p>
                </div>
                @endif
                @if(($fiberUsages ?? collect())->count() > 0 || ($fiberOutLogs ?? collect())->count() > 0)
                <div style="margin-top: 12px; border-top: 1px solid #eee; padding-top: 10px;">
                    <h6 style="font-size: 12px; font-weight: 700; color: var(--cp-text-light); text-transform: uppercase; margin-bottom: 8px;"><i class="fas fa-project-diagram mr-1"></i>Fiber / Cable Usage</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-0" style="font-size: 12px;">
                            <thead style="background: #f8f9fa;">
                                <tr><th>#</th><th>Fiber Code</th><th>Type</th><th class="text-center">Used (m)</th><th>Date</th><th>Installed By</th></tr>
                            </thead>
                            <tbody>
                                @php $fRow = 0; @endphp
                                @foreach($fiberUsages ?? [] as $fUsage)
                                @php $fRow++; @endphp
                                <tr>
                                    <td>{{ $fRow }}</td>
                                    <td><code class="text-primary">{{ $fUsage->fiberStock->fiber_code ?? '—' }}</code></td>
                                    <td>{{ $fUsage->fiberStock->fiber_type ?? '—' }}</td>
                                    <td class="text-center"><span class="badge badge-info badge-pill">{{ $fUsage->used_meter }} m</span></td>
                                    <td><small>{{ $fUsage->used_date ? $fUsage->used_date->format('d M Y') : '—' }}</small></td>
                                    <td><small>{{ $fUsage->technician->name ?? '—' }}</small></td>
                                </tr>
                                @endforeach
                                @foreach($fiberOutLogs ?? [] as $fLog)
                                @php $fRow++; @endphp
                                <tr>
                                    <td>{{ $fRow }}</td>
                                    <td><code class="text-primary">{{ $fLog->fiber_code ?? '—' }}</code></td>
                                    <td>{{ $fLog->fiber_type ?? '—' }}</td>
                                    <td class="text-center"><span class="badge badge-info badge-pill">{{ $fLog->meter ?? '—' }} m</span></td>
                                    <td><small>{{ $fLog->date ?? '—' }}</small></td>
                                    <td><small>{{ $fLog->technician_name ?? '—' }}</small></td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif
            </div>
            </div>
        </div>

        <!-- Ticket History -->
        <div class="cp-card">
            <div class="cp-card-header cp-collapsible" onclick="cpToggle(this)">
                <h3 class="cp-card-title"><i class="fas fa-ticket-alt" style="background: rgba(111,66,193,0.1); color: #6f42c1;"></i>Ticket History</h3>
                <i class="fas fa-chevron-down cp-collapse-icon"></i>
            </div>
            <div class="cp-card-collapse collapsed">
            <div class="cp-card-body" style="padding: 0;">
                @php
                    $clientTickets = \App\Models\Ticket::with(['category', 'technician'])
                        ->where('client_id', $client->id)->orderBy('created_at', 'desc')->take(10)->get();
                    $tPriorityColors = ['low' => '#17a2b8','medium' => '#007bff','high' => '#ffc107','urgent' => '#dc3545'];
                    $tStatusColors   = ['open' => 'warning','in_progress' => 'info','resolved' => 'success','closed' => 'secondary'];
                @endphp
                @if($clientTickets->count() > 0)
                <div style="overflow-x: auto;">
                    <table style="width: 100%; font-size: 13px; border-collapse: collapse;">
                        <thead>
                            <tr style="background: #f8f9fc; border-bottom: 2px solid #e9ecef;">
                                <th style="padding: 8px 10px;">Ticket</th>
                                <th style="padding: 8px 10px;">Subject</th>
                                <th style="padding: 8px 10px; text-align: center;">Status</th>
                                <th style="padding: 8px 10px;">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($clientTickets as $cTicket)
                            <tr style="border-bottom: 1px solid #f0f0f0;">
                                <td style="padding: 8px 10px; white-space: nowrap;">
                                    <a href="{{ route('technician.tickets.view', $cTicket) }}" style="color: #6f42c1; font-weight: 600; text-decoration: none;">#{{ $cTicket->ticket_no }}</a>
                                </td>
                                <td style="padding: 8px 10px; max-width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{{ $cTicket->subject }}">
                                    {{ Str::limit($cTicket->subject, 25) }}
                                    @if($cTicket->category)<div style="font-size: 11px; color: #999;">{{ $cTicket->category->name }}</div>@endif
                                </td>
                                <td style="padding: 8px 10px; text-align: center;">
                                    <span class="badge badge-{{ $tStatusColors[$cTicket->status] ?? 'secondary' }}" style="font-size: 11px;">{{ ucfirst(str_replace('_',' ',$cTicket->status)) }}</span>
                                </td>
                                <td style="padding: 8px 10px; font-size: 12px; color: #666; white-space: nowrap;">{{ $cTicket->created_at->format('d M Y') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @php $totalClientTickets = \App\Models\Ticket::where('client_id', $client->id)->count(); @endphp
                @if($totalClientTickets > 10)
                <div style="text-align: center; padding: 8px; border-top: 1px solid #f0f0f0;">
                    <a href="{{ route('technician.tickets', ['client_id' => $client->id]) }}" style="font-size: 12px; color: #6f42c1; font-weight: 600; text-decoration: none;">View All {{ $totalClientTickets }} Tickets <i class="fas fa-arrow-right" style="font-size: 10px;"></i></a>
                </div>
                @endif
                @else
                <div style="text-align: center; padding: 30px; color: var(--cp-text-light);">
                    <i class="fas fa-ticket-alt fa-2x mb-2" style="opacity: 0.4;"></i>
                    <p style="margin: 0; font-size: 14px;">No tickets found for this client</p>
                </div>
                @endif
            </div>
            </div>
        </div>

    </div>

    <!-- Right Column / Sidebar -->
    <div class="col-lg-4">
        <div class="cp-sidebar">

            <!-- Billing Summary -->
            <div class="cp-card">
                <div class="cp-card-header">
                    <h3 class="cp-card-title"><i class="fas fa-money-bill-wave" style="background: rgba(40,167,69,0.1); color: var(--cp-success);"></i>Billing Summary</h3>
                </div>
                <div class="cp-card-body" style="padding: 0;">
                    <div style="padding: 14px 20px; border-bottom: 1px solid #f1f3f5; display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-size: 13px; color: var(--cp-text-light);">Monthly Bill</span>
                        <span style="font-size: 15px; font-weight: 700; color: var(--cp-primary);">৳{{ $client->is_free ? '0.00' : number_format($client->monthly_fee ?? 0, 2) }}</span>
                    </div>
                    @if($client->is_free)
                    <div style="padding: 14px 20px; border-bottom: 1px solid #f1f3f5; display: flex; justify-content: space-between; align-items: center; background: #e8f4f8;">
                        <span style="font-size: 13px; font-weight: 600; color: var(--cp-info);"><i class="fas fa-gift mr-1"></i>Monthly Due</span>
                        <span style="background: #17a2b8; color: #fff; padding: 2px 10px; border-radius: 6px; font-size: 11px; font-weight: 700;">FREE</span>
                    </div>
                    @else
                    <div style="padding: 14px 20px; border-bottom: 1px solid #f1f3f5; display: flex; justify-content: space-between; align-items: center; {{ $clientTotalDue > 0 ? 'background: #fff5f5;' : 'background: #f0faf4;' }}">
                        <span style="font-size: 13px; font-weight: 600; color: {{ $clientTotalDue > 0 ? 'var(--cp-danger)' : 'var(--cp-success)' }};">Monthly Due</span>
                        <span style="font-size: 18px; font-weight: 700; color: {{ $clientTotalDue > 0 ? 'var(--cp-danger)' : 'var(--cp-success)' }};">৳{{ number_format($clientTotalDue ?? 0, 2) }}</span>
                    </div>
                    @endif
                    @if($remainingAdvance > 0)
                    <div style="padding: 14px 20px; border-bottom: 1px solid #f1f3f5; display: flex; justify-content: space-between; align-items: center; background: #f0faf4;">
                        <span style="font-size: 13px; font-weight: 600; color: var(--cp-success);">Advance</span>
                        <span style="font-size: 15px; font-weight: 700; color: var(--cp-success);">৳{{ number_format($remainingAdvance ?? 0, 2) }}</span>
                    </div>
                    @endif
                    @php
                        $installCharge = (float)$client->installation_charge ?: (float)$client->signup_fee ?: 0;
                        $installTotalPaid = (float) \App\Models\ClientServiceCharge::where('client_id', $client->id)->where('charge_type', 'Installation Charge')->whereNotNull('paid_by')->where('amount', '>', 0)->sum('amount');
                        $installFullyPaid = $installTotalPaid >= $installCharge && $installCharge > 0;
                        $installRemaining = max(0, $installCharge - $installTotalPaid);
                    @endphp
                    @if($installCharge > 0)
                    <div style="padding: 14px 20px; border-bottom: 1px solid #f1f3f5; {{ $installFullyPaid ? 'background: #f0faf4;' : 'background: #fff5f5;' }}">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="font-size: 13px; font-weight: 600; color: {{ $installFullyPaid ? 'var(--cp-success)' : 'var(--cp-danger)' }};"><i class="fas fa-tools mr-1" style="font-size: 11px;"></i>Install Charge</span>
                            <span style="font-size: 15px; font-weight: 700; color: {{ $installFullyPaid ? 'var(--cp-success)' : 'var(--cp-danger)' }};">
                                ৳{{ number_format($installCharge, 2) }}
                                @if($installFullyPaid)<span style="background: #28a745; color: #fff; padding: 2px 8px; border-radius: 6px; font-size: 10px; font-weight: 700; margin-left: 6px;">PAID</span>@endif
                            </span>
                        </div>
                        @if($installTotalPaid > 0 && !$installFullyPaid)
                        <div style="display: flex; justify-content: space-between; margin-top: 6px; font-size: 12px;">
                            <span style="color: var(--cp-success);"><i class="fas fa-check-circle mr-1"></i>Paid: ৳{{ number_format($installTotalPaid, 2) }}</span>
                            <span style="color: var(--cp-danger); font-weight: 600;">Due: ৳{{ number_format($installRemaining, 2) }}</span>
                        </div>
                        @endif
                    </div>
                    @endif
                </div>
                <div style="padding: 14px;">
                    @if(isset($canCollectPayment) && $canCollectPayment && !$client->is_free)
                    <button type="button" class="btn" data-toggle="modal" data-target="#billSubmitModal" style="margin-bottom: 4px;">
                        <i class="fas fa-money-bill-wave"></i>
                        Collect Payment
                        @if($clientTotalDue > 0)
                        <span style="margin-left: auto; background: rgba(255,255,255,0.9); color: var(--cp-success); padding: 2px 8px; border-radius: 6px; font-size: 11px;">৳{{ number_format($clientTotalDue ?? 0) }}</span>
                        @endif
                    </button>
                    @endif
                </div>
            </div>

            <!-- Remarks -->
            <div class="cp-card">
                <div class="cp-card-header">
                    <h3 class="cp-card-title"><i class="fas fa-sticky-note" style="background: rgba(108,117,125,0.1); color: #6c757d;"></i>Remarks</h3>
                </div>
                <div class="cp-card-body">
                    @if($client->remarks)
                    <p style="font-size: 13px; color: var(--cp-text-dark); margin: 0; white-space: pre-wrap;">{{ $client->remarks }}</p>
                    @else
                    <p style="font-size: 13px; color: var(--cp-text-light); margin: 0; font-style: italic;">No remarks added</p>
                    @endif
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Bill Submit Modal -->
@php
    $modalData = $client->getBillingSummary();
    $modalDue = $modalData['due'];
    $modalRemainAdv = $modalData['advance'];
@endphp
<div class="modal fade" id="billSubmitModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius: 12px; border: none; box-shadow: 0 20px 60px rgba(0,0,0,0.15);">
            <form action="{{ route('technician.collect-payment', $client) }}" method="POST" id="billSubmitForm">
                @csrf
                <input type="hidden" name="redirect_to" value="{{ url()->current() }}">
                <div class="modal-header" style="background: linear-gradient(135deg, #28a745 0%, #20853e 100%); color: #fff; border-radius: 12px 12px 0 0; padding: 14px 20px;">
                    <h5 class="modal-title" style="font-weight: 700;"><i class="fas fa-money-bill-wave mr-2"></i>Collect Payment</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info py-2">
                        <div class="row">
                            <div class="col-6">
                                <strong>Client:</strong> {{ $client->name }}<br>
                                <strong>ID:</strong> {{ $client->client_id }}
                            </div>
                            <div class="col-6 text-right">
                                <strong>Due:</strong> <span class="{{ $modalDue > 0 ? 'text-danger' : 'text-success' }} font-weight-bold">৳{{ number_format($modalDue ?? 0, 2) }}</span>
                                @if($modalRemainAdv > 0)
                                <br><strong>Advance:</strong> <span class="text-success">৳{{ number_format($modalRemainAdv ?? 0, 2) }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-money-bill mr-1"></i> Amount (৳)</label>
                        <input type="number" name="amount" id="bs_amount_tech" class="form-control form-control-lg text-center"
                               required min="1" step="1" value="{{ (int) max(1, $modalDue) }}" onchange="checkTechAmountDiff()" onkeyup="checkTechAmountDiff()">
                        <small class="text-muted">Enter amount collected from client</small>
                        <small id="bs_amount_warning_tech" class="text-warning mt-1" style="display: none;"><i class="fas fa-exclamation-triangle mr-1"></i>Amount differs from due - Note required!</small>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-credit-card mr-1"></i> Payment Method</label>
                        <select name="payment_method_id" class="form-control tech-payment-method-select" required>
                            @foreach(auth()->user()->allowedPaymentMethods() as $method)
                            <option value="{{ $method->id }}" data-name="{{ strtolower($method->name) }}">{{ $method->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="tech-online-payment-fields" style="display: none;">
                        <div class="form-group">
                            <label><i class="fas fa-hashtag mr-1"></i> Transaction ID (TrxID) <span class="text-danger">*</span></label>
                            <input type="text" name="transaction_id" class="form-control tech-trx-id" placeholder="Enter transaction ID">
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-mobile-alt mr-1"></i> <span class="tech-mobile-label">bKash</span> Number <span class="text-danger">*</span></label>
                            <input type="text" name="mobile_payment_number" class="form-control tech-mobile-number" placeholder="Enter the bKash/Nagad/Rocket number" maxlength="15">
                        </div>
                    </div>
                    <div class="form-group tech-cash-receipt-field" style="display: none;">
                        <label><i class="fas fa-receipt mr-1"></i> Money Receipt No.</label>
                        <input type="text" name="money_receipt_no" class="form-control" placeholder="Enter money receipt number">
                    </div>
                    <div class="form-group" id="tech_reason_group" style="display: none;">
                        <label><i class="fas fa-comment-alt mr-1"></i> Reason <span class="text-danger">*</span></label>
                        @php $techPaymentReasons = \App\Models\DropdownOption::getOptions('payment_reasons'); @endphp
                        @if(!empty($techPaymentReasons))
                        <select class="form-control mb-2" onchange="if(this.value){document.getElementById('tech_reason').value=this.value;}">
                            <option value="">-- Select reason --</option>
                            @foreach($techPaymentReasons as $reason)
                            <option value="{{ $reason }}">{{ $reason }}</option>
                            @endforeach
                        </select>
                        @endif
                        <input type="text" name="notes_reason" id="tech_reason" class="form-control" placeholder="Why is the amount different from due?">
                        <small class="text-warning"><i class="fas fa-exclamation-triangle mr-1"></i> Required when amount differs from due</small>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-sticky-note mr-1"></i> Notes</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Payment notes..."></textarea>
                    </div>
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="bs_send_sms_tech" name="send_sms" value="1" checked>
                        <label class="custom-control-label" for="bs_send_sms_tech"><i class="fas fa-sms mr-1"></i> Send SMS notification</label>
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success"><i class="fas fa-check mr-1"></i>Submit Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>

@include('components.payment-receipt-modal')

<!-- Ping Modal -->
<div class="modal fade" id="pingModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content" style="background:rgba(255,255,255,0.97);border-radius:12px;box-shadow:0 8px 40px rgba(0,0,0,0.15);border:1px solid rgba(0,0,0,0.08);">
            <div class="modal-header py-2 px-3" style="border-bottom:1px solid #e9ecef;">
                <h6 class="modal-title"><i class="fas fa-satellite-dish text-info mr-2"></i>Ping Test — <code id="pingModalIP">{{ $client->ip_address ?? 'N/A' }}</code></h6>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body p-3">
                <div class="d-flex align-items-center mb-2" style="gap:6px;">
                    <div class="input-group input-group-sm" style="max-width:100px;">
                        <div class="input-group-prepend"><span class="input-group-text" style="font-size:11px;">Count</span></div>
                        <input type="number" id="pingCount" class="form-control" value="20" min="1" max="50" style="font-size:12px;">
                    </div>
                    <button class="btn btn-sm btn-success" onclick="doPing('client')" id="btnClientPing"><i class="fas fa-desktop mr-1"></i>Client Side</button>
                    <button class="btn btn-sm btn-primary" onclick="doPing('mikrotik')" id="btnMikrotikPing"><i class="fas fa-server mr-1"></i>MikroTik Side</button>
                    <span id="pingSpinner" class="ml-auto" style="display:none;"><i class="fas fa-circle-notch fa-spin text-info"></i></span>
                </div>
                <pre id="pingTerminal" style="background:#f6f8fa;color:#24292f;font-family:'SF Mono','Cascadia Code','Courier New',monospace;font-size:12px;padding:12px 15px;margin:0;min-height:300px;max-height:450px;overflow-y:auto;line-height:1.6;white-space:pre;border:1px solid #d0d7de;border-radius:8px;"></pre>
                <div class="d-flex mt-2 pt-2" style="gap:15px;border-top:1px solid #e9ecef;">
                    <div class="text-center"><small class="text-muted d-block" style="font-size:10px;">MIN</small><strong id="pingMin" class="text-success" style="font-size:14px;">--</strong><small class="text-muted"> ms</small></div>
                    <div class="text-center"><small class="text-muted d-block" style="font-size:10px;">AVG</small><strong id="pingAvg" class="text-primary" style="font-size:14px;">--</strong><small class="text-muted"> ms</small></div>
                    <div class="text-center"><small class="text-muted d-block" style="font-size:10px;">MAX</small><strong id="pingMax" class="text-warning" style="font-size:14px;">--</strong><small class="text-muted"> ms</small></div>
                    <div class="text-center"><small class="text-muted d-block" style="font-size:10px;">LOSS</small><strong id="pingLoss" class="text-success" style="font-size:14px;">0</strong><small class="text-muted"> %</small></div>
                    <div class="text-center ml-auto"><small class="text-muted d-block" style="font-size:10px;">METHOD</small><strong id="pingMethod" class="text-info" style="font-size:12px;">--</strong></div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
var _onuAutoSyncRunning = false;

/* Collapsible cards */
function cpToggle(header) {
    var icon = header.querySelector('.cp-collapse-icon');
    var collapse = header.nextElementSibling;
    if (collapse && collapse.classList.contains('cp-card-collapse')) {
        var isCollapsed = collapse.classList.contains('collapsed');
        if (isCollapsed) {
            collapse.classList.remove('collapsed');
            collapse.style.maxHeight = collapse.scrollHeight + 'px';
            icon && icon.classList.remove('collapsed');
        } else {
            collapse.classList.add('collapsed');
            collapse.style.maxHeight = '0';
            icon && icon.classList.add('collapsed');
        }
    }
}
document.querySelectorAll('.cp-card-collapse:not(.collapsed)').forEach(function(el) {
    el.style.maxHeight = el.scrollHeight + 'px';
});

/* Sticky bar */
var cpHero = document.querySelector('.cp-hero');
var cpStickyBar = document.getElementById('cpStickyBar');
if (cpHero && cpStickyBar) {
    window.addEventListener('scroll', function() {
        cpStickyBar.classList.toggle('visible', cpHero.getBoundingClientRect().bottom < 0);
    });
}

/* Password toggle */
document.querySelectorAll('.toggle-cp-password').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var masked = document.getElementById('cp-password-masked');
        var plain = document.getElementById('cp-password-plain');
        if (masked && plain) {
            var showing = plain.style.display !== 'none';
            masked.style.display = showing ? '' : 'none';
            plain.style.display = showing ? 'none' : '';
            btn.innerHTML = showing ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
        }
    });
});

/* Bill Submit */
var techDueAmount = {{ $modalDue ?? 0 }};
function checkTechAmountDiff() {
    var amount = parseFloat(document.getElementById('bs_amount_tech').value) || 0;
    var isDifferent = Math.abs(amount - techDueAmount) > 0.01;
    document.getElementById('bs_amount_warning_tech').style.display = isDifferent ? 'block' : 'none';
    var rg = document.getElementById('tech_reason_group');
    var ri = document.getElementById('tech_reason');
    if (rg) { rg.style.display = isDifferent ? 'block' : 'none'; }
    if (ri) { ri.required = isDifferent; if (!isDifferent) ri.value = ''; }
}
(function(){
    var mobileMethods = ['bkash', 'nagad', 'rocket'];
    function checkTechPaymentMethod() {
        var sel = $('.tech-payment-method-select');
        var methodName = (sel.find('option:selected').data('name') || '').toLowerCase();
        var isMobile = mobileMethods.some(function(m) { return methodName.indexOf(m) !== -1; });
        if (isMobile) {
            $('.tech-online-payment-fields').show();
            $('.tech-mobile-number').prop('required', true);
            $('.tech-trx-id').prop('required', true);
            $('.tech-mobile-label').text(sel.find('option:selected').text().trim());
        } else {
            $('.tech-online-payment-fields').hide();
            $('.tech-mobile-number').prop('required', false);
            $('.tech-trx-id').prop('required', false);
        }
        $('.tech-cash-receipt-field').toggle(methodName.indexOf('cash') !== -1);
    }
    $('.tech-payment-method-select').on('change', checkTechPaymentMethod);
    checkTechPaymentMethod();
})();

/* Live bandwidth monitoring */
const clientId = {{ $client->id }};
let bandwidthChart = null;
let bandwidthData = { labels: [], rx: [], tx: [] };
let bandwidthInterval = null;
const maxDataPoints = 30;

document.addEventListener('DOMContentLoaded', function() {
    initBandwidthChart();
    startLiveMonitoring();
});

function initBandwidthChart() {
    const ctx = document.getElementById('bandwidthChart').getContext('2d');
    bandwidthChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: bandwidthData.labels,
            datasets: [
                { label: 'Download (Mbps)', data: bandwidthData.rx, borderColor: 'rgba(40,167,69,1)', backgroundColor: 'rgba(40,167,69,0.1)', fill: true, tension: 0.4, pointRadius: 2 },
                { label: 'Upload (Mbps)', data: bandwidthData.tx, borderColor: 'rgba(255,193,7,1)', backgroundColor: 'rgba(255,193,7,0.1)', fill: true, tension: 0.4, pointRadius: 2 }
            ]
        },
        options: {
            responsive: true, maintainAspectRatio: true, animation: { duration: 300 },
            scales: { y: { beginAtZero: true, title: { display: true, text: 'Mbps' } } },
            plugins: { legend: { position: 'top', labels: { boxWidth: 12 } } }
        }
    });
}

function startLiveMonitoring() { fetchBandwidth(); bandwidthInterval = setInterval(fetchBandwidth, 3000); }

function fetchBandwidth() {
    fetch('/technician/clients/' + clientId + '/live-bandwidth')
        .then(r => r.json())
        .then(data => { if (data.success && data.data) { updateBandwidthUI(data); } else { updateLiveStatus(false, data.message || 'Offline'); } })
        .catch(() => updateLiveStatus(false, 'Error'));
}

function updateBandwidthUI(data) {
    const bw = data.data;
    updateLiveStatus(bw.online, bw.online ? 'Live' : 'Offline');
    document.getElementById('currentRxDisplay').textContent = formatSpeed(bw.rx_rate || 0);
    document.getElementById('currentTxDisplay').textContent = formatSpeed(bw.tx_rate || 0);
    document.getElementById('sessionUptime').textContent = bw.uptime || '--';
    document.getElementById('totalRx').textContent = formatBytes(bw.rx_bytes || 0);
    document.getElementById('totalTx').textContent = formatBytes(bw.tx_bytes || 0);
    bandwidthData.labels.push(data.timestamp || new Date().toLocaleTimeString());
    bandwidthData.rx.push(bw.rx_rate || 0);
    bandwidthData.tx.push(bw.tx_rate || 0);
    if (bandwidthData.labels.length > maxDataPoints) { bandwidthData.labels.shift(); bandwidthData.rx.shift(); bandwidthData.tx.shift(); }
    bandwidthChart.update('none');
}

function updateLiveStatus(isOnline, text) {
    const liveStatus = document.getElementById('liveStatus');
    if (isOnline) { liveStatus.innerHTML = `<i class="fas fa-circle" style="font-size:8px; color:var(--cp-success);"></i> ${text}`; liveStatus.className = 'cp-badge cp-badge-online'; }
    else { liveStatus.innerHTML = `<i class="fas fa-circle" style="font-size:8px; color:var(--cp-danger);"></i> ${text}`; liveStatus.className = 'cp-badge cp-badge-offline'; }
    const connBadge = document.getElementById('connectionStatusBadge');
    const connText = document.getElementById('connectionStatusText');
    if (connBadge && connText) {
        if (isOnline) { connBadge.className = 'cp-badge cp-badge-online'; connText.textContent = 'Online'; }
        else { connBadge.className = 'cp-badge cp-badge-offline'; connText.textContent = 'Offline'; }
    }
}

function formatSpeed(mbps) {
    mbps = parseFloat(mbps) || 0; if (mbps === 0) return '0 Kbps';
    var kbps = mbps * 1000; return kbps < 1000 ? parseFloat(kbps.toFixed(0)) + ' Kbps' : parseFloat(mbps.toFixed(2)) + ' Mbps';
}
function formatBytes(bytes) {
    if (!bytes || isNaN(bytes)) return '0 KB';
    var k = 1024, sizes = ['B','KB','MB','GB','TB'];
    var i = Math.floor(Math.log(bytes) / Math.log(k)); if (i < 0) i = 0;
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

window.addEventListener('beforeunload', function() { if (bandwidthInterval) clearInterval(bandwidthInterval); });

/* Ping test */
var pingEventSource = null;
function doPing(source) {
    if (pingEventSource) { pingEventSource.close(); pingEventSource = null; }
    var terminal = document.getElementById('pingTerminal');
    var btnClient = document.getElementById('btnClientPing');
    var btnMikrotik = document.getElementById('btnMikrotikPing');
    var spinner = document.getElementById('pingSpinner');
    var count = parseInt(document.getElementById('pingCount').value) || 20;
    btnClient.disabled = true; btnMikrotik.disabled = true; spinner.style.display = '';
    terminal.textContent = '';
    ['pingMin','pingAvg','pingMax','pingLoss','pingMethod'].forEach(function(id) { document.getElementById(id).textContent = '--'; });
    var url = '/technician/clients/' + clientId + '/ping-stream?source=' + source + '&count=' + count;
    pingEventSource = new EventSource(url);
    pingEventSource.addEventListener('start', function(e) {
        var d = JSON.parse(e.data);
        terminal.textContent = 'Pinging ' + d.ip + ' [' + (source === 'client' ? 'Client Side' : 'MikroTik Side') + '] with ' + d.count + ' packets:\n\n';
        if (d.ip) { document.getElementById('pingIP').textContent = d.ip; document.getElementById('pingModalIP').textContent = d.ip; }
    });
    pingEventSource.addEventListener('reply', function(e) {
        var r = JSON.parse(e.data);
        var seq = String(r.seq || '?').padStart(2, ' ');
        terminal.textContent += r.status === 'reply'
            ? '  ' + seq + '  Reply from ' + (r.ip||'') + (r.port?':'+r.port:'') + ':  bytes=32  time=' + r.time + 'ms' + (r.ttl?'  TTL='+r.ttl:'') + '\n'
            : '  ' + seq + '  Request timed out.\n';
        terminal.scrollTop = terminal.scrollHeight;
    });
    pingEventSource.addEventListener('done', function(e) {
        var d = JSON.parse(e.data);
        terminal.textContent += '\nPing statistics:\n  Packets: Sent=' + d.sent + ', Received=' + d.received + ', Lost=' + d.lost + ' (' + d.packet_loss + '% loss)\n';
        if (d.min !== null) terminal.textContent += 'Round trip: Min=' + d.min + 'ms, Max=' + d.max + 'ms, Avg=' + d.avg + 'ms\n';
        terminal.scrollTop = terminal.scrollHeight;
        document.getElementById('pingMin').textContent = d.min !== null ? d.min : '--';
        document.getElementById('pingAvg').textContent = d.avg !== null ? d.avg : '--';
        document.getElementById('pingMax').textContent = d.max !== null ? d.max : '--';
        var lossEl = document.getElementById('pingLoss');
        lossEl.textContent = d.packet_loss;
        lossEl.style.color = d.packet_loss == 0 ? 'var(--cp-success)' : (d.packet_loss < 50 ? 'var(--cp-warning)' : 'var(--cp-danger)');
        document.getElementById('pingMethod').textContent = d.method === 'tcp' ? 'TCP' : (d.method === 'mikrotik-api' ? 'MikroTik API' : 'ICMP');
        spinner.style.display = 'none'; btnClient.disabled = false; btnMikrotik.disabled = false;
        pingEventSource.close(); pingEventSource = null;
    });
    pingEventSource.addEventListener('error', function(e) {
        try { var d = JSON.parse(e.data); terminal.textContent += '\nError: ' + d.message + '\n'; } catch(ex) {}
        spinner.style.display = 'none'; btnClient.disabled = false; btnMikrotik.disabled = false;
        pingEventSource.close(); pingEventSource = null;
    });
    pingEventSource.onerror = function() {
        spinner.style.display = 'none'; btnClient.disabled = false; btnMikrotik.disabled = false;
        if (pingEventSource) { pingEventSource.close(); pingEventSource = null; }
    };
}
$('#pingModal').on('hidden.bs.modal', function() {
    if (pingEventSource) { pingEventSource.close(); pingEventSource = null; }
});



/* Quick SMS char count */
var qsm = document.getElementById('techSmsMessage');
if (qsm) {
    qsm.addEventListener('input', function() {
        var c = qsm.value.length;
        document.getElementById('techSmsCharCount').textContent = c;
        document.getElementById('techSmsParts').textContent = Math.max(1, Math.ceil(c / 160));
    });
}
$('#techSmsTemplate').on('change', function() { if ($(this).val()) { $('#techSmsMessage').val($(this).val()).trigger('input'); } });

$('#refreshOnuDiag').on('click', function() {
    var onuId = $(this).data('onu-id');
    if (!onuId) { toastr.warning('No ONU record linked. Please contact admin.'); return; }
    var $btn = $(this), $icon = $('#refreshOnuIcon');
    $btn.prop('disabled', true); $icon.addClass('fa-spin');
    $('#onuDiagSource').html('<i class="fas fa-spinner fa-spin text-info mr-1"></i><small class="text-muted">Syncing live data from OLT...</small>').show();
        $.ajax({
        url: '/technician/network/onu/' + onuId + '/live-info?_t=' + Date.now(),
        type: 'GET', cache: false, timeout: 90000,
        success: function(res) {
            $btn.prop('disabled', false); $icon.removeClass('fa-spin');
            if (!res.success || !res.data) { toastr.error('OLT sync failed.'); $('#onuDiagSource').html('<i class="fas fa-exclamation-triangle text-warning mr-1"></i><small>OLT sync failed.</small>'); return; }
            var d = res.data;
            if (d.port) $('#onu_port_val').text(d.port + (d.onu_number ? ':' + d.onu_number : ''));
            if (d.temperature !== null && d.temperature !== undefined) $('#onu_temp_val').text(d.temperature + ' °C');
            if (d.voltage !== null && d.voltage !== undefined) $('#onu_voltage_val').text(d.voltage + ' V');
            if (d.tx_bias !== null && d.tx_bias !== undefined) $('#onu_txbias_val').text(d.tx_bias + ' mA');
            if (d.distance !== null && d.distance !== undefined) $('#onu_distance_val').text(d.distance + ' m');
            if (d.status) {
                if (d.status === 'online') {
                    $('#onu_status_val').html('<span style="font-size:13px;font-weight:700;color:#28a745;white-space:nowrap;"><i class="fas fa-wifi" style="font-size:11px;margin-right:3px;"></i>ONLINE</span>');
                    $('#onu_down_reason_val').html('<span class="text-muted" style="font-size:12px;">—</span>');
                } else {
                    $('#onu_status_val').html('<span style="font-size:13px;font-weight:700;color:#dc3545;white-space:nowrap;"><i class="fas fa-wifi" style="font-size:11px;margin-right:3px;"></i>OFFLINE</span>');
                    var dr = d.down_reason ? d.down_reason.toUpperCase().replace(/-/g,' ') : 'OFFLINE';
                    var drColor = dr.includes('POWER') ? '#dc3545' : (dr.includes('WIRE') ? '#fd7e14' : (dr.includes('LOS') ? '#e83e8c' : '#6c757d'));
                    var drIcon  = dr.includes('POWER') ? 'fa-power-off' : (dr.includes('WIRE') ? 'fa-cut' : (dr.includes('LOS') ? 'fa-signal' : 'fa-exclamation-triangle'));
                    $('#onu_down_reason_val').html('<span style="color:'+drColor+';font-size:12px;font-weight:600;"><i class="fas '+drIcon+' mr-1"></i>'+dr+'</span>');
                }
            }
            if (d.rx_power !== null && d.rx_power !== undefined) {
                var rxColor = d.rx_power >= -15 ? '#28a745' : (d.rx_power >= -20 ? '#17a2b8' : (d.rx_power >= -25 ? '#ffc107' : '#dc3545'));
                $('#onu_rx_val').html('<span style="color:'+rxColor+';font-weight:600;">'+parseFloat(d.rx_power).toFixed(2)+' dBm</span>');
            }
            if (d.tx_power !== null && d.tx_power !== undefined) {
                $('#onu_tx_val').html('<span class="text-primary">'+parseFloat(d.tx_power).toFixed(2)+' dBm</span>');
            }
            var srcLabel = '<i class="fas fa-check-circle text-success mr-1"></i><small>Live OLT data — ' + (new Date().toLocaleTimeString()) + '</small>';
            $('#onuDiagSource').html(srcLabel);
            toastr.success('ONU data synced successfully!');
        },
        error: function() {
            $btn.prop('disabled', false); $icon.removeClass('fa-spin');
            $('#onuDiagSource').html('<i class="fas fa-exclamation-triangle text-warning mr-1"></i><small>OLT sync failed.</small>');
            toastr.error('OLT sync failed. Please try again.');
        }
    });
});

</script>

<!-- Send SMS Modal -->
@if($client->phone)
<div class="modal fade" id="techSmsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius: 12px; border: none; box-shadow: 0 20px 60px rgba(0,0,0,0.15);">
            <form action="{{ route('technician.clients.send-sms', $client) }}" method="POST">
                @csrf
                <div class="modal-header" style="background: linear-gradient(135deg, var(--cp-primary) 0%, #1e3c72 100%); color: #fff; border-radius: 12px 12px 0 0; padding: 14px 20px;">
                    <h5 class="modal-title" style="font-weight: 700;"><i class="fas fa-sms mr-2"></i>Send SMS to {{ $client->name }}</h5>
                    <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body" style="padding: 20px;">
                    <div style="font-size: 12px; color: var(--cp-text-light); margin-bottom: 12px;">Phone: <strong>{{ $client->phone }}</strong></div>
                    <div class="form-group">
                        <label style="font-size: 13px; font-weight: 600;">Message <span class="text-danger">*</span></label>
                        <textarea name="message" class="form-control" rows="4" required id="techSmsMessage" placeholder="Type your message..." style="border-radius: 8px;"></textarea>
                        <small class="text-muted">Characters: <span id="techSmsCharCount">0</span> | SMS parts: <span id="techSmsParts">1</span></small>
                    </div>
                    <div class="form-group">
                        <label style="font-size: 13px; font-weight: 600;">Quick Templates</label>
                        <select class="form-control form-control-sm" id="techSmsTemplate" style="border-radius: 8px;">
                            <option value="">-- Select Template --</option>
                            <option value="Dear {{ $client->name }}, your bill for this month is BDT {{ number_format($client->monthly_fee ?? 0) }}. Please pay before the deadline. Thank you!">Monthly Bill Reminder</option>
                            <option value="Dear {{ $client->name }}, your internet service has been restored. Thank you for your payment!">Service Restored</option>
                            <option value="Dear {{ $client->name }}, your internet service will be suspended due to non-payment. Please pay your dues immediately.">Suspension Warning</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer" style="padding: 12px 20px;">
                    <button type="button" class="btn btn-light" data-dismiss="modal" style="border-radius: 8px;">Cancel</button>
                    <button type="submit" class="btn btn-primary" style="border-radius: 8px;"><i class="fas fa-paper-plane mr-1"></i>Send SMS</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<script>
<script>
jQuery(document).ready(function() {
    var techBillForm = document.getElementById("billSubmitForm");
    if (techBillForm) {
        jQuery(techBillForm).on("submit", function(e) {
            e.preventDefault();
            var formEl = techBillForm;
            var btn = jQuery(formEl).find("[type=submit]");
            btn.prop("disabled", true).text("Processing...");
            jQuery.ajax({
                url: formEl.action,
                method: "POST",
                data: jQuery(formEl).serialize(),
                headers: { "Accept": "application/json" },
                success: function(res) {
                    if (res.success) {
                        jQuery("#billSubmitModal").modal("hide");
                        if (res.thermal_url) window.open(res.thermal_url, "_blank");
                        toastr.success(res.message || "Payment collected!");
                        setTimeout(function() { location.reload(); }, 1500);
                    } else {
                        toastr.error(res.message || "Payment failed.");
                        btn.prop("disabled", false).text("Submit Payment");
                    }
                },
                error: function(xhr) {
                    var msg = xhr.responseJSON ? (xhr.responseJSON.message || "Payment failed.") : "Payment failed.";
                    toastr.error(msg);
                    btn.prop("disabled", false).text("Submit Payment");
                }
            });
        });
    }
});
</script>
@endpush
