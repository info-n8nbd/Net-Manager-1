@extends('layouts.admin')

@section('title', 'Router Detail: ' . $device->name)
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
<li class="breadcrumb-item"><a href="{{ route('admin.network.devices') }}">MikroTik Devices</a></li>
<li class="breadcrumb-item active">{{ $device->name }}</li>
@endsection

@section('content')
<div class="row mb-3">
    <div class="col-12 d-flex flex-wrap align-items-center justify-content-between">
        <div>
            <h4 class="mb-0">
                <i class="fas fa-server mr-2"></i>{{ $device->name }}
                @php
                    $rtBadge = match($device->router_type) { 'core' => 'primary', 'noc' => 'warning', 'pop' => 'success', default => 'info' };
                    $rtLabel = match($device->router_type) { 'noc' => 'NOC', 'pop' => 'POP', default => ucfirst($device->router_type) };
                @endphp
                <span class="badge badge-{{ $rtBadge }} ml-2">{{ $rtLabel }}</span>
                <span class="badge badge-{{ $device->status === 'online' ? 'success' : 'danger' }} ml-1" id="device-status-badge">
                    <i class="fas fa-{{ $device->status === 'online' ? 'check-circle' : 'times-circle' }} mr-1"></i>{{ ucfirst($device->status ?? 'Unknown') }}
                </span>
            </h4>
        </div>
        <div class="mt-2 mt-md-0">
            <button type="button" class="btn btn-info btn-sm" onclick="testConnection()">
                <i class="fas fa-plug mr-1"></i>Test API
            </button>
            @if($__canAction('network', 'edit_router'))
            <a href="{{ route('admin.network.devices.edit', $device) }}" class="btn btn-warning btn-sm">
                <i class="fas fa-edit mr-1"></i>Edit
            </a>
            @endif
            <a href="{{ route('admin.network.devices.ppp-users', $device) }}" class="btn btn-success btn-sm">
                <i class="fas fa-users mr-1"></i>PPP Users
            </a>
            <a href="{{ route('admin.network.devices') }}" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left mr-1"></i>Back
            </a>
        </div>
    </div>
</div>

@php
    $cpuLoad = $routerStatus['success'] ? ($routerStatus['resources']['cpu-load'] ?? $device->cpu_load ?? 0) : ($device->cpu_load ?? 0);
    $totalMem = $routerStatus['success'] ? ($routerStatus['resources']['total-memory'] ?? 1) : 1;
    $freeMem = $routerStatus['success'] ? ($routerStatus['resources']['free-memory'] ?? 0) : 0;
    $memUsage = $totalMem > 0 ? round((($totalMem - $freeMem) / $totalMem) * 100, 1) : ($device->memory_usage ?? 0);
    $uptime = $routerStatus['success'] ? ($routerStatus['resources']['uptime'] ?? $device->uptime ?? 'N/A') : ($device->uptime ?? 'N/A');
    $version = $routerStatus['success'] ? ($routerStatus['resources']['version'] ?? $device->version ?? 'N/A') : ($device->version ?? 'N/A');

    $cpuColor = 'success';
    if ($cpuLoad >= 80) $cpuColor = 'danger';
    elseif ($cpuLoad >= 50) $cpuColor = 'warning';

    $memColor = 'success';
    if ($memUsage >= 80) $memColor = 'danger';
    elseif ($memUsage >= 50) $memColor = 'warning';
@endphp

