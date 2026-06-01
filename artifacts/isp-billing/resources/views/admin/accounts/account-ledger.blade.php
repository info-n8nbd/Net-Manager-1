@extends('layouts.admin')

@section('title', $account->name . ' — Ledger')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.accounts.cash') }}">Cash In Hand</a></li>
<li class="breadcrumb-item active">{{ $account->name }} Ledger</li>
@endsection

@section('content')
<div class="container-fluid">

    {{-- Date Filter --}}
    <div class="card mb-3">
        <div class="card-body py-2">
            <form method="GET" class="form-inline flex-wrap">
                <div class="form-group mr-2 mb-1">
                    <label class="mr-1 font-weight-bold">From:</label>
                    <input type="date" name="date_from" class="form-control form-control-sm" value="{{ $dateFrom }}">
                </div>
                <div class="form-group mr-2 mb-1">
                    <label class="mr-1 font-weight-bold">To:</label>
                    <input type="date" name="date_to" class="form-control form-control-sm" value="{{ $dateTo }}">
                </div>
                <button type="submit" class="btn btn-primary btn-sm mr-2 mb-1">
                    <i class="fas fa-filter mr-1"></i>Filter
                </button>
                <a href="{{ route('admin.accounts.account-ledger', $account->id) }}" class="btn btn-secondary btn-sm mb-1">
                    <i class="fas fa-undo mr-1"></i>Reset
                </a>
            </form>
        </div>
    </div>

    {{-- Account Info + Summary --}}
    <div class="row mb-3">
        <div class="col-md-4 mb-2">
            <div class="card h-100 mb-0">
                <div class="card-body py-3">
                    <h5 class="font-weight-bold">{{ $account->name }}</h5>
                    <div class="text-muted small">
                        Type:
                        @switch($account->type)
                            @case('cash') <span class="badge badge-secondary">Cash</span> @break
                            @case('bank') <span class="badge badge-primary">Bank</span> @break
                            @case('mobile_banking') <span class="badge badge-info">Mobile Banking</span> @break
                            @default <span class="badge badge-light">{{ ucfirst($account->type) }}</span>
                        @endswitch
                    </div>
                    @if($account->account_number)
                    <div class="text-muted small mt-1">Account #: {{ $account->account_number }}</div>
                    @endif
                    @if($account->description)
                    <div class="text-muted small mt-1">{{ $account->description }}</div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="row">
                <div class="col-4 mb-2">
                    <div class="info-box mb-0">
                        <span class="info-box-icon bg-secondary"><i class="fas fa-wallet"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Opening</span>
                            <span class="info-box-number">৳{{ number_format($account->opening_balance, 2) }}</span>
                        </div>
                    </div>
                </div>
                <div class="col-4 mb-2">
                    <div class="info-box mb-0">
                        <span class="info-box-icon bg-success"><i class="fas fa-arrow-down"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Total Received</span>
                            <span class="info-box-number">৳{{ number_format($totalCredit, 2) }}</span>
                        </div>
                    </div>
                </div>
                <div class="col-4 mb-2">
                    <div class="info-box mb-0">
                        <span class="info-box-icon bg-danger"><i class="fas fa-arrow-up"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Total Paid</span>
                            <span class="info-box-number">৳{{ number_format($totalDebit, 2) }}</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-12 mb-2">
                    <div class="info-box mb-0 {{ $closingBal >= 0 ? 'bg-success' : 'bg-danger' }}">
                        <span class="info-box-icon"><i class="fas fa-hand-holding-usd"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Closing Balance ({{ $dateFrom }} to {{ $dateTo }})</span>
                            <span class="info-box-number">৳{{ number_format($closingBal, 2) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Transaction Table --}}
    <div class="card">
        <div class="card-header py-2">
            <h3 class="card-title">
                <i class="fas fa-book mr-2"></i>
                {{ $account->name }} — Transaction Ledger
                <span class="text-muted small ml-2">({{ $dateFrom }} to {{ $dateTo }})</span>
            </h3>
            <div class="card-tools">
                <button class="btn btn-tool" onclick="window.print()"><i class="fas fa-print"></i></button>
                <a href="{{ route('admin.accounts.cash') }}" class="btn btn-tool"><i class="fas fa-arrow-left"></i></a>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-sm mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th width="40" class="text-center">#</th>
                            <th width="100">Date</th>
                            <th>Description</th>
                            <th class="text-right">Debit (৳)</th>
                            <th class="text-right">Credit (৳)</th>
                            <th class="text-right">Balance (৳)</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- Opening Balance Row --}}
                        <tr class="bg-light font-weight-bold">
                            <td class="text-center">—</td>
                            <td>{{ $dateFrom }}</td>
                            <td><em>Opening Balance</em></td>
                            <td class="text-right">—</td>
                            <td class="text-right">—</td>
                            <td class="text-right">{{ number_format($account->opening_balance, 2) }}</td>
                        </tr>
                        @forelse($transactions as $n => $t)
                        <tr>
                            <td class="text-center">{{ $n + 1 }}</td>
                            <td class="text-nowrap">{{ \Carbon\Carbon::parse($t['date'])->format('d M Y') }}</td>
                            <td>
                                {{ $t['description'] }}
                                @switch($t['type'])
                                    @case('income') <span class="badge badge-success ml-1">Income</span> @break
                                    @case('expense') <span class="badge badge-danger ml-1">Expense</span> @break
                                    @case('transfer_in') <span class="badge badge-info ml-1">Transfer In</span> @break
                                    @case('transfer_out') <span class="badge badge-warning ml-1">Transfer Out</span> @break
                                @endswitch
                            </td>
                            <td class="text-right {{ $t['debit'] > 0 ? 'text-danger' : 'text-muted' }}">
                                {{ $t['debit'] > 0 ? number_format($t['debit'], 2) : '—' }}
                            </td>
                            <td class="text-right {{ $t['credit'] > 0 ? 'text-success' : 'text-muted' }}">
                                {{ $t['credit'] > 0 ? number_format($t['credit'], 2) : '—' }}
                            </td>
                            <td class="text-right font-weight-bold {{ $t['balance'] < 0 ? 'text-danger' : '' }}">
                                {{ number_format($t['balance'], 2) }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No transactions in this period</td>
                        </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="bg-light font-weight-bold">
                        <tr>
                            <td colspan="3" class="text-right">TOTAL:</td>
                            <td class="text-right text-danger">{{ number_format($totalDebit, 2) }}</td>
                            <td class="text-right text-success">{{ number_format($totalCredit, 2) }}</td>
                            <td class="text-right {{ $closingBal < 0 ? 'text-danger' : 'text-success' }}">
                                {{ number_format($closingBal, 2) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

</div>
@endsection
