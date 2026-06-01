@extends('layouts.admin')

@section('title', 'Products')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.store.products') }}">Store</a></li>
<li class="breadcrumb-item active">Products</li>
@endsection

@section('content')
@php
    $totalProducts = $products->total();
    $lowStockCount = $lowStockCount ?? 0;
    $outOfStockCount = $outOfStockCount ?? 0;
    $totalValue = $totalValue ?? 0;
@endphp

<div class="row">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>{{ $totalProducts }}</h3>
                <p>Total Products</p>
            </div>
            <div class="icon"><i class="fas fa-boxes"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3>{{ $lowStockCount }}</h3>
                <p>Low Stock</p>
            </div>
            <div class="icon"><i class="fas fa-exclamation-triangle"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-danger">
            <div class="inner">
                <h3>{{ $outOfStockCount }}</h3>
                <p>Out of Stock</p>
            </div>
            <div class="icon"><i class="fas fa-times-circle"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>৳{{ number_format($totalValue, 0) }}</h3>
                <p>Total Stock Value</p>
            </div>
            <div class="icon"><i class="fas fa-money-bill-wave"></i></div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-boxes mr-2"></i>Product List</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addProductModal">
                <i class="fas fa-plus"></i> Add Product
            </button>
        </div>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('admin.store.products') }}" class="mb-3">
            <div class="row">
                <div class="col-md-3 col-sm-6 mb-2">
                    <input type="text" name="search" class="form-control" placeholder="Search by name or code..." value="{{ request('search') }}">
                </div>
                <div class="col-md-2 col-sm-6 mb-2">
                    <select name="category_id" class="form-control">
                        <option value="">All Categories</option>
                        @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 col-sm-6 mb-2">
                    <select name="type" class="form-control">
                        <option value="">All Types</option>
                        <option value="product" {{ request('type') == 'product' ? 'selected' : '' }}>Product</option>
                        <option value="service" {{ request('type') == 'service' ? 'selected' : '' }}>Service</option>
                        <option value="consumable" {{ request('type') == 'consumable' ? 'selected' : '' }}>Consumable</option>
                    </select>
                </div>
                <div class="col-md-2 col-sm-6 mb-2">
                    <select name="status" class="form-control">
                        <option value="">All Status</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                        <option value="low_stock" {{ request('status') == 'low_stock' ? 'selected' : '' }}>Low Stock</option>
                        <option value="out_of_stock" {{ request('status') == 'out_of_stock' ? 'selected' : '' }}>Out of Stock</option>
                    </select>
                </div>
                <div class="col-md-3 col-sm-12 mb-2">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filter</button>
                    <a href="{{ route('admin.store.products') }}" class="btn btn-secondary"><i class="fas fa-redo"></i> Reset</a>
                </div>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-bordered table-hover table-striped">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Brand/Model</th>
                        <th>Type</th>
                        <th>Purchase Price</th>
                        <th>Selling Price</th>
                        <th>Stock</th>
                        <th>Alert Qty</th>
                        <th>Warranty</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                    @php
                        $isOutOfStock = $product->stock_quantity <= 0;
                        $isLowStock = !$isOutOfStock && $product->stock_quantity <= ($product->alert_quantity ?? 0);
                    @endphp
                    <tr class="{{ $isOutOfStock ? 'table-danger' : ($isLowStock ? 'table-warning' : '') }}">
                        <td><code>{{ $product->code }}</code></td>
                        <td>
                            {{ $product->name }}
                            @if($product->has_serial)
                            <span class="badge badge-info badge-sm" title="Serial Tracking"><i class="fas fa-barcode"></i></span>
                            @endif
                        </td>
                        <td>{{ $product->category->name ?? 'N/A' }}</td>
                        <td>
                            @if($product->brand || $product->model)
                            {{ $product->brand }}{{ $product->brand && $product->model ? ' / ' : '' }}{{ $product->model }}
                            @else
                            <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td><span class="badge badge-{{ $product->type == 'service' ? 'info' : ($product->type == 'consumable' ? 'warning' : 'primary') }}">{{ ucfirst($product->type ?? 'product') }}</span></td>
                        <td class="text-right">৳{{ number_format($product->purchase_price, 2) }}</td>
                        <td class="text-right">৳{{ number_format($product->selling_price, 2) }}</td>
                        <td class="text-center">
                            @if($isOutOfStock)
                            <span class="badge badge-danger">{{ $product->stock_quantity }} {{ $product->unit }}</span>
                            @elseif($isLowStock)
                            <span class="badge badge-warning">{{ $product->stock_quantity }} {{ $product->unit }}</span>
                            @else
                            <span class="badge badge-success">{{ $product->stock_quantity }} {{ $product->unit }}</span>
                            @endif
                        </td>
                        <td class="text-center">{{ $product->alert_quantity ?? 0 }}</td>
                        <td class="text-center">
                            @if($product->warranty_months)
                            {{ $product->warranty_months }} mo
                            @else
                            <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <span class="badge badge-{{ $product->is_active ? 'success' : 'danger' }}">{{ $product->is_active ? 'Active' : 'Inactive' }}</span>
                        </td>
                        <td>
                            <button type="button" class="btn btn-sm btn-info" data-toggle="modal" data-target="#editProductModal{{ $product->id }}" title="Edit"><i class="fas fa-edit"></i></button>
                            <form action="{{ route('admin.store.products.destroy', $product) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this product?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger" {{ $product->stock_quantity > 0 ? 'disabled' : '' }} title="Delete"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="12" class="text-center text-muted py-4"><i class="fas fa-inbox fa-2x mb-2 d-block"></i>No products found</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">
            {{ $products->appends(request()->query())->links() }}
        </div>
    </div>
