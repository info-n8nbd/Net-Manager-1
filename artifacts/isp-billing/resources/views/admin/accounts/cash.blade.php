@extends('layouts.admin')

@section('title', 'Cash In Hand Statement')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="#">Accounts</a></li>
<li class="breadcrumb-item active">Cash In Hand</li>
@endsection

@section('content')
<div class="container-fluid">

    {{-- Date Filter --}}
    <div class="card mb-3">
        <div class="card-body py-2">
            <form method="GET" class="form-inline flex-wrap">
                <div class="form-group mr-2 mb-1">
                    <label class="mr-1 font-weight-bold">From:</label>
                    <input type="date" name="date_from" class="form-control form-control-sm" value="{{ $dateFrom ?? '' }}">
                </div>
                <div class="form-group mr-2 mb-1">
                    <label class="mr-1 font-weight-bold">To:</label>
                    <input type="date" name="date_to" class="form-control form-control-sm" value="{{ $dateTo ?? '' }}">
                </div>
                <button type="submit" class="btn btn-primary btn-sm mr-2 mb-1">
                    <i class="fas fa-filter mr-1"></i>Filter
                </button>
                <a href="{{ route('admin.accounts.cash') }}" class="btn btn-secondary btn-sm mb-1">
                    <i class="fas fa-undo mr-1"></i>Reset
                </a>
                @if($dateFrom || $dateTo)
                <span class="ml-3 text-muted mb-1 align-self-center small">
                    Showing: {{ $dateFrom ? \Carbon\Carbon::parse($dateFrom)->format('d M Y') : 'Beginning' }}
                    &mdash; {{ $dateTo ? \Carbon\Carbon::parse($dateTo)->format('d M Y') : 'Today' }}
                </span>
                @endif
            </form>
        </div>
    </div>

    {{-- Summary KPI Cards --}}
    <div class="row mb-3">
        <div class="col-6 col-md-3 mb-2">
            <div class="small-box bg-secondary mb-0">
                <div class="inner">
                    <h4 class="text-white">৳{{ number_format($grandOpening, 2) }}</h4>
                    <p>Opening Balance</p>
                </div>
                <div class="icon"><i class="fas fa-wallet"></i></div>
            </div>
        </div>
        <div class="col-6 col-md-3 mb-2">
            <div class="small-box bg-success mb-0">
                <div class="inner">
                    <h4>৳{{ number_format($grandReceived, 2) }}</h4>
                    <p>Total Received</p>
                </div>
                <div class="icon"><i class="fas fa-arrow-circle-down"></i></div>
            </div>
        </div>
        <div class="col-6 col-md-3 mb-2">
            <div class="small-box bg-danger mb-0">
                <div class="inner">
                    <h4>৳{{ number_format($grandPaid, 2) }}</h4>
                    <p>Total Paid Out</p>
                </div>
                <div class="icon"><i class="fas fa-arrow-circle-up"></i></div>
            </div>
        </div>
        <div class="col-6 col-md-3 mb-2">
            <div class="small-box mb-0" style="background:{{ $grandBalance >= 0 ? '#00a65a' : '#dd4b39' }}; color:#fff;">
                <div class="inner">
                    <h4>৳{{ number_format(abs($grandBalance), 2) }}</h4>
                    <p>Net Cash In Hand</p>
                </div>
                <div class="icon"><i class="fas fa-hand-holding-usd"></i></div>
            </div>
        </div>
    </div>

    {{-- Main Statement Table --}}
    <div class="card">
        <div class="card-header py-2">
            <h3 class="card-title"><i class="fas fa-list-alt mr-2"></i>Cash In Hand Statement</h3>
            <div class="card-tools">
                <button class="btn btn-tool" onclick="window.print()"><i class="fas fa-print"></i></button>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0" id="cashTable">
                    <thead>
                        <tr style="background:#f4f6f9;">
                            <th width="45" class="text-center align-middle">S/L</th>
                            <th class="align-middle">Bank Name</th>
                            <th class="text-right align-middle">Opening Balance</th>
                            <th class="text-right align-middle">Received Amount</th>
                            <th class="text-right align-middle">Paid Amount</th>
                            <th class="text-right align-middle">Balance</th>
                            <th width="70" class="text-center align-middle">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $i => $row)
                        <tr>
                            <td class="text-center align-middle">{{ $i + 1 }}</td>
                            <td class="align-middle">
                                <strong>{{ $row['name'] }}</strong>
                                @if($row['sub_info'])
                                <div class="small text-muted mt-1">
                                    {{ $row['sub_info']['name'] }}
                                    @if($row['sub_info']['emp_id'])
                                    ({{ $row['sub_info']['emp_id'] }})
                                    @endif
                                    @if($row['sub_info']['dept'])
                                    <br>{{ $row['sub_info']['dept'] }}
                                    @endif
                                </div>
                                @else
                                <div class="mt-1">
                                    @switch($row['type'])
                                        @case('cash') <span class="badge badge-secondary">Cash</span> @break
                                        @case('bank') <span class="badge badge-primary">Bank</span> @break
                                        @case('mobile_banking') <span class="badge badge-info">Mobile Banking</span> @break
                                        @default <span class="badge badge-light text-dark">{{ ucfirst(str_replace('_',' ',$row['type'])) }}</span>
                                    @endswitch
                                </div>
                                @endif
                            </td>
                            <td class="text-right align-middle">
                                {{ number_format($row['opening'], 2) }}
                            </td>
                            <td class="text-right align-middle text-success font-weight-bold">
                                {{ number_format($row['received'], 2) }}
                            </td>
                            <td class="text-right align-middle">
                                {{ number_format($row['paid'], 2) }}
                            </td>
                            <td class="text-right align-middle font-weight-bold {{ $row['balance'] < 0 ? 'text-danger' : '' }}">
                                {{ number_format($row['balance'], 2) }}
                            </td>
                            <td class="text-center align-middle">
                                @if($row['row_type'] === 'account')
                                    <a href="{{ route('admin.accounts.account-ledger', $row['id']) }}{{ ($dateFrom || $dateTo) ? '?date_from='.($dateFrom??'').'&date_to='.($dateTo??'') : '' }}"
                                       class="btn btn-sm btn-outline-info" title="View Ledger">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                @else
                                    <a href="{{ route('admin.accounts.staff-cash-ledger', $row['id']) }}{{ ($dateFrom || $dateTo) ? '?date_from='.($dateFrom??'').'&date_to='.($dateTo??'') : '' }}"
                                       class="btn btn-sm btn-outline-info" title="View Ledger">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                <i class="fas fa-inbox fa-2x mb-2 d-block"></i>No accounts found
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr style="background:#f4f6f9; font-weight:bold;">
                            <td colspan="2" class="text-right">TOTAL:</td>
                            <td class="text-right">{{ number_format($grandOpening, 2) }}</td>
                            <td class="text-right text-success">{{ number_format($grandReceived, 2) }}</td>
                            <td class="text-right">{{ number_format($grandPaid, 2) }}</td>
                            <td class="text-right {{ $grandBalance < 0 ? 'text-danger' : 'text-success' }}" style="font-size:1.05em;">
                                {{ number_format($grandBalance, 2) }}
                            </td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

</div>
@endsection
