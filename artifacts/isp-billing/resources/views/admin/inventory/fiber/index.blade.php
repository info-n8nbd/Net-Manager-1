@extends('layouts.admin')

@section('title', 'Fiber Stock Management')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="#">Inventory</a></li>
<li class="breadcrumb-item active">Fiber Stock</li>
@endsection

@section('content')
<div class="row">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>{{ $stocks->count() }}</h3>
                <p>Total Drums</p>
            </div>
            <div class="icon"><i class="fas fa-box"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>{{ $stocks->where('status', 'active')->count() }}</h3>
                <p>Active Drums</p>
            </div>
            <div class="icon"><i class="fas fa-check-circle"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-danger">
            <div class="inner">
                <h3>{{ $stocks->where('status', 'finished')->count() }}</h3>
                <p>Finished Drums</p>
            </div>
            <div class="icon"><i class="fas fa-times-circle"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3>{{ number_format($stocks->sum('remaining_meter')) }}m</h3>
                <p>Total Remaining Meters</p>
            </div>
            <div class="icon"><i class="fas fa-ruler"></i></div>
        </div>
    </div>
</div>
@include('admin.inventory.partials.nav-buttons')

<div class="card card-primary card-outline">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-list mr-2"></i>Fiber Stock List</h3>
        <div class="card-tools">
            @if($__canAction('inventory', 'add_product'))
            <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addFiberModal">
                <i class="fas fa-plus mr-1"></i> Add Fiber
            </button>
            @endif
        </div>
    </div>
    <div class="card-body">
        <table id="fiberStockTable" class="table table-bordered table-striped table-hover">
            <thead>
                <tr>
                    <th>Fiber Code</th>
                    <th>Brand</th>
                    <th>Vendor</th>
                    <th>Fiber Type</th>
                    <th>Core</th>
                    <th>Start Meter</th>
                    <th>End Meter</th>
                    <th>Total Meter</th>
                    <th>Remaining Meter</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($stocks as $stock)
                <tr>
                    <td><strong>{{ $stock->fiber_code }}</strong></td>
                    <td>{{ $stock->brand_name ?? '-' }}</td>
                    <td>{{ $stock->vendor->name ?? 'N/A' }}</td>
                    <td>{{ $stock->fiber_type ?? '-' }}</td>
                    <td>{{ $stock->core }} Core</td>
                    <td>{{ number_format($stock->start_meter) }}</td>
                    <td>{{ number_format($stock->end_meter) }}</td>
                    <td>{{ number_format($stock->total_meter) }}m</td>
                    <td>
                        {{ number_format($stock->remaining_meter) }}m
                        @php
                            $percent = $stock->total_meter > 0 ? round(($stock->remaining_meter / $stock->total_meter) * 100) : 0;
                        @endphp
                        <div class="progress progress-xs mt-1">
                            <div class="progress-bar {{ $percent > 50 ? 'bg-success' : ($percent > 20 ? 'bg-warning' : 'bg-danger') }}" style="width: {{ $percent }}%"></div>
                        </div>
                        <small class="text-muted">{{ $percent }}% remaining</small>
                    </td>
                    <td>
                        @if($stock->status === 'active')
                            <span class="badge badge-success">Active</span>
                        @else
                            <span class="badge badge-danger">Finished</span>
                        @endif
                    </td>
                    <td class="text-nowrap">
                        <a href="{{ route('admin.inventory.fiber.show', $stock->id) }}" class="btn btn-info btn-xs" title="View">
                            <i class="fas fa-eye"></i>
                        </a>
                        @if($__canAction('inventory', 'edit_product'))
                        <a href="{{ route('admin.inventory.fiber.edit', $stock->id) }}" class="btn btn-warning btn-xs" title="Edit">
                            <i class="fas fa-edit"></i>
                        </a>
                        @endif
                        @if($__canAction('inventory', 'delete_product'))
                        <form action="{{ route('admin.inventory.fiber.destroy', $stock->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this fiber stock? This cannot be undone!')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-xs" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="11" class="text-center text-muted">No fiber stock records found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="addFiberModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <form method="POST" action="{{ route('admin.inventory.fiber.store') }}" id="addFiberForm">
            @csrf
            <div class="modal-content">
                <div class="modal-header bg-primary">
                    <h5 class="modal-title text-white"><i class="fas fa-plus-circle mr-1"></i> Add Fiber</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body" style="max-height:70vh; overflow-y:auto;">
                    <div class="row">
                        <div class="col-md-3 form-group">
                            <label><i class="fas fa-truck mr-1"></i> Vendor</label>
                            <select name="vendor_id" class="form-control form-control-sm" required>
                                <option value="">-- Select Vendor --</option>
                                @foreach($vendors as $vendor)
                                    <option value="{{ $vendor->id }}">{{ $vendor->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 form-group">
                            <label><i class="fas fa-user mr-1"></i> Purchaser</label>
                            <select name="purchaser" class="form-control form-control-sm">
                                <option value="">-- Select --</option>
                                @foreach($employees as $emp)
                                    <option value="{{ $emp->name }}">{{ $emp->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 form-group">
                            <label><i class="fas fa-file-invoice mr-1"></i> Invoice No</label>
                            <input type="text" name="invoice_number" class="form-control form-control-sm" placeholder="e.g. INV-2026-001">
                        </div>
                        <div class="col-md-3 form-group">
                            <label><i class="fas fa-calendar mr-1"></i> Stock Date</label>
                            <input type="date" name="stock_date" class="form-control form-control-sm" value="{{ date('Y-m-d') }}" required>
                        </div>
                    </div>

                    <div id="fiber-items-container">
                        <div class="fiber-item" data-index="0">
                            <div class="d-flex align-items-center mb-2" style="gap:8px;">
                                <span class="badge badge-primary fiber-item-number" style="font-size:0.85rem;">Fiber #1</span>
                                <hr class="flex-grow-1 my-0">
                                <button type="button" class="btn btn-danger btn-xs remove-fiber-btn" style="display:none;" title="Remove this fiber">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                            <div class="row">
                                <div class="col-md-3 form-group">
                                    <label><i class="fas fa-barcode mr-1"></i> Fiber Code</label>
                                    <input type="text" name="fibers[0][fiber_code]" class="form-control form-control-sm" placeholder="e.g. FBR-001" required>
                                </div>
                                <div class="col-md-3 form-group">
                                    <label><i class="fas fa-tag mr-1"></i> Brand Name</label>
                                    <input type="text" name="fibers[0][brand_name]" class="form-control form-control-sm" placeholder="e.g. Optix">
                                </div>
                                <div class="col-md-3 form-group">
                                    <label><i class="fas fa-ethernet mr-1"></i> Fiber Type</label>
                                    <select name="fibers[0][fiber_type]" class="form-control form-control-sm" required>
                                        <option value="">-- Select --</option>
                                        <option value="Single Mode">Single Mode</option>
                                        <option value="Multi Mode">Multi Mode</option>
                                    </select>
                                </div>
                                <div class="col-md-3 form-group">
                                    <label><i class="fas fa-circle-notch mr-1"></i> Core</label>
                                    <select name="fibers[0][core]" class="form-control form-control-sm" required>
                                        <option value="2">2 Core</option>
                                        <option value="4">4 Core</option>
                                        <option value="6">6 Core</option>
                                        <option value="8">8 Core</option>
                                        <option value="12">12 Core</option>
                                        <option value="24">24 Core</option>
                                    </select>
                                </div>
                                <div class="col-md-4 form-group">
                                    <label><i class="fas fa-ruler-horizontal mr-1"></i> Start Meter</label>
                                    <input type="number" name="fibers[0][start_meter]" class="form-control form-control-sm fiber-start" step="0.01" min="0" required>
                                </div>
                                <div class="col-md-4 form-group">
                                    <label><i class="fas fa-ruler-horizontal mr-1"></i> End Meter</label>
                                    <input type="number" name="fibers[0][end_meter]" class="form-control form-control-sm fiber-end" step="0.01" min="0" required>
                                </div>
                                <div class="col-md-4 form-group">
                                    <label><i class="fas fa-ruler mr-1"></i> Total Meter</label>
                                    <input type="text" class="form-control form-control-sm bg-light fiber-total-meter" readonly style="font-weight:bold; color:#28a745;">
                                </div>
                                <div class="col-md-3 form-group">
                                    <label><i class="fas fa-money-bill mr-1"></i> Per Meter Price (৳)</label>
                                    <input type="number" name="fibers[0][per_meter_price]" class="form-control form-control-sm fiber-price" step="0.01" min="0" placeholder="0.00">
                                </div>
                                <div class="col-md-3 form-group">
                                    <label><i class="fas fa-calculator mr-1"></i> Total Cost (৳)</label>
                                    <input type="text" class="form-control form-control-sm bg-light fiber-total-cost" readonly style="font-weight:bold; color:#007bff;">
                                </div>
                                <div class="col-md-6 form-group">
                                    <label><i class="fas fa-sticky-note mr-1"></i> Notes</label>
                                    <input type="text" name="fibers[0][notes]" class="form-control form-control-sm" placeholder="Optional notes...">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="text-center mt-2 mb-1">
                        <button type="button" id="add-another-fiber-btn" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-plus-circle mr-1"></i> Add Another Fiber
                        </button>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times mr-1"></i> Close</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Save Fiber Stock</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    if ($('#fiberStockTable thead th').length > 0 && $('#fiberStockTable tbody tr td[colspan]').length === 0) {
        $('#fiberStockTable').DataTable({
            order: [[0, 'asc']],
            autoWidth: false
        });
    }

    var fiberIndex = 0;

    function recalcFiber(item) {
        var s = parseFloat(item.find('.fiber-start').val()) || 0;
        var e = parseFloat(item.find('.fiber-end').val()) || 0;
        var t = e - s;
        item.find('.fiber-total-meter').val(t > 0 ? t.toFixed(2) + ' m' : '');
        var price = parseFloat(item.find('.fiber-price').val()) || 0;
        var cost = t > 0 && price > 0 ? (t * price) : 0;
        item.find('.fiber-total-cost').val(cost > 0 ? '৳' + cost.toFixed(2) : '');
    }

    $(document).on('input change', '.fiber-start, .fiber-end, .fiber-price', function() {
        recalcFiber($(this).closest('.fiber-item'));
    });

    function renumberFibers() {
        $('#fiber-items-container .fiber-item').each(function(i) {
            $(this).find('.fiber-item-number').text('Fiber #' + (i + 1));
            if ($('#fiber-items-container .fiber-item').length > 1) {
                $(this).find('.remove-fiber-btn').show();
            } else {
                $(this).find('.remove-fiber-btn').hide();
            }
        });
    }

    function getTodayDate() {
        var d = new Date();
        var mm = String(d.getMonth() + 1).padStart(2, '0');
        var dd = String(d.getDate()).padStart(2, '0');
        return d.getFullYear() + '-' + mm + '-' + dd;
    }

    $('#add-another-fiber-btn').on('click', function() {
        fiberIndex++;
        var template = $('#fiber-items-container .fiber-item:first').clone();
        template.attr('data-index', fiberIndex);
        template.find('input[type="text"]').val('');
        template.find('input[type="number"]').val('');
        template.find('.fiber-total-meter, .fiber-total-cost').val('');
        template.find('select').each(function() {
            if ($(this).attr('name') && $(this).attr('name').indexOf('core') !== -1) {
                $(this).val('2');
            } else {
                $(this).val('');
            }
        });
        template.find('[name]').each(function() {
            var name = $(this).attr('name');
            if (name) {
                $(this).attr('name', name.replace(/fibers\[\d+\]/, 'fibers[' + fiberIndex + ']'));
            }
        });
        $('#fiber-items-container').append(template);
        renumberFibers();
        template.find('input:visible:first').focus();
        var modalBody = $('#addFiberModal .modal-body');
        modalBody.animate({ scrollTop: modalBody[0].scrollHeight }, 300);
    });

    $(document).on('click', '.remove-fiber-btn', function() {
        if ($('#fiber-items-container .fiber-item').length > 1) {
            $(this).closest('.fiber-item').remove();
            renumberFibers();
        }
    });

    $('#addFiberModal').on('hidden.bs.modal', function() {
        $('#fiber-items-container .fiber-item:gt(0)').remove();
        var first = $('#fiber-items-container .fiber-item:first');
        first.find('input[type="text"], input[type="number"]').val('');
        first.find('.fiber-total-meter, .fiber-total-cost').val('');
        first.find('select').each(function() {
            if ($(this).attr('name') && $(this).attr('name').indexOf('core') !== -1) {
                $(this).val('2');
            } else {
                $(this).val('');
            }
        });
        fiberIndex = 0;
        renumberFibers();
    });
});
</script>
@endpush