<div class="row" id="system-info-cards">
    <div class="col-lg-3 col-sm-6">
        <div class="info-box">
            <span class="info-box-icon bg-{{ $cpuColor }}"><i class="fas fa-microchip"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">CPU Load</span>
                <span class="info-box-number" id="cpu-value">{{ $cpuLoad }}%</span>
                <div class="progress">
                    <div class="progress-bar bg-{{ $cpuColor }}" id="cpu-bar" style="width: {{ $cpuLoad }}%"></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-sm-6">
        <div class="info-box">
            <span class="info-box-icon bg-{{ $memColor }}"><i class="fas fa-memory"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Memory Usage</span>
                <span class="info-box-number" id="mem-value">{{ $memUsage }}%</span>
                <div class="progress">
                    <div class="progress-bar bg-{{ $memColor }}" id="mem-bar" style="width: {{ $memUsage }}%"></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-sm-6">
        <div class="info-box">
            <span class="info-box-icon bg-info"><i class="fas fa-clock"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Uptime</span>
                <span class="info-box-number" id="uptime-value">{{ $uptime }}</span>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-sm-6">
        <div class="info-box">
            <span class="info-box-icon bg-primary"><i class="fas fa-code-branch"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">RouterOS Version</span>
                <span class="info-box-number" id="version-value">{{ $version }}</span>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-5">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-info-circle mr-2"></i>Device Information</h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-striped mb-0">
                    <tbody>
                        <tr>
                            <th style="width:40%">Name</th>
                            <td>{{ $device->name }}</td>
                        </tr>
                        <tr>
                            <th>System Identity</th>
                            <td>
                                {{ $device->system_identity ?? 'N/A' }}
                                @if($device->system_identity && $device->system_identity !== $device->name)
                                    <span class="badge badge-warning ml-1" title="System identity does not match device name">
                                        <i class="fas fa-exclamation-triangle mr-1"></i>Mismatch
                                    </span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Router Type</th>
                            <td><span class="badge badge-{{ $rtBadge }}">{{ $rtLabel }}</span></td>
                        </tr>
                        <tr>
                            <th>Model</th>
                            <td>{{ $device->model ?? ($routerStatus['success'] ? ($routerStatus['resources']['board-name'] ?? 'N/A') : 'N/A') }}</td>
                        </tr>
                        <tr>
                            <th>Host:Port</th>
                            <td><code>{{ $device->host }}:{{ $device->port }}</code></td>
                        </tr>
                        <tr>
                            <th>Zone</th>
                            <td>{{ $device->zone->name ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>VPN Account</th>
                            <td>
                                @if($device->vpnAccount)
                                    <span class="badge badge-success"><i class="fas fa-shield-alt mr-1"></i>{{ $device->vpnAccount->name ?? $device->vpnAccount->username }}</span>
                                @else
                                    <span class="text-muted">N/A</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Clients (Total / Active)</th>
                            <td>{{ $clientCount }} / {{ $activeClientCount }}</td>
                        </tr>
                        <tr>
                            <th>Last Checked</th>
                            <td>{{ $device->last_checked_at ? $device->last_checked_at->diffForHumans() : 'Never' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card card-success card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-network-wired mr-2"></i>Interface Status</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-sm btn-info" id="sync-interfaces-btn" onclick="syncInterfaces()">
                        <i class="fas fa-sync-alt mr-1"></i> Sync
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                        <thead class="bg-dark text-white">
                            <tr>
                                <th>Name</th>
                                <th>Type</th>
                                <th>Running</th>
                                <th>Disabled</th>
                                <th>MAC Address</th>
                            </tr>
                        </thead>
                        <tbody id="interface-table-body">
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    <i class="fas fa-sync-alt mr-1"></i> Click <strong>Sync</strong> to load interface status from the router
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function testConnection() {
    Swal.fire({
        title: 'Testing API Connection...',
        text: 'Connecting to MikroTik router via API port',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });

    $.ajax({
        url: '{{ route("admin.network.devices.test", $device) }}',
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        success: function(response) {
            if (response.success) {
                $('#device-status-badge')
                    .removeClass('badge-danger badge-secondary')
                    .addClass('badge-success')
                    .html('<i class="fas fa-check-circle mr-1"></i>Online');
                Swal.fire({
                    icon: 'success',
                    title: 'API: OK',
                    html: response.message,
                    confirmButtonText: 'OK'
                });
            } else {
                $('#device-status-badge')
                    .removeClass('badge-success badge-secondary')
                    .addClass('badge-danger')
                    .html('<i class="fas fa-times-circle mr-1"></i>Offline');
                Swal.fire({
                    icon: 'error',
                    title: 'API: FAIL',
                    html: response.message,
                    confirmButtonText: 'OK'
                });
            }
        },
        error: function(xhr, status) {
            var msg = status === 'timeout'
                ? 'Connection test timed out. The router may be unreachable from this server.'
                : 'Could not reach the billing server. Please check your connection and try again.';
            $('#device-status-badge')
                .removeClass('badge-success badge-secondary')
                .addClass('badge-danger')
                .html('<i class="fas fa-times-circle mr-1"></i>Unreachable');
            Swal.fire({ icon: 'error', title: 'Connection Failed', html: msg, confirmButtonText: 'OK' });
        }
    });
}

function refreshStatus() {
    $.ajax({
        url: '{{ route("admin.network.devices.detail-api", $device) }}',
        method: 'GET',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        beforeSend: function() {
            Swal.fire({
                title: 'Refreshing...',
                text: 'Fetching latest router status',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });
        },
        success: function(data) {
            Swal.close();
            if (data.success) {
                var res = data.resources || {};
                var cpuLoad = parseInt(res['cpu-load']) || 0;
                var totalMem = parseInt(res['total-memory']) || 1;
                var freeMem = parseInt(res['free-memory']) || 0;
                var memUsage = Math.round(((totalMem - freeMem) / totalMem) * 1000) / 10;

                var cpuColor = cpuLoad >= 80 ? 'danger' : (cpuLoad >= 50 ? 'warning' : 'success');
                var memColor = memUsage >= 80 ? 'danger' : (memUsage >= 50 ? 'warning' : 'success');

                $('#cpu-value').text(cpuLoad + '%');
                $('#cpu-bar').css('width', cpuLoad + '%').removeClass('bg-success bg-warning bg-danger').addClass('bg-' + cpuColor);

                $('#mem-value').text(memUsage + '%');
                $('#mem-bar').css('width', memUsage + '%').removeClass('bg-success bg-warning bg-danger').addClass('bg-' + memColor);

                $('#uptime-value').text(res['uptime'] || 'N/A');
                $('#version-value').text(res['version'] || 'N/A');

                $('#device-status-badge')
                    .removeClass('badge-danger badge-secondary')
                    .addClass('badge-success')
                    .html('<i class="fas fa-check-circle mr-1"></i>Online');
            } else {
                $('#device-status-badge')
                    .removeClass('badge-success badge-secondary')
                    .addClass('badge-danger')
                    .html('<i class="fas fa-times-circle mr-1"></i>Offline');
                Swal.fire('Error', 'Could not fetch router status.', 'error');
            }
        },
        error: function(xhr, status) {
            Swal.close();
            var msg = status === 'timeout'
                ? 'Router status refresh timed out. The router may be unreachable from this server.'
                : 'Could not reach the billing server. Please check your connection and try again.';
            $('#device-status-badge')
                .removeClass('badge-success badge-secondary')
                .addClass('badge-danger')
                .html('<i class="fas fa-times-circle mr-1"></i>Unreachable');
            Swal.fire({ icon: 'error', title: 'Connection Failed', html: msg, confirmButtonText: 'OK' });
        }
    });
}

function syncInterfaces() {
    var btn = $('#sync-interfaces-btn');
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Syncing...');

    $.ajax({
        url: '{{ route("admin.network.devices.detail-api", $device) }}',
        method: 'GET',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        success: function(data) {
            btn.prop('disabled', false).html('<i class="fas fa-sync-alt mr-1"></i> Sync');
            if (data.success && data.interfaces && data.interfaces.length > 0) {
                var ifaceHtml = '';
                data.interfaces.forEach(function(iface) {
                    var running = (iface.running === 'true' || iface.running === true);
                    var disabled = (iface.disabled === 'true' || iface.disabled === true);
                    ifaceHtml += '<tr>';
                    ifaceHtml += '<td><strong>' + (iface.name || 'N/A') + '</strong></td>';
                    ifaceHtml += '<td>' + (iface.type || 'N/A') + '</td>';
                    ifaceHtml += '<td><span class="badge badge-' + (running ? 'success' : 'danger') + '"><i class="fas fa-' + (running ? 'check' : 'times') + ' mr-1"></i>' + (running ? 'Yes' : 'No') + '</span></td>';
                    ifaceHtml += '<td><span class="badge badge-' + (disabled ? 'warning' : 'success') + '"><i class="fas fa-' + (disabled ? 'ban' : 'check') + ' mr-1"></i>' + (disabled ? 'Yes' : 'No') + '</span></td>';
                    ifaceHtml += '<td><code>' + (iface['mac-address'] || 'N/A') + '</code></td>';
                    ifaceHtml += '</tr>';
                });
                $('#interface-table-body').html(ifaceHtml);
            } else {
                $('#interface-table-body').html('<tr><td colspan="5" class="text-center text-muted py-3"><i class="fas fa-exclamation-circle mr-1"></i>' + (data.success ? 'No interfaces found on this router' : 'Could not connect to router') + '</td></tr>');
            }
        },
        error: function() {
            btn.prop('disabled', false).html('<i class="fas fa-sync-alt mr-1"></i> Sync');
            $('#interface-table-body').html('<tr><td colspan="5" class="text-center text-danger py-3"><i class="fas fa-times-circle mr-1"></i> Failed to connect. Check router connectivity.</td></tr>');
        }
    });
}
</script>
@endpush
