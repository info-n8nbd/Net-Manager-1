@extends('layouts.admin')

@section('title', 'Left Clients')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.clients.index') }}">Clients</a></li>
<li class="breadcrumb-item active">Left Clients</li>
@endsection

@section('content')
<style>
.client-card { border-left: 4px solid #6c757d; margin-bottom: 12px; background: #fff; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
.client-card.deleted { border-left-color: #dc3545; }
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

<div class="row mb-3">
    <div class="col-md-6">
        <div class="info-box bg-secondary">
            <span class="info-box-icon"><i class="fas fa-user-slash"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Total Left</span>
                <span class="info-box-number">{{ $leftCount ?? 0 }}</span>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="info-box bg-danger">
            <span class="info-box-icon"><i class="fas fa-trash-alt"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Deleted</span>
                <span class="info-box-number">{{ $deletedCount ?? 0 }}</span>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-user-slash mr-2"></i>Left Clients (Deleted)</h3>
    </div>
    <div class="card-body">
        <div class="mobile-cards">
            @forelse($clients as $client)
            <div class="card client-card {{ $client->status === 'deleted' ? 'deleted' : '' }}">
                <div class="card-body">
                    <div class="client-header">
                        <h6 class="client-name">{{ $client->name }}</h6>
                        @if($client->status === 'deleted')
                        <span class="badge badge-danger">Deleted</span>
                        @else
                        <span class="badge badge-warning">Inactive</span>
                        @endif
                    </div>
                    <div class="client-info">
                        <strong>ID:</strong> {{ $client->client_id }} | <strong>Phone:</strong> {{ $client->phone }}
                        @if($client->username)<br><strong>PPPoE:</strong> {{ $client->username }}@endif
                    </div>
                    <div class="client-meta">
                        <span><i class="fas fa-map-marker-alt mr-1"></i>{{ $client->zone->name ?? '-' }}</span>
                        <span><i class="fas fa-box mr-1"></i>{{ $client->package->name ?? '-' }}</span>
                        <span class="text-danger"><strong>Due:</strong> ৳{{ number_format($client->due_amount ?? 0) }}</span>
                    </div>
                    @if($client->status === 'deleted' && $client->deleted_reason)
                    <div class="text-muted mb-2" style="font-size: 12px;"><i class="fas fa-comment mr-1"></i>{{ $client->deleted_reason }}</div>
                    @endif
                    <div class="btn-group-mobile">
                        <button type="button" class="btn btn-success btn-sm" data-toggle="modal" data-target="#restoreModal{{ $client->id }}">
                            <i class="fas fa-redo mr-1"></i> Restore
                        </button>
                        <a href="tel:{{ $client->phone }}" class="btn btn-info btn-sm">
                            <i class="fas fa-phone mr-1"></i> Call
                        </a>
                    </div>
                </div>
            </div>
            @empty
            <div class="text-center text-muted py-4">
                <i class="fas fa-check-circle fa-2x mb-2 d-block text-success"></i>
                No left clients! All your clients are active.
            </div>
            @endforelse
        </div>

        <div class="desktop-table">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-sm">
                    <thead>
                        <tr>
                            <th>Client ID</th>
                            <th>Name</th>
                            <th>Phone</th>
                            <th>PPPoE</th>
                            <th>Zone</th>
                            <th>Package</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Due (৳)</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($clients as $client)
                        <tr>
                            <td>{{ $client->client_id }}</td>
                            <td>{{ $client->name }}</td>
                            <td>{{ $client->phone }}</td>
                            <td>{{ $client->username ?? '-' }}</td>
                            <td>{{ $client->zone->name ?? '-' }}</td>
                            <td>{{ $client->package->name ?? '-' }}</td>
                            <td>
                                @if($client->status === 'deleted')
                                <span class="badge badge-danger">Deleted</span>
                                @if($client->deleted_reason)
                                <br><small class="text-muted">{{ Str::limit($client->deleted_reason, 30) }}</small>
                                @endif
                                @else
                                <span class="badge badge-warning">Inactive</span>
                                @if($client->auto_inactive_note)
                                <br><small class="text-muted">{{ Str::limit($client->auto_inactive_note, 30) }}</small>
                                @endif
                                @endif
                            </td>
                            <td>
                                @if($client->status === 'deleted' && $client->deleted_at_custom)
                                {{ \Carbon\Carbon::parse($client->deleted_at_custom)->format('d-m-Y') }}
                                @elseif($client->last_inactive_date)
                                {{ \Carbon\Carbon::parse($client->last_inactive_date)->format('d-m-Y') }}
                                @else
                                {{ $client->updated_at->format('d-m-Y') }}
                                @endif
                            </td>
                            <td>{{ number_format($client->due_amount ?? 0, 2) }}</td>
                            <td>
                                <button type="button" class="btn btn-success btn-xs" data-toggle="modal" data-target="#restoreModal{{ $client->id }}">
                                    <i class="fas fa-redo mr-1"></i>Restore
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="10" class="text-center text-muted py-4">
                            <i class="fas fa-check-circle fa-2x mb-2 d-block text-success"></i>
                            No left clients! All your clients are active.
                        </td></tr>
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

@foreach($clients as $client)
<div class="modal fade" id="restoreModal{{ $client->id }}" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius: 12px; border: none; box-shadow: 0 20px 60px rgba(0,0,0,0.15);">
            <div class="modal-header" style="background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%); color: #fff; border-radius: 12px 12px 0 0; padding: 14px 20px;">
                <h5 class="modal-title" style="font-weight: 700;"><i class="fas fa-redo mr-2"></i>Restore Client</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <form action="{{ route('admin.client-recovery.recover', $client->id) }}" method="POST">
                @csrf
                <div class="modal-body" style="padding: 20px;">
                    <div style="background: #f8f9fa; border-radius: 10px; padding: 12px 16px; margin-bottom: 16px;">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong style="font-size: 14px;">{{ $client->name }}</strong>
                                <div style="font-size: 12px; color: #888;">{{ $client->client_id }} | {{ $client->phone }}</div>
                            </div>
                            <div class="text-right">
                                <div style="font-size: 12px; color: #888;">Package</div>
                                <strong style="font-size: 14px; color: #333;">{{ $client->package->name ?? 'N/A' }}</strong>
                            </div>
                        </div>
                    </div>
                    <div class="alert alert-info mb-3" style="font-size: 13px;">
                        <i class="fas fa-info-circle mr-1"></i>
                        This will restore the client to <strong>Active</strong> status with all previous information (bills, payments, history).
                        @if($client->username && $client->connection_type === 'pppoe')
                        <br><i class="fas fa-server mr-1 mt-1"></i>PPPoE secret <strong>{{ $client->username }}</strong> will be re-created on MikroTik.
                        @endif
                    </div>
                    <table class="table table-sm table-bordered mb-3" style="font-size: 13px;">
                        <tr><td><strong>Zone:</strong></td><td>{{ $client->zone->name ?? '-' }}</td></tr>
                        <tr><td><strong>PPPoE:</strong></td><td>{{ $client->username ?? '-' }}</td></tr>
                        <tr><td><strong>Due Amount:</strong></td><td class="text-danger">৳{{ number_format($client->due_amount ?? 0, 2) }}</td></tr>
                        @if($client->status === 'deleted' && $client->deleted_reason)
                        <tr><td><strong>Delete Reason:</strong></td><td>{{ $client->deleted_reason }}</td></tr>
                        @endif
                    </table>
                    <div class="form-group">
                        <label style="font-size: 13px; font-weight: 600;"><i class="fas fa-sticky-note mr-1"></i>Restore Notes (Optional)</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Reason for restoring..." style="border-radius: 8px;"></textarea>
                    </div>
                </div>
                <div class="modal-footer" style="padding: 12px 20px; border-top: 1px solid #f1f3f5;">
                    <button type="button" class="btn btn-light" data-dismiss="modal" style="border-radius: 8px;">Cancel</button>
                    <button type="submit" class="btn btn-success" style="border-radius: 8px;"><i class="fas fa-redo mr-1"></i>Restore Client</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach
@endsection
