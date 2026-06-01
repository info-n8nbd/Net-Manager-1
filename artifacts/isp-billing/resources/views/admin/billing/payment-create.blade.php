@extends('layouts.admin')

@section('title', 'Receive Payment')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.payments.index') }}">Payments</a></li>
<li class="breadcrumb-item active">Receive</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-money-bill-wave mr-2"></i>Receive Payment</h3>
            </div>
            <form action="{{ route('admin.payments.store') }}" method="POST">
                @csrf
                <div class="card-body">
                    @if(isset($selectedClient))
                    @php
                        // Use centralized BillingCalculatorService via Client model
                        $billingData = $selectedClient->getBillingSummary();
                        $realDue = $billingData['due'];
                        $remainAdv = $billingData['advance'];
                    @endphp
                    <div class="alert alert-info">
                        <i class="fas fa-user mr-2"></i>
                        <strong>Client:</strong> {{ $selectedClient->client_id }} - {{ $selectedClient->name }}
                        <span class="badge badge-{{ $realDue > 0 ? 'danger' : 'success' }} ml-2">
                            Due: ৳{{ number_format($realDue ?? 0, 2) }}
                        </span>
                        @if($remainAdv > 0)
                        <span class="badge badge-success ml-1">Advance: ৳{{ number_format($remainAdv ?? 0, 2) }}</span>
                        @endif
                        <input type="hidden" name="client_id" value="{{ $selectedClient->id }}">
                        <input type="hidden" name="redirect_to" id="redirect_to" value="">
                    </div>
                    
                    @if(isset($unpaidBills) && $unpaidBills->count() > 0)
                    @php
                        $oldestBill = $unpaidBills->sortBy('billing_month')->first();
                        // Use realDue from centralized calculation
                        $billDueAfterAdv = $realDue;
                    @endphp
                    <div class="alert alert-light border py-2 mb-3">
                        <small class="text-muted"><i class="fas fa-file-invoice mr-1"></i> Auto-selected: Oldest Bill</small><br>
                        <strong>{{ $oldestBill->bill_month }}</strong> - 
                        ৳{{ number_format($billDueAfterAdv ?? 0, 2) }} due
                        (Total: ৳{{ number_format($oldestBill->total_amount ?? 0, 2) }})
                        @if($remainAdv > 0)
                        <small class="text-info ml-2">(Advance: ৳{{ number_format($remainAdv ?? 0, 2) }} available)</small>
                        @endif
                    </div>
                    @else
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle mr-2"></i> No unpaid bills for this client.
                    </div>
                    @endif
                    @else
                    @include('admin.partials.client-search', [
                        'inputId' => 'payCreateClientSearch',
                        'hiddenId' => 'payCreateClientSearchId',
                        'fieldName' => 'client_id',
                        'label' => 'Select Client',
                        'required' => true,
                    ])
                    @endif

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><i class="fas fa-dollar-sign mr-1"></i> Amount (৳) <span class="text-danger">*</span></label>
                                <input type="number" name="amount" id="paymentAmount" class="form-control form-control-lg" step="0.01" min="1" 
                                       placeholder="Enter amount" value="{{ old('amount', isset($billDueAfterAdv) ? $billDueAfterAdv : (isset($realDue) ? $realDue : '')) }}" 
                                       data-due="{{ isset($billDueAfterAdv) ? $billDueAfterAdv : (isset($realDue) ? $realDue : 0) }}" required>
                                <small id="amountWarning" class="text-warning" style="display: none;"><i class="fas fa-exclamation-triangle mr-1"></i>Amount differs from due - Note required!</small>
                                <div id="pc_reason_wrapper" style="display: none;" class="mt-2">
                                    <input type="text" name="notes_reason" id="pc_reason" class="form-control" placeholder="Why is the amount different from due?" style="border-radius: 8px;">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><i class="fas fa-credit-card mr-1"></i> Payment Method <span class="text-danger">*</span></label>
                                <select name="payment_method_id" id="paymentMethodSelect" class="form-control" required>
                                    @foreach($paymentMethods as $method)
                                    <option value="{{ $method->id }}" 
                                            data-name="{{ strtolower($method->name) }}"
                                            {{ old('payment_method_id') == $method->id ? 'selected' : '' }}>
                                        {{ $method->name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row" id="mobileNumberRow" style="display: none;">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><i class="fas fa-mobile-alt mr-1"></i> <span id="mobileMethodLabel">bKash</span> Number <span class="text-danger">*</span></label>
                                <input type="text" name="mobile_payment_number" id="mobilePaymentNumber" class="form-control" 
                                       placeholder="" value="{{ old('mobile_payment_number') }}" maxlength="15">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><i class="fas fa-receipt mr-1"></i> Transaction ID</label>
                                <input type="text" name="transaction_id" class="form-control transaction-id-input" 
                                       placeholder="Enter Transaction ID" value="{{ old('transaction_id') }}">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><i class="fas fa-calendar mr-1"></i> Payment Date <span class="text-danger">*</span></label>
                                <input type="date" name="payment_date" class="form-control" 
                                       value="{{ old('payment_date', date('Y-m-d')) }}" required>
                            </div>
                        </div>
                        <div class="col-md-6" id="transactionIdNonMobile">
                            <div class="form-group">
                                <label><i class="fas fa-receipt mr-1"></i> Transaction ID</label>
                                <input type="text" name="transaction_id" class="form-control transaction-id-input" 
                                       placeholder="Optional - Transaction ID" value="{{ old('transaction_id') }}">
                            </div>
                        </div>
                    </div>

                    <div class="row" id="cashReceiptRow" style="display: none;">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><i class="fas fa-receipt mr-1"></i> Money Receipt No.</label>
                                <input type="text" name="money_receipt_no" class="form-control" placeholder="Enter money receipt number" value="{{ old('money_receipt_no') }}">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-comment mr-1"></i> Notes</label>
                        <textarea name="remarks" class="form-control" rows="2" placeholder="Payment notes...">{{ old('remarks') }}</textarea>
                    </div>
                    
                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="send_sms" name="send_sms" value="1" checked>
                            <label class="custom-control-label" for="send_sms">
                                <i class="fas fa-sms mr-1"></i> Send SMS notification to client
                            </label>
                            <small class="form-text text-muted">Uncheck to skip SMS for this payment (SMS will still be logged)</small>
                        </div>
                    </div>
                </div>
                <div class="card-footer text-right">
                    <a href="{{ isset($selectedClient) ? route('admin.clients.show', $selectedClient->id) : route('admin.payments.index') }}" class="btn btn-secondary mr-2">
                        <i class="fas fa-times mr-1"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-success btn-lg">
                        <i class="fas fa-check mr-1"></i> Receive Payment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@include('components.payment-receipt-modal')
@endsection


@push('scripts')
<script>
$(document).ready(function() {
    var redirectField = document.getElementById('redirect_to');
    if (redirectField && document.referrer) {
        redirectField.value = document.referrer;
    }

    function checkPaymentMethod() {
        var selected = $('#paymentMethodSelect option:selected');
        var methodName = (selected.data('name') || '').toLowerCase();
        var displayName = selected.text().trim();
        var isOnline = (methodName !== 'cash');
        
        var isCash = methodName === 'cash';
        if (isOnline) {
            $('#mobileNumberRow').show();
            $('#mobilePaymentNumber').prop('required', true);
            $('#transactionIdNonMobile').hide();
            $('#transactionIdNonMobile').find('input').prop('disabled', true);
            $('#mobileNumberRow').find('.transaction-id-input').prop('disabled', false);
            $('#mobileMethodLabel').text(displayName);
            $('#mobilePaymentNumber').attr('placeholder', 'Enter ' + displayName + ' number');
        } else {
            $('#mobileNumberRow').hide();
            $('#mobilePaymentNumber').prop('required', false).val('');
            $('#transactionIdNonMobile').show();
            $('#transactionIdNonMobile').find('input').prop('disabled', false);
            $('#mobileNumberRow').find('.transaction-id-input').prop('disabled', true);
        }
        
        if (isCash) {
            $('#cashReceiptRow').show();
        } else {
            $('#cashReceiptRow').hide();
            $('#cashReceiptRow').find('input').val('');
        }
    }
    
    $('#paymentMethodSelect').on('change', checkPaymentMethod);
    checkPaymentMethod();
    
    function checkAmountDiff() {
        var amount = parseFloat($('#paymentAmount').val()) || 0;
        var due = parseFloat($('#paymentAmount').data('due')) || 0;
        var isDifferent = due > 0 && Math.abs(amount - due) > 0.01;
        
        $('#amountWarning').toggle(isDifferent);
        $('#pc_reason_wrapper').toggle(isDifferent);
        $('#pc_reason').prop('required', isDifferent);
        return isDifferent;
    }
    
    checkAmountDiff();
    $('#paymentAmount').on('input change keyup', checkAmountDiff);
});
</script>
@endpush
