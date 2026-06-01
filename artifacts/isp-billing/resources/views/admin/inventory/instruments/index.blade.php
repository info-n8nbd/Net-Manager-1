@extends('layouts.admin')

@section('title', 'Products - Inventory')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="#">Inventory</a></li>
<li class="breadcrumb-item active">Products</li>
@endsection

@section('content')
<div class="row">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>{{ $instruments->sum(fn($i) => $i->quantity ?? 1) }}</h3>
                <p>Total Products</p>
            </div>
            <div class="icon"><i class="fas fa-tools"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>{{ $instruments->where('status', 'available')->sum(fn($i) => $i->quantity ?? 1) }}</h3>
                <p>In Stock</p>
            </div>
            <div class="icon"><i class="fas fa-check-circle"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3>{{ $instruments->where('status', 'in_use')->sum(fn($i) => $i->quantity ?? 1) }}</h3>
                <p>Out / In Use</p>
            </div>
            <div class="icon"><i class="fas fa-user-cog"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-danger">
            <div class="inner">
                <h3>{{ $instruments->whereIn('status', ['under_repair', 'retired'])->sum(fn($i) => $i->quantity ?? 1) }}</h3>
                <p>Repair / Retired</p>
            </div>
            <div class="icon"><i class="fas fa-wrench"></i></div>
        </div>
    </div>
</div>
@include('admin.inventory.partials.nav-buttons')

