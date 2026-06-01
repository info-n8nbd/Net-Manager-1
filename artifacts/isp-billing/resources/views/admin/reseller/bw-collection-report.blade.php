@extends('layouts.admin')

@section('title', 'Bandwidth Reseller Collection Report')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.reseller.index', ['type'=>'bandwidth']) }}">Bandwidth Resellers</a></li>
<li class="breadcrumb-item active">Collection Report</li>
@endsection

@section('content')

{{-- Summary Stats --}}
@php
    $grandBilled = $resellers->sum('total_billed');
    $grandPaid   = $resellers->sum('total_paid');
    $grandDue    = $resellers->sum('total_due');
    $grandBills  = $resellers->sum('bill_count');
@endphp
<div class="row mb-3">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>৳{{ number_format($grandBilled, 0) }}</h3>
                <p>Total Billed</p>
            </div>
            <div class="icon"><i class="fas fa-file-invoice"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>৳{{ number_format($grandPaid, 0) }}</h3>
                <p>Total Paid</p>
            </div>
            <div class="icon"><i class="fas fa-check-circle"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-danger">
            <div class="inner">
                <h3>৳{{ number_format($grandDue, 0) }}</h3>
                <p>Total Due</p>
            </div>
            <div class="icon"><i class="fas fa-exclamation-circle"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-secondary">
            <div class="inner">
                <h3>{{ $grandBills }}</h3>
                <p>Total Bills</p>
            </div>
            <div class="icon"><i class="fas fa-file-alt"></i></div>
        </div>
    </div>
</div>

{{-- Filter --}}
<div class="card card-outline card-info mb-3">
    <div class="card-header py-2">
        <h3 class="card-title"><i class="fas fa-filter mr-1"></i>Filter</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
        </div>
    </div>
    <div class="card-body py-2">
        <form method="GET" action="{{ route('admin.reseller.bw-collection-report') }}">
            <div class="row align-items-end">
                <div class="col-md-3">
                    <label class="small font-weight-bold">Bandwidth Reseller</label>
                    <select name="reseller_id" class="form-control form-control-sm">
                        <option value="">All BW Resellers</option>
                        @foreach($resellers as $r)
                        <option value="{{ $r->id }}" {{ request('reseller_id')==$r->id ? 'selected' : '' }}>
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
                <div class="col-md-3 d-flex" style="gap:4px;">
                    <button type="submit" class="btn btn-info btn-sm flex-grow-1">
                        <i class="fas fa-filter mr-1"></i>Filter
                    </button>
                    <a href="{{ route('admin.reseller.bw-collection-report') }}" class="btn btn-default btn-sm" title="Clear">
                        <i class="fas fa-times"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Table --}}
<div class="card card-outline card-info">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-tachometer-alt mr-2 text-info"></i>Bandwidth Reseller Collection Report</h3>
        <div class="card-tools">
            <div class="input-group input-group-sm" style="width:200px;">
                <input type="text" id="bwSearch" class="form-control" placeholder="Search reseller...">
                <div class="input-group-append"><span class="input-group-text"><i class="fas fa-search"></i></span></div>
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-bordered table-hover mb-0" id="bwTable">
                <thead class="thead-dark">
                    <tr>
                        <th style="width:40px;">#</th>
                        <th>Bandwidth Reseller</th>
                        <th class="text-right" style="width:130px;">Total Billed (৳)</th>
                        <th class="text-right" style="width:130px;">Total Paid (৳)</th>
                        <th class="text-right" style="width:110px;">Due (৳)</th>
                        <th class="text-center" style="width:80px;">Bills</th>
                        <th class="text-center" style="width:120px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($resellers as $i => $reseller)
                    <tr class="{{ $reseller->total_due > 0 ? 'table-warning' : '' }}" data-name="{{ strtolower($reseller->name) }}">
                        <td>{{ $i + 1 }}</td>
                        <td>
                            <a href="{{ route('admin.reseller.show', $reseller) }}" class="font-weight-bold">
                                {{ $reseller->name }}
                            </a>
                            <span class="badge badge-info ml-1" style="font-size:9px;">Bandwidth</span>
                            <br>
                            <small class="text-muted">{{ $reseller->reseller_id }}</small>
                        </td>
                        <td class="text-right font-weight-bold">৳{{ number_format($reseller->total_billed, 2) }}</td>
                        <td class="text-right font-weight-bold text-success">৳{{ number_format($reseller->total_paid, 2) }}</td>
                        <td class="text-right {{ $reseller->total_due > 0 ? 'text-danger font-weight-bold' : 'text-muted' }}">
                            ৳{{ number_format($reseller->total_due, 2) }}
                        </td>
                        <td class="text-center">
                            <span class="badge badge-secondary">{{ $reseller->bill_count }}</span>
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
                                @if($reseller->total_due > 0)
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
                        <td colspan="7" class="text-center text-muted py-4">
                            <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                            No bandwidth reseller data available
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                @if($resellers->count() > 0)
                <tfoot class="bg-dark text-white" style="font-size:12px;">
                    <tr>
                        <td colspan="2" class="text-right font-weight-bold">Grand Total</td>
                        <td class="text-right font-weight-bold">৳{{ number_format($grandBilled, 2) }}</td>
                        <td class="text-right font-weight-bold text-success">৳{{ number_format($grandPaid, 2) }}</td>
                        <td class="text-right font-weight-bold text-warning">৳{{ number_format($grandDue, 2) }}</td>
                        <td class="text-center font-weight-bold">{{ $grandBills }}</td>
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
    $('#bwSearch').on('keyup', function(){
        var val = $(this).val().toLowerCase();
        $('#bwTable tbody tr').each(function(){
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
