@extends('layouts.admin')

@section('title', 'Add Additional Payment')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.clients.index') }}">Clients</a></li>
<li class="breadcrumb-item"><a href="{{ route('admin.clients.show', $client) }}">{{ $client->name }}</a></li>
<li class="breadcrumb-item"><a href="{{ route('admin.clients.additional-payments.index', $client) }}">Additional Payments</a></li>
<li class="breadcrumb-item active">Add Payment</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-plus mr-2"></i>Record Additional Payment</h3>
            </div>
            <form action="{{ route('admin.clients.additional-payments.store', $client) }}" method="POST">
                @csrf
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Payment Type <span class="text-danger">*</span></label>
                                <select name="payment_type" class="form-control" required>
                                    <option value="">Select Type</option>
                                    @foreach($paymentTypes as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Amount (৳) <span class="text-danger">*</span></label>
                                <input type="number" name="amount" class="form-control" step="0.01" min="0.01" required placeholder="0.00">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Payment Date <span class="text-danger">*</span></label>
                                <input type="date" name="payment_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Payment Method <span class="text-danger">*</span></label>
                                <select name="payment_method_id" class="form-control" required id="apPaymentMethod">
                                    @foreach($paymentMethods as $method)
                                    <option value="{{ $method->id }}" data-name="{{ strtolower($method->name) }}">{{ $method->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group" id="apCashReceiptField" style="display: none;">
                        <label><i class="fas fa-receipt mr-1"></i> Money Receipt No.</label>
                        <input type="text" name="money_receipt_no" class="form-control" placeholder="Enter money receipt number">
                    </div>

                    <div class="form-group">
                        <label>Description</label>
                        <input type="text" name="description" class="form-control" placeholder="e.g., TP-Link Router, ONU Device Model X">
                    </div>

                    <div class="form-group">
                        <label>Remarks</label>
                        <textarea name="remarks" class="form-control" rows="2" placeholder="Additional notes..."></textarea>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-1"></i> Save Payment
                    </button>
                    <a href="{{ route('admin.clients.additional-payments.index', $client) }}" class="btn btn-secondary">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-info">
                <h3 class="card-title"><i class="fas fa-user mr-2"></i>Client Info</h3>
            </div>
            <div class="card-body">
                <p><strong>Name:</strong> {{ $client->name }}</p>
                <p><strong>ID:</strong> {{ $client->client_id }}</p>
                <p><strong>Phone:</strong> {{ $client->phone }}</p>
                <p><strong>Package:</strong> {{ $client->package->name ?? 'N/A' }}</p>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header bg-secondary">
                <h3 class="card-title"><i class="fas fa-info-circle mr-2"></i>Payment Types</h3>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    @foreach($paymentTypes as $key => $label)
                    <li class="list-group-item d-flex justify-content-between">
                        <span>{{ $label }}</span>
                        <code>{{ $key }}</code>
                    </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    function toggleApCashReceipt() {
        var name = ($('#apPaymentMethod option:selected').data('name') || '').toLowerCase();
        var isCash = name.indexOf('cash') !== -1;
        if (isCash) {
            $('#apCashReceiptField').show();
        } else {
            $('#apCashReceiptField').hide();
            $('#apCashReceiptField input').val('');
        }
    }
    $('#apPaymentMethod').on('change', toggleApCashReceipt);
    toggleApCashReceipt();
});
</script>
@endpush
