@extends('layouts.reseller')
@section('title', 'Bills')
@section('breadcrumb')<li class="breadcrumb-item active">Bills</li>@endsection

@section('content')
<div class="row">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner"><h3>{{ $totalBills }}</h3><p>Total Bills</p></div>
            <div class="icon"><i class="fas fa-file-invoice"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner"><h3>{{ $paidBills }}</h3><p>Paid</p></div>
            <div class="icon"><i class="fas fa-check-circle"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning">
            <div class="inner"><h3>{{ $partialBills }}</h3><p>Partial</p></div>
            <div class="icon"><i class="fas fa-clock"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-danger">
            <div class="inner"><h3>{{ $unpaidBills }}</h3><p>Unpaid</p></div>
            <div class="icon"><i class="fas fa-exclamation-circle"></i></div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Client Bills</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#generateBillsModal">
                <i class="fas fa-plus mr-1"></i> Generate Bills
            </button>
        </div>
    </div>
    <div class="card-body">
        <form method="GET" class="mb-3">
            <div class="row">
                <div class="col-md-3">
                    <select name="month" class="form-control">
                        <option value="">All Months</option>
                        @for($i = 1; $i <= 12; $i++)
                        <option value="{{ $i }}" {{ request('month') == $i ? 'selected' : '' }}>{{ date('F', mktime(0,0,0,$i,1)) }}</option>
                        @endfor
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="year" class="form-control">
                        <option value="">All Years</option>
                        @for($y = now()->year; $y >= now()->year - 2; $y--)
                        <option value="{{ $y }}" {{ request('year') == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-control">
                        <option value="">All Status</option>
                        <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Paid</option>
                        <option value="partial" {{ request('status') == 'partial' ? 'selected' : '' }}>Partial</option>
                        <option value="unpaid" {{ request('status') == 'unpaid' ? 'selected' : '' }}>Unpaid</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-info">Filter</button>
                    <a href="{{ route('reseller.bills') }}" class="btn btn-secondary">Reset</a>
                </div>
            </div>
        </form>
        
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Bill No</th>
                        <th>Client</th>
                        <th>Period</th>
                        <th>Amount</th>
                        <th>Paid</th>
                        <th>Due</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($bills as $bill)
                    <tr>
                        <td>{{ $bill->bill_no }}</td>
                        <td><a href="{{ route('reseller.clients.show', $bill->client) }}">{{ $bill->client->name ?? 'N/A' }}</a></td>
                        <td>{{ $bill->billing_month }}/{{ $bill->billing_year }}</td>
                        <td>৳{{ number_format($bill->total_amount ?? 0, 2) }}</td>
                        <td class="text-success">৳{{ number_format($bill->paid_amount ?? 0, 2) }}</td>
                        <td class="text-danger">৳{{ number_format($bill->due_amount ?? 0, 2) }}</td>
                        <td>
                            @if($bill->status == 'paid')
                            <span class="badge badge-success">Paid</span>
                            @elseif($bill->status == 'partial')
                            <span class="badge badge-warning">Partial</span>
                            @else
                            <span class="badge badge-danger">Unpaid</span>
                            @endif
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('reseller.clients.show', $bill->client) }}" class="btn btn-info" title="View Client">
                                    <i class="fas fa-eye"></i>
                                </a>
                                @if($bill->status != 'paid')
                                <button type="button" class="btn btn-success collect-payment-btn" 
                                        data-toggle="modal" data-target="#collectPaymentModal"
                                        data-client-id="{{ $bill->client_id }}"
                                        data-client-name="{{ $bill->client->name ?? 'N/A' }}"
                                        data-bill-id="{{ $bill->id }}"
                                        data-bill-no="{{ $bill->bill_no }}"
                                        data-due="{{ $bill->due_amount }}"
                                        title="Collect Payment">
                                    <i class="fas fa-money-bill-wave"></i>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center">No bills found</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $bills->links() }}
    </div>
</div>

<div class="modal fade" id="generateBillsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('reseller.bills.generate') }}" method="POST">
                @csrf
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-file-invoice mr-2"></i>Generate Bills</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-1"></i> This will generate bills for all your active clients for the selected month.
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Month</label>
                                <select name="month" class="form-control" required>
                                    @for($i = 1; $i <= 12; $i++)
                                    <option value="{{ $i }}" {{ now()->month == $i ? 'selected' : '' }}>{{ date('F', mktime(0,0,0,$i,1)) }}</option>
                                    @endfor
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Year</label>
                                <select name="year" class="form-control" required>
                                    @for($y = now()->year; $y >= now()->year - 1; $y--)
                                    <option value="{{ $y }}" {{ now()->year == $y ? 'selected' : '' }}>{{ $y }}</option>
                                    @endfor
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-check mr-1"></i>Generate Bills
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="collectPaymentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="collectPaymentForm" method="POST">
                @csrf
                <input type="hidden" name="bill_id" id="cp_bill_id">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="fas fa-money-bill-wave mr-2"></i>Collect Payment</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info py-2">
                        <div class="row">
                            <div class="col-6">
                                <strong>Client:</strong> <span id="cp_client_name"></span><br>
                                <strong>Bill:</strong> <span id="cp_bill_no"></span>
                            </div>
                            <div class="col-6 text-right">
                                <strong>Due:</strong> <span class="text-danger font-weight-bold" id="cp_due_display"></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-money-bill mr-1"></i> Amount (৳)</label>
                        <input type="number" name="amount" id="cp_amount" class="form-control form-control-lg text-center" 
                               required min="1" step="0.01">
                        <small class="text-muted">Enter amount collected from client</small>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-credit-card mr-1"></i> Payment Method</label>
                        <select name="payment_method_id" class="form-control" required>
                            @foreach(auth()->user()->allowedPaymentMethods() as $method)
                            <option value="{{ $method->id }}" data-name="{{ strtolower($method->name) }}">{{ $method->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group" id="cp_cash_receipt_field" style="display: none;">
                        <label><i class="fas fa-receipt mr-1"></i> Money Receipt No.</label>
                        <input type="text" name="money_receipt_no" class="form-control" placeholder="Enter money receipt number">
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-sticky-note mr-1"></i> Notes (Optional)</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Payment notes..."></textarea>
                    </div>
                    
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="cp_send_sms" name="send_sms" value="1" checked>
                        <label class="custom-control-label" for="cp_send_sms">
                            <i class="fas fa-sms mr-1"></i> Send SMS notification
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check mr-1"></i>Submit Payment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('#collectPaymentModal').on('change', 'select[name="payment_method_id"]', function() {
        var methodName = ($(this).find('option:selected').data('name') || '').toLowerCase();
        var isCash = methodName.indexOf('cash') !== -1;
        $('#cp_cash_receipt_field').toggle(isCash);
        if (!isCash) $('#cp_cash_receipt_field').find('input').val('');
    });

    $('.collect-payment-btn').on('click', function() {
        var clientId = $(this).data('client-id');
        var clientName = $(this).data('client-name');
        var billId = $(this).data('bill-id');
        var billNo = $(this).data('bill-no');
        var due = $(this).data('due');
        
        $('#cp_client_name').text(clientName);
        $('#cp_bill_no').text('#' + billNo);
        $('#cp_bill_id').val(billId);
        $('#cp_due_display').text('৳' + parseFloat(due).toFixed(2));
        $('#cp_amount').val(Math.max(1, due));
        
        $('#collectPaymentForm').attr('action', '/reseller/clients/' + clientId + '/collect-payment');
    });
});
</script>
@endpush
