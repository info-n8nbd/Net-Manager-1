@extends('layouts.admin')

@section('title', 'Fiber History')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="#">Inventory</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.inventory.fiber.index') }}">Fiber Stock</a></li>
    <li class="breadcrumb-item active">History</li>
@endsection

@section('content')
<div class="card card-primary card-outline">
    <div class="card-header">
        <h3 class="card-title"><i class="fa-solid fa-list"></i> All Fiber History</h3>
    </div>
    <div class="card-body">
        <table id="historyTable" class="table table-bordered table-striped table-hover">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Fiber Code</th>
                    <th>Type</th>
                    <th>Meter</th>
                    <th>Details</th>
                </tr>
            </thead>
            <tbody>
                @forelse($histories as $history)
                <tr>
                    <td>{{ $history->date ? \Carbon\Carbon::parse($history->date)->format('d-m-Y') : 'N/A' }}</td>
                    <td><strong>{{ $history->fiber_code ?? 'N/A' }}</strong></td>
                    <td>
                        @if($history->type === 'stock_in')
                            <span class="badge badge-success"><i class="fa-solid fa-arrow-down"></i> Stock In</span>
                        @else
                            <span class="badge badge-warning text-dark"><i class="fa-solid fa-arrow-up"></i> Usage</span>
                        @endif
                    </td>
                    <td>{{ number_format($history->meter) }}m</td>
                    <td>{{ $history->details ?? '-' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center text-muted">No history records found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer clearfix">
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
        {{ $histories->appends(request()->query())->links() }}
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

@push('scripts')
<script>
$(document).ready(function() {
    if ($('#historyTable tbody tr td[colspan]').length === 0) { $('#historyTable').DataTable({
        responsive: true,
        paging: false,
        info: false,
        order: [[0, 'desc']]
    }); }
});
</script>
@endpush
