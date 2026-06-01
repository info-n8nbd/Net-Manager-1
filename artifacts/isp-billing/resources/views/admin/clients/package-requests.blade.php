@extends('layouts.admin')

@section('title', 'Package Change Requests')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.clients.index') }}">Clients</a></li>
<li class="breadcrumb-item active">Package Change Requests</li>
@endsection

@section('content')
<style>
.request-card { border-left: 4px solid #17a2b8; margin-bottom: 12px; background: #fff; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
.request-card.status-pending { border-left-color: #ffc107; }
.request-card.status-approved { border-left-color: #28a745; }
.request-card.status-rejected { border-left-color: #dc3545; }
.request-card .card-body { padding: 12px 15px; }
.request-card .request-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px; }
.request-card .client-name { font-weight: 600; font-size: 15px; color: #333; margin: 0; }
.request-card .request-info { font-size: 13px; color: #666; margin-bottom: 8px; }
.request-card .package-change { display: flex; align-items: center; gap: 8px; margin-bottom: 10px; font-size: 13px; }
.request-card .package-change .arrow { color: #28a745; font-weight: bold; }
.request-card .btn-group-mobile { display: flex; flex-wrap: wrap; gap: 6px; }
.request-card .btn-group-mobile .btn { flex: 1; min-width: 80px; font-size: 13px; padding: 6px 10px; }
@media (min-width: 768px) { .mobile-cards { display: none; } }
@media (max-width: 767px) { .desktop-table { display: none; } }
</style>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-exchange-alt mr-2"></i>Package Change Requests</h3>
    </div>
    <div class="card-body">
        <div class="mobile-cards">
            @forelse($requests as $req)
            <div class="card request-card status-{{ $req->status }}">
                <div class="card-body">
                    <div class="request-header">
                        <h6 class="client-name">{{ $req->client->name ?? 'N/A' }}</h6>
                        <span class="badge badge-{{ $req->status == 'approved' ? 'success' : ($req->status == 'rejected' ? 'danger' : 'warning') }}">
                            {{ ucfirst($req->status) }}
                        </span>
                    </div>
                    <div class="request-info">
                        <strong>ID:</strong> {{ $req->client->client_id ?? 'N/A' }} | 
                        <i class="fas fa-calendar mr-1"></i>{{ $req->created_at->format('d-m-Y') }}
                    </div>
                    <div class="package-change">
                        <span class="text-muted">{{ $req->currentPackage->name ?? 'N/A' }} (৳{{ number_format($req->currentPackage->price ?? 0) }})</span>
                        <span class="arrow"><i class="fas fa-arrow-right"></i></span>
                        <span class="text-success font-weight-bold">{{ $req->requestedPackage->name ?? 'N/A' }} (৳{{ number_format($req->requestedPackage->price ?? 0) }})</span>
                    </div>
                    @php $diff = ($req->requestedPackage->price ?? 0) - ($req->currentPackage->price ?? 0); @endphp
                    @if($diff > 0)
                    <div class="mb-2"><span class="badge badge-info">Upgrade: +৳{{ number_format($diff) }}</span></div>
                    @elseif($diff < 0)
                    <div class="mb-2"><span class="badge badge-secondary">Downgrade: ৳{{ number_format(abs($diff)) }}</span></div>
                    @endif
                    @if($req->status == 'pending')
                    <div class="btn-group-mobile">
                        <form action="{{ route('admin.clients.package-requests.process', $req) }}" method="POST" class="flex-fill">
                            @csrf
                            <input type="hidden" name="action" value="approve">
                            <button type="submit" class="btn btn-success btn-sm btn-block" onclick="return confirm('Approve this package change?')">
                                <i class="fas fa-check mr-1"></i> Approve
                            </button>
                        </form>
                        <form action="{{ route('admin.clients.package-requests.process', $req) }}" method="POST" class="flex-fill">
                            @csrf
                            <input type="hidden" name="action" value="reject">
                            <button type="submit" class="btn btn-danger btn-sm btn-block" onclick="return confirm('Reject this request?')">
                                <i class="fas fa-times mr-1"></i> Reject
                            </button>
                        </form>
                    </div>
                    @else
                    <div class="request-info">
                        <small class="text-muted">
                            <i class="fas fa-user mr-1"></i>{{ $req->approver->name ?? 'System' }} |
                            {{ $req->effective_date?->format('d-m-Y') }}
                        </small>
                    </div>
                    @endif
                </div>
            </div>
            @empty
            <div class="text-center text-muted py-4">No pending package change requests</div>
            @endforelse
        </div>

        <div class="desktop-table">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Client</th>
                            <th>Current Package</th>
                            <th>Requested Package</th>
                            <th>Request Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($requests as $req)
                        <tr>
                            <td>
                                @if($req->client)
                                <a href="{{ route('admin.clients.show', $req->client) }}">{{ $req->client->name }}</a>
                                <br><small class="text-muted">{{ $req->client->client_id }}</small>
                                @else
                                <span class="text-muted">Unknown Client</span>
                                @endif
                            </td>
                            <td>
                                {{ $req->currentPackage->name ?? 'N/A' }}
                                <br><small class="text-muted">৳{{ number_format($req->currentPackage->price ?? 0, 2) }}</small>
                            </td>
                            <td>
                                {{ $req->requestedPackage->name ?? 'N/A' }}
                                <br><small class="text-success">৳{{ number_format($req->requestedPackage->price ?? 0, 2) }}</small>
                                @php $diff = ($req->requestedPackage->price ?? 0) - ($req->currentPackage->price ?? 0); @endphp
                                @if($diff > 0)
                                <br><span class="badge badge-info">+৳{{ number_format($diff) }}</span>
                                @elseif($diff < 0)
                                <br><span class="badge badge-secondary">-৳{{ number_format(abs($diff)) }}</span>
                                @endif
                            </td>
                            <td>{{ $req->created_at->format('d-m-Y') }}</td>
                            <td>
                                <span class="badge badge-{{ $req->status == 'approved' ? 'success' : ($req->status == 'rejected' ? 'danger' : 'warning') }}">
                                    {{ ucfirst($req->status) }}
                                </span>
                            </td>
                            <td>
                                @if($req->status == 'pending')
                                <form action="{{ route('admin.clients.package-requests.process', $req) }}" method="POST" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="action" value="approve">
                                    <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Approve this package change?')">
                                        <i class="fas fa-check"></i>
                                    </button>
                                </form>
                                <form action="{{ route('admin.clients.package-requests.process', $req) }}" method="POST" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="action" value="reject">
                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Reject this request?')">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </form>
                                @else
                                <small class="text-muted">
                                    By {{ $req->approver->name ?? 'System' }}
                                    <br>{{ $req->effective_date?->format('d-m-Y') }}
                                </small>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No pending package change requests</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="card-footer"><div class="d-flex flex-wrap justify-content-between align-items-center mt-2 mb-1">
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
        {{ $requests->appends(request()->query())->links() }}
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
</script></div>
</div>
@endsection
