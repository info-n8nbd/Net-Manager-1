@extends('layouts.admin')

@section('title', 'Device Monitoring')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.network.devices') }}">Network</a></li>
<li class="breadcrumb-item active">Device Monitoring</li>
@endsection

@section('content')
<div class="row mb-3">
    <div class="col-6 col-md-3 mb-2">
        <div class="stat-box bg-primary text-white text-center py-3 rounded h-100">
            <div class="stat-number" id="totalDevices">{{ $totalDevices ?? 0 }}</div>
            <div class="stat-label">Total</div>
        </div>
    </div>
    <div class="col-6 col-md-3 mb-2">
        <div class="stat-box bg-success text-white text-center py-3 rounded h-100">
            <div class="stat-number" id="onlineDevices">{{ $onlineDevices ?? 0 }}</div>
            <div class="stat-label">Online</div>
        </div>
    </div>
    <div class="col-6 col-md-3 mb-2">
        <div class="stat-box bg-danger text-white text-center py-3 rounded h-100">
            <div class="stat-number" id="offlineDevices">{{ $offlineDevices ?? 0 }}</div>
            <div class="stat-label">Offline</div>
        </div>
    </div>
    <div class="col-6 col-md-3 mb-2">
        <div class="stat-box bg-warning text-dark text-center py-3 rounded h-100">
            <div class="stat-number" id="lastCheck">{{ now()->format('H:i') }}</div>
            <div class="stat-label">Last Check</div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-server mr-2"></i>All Devices</h3>
                <div class="card-tools">
                    <div class="btn-group">
                        <button type="button" class="btn btn-sm btn-outline-primary active" data-filter="all">All</button>
                        <button type="button" class="btn btn-sm btn-outline-success" data-filter="online">Online</button>
                        <button type="button" class="btn btn-sm btn-outline-danger" data-filter="offline">Offline</button>
                    </div>
                </div>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover table-striped">
                    <thead>
                        <tr>
                            <th>Device</th>
                            <th>Type</th>
                            <th>IP Address</th>
                            <th>Status</th>
                            <th>Last Seen</th>
                            <th>Uptime</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="devicesTable">
                        @forelse($devices ?? [] as $device)
                        <tr class="device-row" data-status="{{ $device['status'] ?? 'unknown' }}">
                            <td>
                                <strong>{{ $device['name'] ?? 'Unknown' }}</strong>
                                @if($device['location'] ?? false)
                                <br><small class="text-muted">{{ $device['location'] }}</small>
                                @endif
                            </td>
                            <td>
                                <span class="badge badge-info">{{ ucfirst($device['type'] ?? 'Device') }}</span>
                            </td>
                            <td><code>{{ $device['ip'] ?? 'N/A' }}</code></td>
                            <td>
                                @if(($device['status'] ?? '') === 'online')
                                <span class="badge badge-success"><i class="fas fa-circle mr-1"></i>Online</span>
                                @elseif(($device['status'] ?? '') === 'offline')
                                <span class="badge badge-danger"><i class="fas fa-circle mr-1"></i>Offline</span>
                                @else
                                <span class="badge badge-warning"><i class="fas fa-circle mr-1"></i>Unknown</span>
                                @endif
                            </td>
                            <td>{{ $device['last_seen'] ?? 'Never' }}</td>
                            <td>{{ $device['uptime'] ?? 'N/A' }}</td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-info" title="Ping"><i class="fas fa-wifi"></i></button>
                                    <button type="button" class="btn btn-primary" title="Details"><i class="fas fa-eye"></i></button>
                                    <button type="button" class="btn btn-warning" title="Reboot"><i class="fas fa-redo"></i></button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted">No devices configured. Add MikroTik devices from Network menu.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-danger card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-bell mr-2"></i>Live Notifications</h3>
                <div class="card-tools">
                    <span class="badge badge-danger" id="notifCount">0</span>
                </div>
            </div>
            <div class="card-body p-0" style="max-height: 400px; overflow-y: auto;">
                <ul class="list-group list-group-flush" id="notifications">
                    <li class="list-group-item text-center text-muted">
                        <i class="fas fa-check-circle text-success fa-2x mb-2"></i><br>
                        No alerts at this time
                    </li>
                </ul>
            </div>
        </div>
        
        <div class="card card-info card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-cog mr-2"></i>Monitoring Settings</h3>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label>Check Interval</label>
                    <select class="form-control" id="checkInterval">
                        <option value="30">30 seconds</option>
                        <option value="60" selected>1 minute</option>
                        <option value="300">5 minutes</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Notification Method</label>
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="notifSms" checked>
                        <label class="custom-control-label" for="notifSms">SMS Alert</label>
                    </div>
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="notifTelegram" checked>
                        <label class="custom-control-label" for="notifTelegram">Telegram</label>
                    </div>
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="notifEmail">
                        <label class="custom-control-label" for="notifEmail">Email</label>
                    </div>
                </div>
                <button type="button" class="btn btn-primary btn-block">Save Settings</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('[data-filter]').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var filter = this.dataset.filter;
        document.querySelectorAll('[data-filter]').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        
        document.querySelectorAll('.device-row').forEach(function(row) {
            if (filter === 'all' || row.dataset.status === filter) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });
});

setInterval(function() {
    document.getElementById('lastCheck').textContent = new Date().toLocaleTimeString();
}, 60000);
</script>
@endpush
