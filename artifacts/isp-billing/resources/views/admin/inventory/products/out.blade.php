@extends('layouts.admin')

@section('title', 'Product Out')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="#">Inventory</a></li>
<li class="breadcrumb-item"><a href="{{ route('admin.inventory.instruments.index') }}">Products</a></li>
<li class="breadcrumb-item active">Product Out</li>
@endsection

@section('content')
@include('admin.inventory.partials.nav-buttons')

<div class="row">
    <div class="col-md-5">
        <div class="card card-danger card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-arrow-circle-up mr-2"></i>Record Product Out</h3>
            </div>
            <form method="POST" action="{{ route('admin.inventory.products.out.store') }}" id="productOutForm">
                @csrf
                <div class="card-body">
                    <div class="form-group">
                        <label><i class="fas fa-box mr-1"></i> Select Product <span class="text-danger">*</span></label>
                        <select id="productSelect" class="form-control">
                            <option value="">-- Select Product --</option>
                            @foreach($stockSummary as $item)
                                <option value="{{ $item['key'] }}" data-stock="{{ $item['stock'] }}" data-serials="{{ json_encode($item['serials']) }}" data-has-serial="{{ $item['has_serial'] ? '1' : '0' }}" data-label="{{ $item['category'] }}{{ $item['type'] ? ' - '.$item['type'] : '' }}{{ $item['brand'] ? ' | '.$item['brand'] : '' }}{{ $item['model'] ? ' '.$item['model'] : '' }}">
                                    {{ $item['category'] }}{{ $item['type'] ? ' - '.$item['type'] : '' }}{{ $item['brand'] ? ' | '.$item['brand'] : '' }}{{ $item['model'] ? ' '.$item['model'] : '' }} ({{ $item['stock'] }} in stock)
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group" id="serialSelectSection" style="display:none;">
                        <label><i class="fas fa-barcode mr-1"></i> Select Serial(s)</label>
                        <div class="input-group input-group-sm mb-1">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                            </div>
                            <input type="text" id="serialSearchInput" class="form-control form-control-sm" placeholder="Search by Serial or MAC...">
                        </div>
                        <div id="serialCheckboxes" class="border rounded p-1" style="max-height:150px; overflow-y:auto; font-size:0.82rem;">
                        </div>
                        <div class="mt-1 d-flex align-items-center">
                            <button type="button" class="btn btn-xs btn-outline-primary" onclick="selectAllSerials()">Select All</button>
                            <button type="button" class="btn btn-xs btn-outline-secondary ml-1" onclick="deselectAllSerials()">Deselect All</button>
                            <span class="ml-2 text-sm text-muted">Selected: <strong id="selectedCount">0</strong></span>
                            <span class="ml-auto text-sm text-muted" id="serialFilterInfo"></span>
                        </div>
                    </div>

                    <div class="form-group" id="bulkQtySection" style="display:none;">
                        <label><i class="fas fa-cubes mr-1"></i> Quantity to Out</label>
                        <div class="input-group">
                            <input type="number" id="bulkOutQty" class="form-control" min="1" value="1" placeholder="Enter quantity">
                            <div class="input-group-append">
                                <span class="input-group-text" id="bulkStockInfo">/ 0 in stock</span>
                            </div>
                        </div>
                        <small class="text-muted">Enter the number of pieces to mark as out.</small>
                    </div>

                    <div class="text-right mb-3">
                        <button type="button" class="btn btn-info btn-sm" id="addToListBtn" disabled>
                            <i class="fas fa-plus-circle mr-1"></i> Add to List
                        </button>
                    </div>

                    <div id="outListSection" style="display:none;">
                        <label><i class="fas fa-list mr-1"></i> Products to Out</label>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-2" id="outListTable">
                                <thead class="bg-secondary text-white">
                                    <tr>
                                        <th>#</th>
                                        <th>Product</th>
                                        <th class="text-center">Qty</th>
                                        <th style="width:40px;"></th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                        <div class="text-right mb-2">
                            <span class="text-muted text-sm">Total Items: <strong id="totalOutItems">0</strong></span>
                        </div>
                    </div>

                    <hr>

                    <div class="form-group">
                        <label><i class="fas fa-clipboard mr-1"></i> Reason <span class="text-danger">*</span></label>
                        <select name="reason" class="form-control" required>
                            <option value="">-- Select Reason --</option>
                            @foreach(\App\Models\DropdownOption::getOptions('product_out_reasons') as $opt)
                            <option value="{{ $opt }}">{{ $opt }}</option>
                            @endforeach
                        </select>
                    </div>

                    @include('admin.partials.client-search', [
                        'inputId' => 'prodOutClientSearch',
                        'hiddenId' => 'prodOutClientSearchId',
                        'fieldName' => 'issued_to',
                        'label' => 'Client Name',
                        'required' => false,
                    ])

                    <div class="form-group">
                        <label><i class="fas fa-user-cog mr-1"></i> Technician</label>
                        <select name="technician_id" class="form-control">
                            <option value="">-- Select Technician (Optional) --</option>
                            @foreach($technicians as $tech)
                                <option value="{{ $tech->id }}">{{ $tech->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- ===== Equipment Charge ===== --}}
                    <div class="form-group" id="equipChargeGroup">
                        <label style="font-size:12px;font-weight:700;"><i class="fas fa-money-bill-wave mr-1"></i> Equipment Charge Type</label>
                        <select name="charge_type" id="chargeTypeSelect" class="form-control" onchange="toggleChargeAmount(this.value)">
                            <option value="free">Free (No Charge)</option>
                            <option value="monthly_rental">Monthly Rental (৳/month added to bill)</option>
                            <option value="one_time_sale">One-time Sale (৳)</option>
                            <option value="refundable_deposit">Refundable Deposit (৳)</option>
                        </select>
                        <small class="text-muted" style="font-size:11px;"><i class="fas fa-info-circle mr-1"></i>Is the client paying for this equipment?</small>
                    </div>
                    <div class="form-group" id="chargeAmountSection" style="display:none;">
                        <label style="font-size:12px;font-weight:700;"><i class="fas fa-coins mr-1"></i> Amount (৳) <span class="text-danger">*</span></label>
                        <input type="number" name="charge_amount" id="chargeAmountInput" class="form-control" step="0.01" min="0" value="0" placeholder="Enter charge amount">
                        <div class="mt-2">
                            <label style="font-size:12px;font-weight:700;"><i class="fas fa-check-circle mr-1"></i> Payment Status</label>
                            <select name="payment_status" class="form-control">
                                <option value="paid">Paid — Collected Now</option>
                                <option value="due">Due — Collect Later</option>
                                <option value="waived">Waived</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-calendar mr-1"></i> Date <span class="text-danger">*</span></label>
                        <input type="date" name="out_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-sticky-note mr-1"></i> Remarks</label>
                        <textarea name="remarks" class="form-control" rows="2" placeholder="Optional notes..."></textarea>
                    </div>

                    <div id="outHiddenInputs"></div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-danger btn-block" id="submitOutBtn" disabled>
                        <i class="fas fa-arrow-circle-up mr-1"></i> Record Product Out
                    </button>
                </div>
            </form>
        </div>
    </div>
    <div class="col-md-7">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-boxes mr-2"></i>Available Stock Summary</h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                        <thead class="bg-dark text-white">
                            <tr>
                                <th>Category</th>
                                <th>Type</th>
                                <th>Brand</th>
                                <th>Model</th>
                                <th class="text-center">Stock</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($stockSummary as $item)
                            <tr>
                                <td><strong>{{ $item['category'] }}</strong></td>
                                <td>{{ $item['type'] ?: '-' }}</td>
                                <td>{{ $item['brand'] ?: '-' }}</td>
                                <td>{{ $item['model'] ?: '-' }}</td>
                                <td class="text-center">
                                    <span class="badge badge-{{ $item['stock'] > 5 ? 'success' : ($item['stock'] > 0 ? 'warning' : 'danger') }} badge-pill">
                                        {{ $item['stock'] }}
                                    </span>
                                </td>
                                <td>
                                    @if($item['stock'] > 5)
                                        <span class="badge badge-success"><i class="fas fa-check mr-1"></i>OK</span>
                                    @elseif($item['stock'] > 0)
                                        <span class="badge badge-warning"><i class="fas fa-exclamation-triangle mr-1"></i>Low</span>
                                    @else
                                        <span class="badge badge-danger"><i class="fas fa-times mr-1"></i>Empty</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">No products with stock available.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="confirmOutModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content" style="border-radius: 12px; border: none; overflow: hidden; box-shadow: 0 20px 60px rgba(0,0,0,0.15);">
            <div class="modal-header" style="background: linear-gradient(135deg, #e74c3c, #c0392b); border: none; padding: 18px 24px;">
                <h5 class="modal-title text-white" style="font-weight: 700; font-size: 16px;">
                    <i class="fas fa-exclamation-triangle mr-2"></i> Confirm Product Out
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" style="opacity: 0.9;"><span>&times;</span></button>
            </div>
            <div class="modal-body" style="padding: 24px;">
                <div style="text-align: center; margin-bottom: 16px;">
                    <div style="width: 60px; height: 60px; border-radius: 50%; background: rgba(231,76,60,0.1); display: inline-flex; align-items: center; justify-content: center; margin-bottom: 12px;">
                        <i class="fas fa-arrow-circle-up" style="font-size: 28px; color: #e74c3c;"></i>
                    </div>
                    <h5 style="font-weight: 700; color: #2c3e50; margin-bottom: 6px;">Are you sure?</h5>
                    <p style="color: #7f8c8d; font-size: 14px; margin: 0;" id="confirmOutText"></p>
                </div>
                <div style="background: #fef9e7; border: 1px solid #f9e79f; border-radius: 8px; padding: 12px 16px; font-size: 13px; color: #7d6608;">
                    <i class="fas fa-info-circle mr-1"></i> This action will reduce stock and cannot be undone easily.
                </div>
            </div>
            <div class="modal-footer" style="border-top: 1px solid #f0f0f0; padding: 14px 24px;">
                <button type="button" class="btn btn-light" data-dismiss="modal" style="border-radius: 8px; padding: 8px 20px; font-weight: 600;">
                    <i class="fas fa-times mr-1"></i> Cancel
                </button>
                <button type="button" class="btn btn-danger" id="confirmOutYes" style="border-radius: 8px; padding: 8px 24px; font-weight: 600;">
                    <i class="fas fa-check-circle mr-1"></i> Yes, Record Out
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
var outList = [];
var usedSerialIds = {};

