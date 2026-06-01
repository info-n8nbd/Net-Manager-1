@extends('layouts.admin')
@section('title', 'Product History')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.inventory.stock.summary') }}">Inventory</a></li>
<li class="breadcrumb-item active">Product History</li>
@endsection

@section('content')
@include('admin.inventory.partials.nav-buttons')

<div class="card card-outline card-primary shadow-sm">
    <div class="card-header p-0 border-bottom-0" style="background:#fff;">
        <ul class="nav nav-tabs px-2 pt-2" id="productHistoryTabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link {{ $tab === 'in' ? 'active font-weight-bold' : '' }}"
                   href="{{ route('admin.inventory.products.history', ['tab' => 'in']) }}"
                   style="font-size:13px;">
                    <i class="fas fa-arrow-circle-down mr-1 text-success"></i> In History
                    @if($tab === 'in' && $inHistories)
                        <span class="badge badge-success ml-1" style="font-size:10px;">{{ $inHistories->total() }}</span>
                    @endif
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $tab === 'out' ? 'active font-weight-bold' : '' }}"
                   href="{{ route('admin.inventory.products.history', ['tab' => 'out']) }}"
                   style="font-size:13px;">
                    <i class="fas fa-arrow-circle-up mr-1 text-danger"></i> Out History
                    @if($tab === 'out' && $outHistories)
                        <span class="badge badge-danger ml-1" style="font-size:10px;">{{ $outHistories->total() }}</span>
                    @endif
                </a>
            </li>
        </ul>
    </div>

    <div class="card-body p-0">

        @if($tab === 'in')
        {{-- ===== IN HISTORY ===== --}}
        <div class="px-3 py-2 border-bottom d-flex align-items-center justify-content-between" style="background:#f8fff8;">
            <small class="text-muted"><i class="fas fa-info-circle mr-1"></i>Total Stock-In Records: <strong>{{ $inHistories->total() }}</strong></small>
            <div class="input-group input-group-sm" style="width:260px;">
                <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-search"></i></span></div>
                <input type="text" class="form-control table-quick-search" placeholder="Quick search..." data-table="productInHistoryTable">
            </div>
        </div>
        <div class="table-responsive">
            <table id="productInHistoryTable" class="table table-bordered table-striped table-hover table-sm mb-0">
                <thead class="bg-success text-white">
                    <tr>
                        <th style="width:40px;">#</th>
                        <th>Date</th>
                        <th>Invoice</th>
                        <th>Product</th>
                        <th>Brand</th>
                        <th class="text-center">Qty In</th>
                        <th>Unit</th>
                        <th>Reference</th>
                        <th>Details</th>
                        <th>Added By</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($inHistories as $index => $h)
                    <tr>
                        <td>{{ $inHistories->firstItem() + $index }}</td>
                        <td>{{ \Carbon\Carbon::parse($h->created_at)->format('d-m-y H:i') }}</td>
                        <td>
                            @if($h->reference_type === 'instrument')
                                <span class="badge badge-dark">{{ $h->product_code }}</span>
                            @elseif($h->reference_type === 'purchase')
                                <span class="badge badge-info">Purchase #{{ $h->reference_id }}</span>
                            @else
                                <code>{{ $h->product_code ?? '-' }}</code>
                            @endif
                        </td>
                        <td><strong>{{ $h->product_name ?? '-' }}</strong></td>
                        <td>{{ $h->product_brand ?? '-' }}</td>
                        <td class="text-center"><span class="badge badge-success">+ {{ $h->quantity }}</span></td>
                        <td>{{ $h->product_unit ?? 'pcs' }}</td>
                        <td>
                            @if($h->reference_type === 'purchase')
                                <span class="badge badge-info"><i class="fas fa-shopping-cart mr-1"></i>Purchase</span>
                            @elseif($h->reference_type === 'product_create')
                                <span class="badge badge-primary"><i class="fas fa-plus-circle mr-1"></i>Product Create</span>
                            @elseif($h->reference_type === 'instrument')
                                <span class="badge badge-success"><i class="fas fa-tools mr-1"></i>Instrument Add</span>
                            @elseif($h->reference_type)
                                <span class="badge badge-secondary">{{ ucfirst(str_replace('_', ' ', $h->reference_type)) }}</span>
                            @else
                                <span class="badge badge-light">Manual Entry</span>
                            @endif
                        </td>
                        <td style="font-size:12px;max-width:320px;">
                            @if($h->reference_type === 'instrument' && $h->products_detail)
                                @php $parts = explode(' | Serials: ', $h->products_detail); $info = $parts[0] ?? ''; $serials = $parts[1] ?? ''; @endphp
                                <div>{{ $info }}</div>
                                @if($serials)<div class="mt-1"><small class="text-muted"><i class="fas fa-barcode mr-1"></i>{{ $serials }}</small></div>@endif
                                @if(isset($h->total_price) && $h->total_price > 0)
                                    <div class="mt-1"><small class="text-success font-weight-bold"><i class="fas fa-money-bill mr-1"></i>৳{{ number_format($h->total_price, 2) }}</small></div>
                                @endif
                            @else
                                {{ $h->description ?? '-' }}
                            @endif
                        </td>
                        <td>{{ $h->created_by_name ?? '-' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="10" class="text-center text-muted py-4"><i class="fas fa-inbox fa-2x mb-2 d-block"></i>No product in records found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-3 py-2">{{ $inHistories->appends(request()->query())->links() }}</div>

        @else
        {{-- ===== OUT HISTORY ===== --}}
        <div class="px-3 py-2 border-bottom d-flex align-items-center justify-content-between" style="background:#fff8f8;">
            <small class="text-muted"><i class="fas fa-info-circle mr-1"></i>Total Stock-Out Records: <strong>{{ $outHistories->total() }}</strong></small>
            <div class="input-group input-group-sm" style="width:260px;">
                <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-search"></i></span></div>
                <input type="text" class="form-control table-quick-search" placeholder="Quick search..." data-table="outHistoryTable">
            </div>
        </div>
        <div class="table-responsive">
            <table id="outHistoryTable" class="table table-bordered table-striped table-hover table-sm mb-0">
                <thead class="bg-danger text-white">
                    <tr>
                        <th style="width:40px;">#</th>
                        <th>Date</th>
                        <th>Products</th>
                        <th class="text-center">Qty</th>
                        <th>Reason</th>
                        <th>Issued To</th>
                        <th>Remarks</th>
                        <th>Issued By</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($outHistories as $index => $h)
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
                                if (!isset($groupedProducts[$key])) $groupedProducts[$key] = ['label' => $label, 'model' => $modelBrand, 'serials' => [], 'qty' => 0];
                                $groupedProducts[$key]['qty']++;
                                if ($inst->serial_number) $groupedProducts[$key]['serials'][] = $inst->serial_number;
                            }
                        }
                        if (empty($groupedProducts)) {
                            $desc = $h->description ?? '';
                            if (preg_match('/Products:\s*(.+)$/m', $desc, $m)) {
                                foreach (explode(';', $m[1]) as $part) {
                                    $part = trim($part);
                                    if ($part) $groupedProducts[] = ['label' => $part, 'model' => '', 'serials' => [], 'qty' => 1];
                                }
                            }
                        }
                    @endphp
                    <tr>
                        <td>{{ $outHistories->firstItem() + $index }}</td>
                        <td>{{ \Carbon\Carbon::parse($h->out_date)->format('d-m-Y') }}</td>
                        <td>
                            @if(count($groupedProducts) > 0)
                                @foreach($groupedProducts as $gp)
                                <div style="margin-bottom:3px;{{ !$loop->last ? 'padding-bottom:3px;border-bottom:1px dashed #eee;' : '' }}">
                                    <strong style="font-size:12px;">{{ $gp['label'] }}</strong>
                                    @if($gp['model'])<span class="text-muted" style="font-size:11px;"> | {{ $gp['model'] }}</span>@endif
                                    @if($gp['qty'] > 1 && empty($gp['serials']))<span class="badge badge-secondary badge-pill ml-1" style="font-size:9px;">x{{ $gp['qty'] }}</span>@endif
                                    @if(!empty($gp['serials']))<br><code style="font-size:10px;color:#e74c3c;">{{ implode(', ', $gp['serials']) }}</code>@endif
                                </div>
                                @endforeach
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="text-center"><span class="badge badge-danger badge-pill">{{ $h->quantity }}</span></td>
                        <td><span class="badge badge-warning" style="font-size:10px;">{{ $h->reason }}</span></td>
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
                                <button type="button" class="btn btn-outline-primary edit-out-btn" data-id="{{ $h->id }}" title="Edit"><i class="fas fa-edit"></i></button>
                                <button type="button" class="btn btn-outline-danger delete-out-btn" data-id="{{ $h->id }}" data-qty="{{ $h->quantity }}" title="Delete"><i class="fas fa-trash-alt"></i></button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="9" class="text-center text-muted py-4"><i class="fas fa-inbox fa-2x mb-2 d-block"></i>No product out records found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-3 py-2">{{ $outHistories->appends(request()->query())->links() }}</div>
        @endif

    </div>