</div>

<div class="modal fade" id="addProductModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('admin.store.products.store') }}" method="POST">
                @csrf
                <div class="modal-header bg-primary">
                    <h4 class="modal-title text-white"><i class="fas fa-plus mr-2"></i>Add Product</h4>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Code <span class="text-danger">*</span></label>
                                <input type="text" name="code" class="form-control" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Category</label>
                                <select name="category_id" class="form-control">
                                    <option value="">None</option>
                                    @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Type</label>
                                <select name="type" class="form-control">
                                    <option value="product">Product</option>
                                    <option value="service">Service</option>
                                    <option value="consumable">Consumable</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Brand</label>
                                <input type="text" name="brand" class="form-control" placeholder="e.g. TP-Link">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Model</label>
                                <input type="text" name="model" class="form-control" placeholder="e.g. TL-WR840N">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Unit</label>
                                <input type="text" name="unit" class="form-control" value="pcs">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Purchase Price (৳) <span class="text-danger">*</span></label>
                                <input type="number" name="purchase_price" class="form-control" step="0.01" min="0" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Selling Price (৳) <span class="text-danger">*</span></label>
                                <input type="number" name="selling_price" class="form-control" step="0.01" min="0" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Stock Quantity</label>
                                <input type="number" name="stock_quantity" class="form-control" value="0" min="0">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Alert Quantity</label>
                                <input type="number" name="alert_quantity" class="form-control" value="5" min="0">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Warranty (Months)</label>
                                <input type="number" name="warranty_months" class="form-control" value="0" min="0">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Barcode Prefix</label>
                                <input type="text" name="barcode_prefix" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label>Description</label>
                                <textarea name="description" class="form-control" rows="2"></textarea>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group pt-4">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="has_serial_add" name="has_serial" value="1">
                                    <label class="custom-control-label" for="has_serial_add">Track Serial Numbers</label>
                                </div>
                                <div class="custom-control custom-checkbox mt-2">
                                    <input type="checkbox" class="custom-control-input" id="is_active_add" name="is_active" value="1" checked>
                                    <label class="custom-control-label" for="is_active_add">Active</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i>Save Product</button>
                </div>
            </form>
        </div>
    </div>
</div>

@foreach($products as $product)
<div class="modal fade" id="editProductModal{{ $product->id }}">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('admin.store.products.update', $product) }}" method="POST">
                @csrf @method('PUT')
                <div class="modal-header bg-info">
                    <h4 class="modal-title text-white"><i class="fas fa-edit mr-2"></i>Edit Product</h4>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" value="{{ $product->name }}" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Code <span class="text-danger">*</span></label>
                                <input type="text" name="code" class="form-control" value="{{ $product->code }}" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Category</label>
                                <select name="category_id" class="form-control">
                                    <option value="">None</option>
                                    @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}" {{ $product->category_id == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Type</label>
                                <select name="type" class="form-control">
                                    <option value="product" {{ $product->type == 'product' ? 'selected' : '' }}>Product</option>
                                    <option value="service" {{ $product->type == 'service' ? 'selected' : '' }}>Service</option>
                                    <option value="consumable" {{ $product->type == 'consumable' ? 'selected' : '' }}>Consumable</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Brand</label>
                                <input type="text" name="brand" class="form-control" value="{{ $product->brand }}">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Model</label>
                                <input type="text" name="model" class="form-control" value="{{ $product->model }}">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Unit</label>
                                <input type="text" name="unit" class="form-control" value="{{ $product->unit }}">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Purchase Price (৳) <span class="text-danger">*</span></label>
                                <input type="number" name="purchase_price" class="form-control" step="0.01" min="0" value="{{ $product->purchase_price }}" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Selling Price (৳) <span class="text-danger">*</span></label>
                                <input type="number" name="selling_price" class="form-control" step="0.01" min="0" value="{{ $product->selling_price }}" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Stock Quantity</label>
                                <input type="number" name="stock_quantity" class="form-control" value="{{ $product->stock_quantity }}" min="0">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Alert Quantity</label>
                                <input type="number" name="alert_quantity" class="form-control" value="{{ $product->alert_quantity }}" min="0">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Warranty (Months)</label>
                                <input type="number" name="warranty_months" class="form-control" value="{{ $product->warranty_months ?? 0 }}" min="0">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Barcode Prefix</label>
                                <input type="text" name="barcode_prefix" class="form-control" value="{{ $product->barcode_prefix }}">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label>Description</label>
                                <textarea name="description" class="form-control" rows="2">{{ $product->description }}</textarea>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group pt-4">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="has_serial_{{ $product->id }}" name="has_serial" value="1" {{ $product->has_serial ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="has_serial_{{ $product->id }}">Track Serial Numbers</label>
                                </div>
                                <div class="custom-control custom-checkbox mt-2">
                                    <input type="checkbox" class="custom-control-input" id="is_active_{{ $product->id }}" name="is_active" value="1" {{ $product->is_active ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="is_active_{{ $product->id }}">Active</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info"><i class="fas fa-save mr-1"></i>Update Product</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach

@endsection
