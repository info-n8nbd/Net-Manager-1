@extends('layouts.reseller')

@section('title', 'Collect Payment')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('reseller.dashboard') }}">Dashboard</a></li>
<li class="breadcrumb-item"><a href="{{ route('reseller.clients') }}">Clients</a></li>
<li class="breadcrumb-item"><a href="{{ route('reseller.clients.show', $client->id) }}">{{ $client->name }}</a></li>
<li class="breadcrumb-item active">Collect Payment</li>
@endsection

@section('content')
<div class="row">
    {{-- Client Info --}}
    <div class="col-md-4">
        <div class="card card-info card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-user mr-2"></i>Client Info</h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td class="text-muted pl-3" width="40%">Name</td>
                        <td><strong>{{ $client->name }}</strong></td>
                    </tr>
                    <tr>
                        <td class="text-muted pl-3">Client ID</td>
                        <td>{{ $client->client_id }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted pl-3">Username</td>
                        <td><code>{{ $client->username }}</code></td>
                    </tr>
                    <tr>
                        <td class="text-muted pl-3">Phone</td>
                        <td>{{ $client->phone ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted pl-3">Package</td>
                        <td>{{ $client->package->name ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted pl-3">Monthly Bill</td>
                        <td><strong>৳{{ number_format($client->monthly_fee ?? 0, 2) }}</strong></td>
                    </tr>
                    <tr class="table-danger">
                        <td class="text-muted pl-3">Total Due</td>
                        <td><strong class="text-danger">৳{{ number_format($client->due_amount ?? 0, 2) }}</strong></td>
                    </tr>
                </table>
            </div>
        </div>

        {{-- Unpaid Bills Summary --}}
        @if($unpaidBills->count())
        <div class="card card-warning card-outline">
            <div class="card-header py-2">
                <h3 class="card-title"><i class="fas fa-file-invoice-dollar mr-1"></i> Unpaid Bills ({{ $unpaidBills->count() }})</h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Month</th>
                            <th>Amount</th>
                            <th>Due</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($unpaidBills as $bill)
                        <tr>
                            <td>{{ $bill->bill_month ?? 'N/A' }}/{{ $bill->bill_year ?? '' }}</td>
                            <td>৳{{ number_format($bill->amount, 2) }}</td>
                            <td class="text-danger"><strong>৳{{ number_format($bill->amount - ($bill->paid_amount ?? 0), 2) }}</strong></td>
                            <td><span class="badge badge-{{ $bill->status === 'paid' ? 'success' : ($bill->status === 'partial' ? 'info' : 'warning') }}">{{ ucfirst($bill->status) }}</span></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>

    {{-- Payment Form --}}
    <div class="col-md-8">
        <div class="card card-success card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-money-bill-wave mr-2"></i>Collect Payment</h3>
            </div>
            <form method="POST" action="{{ route('reseller.collect-payment', $client->id) }}" id="collectPaymentForm">
                @csrf
                <div class="card-body">

                    @if($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><i class="fas fa-dollar-sign mr-1"></i> Amount (৳) <span class="text-danger">*</span></label>
                                <input type="number" name="amount" id="cp_amount"
                                       class="form-control form-control-lg"
                                       value="{{ old('amount', $client->due_amount > 0 ? $client->due_amount : '') }}"
                                       step="0.01" min="1"
                                       data-due="{{ $client->due_amount ?? 0 }}"
                                       required
                                       oninput="checkAmountDiff()">
                                <small id="cp_amount_warning" class="text-warning" style="display:none;">
                                    <i class="fas fa-exclamation-triangle mr-1"></i>Amount differs from due — note required!
                                </small>
                                <div id="cp_reason_wrapper" style="display:none;" class="mt-2">
                                    <input type="text" name="notes_reason" id="cp_reason" class="form-control" placeholder="Why is the amount different from due?">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><i class="fas fa-credit-card mr-1"></i> Payment Method <span class="text-danger">*</span></label>
                                <select name="payment_method_id" id="cp_method" class="form-control" required>
                                    @foreach($paymentMethods as $pm)
                                        <option value="{{ $pm->id }}"
                                                data-name="{{ strtolower($pm->name) }}"
                                                {{ old('payment_method_id') == $pm->id ? 'selected' : '' }}>
                                            {{ $pm->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    {{-- MFS / Online Payment Fields (bKash, Nagad, Rocket, etc.) --}}
                    <div class="row" id="cp_mfs_row" style="display:none;">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><i class="fas fa-mobile-alt mr-1"></i>
                                    <span id="cp_mfs_label">bKash</span> Number <span class="text-danger">*</span>
                                </label>
                                <input type="text" name="mobile_payment_number" id="cp_mobile_number"
                                       class="form-control"
                                       placeholder="Enter mobile payment number"
                                       value="{{ old('mobile_payment_number') }}"
                                       maxlength="15">
                                <small class="text-muted">Sender's number used for the transaction</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><i class="fas fa-receipt mr-1"></i> Transaction ID <span class="text-danger">*</span></label>
                                <input type="text" name="transaction_id" id="cp_trx_mfs"
                                       class="form-control"
                                       placeholder="e.g. 8N6A3XXXX"
                                       value="{{ old('transaction_id') }}">
                            </div>
                        </div>
                    </div>

                    {{-- Non-MFS Transaction ID & Money Receipt --}}
                    <div class="row" id="cp_nonmfs_row">
                        <div class="col-md-6" id="cp_trx_row">
                            <div class="form-group">
                                <label><i class="fas fa-receipt mr-1"></i> Transaction ID <small class="text-muted">(Optional)</small></label>
                                <input type="text" name="transaction_id" id="cp_trx_nonmfs"
                                       class="form-control"
                                       placeholder="Optional transaction ID"
                                       value="{{ old('transaction_id') }}">
                            </div>
                        </div>
                        <div class="col-md-6" id="cp_receipt_row" style="display:none;">
                            <div class="form-group">
                                <label><i class="fas fa-file-alt mr-1"></i> Money Receipt No. <small class="text-muted">(Optional)</small></label>
                                <input type="text" name="money_receipt_no"
                                       class="form-control"
                                       placeholder="Enter money receipt number"
                                       value="{{ old('money_receipt_no') }}">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-sticky-note mr-1"></i> Notes <small class="text-muted">(Optional)</small></label>
                        <textarea name="notes" class="form-control" rows="2"
                                  placeholder="Payment notes or remarks...">{{ old('notes') }}</textarea>
                    </div>

                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="cp_send_sms" name="send_sms" value="1" checked>
                        <label class="custom-control-label" for="cp_send_sms">
                            <i class="fas fa-sms mr-1"></i> Send SMS notification to client
                        </label>
                        <small class="form-text text-muted">Uncheck to skip SMS for this payment</small>
                    </div>

                </div>
                <div class="card-footer d-flex justify-content-between align-items-center">
                    <a href="{{ route('reseller.clients.show', $client->id) }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left mr-1"></i> Back
                    </a>
                    <button type="submit" class="btn btn-success btn-lg" id="cp_submit_btn">
                        <i class="fas fa-check mr-1"></i> Submit Payment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function () {

    var MFS_KEYWORDS = ['bkash', 'nagad', 'rocket', 'upay', 'tap', 'mcash', 'surecash', 'dmoney', 'mfs', 'mobile'];

    function isMfsMethod(name) {
        name = (name || '').toLowerCase();
        return MFS_KEYWORDS.some(function (k) { return name.indexOf(k) !== -1; });
    }

    function isCashMethod(name) {
        return (name || '').toLowerCase().indexOf('cash') !== -1;
    }

    function checkPaymentMethod() {
        var $sel   = $('#cp_method');
        var $opt   = $sel.find('option:selected');
        var name   = ($opt.data('name') || $opt.text() || '').toLowerCase();
        var isMfs  = isMfsMethod(name);
        var isCash = isCashMethod(name);

        if (isMfs) {
            // Show MFS row, hide non-MFS row
            $('#cp_mfs_row').show();
            $('#cp_nonmfs_row').hide();
            // Update dynamic label
            $('#cp_mfs_label').text($opt.text().trim());
            // Enable MFS inputs & make required
            $('#cp_mobile_number').prop('disabled', false).prop('required', true);
            $('#cp_trx_mfs').prop('disabled', false).prop('required', true);
            // Disable non-MFS inputs so they don't submit as empty duplicates
            $('#cp_trx_nonmfs').prop('disabled', true);
        } else {
            // Hide MFS row, show non-MFS row
            $('#cp_mfs_row').hide();
            $('#cp_nonmfs_row').show();
            // Disable MFS inputs (hidden) so they don't submit
            $('#cp_mobile_number').prop('disabled', true).prop('required', false).val('');
            $('#cp_trx_mfs').prop('disabled', true).prop('required', false).val('');

            if (isCash) {
                // Cash: hide TrxID, show Money Receipt No.
                $('#cp_trx_row').hide();
                $('#cp_trx_nonmfs').prop('disabled', true).val('');
                $('#cp_receipt_row').show();
            } else {
                // Bank Transfer / Other: show TrxID (optional), hide Receipt
                $('#cp_trx_row').show();
                $('#cp_trx_nonmfs').prop('disabled', false);
                $('#cp_receipt_row').hide().find('input').val('');
            }
        }
    }

    // Check amount differs from due
    window.checkAmountDiff = function () {
        var amount = parseFloat($('#cp_amount').val()) || 0;
        var due    = parseFloat($('#cp_amount').data('due')) || 0;
        var isDifferent = due > 0 && Math.abs(amount - due) > 0.01;
        $('#cp_amount_warning').toggle(isDifferent);
        $('#cp_reason_wrapper').toggle(isDifferent);
        $('#cp_reason').prop('required', isDifferent);
    };

    $('#cp_method').on('change', checkPaymentMethod);
    checkPaymentMethod(); // run on page load

    // Prevent double submit
    $('#collectPaymentForm').on('submit', function () {
        var $btn = $('#cp_submit_btn');
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Processing...');
    });
});
</script>
@endpush
