@extends('layouts.admin')

@section('title', 'Generate Bill - ' . $reseller->name)
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.reseller.index', ['type'=>$reseller->type]) }}">{{ $reseller->type === 'bandwidth' ? 'Bandwidth Reseller' : 'MAC Reseller' }}</a></li>
<li class="breadcrumb-item"><a href="{{ route('admin.reseller.show', $reseller) }}">{{ $reseller->name }}</a></li>
<li class="breadcrumb-item active">Generate Bill</li>
@endsection

@section('content')
<div class="container-fluid">

    @if(session('success'))
    <div class="alert alert-success alert-dismissible">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        <i class="fas fa-check-circle mr-1"></i> {{ session('success') }}
    </div>
    @endif
    @if(session('error'))
    <div class="alert alert-danger alert-dismissible">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        <i class="fas fa-exclamation-circle mr-1"></i> {{ session('error') }}
    </div>
    @endif

    @if($reseller->auto_bill_generate)
    {{-- ═══ AUTO MODE ═══ --}}
    <div class="card card-outline card-success">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-robot mr-2"></i>Bill Generation Controls
                <span class="text-muted font-weight-normal ml-2">— {{ $reseller->name }}</span>
            </h3>
        </div>
        <div class="card-body">
            <div class="d-flex align-items-center flex-wrap mb-3" style="gap:10px;">
                <h5 class="mb-0 font-weight-bold">
                    <i class="fas fa-calendar-check mr-1 text-success"></i>
                    Auto Bill Generate
                    <small class="text-muted font-weight-normal">(1st of every month)</small>
                </h5>
                <span class="badge badge-success p-2">
                    <i class="fas fa-check-circle mr-1"></i>Enabled
                </span>
            </div>
            <p class="text-muted mb-4">
                <i class="fas fa-info-circle mr-1"></i>
                Bills are automatically generated on the <strong>1st of every month</strong> at 01:00 AM.
            </p>
            <form method="POST" action="{{ route('admin.reseller.toggle-auto-bill', $reseller) }}" style="display:inline">
                @csrf
                <button type="submit" class="btn btn-warning"
                    onclick="return confirm('Disable auto bill generation for {{ addslashes($reseller->name) }}?')">
                    <i class="fas fa-toggle-off mr-1"></i>Disable Auto
                </button>
            </form>
            <p class="text-muted small mt-3 mb-0">
                <i class="fas fa-lightbulb mr-1"></i>
                To generate a bill manually, first disable auto bill generation.
            </p>
        </div>
    </div>

    @else
    {{-- ═══ MANUAL MODE ═══ --}}
    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-robot mr-2"></i>Bill Generation Controls
                <span class="text-muted font-weight-normal ml-2">— {{ $reseller->name }}</span>
            </h3>
        </div>
        <div class="card-body">
            <div class="d-flex align-items-center flex-wrap mb-3" style="gap:10px;">
                <h5 class="mb-0 font-weight-bold">
                    <i class="fas fa-hand-pointer mr-1 text-primary"></i>
                    Manual Bill Generation
                </h5>
                <span class="badge badge-secondary p-2">
                    <i class="fas fa-times-circle mr-1"></i>Auto Disabled
                </span>
            </div>
            <form method="POST" action="{{ route('admin.reseller.generate-bill-now', $reseller) }}" class="mb-3">
                @csrf
                <div class="form-row mb-3" style="max-width:400px;">
                    <div class="col">
                        <label class="small font-weight-bold text-muted">Month</label>
                        <select name="month" class="form-control">
                            @for($i = 1; $i <= 12; $i++)
                            <option value="{{ $i }}" {{ now()->month == $i ? 'selected' : '' }}>{{ date('F', mktime(0,0,0,$i,1)) }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="col">
                        <label class="small font-weight-bold text-muted">Year</label>
                        <select name="year" class="form-control">
                            @for($y = now()->year + 1; $y >= now()->year - 2; $y--)
                            <option value="{{ $y }}" {{ now()->year == $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary"
                    onclick="return confirm('Generate bill for {{ addslashes($reseller->name) }} — ' + this.form.month.options[this.form.month.selectedIndex].text + ' ' + this.form.year.value + '?')">
                    <i class="fas fa-file-invoice-dollar mr-1"></i>Generate Bill Now
                </button>
                <p class="text-muted small mt-2 mb-0">Select month &amp; year and click to generate bill immediately.</p>
            </form>

            <hr class="mt-3">
            <p class="text-muted small mb-2">
                <i class="fas fa-robot mr-1"></i>
                Want bills generated automatically on the 1st of every month?
            </p>
            <form method="POST" action="{{ route('admin.reseller.toggle-auto-bill', $reseller) }}" style="display:inline">
                @csrf
                <button type="submit" class="btn btn-success btn-sm"
                    onclick="return confirm('Enable auto bill generation for {{ addslashes($reseller->name) }}?')">
                    <i class="fas fa-toggle-on mr-1"></i>Enable Auto Bill
                </button>
            </form>
        </div>
    </div>
    @endif

    <div class="mt-3">
        <a href="{{ route('admin.reseller.bill-history', $reseller) }}" class="btn btn-secondary mr-2">
            <i class="fas fa-history mr-1"></i> Bill History
        </a>
        <a href="{{ route('admin.reseller.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left mr-1"></i> Back to {{ $reseller->type === 'bandwidth' ? 'Bandwidth Reseller' : 'MAC Reseller' }} List
        </a>
    </div>

</div>
@endsection
