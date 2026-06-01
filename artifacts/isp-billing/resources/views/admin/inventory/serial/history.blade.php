@extends('layouts.admin')

@section('title', 'Serial History - ' . ($product->name ?? 'Product'))
@section('breadcrumb')
<li class="breadcrumb-item"><a href="#">Inventory</a></li>
<li class="breadcrumb-item"><a href="{{ route('admin.inventory.stock.availability') }}">Stock Availability</a></li>
<li class="breadcrumb-item active">Serial History</li>
@endsection

@section('content')
<div class="container-fluid">

    <div class="row">
        <div class="col-12">
            <div class="card card-outline card-primary shadow-sm mb-4">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h4 class="mb-1 font-weight-bold">
                                <i class="fas fa-microchip mr-2 text-primary"></i>{{ $product->name }}
                            </h4>
                            <p class="mb-0 text-muted">
                                Code: <code class="text-dark">{{ $product->code ?? 'N/A' }}</code>
                                <span class="mx-2">|</span>
                                Unit: <strong>{{ $product->unit ?? 'PCS' }}</strong>
                                <span class="mx-2">|</span>
                                Category: <span class="badge badge-info">{{ $product->category->name ?? 'N/A' }}</span>
                            </p>
                        </div>
                        <div class="col-md-6 text-md-right mt-3 mt-md-0">
                            <a href="{{ route('admin.inventory.stock.availability') }}" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-arrow-left mr-1"></i>Back to Stock
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-2 col-md-4 col-sm-6 col-12">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ $serialSummary['total'] ?? 0 }}</h3>
                    <p>Total Serials</p>
                </div>
                <div class="icon"><i class="fas fa-barcode"></i></div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6 col-12">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ $serialSummary['in_stock'] ?? 0 }}</h3>
                    <p>In Stock</p>
                </div>
                <div class="icon"><i class="fas fa-box"></i></div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6 col-12">
            <div class="small-box bg-primary">
                <div class="inner">
                    <h3>{{ $serialSummary['assigned'] ?? 0 }}</h3>
                    <p>Assigned</p>
                </div>
                <div class="icon"><i class="fas fa-user-check"></i></div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6 col-12">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>{{ $serialSummary['damaged'] ?? 0 }}</h3>
                    <p>Damaged</p>
                </div>
                <div class="icon"><i class="fas fa-hammer"></i></div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6 col-12">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ $serialSummary['returned'] ?? 0 }}</h3>
                    <p>Returned</p>
                </div>
                <div class="icon"><i class="fas fa-rotate-left"></i></div>
            </div>
        </div>
    </div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6 col-12">
            <div class="small-box bg-success">
                <div class="inner"><h3>{{ $serialSummary['in_stock'] ?? 0 }}</h3><p>In Stock</p></div>
                <div class="icon"><i class="fas fa-box"></i></div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6 col-12">
            <div class="small-box bg-primary">
                <div class="inner"><h3>{{ $serialSummary['assigned'] ?? 0 }}</h3><p>Assigned</p></div>
                <div class="icon"><i class="fas fa-user-check"></i></div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6 col-12">
            <div class="small-box bg-danger">
                <div class="inner"><h3>{{ $serialSummary['damaged'] ?? 0 }}</h3><p>Damaged</p></div>
                <div class="icon"><i class="fas fa-hammer"></i></div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6 col-12">
            <div class="small-box bg-warning">
                <div class="inner"><h3>{{ $serialSummary['returned'] ?? 0 }}</h3><p>Returned</p></div>
                <div class="icon"><i class="fas fa-rotate-left"></i></div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card card-outline card-info shadow-sm">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-list-ol mr-2"></i>Serial Numbers & Status</h3>
                    <div class="card-tools">
                        <span class="badge badge-info">{{ $serials->count() }} serials</span>
                        <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
                    </div>
                </div>
                <div class="card-body table-responsive p-0">
                    <table id="serialsTable" class="table table-hover table-striped table-sm mb-0">
                        <thead class="bg-info text-white">
                            <tr>
                                <th>#</th>
                                <th>Serial Number</th>
                                <th class="text-center">Status</th>
                                <th>Assigned To</th>
                                <th>Warranty Start</th>
                                <th>Warranty End</th>
                                <th class="text-center">Warranty Status</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($serials as $key => $serial)
                            @php
                                $warrantyActive = false;
                                $warrantyExpiring = false;
                                if($serial->warranty_end) {
                                    $warrantyEnd = \Carbon\Carbon::parse($serial->warranty_end);
                                    $warrantyActive = $warrantyEnd->isFuture();
                                    $warrantyExpiring = $warrantyActive && $warrantyEnd->diffInDays(now()) <= 30;
                                }
                            @endphp
                            <tr>
                                <td>{{ $key + 1 }}</td>
                                <td>
                                    <code class="font-weight-bold">{{ $serial->serial_number }}</code>
                                    @if($serial->mac_address)
                                    <br><small class="text-muted">MAC: {{ $serial->mac_address }}</small>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @switch($serial->status)
                                        @case('stock')
                                            <span class="badge badge-success"><i class="fas fa-box mr-1"></i>In Stock</span>
                                            @break
                                        @case('assigned')
                                            <span class="badge badge-primary"><i class="fas fa-user-check mr-1"></i>Assigned</span>
                                            @break
                                        @case('damaged')
                                            <span class="badge badge-danger"><i class="fas fa-times-circle mr-1"></i>Damaged</span>
                                            @break
                                        @case('returned')
                                            <span class="badge badge-warning"><i class="fas fa-rotate-left mr-1"></i>Returned</span>
                                            @break
                                        @default
                                            <span class="badge badge-secondary">{{ ucfirst($serial->status) }}</span>
                                    @endswitch
                                </td>
                                <td>
                                    @if($serial->client)
                                        <i class="fas fa-user text-primary mr-1"></i>
                                        {{ $serial->client->name ?? 'N/A' }}
                                        @if($serial->client->client_id ?? null)
                                        <br><small class="text-muted">ID: {{ $serial->client->client_id }}</small>
                                        @endif
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if($serial->warranty_start)
                                        <small>{{ \Carbon\Carbon::parse($serial->warranty_start)->format('d-m-Y') }}</small>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if($serial->warranty_end)
                                        <small>{{ \Carbon\Carbon::parse($serial->warranty_end)->format('d-m-Y') }}</small>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($serial->warranty_end)
                                        @if($warrantyExpiring)
                                            <span class="badge badge-warning"><i class="fas fa-clock mr-1"></i>Expiring Soon</span>
                                        @elseif($warrantyActive)
                                            <span class="badge badge-success"><i class="fas fa-shield-halved mr-1"></i>Active</span>
                                        @else
                                            <span class="badge badge-secondary"><i class="fas fa-shield-xmark mr-1"></i>Expired</span>
                                        @endif
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td><small>{{ $serial->notes ?? '-' }}</small></td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">
                                    <i class="fas fa-inbox fa-2x mb-2 d-block"></i>No serial numbers found for this product
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @if(isset($movements) && $movements->count() > 0)
    <div class="row">
        <div class="col-12">
            <div class="card card-outline card-dark shadow-sm">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-history mr-2"></i>Movement History</h3>
                    <div class="card-tools">
                        <span class="badge badge-dark">{{ $movements->total() }} records</span>
                        <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
                    </div>
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-hover table-striped table-sm mb-0">
                        <thead class="thead-dark">
                            <tr>
                                <th>Date</th>
                                <th class="text-center">Type</th>
                                <th class="text-center">Quantity</th>
                                <th>Serial Number</th>
                                <th>Description</th>
                                <th>Reference</th>
                                <th>By</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($movements as $movement)
                            <tr>
                                <td>
                                    <small>{{ \Carbon\Carbon::parse($movement->created_at)->format('d-m-Y') }}</small>
                                    <br><small class="text-muted">{{ \Carbon\Carbon::parse($movement->created_at)->format('h:i A') }}</small>
                                </td>
                                <td class="text-center">
                                    @if($movement->type == 'in')
                                        <span class="badge badge-success"><i class="fas fa-arrow-down mr-1"></i>IN</span>
                                    @else
                                        <span class="badge badge-danger"><i class="fas fa-arrow-up mr-1"></i>OUT</span>
                                    @endif
                                </td>
                                <td class="text-center font-weight-bold">{{ $movement->quantity }}</td>
                                <td><code>{{ $movement->serial_number ?? '-' }}</code></td>
                                <td><small>{{ $movement->description ?? '-' }}</small></td>
                                <td><small>{{ $movement->reference ?? '-' }}</small></td>
                                <td><small>{{ $movement->created_by_name ?? 'System' }}</small></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($movements->hasPages())
                <div class="card-footer clearfix">
                    {{ $movements->links('vendor.pagination.bootstrap-4') }}
                </div>
                @endif
            </div>
        </div>
    </div>
    @endif

</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    if ($('#serialsTable tbody tr td[colspan]').length === 0) { $('#serialsTable').DataTable({
        "paging": true,
        "lengthChange": true,
        "searching": true,
        "ordering": true,
        "info": true,
        "autoWidth": false,
        "responsive": true,
        "pageLength": 25,
        "order": [[2, "asc"]],
        "language": {
            "search": "<i class='fas fa-search'></i> ",
            "emptyTable": "No serial numbers found"
        }
    }); }
});
</script>
@endpush
