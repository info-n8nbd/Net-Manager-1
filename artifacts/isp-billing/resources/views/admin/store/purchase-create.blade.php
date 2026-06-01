@extends('layouts.admin')
@section('title', 'Add Purchase')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.store.purchases') }}">Purchases</a></li>
<li class="breadcrumb-item active">Add Purchase</li>
@endsection

@section('content')
<form action="{{ route('admin.purchase.store') }}" method="POST" id="purchaseForm">
    @csrf

    <div class="row">
        <div class="col-md-8">
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-file-invoice mr-2"></i>Purchase Information</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Vendor <span class="text-danger">*</span></label>
                                <select name="vendor_id" class="form-control" required>
                                    <option value="">-- Select Vendor --</option>
                                    @foreach($vendors as $vendor)
                                    <option value="{{ $vendor->id }}" {{ old('vendor_id') == $vendor->id ? 'selected' : '' }}>{{ $vendor->name }}{{ $vendor->company ? ' ('.$vendor->company.')' : '' }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Purchase Date <span class="text-danger">*</span></label>
                                <input type="date" name="purchase_date" class="form-control" value="{{ old('purchase_date', date('Y-m-d')) }}" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Invoice Number</label>
                                <input type="text" name="invoice_number" class="form-control" value="{{ old('invoice_number') }}" placeholder="Vendor invoice #">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card card-info card-outline">
                <div class="card-header d-flex align-items-center">
                    <h3 class="card-title"><i class="fas fa-boxes mr-2"></i>Purchase Items</h3>
                    <button type="button" class="btn btn-success btn-sm ml-auto" id="addItemBtn">
                        <i class="fas fa-plus mr-1"></i> Add Item
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover mb-0" id="itemsTable">
                            <thead>
                                <tr>
                                    <th style="width: 5%">#</th>
                                    <th style="width: 35%">Product <span class="text-danger">*</span></th>
                                    <th style="width: 15%">Quantity <span class="text-danger">*</span></th>
                                    <th style="width: 20%">Unit Price <span class="text-danger">*</span></th>
                                    <th style="width: 20%">Total</th>
                                    <th style="width: 5%"></th>
                                </tr>
                            </thead>
                            <tbody id="itemsBody">
                                <tr class="item-row" data-index="0">
                                    <td class="text-center align-middle row-number">1</td>
                                    <td>
                                        <select name="items[0][product_id]" class="form-control product-select" required>
                                            <option value="">-- Select Product --</option>
                                            @foreach($products as $product)
                                            <option value="{{ $product->id }}" data-price="{{ $product->purchase_price ?? 0 }}">{{ $product->name }}@if($product->brand || $product->model) - {{ $product->brand }}{{ $product->brand && $product->model ? ' / ' : '' }}{{ $product->model }}@endif{{ $product->code ? ' ('.$product->code.')' : '' }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <input type="number" name="items[0][quantity]" class="form-control item-qty" value="1" min="1" step="1" required>
                                    </td>
                                    <td>
                                        <input type="number" name="items[0][unit_price]" class="form-control item-price" value="0" min="0" step="0.01" required>
                                    </td>
                                    <td>
                                        <input type="text" class="form-control item-total" value="0.00" readonly style="background: #f8fafc; font-weight: 600;">
                                    </td>
                                    <td class="text-center align-middle">
                                        <button type="button" class="btn btn-danger btn-sm remove-item" title="Remove"><i class="fas fa-times"></i></button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="p-2 text-center d-md-none">
                        <button type="button" class="btn btn-success btn-sm btn-block" id="addItemBtnMobile">
                            <i class="fas fa-plus mr-1"></i> Add Another Item
                        </button>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-sticky-note mr-2"></i>Notes</h3>
                </div>
                <div class="card-body">
                    <textarea name="notes" class="form-control" rows="3" placeholder="Any additional notes for this purchase...">{{ old('notes') }}</textarea>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card card-warning card-outline" style="position: sticky; top: 70px;">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-calculator mr-2"></i>Order Summary</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="text-muted">Subtotal</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">৳</span>
                            </div>
                            <input type="text" id="subtotal" class="form-control font-weight-bold" value="0.00" readonly style="background: #f8fafc;">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="text-muted">Discount (Amount)</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">৳</span>
                            </div>
                            <input type="number" name="discount" id="discount" class="form-control" value="{{ old('discount', 0) }}" min="0" step="0.01">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="text-muted">Tax (%)</label>
                        <div class="input-group">
                            <input type="number" name="tax_percent" id="taxPercent" class="form-control" value="{{ old('tax_percent', 0) }}" min="0" max="100" step="0.01">
                            <div class="input-group-append">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                        <small class="text-muted">Tax Amount: ৳<span id="taxAmount">0.00</span></small>
                    </div>

                    <hr>

                    <div class="form-group">
                        <label class="font-weight-bold" style="font-size: 16px;">Grand Total</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text bg-primary text-white">৳</span>
                            </div>
                            <input type="text" id="grandTotal" class="form-control font-weight-bold" value="0.00" readonly style="background: #f0f9ff; font-size: 18px; color: #1e3c72;">
                            <input type="hidden" name="total_amount" id="totalAmountHidden" value="0">
                        </div>
                    </div>

                    <hr>

                    <div class="form-group">
                        <label class="text-muted">Paid Amount</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text bg-success text-white">৳</span>
                            </div>
                            <input type="number" name="paid_amount" id="paidAmount" class="form-control" value="{{ old('paid_amount', 0) }}" min="0" step="0.01">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="text-muted">Due Amount</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text bg-danger text-white">৳</span>
                            </div>
                            <input type="text" id="dueAmount" class="form-control font-weight-bold text-danger" value="0.00" readonly style="background: #fef2f2;">
                        </div>
                    </div>

                    <div id="paymentStatus" class="alert alert-info py-2 px-3 mb-0 mt-2" style="font-size: 13px;">
                        <i class="fas fa-info-circle mr-1"></i> <span id="paymentStatusText">No payment</span>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary btn-block btn-lg" id="submitBtn">
                        <i class="fas fa-save mr-2"></i> Save Purchase
                    </button>
                    <a href="{{ route('admin.store.purchases') }}" class="btn btn-default btn-block">
                        <i class="fas fa-arrow-left mr-1"></i> Cancel
                    </a>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@push('styles')
<style>
    .item-row:hover { background: #f0f9ff; }
    .remove-item { opacity: 0.6; transition: opacity 0.2s; }
    .remove-item:hover { opacity: 1; }
    #itemsTable .form-control { padding: 6px 10px; font-size: 13px; }
    #itemsTable select.form-control { padding: 6px 8px; }
    .product-select { min-width: 180px; }

    @media (max-width: 767px) {
        #itemsTable thead { display: none; }
        #itemsTable .item-row {
            display: block;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            margin-bottom: 10px;
            padding: 10px;
            position: relative;
        }
        #itemsTable .item-row td {
            display: block;
            border: none;
            padding: 4px 0;
        }
        #itemsTable .item-row td:before {
            content: attr(data-label);
            font-weight: 600;
            font-size: 11px;
            color: #64748b;
            display: block;
            margin-bottom: 2px;
        }
        #itemsTable .item-row td:last-child {
            position: absolute;
            top: 8px;
            right: 8px;
        }
        .row-number { display: none !important; }
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    let itemIndex = 0;
    @php
        $productsArray = $products->map(function($p) {
            return ['id' => $p->id, 'name' => $p->name, 'code' => $p->code ?? '', 'brand' => $p->brand ?? '', 'model' => $p->model ?? '', 'purchase_price' => $p->purchase_price ?? 0];
        })->values();
    @endphp
    const productsData = @json($productsArray);

    function buildProductOptions(selectedId) {
        let html = '<option value="">-- Select Product --</option>';
        productsData.forEach(function(p) {
            let sel = (selectedId == p.id) ? 'selected' : '';
            let brandModel = [p.brand, p.model].filter(Boolean).join(' / ');
            html += '<option value="' + p.id + '" data-price="' + p.purchase_price + '" ' + sel + '>' + p.name + (brandModel ? ' - ' + brandModel : '') + (p.code ? ' (' + p.code + ')' : '') + '</option>';
        });
        return html;
    }

    function addItemRow() {
        itemIndex++;
        let row = document.createElement('tr');
        row.className = 'item-row';
        row.dataset.index = itemIndex;
        row.innerHTML = `
            <td class="text-center align-middle row-number" data-label="">${itemIndex + 1}</td>
            <td data-label="Product">
                <select name="items[${itemIndex}][product_id]" class="form-control product-select" required>
                    ${buildProductOptions(null)}
                </select>
            </td>
            <td data-label="Quantity">
                <input type="number" name="items[${itemIndex}][quantity]" class="form-control item-qty" value="1" min="1" step="1" required>
            </td>
            <td data-label="Unit Price">
                <input type="number" name="items[${itemIndex}][unit_price]" class="form-control item-price" value="0" min="0" step="0.01" required>
            </td>
            <td data-label="Total">
                <input type="text" class="form-control item-total" value="0.00" readonly style="background: #f8fafc; font-weight: 600;">
            </td>
            <td class="text-center align-middle">
                <button type="button" class="btn btn-danger btn-sm remove-item" title="Remove"><i class="fas fa-times"></i></button>
            </td>
        `;
        document.getElementById('itemsBody').appendChild(row);
        bindRowEvents(row);
        updateRowNumbers();
        row.querySelector('.product-select').focus();
    }

    function bindRowEvents(row) {
        row.querySelector('.product-select').addEventListener('change', function() {
            let opt = this.options[this.selectedIndex];
            let price = parseFloat(opt.dataset.price) || 0;
            row.querySelector('.item-price').value = price.toFixed(2);
            calcRowTotal(row);
        });

        row.querySelector('.item-qty').addEventListener('input', function() {
            calcRowTotal(row);
        });

        row.querySelector('.item-price').addEventListener('input', function() {
            calcRowTotal(row);
        });

        row.querySelector('.remove-item').addEventListener('click', function() {
            let rows = document.querySelectorAll('.item-row');
            if (rows.length <= 1) {
                alert('At least one item is required.');
                return;
            }
            row.remove();
            updateRowNumbers();
            calcTotals();
        });
    }

    function calcRowTotal(row) {
        let qty = parseFloat(row.querySelector('.item-qty').value) || 0;
        let price = parseFloat(row.querySelector('.item-price').value) || 0;
        let total = qty * price;
        row.querySelector('.item-total').value = total.toFixed(2);
        calcTotals();
    }

    function calcTotals() {
        let subtotal = 0;
        document.querySelectorAll('.item-total').forEach(function(el) {
            subtotal += parseFloat(el.value) || 0;
        });

        let discount = parseFloat(document.getElementById('discount').value) || 0;
        let taxPercent = parseFloat(document.getElementById('taxPercent').value) || 0;
        let afterDiscount = subtotal - discount;
        if (afterDiscount < 0) afterDiscount = 0;
        let taxAmount = (afterDiscount * taxPercent) / 100;
        let grandTotal = afterDiscount + taxAmount;
        let paidAmount = parseFloat(document.getElementById('paidAmount').value) || 0;
        let dueAmount = grandTotal - paidAmount;
        if (dueAmount < 0) dueAmount = 0;

        document.getElementById('subtotal').value = subtotal.toFixed(2);
        document.getElementById('taxAmount').textContent = taxAmount.toFixed(2);
        document.getElementById('grandTotal').value = grandTotal.toFixed(2);
        document.getElementById('totalAmountHidden').value = grandTotal.toFixed(2);
        document.getElementById('dueAmount').value = dueAmount.toFixed(2);

        let statusEl = document.getElementById('paymentStatus');
        let statusText = document.getElementById('paymentStatusText');
        if (grandTotal <= 0) {
            statusEl.className = 'alert alert-info py-2 px-3 mb-0 mt-2';
            statusText.textContent = 'No items added';
        } else if (paidAmount >= grandTotal) {
            statusEl.className = 'alert alert-success py-2 px-3 mb-0 mt-2';
            statusText.textContent = 'Fully Paid';
        } else if (paidAmount > 0) {
            statusEl.className = 'alert alert-warning py-2 px-3 mb-0 mt-2';
            statusText.textContent = 'Partially Paid';
        } else {
            statusEl.className = 'alert alert-danger py-2 px-3 mb-0 mt-2';
            statusText.textContent = 'Unpaid';
        }
    }

    function updateRowNumbers() {
        document.querySelectorAll('.row-number').forEach(function(el, i) {
            el.textContent = i + 1;
        });
    }

    document.getElementById('addItemBtn').addEventListener('click', addItemRow);
    document.getElementById('addItemBtnMobile').addEventListener('click', addItemRow);

    document.getElementById('discount').addEventListener('input', calcTotals);
    document.getElementById('taxPercent').addEventListener('input', calcTotals);
    document.getElementById('paidAmount').addEventListener('input', calcTotals);

    document.querySelectorAll('.item-row').forEach(function(row) {
        bindRowEvents(row);
    });

    document.getElementById('purchaseForm').addEventListener('submit', function(e) {
        let rows = document.querySelectorAll('.item-row');
        let valid = true;
        rows.forEach(function(row) {
            let product = row.querySelector('.product-select').value;
            let qty = parseFloat(row.querySelector('.item-qty').value) || 0;
            let price = parseFloat(row.querySelector('.item-price').value) || 0;
            if (!product || qty <= 0 || price <= 0) {
                valid = false;
            }
        });
        if (!valid) {
            e.preventDefault();
            alert('Please fill in all item fields. Each item must have a product, quantity > 0, and unit price > 0.');
            return false;
        }

        let vendorSelect = document.querySelector('select[name="vendor_id"]');
        if (!vendorSelect.value) {
            e.preventDefault();
            alert('Please select a vendor.');
            vendorSelect.focus();
            return false;
        }

        document.getElementById('submitBtn').disabled = true;
        document.getElementById('submitBtn').innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Saving...';
    });

    calcTotals();
});
</script>
@endpush
