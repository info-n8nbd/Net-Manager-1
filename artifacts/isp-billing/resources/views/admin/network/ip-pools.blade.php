@extends('layouts.admin')

@section('title', 'IP Pools')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
<li class="breadcrumb-item">Network</li>
<li class="breadcrumb-item active">IP Pools</li>
@endsection

@section('content')
<div class="row mb-3">
    <div class="col-md-12">
        <button type="button" class="btn btn-info" data-toggle="modal" data-target="#importModal">
            <i class="fas fa-download mr-1"></i>Import from Router
        </button>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-network-wired mr-2"></i>IP Address Pools</h3>
        <div class="card-tools">
            <span class="badge badge-info">{{ $pools->count() }} pools</span>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped table-hover mb-0">
                <thead class="bg-dark text-white">
                    <tr>
                        <th>Name</th>
                        <th>Range</th>
                        <th>Total</th>
                        <th>Used</th>
                        <th>Available</th>
                        <th>Router</th>
                        <th>Synced</th>
                        <th width="180">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pools as $pool)
                    <tr>
                        <td>
                            <a href="{{ route('admin.network.ip-pool-detail', $pool->id) }}">
                                <strong>{{ $pool->name }}</strong>
                            </a>
                        </td>
                        <td><code>{{ $pool->range_start }} - {{ $pool->range_end }}</code></td>
                        <td>{{ $pool->total_ips }}</td>
                        <td>
                            @if($pool->total_ips > 0)
                                <span class="badge badge-{{ $pool->used_ips / $pool->total_ips > 0.9 ? 'danger' : ($pool->used_ips / $pool->total_ips > 0.7 ? 'warning' : 'info') }}">
                                    {{ $pool->used_ips }}
                                </span>
                            @else
                                <span class="badge badge-secondary">0</span>
                            @endif
                        </td>
                        <td><span class="badge badge-success">{{ $pool->available_ips }}</span></td>
                        <td>{{ $pool->router_name ?? 'N/A' }}</td>
                        <td>
                            @if($pool->synced_to_router)
                                <span class="badge badge-success"><i class="fas fa-check mr-1"></i>Yes</span>
                                @if($pool->last_synced_at)
                                    <br><small class="text-muted">{{ \Carbon\Carbon::parse($pool->last_synced_at)->diffForHumans() }}</small>
                                @endif
                            @else
                                <span class="badge badge-secondary">No</span>
                            @endif
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('admin.network.ip-pool-detail', $pool->id) }}" class="btn btn-primary" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <button type="button" class="btn btn-info" onclick="syncPool({{ $pool->id }})" title="Sync to Router">
                                    <i class="fas fa-sync"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-4">
                            <i class="fas fa-network-wired fa-3x text-muted mb-3 d-block"></i>
                            <p class="text-muted">No IP pools found. Import pools from your router to get started.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@if($pools->count() > 0)
<div class="row">
    @foreach($pools as $pool)
    @if($pool->total_ips > 0)
    <div class="col-md-3 col-sm-6">
        <div class="info-box">
            <span class="info-box-icon bg-{{ $pool->total_ips > 0 && $pool->used_ips / $pool->total_ips > 0.9 ? 'danger' : 'info' }}">
                <i class="fas fa-chart-pie"></i>
            </span>
            <div class="info-box-content">
                <span class="info-box-text">{{ $pool->name }}</span>
                <span class="info-box-number">{{ $pool->total_ips > 0 ? round(($pool->used_ips / $pool->total_ips) * 100) : 0 }}% Used</span>
                <div class="progress">
                    <div class="progress-bar bg-{{ $pool->total_ips > 0 && $pool->used_ips / $pool->total_ips > 0.9 ? 'danger' : 'info' }}" style="width: {{ $pool->total_ips > 0 ? ($pool->used_ips / $pool->total_ips) * 100 : 0 }}%"></div>
                </div>
                <span class="progress-description">{{ $pool->used_ips }} / {{ $pool->total_ips }} addresses</span>
            </div>
        </div>
    </div>
    @endif
    @endforeach
</div>
@endif

<div class="modal fade" id="importModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="fas fa-download mr-2"></i>Import Pools from Router</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Select Router</label>
                    <select class="form-control" id="importDeviceId">
                        <option value="">-- Select a device --</option>
                        @foreach($devices as $d)
                            <option value="{{ $d->id }}">{{ $d->name }} ({{ $d->host }})</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-info" id="btnImportPools">
                    <i class="fas fa-download mr-1"></i>Import
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
var csrfToken = $('meta[name="csrf-token"]').attr('content');

function syncPool(poolId) {
    $.ajax({
        url: '/admin/network/ip-pools/' + poolId + '/sync-to-router',
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken },
        dataType: 'json',
        success: function(res) {
            if (res.success) {
                alert(res.message);
                location.reload();
            } else {
                alert('Error: ' + res.message);
            }
        },
        error: function() { alert('Sync request failed.'); }
    });
}

$('#btnImportPools').click(function() {
    var deviceId = $('#importDeviceId').val();
    if (!deviceId) {
        alert('Please select a router.');
        return;
    }
    var btn = $(this);
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Importing...');
    $.ajax({
        url: '{{ route("admin.network.ip-pool-import") }}',
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken },
        data: { device_id: deviceId },
        dataType: 'json',
        success: function(res) {
            if (res.success) {
                alert(res.message);
                location.reload();
            } else {
                alert('Error: ' + res.message);
            }
        },
        error: function() { alert('Import request failed.'); },
        complete: function() {
            btn.prop('disabled', false).html('<i class="fas fa-download mr-1"></i>Import');
        }
    });
});
</script>
@endpush
