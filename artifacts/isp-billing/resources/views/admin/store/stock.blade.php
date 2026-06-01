@extends('layouts.admin')

@section('title', 'Inventory Dashboard')
@section('breadcrumb')
<li class="breadcrumb-item active">Inventory Dashboard</li>
@endsection

@section('content')
<div class="container-fluid">

{{-- Summary Cards --}}
<div class="row mb-3">
    <div class="col-6 col-md-2 mb-2">
        <div class="small-box bg-primary mb-0">
            <div class="inner">
                <h3>{{ $summary['total_products'] }}</h3>
                <p>Total Products</p>
            </div>
            <div class="icon"><i class="fas fa-boxes"></i></div>
        </div>
    </div>
    <div class="col-6 col-md-2 mb-2">
        <div class="small-box bg-success mb-0">
            <div class="inner">
                <h3>{{ number_format($summary['total_in_stock']) }}</h3>
                <p>In Stock</p>
            </div>
            <div class="icon"><i class="fas fa-cubes"></i></div>
        </div>
    </div>
    <div class="col-6 col-md-2 mb-2">
        <div class="small-box bg-info mb-0">
            <div class="inner">
                <h3>{{ number_format($summary['total_purchased']) }}</h3>
                <p>Total Purchased</p>
            </div>
            <div class="icon"><i class="fas fa-truck-loading"></i></div>
        </div>
    </div>
    <div class="col-6 col-md-2 mb-2">
        <div class="small-box bg-danger mb-0">
            <div class="inner">
                <h3>{{ number_format($summary['total_sold']) }}</h3>
                <p>Given to Clients</p>
            </div>
            <div class="icon"><i class="fas fa-share-square"></i></div>
        </div>
    </div>
    <div class="col-6 col-md-2 mb-2">
        <div class="small-box bg-warning mb-0">
            <div class="inner">
                <h3>{{ $summary['low_stock'] }}</h3>
                <p>Low Stock</p>
            </div>
            <div class="icon"><i class="fas fa-exclamation-triangle"></i></div>
        </div>
    </div>
    <div class="col-6 col-md-2 mb-2">
        <div class="small-box mb-0" style="background:#6f42c1;">
            <div class="inner text-white">
                <h3 style="font-size:1.3rem;">৳{{ number_format($summary['stock_value'], 0) }}</h3>
                <p>Stock Value</p>
            </div>
            <div class="icon"><i class="fas fa-coins"></i></div>
        </div>
    </div>
</div>

{{-- Main Table --}}
<div class="card card-outline card-primary shadow-sm">
    <div class="card-header d-flex align-items-center justify-content-between py-2">
        <h3 class="card-title mb-0"><i class="fas fa-warehouse mr-2"></i>Product Inventory Status</h3>
        <div>
            <a href="{{ route('admin.store.purchases') }}" class="btn btn-info btn-sm mr-1">
                <i class="fas fa-truck mr-1"></i>Purchases
            </a>
            <a href="{{ route('admin.sales.index') }}" class="btn btn-success btn-sm mr-1">
                <i class="fas fa-receipt mr-1"></i>Sales
            </a>
            <a href="{{ route('admin.store.stock-movements') }}" class="btn btn-secondary btn-sm">
                <i class="fas fa-exchange-alt mr-1"></i>Movements
            </a>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered table-hover table-sm mb-0" style="font-size:13px;">
                <thead style="background:#1a3c5e; color:#fff;">
                    <tr>
                        <th style="width:40px;">#</th>
                        <th>Product</th>
                        <th style="width:110px;">Category</th>
                        <th class="text-center" style="width:100px;">
                            <i class="fas fa-truck mr-1"></i>Purchased
                        </th>
                        <th class="text-center" style="width:110px;">
                            <i class="fas fa-users mr-1"></i>Given to Clients
                        </th>
                        <th class="text-center" style="width:90px;">
                            <i class="fas fa-box mr-1"></i>Remaining
                        </th>
                        <th class="text-center" style="width:90px;">Alert</th>
                        <th class="text-right" style="width:110px;">Stock Value</th>
                        <th class="text-center" style="width:100px;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @php $catGroup = null; $rowNum = 0; @endphp
                    @foreach($products->sortBy(fn($p) => $p->category?->name ?? 'zzz') as $product)
                    @php
                        $cat = $product->category?->name ?? 'Uncategorized';
                        $sold = (int)($product->total_sold ?? 0);
                        $purchased = (int)($product->total_purchased ?? 0);
                        $remaining = $product->stock_quantity ?? 0;
                        $alert = $product->alert_quantity ?? 0;
                        $value = $remaining * ($product->purchase_price ?? 0);
                        $rowNum++;
                    @endphp
                    @if($catGroup !== $cat)
                    @php $catGroup = $cat; @endphp
                    <tr style="background:#e8f4fd;">
                        <td colspan="9" class="py-1 px-3">
                            <strong><i class="fas fa-folder-open mr-1 text-primary"></i>{{ $cat }}</strong>
                        </td>
                    </tr>
                    @endif
                    <tr class="{{ $remaining <= 0 ? 'table-danger' : ($remaining <= $alert ? 'table-warning' : '') }}">
                        <td class="text-center text-muted">{{ $rowNum }}</td>
                        <td>
                            <strong>{{ $product->name }}</strong>
                            @if($product->brand)
                            <br><small class="text-muted">{{ $product->brand }} {{ $product->model }}</small>
                            @endif
                        </td>
                        <td><span class="badge badge-light border">{{ $cat }}</span></td>
                        <td class="text-center">
                            <span class="badge badge-info px-2">{{ $purchased > 0 ? $purchased : $remaining + $sold }}</span>
                        </td>
                        <td class="text-center">
                            @if($sold > 0)
                                <span class="badge badge-danger px-2">{{ $sold }}</span>
                            @else
                                <span class="badge badge-secondary px-2">0</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <span class="font-weight-bold {{ $remaining <= 0 ? 'text-danger' : ($remaining <= $alert ? 'text-warning' : 'text-success') }}">
                                {{ $remaining }}
                            </span>
                            <small class="text-muted">{{ $product->unit }}</small>
                        </td>
                        <td class="text-center text-muted">{{ $alert }}</td>
                        <td class="text-right font-weight-bold">৳{{ number_format($value, 0) }}</td>
                        <td class="text-center">
                            @if($remaining <= 0)
                                <span class="badge badge-danger"><i class="fas fa-times-circle mr-1"></i>Out</span>
                            @elseif($remaining <= $alert)
                                <span class="badge badge-warning"><i class="fas fa-exclamation mr-1"></i>Low</span>
                            @else
                                <span class="badge badge-success"><i class="fas fa-check mr-1"></i>OK</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot style="background:#f4f6f9; font-weight:bold;">
                    <tr>
                        <td colspan="3" class="text-right">Total:</td>
                        <td class="text-center text-info">{{ $products->sum(fn($p) => max((int)($p->total_purchased??0), $p->stock_quantity + (int)($p->total_sold??0))) }}</td>
                        <td class="text-center text-danger">{{ $products->sum(fn($p) => (int)($p->total_sold??0)) }}</td>
                        <td class="text-center text-success">{{ number_format($summary['total_in_stock']) }}</td>
                        <td></td>
                        <td class="text-right">৳{{ number_format($summary['stock_value'], 0) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

</div>
@endsection
