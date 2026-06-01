@extends('layouts.admin')
@section('title', 'Referral Partner Ledger - ' . $agent->name)

@section('content')
<div class="row mb-3">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>৳{{ number_format($totalEarned, 2) }}</h3>
                <p>Total Commission Earned</p>
            </div>
            <div class="icon"><i class="fas fa-arrow-circle-down"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-danger">
            <div class="inner">
                <h3>৳{{ number_format($totalPaid, 2) }}</h3>
                <p>Total Paid Out</p>
            </div>
            <div class="icon"><i class="fas fa-arrow-circle-up"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box {{ $currentBalance > 0 ? 'bg-warning' : 'bg-secondary' }}">
            <div class="inner">
                <h3>৳{{ number_format($currentBalance, 2) }}</h3>
                <p>Current Balance (Due)</p>
            </div>
            <div class="icon"><i class="fas fa-wallet"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>{{ $entries->where('type', 'commission')->count() }}</h3>
                <p>Total Commission Entries</p>
            </div>
            <div class="icon"><i class="fas fa-file-invoice"></i></div>
        </div>
    </div>
</div>

<div class="card card-outline card-info">
    <div class="card-header d-flex align-items-center">
        <h3 class="card-title"><i class="fas fa-book mr-2"></i>Referral Partner Ledger - {{ $agent->name }}</h3>
        <div class="ml-auto">
            <button class="btn btn-success btn-sm mr-1" data-toggle="modal" data-target="#payAgentModal" {{ $currentBalance <= 0 ? 'disabled' : '' }}>
                <i class="fas fa-hand-holding-usd mr-1"></i> PAY PARTNER
            </button>
            <a href="{{ route('admin.agent.commission', $agent->id) }}" class="btn btn-secondary btn-sm"><i class="fas fa-coins mr-1"></i> Commission</a>
            <a href="{{ route('admin.agent.ledgers') }}" class="btn btn-outline-secondary btn-sm ml-1"><i class="fas fa-list mr-1"></i> All Partners</a>
            <button class="btn btn-danger btn-sm ml-1" onclick="window.print()"><i class="fas fa-print mr-1"></i> PRINT</button>
        </div>
    </div>
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-3"><strong>Partner ID:</strong> {{ $agent->agent_id }}</div>
            <div class="col-md-3"><strong>Name:</strong> {{ $agent->name }}</div>
            <div class="col-md-3"><strong>Contact:</strong> {{ $agent->personal_contact ?? '-' }}</div>
            <div class="col-md-3"><strong>Commission Rate:</strong> {{ number_format($agent->default_commission, 2) }}%</div>
        </div>

        <h5 class="mb-3">Monthly Summary</h5>
        <div class="table-responsive mb-4">
            <table class="table table-bordered table-striped">
                <thead class="bg-info text-white">
                    <tr>
                        <th>Month</th>
                        <th>Entries</th>
                        <th>Total Commission (TK)</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($monthlyTotals as $mt)
                    <tr>
                        <td>{{ \Carbon\Carbon::createFromDate($mt->year, $mt->month, 1)->format('F Y') }}</td>
                        <td>{{ $mt->entries }}</td>
                        <td><strong>{{ number_format($mt->total, 2) }}</strong></td>
                        <td>
                            <a href="{{ route('admin.agent.commission', ['id' => $agent->id, 'month' => $mt->month, 'year' => $mt->year]) }}" class="btn btn-info btn-xs">
                                <i class="fas fa-eye mr-1"></i> View
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="text-center text-muted">No commission records found</td>
                    </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="bg-light">
                        <td colspan="2" class="text-right"><strong>Grand Total</strong></td>
                        <td><strong class="text-success">{{ number_format($totalEarned, 2) }}</strong></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <h5 class="mb-3">Ledger History</h5>
        <div class="table-responsive">
            <table id="ledgerTable" class="table table-bordered table-hover mb-0">
                <thead class="bg-dark text-white">
                    <tr>
                        <th>#</th>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Client / Description</th>
                        <th>Purpose</th>
                        <th class="text-right">Credit (TK)</th>
                        <th class="text-right">Debit (TK)</th>
                        <th class="text-right">Balance (TK)</th>
                        <th>Note</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($entriesDesc as $i => $e)
                    <tr class="{{ $e->type === 'payout' ? 'table-danger' : '' }}">
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $e->date ? $e->date->format('d-m-Y') : '-' }}</td>
                        <td>
                            @if($e->type === 'commission')
                                <span class="badge badge-success"><i class="fas fa-arrow-down mr-1"></i>Commission</span>
                            @else
                                <span class="badge badge-danger"><i class="fas fa-arrow-up mr-1"></i>Paid Out</span>
                            @endif
                        </td>
                        <td>
                            @if($e->type === 'commission')
                                <strong>{{ $e->client_name }}</strong>
                                @if($e->client_pppoe_id)
                                    <br><small class="text-muted">{{ $e->client_pppoe_id }}</small>
                                @endif
                            @else
                                <strong>Partner Payment</strong>
                            @endif
                        </td>
                        <td>{{ $e->purpose ?? '-' }}</td>
                        <td class="text-right">
                            @if($e->type === 'commission')
                                <strong class="text-success">৳{{ number_format($e->commission_amount, 2) }}</strong>
                                <br><small class="text-muted">{{ number_format($e->payment_amount, 2) }} x {{ number_format($e->commission_percent, 2) }}%</small>
                            @else
                                -
                            @endif
                        </td>
                        <td class="text-right">
                            @if($e->type === 'payout')
                                <strong class="text-danger">৳{{ number_format($e->commission_amount, 2) }}</strong>
                            @else
                                -
                            @endif
                        </td>
                        <td class="text-right">
                            <strong class="{{ $e->running_balance > 0 ? 'text-primary' : 'text-secondary' }}">৳{{ number_format($e->running_balance, 2) }}</strong>
                        </td>
                        <td style="max-width: 200px; font-size: 12px;">{{ $e->note ?? '-' }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="bg-light font-weight-bold">
                        <td colspan="5" class="text-right">Summary</td>
                        <td class="text-right text-success">৳{{ number_format($totalEarned, 2) }}</td>
                        <td class="text-right text-danger">৳{{ number_format($totalPaid, 2) }}</td>
                        <td class="text-right text-primary">৳{{ number_format($currentBalance, 2) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    <div class="card-footer text-right">
        <button class="btn btn-danger btn-sm" onclick="window.print()"><i class="fas fa-print mr-1"></i> PRINT</button>
    </div>
</div>

<div class="modal fade" id="payAgentModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.agent.pay', $agent->id) }}" method="POST">
                @csrf
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="fas fa-hand-holding-usd mr-1"></i> Pay Referral Partner - {{ $agent->name }}</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info mb-3">
                        <strong>Available Balance:</strong> ৳{{ number_format($currentBalance, 2) }}
                    </div>
                    <div class="form-group">
                        <label>Payment Amount (TK) <span class="text-danger">*</span></label>
                        <input type="number" name="amount" class="form-control" step="0.01" min="0.01" max="{{ $currentBalance }}" required placeholder="Enter amount to pay">
                    </div>
                    <div class="form-group">
                        <label>Date <span class="text-danger">*</span></label>
                        <input type="date" name="date" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="form-group mb-0">
                        <label>Note</label>
                        <textarea name="note" class="form-control" rows="2" placeholder="Payment note (optional)"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success"><i class="fas fa-check mr-1"></i> Confirm Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    if ($('#ledgerTable tbody tr').length > 0) {
        $('#ledgerTable').DataTable({
            "responsive": true,
            "autoWidth": false,
            "order": [],
            "pageLength": 50,
            "ordering": false
        });
    }
});
</script>
@endpush
