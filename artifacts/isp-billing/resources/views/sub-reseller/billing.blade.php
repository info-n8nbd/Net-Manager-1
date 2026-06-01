@extends('layouts.sub-reseller')

@section('title', 'Billing')

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-file-invoice-dollar mr-2"></i>Payments</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#collectPaymentModal">
                <i class="fas fa-plus mr-1"></i> Collect Payment
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Client</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payments ?? [] as $payment)
                    <tr>
                        <td>{{ $payment->id }}</td>
                        <td>{{ $payment->client->name ?? 'N/A' }}</td>
                        <td class="text-success">৳{{ number_format($payment->amount) }}</td>
                        <td>{{ ucfirst($payment->payment_method ?? 'cash') }}</td>
                        <td>{{ $payment->payment_date->format('d-m-Y') }}</td>
                        <td>
                            <span class="badge badge-{{ $payment->status == 'completed' ? 'success' : 'warning' }}">
                                {{ ucfirst($payment->status ?? 'pending') }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted">No payments found</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if(isset($payments) && $payments->hasPages())
        <div class="mt-3">
            {{ $payments->appends(request()->query())->links() }}
        </div>
        @endif
    </div>
</div>

<div class="modal fade" id="collectPaymentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('sub-reseller.billing.collect') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-money-bill-wave mr-2"></i>Collect Payment</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    @include('admin.partials.client-search', [
                        'inputId' => 'subResBillClientSearch',
                        'hiddenId' => 'subResBillClientSearchId',
                        'fieldName' => 'client_id',
                        'label' => 'Client',
                        'required' => true,
                        'searchUrl' => route('sub-reseller.api.search-clients'),
                    ])
                    <div class="form-group">
                        <label for="amount">Amount (৳)</label>
                        <input type="number" name="amount" id="amount" class="form-control" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label for="payment_method">Payment Method</label>
                        <select name="payment_method" id="payment_method" class="form-control">
                            <option value="cash">Cash</option>
                            <option value="bkash">bKash</option>
                            <option value="nagad">Nagad</option>
                            <option value="bank">Bank Transfer</option>
                        </select>
                    </div>
                    <div class="form-group" id="sr_cash_receipt_field" style="display: none;">
                        <label><i class="fas fa-receipt mr-1"></i> Money Receipt No.</label>
                        <input type="text" name="money_receipt_no" class="form-control" placeholder="Enter money receipt number">
                    </div>
                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="sr_send_sms" name="send_sms" value="1" checked>
                            <label class="custom-control-label" for="sr_send_sms">
                                <i class="fas fa-sms mr-1"></i> Send SMS notification
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-check mr-1"></i> Collect</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('#payment_method').on('change', function() {
        var isCash = $(this).val().toLowerCase().indexOf('cash') !== -1;
        $('#sr_cash_receipt_field').toggle(isCash);
        if (!isCash) $('#sr_cash_receipt_field').find('input').val('');
    }).trigger('change');
});
</script>
@endpush