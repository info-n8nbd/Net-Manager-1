@extends('layouts.admin')
@section('title', 'Referral Partner Ledger')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.agent.index') }}">Referral Partner</a></li>
<li class="breadcrumb-item active">Referral Partner Ledger</li>
@endsection

@section('content')
<div class="row mb-3">
    <div class="col-lg-4 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>{{ $agents->count() }}</h3>
                <p>Total Referral Partners</p>
            </div>
            <div class="icon"><i class="fas fa-user-tie"></i></div>
        </div>
    </div>
    <div class="col-lg-4 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>৳{{ number_format($grandTotal, 2) }}</h3>
                <p>Total Commission</p>
            </div>
            <div class="icon"><i class="fas fa-money-bill-wave"></i></div>
        </div>
    </div>
    <div class="col-lg-4 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3>{{ $totalEntries }}</h3>
                <p>Total Entries</p>
            </div>
            <div class="icon"><i class="fas fa-file-invoice"></i></div>
        </div>
    </div>
</div>

<div class="card card-outline card-primary">
    <div class="card-header d-flex align-items-center">
        <h3 class="card-title"><i class="fas fa-book mr-2"></i>Referral Partner Ledger</h3>
        <div class="ml-auto">
            <a href="{{ route('admin.agent.index') }}" class="btn btn-secondary btn-sm"><i class="fas fa-list mr-1"></i> Referral Partner List</a>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="agentLedgerTable" class="table table-bordered table-striped table-hover">
                <thead class="bg-primary text-white">
                    <tr>
                        <th>#</th>
                        <th>Partner ID</th>
                        <th>Name</th>
                        <th>Contact</th>
                        <th>Commission Rate</th>
                        <th>Clients</th>
                        <th>Entries</th>
                        <th>Total Commission</th>
                        <th>Paid Out</th>
                        <th>Balance</th>
                        <th>Last Activity</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($agents as $i => $agent)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td><strong>{{ $agent->agent_id }}</strong></td>
                        <td>{{ $agent->name }}</td>
                        <td>{{ $agent->personal_contact ?? '-' }}</td>
                        <td><span class="badge badge-info">{{ number_format($agent->default_commission, 2) }}%</span></td>
                        <td class="text-center"><span class="badge badge-primary">{{ $agent->client_count }}</span></td>
                        <td class="text-center"><span class="badge badge-secondary">{{ $agent->total_entries }}</span></td>
                        <td class="text-right"><strong class="text-success">৳{{ number_format($agent->total_commission, 2) }}</strong></td>
                        <td class="text-right"><strong class="text-danger">৳{{ number_format($agent->total_paid, 2) }}</strong></td>
                        <td class="text-right"><strong class="{{ $agent->current_balance > 0 ? 'text-warning' : 'text-secondary' }}">৳{{ number_format($agent->current_balance, 2) }}</strong></td>
                        <td>
                            @if($agent->last_commission_date)
                                {{ \Carbon\Carbon::parse($agent->last_commission_date)->format('d M Y') }}
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($agent->status === 'active')
                                <span class="badge badge-success">Active</span>
                            @else
                                <span class="badge badge-danger">Inactive</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('admin.agent.ledger', $agent->id) }}" class="btn btn-info btn-sm" title="View Ledger">
                                <i class="fas fa-book mr-1"></i> Ledger
                            </a>
                            <a href="{{ route('admin.agent.commission', $agent->id) }}" class="btn btn-success btn-sm" title="Commission">
                                <i class="fas fa-coins"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="13" class="text-center text-muted py-4">
                            <i class="fas fa-user-tie fa-2x mb-2 d-block"></i>No referral partners found
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="bg-light font-weight-bold">
                        <td colspan="6" class="text-right">Grand Total</td>
                        <td class="text-center">{{ $totalEntries }}</td>
                        <td class="text-right text-success">৳{{ number_format($grandTotal, 2) }}</td>
                        <td class="text-right text-danger">৳{{ number_format($agents->sum('total_paid'), 2) }}</td>
                        <td class="text-right text-warning">৳{{ number_format($agents->sum('current_balance'), 2) }}</td>
                        <td colspan="3"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    if ($('#agentLedgerTable tbody tr td[colspan]').length === 0) {
        $('#agentLedgerTable').DataTable({
            "responsive": true,
            "autoWidth": false,
            "order": [[7, "desc"]],
            "pageLength": 25
        });
    }
});
</script>
@endpush
