@extends('layouts.admin')

@section('title', 'Staff Cash Ledger — ' . $user->name)
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.accounts.cash') }}">Cash In Hand</a></li>
<li class="breadcrumb-item active">{{ $user->name }} — Cash Ledger</li>
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
                <a href="{{ route('admin.accounts.staff-cash-ledger', $user->id) }}" class="btn btn-secondary btn-sm mb-1">
                    <i class="fas fa-undo mr-1"></i>Reset
                </a>
            </form>
        </div>
    </div>

    {{-- Staff Info + Summary --}}
    <div class="row mb-3">
        <div class="col-md-4 mb-2">
            <div class="card h-100 mb-0">
                <div class="card-body py-3">
                    <h5 class="font-weight-bold">{{ $user->name }}</h5>
                    @if($employee)
                    <div class="text-muted small">
                        <i class="fas fa-id-badge mr-1"></i>{{ $employee->employee_id }}
                    </div>
                    @endif
                    <div class="text-muted small mt-1">
                        <i class="fas fa-envelope mr-1"></i>{{ $user->email }}
                    </div>
                    <div class="mt-2">
                        <span class="badge badge-warning">Staff Cash Account</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="row">
                <div class="col-4 mb-2">
                    <div class="info-box mb-0">
                        <span class="info-box-icon bg-success"><i class="fas fa-arrow-down"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Total Collected</span>
                            <span class="info-box-number">৳{{ number_format($staffDue?->total_collected ?? 0, 2) }}</span>
                        </div>
                    </div>
                </div>
                <div class="col-4 mb-2">
                    <div class="info-box mb-0">
                        <span class="info-box-icon bg-info"><i class="fas fa-paper-plane"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Total Submitted</span>
                            <span class="info-box-number">৳{{ number_format($staffDue?->total_submitted ?? 0, 2) }}</span>
                        </div>
                    </div>
                </div>
                <div class="col-4 mb-2">
                    <div class="info-box mb-0 {{ $currentDue > 0 ? 'bg-warning' : 'bg-success' }}">
                        <span class="info-box-icon"><i class="fas fa-hand-holding-usd"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Current Due (In Hand)</span>
                            <span class="info-box-number">৳{{ number_format($currentDue, 2) }}</span>
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
                Cash-{{ $user->name }} — Transaction Ledger
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
                            <th class="text-right">Submitted (৳)</th>
                            <th class="text-right">Collected (৳)</th>
                            <th class="text-right">Balance (৳)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transactions as $n => $t)
                        <tr>
                            <td class="text-center">{{ $n + 1 }}</td>
                            <td class="text-nowrap">{{ \Carbon\Carbon::parse($t['date'])->format('d M Y') }}</td>
                            <td>
                                {{ $t['description'] }}
                                @if($t['type'] === 'collection')
                                <span class="badge badge-success ml-1">Collection</span>
                                @else
                                <span class="badge badge-info ml-1">Submitted</span>
                                @endif
                            </td>
                            <td class="text-right {{ $t['debit'] > 0 ? 'text-info font-weight-bold' : 'text-muted' }}">
                                {{ $t['debit'] > 0 ? number_format($t['debit'], 2) : '—' }}
                            </td>
                            <td class="text-right {{ $t['credit'] > 0 ? 'text-success font-weight-bold' : 'text-muted' }}">
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
                            <td class="text-right text-info">{{ number_format($totalSubmitted, 2) }}</td>
                            <td class="text-right text-success">{{ number_format($totalCollected, 2) }}</td>
                            <td class="text-right {{ $currentDue < 0 ? 'text-danger' : 'text-warning' }}">
                                {{ number_format($currentDue, 2) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

</div>
@endsection