$('#productSelect').on('change', function() {
    var selected = $(this).find('option:selected');
    var serials = selected.data('serials');
    var hasSerial = selected.data('has-serial');
    var stock = parseInt(selected.data('stock')) || 0;
    var $serialSection = $('#serialSelectSection');
    var $bulkSection = $('#bulkQtySection');
    var $checkboxes = $('#serialCheckboxes');

    $checkboxes.html('');
    $('#selectedCount').text('0');
    $('#addToListBtn').prop('disabled', true);
    $('#serialSearchInput').val('');
    $('#serialFilterInfo').text('');
    $serialSection.hide();
    $bulkSection.hide();

    if (!$(this).val() || !serials || serials.length === 0) {
        return;
    }

    if (hasSerial == '0') {
        var firstItem = serials[0];
        var usedQty = 0;
        outList.forEach(function(item) {
            if (item.bulkId == firstItem.id) usedQty += item.qty;
        });
        var availableQty = stock - usedQty;
        if (availableQty <= 0) {
            $bulkSection.show();
            $('#bulkOutQty').attr('max', 0).val(0);
            $('#bulkStockInfo').text('/ 0 available');
            return;
        }
        $('#bulkOutQty').attr('max', availableQty).val(1);
        $('#bulkStockInfo').text('/ ' + availableQty + ' available');
        $bulkSection.show();
        $('#addToListBtn').prop('disabled', false);
    } else {
        var html = '';
        serials.forEach(function(s) {
            if (usedSerialIds[s.id]) return;
            html += '<div class="custom-control custom-checkbox py-0 serial-item" data-serial="' + (s.serial || '').toLowerCase() + '">';
            html += '  <input class="custom-control-input serial-checkbox" type="checkbox" value="' + s.id + '" id="serial_' + s.id + '" data-serial-text="' + (s.serial || 'No Serial') + '">';
            html += '  <label class="custom-control-label" for="serial_' + s.id + '" style="font-size:0.82rem;line-height:1.4;"><code class="text-primary">' + s.serial + '</code></label>';
            html += '</div>';
        });
        if (!html) {
            $checkboxes.html('<div class="text-muted text-center py-2">All serials already added to list</div>');
        } else {
            $checkboxes.html(html);
        }
        $serialSection.show();
    }
});

