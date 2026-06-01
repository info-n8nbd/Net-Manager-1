@extends('layouts.admin')

@section('title', 'Damaged Items')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="#">Inventory</a></li>
<li class="breadcrumb-item active">Damaged Items</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header d-flex align-items-center">
        <h3 class="card-title">Damaged Items</h3>
        <div class="input-group input-group-sm ml-auto" style="width:250px;">
            <div class="input-group-prepend">
                <span class="input-group-text"><i class="fas fa-search"></i></span>
            </div>
            <input type="text" class="form-control form-control-sm table-quick-search" placeholder="Quick search..." data-table="damagedItemsTable">
        </div>
    </div>
    <div class="card-body">
        <table id="damagedItemsTable" class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Product</th>
                    <th>Serial</th>
                    <th>Quantity</th>
                    <th>Reason</th>
                    <th>Reported By</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $item)
                <tr>
                    <td>{{ $item->created_at->format('d-m-Y') }}</td>
                    <td>{{ $item->product->name ?? 'N/A' }}</td>
                    <td>{{ $item->serial->serial_number ?? '-' }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>{{ $item->damage_reason ?? '-' }}</td>
                    <td>{{ $item->reporter->name ?? 'N/A' }}</td>
                    <td class="text-nowrap">
                        <a href="{{ route('admin.inventory.damage.edit', $item->id) }}" class="btn btn-warning btn-xs" title="Edit">
                            <i class="fas fa-edit"></i>
                        </a>
                        <form action="{{ route('admin.inventory.damage.destroy', $item->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this damage record?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-xs" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center">No damaged items found</td></tr>
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
        {{ $items->appends(request()->query())->links() }}
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
$('.table-quick-search').on('input', function() {
    var q = $(this).val().toLowerCase();
    var tableId = $(this).data('table');
    $('#' + tableId + ' tbody tr').each(function() {
        var text = $(this).text().toLowerCase();
        $(this).toggle(!q || text.indexOf(q) !== -1);
    });
});
</script>
@endpush
