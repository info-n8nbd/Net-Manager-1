@extends('layouts.admin')

@section('title', 'Stock Report')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="#">Inventory</a></li>
<li class="breadcrumb-item active">Stock Report</li>
@endsection

@section('content')
<div class="container-fluid">

    <div class="row">
        <div class="col-md-12 mb-3">
            <h4 class="font-weight-bold"><i class="fas fa-chart-bar mr-2"></i>Vendor-wise Stock Report</h4>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ isset($report) ? count($report) : 0 }}</h3>
                    <p>Total Products</p>
                </div>
                <div class="icon"><i class="fas fa-boxes-stacked"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>
                        @php
                            $totalInStock = 0;
                            if(isset($report)) {
                                foreach($report as $item) {
                                    $totalInStock += $item['current_stock'] ?? ($item->current_stock ?? 0);
                                }
                            }
                        @endphp
                        {{ number_format($totalInStock) }}
                    </h3>
                    <p>Total Current Stock</p>
                </div>
                <div class="icon"><i class="fas fa-cubes"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>
                        @php
                            $lowStockCount = 0;
                            if(isset($report)) {
                                foreach($report as $item) {
                                    $current = $item['current_stock'] ?? ($item->current_stock ?? 0);
                                    $min = $item['min_stock'] ?? ($item->min_stock ?? 0);
                                    if($current <= $min && $min > 0) $lowStockCount++;
                                }
                            }
                        @endphp
                        {{ $lowStockCount }}
                    </h3>
                    <p>Low Stock Items</p>
                </div>
                <div class="icon"><i class="fas fa-exclamation-triangle"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>
                        @php
                            $outOfStock = 0;
                            if(isset($report)) {
                                foreach($report as $item) {
                                    $current = $item['current_stock'] ?? ($item->current_stock ?? 0);
                                    if($current <= 0) $outOfStock++;
                                }
                            }
                        @endphp
                        {{ $outOfStock }}
                    </h3>
                    <p>Out of Stock</p>
                </div>
                <div class="icon"><i class="fas fa-ban"></i></div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card card-outline card-primary shadow-sm">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-table mr-2"></i>Stock Summary Report</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
                    </div>
                </div>
                <div class="card-body table-responsive p-0">
                    <table id="stockReportTable" class="table table-hover table-striped table-sm mb-0">
                        <thead class="bg-primary text-white">
                            <tr>
                                <th>#</th>
                                <th>Product Name</th>
                                <th>Code</th>
                                <th>Category</th>
                                <th class="text-center">Total Purchased</th>
                                <th class="text-center">Total Sold/Assigned</th>
                                <th class="text-center">Current Stock</th>
                                <th class="text-center">Min Stock</th>
                                <th class="text-center">Stock Value</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($report as $key => $item)
                            @php
                                $productName = $item['product_name'] ?? ($item->name ?? 'N/A');
                                $productCode = $item['code'] ?? ($item->code ?? '-');
                                $category = $item['category'] ?? ($item->category->name ?? 'N/A');
                                $totalIn = $item['total_in'] ?? 0;
                                $totalOut = $item['total_out'] ?? 0;
                                $currentStock = $item['current_stock'] ?? ($item->current_stock ?? 0);
                                $minStock = $item['min_stock'] ?? ($item->min_stock ?? 0);
                                $purchasePrice = $item['purchase_price'] ?? ($item->purchase_price ?? 0);
                                $stockValue = $currentStock * $purchasePrice;
                            @endphp
                            <tr class="{{ $currentStock <= 0 ? 'table-danger' : ($currentStock <= $minStock ? 'table-warning' : '') }}">
                                <td>{{ $key + 1 }}</td>
                                <td><strong>{{ $productName }}</strong></td>
                                <td><code>{{ $productCode }}</code></td>
                                <td><span class="badge badge-light border">{{ $category }}</span></td>
                                <td class="text-center">
                                    <span class="text-primary font-weight-bold">{{ $totalIn }}</span>
                                </td>
                                <td class="text-center">
                                    <span class="text-danger font-weight-bold">{{ $totalOut }}</span>
                                </td>
                                <td class="text-center">
                                    <span class="font-weight-bold {{ $currentStock <= 0 ? 'text-danger' : ($currentStock <= $minStock ? 'text-warning' : 'text-success') }}">
                                        {{ $currentStock }}
                                    </span>
                                </td>
                                <td class="text-center text-muted">{{ $minStock }}</td>
                                <td class="text-center font-weight-bold">{{ number_format($stockValue, 2) }}</td>
                                <td class="text-center">
                                    @if($currentStock <= 0)
                                        <span class="badge badge-danger"><i class="fas fa-times-circle mr-1"></i>Out of Stock</span>
                                    @elseif($currentStock <= $minStock)
                                        <span class="badge badge-warning"><i class="fas fa-exclamation-circle mr-1"></i>Low Stock</span>
                                    @else
                                        <span class="badge badge-success"><i class="fas fa-check-circle mr-1"></i>In Stock</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="10" class="text-center text-muted py-4">
                                    <i class="fas fa-inbox fa-2x mb-2 d-block"></i>No stock data available
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                        @if(isset($report) && count($report) > 0)
                        <tfoot class="bg-light font-weight-bold">
                            <tr>
                                <td colspan="4" class="text-right">Totals:</td>
                                <td class="text-center text-primary">
                                    @php $totalPurchased = 0; foreach($report as $item) { $totalPurchased += $item['total_in'] ?? 0; } @endphp
                                    {{ $totalPurchased }}
                                </td>
                                <td class="text-center text-danger">
                                    @php $totalSold = 0; foreach($report as $item) { $totalSold += $item['total_out'] ?? 0; } @endphp
                                    {{ $totalSold }}
                                </td>
                                <td class="text-center text-success">{{ number_format($totalInStock) }}</td>
                                <td></td>
                                <td class="text-center">
                                    @php
                                        $totalValue = 0;
                                        foreach($report as $item) {
                                            $cs = $item['current_stock'] ?? ($item->current_stock ?? 0);
                                            $pp = $item['purchase_price'] ?? ($item->purchase_price ?? 0);
                                            $totalValue += $cs * $pp;
                                        }
                                    @endphp
                                    {{ number_format($totalValue, 2) }}
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>
    </div>

    @if(isset($report) && count($report) > 0)
    <div class="row">
        <div class="col-12">
            <div class="card card-outline card-secondary shadow-sm">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-layer-group mr-2"></i>Category-wise Breakdown</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
                    </div>
                </div>
                <div class="card-body">
                    @php
                        $categories = [];
                        foreach($report as $item) {
                            $cat = $item['category'] ?? ($item->category->name ?? 'Uncategorized');
                            if(!isset($categories[$cat])) {
                                $categories[$cat] = ['items' => [], 'total_stock' => 0, 'total_value' => 0];
                            }
                            $cs = $item['current_stock'] ?? ($item->current_stock ?? 0);
                            $pp = $item['purchase_price'] ?? ($item->purchase_price ?? 0);
                            $categories[$cat]['items'][] = $item;
                            $categories[$cat]['total_stock'] += $cs;
                            $categories[$cat]['total_value'] += $cs * $pp;
                        }
                    @endphp

                    <div id="categoryAccordion">
                        @foreach($categories as $catName => $catData)
                        <div class="card card-sm mb-2">
                            <div class="card-header p-2" id="heading-{{ $loop->index }}">
                                <h5 class="mb-0">
                                    <button class="btn btn-link btn-sm text-left w-100 d-flex justify-content-between align-items-center collapsed"
                                            data-toggle="collapse"
                                            data-target="#collapse-{{ $loop->index }}"
                                            aria-expanded="false">
                                        <span>
                                            <i class="fas fa-folder mr-2 text-primary"></i>
                                            <strong>{{ $catName }}</strong>
                                            <span class="badge badge-primary ml-2">{{ count($catData['items']) }} items</span>
                                        </span>
                                        <span class="text-muted">
                                            Stock: <strong>{{ $catData['total_stock'] }}</strong> |
                                            Value: <strong>{{ number_format($catData['total_value'], 2) }}</strong>
                                        </span>
                                    </button>
                                </h5>
                            </div>
                            <div id="collapse-{{ $loop->index }}" class="collapse" data-parent="#categoryAccordion">
                                <div class="card-body p-0">
                                    <table class="table table-sm table-hover mb-0">
                                        <thead class="bg-light">
                                            <tr>
                                                <th>Product</th>
                                                <th class="text-center">Current Stock</th>
                                                <th class="text-center">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($catData['items'] as $catItem)
                                            @php
                                                $cn = $catItem['product_name'] ?? ($catItem->name ?? 'N/A');
                                                $cs2 = $catItem['current_stock'] ?? ($catItem->current_stock ?? 0);
                                                $ms2 = $catItem['min_stock'] ?? ($catItem->min_stock ?? 0);
                                            @endphp
                                            <tr>
                                                <td>{{ $cn }}</td>
                                                <td class="text-center">{{ $cs2 }}</td>
                                                <td class="text-center">
                                                    @if($cs2 <= 0)
                                                        <span class="badge badge-danger">Out of Stock</span>
                                                    @elseif($cs2 <= $ms2)
                                                        <span class="badge badge-warning">Low</span>
                                                    @else
                                                        <span class="badge badge-success">OK</span>
                                                    @endif
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
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
    if ($('#stockReportTable tbody tr td[colspan]').length === 0) { $('#stockReportTable').DataTable({
        "paging": true,
        "lengthChange": true,
        "searching": true,
        "ordering": true,
        "info": true,
        "autoWidth": false,
        "responsive": true,
        "pageLength": 25,
        "order": [[6, "asc"]],
        "dom": '<"row"<"col-sm-6"l><"col-sm-6"f>>rtip',
        "language": {
            "search": "<i class='fas fa-search'></i> ",
            "emptyTable": "No stock data available"
        }
    }); }
});
</script>
@endpush
