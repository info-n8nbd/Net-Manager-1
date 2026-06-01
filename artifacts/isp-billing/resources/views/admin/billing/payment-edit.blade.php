@extends('layouts.admin')

@section('title', 'Edit Payment')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.payments.index') }}">Payments</a></li>
<li class="breadcrumb-item active">Edit Payment</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-edit mr-2"></i>Edit Payment</h3>
            </div>
            <form action="{{ route('admin.payments.update', $payment) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="card-body">
                    <div class="alert alert-info">
                        <strong>Client:</strong> {{ $payment->client->name }} ({{ $payment->client->client_id }})<br>
                        <strong>Original Amount:</strong> ৳{{ number_format($payment->amount ?? 0, 2) }}<br>
                        <strong>Collected By:</strong> {{ $payment->collectedBy->name ?? 'System' }}
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Amount (৳) <span class="text-danger">*</span></label>
                                <input type="number" name="amount" class="form-control" value="{{ $payment->amount }}" step="0.01" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Payment Date <span class="text-danger">*</span></label>
                                <input type="date" name="payment_date" class="form-control" value="{{ $payment->payment_date->format('Y-m-d') }}" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Payment Method <span class="text-danger">*</span></label>
                                <select name="payment_method_id" class="form-control" required>
                                    @foreach($paymentMethods as $method)
                                    <option value="{{ $method->id }}" data-name="{{ strtolower($method->name) }}" {{ $payment->payment_method_id == $method->id ? 'selected' : '' }}>{{ $method->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Transaction ID</label>
                                <input type="text" name="transaction_id" class="form-control" value="{{ $payment->transaction_id }}">
                            </div>
                        </div>
                    </div>

                    <div class="form-group" id="cashReceiptField" style="display: none;">
                        <label><i class="fas fa-receipt mr-1"></i> Money Receipt No.</label>
                        <input type="text" name="money_receipt_no" class="form-control" placeholder="Enter money receipt number" value="{{ $payment->money_receipt_no ?? old('money_receipt_no') }}">
                    </div>

                    <div class="form-group">
                        <label>Remarks</label>
                        <textarea name="remarks" class="form-control" rows="2">{{ $payment->remarks }}</textarea>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i>Update Payment</button>
                    <a href="{{ route('admin.payments.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-danger">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-trash mr-2"></i>Delete Payment</h3>
            </div>
            <div class="card-body">
                <p class="text-muted">Permanently delete this payment. The client's due amount will be restored.</p>
                <form action="{{ route('admin.payments.destroy', $payment) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this payment? The client\'s due will be restored.')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-block">
                        <i class="fas fa-trash mr-1"></i>Delete Payment
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    function toggleCashReceipt() {
        var sel = $('select[name="payment_method_id"]');
        var name = (sel.find('option:selected').data('name') || '').toLowerCase();
        var isCash = name.indexOf('cash') !== -1;
        if (isCash) {
            $('#cashReceiptField').show();
        } else {
            $('#cashReceiptField').hide();
            $('#cashReceiptField input').val('');
        }
    }
    $('select[name="payment_method_id"]').on('change', toggleCashReceipt);
    toggleCashReceipt();
});
</script>
@endpush
