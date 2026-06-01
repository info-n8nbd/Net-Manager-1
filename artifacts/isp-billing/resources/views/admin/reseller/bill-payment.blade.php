@extends('layouts.admin')

@section('title', 'Receive Payment - ' . $reseller->name)
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.reseller.index', ['type'=>$reseller->type]) }}">{{ $reseller->type === 'bandwidth' ? 'Bandwidth Reseller' : 'MAC Reseller' }}</a></li>
<li class="breadcrumb-item"><a href="{{ route('admin.reseller.show', $reseller) }}">{{ $reseller->name }}</a></li>
<li class="breadcrumb-item active">Payment</li>
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-user mr-2"></i>{{ $reseller->type === 'bandwidth' ? 'Bandwidth Reseller' : 'MAC Reseller' }} Summary</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-2">
                        <strong>Name:</strong><br>{{ $reseller->name }}
                    </div>
                    <div class="col-md-2">
                        <strong>Area/Zone:</strong><br>{{ $reseller->thana ?? '-' }}
                    </div>
                    <div class="col-md-2">
                        <strong>Cell No:</strong><br>{{ $reseller->phone ?? '-' }}
                    </div>
                    <div class="col-md-2">
                        <strong>Total Clients:</strong><br>{{ $totalClients }}
                    </div>
                    <div class="col-md-2">
                        <strong>Active Clients:</strong><br>{{ $activeClients }}
                    </div>
                    <div class="col-md-2">
                        <strong>Balance:</strong><br>
                        <span class="{{ $reseller->wallet_balance >= 0 ? 'text-success' : 'text-danger' }} font-weight-bold" style="font-size: 1.1em;">
                            ৳{{ number_format($reseller->wallet_balance, 2) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-sync-alt mr-2"></i>{{ isset($reseller) && $reseller->type === 'bandwidth' ? 'Current Month Bills' : 'Current Month Recharges' }}</h3>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-sm table-striped table-bordered">
                    <thead class="thead-dark">
                        <tr>
                            <th>S/L</th>
                            <th>Client ID</th>
                            <th>Date</th>
                            <th>Package</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th class="text-center">Days</th>
                            <th class="text-right">{{ isset($reseller) && $reseller->type === 'bandwidth' ? 'Bill Amount' : 'Recharge Amount' }}</th>
                            <th>Entry By</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $rechargeTotal = 0; @endphp
                        @forelse($recharges as $index => $recharge)
                        @php $rechargeTotal += $recharge->recharge_amount; @endphp
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $recharge->client->client_id ?? '-' }}</td>
                            <td>{{ $recharge->created_at ? $recharge->created_at->format('d-m-Y') : '-' }}</td>
                            <td>{{ $recharge->package_name ?? '-' }}</td>
                            <td>{{ $recharge->start_date ? $recharge->start_date->format('d-m-Y') : '-' }}</td>
                            <td>{{ $recharge->end_date ? $recharge->end_date->format('d-m-Y') : '-' }}</td>
                            <td class="text-center">{{ $recharge->days ?? '-' }}</td>
                            <td class="text-right">৳{{ number_format($recharge->recharge_amount, 2) }}</td>
                            <td>{{ $recharge->rechargedByUser->name ?? '-' }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted">{{ isset($reseller) && $reseller->type === 'bandwidth' ? 'No bills this month' : 'No recharges this month' }}</td>
                        </tr>
                        @endforelse
                    </tbody>
                    @if($recharges->count() > 0)
                    <tfoot class="font-weight-bold bg-light">
                        <tr>
                            <td colspan="7" class="text-right">Total:</td>
                            <td class="text-right">৳{{ number_format($rechargeTotal, 2) }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card card-success">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-money-bill-wave mr-2"></i>Receive Payment from {{ $reseller->name }}</h3>
            </div>
            <form action="{{ route('admin.reseller.bill-payment.store', $reseller) }}" method="POST">
                @csrf
                <div class="card-body">
                    <div class="callout callout-info">
                        <p class="mb-0">
                            Current Balance:
                            <strong class="{{ $reseller->wallet_balance >= 0 ? 'text-success' : 'text-danger' }}">
                                ৳{{ number_format($reseller->wallet_balance, 2) }}
                            </strong>
                            @if($currentBill)
                            | Current Month Due:
                            <strong class="text-danger">৳{{ number_format($currentBill->due_amount, 2) }}</strong>
                            @endif
                        </p>
                    </div>

                    <div class="form-group">
                        <label for="payment_method">Payment Method <span class="text-danger">*</span></label>
                        <select name="payment_method" id="payment_method" class="form-control @error('payment_method') is-invalid @enderror" required>
                            <option value="">-- Select Method --</option>
                            @foreach($paymentMethods as $method)
                            <option value="{{ $method->name ?? $method }}" {{ old('payment_method') == ($method->name ?? $method) ? 'selected' : '' }}>
                                {{ ucfirst($method->name ?? $method) }}
                            </option>
                            @endforeach
                        </select>
                        @error('payment_method')
                        <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="form-group" id="field_account_no_digital" style="display:none;">
                        <label for="payment_account_no"><i class="fas fa-mobile-alt mr-1 text-info"></i> Account No. <span class="text-danger">*</span></label>
                        <input type="text" id="payment_account_no" name="payment_account_no" class="form-control" placeholder="e.g. 017XXXXXXXX">
                    </div>

                    <div class="form-group" id="field_txn_no" style="display:none;">
                        <label for="payment_reference_txn"><i class="fas fa-mobile-alt mr-1 text-info"></i> Transaction No.</label>
                        <input type="text" id="payment_reference_txn" class="form-control" placeholder="Enter transaction number">
                    </div>

                    <div class="form-group">
                        <label for="amount" id="amount-label">Amount <span class="text-danger">*</span></label>
                        <input type="number" name="amount" id="amount" class="form-control @error('amount') is-invalid @enderror"
                               step="0.01" min="1" value="{{ old('amount') }}" required placeholder="Enter amount">
                        @error('amount')
                        <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="discount_amount">Discount Amount</label>
                        <input type="number" name="discount_amount" id="discount_amount"
                               class="form-control @error('discount_amount') is-invalid @enderror"
                               step="0.01" min="0" value="{{ old('discount_amount', 0) }}" placeholder="0.00">
                        @error('discount_amount')
                        <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    @if($reseller->agent_id && $reseller->agent)
                    <div class="card card-outline card-warning mb-3">
                        <div class="card-header py-2">
                            <h3 class="card-title" style="font-size: 13px;"><i class="fas fa-user-tie mr-1"></i>Referral Partner Commission — {{ $reseller->agent->name }}</h3>
                        </div>
                        <div class="card-body py-2">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-1">
                                        <label style="font-size: 12px;">Commission %</label>
                                        <input type="number" name="referral_commission_percent" id="referral_commission_percent"
                                               class="form-control form-control-sm"
                                               step="0.01" min="0" max="100" value="{{ old('referral_commission_percent', $reseller->referral_commission_percent ?? $reseller->agent->default_commission ?? 0) }}">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group mb-1">
                                        <label style="font-size: 12px;">Commission Amount (৳)</label>
                                        <input type="number" name="referral_commission_amount" id="referral_commission_amount"
                                               class="form-control form-control-sm"
                                               step="0.01" min="0" value="{{ old('referral_commission_amount', 0) }}">
                                    </div>
                                </div>
                            </div>
                            <small class="text-muted">Default: {{ $reseller->agent->default_commission ?? 0 }}% — change % or amount manually if needed</small>
                        </div>
                    </div>
                    @endif


                    <div class="form-group" id="field_money_receipt" style="display:none;">
                        <label for="payment_reference_cash"><i class="fas fa-receipt mr-1 text-success"></i> Money Receipt No.</label>
                        <input type="text" id="payment_reference_cash" class="form-control" placeholder="Enter money receipt number">
                    </div>
                    <div class="form-group" id="field_account_no" style="display:none;">
                        <label for="payment_reference_bank"><i class="fas fa-university mr-1 text-primary"></i> Account No. / Bank Reference</label>
                        <input type="text" id="payment_reference_bank" class="form-control" placeholder="Enter account or reference number">
                    </div>
                    <input type="hidden" name="payment_reference" id="payment_reference" value="{{ old('payment_reference') }}">

                    <div class="form-group">
                        <label for="payment_date">Payment Date</label>
                        <input type="date" name="payment_date" id="payment_date"
                               class="form-control @error('payment_date') is-invalid @enderror"
                               value="{{ old('payment_date', date('Y-m-d')) }}">
                        @error('payment_date')
                        <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="notes">Notes</label>
                        <textarea name="notes" id="notes" class="form-control @error('notes') is-invalid @enderror"
                                  rows="3" placeholder="Optional notes">{{ old('notes') }}</textarea>
                        @error('notes')
                        <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-success btn-block">
                        <i class="fas fa-check mr-1"></i> Receive Payment
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-history mr-2"></i>Recent Payments</h3>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-sm table-striped table-bordered">
                    <thead class="thead-light">
                        <tr>
                            <th>Date</th>
                            <th class="text-right">Amount</th>
                            <th class="text-right">Discount</th>
                            <th>Method</th>
                            <th>Reference</th>
                            <th>Received By</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentPayments as $payment)
                        <tr>
                            <td>{{ $payment->payment_date ? $payment->payment_date->format('d-m-Y') : '-' }}</td>
                            <td class="text-right">৳{{ number_format($payment->amount, 2) }}</td>
                            <td class="text-right">৳{{ number_format($payment->discount_amount, 2) }}</td>
                            <td>{{ ucfirst($payment->payment_method ?? '-') }}</td>
                            <td>{{ $payment->payment_reference ?? '-' }}</td>
                            <td>{{ $payment->receivedByUser->name ?? '-' }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted">No recent payments</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="mb-3">
    <a href="{{ route('admin.reseller.show', $reseller) }}" class="btn btn-secondary">
        <i class="fas fa-arrow-left mr-1"></i> Back to {{ $reseller->type === 'bandwidth' ? 'Bandwidth Reseller' : 'MAC Reseller' }}
    </a>
    <a href="{{ route('admin.reseller.bill-history', $reseller) }}" class="btn btn-info">
        <i class="fas fa-history mr-1"></i> View Bill History
    </a>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var amountInput = document.getElementById('amount');
        var refPercentInput = document.getElementById('referral_commission_percent');
        var refAmountInput = document.getElementById('referral_commission_amount');

        function updateAll() {
            var amount = parseFloat(amountInput.value) || 0;
            if (refPercentInput && refAmountInput) {
                var refPercent = parseFloat(refPercentInput.value) || 0;
                refAmountInput.value = ((amount / 100) * refPercent).toFixed(2);
            }
        }

        if (amountInput) {
            amountInput.addEventListener('input', updateAll);
        }
        if (refPercentInput) {
            refPercentInput.addEventListener('input', function() {
                var amount = parseFloat(amountInput.value) || 0;
                var refPercent = parseFloat(this.value) || 0;
                refAmountInput.value = ((amount / 100) * refPercent).toFixed(2);
            });
        }

        var methodSelect = document.getElementById("payment_method");
        var fieldAccountDigital = document.getElementById("field_account_no_digital");
        var inputAccountDigital = document.getElementById("payment_account_no");
        var fieldReceipt = document.getElementById('field_money_receipt');
        var fieldTxn     = document.getElementById('field_txn_no');
        var fieldBank    = document.getElementById('field_account_no');
        var refInput     = document.getElementById('payment_reference');

        var inputReceipt = document.getElementById('payment_reference_cash');
        var inputTxn     = document.getElementById('payment_reference_txn');
        var inputBank    = document.getElementById('payment_reference_bank');

        function syncReference() {
            if (fieldReceipt.style.display !== 'none') {
                refInput.value = inputReceipt.value;
            } else if (fieldTxn.style.display !== 'none') {
                refInput.value = inputTxn.value;
            } else if (fieldBank.style.display !== 'none') {
                refInput.value = inputBank.value;
            } else {
                refInput.value = '';
            }
        }

        [inputReceipt, inputTxn, inputBank].forEach(function(el) {
            el.addEventListener('input', syncReference);
        });

        function onMethodChange() {
            var method = (methodSelect.value || '').toLowerCase();
            var methodLabel = methodSelect.options[methodSelect.selectedIndex]
                              ? methodSelect.options[methodSelect.selectedIndex].text.trim()
                              : '';

            var isCash   = method.indexOf('cash') !== -1;
            var isMobile = method.indexOf('bkash') !== -1 || method.indexOf('nagad') !== -1
                        || method.indexOf('rocket') !== -1 || method.indexOf('upay') !== -1
                        || method.indexOf('mfs') !== -1 || method.indexOf('mobile') !== -1;
            var isBank   = method.indexOf('bank') !== -1 || method.indexOf('transfer') !== -1
                        || method.indexOf('cheque') !== -1 || method.indexOf('neft') !== -1;

            // Update amount label dynamically
            var amountLabel = document.getElementById('amount-label');
            if (amountLabel) {
                var labelText = methodLabel ? methodLabel + ' Amount' : 'Amount';
                amountLabel.innerHTML = labelText + ' <span class="text-danger">*</span>';
            }

            fieldReceipt.style.display = isCash ? "" : "none";
            fieldTxn.style.display     = isMobile ? "" : "none";
            if (fieldAccountDigital) fieldAccountDigital.style.display = isMobile ? "" : "none";
            fieldBank.style.display    = isBank ? '' : 'none';

            if (!isCash) inputReceipt.value = '';
            if (!isMobile) { inputTxn.value = ""; if (inputAccountDigital) inputAccountDigital.value = ""; }
            if (!isBank) inputBank.value = '';
            syncReference();
        }

        if (methodSelect) {
            methodSelect.addEventListener('change', onMethodChange);
            onMethodChange();
        }

        document.querySelector('form').addEventListener('submit', function() {
            syncReference();
        });
    });
</script>
@endpush
