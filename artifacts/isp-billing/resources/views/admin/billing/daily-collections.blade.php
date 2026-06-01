@extends('layouts.admin')
@section('title', 'Daily Collections')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.billing.daily-collections') }}">Billing</a></li>
<li class="breadcrumb-item active">Daily Collections</li>
@endsection
@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-md-3"><div class="small-box bg-info"><div class="inner"><h3>৳{{ number_format($totalAmount) }}</h3><p>Total Collection</p></div><div class="icon"><i class="fas fa-money-bill-wave"></i></div></div></div>
        <div class="col-md-3"><div class="small-box bg-success"><div class="inner"><h3>{{ $totalCount }}</h3><p>Total Bills Collected</p></div><div class="icon"><i class="fas fa-receipt"></i></div></div></div>
        <div class="col-md-3"><div class="small-box bg-warning"><div class="inner"><h3>৳{{ number_format($pendingAmount) }}</h3><p>Pending Approval</p></div><div class="icon"><i class="fas fa-clock"></i></div></div></div>
        <div class="col-md-3"><div class="small-box bg-primary"><div class="inner"><h3>৳{{ number_format($approvedAmount) }}</h3><p>Approved</p></div><div class="icon"><i class="fas fa-check-circle"></i></div></div></div>
    </div>
    <div class="row">
        <div class="col-md-4">
            <div class="card card-primary card-outline">
                <div class="card-header"><h3 class="card-title"><i class="fas fa-plus-circle mr-2"></i>Submit Collection</h3></div>
                <form action="{{ route('admin.billing.daily-collections.store') }}" method="POST">
                    @csrf
                    <div class="card-body">
                        <div class="form-group"><label>Client <span class="text-danger">*</span></label><select class="form-control" id="clientSearch" name="client_id" required></select></div>
                        <div id="clientInfo" class="alert alert-light d-none mb-3 p-2"><small><strong>Package:</strong> <span id="ciPackage"></span> | <strong>Due:</strong> ৳<span id="ciDue"></span></small></div>
                        <div class="form-group"><label>Amount (৳) <span class="text-danger">*</span></label><input type="number" name="amount" class="form-control" step="1" min="1" required placeholder="Enter amount"></div>
                        <div class="form-group"><label>Collection Date</label><input type="date" name="collection_date" class="form-control" value="{{ $date }}"></div>
                        <div class="form-group"><label>Payment Method</label><select name="payment_method" class="form-control"><option value="Cash">Cash</option><option value="bKash">bKash</option><option value="Nagad">Nagad</option><option value="Rocket">Rocket</option><option value="Bank">Bank Transfer</option></select></div>
                        <div class="form-group"><label>Remarks</label><textarea name="remarks" class="form-control" rows="2" placeholder="Optional notes..."></textarea></div>
                    </div>
                    <div class="card-footer"><button type="submit" class="btn btn-primary btn-block"><i class="fas fa-save mr-2"></i>Submit Collection</button></div>
                </form>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-list mr-2"></i>Collections for {{ \Carbon\Carbon::parse($date)->format('d M Y') }}</h3>
                    <div class="card-tools">
                        <form class="form-inline" method="GET">
                            <input type="date" name="date" class="form-control form-control-sm mr-2" value="{{ $date }}">
                            @if($isSuperAdmin && $collectors->count() > 0)
                            <select name="user_id" class="form-control form-control-sm mr-2"><option value="">All Employees</option>@foreach($collectors as $c)<option value="{{ $c->id }}" {{ request('user_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>@endforeach</select>
                            @endif
                            <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-search"></i></button>
                        </form>
                    </div>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm table-striped table-hover mb-0">
                        <thead class="bg-light"><tr><th>#</th><th>Employee</th><th>Client</th><th>Amount</th><th>Method</th><th>Time</th><th>Status</th>@if($isSuperAdmin)<th>Action</th>@endif</tr></thead>
                        <tbody>
                            @forelse($collections as $i => $col)
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td><i class="fas fa-user-tie mr-1 text-muted"></i>{{ $col->user->name ?? 'N/A' }}</td>
                                <td><strong>{{ $col->client->client_id ?? '' }}</strong><br><small class="text-muted">{{ $col->client->name ?? 'N/A' }}</small></td>
                                <td class="text-success font-weight-bold">৳{{ number_format($col->amount) }}</td>
                                <td><span class="badge badge-secondary">{{ $col->payment_method }}</span></td>
                                <td><small>{{ $col->created_at->format('h:i A') }}</small></td>
                                <td>
                                    @if($col->status == 'approved')<span class="badge badge-success"><i class="fas fa-check mr-1"></i>Approved</span>
                                    @elseif($col->status == 'rejected')<span class="badge badge-danger"><i class="fas fa-times mr-1"></i>Rejected</span>
                                    @else<span class="badge badge-warning"><i class="fas fa-clock mr-1"></i>Pending</span>@endif
                                </td>
                                @if($isSuperAdmin)
                                <td>
                                    @if($col->status == 'pending')
                                    <form action="{{ route('admin.billing.daily-collections.approve', $col) }}" method="POST" class="d-inline">@csrf<button class="btn btn-success btn-xs" title="Approve"><i class="fas fa-check"></i></button></form>
                                    <form action="{{ route('admin.billing.daily-collections.reject', $col) }}" method="POST" class="d-inline">@csrf<button class="btn btn-danger btn-xs" title="Reject"><i class="fas fa-times"></i></button></form>
                                    @else<small class="text-muted">{{ $col->approvedBy->name ?? '' }}</small>@endif
                                </td>
                                @endif
                            </tr>
                            @empty
                            <tr><td colspan="8" class="text-center py-4 text-muted"><i class="fas fa-inbox fa-2x mb-2 d-block"></i>No collections found for this date</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($isSuperAdmin && $dailySummary->count() > 0)
            <div class="card">
                <div class="card-header bg-gradient-info"><h3 class="card-title text-white"><i class="fas fa-chart-bar mr-2"></i>Employee Summary — {{ \Carbon\Carbon::parse($date)->format('d M Y') }}</h3></div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <thead class="bg-light"><tr><th>Employee</th><th>Bills Collected</th><th>Total Amount</th></tr></thead>
                        <tbody>@foreach($dailySummary as $s)<tr><td><i class="fas fa-user mr-1"></i>{{ $s->user->name ?? 'N/A' }}</td><td><span class="badge badge-info">{{ $s->total_bills }}</span></td><td class="text-success font-weight-bold">৳{{ number_format($s->total_amount) }}</td></tr>@endforeach</tbody>
                    </table>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
@push('scripts')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap4-theme@1.0.0/dist/select2-bootstrap4.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(function(){
    $('#clientSearch').select2({
        theme: 'bootstrap4', placeholder: 'Search client by name, ID, phone...',
        ajax: { url: '{{ route("admin.billing.daily-collections.search-clients") }}', dataType: 'json', delay: 300,
            data: function(params){ return { q: params.term }; },
            processResults: function(data){ return { results: data.results }; }, minimumInputLength: 2 }
    }).on('select2:select', function(e){
        var d = e.params.data; $('#ciPackage').text(d.package); $('#ciDue').text(d.due); $('#clientInfo').removeClass('d-none');
    });
});
</script>
@endpush
