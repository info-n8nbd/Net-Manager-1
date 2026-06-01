@extends('layouts.admin')

@section('title', 'Product Out History')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="#">Inventory</a></li>
<li class="breadcrumb-item"><a href="{{ route('admin.inventory.instruments.index') }}">Products</a></li>
<li class="breadcrumb-item active">Product Out History</li>
@endsection

@section('content')
<div class="d-flex align-items-center mb-3" style="gap:0;">
    <a href="{{ route('admin.inventory.products.in_history') }}"
       class="btn btn-sm {{ request()->routeIs('admin.inventory.products.in_history') ? 'btn-success' : 'btn-outline-success' }}"
       style="border-radius:6px 0 0 6px; font-size:12px; padding:6px 16px; white-space:nowrap;">
        <i class="fas fa-arrow-circle-down mr-1"></i> Product In History
    </a>
    <a href="{{ route('admin.inventory.products.out_history') }}"
       class="btn btn-sm {{ request()->routeIs('admin.inventory.products.out_history') ? 'btn-danger' : 'btn-outline-danger' }}"
       style="border-radius:0 6px 6px 0; border-left:0; font-size:12px; padding:6px 16px; white-space:nowrap;">
        <i class="fas fa-arrow-circle-up mr-1"></i> Product Out History
    </a>
</div>

<div class="row mb-3">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-danger">
            <div class="inner">
                <h3>{{ $histories->total() }}</h3>
                <p>Total Stock-Out Records</p>
            </div>
            <div class="icon"><i class="fas fa-arrow-circle-up"></i></div>
        </div>
    </div>
</div>
@include('admin.inventory.partials.nav-buttons')

<div class="card card-danger card-outline">
    <div class="card-header d-flex align-items-center">
        <h3 class="card-title"><i class="fas fa-arrow-circle-up mr-2"></i>Product Out History</h3>
        <div class="card-tools d-flex align-items-center ml-auto">
            <div class="input-group input-group-sm mr-2" style="width:250px;">
                <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                </div>
                <input type="text" class="form-control form-control-sm table-quick-search" placeholder="Quick search..." data-table="outHistoryTable">
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table id="outHistoryTable" class="table table-bordered table-striped table-hover mb-0">
                <thead class="bg-dark text-white">
                    <tr>
                        <th style="width: 3%;">#</th>
                        <th style="width: 10%;">Date</th>
                        <th>Products</th>
                        <th style="width: 8%;">Qty</th>
                        <th style="width: 12%;">Reason</th>
                        <th style="width: 15%;">Issued To</th>
                        <th style="width: 10%;">Remarks</th>
                        <th style="width: 10%;">Issued By</th>
                        <th style="width: 10%;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($histories as $index => $h)
                    @php
                        $instrumentIds = json_decode($h->instrument_ids ?? '[]', true) ?: [];
                        $groupedProducts = [];
                        foreach ($instrumentIds as $iid) {
                            $inst = $instrumentsMap[(int)$iid] ?? null;
                            if ($inst) {
                                $label = $inst->category ?? '';
                                if ($inst->sub_category) $label .= ' - ' . $inst->sub_category;
                                $modelBrand = trim(($inst->brand ?? '') . ' ' . ($inst->model ?? ''));
                                $key = $label . '|' . $modelBrand;
                                if (!isset($groupedProducts[$key])) {
                                    $groupedProducts[$key] = ['label' => $label, 'model' => $modelBrand, 'serials' => [], 'qty' => 0];
                                }
                                $groupedProducts[$key]['qty']++;
                                if ($inst->serial_number) {
                                    $groupedProducts[$key]['serials'][] = $inst->serial_number;
                                }
                            }
                        }
                        if (empty($groupedProducts)) {
                            $desc = $h->description ?? '';
                            if (preg_match('/Products:\s*(.+)$/m', $desc, $m)) {
                                $parts = explode(';', $m[1]);
                                foreach ($parts as $part) {
                                    $part = trim($part);
                                    if ($part) {
                                        $groupedProducts[] = ['label' => $part, 'model' => '', 'serials' => [], 'qty' => 1];
                                    }
                                }
                            }
                        }
                    @endphp
                    <tr>
                        <td>{{ $histories->firstItem() + $index }}</td>
                        <td>{{ \Carbon\Carbon::parse($h->out_date)->format('d-m-Y') }}</td>
                        <td>
                            @if(count($groupedProducts) > 0)
                                @foreach($groupedProducts as $gp)
                                <div style="margin-bottom: 4px; {{ !$loop->last ? 'padding-bottom: 4px; border-bottom: 1px dashed #eee;' : '' }}">
                                    <strong style="font-size: 13px;">{{ $gp['label'] }}</strong>
                                    @if($gp['model'])
                                        <span class="text-muted" style="font-size: 12px;">| {{ $gp['model'] }}</span>
                                    @endif
                                    @if($gp['qty'] > 1 && empty($gp['serials']))
                                        <span class="badge badge-secondary badge-pill ml-1" style="font-size: 10px;">x{{ $gp['qty'] }}</span>
                                    @endif
                                    @if(!empty($gp['serials']))
                                        <br><code style="font-size: 11px; color: #e74c3c;">{{ implode(', ', $gp['serials']) }}</code>
                                    @endif
                                </div>
                                @endforeach
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td><span class="badge badge-danger badge-pill">{{ $h->quantity }}</span></td>
                        <td><span class="badge badge-warning">{{ $h->reason }}</span></td>
                        <td>
                            @if($h->issued_to && is_numeric($h->issued_to) && isset($clientNameMap[(int)$h->issued_to]))
                                {{ $clientNameMap[(int)$h->issued_to] }}
                            @else
                                {{ $h->issued_to ?? '-' }}
                            @endif
                        </td>
                        <td><small class="text-muted">{{ $h->remarks ?? '-' }}</small></td>
                        <td>{{ $h->created_by_name ?? '-' }}</td>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-primary edit-out-btn" data-id="{{ $h->id }}" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button type="button" class="btn btn-outline-danger delete-out-btn" data-id="{{ $h->id }}" data-qty="{{ $h->quantity }}" title="Delete">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted">No product out records found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer clearfix">
        {{ $histories->links() }}
    </div>
