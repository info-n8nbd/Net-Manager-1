@extends('layouts.client')
@section('title', 'Connection Status')

@push('styles')
<style>
.c-panel { background:#fff; border-radius:10px; box-shadow:0 1px 5px rgba(0,0,0,0.06); margin-bottom:10px; overflow:hidden; }
.c-ph { display:flex; align-items:center; justify-content:space-between; padding:8px 14px; border-bottom:1px solid #f1f5f9; font-size:12px; font-weight:600; color:#1e3c72; }
.c-body { padding:10px 14px; }
.c-field { margin-bottom:8px; }
.c-field small { font-size:9px; color:#94a3b8; display:block; text-transform:uppercase; }
.c-field strong { font-size:12px; color:#1e293b; }
.speed-num { font-size:24px; font-weight:700; color:#1e3c72; line-height:1; }
</style>
@endpush

@section('content')

{{-- STATUS BANNER --}}
<div class="status-card {{ ($client->connection_status ?? 'online') == 'online' ? 'online' : 'offline' }} mb-2" style="padding:9px 14px; border-radius:10px;">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <span style="font-weight:700; font-size:15px;">
                <i class="fas fa-{{ ($client->connection_status ?? 'online') == 'online' ? 'wifi' : 'times-circle' }} mr-2"></i>
                <span id="statusText">{{ ($client->connection_status ?? 'online') == 'online' ? 'Connected' : 'Disconnected' }}</span>
            </span>
            <small class="d-block" style="font-size:10px; opacity:.8; margin-top:1px;">Last updated: <span id="lastUpdate">{{ now()->format('h:i:s A') }}</span></small>
        </div>
        <button class="btn btn-light btn-sm" onclick="refreshStatus()" style="font-size:11px; padding:4px 10px;">
            <i class="fas fa-sync-alt mr-1"></i>Refresh
        </button>
    </div>
</div>

<div class="row">
    {{-- CURRENT SPEED --}}
    <div class="col-md-4 mb-2">
        <div class="c-panel h-100" style="margin-bottom:0;">
            <div class="c-ph"><span><i class="fas fa-tachometer-alt mr-1"></i>Current Speed</span></div>
            <div class="c-body">
                <div class="row text-center">
                    <div class="col-6">
                        <i class="fas fa-arrow-down text-success" style="font-size:14px;"></i>
                        <div class="speed-num" id="downloadSpeed">{{ $client->download_speed ?? '0' }}</div>
                        <small style="font-size:9px; color:#94a3b8;">Mbps DL</small>
                    </div>
                    <div class="col-6">
                        <i class="fas fa-arrow-up text-primary" style="font-size:14px;"></i>
                        <div class="speed-num" id="uploadSpeed">{{ $client->upload_speed ?? '0' }}</div>
                        <small style="font-size:9px; color:#94a3b8;">Mbps UL</small>
                    </div>
                </div>
                <div class="text-center mt-2" style="font-size:10px; color:#94a3b8;">Package: {{ ($client->package->download_speed ?? '') ? ($client->package->download_speed.'/'.$client->package->upload_speed.' '.($client->package->speed_unit ?? 'Mbps')) : 'N/A' }}</div>
            </div>
        </div>
    </div>

    {{-- SESSION INFO --}}
    <div class="col-md-4 mb-2">
        <div class="c-panel h-100" style="margin-bottom:0;">
            <div class="c-ph"><span><i class="fas fa-info-circle mr-1"></i>Session Info</span></div>
            <div class="c-body">
                <div class="row">
                    <div class="col-6 c-field"><small>Uptime</small><strong id="sessionUptime">{{ $client->session_uptime ?? 'N/A' }}</strong></div>
                    <div class="col-6 c-field"><small>IP Address</small><strong id="ipAddress">{{ $client->ip_address ?? 'Dynamic' }}</strong></div>
                    <div class="col-6 c-field"><small>MAC Address</small><strong>{{ $client->mac_address ?? 'N/A' }}</strong></div>
                    <div class="col-6 c-field"><small>Last Logout</small><strong id="lastLogout">{{ $client->last_logout ?? 'N/A' }}</strong></div>
                </div>
            </div>
        </div>
    </div>

    {{-- SIGNAL QUALITY --}}
    <div class="col-md-4 mb-2">
        <div class="c-panel h-100" style="margin-bottom:0;">
            <div class="c-ph"><span><i class="fas fa-signal mr-1"></i>Signal / ONU</span></div>
            <div class="c-body">
                <div class="row">
                    <div class="col-6 c-field"><small>ONU Signal (RX)</small><strong id="rxSignal">{{ $client->onu_rx_power ?? 'N/A' }} dBm</strong></div>
                    <div class="col-6 c-field"><small>Signal Quality</small>
                        <span class="badge badge-{{ ($client->onu_signal_quality ?? 'unknown') == 'excellent' ? 'success' : (($client->onu_signal_quality ?? 'unknown') == 'good' ? 'info' : 'warning') }}" id="signalQuality" style="font-size:9px;">
                            {{ ucfirst($client->onu_signal_quality ?? 'Unknown') }}
                        </span>
                    </div>
                    <div class="col-6 c-field"><small>ONU Serial</small><strong>{{ $client->onu_serial ?? 'N/A' }}</strong></div>
                    <div class="col-6 c-field"><small>ONU Model</small><strong>{{ $client->onu_model ?? 'N/A' }}</strong></div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- DOWNTIME HISTORY --}}
<div class="c-panel">
    <div class="c-ph"><span><i class="fas fa-history mr-1"></i>Downtime History</span></div>
    <div>
        @php
            $downtimes = \DB::table('client_session_logs')
                ->where('client_id', $client->id)
                ->where('event_type', 'disconnect')
                ->orderByDesc('created_at')->take(5)->get();
        @endphp
        @forelse($downtimes as $dt)
        <div class="d-flex justify-content-between" style="padding:7px 14px; border-bottom:1px solid #f8fafc; font-size:11px;">
            <div><i class="fas fa-times-circle text-danger mr-1"></i>{{ \Carbon\Carbon::parse($dt->created_at)->format('d M Y, h:i A') }}</div>
            <small class="text-muted">{{ $dt->reason ?? 'Unknown' }}</small>
        </div>
        @empty
        <div class="text-center text-muted" style="padding:14px; font-size:12px;">
            <i class="fas fa-check-circle text-success mr-1"></i>No recent disconnections
        </div>
        @endforelse
    </div>
</div>
@endsection

@push('scripts')
<script>
function refreshStatus() {
    $.get('{{ route("client.live-status") }}', function(data) {
        $('#statusText').text(data.online ? 'Connected' : 'Disconnected');
        $('#lastUpdate').text(new Date().toLocaleTimeString());
        $('#sessionUptime').text(data.uptime || 'N/A');
        $('#ipAddress').text(data.ip_address || 'Dynamic');
        $('#downloadSpeed').text(data.download_speed || '0');
        $('#uploadSpeed').text(data.upload_speed || '0');
        if(data.online) { $('.status-card').removeClass('offline').addClass('online'); }
        else { $('.status-card').removeClass('online').addClass('offline'); }
    });
}
$(document).ready(function() { refreshStatus(); setInterval(refreshStatus, 30000); });
</script>
@endpush
