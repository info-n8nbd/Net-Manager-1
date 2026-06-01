@extends('layouts.admin')

@section('title', 'BTRC Monthly Revenue Report')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
<li class="breadcrumb-item"><a href="{{ route('admin.btrc.index') }}">BTRC Reports</a></li>
<li class="breadcrumb-item active">Monthly Revenue</li>
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-money-bill-wave mr-2"></i>Monthly Revenue Report - {{ $data['period'] ?? '' }}</h3>
            </div>
            <div class="card-body">
                <form method="GET" class="mb-4">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Year</label>
                                <select name="year" class="form-control">
                                    @for($y = date('Y'); $y >= date('Y') - 5; $y--)
                                    <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                                    @endfor
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Month</label>
                                <select name="month" class="form-control">
                                    @foreach(range(1, 12) as $m)
                                    <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>{{ date('F', mktime(0, 0, 0, $m, 1)) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-filter mr-1"></i> View Report</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-4 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>{{ number_format($data['total_collection'] ?? 0) }} <small>BDT</small></h3>
                <p>Total Collection</p>
            </div>
            <div class="icon"><i class="fas fa-money-bill-wave"></i></div>
        </div>
    </div>
    <div class="col-lg-4 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>{{ number_format($data['total_transactions'] ?? 0) }}</h3>
                <p>Total Transactions</p>
            </div>
            <div class="icon"><i class="fas fa-receipt"></i></div>
        </div>
    </div>
    <div class="col-lg-4 col-6">
        <div class="small-box bg-primary">
            <div class="inner">
                <h3>{{ $data['period'] ?? 'N/A' }}</h3>
                <p>Report Period</p>
            </div>
            <div class="icon"><i class="fas fa-calendar"></i></div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h3 class="card-title"><i class="fas fa-credit-card mr-2"></i>Collection by Payment Method</h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Method</th>
                            <th class="text-right">Transactions</th>
                            <th class="text-right">Amount (BDT)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($data['by_method'] ?? [] as $method => $info)
                        <tr>
                            <td>{{ ucfirst($method) }}</td>
                            <td class="text-right">{{ number_format($info['count'] ?? 0) }}</td>
                            <td class="text-right"><strong>{{ number_format($info['amount'] ?? 0) }}</strong></td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="text-center py-3">No data</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h3 class="card-title"><i class="fas fa-map-marker-alt mr-2"></i>Collection by Zone</h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Zone</th>
                            <th class="text-right">Transactions</th>
                            <th class="text-right">Amount (BDT)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($data['by_zone'] ?? [] as $zoneData)
                        <tr>
                            <td>{{ $zoneData['zone_name'] }}</td>
                            <td class="text-right">{{ number_format($zoneData['count'] ?? 0) }}</td>
                            <td class="text-right"><strong>{{ number_format($zoneData['total'] ?? 0) }}</strong></td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="text-center py-3">No data</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
