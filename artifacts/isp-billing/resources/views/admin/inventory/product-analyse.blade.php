@extends('layouts.admin')
@section('title', 'Product Analyse')
@section('content')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-0"><i class="fas fa-chart-pie mr-2 text-primary"></i>Product Analyse</h4>
            <small class="text-muted">Complete inventory analysis — instruments, products &amp; fiber</small>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="info-box shadow-sm">
                <span class="info-box-icon bg-info"><i class="fas fa-boxes"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Total Product Types</span>
                    <span class="info-box-number">{{ $totalProductTypes }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="info-box shadow-sm">
                <span class="info-box-icon bg-success"><i class="fas fa-check-circle"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Total Available Units</span>
                    <span class="info-box-number">{{ $totalAvailable }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="info-box shadow-sm">
                <span class="info-box-icon bg-warning"><i class="fas fa-user-check"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Total In Use</span>
                    <span class="info-box-number">{{ $totalInUse }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="info-box shadow-sm">
                <span class="info-box-icon bg-danger"><i class="fas fa-exclamation-triangle"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Damaged / Low Stock</span>
                    <span class="info-box-number">{{ $totalDamaged }} / {{ $lowStockCount }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Instruments Category Breakdown -->
    <div class="card card-outline card-primary shadow-sm mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0"><i class="fas fa-tools mr-2"></i>Instruments — Category Breakdown</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-striped table-hover mb-0">
                    <thead class="thead-dark">
                        <tr>
                            <th>#</th>
                            <th>Category</th>
                            <th>Sub Category</th>
                            <th class="text-center">Total</th>
                            <th class="text-center">Available</th>
                            <th class="text-center">In Use</th>
                            <th class="text-center">Damaged</th>
                            <th class="text-center">Usage %</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($instrumentGroups as $i => $g)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td><strong>{{ $g->category ?? 'N/A' }}</strong></td>
                            <td>
                                @if($g->sub_category)
                                    <span class="badge badge-light border">{{ $g->sub_category }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-center"><span class="badge badge-info">{{ $g->total }}</span></td>
                            <td class="text-center"><span class="badge badge-success">{{ $g->available }}</span></td>
                            <td class="text-center"><span class="badge badge-warning">{{ $g->in_use }}</span></td>
                            <td class="text-center"><span class="badge badge-danger">{{ $g->damaged }}</span></td>
                            <td class="text-center" style="min-width:100px;">
                                @php $pct = $g->total > 0 ? round($g->in_use / $g->total * 100) : 0; @endphp
                                <div class="progress" style="height:16px;">
                                    <div class="progress-bar {{ $pct >= 80 ? 'bg-danger' : ($pct >= 50 ? 'bg-warning' : 'bg-success') }}"
                                         style="width:{{ $pct }}%">{{ $pct }}%</div>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="8" class="text-center text-muted py-3">No instrument data found.</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="table-secondary font-weight-bold">
                            <td colspan="3" class="text-right pr-3">Total:</td>
                            <td class="text-center"><span class="badge badge-info">{{ $instrumentGroups->sum('total') }}</span></td>
                            <td class="text-center"><span class="badge badge-success">{{ $instrumentGroups->sum('available') }}</span></td>
                            <td class="text-center"><span class="badge badge-warning">{{ $instrumentGroups->sum('in_use') }}</span></td>
                            <td class="text-center"><span class="badge badge-danger">{{ $instrumentGroups->sum('damaged') }}</span></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- Products Stock Analysis -->
    <div class="card card-outline card-success shadow-sm mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0"><i class="fas fa-box-open mr-2"></i>Products Stock — Analysis</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-striped table-hover mb-0">
                    <thead class="thead-dark">
                        <tr>
                            <th>#</th>
                            <th>Category</th>
                            <th>Product Name</th>
                            <th>Code</th>
                            <th class="text-center">Stock</th>
                            <th class="text-center">Alert Level</th>
                            <th class="text-center">Status</th>
                            <th class="text-right">Purchase Price</th>
                            <th class="text-right">Stock Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($products as $i => $p)
                        <tr class="{{ $p->stock_quantity <= $p->alert_quantity ? 'table-warning' : '' }}">
                            <td>{{ $i + 1 }}</td>
                            <td><span class="badge badge-light border">{{ $p->category_name ?? 'N/A' }}</span></td>
                            <td><strong>{{ $p->name }}</strong></td>
                            <td><small class="text-muted">{{ $p->code ?? '—' }}</small></td>
                            <td class="text-center">
                                <span class="badge {{ $p->stock_quantity <= 0 ? 'badge-danger' : ($p->stock_quantity <= $p->alert_quantity ? 'badge-warning' : 'badge-success') }}">
                                    {{ $p->stock_quantity }}
                                </span>
                            </td>
                            <td class="text-center"><span class="badge badge-secondary">{{ $p->alert_quantity }}</span></td>
                            <td class="text-center">
                                @if($p->stock_quantity <= 0)
                                    <span class="badge badge-danger">Out of Stock</span>
                                @elseif($p->stock_quantity <= $p->alert_quantity)
                                    <span class="badge badge-warning"><i class="fas fa-exclamation-triangle mr-1"></i>Low Stock</span>
                                @else
                                    <span class="badge badge-success"><i class="fas fa-check mr-1"></i>OK</span>
                                @endif
                            </td>
                            <td class="text-right">{{ number_format($p->purchase_price, 2) }} &#2547;</td>
                            <td class="text-right font-weight-bold">{{ number_format($p->stock_quantity * $p->purchase_price, 2) }} &#2547;</td>
                        </tr>
                        @empty
                        <tr><td colspan="9" class="text-center text-muted py-3">No product data found.</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="table-secondary font-weight-bold">
                            <td colspan="8" class="text-right pr-3">Total Stock Value:</td>
                            <td class="text-right text-success">
                                @php $totalVal = $products->sum(function($p){ return $p->stock_quantity * $p->purchase_price; }); @endphp
                                {{ number_format($totalVal, 2) }} &#2547;
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <!-- Products by Category -->
        <div class="col-md-6">
            <div class="card card-outline card-warning shadow-sm h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="fas fa-sitemap mr-2"></i>Products by Category</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="thead-dark">
                                <tr>
                                    <th>Category</th>
                                    <th class="text-center">Items</th>
                                    <th class="text-center">Total Stock</th>
                                    <th class="text-right">Value (&#2547;)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($productsByCategory as $cat)
                                <tr>
                                    <td>{{ $cat->category_name ?? 'Uncategorized' }}</td>
                                    <td class="text-center"><span class="badge badge-info">{{ $cat->item_count }}</span></td>
                                    <td class="text-center"><span class="badge badge-success">{{ $cat->total_stock }}</span></td>
                                    <td class="text-right font-weight-bold">{{ number_format($cat->total_value, 2) }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="4" class="text-center text-muted">No data</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Fiber Stock Summary -->
        <div class="col-md-6">
            <div class="card card-outline card-info shadow-sm h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="fas fa-project-diagram mr-2"></i>Fiber Stock Summary</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="thead-dark">
                                <tr>
                                    <th>Fiber Code</th>
                                    <th>Brand</th>
                                    <th class="text-center">Core</th>
                                    <th class="text-center">Total (m)</th>
                                    <th class="text-center">Used (m)</th>
                                    <th class="text-center">Remaining (m)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($fibers as $f)
                                <tr>
                                    <td><strong>{{ $f->fiber_code }}</strong></td>
                                    <td>{{ $f->brand ?? '—' }}</td>
                                    <td class="text-center"><span class="badge badge-secondary">{{ $f->core ?? 'N/A' }} Core</span></td>
                                    <td class="text-center"><span class="badge badge-info">{{ number_format($f->stock_in, 2) }}</span></td>
                                    <td class="text-center"><span class="badge badge-warning">{{ number_format($f->stock_out, 2) }}</span></td>
                                    <td class="text-center">
                                        <span class="badge {{ $f->remaining > 0 ? 'badge-success' : 'badge-dark' }}">
                                            {{ number_format($f->remaining, 2) }}
                                        </span>
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="6" class="text-center text-muted py-3">No fiber data found.</td></tr>
                                @endforelse
                            </tbody>
                            <tfoot>
                                <tr class="table-secondary font-weight-bold">
                                    <td colspan="3" class="text-right pr-3">Total:</td>
                                    <td class="text-center">{{ number_format($fibers->sum('stock_in'), 2) }} m</td>
                                    <td class="text-center">{{ number_format($fibers->sum('stock_out'), 2) }} m</td>
                                    <td class="text-center">{{ number_format($fibers->sum('remaining'), 2) }} m</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Low Stock Alert -->
    @if($lowStockProducts->count() > 0)
    <div class="card card-outline card-danger shadow-sm mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fas fa-exclamation-triangle mr-2 text-danger"></i>Low Stock Alert
                <span class="badge badge-danger ml-2">{{ $lowStockProducts->count() }} items</span>
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead class="thead-dark">
                        <tr>
                            <th>#</th>
                            <th>Product Name</th>
                            <th>Code</th>
                            <th class="text-center">Current Stock</th>
                            <th class="text-center">Alert Level</th>
                            <th class="text-center">Shortage</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($lowStockProducts as $i => $p)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td><strong>{{ $p->name }}</strong></td>
                            <td><small class="text-muted">{{ $p->code ?? '—' }}</small></td>
                            <td class="text-center"><span class="badge badge-warning">{{ $p->stock_quantity }}</span></td>
                            <td class="text-center"><span class="badge badge-secondary">{{ $p->alert_quantity }}</span></td>
                            <td class="text-center"><span class="badge badge-danger">{{ $p->stock_quantity - $p->alert_quantity }}</span></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

</div>
@endsection
