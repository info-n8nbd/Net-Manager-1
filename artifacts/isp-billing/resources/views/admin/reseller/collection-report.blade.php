@extends('layouts.admin')
@section('title', 'MAC Reseller Collection Report')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.reseller.index') }}">MAC Resellers</a></li>
<li class="breadcrumb-item active">Collection Report</li>
@endsection

@section('content')

{{-- Summary Stats --}}
@php
    $grandTotal    = $resellers->sum('total_collection');
    $grandPayments = $resellers->sum('payment_count');
    $grandClients  = $resellers->sum(fn($r) => $r->clients->count());
    $grandDue      = $resellers->sum(fn($r) => max(0, ($r->summary_due ?? 0)));
@endphp
<div class="row mb-3">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-primary">
            <div class="inner">
                <h3>{{ $grandClients }}</h3>
                <p>Total Clients</p>
            </div>
            <div class="icon"><i class="fas fa-users"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>৳{{ number_format($grandTotal, 0) }}</h3>
                <p>Total Collection</p>
            </div>
            <div class="icon"><i class="fas fa-money-bill-wave"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>{{ $grandPayments }}</h3>
                <p>Total Payments</p>
            </div>
            <div class="icon"><i class="fas fa-receipt"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-danger">
            <div class="inner">
                <h3>{{ $resellers->filter(fn($r)=>($r->summary_due??0)>0)->count() }}</h3>
                <p>Resellers with Due</p>
            </div>
            <div class="icon"><i class="fas fa-exclamation-circle"></i></div>
        </div>
    </div>
</div>

