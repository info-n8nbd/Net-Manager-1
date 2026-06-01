@extends('layouts.admin')

@section('title', 'Multi-Router Live Panel')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1"><i class="fas fa-network-wired me-2"></i>Multi-Router Live Panel</h4>
            <p class="text-muted mb-0">Monitor and manage all MikroTik routers from one place</p>
        </div>
        <div>
            <button class="btn btn-primary" onclick="syncAllRouters()" id="sync-all-btn">
                <i class="fas fa-sync-alt me-1"></i>Sync All
            </button>
            <button class="btn btn-outline-secondary ms-2" onclick="refreshStatus()">
                <i class="fas fa-redo me-1"></i>Refresh
            </button>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-white-50">Total Routers</h6>
                            <h2 class="mb-0" id="stat-total">{{ $devices->count() }}</h2>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-server fa-2x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-white-50">Online</h6>
                            <h2 class="mb-0" id="stat-online">0</h2>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-check-circle fa-2x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-white-50">Offline</h6>
                            <h2 class="mb-0" id="stat-offline">0</h2>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-times-circle fa-2x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-white-50">Active Users</h6>
                            <h2 class="mb-0" id="stat-users">0</h2>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-users fa-2x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row" id="router-cards">
        @forelse($devices as $device)
        <div class="col-lg-4 col-md-6 mb-4" id="router-{{ $device->id }}">
            <div class="card h-100 router-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0">
                            <span class="status-indicator bg-secondary me-2" id="status-dot-{{ $device->id }}"></span>
                            {{ $device->name }}
                        </h6>
                        <small class="text-muted">{{ $device->host }}</small>
                    </div>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="dropdown">
                            <i class="fas fa-ellipsis-v"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="#" onclick="syncRouter({{ $device->id }})"><i class="fas fa-sync me-2"></i>Sync Now</a></li>
                            <li><a class="dropdown-item" href="#" onclick="viewUsers({{ $device->id }})"><i class="fas fa-users me-2"></i>View Users</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="{{ route('admin.network.devices.edit', $device) }}"><i class="fas fa-cog me-2"></i>Settings</a></li>
                        </ul>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row text-center mb-3">
                        <div class="col-4">
                            <small class="text-muted d-block">Active Users</small>
                            <strong class="h5" id="users-{{ $device->id }}">--</strong>
                        </div>
                        <div class="col-4">
                            <small class="text-muted d-block">CPU</small>
                            <strong class="h5" id="cpu-{{ $device->id }}">--%</strong>
                        </div>
                        <div class="col-4">
                            <small class="text-muted d-block">Memory</small>
                            <strong class="h5" id="mem-{{ $device->id }}">--%</strong>
                        </div>
                    </div>
                    <div class="progress mb-2" style="height: 5px;">
                        <div class="progress-bar bg-primary" id="cpu-bar-{{ $device->id }}" style="width: 0%"></div>
                    </div>
                    <div class="d-flex justify-content-between">
                        <small class="text-muted">Uptime: <span id="uptime-{{ $device->id }}">--</span></small>
                        <small class="text-muted">Last sync: <span id="lastsync-{{ $device->id }}">{{ $device->last_sync_at?->diffForHumans() ?? 'Never' }}</span></small>
                    </div>
                </div>
                <div class="card-footer bg-transparent">
                    <button class="btn btn-sm btn-outline-primary w-100" onclick="syncRouter({{ $device->id }})" id="sync-btn-{{ $device->id }}">
                        <i class="fas fa-sync-alt me-1"></i>Sync
                    </button>
                </div>
            </div>
        </div>
        @empty
        <div class="col-12">
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>No routers configured. 
                <a href="{{ route('admin.network.devices') }}">Add a router</a> to get started.
            </div>
        </div>
        @endforelse
    </div>
</div>

<div class="modal fade" id="usersModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Active Users - <span id="modal-router-name"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="users-loading" class="text-center py-4">
                    <div class="spinner-border text-primary"></div>
                    <p class="mt-2">Loading users...</p>
                </div>
                <div id="users-list" class="d-none">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Username</th>
                                    <th>IP Address</th>
                                    <th>Uptime</th>
                                    <th>MAC</th>
                                </tr>
                            </thead>
                            <tbody id="users-table-body"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.status-indicator {
    display: inline-block;
    width: 10px;
    height: 10px;
    border-radius: 50%;
}
.router-card {
    transition: all 0.3s ease;
}
.router-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
.router-card.online .card-header {
    border-left: 3px solid #28a745;
}
.router-card.offline .card-header {
    border-left: 3px solid #dc3545;
}
</style>
@endsection

