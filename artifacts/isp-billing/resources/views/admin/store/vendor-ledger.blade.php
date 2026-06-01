@extends('layouts.admin')
@section('title', 'Vendor Ledger - ' . $vendor->name)
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.store.purchases') }}">Store</a></li>
<li class="breadcrumb-item"><a href="{{ route('admin.purchase.vendors') }}">Vendors</a></li>
<li class="breadcrumb-item active">{{ $vendor->name }} - Ledger</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-4">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-truck mr-2"></i>Vendor Info</h3>
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tr><td class="text-muted" style="width:40%">Name</td><td><strong>{{ $vendor->name }}</strong></td></tr>
                    @if($vendor->company)<tr><td class="text-muted">Company</td><td>{{ $vendor->company }}</td></tr>@endif
                    @if($vendor->contact_person)<tr><td class="text-muted">Contact</td><td>{{ $vendor->contact_person }}</td></tr>@endif
                    @if($vendor->phone)<tr><td class="text-muted">Phone</td><td>{{ $vendor->phone }}</td></tr>@endif
                    @if($vendor->email)<tr><td class="text-muted">Email</td><td>{{ $vendor->email }}</td></tr>@endif
                    @if($vendor->address)<tr><td class="text-muted">Address</td><td>{{ $vendor->address }}</td></tr>@endif
                    <tr><td class="text-muted">Status</td><td><span class="badge badge-{{ $vendor->is_active ? 'success' : 'danger' }}">{{ $vendor->is_active ? 'Active' : 'Inactive' }}</span></td></tr>
                </table>
            </div>
        </div>

        <div class="card card-info card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-chart-bar mr-2"></i>Summary</h3>
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tr><td class="text-muted">Total Purchases</td><td class="text-right"><strong>{{ $vendor->purchases()->count() }}</strong></td></tr>
                    <tr><td class="text-muted">Purchase Amount</td><td class="text-right"><strong>৳{{ number_format($totalCredit, 2) }}</strong></td></tr>
                    <tr><td class="text-muted">Total Paid</td><td class="text-right text-success"><strong>৳{{ number_format($totalDebit, 2) }}</strong></td></tr>
                    <tr>
                        <td class="text-muted">Current Balance</td>
                        <td class="text-right">
                            <strong class="{{ ($vendor->balance ?? 0) > 0 ? 'text-danger' : (($vendor->balance ?? 0) < 0 ? 'text-success' : '') }}">
                                ৳{{ number_format(abs($vendor->balance ?? 0), 2) }}
                                @if(($vendor->balance ?? 0) > 0) (Due) @elseif(($vendor->balance ?? 0) < 0) (Advance) @endif
                            </strong>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="card card-success card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-money-bill-wave mr-2"></i>Record Payment</h3>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.purchase.vendors.payment', $vendor) }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label>Amount <span class="text-danger">*</span></label>
                        <input type="number" name="amount" class="form-control" step="0.01" min="0.01" required placeholder="Payment amount">
                    </div>
                    <div class="form-group">
                        <label>Payment Method</label>
                        <select name="payment_method" class="form-control">
                            <option value="cash">Cash</option>
                            <option value="bank">Bank Transfer</option>
                            <option value="bkash">bKash</option>
                            <option value="nagad">Nagad</option>
                            <option value="cheque">Cheque</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Reference</label>
                        <input type="text" name="reference" class="form-control" placeholder="Transaction/cheque no.">
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="Payment notes"></textarea>
                    </div>
                    <button type="submit" class="btn btn-success btn-block"><i class="fas fa-check mr-1"></i> Record Payment</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h3 class="card-title"><i class="fas fa-book mr-2"></i>Ledger Entries</h3>
                <span class="badge badge-primary">{{ $ledgerEntries->total() }} entries</span>
            </div>
            <div class="card-body p-0">
                <div class="d-none d-md-block">
                    <table class="table table-hover table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Description</th>
                                <th class="text-right">Debit (৳)</th>
                                <th class="text-right">Credit (৳)</th>
                                <th class="text-right">Balance (৳)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($ledgerEntries as $entry)
                            <tr>
                                <td>{{ $entry->transaction_date ? $entry->transaction_date->format('d-m-Y') : '-' }}</td>
                                <td>
                                    @if($entry->type === 'purchase')
                                        <span class="badge badge-warning">Purchase</span>
                                    @elseif($entry->type === 'payment')
                                        <span class="badge badge-success">Payment</span>
                                    @elseif($entry->type === 'opening')
                                        <span class="badge badge-info">Opening</span>
                                    @else
                                        <span class="badge badge-secondary">{{ ucfirst($entry->type) }}</span>
                                    @endif
                                </td>
                                <td>
                                    {{ $entry->description }}
                                    @if($entry->reference)<br><small class="text-muted">Ref: {{ $entry->reference }}</small>@endif
                                </td>
                                <td class="text-right">{{ $entry->debit > 0 ? number_format($entry->debit, 2) : '-' }}</td>
                                <td class="text-right">{{ $entry->credit > 0 ? number_format($entry->credit, 2) : '-' }}</td>
                                <td class="text-right">
                                    <strong class="{{ $entry->balance > 0 ? 'text-danger' : ($entry->balance < 0 ? 'text-success' : '') }}">
                                        ৳{{ number_format(abs($entry->balance), 2) }}
                                    </strong>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center py-4">
                                    <i class="fas fa-book fa-3x text-muted mb-3 d-block"></i>
                                    <p class="text-muted">No ledger entries yet.</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="d-md-none p-3">
                    @forelse($ledgerEntries as $entry)
                    <div class="card mb-2">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-start mb-1">
                                <small class="text-muted">{{ $entry->transaction_date ? $entry->transaction_date->format('d-m-Y') : '-' }}</small>
                                @if($entry->type === 'purchase')
                                    <span class="badge badge-warning">Purchase</span>
                                @elseif($entry->type === 'payment')
                                    <span class="badge badge-success">Payment</span>
                                @elseif($entry->type === 'opening')
                                    <span class="badge badge-info">Opening</span>
                                @else
                                    <span class="badge badge-secondary">{{ ucfirst($entry->type) }}</span>
                                @endif
                            </div>
                            <div class="small">{{ $entry->description }}</div>
                            @if($entry->reference)<div class="small text-muted">Ref: {{ $entry->reference }}</div>@endif
                            <div class="d-flex justify-content-between mt-2">
                                <span>Debit: <strong>{{ $entry->debit > 0 ? '৳' . number_format($entry->debit, 2) : '-' }}</strong></span>
                                <span>Credit: <strong>{{ $entry->credit > 0 ? '৳' . number_format($entry->credit, 2) : '-' }}</strong></span>
                            </div>
                            <div class="text-right mt-1">
                                Balance: <strong class="{{ $entry->balance > 0 ? 'text-danger' : ($entry->balance < 0 ? 'text-success' : '') }}">৳{{ number_format(abs($entry->balance), 2) }}</strong>
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-4">
                        <i class="fas fa-book fa-3x text-muted mb-3 d-block"></i>
                        <p class="text-muted">No ledger entries yet.</p>
                    </div>
                    @endforelse
                </div>
            </div>
            <div class="card-footer">
                {{ $ledgerEntries->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
