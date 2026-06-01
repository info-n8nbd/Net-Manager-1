@extends('layouts.admin')

@section('title', 'Loan Details — ' . $loan->reference_no)
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.accounts.cash') }}">Accounts</a></li>
<li class="breadcrumb-item"><a href="{{ route('admin.accounts.loan') }}">Loan Management</a></li>
<li class="breadcrumb-item active">{{ $loan->reference_no }}</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card {{ $isOverdue ? 'card-danger' : ($loan->status === 'completed' ? 'card-success' : 'card-primary') }} card-outline">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-file-invoice-dollar mr-2"></i>Loan Details — <strong>{{ $loan->reference_no }}</strong>
                </h3>
                <div class="card-tools">
                    <a href="{{ route('admin.accounts.loan.statement', $loan) }}" class="btn btn-sm btn-outline-secondary"><i class="fas fa-print mr-1"></i>Statement</a>
                    <a href="{{ route('admin.accounts.loan.edit', $loan) }}" class="btn btn-sm btn-outline-warning"><i class="fas fa-edit mr-1"></i>Edit</a>
                    @if($loan->paid_amount <= 0)
                    <form action="{{ route('admin.accounts.loan.destroy', $loan) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this loan?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash mr-1"></i>Delete</button>
                    </form>
                    @endif
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-borderless table-sm">
                            <tr>
                                <td class="text-muted" width="140">Type</td>
                                <td>
                                    <span class="badge badge-{{ $loan->type === 'received' ? 'success' : 'danger' }} px-3 py-1">
                                        {{ $loan->type === 'received' ? '↓ Loan Received' : '↑ Loan Given' }}
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted">Party Name</td>
                                <td><strong>{{ $loan->party_name }}</strong></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Party Phone</td>
                                <td>{{ $loan->party_phone ?: '-' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Account</td>
                                <td>{{ $loan->account->name ?? '-' }}</td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-borderless table-sm">
                            <tr>
                                <td class="text-muted" width="140">Loan Date</td>
                                <td>{{ $loan->loan_date->format('d-M-Y') }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Due Date</td>
                                <td>
                                    @if($loan->due_date)
                                    <span class="{{ $isOverdue ? 'text-danger font-weight-bold' : '' }}">
                                        {{ $loan->due_date->format('d-M-Y') }}
                                        @if($isOverdue)
                                        <i class="fas fa-exclamation-triangle ml-1"></i> ({{ $loan->due_date->diffForHumans() }})
                                        @endif
                                    </span>
                                    @else
                                    <span class="text-muted">Not set</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted">Status</td>
                                <td>
                                    @if($isOverdue)
                                    <span class="badge badge-danger px-3 py-1"><i class="fas fa-exclamation-circle"></i> Overdue</span>
                                    @elseif($loan->status === 'completed')
                                    <span class="badge badge-success px-3 py-1"><i class="fas fa-check-circle"></i> Completed</span>
                                    @elseif($loan->status === 'partial')
                                    <span class="badge badge-warning px-3 py-1"><i class="fas fa-clock"></i> Partial Payment</span>
                                    @else
                                    <span class="badge badge-info px-3 py-1"><i class="fas fa-hourglass-start"></i> Pending</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted">Created By</td>
                                <td>{{ $loan->creator->name ?? '-' }}</td>
                            </tr>
                        </table>
                    </div>
                </div>

                @if($loan->description)
                <div class="callout callout-info py-2 mt-2">
                    <small class="text-muted">Description:</small><br>
                    {{ $loan->description }}
                </div>
                @endif

                <div class="row mt-3">
                    <div class="col-md-4 text-center">
                        <div class="info-box bg-light shadow-sm">
                            <div class="info-box-content">
                                <span class="info-box-text text-muted">Loan Amount</span>
                                <span class="info-box-number text-lg">৳{{ number_format($loan->amount, 2) }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 text-center">
                        <div class="info-box bg-light shadow-sm">
                            <div class="info-box-content">
                                <span class="info-box-text text-success">Paid Amount</span>
                                <span class="info-box-number text-lg text-success">৳{{ number_format($loan->paid_amount, 2) }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 text-center">
                        <div class="info-box bg-light shadow-sm">
                            <div class="info-box-content">
                                <span class="info-box-text text-danger">Remaining</span>
                                <span class="info-box-number text-lg text-danger">৳{{ number_format($loan->remaining_amount, 2) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="progress mt-2" style="height: 25px;">
                    <div class="progress-bar {{ $paidPercent >= 100 ? 'bg-success' : ($paidPercent >= 50 ? 'bg-info' : 'bg-warning') }}"
                         style="width: {{ $paidPercent }}%">
                        {{ $paidPercent }}% Paid
                    </div>
                </div>
            </div>
        </div>

        <div class="card card-outline card-secondary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-history mr-2"></i>Payment History ({{ $loan->payments->count() }} payments)</h3>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover table-striped table-sm">
                    <thead class="thead-light">
                        <tr>
                            <th>#</th>
                            <th>Date</th>
                            <th class="text-right">Amount</th>
                            <th>Account</th>
                            <th>Remarks</th>
                            <th>Recorded By</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($loan->payments as $index => $payment)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $payment->payment_date->format('d-M-Y') }}</td>
                            <td class="text-right text-success font-weight-bold">৳{{ number_format($payment->amount, 2) }}</td>
                            <td>{{ $payment->account->name ?? '-' }}</td>
                            <td>{{ $payment->remarks ?: '-' }}</td>
                            <td>{{ $payment->creator->name ?? '-' }}</td>
                            <td class="text-center">
                                <form action="{{ route('admin.accounts.loan.payment.destroy', [$loan, $payment]) }}" method="POST"
                                      onsubmit="return confirm('Remove this payment of ৳{{ number_format($payment->amount, 2) }}?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-xs btn-outline-danger" title="Remove Payment"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-3">No payments recorded yet</td>
                        </tr>
                        @endforelse
                    </tbody>
                    @if($loan->payments->count() > 0)
                    <tfoot class="bg-light">
                        <tr>
                            <th colspan="2" class="text-right">Total Paid:</th>
                            <th class="text-right text-success">৳{{ number_format($loan->paid_amount, 2) }}</th>
                            <th colspan="4"></th>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        @if($loan->status !== 'completed')
        <div class="card card-success card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-money-bill-wave mr-2"></i>Record Payment</h3>
            </div>
            <form action="{{ route('admin.accounts.loan.payment', $loan) }}" method="POST">
                @csrf
                <div class="card-body">
                    <div class="callout callout-warning py-2 mb-3">
                        <small>Remaining Balance</small>
                        <h4 class="text-danger mb-0">৳{{ number_format($loan->remaining_amount, 2) }}</h4>
                    </div>
                    <div class="form-group">
                        <label>Payment Amount (৳) <span class="text-danger">*</span></label>
                        <input type="number" name="amount" class="form-control" step="0.01" min="0.01"
                               max="{{ $loan->remaining_amount }}" required>
                    </div>
                    <div class="form-group">
                        <label>Payment Date <span class="text-danger">*</span></label>
                        <input type="date" name="payment_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="form-group">
                        <label>Account <span class="text-danger">*</span></label>
                        <select name="account_id" class="form-control" required>
                            @foreach($accounts as $account)
                            <option value="{{ $account->id }}">{{ $account->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group mb-0">
                        <label>Remarks</label>
                        <textarea name="remarks" class="form-control" rows="2" placeholder="Payment note..."></textarea>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-success btn-block"><i class="fas fa-check mr-1"></i>Record Payment</button>
                    <button type="button" class="btn btn-outline-success btn-block mt-1" onclick="this.form.amount.value='{{ $loan->remaining_amount }}'">
                        <i class="fas fa-money-check-alt mr-1"></i>Pay Full Amount
                    </button>
                </div>
            </form>
        </div>
        @else
        <div class="card card-success">
            <div class="card-body text-center py-5">
                <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
                <h4 class="text-success">Fully Paid!</h4>
                <p class="text-muted">This loan has been completely repaid.</p>
                <p class="text-muted mb-0">
                    Completed in {{ $loan->payments->count() }} payment(s)
                </p>
            </div>
        </div>
        @endif

        <div class="card card-outline card-info">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-info-circle mr-2"></i>Quick Actions</h3>
            </div>
            <div class="card-body p-2">
                <a href="{{ route('admin.accounts.loan.statement', $loan) }}" class="btn btn-default btn-block text-left mb-1">
                    <i class="fas fa-print mr-2 text-secondary"></i>Print Statement
                </a>
                <a href="{{ route('admin.accounts.loan.edit', $loan) }}" class="btn btn-default btn-block text-left mb-1">
                    <i class="fas fa-edit mr-2 text-warning"></i>Edit Loan Info
                </a>
                <a href="{{ route('admin.accounts.loan') }}" class="btn btn-default btn-block text-left mb-0">
                    <i class="fas fa-arrow-left mr-2 text-info"></i>Back to Loan List
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
