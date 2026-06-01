@extends('layouts.admin')

@section('title', 'Inventory Dashboard')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="#">Inventory</a></li>
<li class="breadcrumb-item active">Stock Summary</li>
@endsection

@section('content')
<div class="container-fluid">

    <div class="row">
    <div class="col-lg-3 col-md-4 col-sm-6 col-12">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>{{ $dashboard['total_products'] ?? 0 }}</h3>
                <p>Total Products</p>
            </div>
            <div class="icon"><i class="fas fa-boxes"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-md-4 col-sm-6 col-12">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>{{ number_format($dashboard['total_stock_value'] ?? 0, 2) }} <small>BDT</small></h3>
                <p>Total Stock Value</p>
            </div>
            <div class="icon"><i class="fas fa-coins"></i></div>
        </div>
    </div>
    <div class="col-lg-2 col-md-4 col-sm-6 col-12">
        <div class="small-box {{ ($dashboard['low_stock_count'] ?? 0) > 0 ? 'bg-danger' : 'bg-secondary' }}">
            <div class="inner">
                <h3>{{ $dashboard['low_stock_count'] ?? 0 }}</h3>
                <p>Low Stock Alerts</p>
            </div>
            <div class="icon"><i class="fas fa-exclamation-triangle"></i></div>
        </div>
    </div>
    <div class="col-lg-2 col-md-4 col-sm-6 col-12">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3>{{ $dashboard['total_damaged'] ?? $dashboard['damaged_count'] ?? 0 }}</h3>
                <p>Damaged Items</p>
            </div>
            <div class="icon"><i class="fas fa-hammer"></i></div>
        </div>
    </div>
    <div class="col-lg-2 col-md-4 col-sm-6 col-12">
        <div class="small-box bg-purple" style="background: linear-gradient(135deg, #7B1FA2 0%, #9C27B0 100%); color: #fff;">
            <div class="inner">
                <h3>{{ $dashboard['warranty_expiring_soon'] ?? 0 }}</h3>
                <p>Warranty Expiring</p>
            </div>
            <div class="icon"><i class="fas fa-shield-alt"></i></div>
        </div>
    </div>
