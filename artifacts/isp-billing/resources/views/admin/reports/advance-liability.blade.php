@extends('layouts.admin')

@section('title', 'Advance Liability Report')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Reports</a></li>
<li class="breadcrumb-item active">Advance Liability</li>
@endsection

@section('content')
<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center gap-1">
        <h3 class="card-title"><i class="fas fa-filter mr-2"></i>Filter</h3>
        <div class="btn-group">
            <a href="{{ route('admin.reports.export.excel', ['type' => 'advance-liability', 'start_date' => $startDate->format('Y-m-d'), 'end_date' => $endDate->format('Y-m-d')]) }}" class="btn btn-success btn-sm">
                <i class="fas fa-file-csv mr-1"></i> CSV
            </a>
            <a href="{{ route('admin.reports.export.pdf', ['type' => 'advance-liability', 'start_date' => $startDate->format('Y-m-d'), 'end_date' => $endDate->format('Y-m-d')]) }}" class="btn btn-danger btn-sm">
                <i class="fas fa-file-pdf mr-1"></i> PDF
            </a>
        </div>
    </div>
    <div class="card-body">
        <form method="GET" class="row">
            <div class="col-md-4">
                <label>Start Date</label>
                <input type="date" name="start_date" class="form-control" value="{{ $startDate->format('Y-m-d') }}">
            </div>
            <div class="col-md-4">
                <label>End Date</label>
                <input type="date" name="end_date" class="form-control" value="{{ $endDate->format('Y-m-d') }}">
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <button type="submit" class="btn btn-primary"><i class="fas fa-search mr-1"></i> Filter</button>
            </div>
        </form>
    </div>
</div>

<div class="row mb-3">
    <div class="col-md-3">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>৳{{ number_format($report['summary']['total_advance_balance'] ?? 0, 0) }}</h3>
                <p>Total Advance Balance (Liability)</p>
            </div>
            <div class="icon"><i class="fas fa-wallet"></i></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>৳{{ number_format($report['summary']['advance_received'] ?? 0, 0) }}</h3>
                <p>Advance Received (Period)</p>
            </div>
            <div class="icon"><i class="fas fa-arrow-down"></i></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3>৳{{ number_format($report['summary']['advance_used'] ?? 0, 0) }}</h3>
                <p>Advance Used (Period)</p>
            </div>
            <div class="icon"><i class="fas fa-arrow-up"></i></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="small-box bg-primary">
            <div class="inner">
                <h3>{{ $report['summary']['clients_with_advance'] }}</h3>
                <p>Clients with Advance Balance</p>
            </div>
            <div class="icon"><i class="fas fa-users"></i></div>
        </div>
    </div>
</div>

<div class="alert alert-info">
    <i class="fas fa-exclamation-circle mr-2"></i>
    <strong>Important:</strong> Advance balance is a <strong>liability</strong> to the company. It represents money received that has not yet been applied to bills. This amount must NOT be counted as revenue until bills are settled.
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-users mr-2"></i>Clients with Advance Balance</h3>
        <div class="card-tools">
            <span class="badge badge-primary">{{ count($report['clients']) }} clients</span>
        </div>
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-bordered table-striped table-sm">
            <thead class="thead-dark">
                <tr>
                    <th>Client ID</th>
                    <th>Name</th>
                    <th>Zone</th>
                    <th class="text-right">Advance Balance</th>
                    <th class="text-right">Due Amount</th>
                    <th class="text-right">Net Position</th>
                </tr>
            </thead>
            <tbody>
                @forelse($report['clients'] as $client)
                <tr>
                    <td>{{ $client['client_id'] }}</td>
                    <td>{{ $client['name'] }}</td>
                    <td>{{ $client['zone'] }}</td>
                    <td class="text-right text-success font-weight-bold">৳{{ number_format($client['advance_balance'] ?? 0, 2) }}</td>
                    <td class="text-right text-danger">৳{{ number_format($client['due_amount'] ?? 0, 2) }}</td>
                    <td class="text-right {{ $client['net_position'] >= 0 ? 'text-success' : 'text-danger' }} font-weight-bold">
                        ৳{{ number_format($client['net_position'] ?? 0, 2) }}
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center py-4 text-muted">No clients with advance balance</td></tr>
                @endforelse
            </tbody>
            @if(count($report['clients']) > 0)
            <tfoot class="bg-light">
                <tr>
                    <th colspan="3" class="text-right">Total:</th>
                    <th class="text-right text-success">৳{{ number_format(collect($report['clients'])->sum('advance_balance'), 2) }}</th>
                    <th class="text-right text-danger">৳{{ number_format(collect($report['clients'])->sum('due_amount'), 2) }}</th>
                    <th class="text-right">৳{{ number_format(collect($report['clients'])->sum('net_position'), 2) }}</th>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
</div>

<div class="card mt-3">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-history mr-2"></i>Recent Advance Transactions</h3>
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-bordered table-striped table-sm">
            <thead class="thead-light">
                <tr>
                    <th>Date</th>
                    <th>Client ID</th>
                    <th>Name</th>
                    <th>Type</th>
                    <th class="text-right">Amount</th>
                    <th class="text-right">Used</th>
                    <th class="text-right">Remaining</th>
                </tr>
            </thead>
            <tbody>
                @forelse($report['transactions'] as $tx)
                <tr>
                    <td>{{ $tx['date'] }}</td>
                    <td>{{ $tx['client_id'] }}</td>
                    <td>{{ $tx['client_name'] }}</td>
                    <td><span class="badge badge-success">{{ $tx['type'] }}</span></td>
                    <td class="text-right">৳{{ number_format($tx['amount'] ?? 0, 2) }}</td>
                    <td class="text-right text-warning">৳{{ number_format($tx['used'] ?? 0, 2) }}</td>
                    <td class="text-right text-success">৳{{ number_format($tx['remaining'] ?? 0, 2) }}</td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center py-4 text-muted">No transactions in selected period</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
