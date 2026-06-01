@extends('layouts.admin')

@section('title', 'Credit/Debit Notes')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
<li class="breadcrumb-item active">Credit/Debit Notes</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-4">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3>{{ $pendingCount }}</h3>
                <p>Pending Notes</p>
            </div>
            <div class="icon"><i class="fas fa-clock"></i></div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Credit/Debit Notes</h3>
    </div>
    <div class="card-body">
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Client</th>
                    <th>Type</th>
                    <th>Amount (৳)</th>
                    <th>Reason</th>
                    <th>Status</th>
                    <th>Created By</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($notes as $note)
                <tr>
                    <td>{{ $note->id }}</td>
                    <td>{{ $note->client->name ?? 'N/A' }}</td>
                    <td>
                        <span class="badge badge-{{ $note->type == 'credit' ? 'success' : 'danger' }}">
                            {{ ucfirst($note->type) }}
                        </span>
                    </td>
                    <td>{{ number_format($note->amount, 2) }}</td>
                    <td>{{ $note->reason }}</td>
                    <td>
                        <span class="badge badge-{{ $note->status == 'approved' ? 'success' : ($note->status == 'rejected' ? 'danger' : 'warning') }}">
                            {{ ucfirst($note->status) }}
                        </span>
                    </td>
                    <td>{{ $note->createdBy->name ?? 'System' }}</td>
                    <td>{{ $note->created_at->format('d-m-Y') }}</td>
                    <td>
                        @if($note->status == 'pending')
                            <button class="btn btn-sm btn-success" onclick="approveNote({{ $note->id }})"><i class="fas fa-check"></i></button>
                            <button class="btn btn-sm btn-danger" onclick="rejectNote({{ $note->id }})"><i class="fas fa-times"></i></button>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="9" class="text-center">No notes found</td></tr>
                @endforelse
            </tbody>
        </table>
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
        {{ $notes->appends(request()->query())->links() }}
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
