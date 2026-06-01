@extends('layouts.admin')

@section('title', 'Bandwidth Queues')
@section('breadcrumb')
<li class="breadcrumb-item active">Queues</li>
@endsection

@section('content')
<style>
@media (min-width: 768px) { .mobile-queue-cards { display: none !important; } }
@media (max-width: 767px) { .desktop-queue-table { display: none !important; } }
</style>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-tachometer-alt mr-2"></i>Bandwidth Queues</h3>
        <span class="badge badge-primary ml-2">{{ $queues->total() }} Queues</span>
    </div>
    <div class="card-body">
        <!-- Mobile Cards -->
        <div class="mobile-queue-cards">
            <div class="device-card-grid">
                @forelse($queues as $queue)
                <div class="device-card">
                    <div class="device-card-header">
                        <div class="device-name">{{ $queue->name }}</div>
                        <span class="device-status">P{{ $queue->priority }}</span>
                    </div>
                    <div class="device-card-body">
                        <div class="device-info-row">
                            <span class="device-info-label"><i class="fas fa-bullseye mr-1"></i>Target</span>
                            <span class="device-info-value"><code>{{ $queue->target }}</code></span>
                        </div>
                        <div class="device-info-row">
                            <span class="device-info-label"><i class="fas fa-tachometer-alt mr-1"></i>Max Limit</span>
                            <span class="device-info-value text-success">{{ $queue->max_limit }}</span>
                        </div>
                        <div class="device-info-row">
                            <span class="device-info-label"><i class="fas fa-server mr-1"></i>Device</span>
                            <span class="device-info-value">{{ $queue->device->name ?? 'N/A' }}</span>
                        </div>
                    </div>
                </div>
                @empty
                <div class="text-center py-4 text-muted">
                    <i class="fas fa-tachometer-alt fa-3x mb-3"></i>
                    <p>No queues configured</p>
                </div>
                @endforelse
            </div>
        </div>

        <!-- Desktop Table -->
        <div class="desktop-queue-table">
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead class="bg-dark text-white">
                        <tr><th>Name</th><th>Target</th><th>Max Limit</th><th>Priority</th><th>Device</th></tr>
                    </thead>
                    <tbody>
                        @forelse($queues as $queue)
                        <tr>
                            <td><strong>{{ $queue->name }}</strong></td>
                            <td><code>{{ $queue->target }}</code></td>
                            <td class="text-success">{{ $queue->max_limit }}</td>
                            <td><span class="badge badge-info">{{ $queue->priority }}</span></td>
                            <td>{{ $queue->device->name ?? 'N/A' }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center">No queues configured</td></tr>
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
        {{ $queues->appends(request()->query())->links() }}
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
