@extends('layouts.admin')

@section('title', 'Create Purchase')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="#">Inventory</a></li>
<li class="breadcrumb-item"><a href="{{ route('admin.inventory.purchases.index') }}">Purchases</a></li>
<li class="breadcrumb-item active">Create</li>
@endsection

@section('content')
<form method="POST" action="{{ route('admin.inventory.purchases.store') }}" id="purchaseForm">
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
                    <h3 class="card-title"><i class="fas fa-boxes mr-2"></i>Purchase Items</h3>
                </div>
                <div class="card-body p-0">
                    <table class="table table-bordered mb-0" id="itemsTable">
                        <thead class="bg-light">
                            <tr>
                                <th style="width: 35%">Product</th>
                                <th style="width: 15%">Quantity</th>
                                <th style="width: 20%">Unit Price</th>
                                <th style="width: 20%">Total</th>
                                <th style="width: 10%">Action</th>
                            </tr>
                        </thead>
                        <tbody id="itemRows">
                            <tr class="item-row" data-index="0">
                                <td>
                                    <select name="items[0][product_id]" class="form-control product-select" required>
                                        <option value="">-- Select Product --</option>
                                        @foreach($products as $product)
                                            <option value="{{ $product->id }}">{{ $product->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <input type="number" name="items[0][quantity]" class="form-control item-qty" min="1" value="1" required>
                                </td>
                                <td>
                                    <input type="number" name="items[0][unit_price]" class="form-control item-price" min="0" step="0.01" value="0.00" required>
                                </td>
                                <td>
                                    <input type="number" name="items[0][total]" class="form-control item-total" value="0.00" readonly>
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-danger btn-sm remove-row" title="Remove">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="5">
                                    <button type="button" class="btn btn-success btn-sm" id="addRow">
                                        <i class="fas fa-plus mr-1"></i> Add Item
                                    </button>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card card-info card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-file-invoice mr-2"></i>Purchase Details</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="vendor_id"><i class="fas fa-truck mr-1"></i> Vendor <span class="text-danger">*</span></label>
                        <select name="vendor_id" id="vendor_id" class="form-control @error('vendor_id') is-invalid @enderror" required>
                            <option value="">-- Select Vendor --</option>
                            @foreach($vendors as $vendor)
                                <option value="{{ $vendor->id }}" {{ old('vendor_id') == $vendor->id ? 'selected' : '' }}>
                                    {{ $vendor->name }} {{ $vendor->company ? '(' . $vendor->company . ')' : '' }}
                                </option>
                            @endforeach
                        </select>
                        @error('vendor_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="purchase_date"><i class="fas fa-calendar-alt mr-1"></i> Purchase Date <span class="text-danger">*</span></label>
                        <input type="date" name="purchase_date" id="purchase_date" class="form-control @error('purchase_date') is-invalid @enderror" value="{{ old('purchase_date', date('Y-m-d')) }}" required>
                        @error('purchase_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="notes"><i class="fas fa-sticky-note mr-1"></i> Notes</label>
                        <textarea name="notes" id="notes" class="form-control" rows="3" placeholder="Optional notes...">{{ old('notes') }}</textarea>
                    </div>
                </div>
            </div>

            <div class="card card-success card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-calculator mr-2"></i>Summary</h3>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-borderless">
                        <tr>
                            <td><strong>Subtotal:</strong></td>
                            <td class="text-right" id="subtotalDisplay">0.00</td>
                        </tr>
                        <tr class="border-top">
                            <td><strong class="text-lg">Total:</strong></td>
                            <td class="text-right text-lg"><strong id="totalDisplay">0.00</strong></td>
                        </tr>
                    </table>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-save mr-1"></i> Save Purchase
                    </button>
                    <a href="{{ route('admin.inventory.purchases.index') }}" class="btn btn-secondary btn-block">
                        <i class="fas fa-times mr-1"></i> Cancel
                    </a>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    let rowIndex = 1;

    const productOptions = `<option value="">-- Select Product --</option>
        @foreach($products as $product)
            <option value="{{ $product->id }}">{{ addslashes($product->name) }}</option>
        @endforeach`;

    $('#addRow').on('click', function() {
        const row = `
            <tr class="item-row" data-index="${rowIndex}">
                <td>
                    <select name="items[${rowIndex}][product_id]" class="form-control product-select" required>
                        ${productOptions}
                    </select>
                </td>
                <td>
                    <input type="number" name="items[${rowIndex}][quantity]" class="form-control item-qty" min="1" value="1" required>
                </td>
                <td>
                    <input type="number" name="items[${rowIndex}][unit_price]" class="form-control item-price" min="0" step="0.01" value="0.00" required>
                </td>
                <td>
                    <input type="number" name="items[${rowIndex}][total]" class="form-control item-total" value="0.00" readonly>
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-danger btn-sm remove-row" title="Remove">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>`;
        $('#itemRows').append(row);
        rowIndex++;
    });

    $(document).on('click', '.remove-row', function() {
        if ($('.item-row').length > 1) {
            $(this).closest('tr').remove();
            calculateTotals();
        } else {
            alert('At least one item is required.');
        }
    });

    $(document).on('input', '.item-qty, .item-price', function() {
        const row = $(this).closest('tr');
        const qty = parseFloat(row.find('.item-qty').val()) || 0;
        const price = parseFloat(row.find('.item-price').val()) || 0;
        const total = (qty * price).toFixed(2);
        row.find('.item-total').val(total);
        calculateTotals();
    });

    function calculateTotals() {
        let subtotal = 0;
        $('.item-total').each(function() {
            subtotal += parseFloat($(this).val()) || 0;
        });
        $('#subtotalDisplay').text(subtotal.toFixed(2));
        $('#totalDisplay').text(subtotal.toFixed(2));
    }
});
</script>
@endpush
