@extends('layouts.admin')

@section('title', 'MikroTik Devices')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
<li class="breadcrumb-item">Network</li>
<li class="breadcrumb-item active">MikroTik Devices</li>
@endsection

@section('content')
<style>
@media (min-width: 992px) { .mobile-device-cards { display: none !important; } }
@media (max-width: 991px) { .desktop-device-table { display: none !important; } .add-device-sidebar { display: none !important; } }
</style>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-server mr-2"></i>MikroTik Devices</h3>
                <div class="card-tools">
                    @if($__canAction('network', 'add_router'))
                    <a href="{{ route('admin.network.devices.create') }}" class="btn btn-sm btn-primary mr-1">
                        <i class="fas fa-plus"></i> <span class="d-none d-md-inline">Add Router</span>
                    </a>
                    @endif
                    <a href="{{ route('admin.network.dashboard') }}" class="btn btn-sm btn-dark mr-1">
                        <i class="fas fa-tachometer-alt"></i> <span class="d-none d-md-inline">Dashboard</span>
                    </a>
                    <a href="{{ route('admin.network.diagram') }}" class="btn btn-sm btn-info mr-1">
                        <i class="fas fa-project-diagram"></i> <span class="d-none d-md-inline">Diagram</span>
                    </a>
                    <a href="{{ route('admin.olt.index') }}" class="btn btn-sm btn-success">
                        <i class="fas fa-network-wired"></i> <span class="d-none d-md-inline">OLT</span>
                    </a>
                </div>
            </div>
            <div class="card-body">
                <!-- Mobile Card View -->
                <div class="mobile-device-cards">
                    <div class="device-card-grid">
                        @forelse($devices as $device)
                        <div class="device-card">
                            <div class="device-card-header {{ $device->status == 'online' ? 'online' : 'offline' }}">
                                <div class="device-name">
                                    {{ $device->name }}
                                    @if($device->model)
                                    <br><small style="opacity:0.8">{{ $device->model }}</small>
                                    @endif
                                </div>
                                <span class="device-status">{{ ucfirst($device->status ?? 'Unknown') }}</span>
                            </div>
                            <div class="device-card-body">
                                <div class="device-info-row">
                                    <span class="device-info-label"><i class="fas fa-globe mr-1"></i>Host</span>
                                    <span class="device-info-value"><code>{{ $device->host }}:{{ $device->port }}</code></span>
                                </div>
                                <div class="device-info-row">
                                    <span class="device-info-label"><i class="fas fa-map-marker-alt mr-1"></i>Zone</span>
                                    <span class="device-info-value">{{ $device->zone->name ?? 'N/A' }}</span>
                                </div>
                                <div class="device-info-row">
                                    <span class="device-info-label"><i class="fas fa-toggle-on mr-1"></i>Status</span>
                                    <span class="device-info-value">
                                        <span class="badge badge-{{ $device->is_active ? 'success' : 'danger' }}">
                                            {{ $device->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </span>
                                </div>
                                @if($device->last_checked_at)
                                <div class="device-info-row">
                                    <span class="device-info-label"><i class="fas fa-clock mr-1"></i>Checked</span>
                                    <span class="device-info-value">{{ $device->last_checked_at->diffForHumans() }}</span>
                                </div>
                                @endif
                            </div>
                            <div class="device-card-footer">
                                <a href="{{ route('admin.network.devices.ppp-users', $device) }}" class="btn btn-success btn-sm">
                                    <i class="fas fa-key mr-1"></i>PPPoE
                                </a>
                                <button type="button" class="btn btn-info btn-sm" onclick="testConnection({{ $device->id }})">
                                    <i class="fas fa-plug mr-1"></i>Test
                                </button>
                                @if($__canAction('network', 'edit_router'))
                                <a href="{{ route('admin.network.devices.edit', $device) }}" class="btn btn-warning btn-sm">
                                    <i class="fas fa-edit mr-1"></i>Edit
                                </a>
                                @endif
                                @if($__canAction('network', 'delete_router'))
                                <form action="{{ route('admin.network.devices.destroy', $device) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this device?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
                                </form>
                                @endif
                            </div>
                        </div>
                        @empty
                        <div class="text-center py-4 text-muted">
                            <i class="fas fa-server fa-3x mb-3"></i>
                            <p>No MikroTik devices configured yet.</p>
                        </div>
                        @endforelse
                    </div>
                </div>

                <!-- Desktop Table View -->
                <div class="desktop-device-table">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0">
                            <thead class="bg-dark text-white">
                                <tr>
                                    <th>Name / Identity</th>
                                    <th>Type</th>
                                    <th>Host</th>
                                    <th>Zone</th>
                                    <th>VPN Account</th>
                                    <th>API Status</th>
                                    <th width="180">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($devices as $device)
                                <tr>
                                    <td>
                                        <strong>{{ $device->name }}</strong>
                                        @if($device->system_identity && $device->system_identity !== $device->name)
                                        <br><small class="text-warning" title="System identity differs from device name"><i class="fas fa-exclamation-triangle mr-1"></i>Identity: {{ $device->system_identity }}</small>
                                        @elseif($device->system_identity)
                                        <br><small class="text-success"><i class="fas fa-check mr-1"></i>{{ $device->system_identity }}</small>
                                        @endif
                                        @if($device->model)
                                        <br><small class="text-muted">{{ $device->model }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $rtBadge = match($device->router_type) {
                                                'core' => 'primary',
                                                'noc' => 'warning',
                                                'pop' => 'success',
                                                default => 'info',
                                            };
                                            $rtLabel = match($device->router_type) {
                                                'noc' => 'NOC',
                                                'pop' => 'POP',
                                                default => ucfirst($device->router_type ?? 'core'),
                                            };
                                        @endphp
                                        <span class="badge badge-{{ $rtBadge }}">{{ $rtLabel }}</span>
                                    </td>
                                    <td><code>{{ $device->host }}:{{ $device->port }}</code></td>
                                    <td>{{ $device->zone->name ?? 'N/A' }}</td>
                                    <td>
                                        @if($device->vpnAccount)
                                            <span class="badge badge-{{ $device->vpnAccount->account_type === 'main_router' ? 'success' : 'warning' }}">
                                                <i class="fas fa-shield-alt mr-1"></i>{{ $device->vpnAccount->name ?? $device->vpnAccount->username }}
                                            </span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge badge-{{ $device->status == 'online' ? 'success' : ($device->status == 'warning' ? 'warning' : 'secondary') }}" id="status-{{ $device->id }}">
                                            @if($device->status == 'online')
                                                <i class="fas fa-check-circle mr-1"></i>API: OK
                                            @elseif($device->status == 'offline')
                                                <i class="fas fa-times-circle mr-1"></i>API: FAIL
                                            @else
                                                <i class="fas fa-question-circle mr-1"></i>Unknown
                                            @endif
                                        </span>
                                        @if($device->last_checked_at)
                                        <br><small class="text-muted">{{ $device->last_checked_at->diffForHumans() }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="{{ route('admin.network.devices.detail', $device) }}" class="btn btn-primary btn-sm" title="Router Detail">
                                                <i class="fas fa-tachometer-alt"></i>
                                            </a>
                                            <a href="{{ route('admin.network.devices.ppp-users', $device) }}" class="btn btn-success btn-sm" title="PPPoE Secrets">
                                                <i class="fas fa-key"></i>
                                            </a>
                                            <button type="button" class="btn btn-info btn-sm" onclick="testConnection({{ $device->id }})" title="Test API Connection">
                                                <i class="fas fa-plug"></i>
                                            </button>
                                            @if($__canAction('network', 'edit_router'))
                                            <a href="{{ route('admin.network.devices.edit', $device) }}" class="btn btn-warning btn-sm" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            @endif
                                            @if($__canAction('network', 'delete_router'))
                                            <form action="{{ route('admin.network.devices.destroy', $device) }}" method="POST" style="display:inline" onsubmit="return confirm('Are you sure you want to delete this device?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger btn-sm" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4">
                                        <i class="fas fa-server fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">No MikroTik devices configured yet.</p>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @if($devices->hasPages())
            <div class="card-footer">
                <div class="d-flex flex-wrap justify-content-between align-items-center mt-2 mb-1">
    <div class="d-flex align-items-center">
        <label class="small text-muted mb-0 mr-2" style="white-space:nowrap;">SHOW</label>
        <select class="form-control form-control-sm bulk-show-entries" style="width:80px;">
            <option value="20" {{ request('per_page',20)==20?'selected':'' }}>20</option>
            <option value="50" {{ request('per_page',20)==50?'selected':'' }}>50</option>
            <option value="100" {{ request('per_page',20)==100?'selected':'' }}>100</option>
            <option value="200" {{ request('per_page',20)==200?'selected':'' }}>200</option>
        </select>
        <label class="small text-muted mb-0 ml-2" style="white-space:nowrap;">ENTRIES</label>
    </div>
    <div>
        {{ $devices->appends(request()->query())->links() }}
    </div>
</div>
<script>
(function(){
    var sel = document.querySelector('.bulk-show-entries');
    if(sel){ sel.addEventListener('change',function(){
        var p=new URLSearchParams(window.location.search);
        p.set('per_page',this.value); p.delete('page');
        window.location=window.location.pathname+'?'+p.toString();
    }); }
})();
</script>
            </div>
            @endif
        </div>
    </div>
    <div class="col-lg-4 add-device-sidebar">
        @if($__canAction('network', 'add_router'))
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-plus-circle mr-2"></i>Add MikroTik Device</h3>
            </div>
            <form action="{{ route('admin.network.devices.store') }}" method="POST">
                @csrf
                <div class="card-body">
                    <div class="form-group">
                        <label>Device Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required placeholder="e.g., Main Router">
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label>Router Type</label>
                        <select name="router_type" class="form-control">
                            <option value="core" {{ old('router_type') == 'core' ? 'selected' : '' }}>Core Router</option>
                            <option value="noc" {{ old('router_type') == 'noc' ? 'selected' : '' }}>NOC Router</option>
                            <option value="pop" {{ old('router_type') == 'pop' ? 'selected' : '' }}>POP Router</option>
                            <option value="branch" {{ old('router_type') == 'branch' ? 'selected' : '' }}>Branch Router</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Host/IP Address <span class="text-danger">*</span></label>
                        <input type="text" name="host" class="form-control @error('host') is-invalid @enderror" value="{{ old('host') }}" required placeholder="192.168.1.1">
                        @error('host')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>API Port <span class="text-danger">*</span></label>
                                <input type="number" name="port" class="form-control @error('port') is-invalid @enderror" value="{{ old('port', 8728) }}">
                                @error('port')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>WWW Port <span class="text-danger">*</span></label>
                                <input type="number" name="www_port" class="form-control @error('www_port') is-invalid @enderror" value="{{ old('www_port', 80) }}">
                                @error('www_port')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Zone</label>
                        <select name="zone_id" class="form-control">
                            <option value="">Select Zone</option>
                            @foreach($zones as $zone)
                            <option value="{{ $zone->id }}" {{ old('zone_id') == $zone->id ? 'selected' : '' }}>{{ $zone->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Mikrotik User Name <span class="text-danger">*</span></label>
                        <input type="text" name="username" class="form-control @error('username') is-invalid @enderror" value="{{ old('username') }}" required placeholder="admin">
                        @error('username')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label>Mikrotik Password <span class="text-danger">*</span></label>
                        <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" required>
                        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label>Overwrite PPP Comment</label>
                        <select name="overwrite_ppp_comment" class="form-control">
                            <option value="1" {{ old('overwrite_ppp_comment', '1') == '1' ? 'selected' : '' }}>Yes</option>
                            <option value="0" {{ old('overwrite_ppp_comment') == '0' ? 'selected' : '' }}>No</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="Optional notes...">{{ old('description') }}</textarea>
                    </div>
                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                            <label class="custom-control-label" for="is_active">Active</label>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-save mr-2"></i>Add Device</button>
                </div>
            </form>
        </div>

        <div class="card card-success">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-upload mr-2"></i>Bulk Import Routers</h3>
            </div>
            <form action="{{ route('admin.network.devices.bulk') }}" method="POST">
                @csrf
                <div class="card-body">
                    <div class="form-group">
                        <label>Paste Router Data (one per line)</label>
                        <textarea name="bulk_data" class="form-control" rows="6" placeholder="name,host,port,username,password
Main Router,10.10.10.1,8728,admin,password123
Branch Router,10.10.10.2,8728,admin,password456
Zone-A Router,192.168.1.1,8728,api,apipass"></textarea>
                        <small class="text-muted">
                            Format: <code>name,host,port,username,password</code> or <code>name,host,username,password</code> (port defaults to 8728)
                        </small>
                    </div>
                    <div class="form-group">
                        <label>Assign to Zone (optional)</label>
                        <select name="zone_id" class="form-control">
                            <option value="">No Zone</option>
                            @foreach($zones as $zone)
                            <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-success btn-block"><i class="fas fa-upload mr-2"></i>Import All Routers</button>
                </div>
            </form>
        </div>
        @endif

        <div class="card card-info card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-info-circle mr-2"></i>Quick Help</h3>
            </div>
            <div class="card-body">
                <p class="text-sm"><strong>API Port:</strong> Default is 8728 for non-SSL, 8729 for SSL.</p>
                <p class="text-sm"><strong>Username:</strong> Create a dedicated API user in MikroTik with read/write permissions.</p>
                <p class="text-sm"><strong>Bulk Import:</strong> Add multiple routers at once using CSV format.</p>
                <p class="text-sm mb-0"><strong>Test:</strong> Use the plug icon to test device connectivity.</p>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function testConnection(deviceId) {
    var S = window.SmartSwal || Swal;
    S.fire({
        title: '<i class="fas fa-satellite-dish text-primary mr-2"></i> Testing API Connection...',
        html: '<p class="mb-2">Connecting to MikroTik router via API port</p><div class="progress" style="height:6px;border-radius:3px"><div class="progress-bar progress-bar-striped progress-bar-animated bg-primary" style="width:100%"></div></div>',
        allowOutsideClick: false,
        showConfirmButton: false,
        didOpen: () => { Swal.showLoading(); }
    });
    
    $.ajax({
        url: '/admin/network/devices/' + deviceId + '/test',
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        timeout: 20000,
        success: function(response) {
            var statusEl = $('#status-' + deviceId);
            if (response.success) {
                statusEl.removeClass('badge-secondary badge-warning badge-danger')
                    .addClass('badge-success')
                    .html('<i class="fas fa-check-circle mr-1"></i>API: OK');
                
                var msg = response.message;
                var detailHtml = '<div style="text-align:left;background:#f8fafc;border-radius:10px;padding:14px 18px;margin-top:8px;font-size:13px;line-height:1.8">' + msg + '</div>';
                
                if (response.identity && !response.identity_match) {
                    S.fire({
                        icon: 'warning',
                        title: 'API: OK (Identity Mismatch)',
                        html: '<p class="mb-1" style="color:#92400e">Router connected but system identity doesn\'t match device name.</p>' + detailHtml,
                        confirmButtonText: '<i class="fas fa-check mr-1"></i> Understood'
                    });
                } else {
                    S.fire({
                        icon: 'success',
                        title: 'API Connected Successfully',
                        html: '<p class="mb-1" style="color:#065f46">Router is online and responding.</p>' + detailHtml,
                        confirmButtonText: '<i class="fas fa-check mr-1"></i> Great!'
                    });
                }
            } else {
                statusEl.removeClass('badge-success badge-warning')
                    .addClass('badge-secondary')
                    .html('<i class="fas fa-times-circle mr-1"></i>API: FAIL');
                S.fire({
                    icon: 'error',
                    title: 'API Connection Failed',
                    html: '<p class="mb-1" style="color:#991b1b">' + response.message + '</p><div style="text-align:left;background:#fef2f2;border-radius:10px;padding:12px 16px;margin-top:8px;font-size:12px;color:#92400e"><i class="fas fa-lightbulb mr-1"></i> <strong>Tips:</strong><ul class="mb-0 mt-1 pl-3"><li>Check API port is enabled on router</li><li>Verify username/password</li><li>Ensure firewall allows API access</li></ul></div>',
                    confirmButtonText: '<i class="fas fa-redo mr-1"></i> Close'
                });
            }
        },
        error: function(xhr, status) {
            var msg = status === 'timeout'
                ? '<p>Connection test timed out.</p><div style="text-align:left;background:#fef2f2;border-radius:10px;padding:12px 16px;margin-top:8px;font-size:12px;color:#92400e"><i class="fas fa-lightbulb mr-1"></i> <strong>Possible causes:</strong><ul class="mb-0 mt-1 pl-3"><li>Router IP is not reachable from this server</li><li>API port is blocked by firewall</li><li>Router is overloaded or offline</li></ul></div>'
                : '<p>Could not reach the billing server.</p><p style="font-size:12px;color:#6b7280">Please check your internet connection and try again.</p>';
            var statusEl = $('#status-' + deviceId);
            statusEl.removeClass('badge-success badge-warning')
                .addClass('badge-danger')
                .html('<i class="fas fa-times-circle mr-1"></i>Unreachable');
            S.fire({
                icon: 'error',
                title: 'Connection Failed',
                html: msg,
                confirmButtonText: '<i class="fas fa-times mr-1"></i> Close'
            });
        }
    });
}
</script>
@endpush
