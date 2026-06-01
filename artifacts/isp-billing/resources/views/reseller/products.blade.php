@extends('layouts.reseller')
@section('title', 'Products')
@section('breadcrumb')<li class="breadcrumb-item active">Products</li>@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-box mr-2"></i>Available Products</h3>
    </div>
    <div class="card-body">
        <div class="row">
            @forelse($products as $product)
            <div class="col-md-4 col-lg-3 mb-3">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">{{ $product->name }}</h5>
                        <p class="text-muted mb-1"><small>Code: {{ $product->code }}</small></p>
                        <p class="mb-2">{{ $product->description ?? 'No description' }}</p>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="h5 mb-0 text-primary">৳{{ number_format($product->selling_price ?? 0, 2) }}</span>
                            <span class="badge badge-{{ $product->stock_quantity > 0 ? 'success' : 'danger' }}">
                                {{ $product->stock_quantity > 0 ? 'In Stock' : 'Out of Stock' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            @empty
            <div class="col-12 text-center py-4">
                <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                <p class="text-muted">No products available</p>
            </div>
            @endforelse
        </div>
        {{ $products->links() }}
    </div>
</div>
@endsection
