@extends('layouts.admin')

@section('title', 'Generate Bills')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.billing.index') }}">Billing</a></li>
<li class="breadcrumb-item active">Generate</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header"><h3 class="card-title">Generate Monthly Bills</h3></div>
    <form action="{{ route('admin.billing.generate.post') }}" method="POST">
        @csrf
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Billing Month</label>
                        <select name="billing_month" class="form-control" required>
                            @for($i = 1; $i <= 12; $i++)
                            <option value="{{ $i }}" {{ $i == date('m') ? 'selected' : '' }}>{{ date('F', mktime(0,0,0,$i,1)) }}</option>
                            @endfor
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Billing Year</label>
                        <select name="billing_year" class="form-control" required>
                            @for($y = date('Y')-1; $y <= date('Y')+1; $y++)
                            <option value="{{ $y }}" {{ $y == date('Y') ? 'selected' : '' }}>{{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                </div>
            </div>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> This will generate bills for all <strong>{{ $clients->count() }}</strong> active clients.
            </div>
        </div>
        @if($__canAction('billing', 'generate_bills'))
        <div class="card-footer">
            <button type="submit" class="btn btn-primary"><i class="fas fa-cogs"></i> Generate Bills</button>
        </div>
        @endif
    </form>
</div>
@endsection