@push('scripts')
<script>
let autoRefreshInterval = null;

document.addEventListener('DOMContentLoaded', function() {
    refreshStatus();
    autoRefreshInterval = setInterval(refreshStatus, 60000);
});

function refreshStatus() {
    fetch('{{ route("admin.multi-router.status") }}')
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                updateStats(data.devices);
                data.devices.forEach(updateDeviceCard);
            }
        })
        .catch(e => console.error('Status refresh failed:', e));
}

function updateStats(devices) {
    let online = 0, offline = 0, users = 0;
    devices.forEach(d => {
        if (d.status === 'online') online++;
        else offline++;
        users += d.active_users || 0;
    });
    document.getElementById('stat-online').textContent = online;
    document.getElementById('stat-offline').textContent = offline;
    document.getElementById('stat-users').textContent = users;
}

function updateDeviceCard(device) {
    const card = document.getElementById('router-' + device.id);
    if (!card) return;
    
    const cardEl = card.querySelector('.router-card');
    const dot = document.getElementById('status-dot-' + device.id);
    
    cardEl.classList.remove('online', 'offline');
    cardEl.classList.add(device.status === 'online' ? 'online' : 'offline');
    
    dot.classList.remove('bg-success', 'bg-danger', 'bg-secondary');
    dot.classList.add(device.status === 'online' ? 'bg-success' : 'bg-danger');
    
    document.getElementById('users-' + device.id).textContent = device.active_users ?? '--';
    document.getElementById('cpu-' + device.id).textContent = (device.cpu_load ?? '--') + '%';
    document.getElementById('mem-' + device.id).textContent = (device.memory_usage ?? '--') + '%';
    document.getElementById('uptime-' + device.id).textContent = device.uptime ?? '--';
    document.getElementById('lastsync-' + device.id).textContent = device.last_sync ?? 'Never';
    
    const cpuBar = document.getElementById('cpu-bar-' + device.id);
    cpuBar.style.width = (device.cpu_load || 0) + '%';
    cpuBar.classList.remove('bg-success', 'bg-warning', 'bg-danger');
    if (device.cpu_load > 80) cpuBar.classList.add('bg-danger');
    else if (device.cpu_load > 50) cpuBar.classList.add('bg-warning');
    else cpuBar.classList.add('bg-success');
}

function syncRouter(id) {
    const btn = document.getElementById('sync-btn-' + id);
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Syncing...';
    
    fetch('{{ url("admin/multi-router/sync") }}/' + id, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json'
        }
    })
    .then(r => r.json())
    .then(data => {
        if (data.device) {
            updateDeviceCard(data.device);
        }
        if (!data.success && data.message) {
            alert(data.message);
        }
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-sync-alt me-1"></i>Sync';
        refreshStatus();
    })
    .catch(e => {
        console.error('Sync failed:', e);
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-sync-alt me-1"></i>Sync';
    });
}

function syncAllRouters() {
    const btn = document.getElementById('sync-all-btn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Syncing...';
    
    fetch('{{ route("admin.multi-router.sync-all") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json'
        }
    })
    .then(r => r.json())
    .then(data => {
        refreshStatus();
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-sync-alt me-1"></i>Sync All';
    })
    .catch(e => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-sync-alt me-1"></i>Sync All';
    });
}

function viewUsers(id) {
    const modal = new bootstrap.Modal(document.getElementById('usersModal'));
    document.getElementById('users-loading').classList.remove('d-none');
    document.getElementById('users-list').classList.add('d-none');
    modal.show();
    
    fetch('{{ url("admin/multi-router") }}/' + id + '/users')
        .then(r => r.json())
        .then(data => {
            document.getElementById('modal-router-name').textContent = data.device_name;
            document.getElementById('users-loading').classList.add('d-none');
            document.getElementById('users-list').classList.remove('d-none');
            
            const tbody = document.getElementById('users-table-body');
            tbody.innerHTML = '';
            
            if (data.users && data.users.length > 0) {
                data.users.forEach(user => {
                    tbody.innerHTML += `<tr>
                        <td>${user.name}</td>
                        <td>${user.address}</td>
                        <td>${user.uptime}</td>
                        <td><code>${user.caller_id}</code></td>
                    </tr>`;
                });
            } else {
                tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">No active users</td></tr>';
            }
        });
}
</script>
@endpush
