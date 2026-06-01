@extends('layouts.admin')

@section('title', 'Stock Availability')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="#">Inventory</a></li>
<li class="breadcrumb-item active">Stock Availability</li>
@endsection

@section('content')
<div class="container-fluid">

    <div class="row">
        <div class="col-lg col-md-4 col-sm-6 col-12">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ $overallStats['total_serials'] ?? 0 }}</h3>
                    <p>Total Items</p>
                </div>
                <div class="icon"><i class="fas fa-boxes-stacked"></i></div>
            </div>
        </div>
        <div class="col-lg col-md-4 col-sm-6 col-12">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ $overallStats['total_in_stock'] ?? 0 }}</h3>
                    <p>Available</p>
                </div>
                <div class="icon"><i class="fas fa-check-circle"></i></div>
            </div>
        </div>
        <div class="col-lg col-md-4 col-sm-6 col-12">
            <div class="small-box bg-primary">
                <div class="inner">
                    <h3>{{ $overallStats['total_assigned'] ?? 0 }}</h3>
                    <p>In Use</p>
                </div>
                <div class="icon"><i class="fas fa-share-from-square"></i></div>
            </div>
        </div>
        <div class="col-lg col-md-4 col-sm-6 col-12">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>{{ $overallStats['total_damaged'] ?? 0 }}</h3>
                    <p>Damaged</p>
                </div>
                <div class="icon"><i class="fas fa-hammer"></i></div>
            </div>
        </div>
        <div class="col-lg col-md-4 col-sm-6 col-12">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ $overallStats['total_returned'] ?? 0 }}</h3>
                    <p>Returned</p>
                </div>
                <div class="icon"><i class="fas fa-rotate-left"></i></div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card card-outline card-primary shadow-sm">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-th-list mr-2"></i>Instrument Stock Overview</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
                    </div>
                </div>
                <div class="card-body table-responsive p-0">
                    <table id="availabilityTable" class="table table-hover table-sm mb-0">
                        <thead class="bg-primary text-white">
                            <tr>
                                <th style="width:40px">#</th>
                                <th>Category</th>
                                <th>Type</th>
                                <th>Brand</th>
                                <th class="text-center">Available</th>
                                <th class="text-center">In Use</th>
                                <th class="text-center">Damaged</th>
                                <th class="text-center">Returned</th>
                                <th class="text-center">Total</th>
                                <th style="min-width: 140px;">Utilization</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($instruments as $key => $item)
                            @php
                                $total = $item->total_count ?? 0;
                                $inStock = $item->stock_count ?? 0;
                                $assigned = $item->assigned_count ?? 0;
                                $damaged = $item->damaged_count ?? 0;
                                $returned = $item->returned_count ?? 0;
                                $utilization = $total > 0 ? round(($assigned / $total) * 100) : 0;
                            @endphp
                            <tr>
                                <td>{{ $key + 1 }}</td>
                                <td><strong>{{ $item->category ?? $item->name }}</strong></td>
                                <td>
                                    @if($item->sub_category)
                                    <span class="badge badge-light border">{{ $item->sub_category }}</span>
                                    @else
                                    <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>{{ $item->brand ?? '-' }}</td>
                                <td class="text-center"><span class="badge badge-success">{{ $inStock }}</span></td>
                                <td class="text-center"><span class="badge badge-primary">{{ $assigned }}</span></td>
                                <td class="text-center"><span class="badge {{ $damaged > 0 ? 'badge-danger' : 'badge-light border' }}">{{ $damaged }}</span></td>
                                <td class="text-center"><span class="badge {{ $returned > 0 ? 'badge-warning' : 'badge-light border' }}">{{ $returned }}</span></td>
                                <td class="text-center"><strong>{{ $total }}</strong></td>
                                <td>
                                    <div class="progress progress-sm mt-1" style="height: 14px;" title="{{ $utilization }}% in use">
                                        @if($total > 0)
                                        <div class="progress-bar bg-success" style="width: {{ round(($inStock / $total) * 100) }}%" title="Available: {{ $inStock }}"></div>
                                        <div class="progress-bar bg-primary" style="width: {{ round(($assigned / $total) * 100) }}%" title="In Use: {{ $assigned }}"></div>
                                        <div class="progress-bar bg-danger" style="width: {{ round(($damaged / $total) * 100) }}%" title="Damaged: {{ $damaged }}"></div>
                                        <div class="progress-bar bg-warning" style="width: {{ round(($returned / $total) * 100) }}%" title="Returned: {{ $returned }}"></div>
                                        @endif
                                    </div>
                                    <small class="text-muted">{{ $utilization }}% in use</small>
                                </td>
                                <td class="text-center">
                                    @if($inStock <= 0 && $total > 0)
                                        <span class="badge badge-danger">Depleted</span>
                                    @elseif($inStock > 0 && $inStock <= 2)
                                        <span class="badge badge-warning">Low</span>
                                    @elseif($total == 0)
                                        <span class="badge badge-secondary">Empty</span>
                                    @else
                                        <span class="badge badge-success">Available</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="11" class="text-center text-muted py-4">
                                    <i class="fas fa-inbox fa-2x mb-2 d-block"></i>No instruments found
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                        @if($instruments->count() > 0)
                        <tfoot class="bg-light">
                            <tr class="font-weight-bold">
                                <td colspan="4" class="text-right">Total:</td>
                                <td class="text-center"><span class="badge badge-success">{{ $instruments->sum('stock_count') }}</span></td>
                                <td class="text-center"><span class="badge badge-primary">{{ $instruments->sum('assigned_count') }}</span></td>
                                <td class="text-center"><span class="badge badge-danger">{{ $instruments->sum('damaged_count') }}</span></td>
                                <td class="text-center"><span class="badge badge-warning">{{ $instruments->sum('returned_count') }}</span></td>
                                <td class="text-center"><strong>{{ $instruments->sum('total_count') }}</strong></td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                </div>
                <div class="card-footer text-muted">
                    <div class="d-flex flex-wrap align-items-center">
                        <small class="mr-3"><span class="badge badge-success">&nbsp;</span> Available</small>
                        <small class="mr-3"><span class="badge badge-primary">&nbsp;</span> In Use</small>
                        <small class="mr-3"><span class="badge badge-danger">&nbsp;</span> Damaged</small>
                        <small class="mr-3"><span class="badge badge-warning">&nbsp;</span> Returned</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if(isset($recentInstruments) && $recentInstruments->count() > 0)
    <div class="row">
        <div class="col-12">
            <div class="card card-outline card-dark shadow-sm">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-clock-rotate-left mr-2"></i>Recent Updates</h3>
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
                                <th>Category</th>
                                <th>Type</th>
                                <th>Brand</th>
                                <th>Serial</th>
                                <th class="text-center">Status</th>
                                <th>Assigned To</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentInstruments as $inst)
                            <tr>
                                <td>
                                    <small>{{ \Carbon\Carbon::parse($inst->updated_at)->format('d-m-Y') }}</small>
                                    <br><small class="text-muted">{{ \Carbon\Carbon::parse($inst->updated_at)->format('h:i A') }}</small>
                                </td>
                                <td><strong>{{ $inst->name }}</strong></td>
                                <td>{{ $inst->category }}</td>
                                <td>{{ $inst->sub_category ?? '-' }}</td>
                                <td>{{ $inst->brand ?? '-' }}</td>
                                <td><code>{{ $inst->serial_number ?? '-' }}</code></td>
                                <td class="text-center">
                                    @if($inst->status == 'available')
                                        <span class="badge badge-success">Available</span>
                                    @elseif($inst->status == 'in_use')
                                        <span class="badge badge-primary">In Use</span>
                                    @elseif($inst->status == 'damaged')
                                        <span class="badge badge-danger">Damaged</span>
                                    @elseif($inst->status == 'returned')
                                        <span class="badge badge-warning">Returned</span>
                                    @else
                                        <span class="badge badge-secondary">{{ ucfirst($inst->status) }}</span>
                                    @endif
                                </td>
                                <td>{{ $inst->assigned_to ?? '-' }}</td>
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
    if ($('#availabilityTable tbody tr td[colspan]').length === 0) {
        $('#availabilityTable').DataTable({
            "paging": true,
            "lengthChange": true,
            "searching": true,
            "ordering": true,
            "info": true,
            "autoWidth": false,
            "responsive": true,
            "pageLength": 25,
            "order": [[4, "desc"]],
            "language": {
                "search": "<i class='fas fa-search'></i> ",
                "emptyTable": "No instruments found"
            }
        });
    }

    $('[title]').tooltip();
});
</script>
@endpush
