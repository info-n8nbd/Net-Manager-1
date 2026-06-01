@extends('layouts.admin')

@section('title', 'Loan Statement — ' . $loan->reference_no)
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.accounts.cash') }}">Accounts</a></li>
<li class="breadcrumb-item"><a href="{{ route('admin.accounts.loan') }}">Loan Management</a></li>
<li class="breadcrumb-item"><a href="{{ route('admin.accounts.loan.show', $loan) }}">{{ $loan->reference_no }}</a></li>
<li class="breadcrumb-item active">Statement</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-md-10">
        <div class="no-print mb-3 text-right">
            <a href="{{ route('admin.accounts.loan.show', $loan) }}" class="btn btn-default"><i class="fas fa-arrow-left mr-1"></i>Back to Details</a>
            <button onclick="window.print()" class="btn btn-primary"><i class="fas fa-print mr-1"></i>Print Statement</button>
        </div>

        <div class="card" id="printArea">
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-6">
                        @if($company)
                        <h4 class="mb-1">{{ $company->name ?? 'Company Name' }}</h4>
                        <p class="text-muted mb-0">{{ $company->address ?? '' }}</p>
                        <p class="text-muted mb-0">{{ $company->phone ?? '' }}</p>
                        @else
                        <h4 class="mb-1">SOFTYNEX</h4>
                        @endif
                    </div>
                    <div class="col-6 text-right">
                        <h3 class="text-primary mb-1">LOAN STATEMENT</h3>
                        <p class="text-muted mb-0">Date: {{ now()->format('d-M-Y') }}</p>
                        <p class="mb-0"><strong>{{ $loan->reference_no }}</strong></p>
                    </div>
                </div>

                <hr>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <table class="table table-sm table-borderless">
                            <tr>
                                <td class="text-muted" width="130">Loan Type</td>
                                <td><strong>{{ $loan->type === 'received' ? 'Loan Received' : 'Loan Given' }}</strong></td>
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
                        <table class="table table-sm table-borderless">
                            <tr>
                                <td class="text-muted" width="130">Loan Date</td>
                                <td>{{ $loan->loan_date->format('d-M-Y') }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Due Date</td>
                                <td>{{ $loan->due_date ? $loan->due_date->format('d-M-Y') : 'Not set' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Loan Amount</td>
                                <td><strong class="text-lg">৳{{ number_format($loan->amount, 2) }}</strong></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Status</td>
                                <td>
                                    @if($loan->status === 'completed')
                                    <span class="badge badge-success px-2">Completed</span>
                                    @elseif($loan->status === 'partial')
                                    <span class="badge badge-warning px-2">Partial</span>
                                    @else
                                    <span class="badge badge-info px-2">Pending</span>
                                    @endif
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                @if($loan->description)
                <div class="callout callout-info py-2 mb-4">
                    <small class="text-muted">Note:</small> {{ $loan->description }}
                </div>
                @endif

                <h5 class="mb-3"><i class="fas fa-list mr-2"></i>Payment Details</h5>
                <table class="table table-bordered table-sm">
                    <thead class="thead-light">
                        <tr>
                            <th width="50">#</th>
                            <th>Date</th>
                            <th class="text-right">Payment Amount</th>
                            <th class="text-right">Running Balance</th>
                            <th>Account</th>
                            <th>Remarks</th>
                            <th>Recorded By</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="bg-light">
                            <td>-</td>
                            <td>{{ $loan->loan_date->format('d-M-Y') }}</td>
                            <td class="text-right">-</td>
                            <td class="text-right font-weight-bold">৳{{ number_format($loan->amount, 2) }}</td>
                            <td>{{ $loan->account->name ?? '-' }}</td>
                            <td><em>Loan Initiated</em></td>
                            <td>{{ $loan->creator->name ?? '-' }}</td>
                        </tr>
                        @forelse($paymentsWithBalance as $index => $payment)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $payment->payment_date->format('d-M-Y') }}</td>
                            <td class="text-right text-success">৳{{ number_format($payment->amount, 2) }}</td>
                            <td class="text-right font-weight-bold {{ $payment->running_balance <= 0 ? 'text-success' : 'text-danger' }}">
                                ৳{{ number_format($payment->running_balance, 2) }}
                            </td>
                            <td>{{ $payment->account->name ?? '-' }}</td>
                            <td>{{ $payment->remarks ?: '-' }}</td>
                            <td>{{ $payment->creator->name ?? '-' }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-3">No payments recorded</td>
                        </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="bg-light font-weight-bold">
                        <tr>
                            <td colspan="2" class="text-right">Summary:</td>
                            <td class="text-right text-success">৳{{ number_format($loan->paid_amount, 2) }}</td>
                            <td class="text-right {{ $loan->remaining_amount <= 0 ? 'text-success' : 'text-danger' }}">
                                ৳{{ number_format($loan->remaining_amount, 2) }}
                            </td>
                            <td colspan="3"></td>
                        </tr>
                    </tfoot>
                </table>

                <div class="row mt-4">
                    <div class="col-4 text-center">
                        <div class="border-top pt-2 mx-4">
                            <small class="text-muted">Total Loan Amount</small>
                            <h5>৳{{ number_format($loan->amount, 2) }}</h5>
                        </div>
                    </div>
                    <div class="col-4 text-center">
                        <div class="border-top pt-2 mx-4">
                            <small class="text-muted">Total Paid</small>
                            <h5 class="text-success">৳{{ number_format($loan->paid_amount, 2) }}</h5>
                        </div>
                    </div>
                    <div class="col-4 text-center">
                        <div class="border-top pt-2 mx-4">
                            <small class="text-muted">Outstanding Balance</small>
                            <h5 class="{{ $loan->remaining_amount <= 0 ? 'text-success' : 'text-danger' }}">৳{{ number_format($loan->remaining_amount, 2) }}</h5>
                        </div>
                    </div>
                </div>

                <div class="row mt-5">
                    <div class="col-6">
                        <div class="border-top pt-2 mt-4 mx-4 text-center">
                            <small class="text-muted">Party Signature</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="border-top pt-2 mt-4 mx-4 text-center">
                            <small class="text-muted">Authorized Signature</small>
                        </div>
                    </div>
                </div>

                <div class="text-center mt-4">
                    <small class="text-muted">This is a computer-generated statement. Generated on {{ now()->format('d-M-Y h:i A') }}</small>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    .no-print, .main-sidebar, .main-header, .main-footer, .content-header, .breadcrumb {
        display: none !important;
    }
    .content-wrapper {
        margin-left: 0 !important;
        padding: 0 !important;
    }
    .card {
        border: none !important;
        box-shadow: none !important;
    }
    body {
        font-size: 12px;
    }
}
</style>
@endsection