<div class="card card-primary card-outline">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-tools mr-2"></i>Product List</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addInstrumentModal">
                <i class="fas fa-plus mr-1"></i> Add Product
            </button>
        </div>
    </div>
    <div class="card-body">
        {{-- Quick Search --}}
        <div class="row mb-3">
            <div class="col-md-6">
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text bg-primary text-white"><i class="fas fa-search"></i></span>
                    </div>
                    <input type="text" id="quickSearch" class="form-control" placeholder="Quick search by invoice, category, type, brand, model, serial..." autocomplete="off">
                    <div class="input-group-append" id="quickSearchClear" style="display:none;">
                        <button class="btn btn-outline-secondary" type="button" onclick="clearQuickSearch()"><i class="fas fa-times"></i></button>
                    </div>
                </div>
                <small class="text-muted" id="quickSearchCount" style="display:none;"></small>
            </div>
        </div>

        {{-- Filter Section --}}
        <div class="row mb-3" id="filterSection">
            <div class="col-md-2">
                <label class="text-xs font-weight-bold">Category</label>
                <select id="filterCategory" class="form-control form-control-sm" onchange="onCategoryFilter()">
                    <option value="">All Categories</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="text-xs font-weight-bold">Type</label>
                <select id="filterType" class="form-control form-control-sm" onchange="onDependentFilter()">
                    <option value="">All Types</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="text-xs font-weight-bold">Brand</label>
                <select id="filterBrand" class="form-control form-control-sm" onchange="onDependentFilter()">
                    <option value="">All Brands</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="text-xs font-weight-bold">Model</label>
                <select id="filterModel" class="form-control form-control-sm" onchange="onDependentFilter()">
                    <option value="">All Models</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="text-xs font-weight-bold">Invoice</label>
                <select id="filterInvoice" class="form-control form-control-sm" onchange="onDependentFilter()">
                    <option value="">All Invoices</option>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="button" class="btn btn-outline-secondary btn-sm btn-block" onclick="clearFilters()">
                    <i class="fas fa-undo mr-1"></i> Clear
                </button>
            </div>
        </div>

        <table id="instrumentTable" class="table table-bordered table-hover">
            <thead class="bg-dark text-white">
                <tr>
                    <th style="width:30px;">#</th>
                    <th>Invoice</th>
                    <th>Category</th>
                    <th>Type</th>
                    <th>Brand</th>
                    <th>Model</th>
                    <th class="text-center">Total</th>
                    <th class="text-center">Stock</th>
                    <th class="text-center">Out</th>
                    <th class="text-center">Repair</th>
                    <th>Purchase Price</th>
                    <th>Total Price</th>
                    <th style="width:60px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $grouped = $instruments->groupBy(function($item) {
                        return ($item->invoice_number ?? '') . '||' . $item->category . '||' . ($item->sub_category ?? '') . '||' . ($item->brand ?? '') . '||' . ($item->model ?? '');
                    });
                    $rowIndex = 0;
                @endphp

                @forelse($grouped as $key => $items)
                    @php
                        $rowIndex++;
                        $parts = explode('||', $key);
                        $invoiceName = $parts[0] ?? '-';
                        $catName = $parts[1] ?? '-';
                        $typeName = $parts[2] ?? '';
                        $brandName = $parts[3] ?? '-';
                        $modelName = $parts[4] ?? '-';
                        $total = $items->sum(fn($i) => $i->quantity ?? 1);
                        $stock = $items->where('status', 'available')->sum(fn($i) => $i->quantity ?? 1);
                        $out = $items->where('status', 'in_use')->sum(fn($i) => $i->quantity ?? 1);
                        $repair = $items->whereIn('status', ['under_repair', 'retired'])->sum(fn($i) => $i->quantity ?? 1);
                        $avgPrice = $items->avg('purchase_price');
                    @endphp
                    <tr class="product-group-row" data-toggle="collapse" data-target="#serials_{{ $rowIndex }}" style="cursor:pointer;" title="Click to see serials"
                        data-category="{{ $catName }}" data-type="{{ $typeName }}" data-brand="{{ $brandName }}" data-model="{{ $modelName }}" data-invoice="{{ $invoiceName }}">
                        <td>{{ $rowIndex }}</td>
                        <td><span class="badge badge-dark">{{ $invoiceName ?: '-' }}</span></td>
                        <td><strong>{{ $catName ?: '-' }}</strong></td>
                        <td>{{ $typeName ?: '-' }}</td>
                        <td>{{ $brandName ?: '-' }}</td>
                        <td>{{ $modelName ?: '-' }}</td>
                        <td class="text-center"><span class="badge badge-primary badge-pill">{{ $total }}</span></td>
                        <td class="text-center"><span class="badge badge-success badge-pill">{{ $stock }}</span></td>
                        <td class="text-center"><span class="badge badge-warning badge-pill">{{ $out }}</span></td>
                        <td class="text-center"><span class="badge badge-danger badge-pill">{{ $repair }}</span></td>
                        <td>{{ $avgPrice ? '৳' . number_format($avgPrice, 2) : '-' }}</td>
                        <td><strong>{{ $avgPrice ? '৳' . number_format($items->sum(fn($i) => ($i->purchase_price ?? 0) * ($i->quantity ?? 1)), 2) : '-' }}</strong></td>
                        <td class="text-nowrap">
                            <button class="btn btn-info btn-xs" title="View Serials"><i class="fas fa-chevron-down"></i></button>
                            <form action="{{ route('admin.inventory.instruments.destroy-group') }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete all {{ $total }} items in this group? This cannot be undone!')">
                                @csrf
                                @method('DELETE')
                                @foreach($items as $item)
                                    <input type="hidden" name="ids[]" value="{{ $item->id }}">
                                @endforeach
                                <button type="submit" class="btn btn-danger btn-xs" title="Delete Group"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    <tr class="collapse serial-detail-row" id="serials_{{ $rowIndex }}">
                        <td colspan="13" class="p-0">
                            <div class="p-2 bg-light">
                                <table class="table table-sm table-bordered mb-0">
                                    <thead class="bg-secondary text-white">
                                        <tr>
                                            <th style="width:30px;">#</th>
                                            <th>Serial / MAC</th>
                                            <th>Invoice No.</th>
                                            <th>Condition</th>
                                            <th>Status</th>
                                            <th>Assigned To</th>
                                            <th>Purchase Date</th>
                                            <th style="width:80px;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($items as $si => $instrument)
                                        <tr>
                                            <td>{{ $si + 1 }}</td>
                                            <td><code>{{ $instrument->serial_number ?? '-' }}</code></td>
                                            <td>{{ $instrument->invoice_number ?? '-' }}</td>
                                            <td>
                                                @if($instrument->condition === 'new')
                                                    <span class="badge badge-success">Brand New</span>
                                                @elseif($instrument->condition === 'used')
                                                    <span class="badge badge-warning">Used</span>
                                                @elseif($instrument->condition === 'replaced' || $instrument->condition === 'replace')
                                                    <span class="badge badge-secondary">Replaced</span>
                                                @elseif($instrument->condition === 'damaged' || $instrument->condition === 'dead')
                                                    <span class="badge badge-danger">Damage/Dead</span>
                                                @else
                                                    <span class="badge badge-info">{{ ucfirst($instrument->condition) }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($instrument->status === 'available')
                                                    <span class="badge badge-success">Stock</span>
                                                @elseif($instrument->status === 'in_use')
                                                    <span class="badge badge-warning">Out</span>
                                                @elseif($instrument->status === 'under_repair')
                                                    <span class="badge badge-info">Repair</span>
                                                @else
                                                    <span class="badge badge-danger">Retired</span>
                                                @endif
                                            </td>
                                            <td>{{ $instrument->assigned_to ?? '-' }}</td>
                                            <td>{{ $instrument->purchase_date ? $instrument->purchase_date->format('d-m-Y') : '-' }}</td>
                                            <td>
                                                <a href="{{ route('admin.inventory.instruments.edit', $instrument->id) }}" class="btn btn-warning btn-xs" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form action="{{ route('admin.inventory.instruments.destroy', $instrument->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this serial?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger btn-xs" title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </td>
                    </tr>
                @empty
                <tr>
                    <td colspan="13" class="text-center text-muted">No products found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Add Product Modal - Multi Product per Invoice --}}
