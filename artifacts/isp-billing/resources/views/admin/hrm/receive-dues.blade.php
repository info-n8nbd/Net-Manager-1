@extends('layouts.admin')

@section('title', 'Receive Dues - ' . $user->name)
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
<li class="breadcrumb-item"><a href="{{ route('admin.hrm.staff-dues') }}">Staff Dues</a></li>
<li class="breadcrumb-item active">Receive from {{ $user->name }}</li>
@endsection

@section('content')
<div class="row">
    <div class="col-lg-6">
        <div class="card card-success">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-plus-circle mr-2"></i>Receive Dues from {{ $user->name }}</h3>
            </div>
            <form action="{{ route('admin.hrm.store-receipt', $user) }}" method="POST">
                @csrf
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-4">
                            <div class="stat-box bg-primary text-white text-center py-2 rounded">
                                <div class="stat-number">৳{{ number_format($totalCollected, 0) }}</div>
                                <div class="stat-label">Collected</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="stat-box bg-success text-white text-center py-2 rounded">
                                <div class="stat-number">৳{{ number_format($receipted, 0) }}</div>
                                <div class="stat-label">Receipted</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="stat-box bg-danger text-white text-center py-2 rounded">
                                <div class="stat-number">৳{{ number_format($currentDue, 0) }}</div>
                                <div class="stat-label">Due</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Amount <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">৳</span>
                                    </div>
                                    <input type="number" name="amount" class="form-control @error('amount') is-invalid @enderror" value="{{ old('amount', $currentDue) }}" max="{{ $currentDue }}" required>
                                    @error('amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <small class="text-muted">Max: ৳{{ number_format($currentDue, 0) }}</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Receipt Date <span class="text-danger">*</span></label>
                                <input type="date" name="receipt_date" class="form-control @error('receipt_date') is-invalid @enderror" value="{{ old('receipt_date', date('Y-m-d')) }}" required>
                                @error('receipt_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Payment Method <span class="text-danger">*</span></label>
                        <select name="payment_method" class="form-control" required>
                            <option value="cash" selected>Cash</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="bkash">bKash</option>
                            <option value="nagad">Nagad</option>
                            <option value="rocket">Rocket</option>
                        </select>
                    </div>
                    <div class="form-group" id="hrmCashReceiptField" style="display: none;">
                        <label><i class="fas fa-receipt mr-1"></i> Money Receipt No.</label>
                        <input type="text" name="money_receipt_no" class="form-control" placeholder="Enter money receipt number" value="{{ old('money_receipt_no') }}">
                    </div>
                    <div class="form-group">
                        <label>Remarks</label>
                        <textarea name="remarks" class="form-control" rows="2" placeholder="Optional notes...">{{ old('remarks') }}</textarea>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-success"><i class="fas fa-check mr-2"></i>Create Receipt</button>
                    <a href="{{ route('admin.hrm.staff-dues') }}" class="btn btn-secondary"><i class="fas fa-arrow-left mr-2"></i>Cancel</a>
                </div>
            </form>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card card-info card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-history mr-2"></i>Recent Collections by {{ $user->name }}</h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Client</th>
                                <th class="text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentPayments as $payment)
                            <tr>
                                <td>{{ $payment->payment_date->format('d-m-Y') }}</td>
                                <td>{{ $payment->client->name ?? 'N/A' }}</td>
                                <td class="text-right">৳{{ number_format($payment->amount, 0) }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted py-3">No recent collections</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    function toggleHrmCashReceipt() {
        var val = $('select[name="payment_method"]').val();
        var isCash = val === 'cash';
        if (isCash) {
            $('#hrmCashReceiptField').show();
        } else {
            $('#hrmCashReceiptField').hide();
            $('#hrmCashReceiptField input').val('');
        }
    }
    $('select[name="payment_method"]').on('change', toggleHrmCashReceipt);
    toggleHrmCashReceipt();
});
</script>
@endpush
