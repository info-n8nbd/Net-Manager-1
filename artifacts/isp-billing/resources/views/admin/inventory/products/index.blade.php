@extends('layouts.admin')

@section('title', 'Products - Inventory')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="#">Inventory</a></li>
<li class="breadcrumb-item active">Products</li>
@endsection

@section('content')
<div class="card card-primary card-outline">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-list mr-2"></i>Product List</h3>
        <div class="card-tools">
            @if($__canAction('inventory', 'add_product'))
            <a href="{{ route('admin.inventory.products.create') }}" class="btn btn-primary btn-sm">
                <i class="fas fa-plus mr-1"></i> Add Product
            </a>
            @endif
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="productsTable" class="table table-bordered table-hover table-striped" style="width:100%">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Code</th>
                        <th>Category</th>
                        <th>Type</th>
                        <th>Brand</th>
                        <th>Model</th>
                        <th>Unit</th>
                        <th>Price</th>
                        <th>Stock Qty</th>
                        <th>Serial Tracking</th>
                        <th>Status</th>
                        <th width="130">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($products as $item)
                    <tr class="{{ isset($item->stock_quantity) && isset($item->alert_quantity) && $item->stock_quantity <= $item->alert_quantity && $item->stock_quantity > 0 ? 'table-warning' : '' }}{{ isset($item->stock_quantity) && $item->stock_quantity == 0 ? ' table-danger' : '' }}">
                        <td>
                            <strong>{{ $item->name }}</strong>
                            @if(isset($item->stock_quantity) && isset($item->alert_quantity) && $item->stock_quantity <= $item->alert_quantity)
                                <br><small class="text-danger"><i class="fas fa-exclamation-triangle"></i> Low Stock</small>
                            @endif
                        </td>
                        <td><code>{{ $item->code }}</code></td>
                        <td>{{ $item->category->name ?? '-' }}</td>
                        <td><span class="badge badge-info">{{ ucfirst($item->type) }}</span></td>
                        <td>{{ $item->brand ?? '-' }}</td>
                        <td>{{ $item->model ?? '-' }}</td>
                        <td>{{ $item->unit ?? 'pcs' }}</td>
                        <td class="text-right">{{ number_format($item->selling_price ?? 0, 2) }}</td>
                        <td class="text-center">
                            <span class="badge {{ isset($item->stock_quantity) && isset($item->alert_quantity) && $item->stock_quantity <= $item->alert_quantity ? 'badge-danger' : 'badge-success' }}">
                                {{ $item->stock_quantity ?? 0 }}
                            </span>
                        </td>
                        <td class="text-center">
                            @if($item->has_serial)
                                <span class="badge badge-primary"><i class="fas fa-barcode mr-1"></i>Yes</span>
                            @else
                                <span class="badge badge-secondary">No</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($item->is_active)
                                <span class="badge badge-success"><i class="fas fa-check-circle mr-1"></i>Active</span>
                            @else
                                <span class="badge badge-danger"><i class="fas fa-times-circle mr-1"></i>Inactive</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($__canAction('inventory', 'edit_product'))
                            <a href="{{ route('admin.inventory.products.edit', $item->id) }}" class="btn btn-warning btn-sm" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            @endif
                            @if($__canAction('inventory', 'delete_product'))
                            <form method="POST" action="{{ route('admin.inventory.products.destroy', $item->id) }}" style="display:inline" onsubmit="return confirm('Are you sure you want to delete this product?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    if ($('#productsTable tbody tr td[colspan]').length === 0) { $('#productsTable').DataTable({
        pageLength: 25,
        order: [[0, 'asc']],
        language: {
            search: "<i class='fas fa-search'></i> Search:",
            lengthMenu: "Show _MENU_ entries",
            info: "Showing _START_ to _END_ of _TOTAL_ products",
            emptyTable: "No products found"
        }
    }); }
});
</script>
@endpush