</div>

@if($tab === 'out')
{{-- Edit Modal --}}
<div class="modal fade" id="editOutModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="border-radius:12px;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,.15);">
            <form id="editOutForm" method="POST">
                @csrf @method('PUT')
                <div class="modal-header" style="background:linear-gradient(135deg,#3498db,#2980b9);border:none;padding:18px 24px;">
                    <h5 class="modal-title text-white" style="font-weight:700;font-size:16px;"><i class="fas fa-edit mr-2"></i>Edit Product Out Record</h5>
                    <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body" style="padding:24px;">
                    <div id="editOutLoading" class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x text-primary"></i><p class="mt-2 text-muted">Loading record...</p></div>
                    <div id="editOutFields" style="display:none;">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Reason <span class="text-danger">*</span></label>
                                    <select name="reason" id="editReason" class="form-control" required>
                                        @foreach(\App\Models\DropdownOption::getOptions('product_out_reasons') as $opt)
                                        <option value="{{ $opt }}">{{ $opt }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Date <span class="text-danger">*</span></label>
                                    <input type="date" name="out_date" id="editOutDate" class="form-control" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                @include('admin.partials.client-search', ['inputId'=>'editClientSearch','hiddenId'=>'editClientSearchId','fieldName'=>'issued_to','label'=>'Issued To (Client)','required'=>false,'searchUrl'=>route('admin.inventory.products.out.search-clients')])
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Technician</label>
                                    <select name="technician_id" id="editTechnician" class="form-control"><option value="">-- None --</option></select>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Remarks</label>
                            <textarea name="remarks" id="editRemarks" class="form-control" rows="2" placeholder="Optional notes..."></textarea>
                        </div>
                        <div class="card card-outline card-info mt-3 mb-0">
                            <div class="card-header py-2"><h6 class="card-title mb-0"><i class="fas fa-boxes mr-1"></i>Products in this Record</h6></div>
                            <div class="card-body p-0">
                                <table class="table table-sm table-bordered mb-0" id="editProductsTable">
                                    <thead class="bg-light"><tr><th>Product</th><th>Brand / Model</th><th>Serial / Qty</th><th>Status</th><th class="text-center">Remove</th></tr></thead>
                                    <tbody id="editProductsBody"></tbody>
                                </table>
                            </div>
                        </div>
                        <div id="removedProductsContainer"></div>
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid #f0f0f0;padding:14px 24px;">
                    <button type="button" class="btn btn-light" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="editOutSubmitBtn"><i class="fas fa-save mr-1"></i>Update Record</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Delete Modal --}}