</div>

<div class="modal fade" id="editOutModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content" style="border-radius: 12px; border: none; overflow: hidden; box-shadow: 0 20px 60px rgba(0,0,0,0.15);">
            <form id="editOutForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header" style="background: linear-gradient(135deg, #3498db, #2980b9); border: none; padding: 18px 24px;">
                    <h5 class="modal-title text-white" style="font-weight: 700; font-size: 16px;">
                        <i class="fas fa-edit mr-2"></i> Edit Product Out Record
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" style="opacity: 0.9;"><span>&times;</span></button>
                </div>
                <div class="modal-body" style="padding: 24px;">
                    <div id="editOutLoading" class="text-center py-4">
                        <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                        <p class="mt-2 text-muted">Loading record...</p>
                    </div>
                    <div id="editOutFields" style="display:none;">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label><i class="fas fa-clipboard mr-1"></i> Reason <span class="text-danger">*</span></label>
                                    <select name="reason" id="editReason" class="form-control" required>
                                        @foreach(\App\Models\DropdownOption::getOptions('product_out_reasons') as $opt)
                                        <option value="{{ $opt }}">{{ $opt }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label><i class="fas fa-calendar mr-1"></i> Date <span class="text-danger">*</span></label>
                                    <input type="date" name="out_date" id="editOutDate" class="form-control" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                @include('admin.partials.client-search', [
                                    'inputId' => 'editClientSearch',
                                    'hiddenId' => 'editClientSearchId',
                                    'fieldName' => 'issued_to',
                                    'label' => 'Issued To (Client)',
                                    'required' => false,
                                    'searchUrl' => route('admin.inventory.products.out.search-clients'),
                                ])
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label><i class="fas fa-user-cog mr-1"></i> Technician</label>
                                    <select name="technician_id" id="editTechnician" class="form-control">
                                        <option value="">-- None --</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-sticky-note mr-1"></i> Remarks</label>
                            <textarea name="remarks" id="editRemarks" class="form-control" rows="2" placeholder="Optional notes..."></textarea>
                        </div>

                        <div class="card card-outline card-info mt-3 mb-0">
                            <div class="card-header py-2">
                                <h6 class="card-title mb-0"><i class="fas fa-boxes mr-1"></i> Products in this Record</h6>
                            </div>
                            <div class="card-body p-0">
                                <table class="table table-sm table-bordered mb-0" id="editProductsTable">
                                    <thead class="bg-light">
                                        <tr>
                                            <th style="width: 35%;">Product</th>
                                            <th style="width: 25%;">Brand / Model</th>
                                            <th style="width: 20%;">Serial / Qty</th>
                                            <th style="width: 10%;">Status</th>
                                            <th style="width: 10%;" class="text-center">Remove</th>
                                        </tr>
                                    </thead>
                                    <tbody id="editProductsBody"></tbody>
                                </table>
                            </div>
                        </div>
                        <div id="removedProductsContainer"></div>

                        <div class="alert alert-info py-2 px-3 mb-0 mt-3" style="font-size: 13px;">
                            <i class="fas fa-info-circle mr-1"></i> Removing products will return them to available stock. Changing the client will update "Assigned To" on remaining products.
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="border-top: 1px solid #f0f0f0; padding: 14px 24px;">
                    <button type="button" class="btn btn-light" data-dismiss="modal" style="border-radius: 8px; padding: 8px 20px; font-weight: 600;">
                        <i class="fas fa-times mr-1"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-primary" id="editOutSubmitBtn" style="border-radius: 8px; padding: 8px 24px; font-weight: 600;">
                        <i class="fas fa-save mr-1"></i> Update Record
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteOutModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content" style="border-radius: 12px; border: none; overflow: hidden; box-shadow: 0 20px 60px rgba(0,0,0,0.15);">
            <form id="deleteOutForm" method="POST">
                @csrf
                @method('DELETE')
                <div class="modal-header" style="background: linear-gradient(135deg, #e74c3c, #c0392b); border: none; padding: 18px 24px;">
                    <h5 class="modal-title text-white" style="font-weight: 700; font-size: 16px;">
                        <i class="fas fa-trash-alt mr-2"></i> Delete Product Out Record
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" style="opacity: 0.9;"><span>&times;</span></button>
                </div>
                <div class="modal-body" style="padding: 24px;">
                    <div class="text-center mb-3">
                        <i class="fas fa-exclamation-triangle text-danger" style="font-size: 48px;"></i>
                    </div>
                    <h5 class="text-center mb-3">Are you sure you want to delete this record?</h5>
                    <div class="alert alert-warning py-2 px-3" style="font-size: 13px;">
                        <i class="fas fa-exclamation-circle mr-1"></i> This will:
                        <ul class="mb-0 mt-1" style="padding-left: 18px;">
                            <li>Return <strong><span id="deleteQtyLabel">0</span></strong> product(s) back to available stock</li>
                            <li>Remove product assignment from the client's profile</li>
                            <li>Permanently delete this out record</li>
                        </ul>
                    </div>
                </div>
                <div class="modal-footer" style="border-top: 1px solid #f0f0f0; padding: 14px 24px;">
                    <button type="button" class="btn btn-light" data-dismiss="modal" style="border-radius: 8px; padding: 8px 20px; font-weight: 600;">
                        <i class="fas fa-times mr-1"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-danger" style="border-radius: 8px; padding: 8px 24px; font-weight: 600;">
                        <i class="fas fa-trash-alt mr-1"></i> Yes, Delete
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$('.table-quick-search').on('input', function() {
    var q = $(this).val().toLowerCase();
    var tableId = $(this).data('table');
    $('#' + tableId + ' tbody tr').each(function() {
        var text = $(this).text().toLowerCase();
        $(this).toggle(!q || text.indexOf(q) !== -1);
    });
});

$(document).on('click', '.edit-out-btn', function() {
    var id = $(this).data('id');
    var editUrl = "{{ url('admin/inventory/product-out-history') }}/" + id + "/edit";
    var updateUrl = "{{ url('admin/inventory/product-out-history') }}/" + id;

    $('#editOutForm').attr('action', updateUrl);
    $('#editOutLoading').show();
    $('#editOutFields').hide();
    $('#editOutModal').modal('show');

    $.get(editUrl, function(data) {
        var log = data.log;
        $('#editReason').val(log.reason);
        $('#editOutDate').val(log.out_date);
        $('#editRemarks').val(log.remarks || '');

        $('#editClientSearch').val(data.client_label || '');
        $('#editClientSearchId').val(data.client_id || '');
        var $info = $('#editClientSearchInfo');
        if (data.client_id && data.client_label) {
            $info.html('<i class="fas fa-check-circle text-success mr-1"></i> ' + data.client_label);
        } else {
            $info.html('');
        }

        var techHtml = '<option value="">-- None --</option>';
        if (data.technicians) {
            data.technicians.forEach(function(t) {
                var sel = (log.technician_id && log.technician_id == t.id) ? 'selected' : '';
                techHtml += '<option value="' + t.id + '" ' + sel + '>' + t.name + '</option>';
            });
        }
        $('#editTechnician').html(techHtml);

        var productsHtml = '';
        var removedHtml = '';
        if (data.products && data.products.length > 0) {
            data.products.forEach(function(p) {
                var serialQty = p.has_serial
                    ? '<code style="font-size:11px; color:#e74c3c;">' + (p.serial_number || 'N/A') + '</code>'
                    : '<span class="badge badge-secondary">' + p.quantity + ' pcs</span>';
                var statusBadge = p.has_serial
                    ? '<span class="badge badge-info">Serial</span>'
                    : '<span class="badge badge-secondary">Bulk</span>';
                productsHtml += '<tr data-product-id="' + p.id + '">'
                    + '<td><strong style="font-size:13px;">' + p.label + '</strong></td>'
                    + '<td><span class="text-muted" style="font-size:12px;">' + (p.model_brand || '-') + '</span></td>'
                    + '<td>' + serialQty + '</td>'
                    + '<td>' + statusBadge + '</td>'
                    + '<td class="text-center">'
                    + '<button type="button" class="btn btn-xs btn-outline-danger remove-product-btn" data-product-id="' + p.id + '" title="Remove from record">'
                    + '<i class="fas fa-times"></i>'
                    + '</button>'
                    + '</td>'
                    + '</tr>';
            });
        } else {
            productsHtml = '<tr><td colspan="5" class="text-center text-muted">No products linked</td></tr>';
        }
        $('#editProductsBody').html(productsHtml);
        $('#removedProductsContainer').html(removedHtml);

        $('#editOutLoading').hide();
        $('#editOutFields').show();
    }).fail(function() {
        $('#editOutLoading').html('<div class="text-danger"><i class="fas fa-exclamation-circle"></i> Failed to load record.</div>');
    });
});

$(document).on('click', '.remove-product-btn', function() {
    var $btn = $(this);
    var productId = $btn.data('product-id');
    var $row = $btn.closest('tr');

    $row.css({'background-color': '#ffeaea', 'text-decoration': 'line-through', 'opacity': '0.5'});
    $btn.replaceWith(
        '<button type="button" class="btn btn-xs btn-outline-success undo-remove-btn" data-product-id="' + productId + '" title="Undo remove">'
        + '<i class="fas fa-undo"></i>'
        + '</button>'
    );

    $('#removedProductsContainer').append(
        '<input type="hidden" name="removed_product_ids[]" value="' + productId + '" class="removed-input-' + productId + '">'
    );
});

$(document).on('click', '.undo-remove-btn', function() {
    var $btn = $(this);
    var productId = $btn.data('product-id');
    var $row = $btn.closest('tr');

    $row.css({'background-color': '', 'text-decoration': '', 'opacity': ''});
    $btn.replaceWith(
        '<button type="button" class="btn btn-xs btn-outline-danger remove-product-btn" data-product-id="' + productId + '" title="Remove from record">'
        + '<i class="fas fa-times"></i>'
        + '</button>'
    );

    $('.removed-input-' + productId).remove();
});

$(document).on('click', '.delete-out-btn', function() {
    var id = $(this).data('id');
    var qty = $(this).data('qty');
    var deleteUrl = "{{ url('admin/inventory/product-out-history') }}/" + id;

    $('#deleteOutForm').attr('action', deleteUrl);
    $('#deleteQtyLabel').text(qty);
    $('#deleteOutModal').modal('show');
});
</script>
@endpush