</div>
@include('admin.inventory.partials.nav-buttons')

    <div class="row">
        <div class="col-lg-6">
            <div class="card card-outline card-success shadow-sm">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-boxes mr-2"></i>Product Stock <small class="text-muted">(PCS Based)</small></h3>
                    <div class="card-tools d-flex align-items-center" style="gap:4px;">
                        <a href="{{ route('admin.inventory.instruments.index') }}" class="btn btn-xs btn-outline-primary" style="font-size:10px;padding:2px 7px;"><i class="fas fa-plus mr-1"></i>Add Product</a>
                        <a href="{{ route('admin.inventory.products.in_history') }}" class="btn btn-xs btn-outline-success" style="font-size:10px;padding:2px 7px;"><i class="fas fa-arrow-down mr-1"></i>Product In</a>
                        <a href="{{ route('admin.inventory.products.out') }}" class="btn btn-xs btn-outline-danger" style="font-size:10px;padding:2px 7px;"><i class="fas fa-arrow-up mr-1"></i>Product Out</a>
                        <a href="{{ route('admin.inventory.products.history') }}" class="btn btn-xs btn-outline-warning" style="font-size:10px;padding:2px 7px;"><i class="fas fa-history mr-1"></i>History</a>
                        <button type="button" class="btn btn-tool ml-1" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
                    </div>
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-hover table-sm mb-0">
                        <thead class="bg-success text-white">
                            <tr>
                                <th style="width:40px">#</th>
                                <th>Category</th>
                                <th>Type</th>
                                <th class="text-center">Total</th>
                                <th class="text-center">Available</th>
                                <th class="text-center">In Use</th>
                                <th class="text-center">Damaged</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($instrumentGroups as $key => $group)
                            <tr>
                                <td>{{ $key + 1 }}</td>
                                <td><strong>{{ $group['category'] }}</strong></td>
                                <td>
                                    @if($group['sub_category'])
                                    <span class="badge badge-light border">{{ $group['sub_category'] }}</span>
                                    @else
                                    <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-center"><span class="badge badge-info">{{ $group['total'] }}</span></td>
                                <td class="text-center"><span class="badge badge-success">{{ $group['available'] }}</span></td>
                                <td class="text-center"><span class="badge badge-warning">{{ $group['in_use'] }}</span></td>
                                <td class="text-center">
                                    @if($group['damaged'] > 0)
                                    <span class="badge badge-danger">{{ $group['damaged'] }}</span>
                                    @else
                                    <span class="badge badge-light">0</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    <i class="fas fa-inbox fa-2x mb-2 d-block"></i>No product data found
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                        @if(count($instrumentGroups) > 0)
                        <tfoot class="bg-light">
                            <tr class="font-weight-bold">
                                <td colspan="3" class="text-right">Total:</td>
                                <td class="text-center"><span class="badge badge-info">{{ collect($instrumentGroups)->sum('total') }}</span></td>
                                <td class="text-center"><span class="badge badge-success">{{ collect($instrumentGroups)->sum('available') }}</span></td>
                                <td class="text-center"><span class="badge badge-warning">{{ collect($instrumentGroups)->sum('in_use') }}</span></td>
                                <td class="text-center"><span class="badge badge-danger">{{ collect($instrumentGroups)->sum('damaged') }}</span></td>
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card card-outline card-success shadow-sm">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-network-wired mr-2"></i>Fiber Stock <small class="text-muted">(Meter Based)</small></h3>
                    <div class="card-tools d-flex align-items-center" style="gap:4px;">
                        <a href="{{ route('admin.inventory.fiber.index') }}" class="btn btn-xs btn-outline-info" style="font-size:10px;padding:2px 7px;"><i class="fas fa-plus mr-1"></i>Add Fiber</a>
                        <a href="{{ route('admin.inventory.fiber.in_history') }}" class="btn btn-xs btn-outline-success" style="font-size:10px;padding:2px 7px;"><i class="fas fa-arrow-down mr-1"></i>Fiber In</a>
                        <a href="{{ route('admin.inventory.fiber.out') }}" class="btn btn-xs btn-outline-danger" style="font-size:10px;padding:2px 7px;"><i class="fas fa-arrow-up mr-1"></i>Fiber Out</a>
                        <a href="{{ route('admin.inventory.fiber.history') }}" class="btn btn-xs btn-outline-warning" style="font-size:10px;padding:2px 7px;"><i class="fas fa-history mr-1"></i>History</a>
                        <button type="button" class="btn btn-tool ml-1" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
                    </div>
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-hover table-sm mb-0">
                        <thead class="bg-success text-white">
                            <tr>
                                <th style="width:40px">#</th>
                                <th>Fiber Code</th>
                                <th>Brand</th>
                                <th class="text-center">Core</th>
                                <th class="text-center">Total (M)</th>
                                <th class="text-center">Used (M)</th>
                                <th class="text-center">Remaining (M)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($fibers as $key => $item)
                            <tr>
                                <td>{{ $key + 1 }}</td>
                                <td><strong>{{ $item->product_code }}</strong></td>
                                <td>{{ $item->brand_name ?? '-' }}</td>
                                <td class="text-center"><span class="badge badge-secondary">{{ $item->core ?? 'N/A' }}</span></td>
                                <td class="text-center"><span class="badge badge-info">{{ number_format($item->stock_in, 2) }}</span></td>
                                <td class="text-center"><span class="badge badge-warning">{{ number_format($item->stock_out, 2) }}</span></td>
                                <td class="text-center">
                                    <span class="badge {{ $item->remaining > 0 ? 'badge-success' : 'badge-dark' }}">
                                        {{ number_format($item->remaining, 2) }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    <i class="fas fa-inbox fa-2x mb-2 d-block"></i>No fiber data found
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                        @if($fibers->count() > 0)
                        <tfoot class="bg-light">
                            <tr class="font-weight-bold">
                                <td colspan="4" class="text-right">Total:</td>
                                <td class="text-center"><span class="badge badge-info">{{ number_format($fibers->sum('stock_in'), 2) }}</span></td>
                                <td class="text-center"><span class="badge badge-warning">{{ number_format($fibers->sum('stock_out'), 2) }}</span></td>
                                <td class="text-center"><span class="badge badge-success">{{ number_format($fibers->sum('remaining'), 2) }}</span></td>
                            </tr>
                            @foreach($fiberCoreGroups as $cg)
                            <tr>
                                <td colspan="4" class="text-right"><span class="badge badge-secondary">{{ $cg->core ?: 'N/A' }} Core</span></td>
                                <td class="text-center"><span class="badge badge-info">{{ number_format($cg->total, 2) }}</span></td>
                                <td class="text-center"><span class="badge badge-warning">{{ number_format($cg->used, 2) }}</span></td>
                                <td class="text-center"><span class="badge badge-success">{{ number_format($cg->remaining, 2) }}</span></td>
                            </tr>
                            @endforeach
                        </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>
    </div>

    @if(isset($lowStockProducts) && $lowStockProducts->count() > 0)
    <div class="row">
        <div class="col-12">
            <div class="card card-outline card-danger shadow-sm">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-exclamation-circle mr-2 text-danger"></i>Low Stock Products</h3>
                    <div class="card-tools">
                        <span class="badge badge-danger">{{ $lowStockProducts->count() }} items</span>
                        <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
                    </div>
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-hover table-sm mb-0">
                        <thead class="bg-danger text-white">
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
                            @foreach($lowStockProducts as $key => $product)
                            <tr>
                                <td>{{ $key + 1 }}</td>
                                <td><strong>{{ $product->name }}</strong></td>
                                <td><code>{{ $product->code }}</code></td>
                                <td class="text-center">
                                    <span class="badge badge-warning">{{ $product->stock_quantity }}</span>
                                </td>
                                <td class="text-center">{{ $product->alert_quantity }}</td>
                                <td class="text-center">
                                    <span class="text-danger font-weight-bold">
                                        -{{ $product->alert_quantity - $product->stock_quantity }}
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @endif

    @if(isset($dashboard['recent_movements']) && count($dashboard['recent_movements']) > 0)
    <div class="row">
        <div class="col-12">
            <div class="card card-outline card-dark shadow-sm">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-clock-rotate-left mr-2"></i>Recent Activities</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
                    </div>
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-hover table-striped table-sm mb-0">
                        <thead class="thead-dark">
                            <tr>
                                <th>Date</th>
                                <th>Item</th>
                                <th class="text-center">Type</th>
                                <th class="text-center">Qty / Meter</th>
                                <th>Details</th>
                                <th>Client</th>
                                <th>By (Account)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($dashboard['recent_movements'] as $movement)
                            <tr>
                                <td>
                                    <small>{{ \Carbon\Carbon::parse($movement->created_at)->format('d-m-Y') }}</small>
                                    <br><small class="text-muted">{{ \Carbon\Carbon::parse($movement->created_at)->format('h:i A') }}</small>
                                </td>
                                <td>{{ $movement->product_name ?? 'N/A' }}</td>
                                <td class="text-center">
                                    @if($movement->type == 'product_in' && \Illuminate\Support\Str::contains($movement->description ?? '', 'Returned from client'))
                                        <span class="badge" style="background:#fd7e14;color:#fff;"><i class="fas fa-undo-alt mr-1"></i>PRODUCT RETURN</span>
                                    @elseif($movement->type == 'product_in')
                                        <span class="badge badge-purple" style="background:#6f42c1;color:#fff;"><i class="fas fa-box-open mr-1"></i>PRODUCT IN</span>
                                    @elseif($movement->type == 'stock_in')
                                        <span class="badge badge-success"><i class="fas fa-arrow-down mr-1"></i>FIBER STOCK IN</span>
                                    @elseif($movement->type == 'fiber_out')
                                        <span class="badge badge-danger"><i class="fas fa-arrow-up mr-1"></i>FIBER OUT</span>
                                    @elseif($movement->type == 'fiber_in' && \Illuminate\Support\Str::contains($movement->description ?? '', 'Returned by client'))
                                        <span class="badge" style="background:#20c997;color:#fff;"><i class="fas fa-undo-alt mr-1"></i>FIBER RETURN</span>
                                    @elseif($movement->type == 'fiber_in')
                                        <span class="badge badge-info"><i class="fas fa-undo-alt mr-1"></i>FIBER IN</span>
                                    @elseif($movement->type == 'usage')
                                        <span class="badge badge-warning"><i class="fas fa-tools mr-1"></i>USAGE</span>
                                    @else
                                        <span class="badge badge-secondary">{{ strtoupper($movement->type) }}</span>
                                    @endif
                                </td>
                                <td class="text-center font-weight-bold">
                                    {{ number_format($movement->quantity, 2) }}
                                    <small class="text-muted">{{ $movement->unit ?? 'm' }}</small>
                                </td>
                                <td>
                                    <small>{{ $movement->description ?? '-' }}</small>
                                    @if(!empty($movement->note) && $movement->note !== $movement->description)
                                        <br><small class="text-info"><i class="fas fa-tag mr-1"></i>{{ $movement->note }}</small>
                                    @endif
                                </td>
                                <td>
                                    @if(!empty($movement->client_info))
                                        <small class="text-primary"><i class="fas fa-user mr-1"></i>{{ $movement->client_info }}</small>
                                    @else
                                        <small class="text-muted">—</small>
                                    @endif
                                </td>
                                <td>
                                    @if(!empty($movement->created_by_name) && $movement->created_by_name !== 'System')
                                        <small class="font-weight-bold text-dark">{{ $movement->created_by_name }}</small>
                                    @else
                                        <small class="badge badge-secondary" style="font-size:10px;">System</small>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @endif

</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('.table').each(function() {
        if ($(this).find('tbody tr').length > 10 && $(this).find('tbody tr td[colspan]').length === 0) {
            $(this).DataTable({
                "paging": true,
                "lengthChange": false,
                "searching": true,
                "ordering": true,
                "info": true,
                "autoWidth": false,
                "responsive": true,
                "pageLength": 15
            });
        }
    });
});
</script>
@endpush