<div class="modal fade" id="deleteOutModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:12px;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,.15);">
            <form id="deleteOutForm" method="POST">
                @csrf @method('DELETE')
                <div class="modal-header" style="background:linear-gradient(135deg,#e74c3c,#c0392b);border:none;padding:18px 24px;">
                    <h5 class="modal-title text-white" style="font-weight:700;font-size:16px;"><i class="fas fa-trash-alt mr-2"></i>Delete Product Out Record</h5>
                    <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body" style="padding:24px;">
                    <div class="text-center mb-3"><i class="fas fa-exclamation-triangle text-danger" style="font-size:48px;"></i></div>
                    <h5 class="text-center mb-3">Are you sure you want to delete this record?</h5>
                    <div class="alert alert-warning py-2 px-3" style="font-size:13px;">
                        <i class="fas fa-exclamation-circle mr-1"></i>This will return <strong><span id="deleteQtyLabel">0</span></strong> product(s) to available stock and delete this record permanently.
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid #f0f0f0;padding:14px 24px;">
                    <button type="button" class="btn btn-light" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger"><i class="fas fa-trash-alt mr-1"></i>Yes, Delete</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
$('.table-quick-search').on('input', function() {
    var q = $(this).val().toLowerCase();
    var tableId = $(this).data('table');
    $('#' + tableId + ' tbody tr').each(function() {
        $(this).toggle(!q || $(this).text().toLowerCase().indexOf(q) !== -1);
    });
});

