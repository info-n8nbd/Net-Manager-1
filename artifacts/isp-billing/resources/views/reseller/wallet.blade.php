@extends('layouts.reseller')

@section('title', 'Wallet')
@section('breadcrumb')
<li class="breadcrumb-item active">Wallet</li>
@endsection

@section('content')
<div class="row mb-2">
    <div class="col-4 mb-2">
        <div class="stat-box bg-success text-white text-center py-3 rounded h-100">
            <div class="stat-number">৳{{ number_format($reseller->wallet_balance ?? 0, 0) }}</div>
            <div class="stat-label">Balance</div>
        </div>
    </div>
    <div class="col-4 mb-2">
        <div class="stat-box bg-info text-white text-center py-3 rounded h-100">
            <div class="stat-number">৳{{ number_format($thisMonthCredits ?? 0, 0) }}</div>
            <div class="stat-label">Credits</div>
        </div>
    </div>
    <div class="col-4 mb-2">
        <div class="stat-box bg-warning text-dark text-center py-3 rounded h-100">
            <div class="stat-number">৳{{ number_format($thisMonthDebits ?? 0, 0) }}</div>
            <div class="stat-label">Debits</div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-primary">
                <h3 class="card-title">Account Info</h3>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Billing Type</span>
                        <span class="badge badge-{{ $reseller->billing_type == 'prepaid' ? 'info' : 'warning' }}">
                            {{ ucfirst($reseller->billing_type) }}
                        </span>
                    </li>
                    @if($reseller->billing_type == 'postpaid')
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Credit Limit</span>
                        <span>৳{{ number_format($reseller->credit_limit ?? 0, 2) }}</span>
                    </li>
                    @endif
                </ul>
                
                @if(isset($monthlyBill) && $monthlyBill)
                <div class="mt-3">
                    <h6 class="text-muted">Monthly Bill ({{ now()->format('M Y') }})</h6>
                    <div class="row">
                        <div class="col-4">
                            <div class="stat-box bg-info text-white text-center py-2 rounded">
                                <div class="stat-number" style="font-size: 14px;">৳{{ number_format($monthlyBill->total_recharge ?? 0, 0) }}</div>
                                <div class="stat-label">Recharge</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="stat-box bg-{{ $monthlyBill->status == 'paid' ? 'success' : ($monthlyBill->status == 'partial' ? 'warning' : 'danger') }} text-{{ $monthlyBill->status == 'partial' ? 'dark' : 'white' }} text-center py-2 rounded">
                                <div class="stat-number" style="font-size: 14px;">৳{{ number_format($monthlyBill->due_amount ?? 0, 0) }}</div>
                                <div class="stat-label">Due</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="stat-box bg-secondary text-white text-center py-2 rounded">
                                <div class="stat-number" style="font-size: 14px;">{{ ucfirst($monthlyBill->status) }}</div>
                                <div class="stat-label">Status</div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-exchange-alt mr-2"></i>Transaction History</h3>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Description</th>
                            <th class="text-right">Amount</th>
                            <th class="text-right">Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transactions as $txn)
                        <tr>
                            <td>{{ $txn->created_at->format('d-m-y h:i A') }}</td>
                            <td>
                                <span class="badge badge-{{ $txn->type == 'credit' ? 'success' : 'danger' }}">
                                    {{ ucfirst($txn->type) }}
                                </span>
                            </td>
                            <td>{{ $txn->description }}</td>
                            <td class="text-right {{ $txn->type == 'credit' ? 'text-success' : 'text-danger' }}">
                                {{ $txn->type == 'credit' ? '+' : '-' }}৳{{ number_format($txn->amount ?? 0, 2) }}
                            </td>
                            <td class="text-right">৳{{ number_format($txn->balance_after ?? 0, 2) }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">No transactions yet</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer">
                {{ $transactions->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
