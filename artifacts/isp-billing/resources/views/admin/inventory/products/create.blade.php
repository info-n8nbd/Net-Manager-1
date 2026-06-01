@extends('layouts.admin')

@section('title', 'Add Product - Inventory')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.inventory.products.index') }}">Products</a></li>
<li class="breadcrumb-item active">Add Product</li>
@endsection

@section('content')
<form method="POST" action="{{ route('admin.inventory.products.store') }}" id="productForm">
    @csrf

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row">
        <div class="col-md-8">
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-info-circle mr-2"></i>Product Information</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="name">Product Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required placeholder="Enter product name">
                                @error('name')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="code">Product Code <span class="text-danger">*</span></label>
                                <input type="text" name="code" id="code" class="form-control @error('code') is-invalid @enderror" value="{{ old('code') }}" required placeholder="e.g. PRD-001">
                                @error('code')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="category_id">Category <span class="text-danger">*</span></label>
                                <select name="category_id" id="category_id" class="form-control @error('category_id') is-invalid @enderror" required>
                                    <option value="">-- Select Category --</option>
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                                    @endforeach
                                </select>
                                @error('category_id')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="type">Type <span class="text-danger">*</span></label>
                                <select name="type" id="type" class="form-control @error('type') is-invalid @enderror" required>
                                    <option value="">-- Select Type --</option>
                                    <option value="ONU" {{ old('type') == 'ONU' ? 'selected' : '' }}>ONU</option>
                                    <option value="Router" {{ old('type') == 'Router' ? 'selected' : '' }}>Router</option>
                                    <option value="Switch" {{ old('type') == 'Switch' ? 'selected' : '' }}>Switch</option>
                                    <option value="Media Converter" {{ old('type') == 'Media Converter' ? 'selected' : '' }}>Media Converter</option>
                                    <option value="Instrument" {{ old('type') == 'Instrument' ? 'selected' : '' }}>Instrument</option>
                                    <option value="Fiber" {{ old('type') == 'Fiber' ? 'selected' : '' }}>Fiber</option>
                                </select>
                                @error('type')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="brand">Brand</label>
                                <input type="text" name="brand" id="brand" class="form-control @error('brand') is-invalid @enderror" value="{{ old('brand') }}" placeholder="e.g. TP-Link">
                                @error('brand')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="model">Model</label>
                                <input type="text" name="model" id="model" class="form-control @error('model') is-invalid @enderror" value="{{ old('model') }}" placeholder="e.g. TL-WR840N">
                                @error('model')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea name="description" id="description" class="form-control @error('description') is-invalid @enderror" rows="3" placeholder="Enter product description">{{ old('description') }}</textarea>
                        @error('description')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="card card-warning card-outline" id="serialCard" style="display:none;">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-barcode mr-2"></i>Serial / MAC Addresses</h3>
                    <div class="card-tools">
                        <span class="badge badge-info" id="serialCountBadge">0 serials</span>
                        <span class="badge badge-success ml-1" id="serialValidBadge" style="display:none;">0 valid</span>
                        <span class="badge badge-danger ml-1" id="serialInvalidBadge" style="display:none;">0 invalid</span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="form-group">
                            <label for="initial_quantity"><i class="fas fa-sort-numeric-up mr-1"></i>Quantity (Number of Items) <span class="text-danger">*</span></label>
                            <div class="input-group" style="max-width:250px;">
                                <input type="number" id="initial_quantity" class="form-control" min="1" max="500" value="" placeholder="Enter quantity">
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-primary" id="generateSerialsBtn">
                                        <i class="fas fa-cogs mr-1"></i>Generate
                                    </button>
                                </div>
                            </div>
                            <small class="text-muted">Enter the number of items, then click Generate to create serial input fields</small>
                        </div>
                    </div>
                    <div id="serialInputsContainer"></div>
                    <div id="serialSummary" style="display:none;" class="mt-3">
                        <div class="alert alert-light border py-2 px-3 mb-0">
                            <i class="fas fa-info-circle text-info mr-1"></i>
                            <strong id="totalSerialsText">0</strong> serial fields generated.
                            <span class="text-success"><strong id="validSerialsText">0</strong> valid</span>,
                            <span class="text-danger"><strong id="invalidSerialsText">0</strong> invalid</span>,
                            <span class="text-muted"><strong id="emptySerialsText">0</strong> empty</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card card-info card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-tags mr-2"></i>Pricing & Stock</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="unit">Unit <span class="text-danger">*</span></label>
                        <select name="unit" id="unit" class="form-control @error('unit') is-invalid @enderror" required>
                            <option value="pcs" {{ old('unit') == 'pcs' ? 'selected' : '' }}>Pcs</option>
                            <option value="meter" {{ old('unit') == 'meter' ? 'selected' : '' }}>Meter</option>
                        </select>
                        @error('unit')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="purchase_price">Purchase Price</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">৳</span>
                            </div>
                            <input type="number" name="purchase_price" id="purchase_price" class="form-control @error('purchase_price') is-invalid @enderror" value="{{ old('purchase_price', '0') }}" step="0.01" min="0">
                        </div>
                        @error('purchase_price')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="selling_price">Selling Price</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">৳</span>
                            </div>
                            <input type="number" name="selling_price" id="selling_price" class="form-control @error('selling_price') is-invalid @enderror" value="{{ old('selling_price', '0') }}" step="0.01" min="0">
                        </div>
                        @error('selling_price')
                            <span class="invalid-feedback d-block">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group" id="initialStockQtyGroup">
                        <label for="initial_stock_quantity"><i class="fas fa-boxes mr-1"></i>Initial Stock Quantity</label>
                        <input type="number" name="initial_stock_quantity" id="initial_stock_quantity" class="form-control @error('initial_stock_quantity') is-invalid @enderror" value="{{ old('initial_stock_quantity', '0') }}" min="0">
                        @error('initial_stock_quantity')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                        <small class="text-muted">Enter opening stock quantity (will appear in Product In History)</small>
                    </div>

                    <div class="form-group">
                        <label for="alert_quantity">Alert Quantity</label>
                        <input type="number" name="alert_quantity" id="alert_quantity" class="form-control @error('alert_quantity') is-invalid @enderror" value="{{ old('alert_quantity', '5') }}" min="0">
                        @error('alert_quantity')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                        <small class="text-muted">Low stock warning threshold</small>
                    </div>

                    <div class="form-group">
                        <label for="warranty_months">Warranty (Months)</label>
                        <input type="number" name="warranty_months" id="warranty_months" class="form-control @error('warranty_months') is-invalid @enderror" value="{{ old('warranty_months', '0') }}" min="0">
                        @error('warranty_months')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="barcode_prefix">Barcode Prefix</label>
                        <input type="text" name="barcode_prefix" id="barcode_prefix" class="form-control @error('barcode_prefix') is-invalid @enderror" value="{{ old('barcode_prefix') }}" placeholder="e.g. PRD">
                        @error('barcode_prefix')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" name="has_serial" id="has_serial" class="custom-control-input" value="1" {{ old('has_serial') ? 'checked' : '' }}>
                            <label class="custom-control-label" for="has_serial">
                                <i class="fas fa-barcode mr-1"></i> Enable Serial Tracking
                            </label>
                        </div>
                        <small class="text-muted">Track individual items by serial number</small>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <button type="submit" class="btn btn-success btn-block" id="submitBtn">
                        <i class="fas fa-save mr-2"></i>Save Product
                    </button>
                    <a href="{{ route('admin.inventory.products.index') }}" class="btn btn-secondary btn-block">
                        <i class="fas fa-arrow-left mr-2"></i>Back to List
                    </a>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<style>
    .serial-input-row {
        margin-bottom: 8px;
        animation: serialFadeIn 0.3s ease forwards;
    }
    @keyframes serialFadeIn {
        from { opacity: 0; transform: translateY(-8px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .serial-input-row .form-control {
        transition: border-color 0.3s ease, box-shadow 0.3s ease;
    }
    .serial-input-row .form-control.is-valid-serial {
        border-color: #28a745 !important;
        box-shadow: 0 0 0 0.15rem rgba(40, 167, 69, 0.2);
    }
    .serial-input-row .form-control.is-invalid-serial {
        border-color: #dc3545 !important;
        box-shadow: 0 0 0 0.15rem rgba(220, 53, 69, 0.2);
    }
    .serial-status-icon {
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 16px;
        z-index: 5;
        transition: opacity 0.2s ease;
    }
    .serial-status-icon.valid { color: #28a745; }
    .serial-status-icon.invalid { color: #dc3545; }
    .serial-status-icon.checking { color: #ffc107; }
    .serial-error-msg {
        font-size: 11px;
        color: #dc3545;
        margin-top: 2px;
        display: none;
    }
    .serial-input-wrapper {
        position: relative;
    }
    .serial-input-wrapper .form-control {
        padding-right: 38px;
    }
</style>
<script>
$(document).ready(function() {
    var checkUrl = "{{ route('admin.inventory.check-serial-duplicate') }}";
    var csrfToken = "{{ csrf_token() }}";
    var debounceTimers = {};
    var serialCount = 0;

    function toggleSerialCard() {
        var unit = $('#unit').val();
        var hasSerial = $('#has_serial').is(':checked');
        if (unit === 'pcs' && hasSerial) {
            $('#serialCard').slideDown(300);
            $('#initialStockQtyGroup').slideUp(200);
        } else {
            $('#serialCard').slideUp(300);
            $('#initialStockQtyGroup').slideDown(200);
        }
    }

    $('#unit').on('change', toggleSerialCard);
    $('#has_serial').on('change', toggleSerialCard);
    toggleSerialCard();

    $('#generateSerialsBtn').on('click', function() {
        var qty = parseInt($('#initial_quantity').val());
        if (!qty || qty < 1) {
            toastr.warning('Please enter a valid quantity (minimum 1)');
            return;
        }
        if (qty > 500) {
            toastr.warning('Maximum 500 serial inputs allowed');
            return;
        }
        generateSerialInputs(qty);
    });

    function generateSerialInputs(count) {
        var container = $('#serialInputsContainer');
        container.empty();
        serialCount = count;

        var html = '<div class="row">';
        for (var i = 0; i < count; i++) {
            html += '<div class="col-md-6 serial-input-row" data-index="' + i + '">';
            html += '  <div class="form-group mb-2">';
            html += '    <label class="mb-1" style="font-size:12px;font-weight:600;color:#6c757d;">Serial ' + (i + 1) + '</label>';
            html += '    <div class="serial-input-wrapper">';
            html += '      <input type="text" name="serials[]" class="form-control form-control-sm serial-field" data-index="' + i + '" placeholder="Enter Serial/MAC #' + (i + 1) + '" autocomplete="off">';
            html += '      <span class="serial-status-icon" id="serialIcon_' + i + '"></span>';
            html += '    </div>';
            html += '    <div class="serial-error-msg" id="serialError_' + i + '"></div>';
            html += '  </div>';
            html += '</div>';
        }
        html += '</div>';
        container.html(html);

        $('#serialSummary').show();
        updateSerialSummary();

        $('#serialCountBadge').text(count + ' serials');

        if (count > 0) {
            container.find('.serial-field').first().focus();
        }
    }

    $(document).on('keyup blur paste', '.serial-field', function() {
        var field = $(this);
        var index = field.data('index');
        var val = field.val().trim();

        if (debounceTimers[index]) clearTimeout(debounceTimers[index]);

        if (val === '') {
            clearValidation(index);
            updateSerialSummary();
            return;
        }

        setChecking(index);

        debounceTimers[index] = setTimeout(function() {
            validateSerial(field, index, val);
        }, 400);
    });

    function validateSerial(field, index, val) {
        var formDuplicate = false;
        var duplicateIndices = [];
        $('.serial-field').each(function() {
            var otherIndex = $(this).data('index');
            if (otherIndex !== index && $(this).val().trim().toLowerCase() === val.toLowerCase() && val !== '') {
                formDuplicate = true;
                duplicateIndices.push(otherIndex);
            }
        });

        if (formDuplicate) {
            setInvalid(index, 'Duplicate serial in this form');
            duplicateIndices.forEach(function(di) {
                var otherVal = $('.serial-field[data-index="' + di + '"]').val().trim();
                if (otherVal !== '') setInvalid(di, 'Duplicate serial in this form');
            });
            updateSerialSummary();
            return;
        }

        $('.serial-field').each(function() {
            var oi = $(this).data('index');
            if (oi !== index && $(this).hasClass('is-invalid-serial')) {
                var ov = $(this).val().trim();
                if (ov !== '' && $('#serialError_' + oi).text() === 'Duplicate serial in this form') {
                    var stillDup = false;
                    $('.serial-field').each(function() {
                        var xi = $(this).data('index');
                        if (xi !== oi && $(this).val().trim().toLowerCase() === ov.toLowerCase()) {
                            stillDup = true;
                            return false;
                        }
                    });
                    if (!stillDup) {
                        validateSerial($(this), oi, ov);
                    }
                }
            }
        });

        $.ajax({
            url: checkUrl,
            method: 'POST',
            data: { serial: val, _token: csrfToken },
            dataType: 'json',
            success: function(resp) {
                if (field.val().trim() !== val) return;
                if (resp.exists) {
                    setInvalid(index, 'Serial already exists in database');
                } else {
                    setValid(index);
                }
                updateSerialSummary();
            },
            error: function() {
                if (field.val().trim() === val) {
                    setValid(index);
                    updateSerialSummary();
                }
            }
        });
    }

    function setValid(index) {
        var field = $('.serial-field[data-index="' + index + '"]');
        field.removeClass('is-invalid-serial').addClass('is-valid-serial');
        $('#serialIcon_' + index).html('<i class="fas fa-check-circle"></i>').removeClass('invalid checking').addClass('valid');
        $('#serialError_' + index).hide().text('');
    }

    function setInvalid(index, msg) {
        var field = $('.serial-field[data-index="' + index + '"]');
        field.removeClass('is-valid-serial').addClass('is-invalid-serial');
        $('#serialIcon_' + index).html('<i class="fas fa-times-circle"></i>').removeClass('valid checking').addClass('invalid');
        $('#serialError_' + index).text(msg).show();
    }

    function setChecking(index) {
        var field = $('.serial-field[data-index="' + index + '"]');
        field.removeClass('is-valid-serial is-invalid-serial');
        $('#serialIcon_' + index).html('<i class="fas fa-spinner fa-spin"></i>').removeClass('valid invalid').addClass('checking');
        $('#serialError_' + index).hide();
    }

    function clearValidation(index) {
        var field = $('.serial-field[data-index="' + index + '"]');
        field.removeClass('is-valid-serial is-invalid-serial');
        $('#serialIcon_' + index).html('').removeClass('valid invalid checking');
        $('#serialError_' + index).hide().text('');
    }

    function updateSerialSummary() {
        var total = 0, valid = 0, invalid = 0, empty = 0;
        $('.serial-field').each(function() {
            total++;
            var val = $(this).val().trim();
            if (val === '') {
                empty++;
            } else if ($(this).hasClass('is-valid-serial')) {
                valid++;
            } else if ($(this).hasClass('is-invalid-serial')) {
                invalid++;
            }
        });
        $('#totalSerialsText').text(total);
        $('#validSerialsText').text(valid);
        $('#invalidSerialsText').text(invalid);
        $('#emptySerialsText').text(empty);

        if (valid > 0) {
            $('#serialValidBadge').text(valid + ' valid').show();
        } else {
            $('#serialValidBadge').hide();
        }
        if (invalid > 0) {
            $('#serialInvalidBadge').text(invalid + ' invalid').show();
        } else {
            $('#serialInvalidBadge').hide();
        }
    }

    $('#productForm').on('submit', function(e) {
        var invalidCount = $('.serial-field.is-invalid-serial').length;
        if (invalidCount > 0) {
            e.preventDefault();
            toastr.error(invalidCount + ' serial(s) have validation errors. Please fix them before saving.');
            $('html, body').animate({ scrollTop: $('#serialCard').offset().top - 80 }, 400);
            return false;
        }
    });
});
</script>
@endpush