{{-- Filter --}}
<div class="card card-outline card-primary mb-3">
    <div class="card-header py-2">
        <h3 class="card-title"><i class="fas fa-filter mr-1"></i>Filter</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
        </div>
    </div>
    <div class="card-body py-2">
        <form method="GET" action="{{ route('admin.reseller.collection-report') }}">
            <div class="row align-items-end">
                <div class="col-md-4">
                    <label class="small font-weight-bold">MAC Reseller</label>
                    <select name="reseller_id" class="form-control form-control-sm">
                        <option value="">All Resellers</option>
                        @foreach($resellers as $r)
                        <option value="{{ $r->id }}" {{ request('reseller_id') == $r->id ? 'selected' : '' }}>
                            {{ $r->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="small font-weight-bold">From Date</label>
                    <input type="date" name="start_date" class="form-control form-control-sm" value="{{ request('start_date') }}">
                </div>
                <div class="col-md-3">
                    <label class="small font-weight-bold">To Date</label>
                    <input type="date" name="end_date" class="form-control form-control-sm" value="{{ request('end_date') }}">
                </div>
                <div class="col-md-2 d-flex" style="gap:4px;">
                    <button type="submit" class="btn btn-primary btn-sm flex-grow-1">
                        <i class="fas fa-filter mr-1"></i>Filter
                    </button>
                    <a href="{{ route('admin.reseller.collection-report') }}" class="btn btn-default btn-sm" title="Clear">
                        <i class="fas fa-times"></i>
                    </a>
                </div>
            </div>
            @if(request('start_date') || request('end_date'))
            <div class="mt-2">
                <small class="text-muted">
                    <i class="fas fa-calendar-alt mr-1"></i>
                    Showing:
                    {{ request('start_date') ? \Carbon\Carbon::parse(request('start_date'))->format('d M Y') : 'beginning' }}
                    —
                    {{ request('end_date') ? \Carbon\Carbon::parse(request('end_date'))->format('d M Y') : 'today' }}
                </small>
            </div>
            @endif
        </form>
    </div>
</div>

{{-- Reseller Table --}}
<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-chart-bar mr-2"></i>MAC Reseller Collection Report</h3>
        <div class="card-tools">
            <div class="input-group input-group-sm" style="width:200px;">
                <input type="text" id="tableSearch" class="form-control" placeholder="Search reseller...">
                <div class="input-group-append"><span class="input-group-text"><i class="fas fa-search"></i></span></div>
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-bordered table-hover mb-0" id="collectionTable">
                <thead class="thead-dark">
                    <tr>
                        <th style="width:40px;">#</th>
                        <th>MAC Reseller</th>
                        <th class="text-center" style="width:80px;">Clients</th>
                        <th class="text-center" style="width:80px;">Payments</th>
                        <th class="text-right" style="width:140px;">Total Collection (৳)</th>
                        <th class="text-right" style="width:110px;">Due (৳)</th>
                        <th class="text-right" style="width:110px;">Wallet (৳)</th>
                        <th class="text-center" style="width:120px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($resellers as $i => $reseller)
                    @php
                        $due    = max(0, $reseller->summary_due ?? 0);
                        $wallet = $reseller->wallet_balance ?? 0;
                    @endphp
                    <tr class="{{ $due > 0 ? 'table-warning' : '' }}" data-name="{{ strtolower($reseller->name) }}">
                        <td>{{ $i + 1 }}</td>
                        <td>
                            <a href="{{ route('admin.reseller.show', $reseller) }}" class="font-weight-bold">
                                {{ $reseller->name }}
                            </a>
                            <br>
                            <small class="text-muted">{{ $reseller->reseller_id }}</small>
                        </td>
                        <td class="text-center">
                            <span class="badge badge-primary">{{ $reseller->clients->count() }}</span>
                        </td>
                        <td class="text-center">
                            <span class="badge badge-info">{{ $reseller->payment_count }}</span>
                        </td>
                        <td class="text-right font-weight-bold text-success">
                            ৳{{ number_format($reseller->total_collection, 2) }}
                        </td>
                        <td class="text-right {{ $due > 0 ? 'text-danger font-weight-bold' : 'text-muted' }}">
                            ৳{{ number_format($due, 2) }}
                        </td>
                        <td class="text-right {{ $wallet > 0 ? 'text-success' : ($wallet < 0 ? 'text-danger' : 'text-muted') }}">
                            ৳{{ number_format($wallet, 2) }}
                        </td>
                        <td class="text-center">
                            <div class="d-flex justify-content-center" style="gap:2px;">
                                <a href="{{ route('admin.reseller.show', $reseller) }}"
                                   class="btn btn-info btn-xs" title="View Profile">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('admin.reseller.bill-history', $reseller) }}"
                                   class="btn btn-secondary btn-xs" title="Bill History">
                                    <i class="fas fa-history"></i>
                                </a>
                                @if($due > 0)
                                <a href="{{ route('admin.reseller.bill-payment', $reseller) }}"
                                   class="btn btn-danger btn-xs" title="Collect Payment">
                                    <i class="fas fa-money-bill-wave"></i>
                                </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">
                            <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                            No reseller data available
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                @if($resellers->count() > 0)
                <tfoot class="bg-dark text-white" style="font-size:12px;">
                    <tr>
                        <td colspan="2" class="text-right font-weight-bold">Grand Total</td>
                        <td class="text-center font-weight-bold">{{ $grandClients }}</td>
                        <td class="text-center font-weight-bold">{{ $grandPayments }}</td>
                        <td class="text-right font-weight-bold text-success">৳{{ number_format($grandTotal, 2) }}</td>
                        <td class="text-right font-weight-bold text-warning">৳{{ number_format($resellers->sum(fn($r)=>max(0,$r->summary_due??0)), 2) }}</td>
                        <td class="text-right font-weight-bold">৳{{ number_format($resellers->sum('wallet_balance'), 2) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
$(function(){
    $('#tableSearch').on('keyup', function(){
        var val = $(this).val().toLowerCase();
        $('#collectionTable tbody tr').each(function(){
            var name = $(this).data('name') || $(this).text().toLowerCase();
            $(this).toggle(name.indexOf(val) > -1);
        });
    });
});
</script>
<style>
.btn-xs { padding: 2px 6px; font-size: 11px; line-height: 1.5; border-radius: 3px; }
</style>
@endpush