@if($tab === 'out')
$(document).on('click', '.edit-out-btn', function() {
    var id = $(this).data('id');
    $('#editOutForm').attr('action', "{{ url('admin/inventory/product-out-history') }}/" + id);
    $('#editOutLoading').show();
    $('#editOutFields').hide();
    $('#editOutModal').modal('show');
    $.get("{{ url('admin/inventory/product-out-history') }}/" + id + "/edit", function(data) {
        var log = data.log;
        $('#editReason').val(log.reason);
        $('#editOutDate').val(log.out_date);
        $('#editRemarks').val(log.remarks || '');
        $('#editClientSearch').val(data.client_label || '');
        $('#editClientSearchId').val(data.client_id || '');
        var $info = $('#editClientSearchInfo');
        if (data.client_id && data.client_label) { $info.html('<i class="fas fa-check-circle text-success mr-1"></i>' + data.client_label); } else { $info.html(''); }
        var techHtml = '<option value="">-- None --</option>';
        if (data.technicians) { data.technicians.forEach(function(t) { var sel = (log.technician_id && log.technician_id == t.id) ? 'selected' : ''; techHtml += '<option value="' + t.id + '" ' + sel + '>' + t.name + '</option>'; }); }
        $('#editTechnician').html(techHtml);
        var productsHtml = '';
        if (data.products && data.products.length > 0) {
            data.products.forEach(function(p) {
                var serialQty = p.has_serial ? '<code style="font-size:11px;color:#e74c3c;">' + (p.serial_number || 'N/A') + '</code>' : '<span class="badge badge-secondary">' + p.quantity + ' pcs</span>';
                var statusBadge = p.has_serial ? '<span class="badge badge-info">Serial</span>' : '<span class="badge badge-secondary">Bulk</span>';
                productsHtml += '<tr data-product-id="' + p.id + '"><td><strong>' + p.label + '</strong></td><td><span class="text-muted">' + (p.model_brand || '-') + '</span></td><td>' + serialQty + '</td><td>' + statusBadge + '</td><td class="text-center"><button type="button" class="btn btn-xs btn-outline-danger remove-product-btn" data-product-id="' + p.id + '"><i class="fas fa-times"></i></button></td></tr>';
            });
        } else { productsHtml = '<tr><td colspan="5" class="text-center text-muted">No products linked</td></tr>'; }
        $('#editProductsBody').html(productsHtml);
        $('#removedProductsContainer').html('');
        $('#editOutLoading').hide();
        $('#editOutFields').show();
    }).fail(function() { $('#editOutLoading').html('<div class="text-danger"><i class="fas fa-exclamation-circle"></i> Failed to load.</div>'); });
});
$(document).on('click', '.remove-product-btn', function() {
    var productId = $(this).data('product-id');
    var $row = $(this).closest('tr');
    $row.css({'background-color':'#ffeaea','text-decoration':'line-through','opacity':'0.5'});
    $(this).replaceWith('<button type="button" class="btn btn-xs btn-outline-success undo-remove-btn" data-product-id="' + productId + '"><i class="fas fa-undo"></i></button>');
    $('#removedProductsContainer').append('<input type="hidden" name="removed_product_ids[]" value="' + productId + '" class="removed-input-' + productId + '">');
});
$(document).on('click', '.undo-remove-btn', function() {
    var productId = $(this).data('product-id');
    var $row = $(this).closest('tr');
    $row.css({'background-color':'','text-decoration':'','opacity':''});
    $(this).replaceWith('<button type="button" class="btn btn-xs btn-outline-danger remove-product-btn" data-product-id="' + productId + '"><i class="fas fa-times"></i></button>');
    $('.removed-input-' + productId).remove();
});
$(document).on('click', '.delete-out-btn', function() {
    var id = $(this).data('id');
    $('#deleteOutForm').attr('action', "{{ url('admin/inventory/product-out-history') }}/" + id);
    $('#deleteQtyLabel').text($(this).data('qty'));
    $('#deleteOutModal').modal('show');
});
@endif
</script>
@endpush
