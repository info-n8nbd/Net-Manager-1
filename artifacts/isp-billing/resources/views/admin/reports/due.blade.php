@extends('layouts.admin')

@section('title', 'Due Report')
@section('breadcrumb')
<li class="breadcrumb-item active">Due Report</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header"><h3 class="card-title">Due Report</h3></div>
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-6 col-md-4">
                <div class="stat-box bg-danger text-white text-center py-3 rounded">
                    <div class="stat-number">৳{{ number_format($totalDue ?? 0, 0) }}</div>
                    <div class="stat-label">Total Due</div>
                </div>
            </div>
        </div>
        <table class="table table-bordered table-striped">
            <thead>
                <tr><th>Client ID</th><th>Name</th><th>Phone</th><th>Zone</th><th>Package</th><th>Due (৳)</th><th>Actions</th></tr>
            </thead>
            <tbody>
                @forelse($clients as $client)
                <tr>
                    <td>{{ $client->client_id }}</td>
                    <td>{{ $client->name }}</td>
                    <td>{{ $client->phone }}</td>
                    <td>{{ $client->zone->name ?? '-' }}</td>
                    <td>{{ $client->package->name ?? '-' }}</td>
                    <td class="text-danger font-weight-bold">{{ number_format($client->due_amount ?? 0, 2) }}</td>
                    <td><a href="{{ route('admin.payments.create') }}?client_id={{ $client->id }}" class="btn btn-success btn-xs">Collect</a></td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center">No due amounts</td></tr>
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
