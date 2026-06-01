@extends('layouts.admin')
@section('title', 'New Sale')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.sales.index') }}">Sales</a></li>
<li class="breadcrumb-item active">New Sale</li>
@endsection

@section('content')
<form action="{{ route('admin.sales.store') }}" method="POST" id="saleForm">
    @csrf

    <div class="row">
        <div class="col-lg-8">
            <div class="card card-outline card-primary" style="overflow: visible;">
                <div class="card-header"><h3 class="card-title"><i class="fas fa-user mr-2"></i>Customer Information</h3></div>
                <div class="card-body" style="overflow: visible;">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Customer Type <span class="text-danger">*</span></label>
                                <select name="customer_type" id="customerType" class="form-control" required>
                                    <option value="client">Existing Client</option>
                                    <option value="walk_in">Walk-in Customer</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Sale Date <span class="text-danger">*</span></label>
                                <input type="date" name="sale_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Payment Method</label>
                                <select name="payment_method" id="salePaymentMethod" class="form-control">
                                    <option value="cash">Cash</option>
                                    <option value="bank">Bank Transfer</option>
                                    <option value="bkash">bKash</option>
                                    <option value="nagad">Nagad</option>
                                    <option value="rocket">Rocket</option>
                                    <option value="card">Card</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3" id="paymentRefGroup">
                            <div class="form-group">
                                <label id="paymentRefLabel">Money Receipt No</label>
                                <input type="text" name="payment_reference" id="paymentRefInput" class="form-control" placeholder="Enter receipt no">
                            </div>
                        </div>
                    </div>

                    <div id="clientSection">
                        <div class="row">
                            <div class="col-md-12">
                                @include('admin.partials.client-search', [
                                    'inputId' => 'saleClientSearch',
                                    'hiddenId' => 'saleClientSearchId',
                                    'fieldName' => 'client_id',
                                    'label' => 'Select Client',
                                    'required' => false,
                                ])
                            </div>
                        </div>
                    </div>

                    <div id="walkInSection" style="display:none;">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Customer Name</label>
                                    <input type="text" name="customer_name" class="form-control" placeholder="Customer name">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Customer Phone</label>
                                    <input type="text" name="customer_phone" class="form-control" placeholder="Phone number">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card card-outline card-info">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-boxes mr-2"></i>Sale Items</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-success btn-sm" id="addItemBtn"><i class="fas fa-plus mr-1"></i>Add Item</button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <table class="table table-bordered mb-0" id="itemsTable">
                        <thead class="bg-light">
                            <tr>
                                <th style="width:40px">#</th>
                                <th>Product <span class="text-danger">*</span></th>
                                <th style="width:100px">Available</th>
                                <th style="width:100px">Qty <span class="text-danger">*</span></th>
                                <th style="width:130px">Unit Price</th>
                                <th style="width:130px">Subtotal</th>
                                <th style="width:120px">Serial #</th>
                                <th style="width:50px"></th>
                            </tr>
                        </thead>
                        <tbody id="itemsBody">
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card card-outline card-secondary">
                <div class="card-header"><h3 class="card-title"><i class="fas fa-sticky-note mr-2"></i>Notes</h3></div>
                <div class="card-body">
                    <textarea name="notes" class="form-control" rows="2" placeholder="Optional notes...">{{ old('notes') }}</textarea>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card card-outline card-success">
                <div class="card-header"><h3 class="card-title"><i class="fas fa-calculator mr-2"></i>Sale Summary</h3></div>
                <div class="card-body">
                    <table class="table table-sm table-borderless mb-0">
                        <tr>
                            <td class="text-muted">Subtotal</td>
                            <td class="text-right"><strong id="displaySubtotal">৳0.00</strong></td>
                        </tr>
                        <tr>
                            <td>
                                <div class="input-group input-group-sm">
                                    <div class="input-group-prepend"><span class="input-group-text">Discount</span></div>
                                    <input type="number" name="discount" id="discountInput" class="form-control" value="0" min="0" step="0.01">
                                </div>
                            </td>
                            <td class="text-right text-danger"><strong id="displayDiscount">-৳0.00</strong></td>
                        </tr>
                        <tr>
                            <td>
                                <div class="input-group input-group-sm">
                                    <div class="input-group-prepend"><span class="input-group-text">Tax %</span></div>
                                    <input type="number" name="tax_percent" id="taxInput" class="form-control" value="0" min="0" step="0.01">
                                </div>
                            </td>
                            <td class="text-right"><strong id="displayTax">+৳0.00</strong></td>
                        </tr>
                        <tr class="border-top">
                            <td><strong class="text-lg">Grand Total</strong></td>
                            <td class="text-right"><strong class="text-lg text-primary" id="displayTotal">৳0.00</strong></td>
                        </tr>
                        <tr>
                            <td>
                                <div class="input-group input-group-sm">
                                    <div class="input-group-prepend"><span class="input-group-text">Paid</span></div>
                                    <input type="number" name="paid_amount" id="paidInput" class="form-control" value="0" min="0" step="0.01">
                                </div>
                            </td>
                            <td class="text-right text-success"><strong id="displayPaid">৳0.00</strong></td>
                        </tr>
                        <tr class="border-top">
                            <td><strong>Due</strong></td>
                            <td class="text-right"><strong class="text-danger" id="displayDue">৳0.00</strong></td>
                        </tr>
                    </table>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary btn-block btn-lg"><i class="fas fa-save mr-2"></i>Save Sale</button>
                    <a href="{{ route('admin.sales.index') }}" class="btn btn-default btn-block">Cancel</a>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    let itemIndex = 0;
    @php
        $productsArray = $products->map(function($p) {
            return ['id' => $p->id, 'name' => $p->name, 'code' => $p->code ?? '', 'brand' => $p->brand ?? '', 'model' => $p->model ?? '', 'selling_price' => $p->selling_price ?? 0, 'stock' => $p->stock_quantity ?? 0];
        })->values();
    @endphp
    const productsData = @json($productsArray);

    function buildProductOptions(selectedId) {
        let html = '<option value="">-- Select Product --</option>';
        productsData.forEach(function(p) {
            let sel = (selectedId == p.id) ? 'selected' : '';
            let brandModel = [p.brand, p.model].filter(Boolean).join(' / ');
            let label = p.name + (brandModel ? ' - ' + brandModel : '') + (p.code ? ' (' + p.code + ')' : '') + ' [Stock: ' + p.stock + ']';
            html += '<option value="' + p.id + '" data-price="' + p.selling_price + '" data-stock="' + p.stock + '" ' + sel + '>' + label + '</option>';
        });
        return html;
    }

    function addItemRow() {
        itemIndex++;
        let row = '<tr id="itemRow' + itemIndex + '">';
        row += '<td class="text-center align-middle">' + itemIndex + '</td>';
        row += '<td><select name="items[' + itemIndex + '][product_id]" class="form-control form-control-sm item-product" data-row="' + itemIndex + '" required>' + buildProductOptions(null) + '</select></td>';
        row += '<td><input type="text" class="form-control form-control-sm item-stock" data-row="' + itemIndex + '" readonly style="background:#f4f6f9"></td>';
        row += '<td><input type="number" name="items[' + itemIndex + '][quantity]" class="form-control form-control-sm item-qty" data-row="' + itemIndex + '" value="1" min="1" required></td>';
        row += '<td><input type="number" name="items[' + itemIndex + '][unit_price]" class="form-control form-control-sm item-price" data-row="' + itemIndex + '" value="0" min="0" step="0.01" required></td>';
        row += '<td><input type="text" class="form-control form-control-sm item-subtotal" data-row="' + itemIndex + '" readonly style="background:#f4f6f9" value="0.00"></td>';
        row += '<td><input type="text" name="items[' + itemIndex + '][serial_number]" class="form-control form-control-sm" placeholder="Optional"></td>';
        row += '<td><button type="button" class="btn btn-danger btn-xs remove-item" data-row="' + itemIndex + '"><i class="fas fa-times"></i></button></td>';
        row += '</tr>';
        document.getElementById('itemsBody').insertAdjacentHTML('beforeend', row);
        recalculate();
    }

    document.getElementById('addItemBtn').addEventListener('click', addItemRow);

    document.getElementById('itemsBody').addEventListener('change', function(e) {
        if (e.target.classList.contains('item-product')) {
            let row = e.target.dataset.row;
            let opt = e.target.options[e.target.selectedIndex];
            let price = opt.dataset.price || 0;
            let stock = opt.dataset.stock || 0;
            document.querySelector('.item-price[data-row="' + row + '"]').value = price;
            document.querySelector('.item-stock[data-row="' + row + '"]').value = stock;
            document.querySelector('.item-qty[data-row="' + row + '"]').max = stock;
            recalculate();
        }
    });

    document.getElementById('itemsBody').addEventListener('input', function(e) {
        if (e.target.classList.contains('item-qty') || e.target.classList.contains('item-price')) {
            recalculate();
        }
    });

    document.getElementById('itemsBody').addEventListener('click', function(e) {
        let btn = e.target.closest('.remove-item');
        if (btn) {
            document.getElementById('itemRow' + btn.dataset.row).remove();
            recalculate();
        }
    });

    document.getElementById('discountInput').addEventListener('input', recalculate);
    document.getElementById('taxInput').addEventListener('input', recalculate);
    document.getElementById('paidInput').addEventListener('input', recalculate);

    function recalculate() {
        let subtotal = 0;
        document.querySelectorAll('#itemsBody tr').forEach(function(tr) {
            let row = tr.id.replace('itemRow', '');
            let qty = parseFloat(tr.querySelector('.item-qty')?.value) || 0;
            let price = parseFloat(tr.querySelector('.item-price')?.value) || 0;
            let lineTotal = qty * price;
            let stField = tr.querySelector('.item-subtotal');
            if (stField) stField.value = lineTotal.toFixed(2);
            subtotal += lineTotal;
        });

        let discount = parseFloat(document.getElementById('discountInput').value) || 0;
        let taxPct = parseFloat(document.getElementById('taxInput').value) || 0;
        let taxAmt = (subtotal - discount) * (taxPct / 100);
        let total = subtotal - discount + taxAmt;
        let paid = parseFloat(document.getElementById('paidInput').value) || 0;
        let due = total - paid;

        document.getElementById('displaySubtotal').textContent = '৳' + subtotal.toFixed(2);
        document.getElementById('displayDiscount').textContent = '-৳' + discount.toFixed(2);
        document.getElementById('displayTax').textContent = '+৳' + taxAmt.toFixed(2);
        document.getElementById('displayTotal').textContent = '৳' + total.toFixed(2);
        document.getElementById('displayPaid').textContent = '৳' + paid.toFixed(2);
        document.getElementById('displayDue').textContent = '৳' + due.toFixed(2);
    }

    document.getElementById('customerType').addEventListener('change', function() {
        if (this.value === 'walk_in') {
            document.getElementById('clientSection').style.display = 'none';
            document.getElementById('walkInSection').style.display = 'block';
            document.getElementById('saleClientSearchId').removeAttribute('required');
        } else {
            document.getElementById('clientSection').style.display = 'block';
            document.getElementById('walkInSection').style.display = 'none';
        }
    });

    document.getElementById('saleForm').addEventListener('submit', function(e) {
        let rows = document.querySelectorAll('#itemsBody tr');
        if (rows.length === 0) {
            e.preventDefault();
            alert('Please add at least one item.');
            return;
        }
    });

    var paymentMethodEl = document.getElementById('salePaymentMethod');
    var paymentRefGroup = document.getElementById('paymentRefGroup');
    var paymentRefLabel = document.getElementById('paymentRefLabel');
    var paymentRefInput = document.getElementById('paymentRefInput');

    function updatePaymentRefField() {
        var method = paymentMethodEl.value;
        var labels = {
            'cash': 'Money Receipt No',
            'bkash': 'bKash Transaction ID',
            'nagad': 'Nagad Transaction ID',
            'rocket': 'Rocket Transaction ID',
            'bank': 'Account No / Reference',
            'card': 'Card Transaction ID',
            'other': 'Reference No'
        };
        var placeholders = {
            'cash': 'Enter receipt no',
            'bkash': 'e.g. TXN123456789',
            'nagad': 'e.g. TXN123456789',
            'rocket': 'e.g. TXN123456789',
            'bank': 'e.g. ACC/REF number',
            'card': 'e.g. TXN123456789',
            'other': 'Enter reference'
        };
        paymentRefLabel.textContent = labels[method] || 'Reference No';
        paymentRefInput.placeholder = placeholders[method] || 'Enter reference';
    }

    paymentMethodEl.addEventListener('change', updatePaymentRefField);
    updatePaymentRefField();

    addItemRow();
});
</script>
@endpush
