@extends('layouts.admin')

@section('title', 'MikroTik Two-Way Sync')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
<li class="breadcrumb-item"><a href="{{ route('admin.network.devices') }}">MikroTik Devices</a></li>
<li class="breadcrumb-item active">Two-Way Sync</li>
@endsection

@section('content')
<div class="row mb-3">
    <div class="col-12 d-flex flex-wrap align-items-center justify-content-between">
        <div>
            <h4 class="mb-0">
                <i class="fas fa-sync-alt mr-2"></i>MikroTik Two-Way Sync Engine
            </h4>
            <small class="text-muted">Detect ghost users, billing-only users, profile mismatches &amp; auto-reconcile</small>
        </div>
        <div class="mt-2 mt-md-0">
            <a href="{{ route('admin.network.devices') }}" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left mr-1"></i>Back to Devices
            </a>
        </div>
    </div>
</div>

<div class="card card-primary card-outline">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-play-circle mr-2"></i>Run New Sync</h3>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.mikrotik-sync.run') }}" method="POST" class="form-inline">
            @csrf
            <div class="form-group mr-3">
                <label for="device_id" class="mr-2">Select Router:</label>
                <select name="device_id" id="device_id" class="form-control" required>
                    <option value="">-- Select Router --</option>
                    @foreach($devices as $device)
                        <option value="{{ $device->id }}">{{ $device->name }} ({{ $device->host }})</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group mr-3">
                <label for="mode" class="mr-2">Mode:</label>
                <select name="mode" id="mode" class="form-control">
                    <option value="dry_run">Dry Run (Preview Only)</option>
                    <option value="apply">Apply Changes</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary" onclick="this.disabled=true;this.form.submit();">
                <i class="fas fa-sync mr-1"></i>Run Sync
            </button>
        </form>
        <small class="text-muted mt-2 d-block">
            <strong>Dry Run:</strong> Compares router vs billing and shows differences without making changes.
            <strong>Apply:</strong> Automatically applies safe changes (create missing users, update profiles, fix enable/disable states).
        </small>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-history mr-2"></i>Sync History</h3>
    </div>
    <div class="card-body p-0">
        <table class="table table-striped table-hover mb-0">
            <thead>
                <tr>
                    <th>Run #</th>
                    <th>Router</th>
                    <th>Mode</th>
                    <th>Status</th>
                    <th>Router Users</th>
                    <th>Billing Users</th>
                    <th class="text-success">Synced</th>
                    <th class="text-warning">Ghost (Router Only)</th>
                    <th class="text-info">Billing Only</th>
                    <th class="text-danger">Mismatches</th>
                    <th>Initiated By</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($runs as $run)
                <tr>
                    <td><strong>#{{ $run->id }}</strong></td>
                    <td>{{ $run->device->name ?? 'N/A' }}</td>
                    <td>
                        @if($run->mode === 'dry_run')
                            <span class="badge badge-info">Dry Run</span>
                        @else
                            <span class="badge badge-success">Applied</span>
                        @endif
                    </td>
                    <td>
                        @if($run->status === 'completed')
                            <span class="badge badge-success"><i class="fas fa-check mr-1"></i>Completed</span>
                        @elseif($run->status === 'running')
                            <span class="badge badge-warning"><i class="fas fa-spinner fa-spin mr-1"></i>Running</span>
                        @else
                            <span class="badge badge-danger"><i class="fas fa-times mr-1"></i>Failed</span>
                        @endif
                    </td>
                    <td>{{ $run->router_user_count }}</td>
                    <td>{{ $run->billing_user_count }}</td>
                    <td><span class="text-success font-weight-bold">{{ $run->synced_count }}</span></td>
                    <td>
                        @if($run->only_on_router_count > 0)
                            <span class="badge badge-warning">{{ $run->only_on_router_count }}</span>
                        @else
                            <span class="text-muted">0</span>
                        @endif
                    </td>
                    <td>
                        @if($run->only_in_billing_count > 0)
                            <span class="badge badge-info">{{ $run->only_in_billing_count }}</span>
                        @else
                            <span class="text-muted">0</span>
                        @endif
                    </td>
                    <td>
                        @if($run->mismatch_count > 0)
                            <span class="badge badge-danger">{{ $run->mismatch_count }}</span>
                        @else
                            <span class="text-muted">0</span>
                        @endif
                    </td>
                    <td>{{ $run->initiator->name ?? 'System' }}</td>
                    <td>{{ $run->created_at->format('d-m-y H:i') }}</td>
                    <td>
                        <a href="{{ route('admin.mikrotik-sync.show', $run) }}" class="btn btn-sm btn-primary" title="View Details">
                            <i class="fas fa-eye"></i>
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="13" class="text-center text-muted py-4">
                        <i class="fas fa-info-circle mr-1"></i>No sync runs yet. Select a router and run your first sync above.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($runs->hasPages())
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
        {{ $runs->appends(request()->query())->links() }}
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
@endsection