$('#serialSearchInput').on('input', function() {
    var q = $(this).val().trim().toLowerCase();
    var total = 0, shown = 0;
    $('.serial-item').each(function() {
        total++;
        var serial = $(this).data('serial');
        if (!q || serial.indexOf(q) !== -1) {
            $(this).show();
            shown++;
        } else {
            $(this).hide();
        }
    });
    if (q && total > 0) {
        $('#serialFilterInfo').text(shown + '/' + total + ' shown');
    } else {
        $('#serialFilterInfo').text('');
    }
});

$(document).on('change', '.serial-checkbox', function() {
    var count = $('.serial-checkbox:checked').length;
    $('#selectedCount').text(count);
    $('#addToListBtn').prop('disabled', count === 0);
});

function selectAllSerials() {
    $('.serial-item:visible .serial-checkbox').prop('checked', true);
    var count = $('.serial-checkbox:checked').length;
    $('#selectedCount').text(count);
    $('#addToListBtn').prop('disabled', count === 0);
}
function deselectAllSerials() {
    $('.serial-item:visible .serial-checkbox').prop('checked', false);
    $('#selectedCount').text('0');
    $('#addToListBtn').prop('disabled', true);
}

$('#addToListBtn').on('click', function() {
    var selected = $('#productSelect').find('option:selected');
    var hasSerial = selected.data('has-serial');
    var label = selected.data('label');

    if (hasSerial == '0') {
        var serials = selected.data('serials');
        var firstItem = serials[0];
        var qty = parseInt($('#bulkOutQty').val()) || 0;
        var maxQty = parseInt($('#bulkOutQty').attr('max')) || 0;
        if (qty <= 0 || qty > maxQty) {
            alert('Please enter a valid quantity (max ' + maxQty + ').');
            return;
        }
        outList.push({
            type: 'bulk',
            bulkId: firstItem.id,
            qty: qty,
            label: label,
            detail: qty + ' pcs'
        });
    } else {
        var checked = $('.serial-checkbox:checked');
        if (checked.length === 0) {
            alert('Please select at least one serial.');
            return;
        }
        var ids = [];
        var serialTexts = [];
        checked.each(function() {
            var id = $(this).val();
            ids.push(id);
            serialTexts.push($(this).data('serial-text'));
            usedSerialIds[id] = true;
        });
        outList.push({
            type: 'serial',
            instrumentIds: ids,
            qty: ids.length,
            label: label,
            detail: serialTexts.join(', ')
        });
    }

    renderOutList();
    $('#productSelect').val('').trigger('change');
});

