@extends('layouts.admin')

@section('title', 'Card Batches')

@section('content')
<div class="container-fluid">

    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-layer-group mr-2"></i>Card Batches</h3>
            <div class="card-tools">
                <a href="{{ route('admin.card-billing.create-batch') }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus mr-1"></i>Create New Batch
                </a>
            </div>
        </div>
        <div class="card-body">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Batch Name</th>
                        <th>Package</th>
                        <th>Amount (৳)</th>
                        <th>Total Cards</th>
                        <th>Used</th>
                        <th>Active</th>
                        <th>Validity (Days)</th>
                        <th>Expires At</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($batches as $batch)
                    <tr>
                        <td>{{ $batch->id }}</td>
                        <td>{{ $batch->name }}</td>
                        <td>{{ $batch->package->name ?? 'N/A' }}</td>
                        <td>{{ number_format($batch->amount, 2) }}</td>
                        <td><span class="badge badge-info">{{ $batch->total_cards }}</span></td>
                        <td><span class="badge badge-warning">{{ $batch->used_cards ?? 0 }}</span></td>
                        <td><span class="badge badge-success">{{ $batch->active_cards ?? 0 }}</span></td>
                        <td>{{ $batch->validity_days }}</td>
                        <td>{{ $batch->expires_at ? $batch->expires_at->format('d-m-Y') : 'N/A' }}</td>
                        <td>
                            @if($batch->is_active)
                                <span class="badge badge-success">Active</span>
                            @else
                                <span class="badge badge-danger">Inactive</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('admin.card-billing.batches.show', $batch) }}" class="btn btn-sm btn-info" title="View Cards">
                                <i class="fas fa-eye"></i>
                            </a>
                            <form action="{{ route('admin.card-billing.destroy-batch', $batch) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this batch and all its cards?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger" title="Delete"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="11" class="text-center">No card batches found</td></tr>
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
        {{ $batches->appends(request()->query())->links() }}
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
</div>
@endsection
