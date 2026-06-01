@extends('layouts.admin')

@section('title', 'Inventory Stock Report')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="#">Inventory</a></li>
<li class="breadcrumb-item active">Stock Report</li>
@endsection

@section('content')
<div class="container-fluid">

    @php
        $ir           = collect($inventoryReport ?? []);
        $ir_total     = $ir->sum('total');
        $ir_available = $ir->sum('available_count');
        $ir_in_use    = $ir->sum('in_use_count');
        $ir_damaged   = $ir->sum('damaged_count');
        $ir_cats      = $ir->groupBy('category');
    @endphp

    {{-- Summary Cards --}}
    <div class="row mb-3">
        <div class="col-6 col-md-3 mb-2">
            <div class="small-box bg-primary mb-0">
                <div class="inner"><h3>{{ number_format($ir_total) }}</h3><p>Total Items</p></div>
                <div class="icon"><i class="fas fa-boxes"></i></div>
            </div>
        </div>
        <div class="col-6 col-md-3 mb-2">
            <div class="small-box bg-success mb-0">
                <div class="inner"><h3>{{ number_format($ir_available) }}</h3><p>Available</p></div>
                <div class="icon"><i class="fas fa-check-circle"></i></div>
            </div>
        </div>
        <div class="col-6 col-md-3 mb-2">
            <div class="small-box bg-info mb-0">
                <div class="inner"><h3>{{ number_format($ir_in_use) }}</h3><p>In Use (Assigned)</p></div>
                <div class="icon"><i class="fas fa-users"></i></div>
            </div>
        </div>
        <div class="col-6 col-md-3 mb-2">
            <div class="small-box bg-danger mb-0">
                <div class="inner"><h3>{{ number_format($ir_damaged) }}</h3><p>Damaged</p></div>
                <div class="icon"><i class="fas fa-exclamation-triangle"></i></div>
            </div>
        </div>
    </div>

    {{-- Inventory Stock Table --}}
    <div class="card card-outline card-success shadow-sm">
        <div class="card-header d-flex align-items-center justify-content-between py-2">
            <h3 class="card-title mb-0"><i class="fas fa-warehouse mr-2"></i>Inventory - Stock Status</h3>
            <div class="card-tools d-flex align-items-center">
                <a href="{{ route('admin.inventory.products.index') }}" class="btn btn-success btn-sm mr-1">
                    <i class="fas fa-boxes mr-1"></i>Products Stocks
                </a>
                <a href="{{ route('admin.inventory.stock.availability') }}" class="btn btn-info btn-sm mr-2">
                    <i class="fas fa-warehouse mr-1"></i>Availability
                </a>
                <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
            </div>
        </div>
        <div class="card-body table-responsive p-0">
            <table id="inventoryStockTable" class="table table-hover table-striped table-sm mb-0" style="font-size:13px;">
                <thead class="bg-success text-white">
                    <tr>
                        <th>#</th>
                        <th>Category</th>
                        <th>Sub-Category</th>
                        <th class="text-center">Total Items</th>
                        <th class="text-center">Available</th>
                        <th class="text-center">In Use</th>
                        <th class="text-center">Damaged</th>
                        <th class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($ir as $idx => $row)
                    @php
                        $avail   = $row->available_count ?? 0;
                        $inUse   = $row->in_use_count ?? 0;
                        $damaged = $row->damaged_count ?? 0;
                        $total   = $row->total ?? 0;
                    @endphp
                    <tr class="{{ $avail <= 0 && $total > 0 ? 'table-warning' : '' }}">
                        <td>{{ $idx + 1 }}</td>
                        <td><strong>{{ $row->category ?? '-' }}</strong></td>
                        <td class="text-muted">{{ $row->sub_category ?: '-' }}</td>
                        <td class="text-center font-weight-bold">{{ $total }}</td>
                        <td class="text-center"><span class="badge badge-success px-2">{{ $avail }}</span></td>
                        <td class="text-center"><span class="badge badge-info px-2">{{ $inUse }}</span></td>
                        <td class="text-center">
                            @if($damaged > 0)
                                <span class="badge badge-danger px-2">{{ $damaged }}</span>
                            @else
                                <span class="badge badge-secondary px-2">0</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($avail <= 0 && $total > 0)
                                <span class="badge badge-warning"><i class="fas fa-exclamation mr-1"></i>None Available</span>
                            @elseif($avail > 0)
                                <span class="badge badge-success"><i class="fas fa-check mr-1"></i>Available</span>
                            @else
                                <span class="badge badge-secondary">No Stock</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">
                            <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                            No inventory data found. Add instruments via
                            <a href="{{ route('admin.inventory.products.index') }}">Products Stocks</a>.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                @if($ir->count() > 0)
                <tfoot class="bg-light font-weight-bold">
                    <tr>
                        <td colspan="3" class="text-right">Totals:</td>
                        <td class="text-center">{{ $ir_total }}</td>
                        <td class="text-center text-success">{{ $ir_available }}</td>
                        <td class="text-center text-info">{{ $ir_in_use }}</td>
                        <td class="text-center text-danger">{{ $ir_damaged }}</td>
                        <td></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>

    {{-- Category-wise Breakdown --}}
    @if($ir->count() > 0)
    <div class="card card-outline card-secondary shadow-sm">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-layer-group mr-2"></i>Category-wise Breakdown</h3>
            <div class="card-tools">
                <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
            </div>
        </div>
        <div class="card-body pt-2">
            <div id="invCategoryAccordion">
                @foreach($ir_cats as $catName => $catRows)
                @php
                    $catTotal   = $catRows->sum('total');
                    $catAvail   = $catRows->sum('available_count');
                    $catInUse   = $catRows->sum('in_use_count');
                    $catDamaged = $catRows->sum('damaged_count');
                @endphp
                <div class="card card-sm mb-2 border">
                    <div class="card-header p-2 bg-light">
                        <button class="btn btn-link btn-sm text-left w-100 d-flex justify-content-between align-items-center collapsed text-dark"
                                data-toggle="collapse" data-target="#inv-cat-{{ $loop->index }}">
                            <span>
                                <i class="fas fa-folder mr-2 text-success"></i>
                                <strong>{{ $catName }}</strong>
                                <span class="badge badge-success ml-2">{{ $catTotal }} items</span>
                            </span>
                            <span class="text-muted small">
                                Available: <strong class="text-success">{{ $catAvail }}</strong> &nbsp;|&nbsp;
                                In Use: <strong class="text-info">{{ $catInUse }}</strong> &nbsp;|&nbsp;
                                Damaged: <strong class="text-danger">{{ $catDamaged }}</strong>
                            </span>
                        </button>
                    </div>
                    <div id="inv-cat-{{ $loop->index }}" class="collapse">
                        <div class="card-body p-0">
                            <table class="table table-sm table-hover mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Sub-Category</th>
                                        <th class="text-center">Total</th>
                                        <th class="text-center">Available</th>
                                        <th class="text-center">In Use</th>
                                        <th class="text-center">Damaged</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($catRows as $catRow)
                                    <tr>
                                        <td>{{ $catRow->sub_category ?: '—' }}</td>
                                        <td class="text-center font-weight-bold">{{ $catRow->total }}</td>
                                        <td class="text-center"><span class="badge badge-success">{{ $catRow->available_count }}</span></td>
                                        <td class="text-center"><span class="badge badge-info">{{ $catRow->in_use_count }}</span></td>
                                        <td class="text-center">
                                            @if($catRow->damaged_count > 0)
                                                <span class="badge badge-danger">{{ $catRow->damaged_count }}</span>
                                            @else
                                                <span class="badge badge-secondary">0</span>
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
    @endif

</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    if ($('#inventoryStockTable tbody tr td[colspan]').length === 0) {
        $('#inventoryStockTable').DataTable({
            "paging": true, "lengthChange": true, "searching": true,
            "ordering": true, "info": true, "autoWidth": false,
            "responsive": true, "pageLength": 25, "order": [[4, "desc"]],
            "dom": '<"row"<"col-sm-6"l><"col-sm-6"f>>rtip',
            "language": { "search": "<i class='fas fa-search'></i> " }
        });
    }
});
</script>
@endpush
