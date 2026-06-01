@extends('layouts.admin')

@section('title', 'MAC Reseller Report')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="#">Reports</a></li>
<li class="breadcrumb-item active">MAC Reseller Report</li>
@endsection

@section('content')
<div class="container-fluid">

    {{-- Filter bar --}}
    <div class="card mb-3">
        <div class="card-body py-2">
            <form method="GET" class="form-inline flex-wrap">
                <div class="form-group mr-2 mb-1">
                    <label class="mr-1"><strong>Reseller:</strong></label>
                    <select name="reseller_id" class="form-control form-control-sm">
                        <option value="">All MAC Resellers</option>
                        @foreach($resellers as $r)
                        <option value="{{ $r->id }}" {{ request('reseller_id') == $r->id ? 'selected' : '' }}>{{ $r->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group mr-2 mb-1">
                    <label class="mr-1"><strong>Type:</strong></label>
                    <select name="type" class="form-control form-control-sm">
                        <option value="">All Types</option>
                        <option value="mac"       {{ request('type') === 'mac'       ? 'selected' : '' }}>MAC (Managed)</option>
                        <option value="bandwidth" {{ request('type') === 'bandwidth' ? 'selected' : '' }}>Bandwidth (Wholesale)</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary btn-sm mr-2 mb-1"><i class="fas fa-filter mr-1"></i>Filter</button>
                <a href="{{ route('admin.reports.reseller') }}" class="btn btn-secondary btn-sm mb-1"><i class="fas fa-undo mr-1"></i>Reset</a>
            </form>
        </div>
    </div>

    {{-- ══ Type Summary Cards ══ --}}
    <div class="row mb-3">
        <div class="col-md-6">
            <div class="card border-info mb-0">
                <div class="card-header py-2 bg-info text-white">
                    <h6 class="mb-0"><i class="fas fa-network-wired mr-2"></i>Resellers (Managed / Subscription)</h6>
                </div>
                <div class="card-body py-2">
                    <div class="row text-center">
                        <div class="col-3">
                            <div class="h5 text-info mb-0">{{ $macSummary->count() }}</div>
                            <small class="text-muted">MAC Resellers</small>
                        </div>
                        <div class="col-3">
                            <div class="h5 text-success mb-0">{{ number_format($macSummary->sum('client_count')) }}</div>
                            <small class="text-muted">Clients</small>
                        </div>
                        <div class="col-3">
                            <div class="h5 text-success mb-0">৳{{ number_format($macSummary->sum('bill_paid'), 0) }}</div>
                            <small class="text-muted">Bill Paid</small>
                        </div>
                        <div class="col-3">
                            <div class="h5 text-danger mb-0">৳{{ number_format($macSummary->sum('bill_due'), 0) }}</div>
                            <small class="text-muted">Bill Due</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-warning mb-0">
                <div class="card-header py-2" style="background:#f77f00; color:#fff;">
                    <h6 class="mb-0"><i class="fas fa-satellite-dish mr-2"></i>Bandwidth Reseller (Wholesale Buyers)</h6>
                </div>
                <div class="card-body py-2">
                    <div class="row text-center">
                        <div class="col-3">
                            <div class="h5 text-warning mb-0">{{ $bwSummary->count() }}</div>
                            <small class="text-muted">MAC Resellers</small>
                        </div>
                        <div class="col-3">
                            <div class="h5 text-muted mb-0">—</div>
                            <small class="text-muted">Clients</small>
                        </div>
                        <div class="col-3">
                            <div class="h5 text-success mb-0">৳{{ number_format($bwSummary->sum('bill_paid'), 0) }}</div>
                            <small class="text-muted">Bill Paid</small>
                        </div>
                        <div class="col-3">
                            <div class="h5 text-danger mb-0">৳{{ number_format($bwSummary->sum('bill_due'), 0) }}</div>
                            <small class="text-muted">Bill Due</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ══ Reseller Table ══ --}}
    @if(!request('type') || request('type') === 'mac')
    <div class="card mb-3">
        <div class="card-header py-2 bg-info text-white">
            <h6 class="mb-0"><i class="fas fa-network-wired mr-2"></i>Resellers — Detail</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-sm mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Name</th>
                            <th>Company</th>
                            <th class="text-center">Clients</th>
                            <th class="text-right">Wallet (৳)</th>
                            <th class="text-right">Bill Total (৳)</th>
                            <th class="text-right">Bill Paid (৳)</th>
                            <th class="text-right">Bill Due (৳)</th>
                            <th class="text-center">Unpaid Bills</th>
                            <th class="text-right">Client Collection (৳)</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $macRows = collect($report)->where('type','mac'); @endphp
                        @forelse($macRows as $row)
                        <tr>
                            <td><strong>{{ $row['reseller'] }}</strong></td>
                            <td>{{ $row['company'] ?? '—' }}</td>
                            <td class="text-center"><span class="badge badge-info">{{ $row['client_count'] }}</span></td>
                            <td class="text-right">{{ number_format($row['wallet_balance'] ?? 0, 2) }}</td>
                            <td class="text-right">{{ number_format($row['bill_total'] ?? 0, 2) }}</td>
                            <td class="text-right text-success"><strong>{{ number_format($row['bill_paid'] ?? 0, 2) }}</strong></td>
                            <td class="text-right {{ ($row['bill_due'] ?? 0) > 0 ? 'text-danger font-weight-bold' : '' }}">{{ number_format($row['bill_due'] ?? 0, 2) }}</td>
                            <td class="text-center">
                                @if(($row['unpaid_bills'] ?? 0) > 0)
                                <span class="badge badge-danger">{{ $row['unpaid_bills'] }}</span>
                                @else
                                <span class="badge badge-success">0</span>
                                @endif
                            </td>
                            <td class="text-right text-info">{{ number_format($row['client_collection'] ?? 0, 2) }}</td>
                            <td class="text-center">
                                <a href="{{ route('admin.reseller.show', $row['id']) }}" class="btn btn-xs btn-info"><i class="fas fa-eye"></i></a>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="10" class="text-center text-muted py-3">No MAC resellers found</td></tr>
                        @endforelse
                    </tbody>
                    @if($macRows->count() > 0)
                    <tfoot class="bg-light font-weight-bold">
                        <tr>
                            <td colspan="2">Total</td>
                            <td class="text-center">{{ $macRows->sum('client_count') }}</td>
                            <td class="text-right">{{ number_format($macRows->sum('wallet_balance'), 2) }}</td>
                            <td class="text-right">{{ number_format($macRows->sum('bill_total'), 2) }}</td>
                            <td class="text-right text-success">{{ number_format($macRows->sum('bill_paid'), 2) }}</td>
                            <td class="text-right text-danger">{{ number_format($macRows->sum('bill_due'), 2) }}</td>
                            <td class="text-center">{{ $macRows->sum('unpaid_bills') }}</td>
                            <td class="text-right text-info">{{ number_format($macRows->sum('client_collection'), 2) }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
    @endif

    {{-- ══ Bandwidth Reseller Table ══ --}}
    @if(!request('type') || request('type') === 'bandwidth')
    <div class="card mb-3">
        <div class="card-header py-2 text-white" style="background:#f77f00;">
            <h6 class="mb-0"><i class="fas fa-satellite-dish mr-2"></i>Bandwidth Reseller — Detail</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-sm mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Name</th>
                            <th>Company</th>
                            <th class="text-right">Wallet (৳)</th>
                            <th class="text-right">Bill Total (৳)</th>
                            <th class="text-right">Bill Paid (৳)</th>
                            <th class="text-right">Bill Due (৳)</th>
                            <th class="text-center">Unpaid Bills</th>
                            <th class="text-center">Total Bills</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $bwRows = collect($report)->where('type','bandwidth'); @endphp
                        @forelse($bwRows as $row)
                        <tr>
                            <td><strong>{{ $row['reseller'] }}</strong></td>
                            <td>{{ $row['company'] ?? '—' }}</td>
                            <td class="text-right">{{ number_format($row['wallet_balance'] ?? 0, 2) }}</td>
                            <td class="text-right">{{ number_format($row['bill_total'] ?? 0, 2) }}</td>
                            <td class="text-right text-success"><strong>{{ number_format($row['bill_paid'] ?? 0, 2) }}</strong></td>
                            <td class="text-right {{ ($row['bill_due'] ?? 0) > 0 ? 'text-danger font-weight-bold' : '' }}">{{ number_format($row['bill_due'] ?? 0, 2) }}</td>
                            <td class="text-center">
                                @if(($row['unpaid_bills'] ?? 0) > 0)
                                <span class="badge badge-danger">{{ $row['unpaid_bills'] }}</span>
                                @else
                                <span class="badge badge-success">0</span>
                                @endif
                            </td>
                            <td class="text-center"><span class="badge badge-secondary">{{ $row['bill_count'] }}</span></td>
                            <td class="text-center">
                                <a href="{{ route('admin.reseller.show', $row['id']) }}" class="btn btn-xs btn-info"><i class="fas fa-eye"></i></a>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="9" class="text-center text-muted py-3">No bandwidth resellers found</td></tr>
                        @endforelse
                    </tbody>
                    @if($bwRows->count() > 0)
                    <tfoot class="bg-light font-weight-bold">
                        <tr>
                            <td colspan="2">Total</td>
                            <td class="text-right">{{ number_format($bwRows->sum('wallet_balance'), 2) }}</td>
                            <td class="text-right">{{ number_format($bwRows->sum('bill_total'), 2) }}</td>
                            <td class="text-right text-success">{{ number_format($bwRows->sum('bill_paid'), 2) }}</td>
                            <td class="text-right text-danger">{{ number_format($bwRows->sum('bill_due'), 2) }}</td>
                            <td class="text-center">{{ $bwRows->sum('unpaid_bills') }}</td>
                            <td class="text-center">{{ $bwRows->sum('bill_count') }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
    @endif

    <div class="alert alert-info py-2">
        <i class="fas fa-info-circle mr-2"></i>
        <strong>ISP Revenue note:</strong>
        <strong>MAC Resellers</strong> — ISP income = <em>Bill Paid</em> (reseller subscription bills). Client Collection passes through the reseller.
        <strong>Bandwidth Reseller</strong> — ISP income = <em>Bill Paid</em> (wholesale bandwidth bills). They manage their own clients.
    </div>

</div>
@endsection