<div class="modal fade" id="addInstrumentModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-xl" role="document">
        <form method="POST" action="{{ route('admin.inventory.instruments.store') }}" id="addProductForm">
            @csrf
            <div class="modal-content">
                <div class="modal-header bg-primary">
                    <h5 class="modal-title text-white"><i class="fas fa-plus-circle mr-1"></i> Add Products</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-3 form-group mb-0">
                            <label><i class="fas fa-file-invoice mr-1"></i> Invoice Number <span class="text-danger">*</span></label>
                            <input type="text" name="invoice_number" class="form-control form-control-sm" placeholder="e.g. INV-2026-001" required>
                        </div>
                        <div class="col-md-3 form-group mb-0">
                            <label><i class="fas fa-calendar mr-1"></i> Purchase Date</label>
                            <input type="date" name="purchase_date" class="form-control form-control-sm" value="{{ date('Y-m-d') }}">
                        </div>
                        <div class="col-md-3 form-group mb-0">
                            <label><i class="fas fa-truck mr-1"></i> Vendor</label>
                            <select name="vendor_id" class="form-control form-control-sm">
                                <option value="">-- Select Vendor --</option>
                                @foreach($vendors as $vendor)
                                    <option value="{{ $vendor->id }}">{{ $vendor->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 form-group mb-0">
                            <label><i class="fas fa-user mr-1"></i> Purchaser</label>
                            <select name="purchaser" class="form-control form-control-sm">
                                <option value="">-- Select --</option>
                                @foreach($employees as $emp)
                                    <option value="{{ $emp->name }}">{{ $emp->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <hr class="mt-0">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <strong><i class="fas fa-boxes mr-1"></i> Products</strong>
                        <button type="button" class="btn btn-sm btn-success" onclick="addProductEntry()">
                            <i class="fas fa-plus mr-1"></i> Add Another Product
                        </button>
                    </div>

                    <div id="productEntriesContainer">
                        <div class="card card-outline card-info mb-2 product-entry" data-index="0">
                            <div class="card-header py-2 d-flex justify-content-between align-items-center">
                                <span class="font-weight-bold text-sm"><i class="fas fa-box mr-1"></i> Product #1</span>
                                <button type="button" class="btn btn-xs btn-danger remove-product-btn" onclick="removeProductEntry(this)" style="display:none;" title="Remove">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            <div class="card-body py-2">
                                <div class="row">
                                    <div class="col-md-3 form-group mb-2">
                                        <label class="text-xs">Category <span class="text-danger">*</span></label>
                                        <select name="products[0][category]" class="form-control form-control-sm product-category" data-index="0" required>
                                            <option value="">-- Select --</option>
                                            @foreach($parentCategories as $pcat)
                                                <option value="{{ $pcat->name }}">{{ $pcat->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-2 form-group mb-2 sub-cat-group" data-index="0" style="display:none;">
                                        <label class="text-xs sub-cat-label">Type</label>
                                        <select name="products[0][sub_category]" class="form-control form-control-sm product-sub-category">
                                            <option value="">-- Select --</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2 form-group mb-2">
                                        <label class="text-xs">Brand</label>
                                        <input type="text" name="products[0][brand]" class="form-control form-control-sm" placeholder="Brand">
                                    </div>
                                    <div class="col-md-2 form-group mb-2">
                                        <label class="text-xs">Model</label>
                                        <input type="text" name="products[0][model]" class="form-control form-control-sm" placeholder="Model">
                                    </div>
                                    <div class="col-md-2 form-group mb-2">
                                        <label class="text-xs">Condition</label>
                                        <select name="products[0][condition]" class="form-control form-control-sm">
                                            <option value="new">Brand New</option>
                                            <option value="used">Used</option>
                                            <option value="replaced">Replaced</option>
                                            <option value="damaged">Damage/Dead</option>
                                        </select>
                                    </div>
                                    <div class="col-md-1 form-group mb-2">
                                        <label class="text-xs">Qty <span class="text-danger">*</span></label>
                                        <input type="number" name="products[0][quantity]" class="form-control form-control-sm product-qty" data-index="0" min="1" value="1" required>
                                    </div>
                                    <div class="col-md-2 form-group mb-2">
                                        <label class="text-xs">Per Pc Price (৳)</label>
                                        <input type="number" name="products[0][purchase_price]" class="form-control form-control-sm per-pc-price" step="0.01" min="0" placeholder="0.00">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12 text-right">
                                        <span class="text-xs text-muted product-total-display" style="font-size:13px;">Total: ৳<strong class="product-total-amount">0.00</strong></span>
                                    </div>
                                </div>
                                <div class="serial-section" data-index="0">
                                    <div class="row serial-fields">
                                        <div class="col-md-4 mb-1">
                                            <div class="input-group input-group-sm">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text"><i class="fas fa-barcode"></i></span>
                                                </div>
                                                <input type="text" name="products[0][serials][]" class="form-control" placeholder="Serial/MAC #1">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times mr-1"></i> Close</button>
                    <button type="submit" class="btn btn-primary" id="submitBtn"><i class="fas fa-save mr-1"></i> Save All Products</button>
                </div>
            </div>
        </form>
    </div>
</div>

@endsection

@push('styles')
<style>
    .product-group-row:hover { background-color: #f0f4ff !important; }
    .serial-detail-row td { background-color: #f8f9fa; }
    .product-entry { transition: all 0.3s ease; }
    .product-entry .card-header { background-color: #f0f7ff; }
    .text-xs { font-size: 12px; font-weight: 600; }
</style>
@endpush

@push('scripts')
<script>
var subCatData = @json($parentCategories->mapWithKeys(function($p) { return [$p->name => $p->children->map(function($c) { return ['id' => $c->id, 'name' => $c->name]; })]; }));
var catSerialData = @json($parentCategories->mapWithKeys(function($p) { return [$p->name => $p->has_serial]; }));
var categoryOptions = '';
@foreach($parentCategories as $pcat)
    categoryOptions += '<option value="{{ $pcat->name }}">{{ $pcat->name }}</option>';
@endforeach

var productIndex = 0;

function addProductEntry() {
    productIndex++;
    var idx = productIndex;
    var html = '';
    html += '<div class="card card-outline card-info mb-2 product-entry" data-index="' + idx + '">';
    html += '  <div class="card-header py-2 d-flex justify-content-between align-items-center">';
    html += '    <span class="font-weight-bold text-sm"><i class="fas fa-box mr-1"></i> Product #' + (idx + 1) + '</span>';
    html += '    <button type="button" class="btn btn-xs btn-danger remove-product-btn" onclick="removeProductEntry(this)" title="Remove"><i class="fas fa-times"></i></button>';
    html += '  </div>';
    html += '  <div class="card-body py-2">';
    html += '    <div class="row">';
    html += '      <div class="col-md-3 form-group mb-2">';
    html += '        <label class="text-xs">Category <span class="text-danger">*</span></label>';
    html += '        <select name="products[' + idx + '][category]" class="form-control form-control-sm product-category" data-index="' + idx + '" required>';
    html += '          <option value="">-- Select --</option>' + categoryOptions;
    html += '        </select>';
    html += '      </div>';
    html += '      <div class="col-md-2 form-group mb-2 sub-cat-group" data-index="' + idx + '" style="display:none;">';
    html += '        <label class="text-xs sub-cat-label">Type</label>';
    html += '        <select name="products[' + idx + '][sub_category]" class="form-control form-control-sm product-sub-category">';
    html += '          <option value="">-- Select --</option>';
    html += '        </select>';
    html += '      </div>';
    html += '      <div class="col-md-2 form-group mb-2">';
    html += '        <label class="text-xs">Brand</label>';
    html += '        <input type="text" name="products[' + idx + '][brand]" class="form-control form-control-sm" placeholder="Brand">';
    html += '      </div>';
    html += '      <div class="col-md-2 form-group mb-2">';
    html += '        <label class="text-xs">Model</label>';
    html += '        <input type="text" name="products[' + idx + '][model]" class="form-control form-control-sm" placeholder="Model">';
    html += '      </div>';
    html += '      <div class="col-md-2 form-group mb-2">';
    html += '        <label class="text-xs">Condition</label>';
    html += '        <select name="products[' + idx + '][condition]" class="form-control form-control-sm">';
    html += '          <option value="new">Brand New</option>';
    html += '          <option value="used">Used</option>';
    html += '          <option value="replaced">Replaced</option>';
    html += '          <option value="damaged">Damage/Dead</option>';
    html += '        </select>';
    html += '      </div>';
    html += '      <div class="col-md-1 form-group mb-2">';
    html += '        <label class="text-xs">Qty <span class="text-danger">*</span></label>';
    html += '        <input type="number" name="products[' + idx + '][quantity]" class="form-control form-control-sm product-qty" data-index="' + idx + '" min="1" value="1" required>';
    html += '      </div>';
    html += '      <div class="col-md-2 form-group mb-2">';
    html += '        <label class="text-xs">Per Pc Price (৳)</label>';
    html += '        <input type="number" name="products[' + idx + '][purchase_price]" class="form-control form-control-sm per-pc-price" step="0.01" min="0" placeholder="0.00">';
    html += '      </div>';
    html += '    </div>';
    html += '    <div class="row"><div class="col-md-12 text-right"><span class="text-xs text-muted product-total-display" style="font-size:13px;">Total: ৳<strong class="product-total-amount">0.00</strong></span></div></div>';
    html += '    <div class="serial-section" data-index="' + idx + '">';
    html += '      <div class="row serial-fields">';
    html += '        <div class="col-md-4 mb-1">';
    html += '          <div class="input-group input-group-sm">';
    html += '            <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-barcode"></i></span></div>';
    html += '            <input type="text" name="products[' + idx + '][serials][]" class="form-control" placeholder="Serial/MAC #1">';
    html += '          </div>';
    html += '        </div>';
    html += '      </div>';
    html += '    </div>';
    html += '  </div>';
    html += '</div>';

    $('#productEntriesContainer').append(html);
    updateRemoveButtons();
}

function removeProductEntry(btn) {
    $(btn).closest('.product-entry').fadeOut(200, function() {
        $(this).remove();
        renumberProducts();
        updateRemoveButtons();
    });
}

function renumberProducts() {
    $('#productEntriesContainer .product-entry').each(function(i) {
        $(this).find('.card-header span:first').html('<i class="fas fa-box mr-1"></i> Product #' + (i + 1));
    });
}

function updateRemoveButtons() {
    var entries = $('#productEntriesContainer .product-entry');
    if (entries.length <= 1) {
        entries.find('.remove-product-btn').hide();
    } else {
        entries.find('.remove-product-btn').show();
    }
}

$(document).on('change', '.product-category', function() {
    var idx = $(this).data('index');
    var catName = $(this).val();
    var $entry = $(this).closest('.product-entry');
    var $subGroup = $entry.find('.sub-cat-group');
    var $subSelect = $entry.find('.product-sub-category');
    var $subLabel = $entry.find('.sub-cat-label');
    var $serialSection = $entry.find('.serial-section');

    $subSelect.html('<option value="">-- Select --</option>');

    if (catName && catSerialData.hasOwnProperty(catName) && !catSerialData[catName]) {
        $serialSection.hide();
        $serialSection.find('input').val('');
    } else {
        $serialSection.show();
    }

    if (!catName || !subCatData[catName] || subCatData[catName].length === 0) {
        $subGroup.hide();
        return;
    }

    $subLabel.text(catName + ' Type');
    subCatData[catName].forEach(function(sc) {
        $subSelect.append('<option value="' + sc.name + '">' + sc.name + '</option>');
    });
    $subGroup.show();
});

$(document).on('input', '.product-qty, .per-pc-price', function() {
    var $entry = $(this).closest('.product-entry');
    var qty = parseInt($entry.find('.product-qty').val()) || 0;
    var price = parseFloat($entry.find('.per-pc-price').val()) || 0;
    var total = (qty * price).toFixed(2);
    $entry.find('.product-total-amount').text(total);
});

$(document).on('input blur', 'input[name$="[serials][]"]', function() {
    checkDuplicateSerials();
});

function checkDuplicateSerials() {
    var serialMap = {};
    var $fields = $('input[name$="[serials][]"]');

    $fields.each(function() {
        $(this).removeClass('is-invalid is-valid');
        $(this).closest('.col-md-4').find('.serial-status-badge').remove();
    });

    $fields.each(function() {
        var val = $(this).val().trim().toUpperCase();
        if (val === '') return;
        if (!serialMap[val]) {
            serialMap[val] = [];
        }
        serialMap[val].push($(this));
    });

    var hasDuplicates = false;

    $fields.each(function() {
        var val = $(this).val().trim().toUpperCase();
        if (val === '') return;

        var $container = $(this).closest('.col-md-4');

        if (serialMap[val] && serialMap[val].length > 1) {
            $(this).addClass('is-invalid');
            if ($container.find('.serial-status-badge').length === 0) {
                $(this).closest('.input-group').after('<small class="text-danger serial-status-badge"><i class="fas fa-exclamation-triangle"></i> Duplicate serial!</small>');
            }
            hasDuplicates = true;
        } else {
            $(this).addClass('is-valid');
            if ($container.find('.serial-status-badge').length === 0) {
                $(this).closest('.input-group').after('<small class="text-success serial-status-badge"><i class="fas fa-check-circle"></i> Valid</small>');
            }
        }
    });

    return !hasDuplicates;
}

$('#addInstrumentForm').on('submit', function(e) {
    if (!checkDuplicateSerials()) {
        e.preventDefault();
        alert('Duplicate serial numbers detected! Please fix them before submitting.');
        return false;
    }
});

$(document).on('input', '.product-qty', function() {
    var qty = parseInt($(this).val()) || 1;
    var $entry = $(this).closest('.product-entry');
    var $section = $entry.find('.serial-section');
    var idx = $entry.data('index');

    var catName = $entry.find('.product-category').val();
    if (catName && catSerialData.hasOwnProperty(catName) && !catSerialData[catName]) {
        $section.hide().html('');
        return;
    }

    var html = '<div class="row serial-fields">';
    for (var i = 0; i < qty; i++) {
        html += '<div class="col-md-4 mb-1">';
        html += '  <div class="input-group input-group-sm">';
        html += '    <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-barcode"></i></span></div>';
        html += '    <input type="text" name="products[' + idx + '][serials][]" class="form-control" placeholder="Serial/MAC #' + (i + 1) + '">';
        html += '  </div>';
        html += '</div>';
    }
    html += '</div>';
    $section.html(html);
    $section.show();
});

var filterData = [];
$(document).ready(function() {
    $('.product-group-row').each(function() {
        filterData.push({
            category: ($(this).data('category') || '').toString(),
            type: ($(this).data('type') || '').toString(),
            brand: ($(this).data('brand') || '').toString(),
            model: ($(this).data('model') || '').toString(),
            invoice: ($(this).data('invoice') || '').toString()
        });
    });
    populateFilter('filterCategory', 'category', []);
    populateFilter('filterType', 'type', []);
    populateFilter('filterBrand', 'brand', []);
    populateFilter('filterModel', 'model', []);
    populateFilter('filterInvoice', 'invoice', []);
});

function getFilteredData() {
    var fCat = $('#filterCategory').val();
    var fType = $('#filterType').val();
    var fBrand = $('#filterBrand').val();
    var fModel = $('#filterModel').val();
    var fInvoice = $('#filterInvoice').val();
    return filterData.filter(function(d) {
        if (fCat && d.category !== fCat) return false;
        if (fType && d.type !== fType) return false;
        if (fBrand && d.brand !== fBrand) return false;
        if (fModel && d.model !== fModel) return false;
        if (fInvoice && d.invoice !== fInvoice) return false;
        return true;
    });
}

function populateFilter(selectId, field, data) {
    var $sel = $('#' + selectId);
    var current = $sel.val();
    var labels = { filterCategory: 'All Categories', filterType: 'All Types', filterBrand: 'All Brands', filterModel: 'All Models', filterInvoice: 'All Invoices' };
    $sel.html('<option value="">' + labels[selectId] + '</option>');
    var vals = [];
    var source = data.length > 0 ? data : filterData;
    source.forEach(function(d) {
        var v = d[field];
        if (v && vals.indexOf(v) === -1) vals.push(v);
    });
    vals.sort();
    vals.forEach(function(v) {
        $sel.append('<option value="' + v + '">' + v + '</option>');
    });
    if (current && vals.indexOf(current) !== -1) {
        $sel.val(current);
    }
}

function refreshDependentFilters() {
    var matched = getFilteredData();
    var fCat = $('#filterCategory').val();
    var catData = fCat ? filterData.filter(function(d) { return d.category === fCat; }) : filterData;
    populateFilter('filterType', 'type', catData);
    populateFilter('filterBrand', 'brand', matched);
    populateFilter('filterModel', 'model', matched);
    populateFilter('filterInvoice', 'invoice', matched);
}

function onCategoryFilter() {
    $('#filterType, #filterBrand, #filterModel, #filterInvoice').val('');
    refreshDependentFilters();
    applyFilters();
}

function onDependentFilter() {
    refreshDependentFilters();
    applyFilters();
}

function applyFilters() {
    var fCat = $('#filterCategory').val();
    var fType = $('#filterType').val();
    var fBrand = $('#filterBrand').val();
    var fModel = $('#filterModel').val();
    var fInvoice = $('#filterInvoice').val();

    var visibleCount = 0;
    $('.product-group-row').each(function() {
        var row = $(this);
        var detailRow = row.next('.serial-detail-row');
        var cat = (row.data('category') || '').toString();
        var type = (row.data('type') || '').toString();
        var brand = (row.data('brand') || '').toString();
        var model = (row.data('model') || '').toString();
        var invoice = (row.data('invoice') || '').toString();

        var show = true;
        if (fCat && cat !== fCat) show = false;
        if (fType && type !== fType) show = false;
        if (fBrand && brand !== fBrand) show = false;
        if (fModel && model !== fModel) show = false;
        if (fInvoice && invoice !== fInvoice) show = false;

        if (show) {
            row.show();
            visibleCount++;
        } else {
            row.hide();
            detailRow.hide().removeClass('show');
        }
    });

    $('#noFilterResult').remove();
    if (visibleCount === 0) {
        $('#instrumentTable tbody').append('<tr id="noFilterResult"><td colspan="13" class="text-center text-muted py-3"><i class="fas fa-search mr-1"></i> No products match the selected filters.</td></tr>');
    }
}

function clearFilters() {
    $('#filterCategory, #filterType, #filterBrand, #filterModel, #filterInvoice').val('');
    refreshDependentFilters();
    $('.product-group-row').show();
    $('#noFilterResult').remove();
}

var searchTimeout = null;
$('#quickSearch').on('input', function() {
    var query = $(this).val().trim().toLowerCase();

    if (searchTimeout) clearTimeout(searchTimeout);

    if (query.length > 0) {
        $('#quickSearchClear').show();
    } else {
        $('#quickSearchClear').hide();
        $('#quickSearchCount').hide();
        clearFilters();
        return;
    }

    searchTimeout = setTimeout(function() {
        $('#filterCategory, #filterType, #filterBrand, #filterModel, #filterInvoice').val('');
        $('#noFilterResult').remove();

        var visibleCount = 0;
        var totalRows = 0;

        $('.product-group-row').each(function() {
            totalRows++;
            var row = $(this);
            var detailRow = row.next('.serial-detail-row');
            var cat = (row.data('category') || '').toString().toLowerCase();
            var type = (row.data('type') || '').toString().toLowerCase();
            var brand = (row.data('brand') || '').toString().toLowerCase();
            var model = (row.data('model') || '').toString().toLowerCase();
            var invoice = (row.data('invoice') || '').toString().toLowerCase();

            var serialTexts = '';
            detailRow.find('code').each(function() {
                serialTexts += ' ' + $(this).text().toLowerCase();
            });

            var rowText = cat + ' ' + type + ' ' + brand + ' ' + model + ' ' + invoice + serialTexts;
            var match = rowText.indexOf(query) !== -1;

            if (match) {
                row.show();
                visibleCount++;
            } else {
                row.hide();
                detailRow.hide().removeClass('show');
            }
        });

        var $count = $('#quickSearchCount');
        if (query.length > 0) {
            $count.html('<i class="fas fa-info-circle mr-1"></i>' + visibleCount + ' of ' + totalRows + ' products found').show();
        }

        if (visibleCount === 0) {
            $('#instrumentTable tbody').append('<tr id="noFilterResult"><td colspan="13" class="text-center text-muted py-3"><i class="fas fa-search mr-1"></i> No products match "' + $('<span>').text(query).html() + '"</td></tr>');
        }
    }, 200);
});

function clearQuickSearch() {
    $('#quickSearch').val('').trigger('input');
    $('#quickSearchClear').hide();
    $('#quickSearchCount').hide();
}
</script>
@endpush
