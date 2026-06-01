@extends('layouts.admin')

@section('title', 'Stock Movements')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="#">Inventory</a></li>
<li class="breadcrumb-item active">Stock Movements</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header d-flex align-items-center">
        <h3 class="card-title">Stock Movement History @if($product) - {{ $product->name }} @endif</h3>
        <div class="input-group input-group-sm ml-auto" style="width:250px;">
            <div class="input-group-prepend">
                <span class="input-group-text"><i class="fas fa-search"></i></span>
            </div>
            <input type="text" class="form-control form-control-sm table-quick-search" placeholder="Quick search..." data-table="stockMovementsTable">
        </div>
    </div>
    <div class="card-body">
        <table id="stockMovementsTable" class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Product</th>
                    <th>Type</th>
                    <th>Quantity</th>
                    <th>Reference</th>
                    <th>Description</th>
                    <th>By</th>
                </tr>
            </thead>
            <tbody>
                @forelse($movements as $movement)
                <tr>
                    <td>{{ $movement->created_at->format('d-m-y H:i') }}</td>
                    <td>{{ $movement->product->name ?? 'N/A' }}</td>
                    <td>
                        @if($movement->type === 'in')
                            <span class="badge badge-success">Stock In</span>
                        @elseif($movement->type === 'out')
                            <span class="badge badge-danger">Stock Out</span>
                        @else
                            <span class="badge badge-warning">Adjustment</span>
                        @endif
                    </td>
                    <td>{{ $movement->quantity }}</td>
                    <td>{{ $movement->reference ?? '-' }}</td>
                    <td>{{ $movement->description ?? '-' }}</td>
                    <td>{{ $movement->creator->name ?? 'N/A' }}</td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center">No stock movements found</td></tr>
                @endforelse
            </tbody>
        </table>
        {{ $movements->links() }}
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