function renderOutList() {
    var $tbody = $('#outListTable tbody');
    var $hidden = $('#outHiddenInputs');
    $tbody.html('');
    $hidden.html('');
    var totalItems = 0;

    if (outList.length === 0) {
        $('#outListSection').hide();
        $('#submitOutBtn').prop('disabled', true);
        $('#totalOutItems').text('0');
        return;
    }

    outList.forEach(function(item, idx) {
        totalItems += item.qty;
        var detailShort = item.detail.length > 40 ? item.detail.substring(0, 40) + '...' : item.detail;
        $tbody.append(
            '<tr>' +
            '<td>' + (idx + 1) + '</td>' +
            '<td><strong>' + item.label + '</strong><br><small class="text-muted">' + detailShort + '</small></td>' +
            '<td class="text-center"><span class="badge badge-warning badge-pill">' + item.qty + '</span></td>' +
            '<td><button type="button" class="btn btn-xs btn-outline-danger remove-out-item" data-index="' + idx + '" title="Remove"><i class="fas fa-times"></i></button></td>' +
            '</tr>'
        );

        if (item.type === 'bulk') {
            $hidden.append('<input type="hidden" name="items[' + idx + '][type]" value="bulk">');
            $hidden.append('<input type="hidden" name="items[' + idx + '][bulk_out_id]" value="' + item.bulkId + '">');
            $hidden.append('<input type="hidden" name="items[' + idx + '][bulk_out_qty]" value="' + item.qty + '">');
        } else {
            $hidden.append('<input type="hidden" name="items[' + idx + '][type]" value="serial">');
            item.instrumentIds.forEach(function(id) {
                $hidden.append('<input type="hidden" name="items[' + idx + '][instrument_ids][]" value="' + id + '">');
            });
        }
    });

    $('#outListSection').show();
    $('#submitOutBtn').prop('disabled', false);
    $('#totalOutItems').text(totalItems);
}

$(document).on('click', '.remove-out-item', function() {
    var idx = parseInt($(this).data('index'));
    var removed = outList[idx];
    if (removed.type === 'serial') {
        removed.instrumentIds.forEach(function(id) {
            delete usedSerialIds[id];
        });
    }
    outList.splice(idx, 1);
    renderOutList();
});

$('#productOutForm').on('submit', function(e) {
    if (outList.length === 0) {
        e.preventDefault();
        alert('Please add at least one product to the list.');
        return false;
    }

    e.preventDefault();
    var totalItems = 0;
    outList.forEach(function(item) { totalItems += item.qty; });
    $('#confirmOutText').text('You are about to mark ' + totalItems + ' item(s) across ' + outList.length + ' product(s) as out from stock.');
    $('#confirmOutModal').modal('show');
    return false;
});

$('#confirmOutYes').on('click', function() {
    $('#confirmOutModal').modal('hide');
    $('#productOutForm').off('submit').submit();
});

function toggleChargeAmount(val) {
    if (val === 'free') {
        $('#chargeAmountSection').hide();
        $('#chargeAmountInput').val(0);
    } else {
        $('#chargeAmountSection').show();
    }
}

</script>
@endpush
