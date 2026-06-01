@extends('layouts.admin')

@section('title', 'Active Connections')
@section('breadcrumb')
<li class="breadcrumb-item active">Active Connections</li>
@endsection

@section('content')
<style>
@media (min-width: 768px) { .mobile-conn-cards { display: none !important; } }
@media (max-width: 767px) { .desktop-conn-table { display: none !important; } }
</style>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-wifi mr-2"></i>Active Connections</h3>
        <span class="badge badge-success ml-2">{{ $users->total() }} Users</span>
    </div>
    <div class="card-body">
        <!-- Mobile Cards -->
        <div class="mobile-conn-cards">
            <div class="device-card-grid">
                @forelse($users as $user)
                <div class="device-card">
                    <div class="device-card-header {{ $user->is_disabled ? 'offline' : 'online' }}">
                        <div class="device-name">{{ $user->client->name ?? 'Unknown' }}</div>
                        <span class="device-status">{{ $user->is_disabled ? 'Disabled' : 'Active' }}</span>
                    </div>
                    <div class="device-card-body">
                        <div class="device-info-row">
                            <span class="device-info-label"><i class="fas fa-user mr-1"></i>Username</span>
                            <span class="device-info-value"><code>{{ $user->username }}</code></span>
                        </div>
                        <div class="device-info-row">
                            <span class="device-info-label"><i class="fas fa-server mr-1"></i>Device</span>
                            <span class="device-info-value">{{ $user->device->name ?? 'N/A' }}</span>
                        </div>
                        <div class="device-info-row">
                            <span class="device-info-label"><i class="fas fa-cog mr-1"></i>Service</span>
                            <span class="device-info-value">{{ $user->service }}</span>
                        </div>
                    </div>
                </div>
                @empty
                <div class="text-center py-4 text-muted">
                    <i class="fas fa-wifi fa-3x mb-3"></i>
                    <p>No connections found</p>
                </div>
                @endforelse
            </div>
        </div>

        <!-- Desktop Table -->
        <div class="desktop-conn-table">
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead class="bg-dark text-white">
                        <tr><th>Client</th><th>Username</th><th>Device</th><th>Service</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                        <tr>
                            <td>{{ $user->client->name ?? 'N/A' }}</td>
                            <td><code>{{ $user->username }}</code></td>
                            <td>{{ $user->device->name ?? 'N/A' }}</td>
                            <td>{{ $user->service }}</td>
                            <td><span class="badge badge-{{ $user->is_disabled ? 'danger' : 'success' }}">{{ $user->is_disabled ? 'Disabled' : 'Active' }}</span></td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center">No connections found</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
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
        {{ $users->appends(request()->query())->links() }}
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
</div>
@endsection
