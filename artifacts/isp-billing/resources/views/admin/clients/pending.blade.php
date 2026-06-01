@extends('layouts.admin')

@section('title', 'New Connection Requests')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.clients.index') }}">Clients</a></li>
<li class="breadcrumb-item active">Pending</li>
@endsection

@section('content')
<style>
.client-card { border-left: 4px solid #ffc107; margin-bottom: 12px; background: #fff; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
.client-card .card-body { padding: 12px 15px; }
.client-card .client-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px; }
.client-card .client-name { font-weight: 600; font-size: 15px; color: #333; margin: 0; }
.client-card .client-info { font-size: 13px; color: #666; margin-bottom: 5px; }
.client-card .client-meta { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 10px; font-size: 12px; }
.client-card .btn-group-mobile { display: flex; flex-wrap: wrap; gap: 6px; }
.client-card .btn-group-mobile .btn { flex: 1; min-width: 70px; font-size: 13px; padding: 6px 10px; }
@media (min-width: 768px) { .mobile-cards { display: none; } }
@media (max-width: 767px) { .desktop-table { display: none; } }
</style>

<div class="card">
    <div class="card-header"><h3 class="card-title">Pending Connection Requests</h3></div>
    <div class="card-body">
        <div class="mobile-cards">
            @forelse($clients as $client)
            <div class="card client-card">
                <div class="card-body">
                    <div class="client-header">
                        <h6 class="client-name">{{ $client->name }}</h6>
                        <span class="badge badge-warning">Pending</span>
                    </div>
                    <div class="client-info">
                        <strong>ID:</strong> {{ $client->client_id }} | <strong>Phone:</strong> {{ $client->phone }}
                    </div>
                    <div class="client-meta">
                        <span><i class="fas fa-map-marker-alt mr-1"></i>{{ $client->zone->name ?? '-' }}</span>
                        <span><i class="fas fa-box mr-1"></i>{{ $client->package->name ?? '-' }}</span>
                        <span><i class="fas fa-calendar mr-1"></i>{{ $client->created_at->format('d-m-Y') }}</span>
                    </div>
                    <div class="btn-group-mobile">
                        <a href="{{ route('admin.clients.show', $client) }}" class="btn btn-info btn-sm">
                            <i class="fas fa-eye mr-1"></i> View
                        </a>
                        <a href="{{ route('admin.clients.edit', $client) }}" class="btn btn-success btn-sm">
                            <i class="fas fa-check mr-1"></i> Approve
                        </a>
                        <a href="tel:{{ $client->phone }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-phone mr-1"></i> Call
                        </a>
                    </div>
                </div>
            </div>
            @empty
            <div class="text-center text-muted py-4">No pending requests</div>
            @endforelse
        </div>

        <div class="desktop-table">
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr><th>Client ID</th><th>Name</th><th>Phone</th><th>Zone</th><th>Package</th><th>Date</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        @forelse($clients as $client)
                        <tr>
                            <td>{{ $client->client_id }}</td>
                            <td>{{ $client->name }}</td>
                            <td>{{ $client->phone }}</td>
                            <td>{{ $client->zone->name ?? '-' }}</td>
                            <td>{{ $client->package->name ?? '-' }}</td>
                            <td>{{ $client->created_at->format('d-m-Y') }}</td>
                            <td>
                                <a href="{{ route('admin.clients.edit', $client) }}" class="btn btn-success btn-xs">Approve</a>
                                <a href="{{ route('admin.clients.show', $client) }}" class="btn btn-info btn-xs">View</a>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="text-center">No pending requests</td></tr>
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
        {{ $clients->appends(request()->query())->links() }}
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
