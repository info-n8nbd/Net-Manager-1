@extends('layouts.admin')

@section('title', 'Active / Inactive History')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.clients.index') }}">Clients</a></li>
<li class="breadcrumb-item"><a href="{{ route('admin.clients.show', $client) }}">{{ $client->name }}</a></li>
<li class="breadcrumb-item active">Active / Inactive History</li>
@endsection

@push('styles')
<style>
.status-timeline {
    position: relative;
    padding: 0;
    list-style: none;
}
.status-timeline::before {
    content: '';
    position: absolute;
    left: 20px;
    top: 0;
    bottom: 0;
    width: 3px;
    background: #dee2e6;
}
.status-entry {
    position: relative;
    padding-left: 55px;
    margin-bottom: 25px;
}
.status-entry:last-child {
    margin-bottom: 0;
}
.status-icon {
    position: absolute;
    left: 8px;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 12px;
}
.status-icon.active { background: #28a745; }
.status-icon.inactive { background: #6c757d; }
.status-card {
    background: #fff;
    border-radius: 8px;
    padding: 15px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    border-left: 4px solid #6c757d;
}
.status-card.active { border-left-color: #28a745; }
.status-card.inactive { border-left-color: #dc3545; }
.status-meta {
    display: flex;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 8px;
    font-size: 12px;
    color: #6c757d;
    margin-top: 10px;
}
.source-badge {
    font-size: 10px;
    padding: 2px 8px;
    border-radius: 10px;
}
.source-badge.auto { background: #e7f3ff; color: #0d6efd; }
.source-badge.manual { background: #f8f9fa; color: #495057; }
</style>
@endpush

@section('content')
<div class="row">
    <div class="col-md-4">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-user mr-2"></i>Client Info</h3>
            </div>
            <div class="card-body">
                <div class="text-center mb-3">
                    <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 60px; height: 60px; font-size: 1.5rem;">
                        {{ strtoupper(substr($client->name, 0, 1)) }}
                    </div>
                    <h5 class="mt-2 mb-0">{{ $client->name }}</h5>
                    <small class="text-muted">{{ $client->client_id }}</small>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between gap-1">
                        <span>Current Status</span>
                        <span class="badge badge-{{ $client->status == 'active' ? 'success' : ($client->status == 'inactive' ? 'danger' : 'secondary') }}">
                            {{ ucfirst($client->status) }}
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between gap-1">
                        <span>Phone</span>
                        <span>{{ $client->phone }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between gap-1">
                        <span>Zone</span>
                        <span>{{ $client->zone->name ?? 'N/A' }}</span>
                    </li>
                </ul>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header bg-info">
                <h3 class="card-title"><i class="fas fa-link mr-2"></i>Quick Links</h3>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    <a href="{{ route('admin.clients.show', $client) }}" class="list-group-item list-group-item-action">
                        <i class="fas fa-user mr-2"></i> View Profile
                    </a>
                    <a href="{{ route('admin.clients.ledger', $client) }}" class="list-group-item list-group-item-action">
                        <i class="fas fa-file-invoice-dollar mr-2"></i> Billing Ledger
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-toggle-on mr-2"></i>Active / Inactive History</h3>
                <div class="card-tools">
                    <span class="badge badge-info">{{ $logs->total() }} records</span>
                </div>
            </div>
            <div class="card-body">
                @if($logs->count() > 0)
                <div class="status-timeline">
                    @foreach($logs as $log)
                    <div class="status-entry">
                        <div class="status-icon {{ $log->new_status }}">
                            <i class="fas fa-{{ $log->new_status == 'active' ? 'check' : ($log->new_status == 'inactive' ? 'times' : 'pause') }}"></i>
                        </div>
                        <div class="status-card {{ $log->new_status }}">
                            <div class="d-flex justify-content-between align-items-start flex-wrap gap-1">
                                <div>
                                    <strong>
                                        @if($log->new_status == 'active')
                                            <span class="text-success">Activated</span>
                                        @elseif($log->new_status == 'inactive')
                                            <span class="text-danger">Deactivated</span>
                                        @else
                                            <span class="text-warning">{{ ucfirst($log->new_status) }}</span>
                                        @endif
                                    </strong>
                                    @if($log->old_status)
                                        <span class="text-muted ml-1">(was {{ ucfirst($log->old_status) }})</span>
                                    @endif
                                </div>
                                <span class="source-badge {{ $log->source ?? 'manual' }}">
                                    {{ ($log->source ?? 'manual') == 'auto' ? 'System' : 'Manual' }}
                                </span>
                            </div>
                            @if($log->reason)
                            <p class="mb-0 mt-2 text-muted">
                                <i class="fas fa-comment-alt mr-1"></i> {{ $log->reason }}
                            </p>
                            @endif
                            <div class="status-meta">
                                <span>
                                    <i class="fas fa-calendar-alt mr-1"></i> 
                                    {{ $log->created_at->format('d-m-Y') }}
                                </span>
                                <span>
                                    <i class="fas fa-clock mr-1"></i> 
                                    {{ $log->created_at->format('h:i A') }}
                                </span>
                                <span>
                                    <i class="fas fa-user mr-1"></i> 
                                    {{ ($log->source ?? 'manual') == 'auto' ? 'System' : ($log->changedBy->name ?? 'Unknown') }}
                                </span>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <div class="text-center py-5">
                    <i class="fas fa-toggle-on fa-4x text-muted mb-3"></i>
                    <h5 class="text-muted">No Status Changes</h5>
                    <p class="text-muted">There are no recorded status changes for this client.</p>
                </div>
                @endif
            </div>
            @if($logs->hasPages())
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
        {{ $logs->appends(request()->query())->links() }}
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
</div>
@endsection
