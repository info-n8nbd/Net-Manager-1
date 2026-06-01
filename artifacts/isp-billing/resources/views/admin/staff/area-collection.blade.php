@extends('layouts.admin')

@section('title', 'Area-wise Collection')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="#">Staff</a></li>
<li class="breadcrumb-item active">Area-wise Collection</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Area-wise Collection - {{ \Carbon\Carbon::parse($date)->format('d-m-Y') }}</h3>
        <div class="card-tools">
            <form method="GET" class="form-inline">
                <input type="date" name="date" class="form-control form-control-sm mr-2" value="{{ $date }}">
                <select name="range" class="form-control form-control-sm mr-2">
                    <option value="day" {{ $dateRange == 'day' ? 'selected' : '' }}>Daily</option>
                    <option value="week" {{ $dateRange == 'week' ? 'selected' : '' }}>Weekly</option>
                    <option value="month" {{ $dateRange == 'month' ? 'selected' : '' }}>Monthly</option>
                </select>
                <button type="submit" class="btn btn-info btn-sm">Filter</button>
            </form>
        </div>
    </div>
    <div class="card-body">
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>Zone / Area</th>
                    <th>Payment Count</th>
                    <th>Total Amount (৳)</th>
                    <th>Collectors</th>
                </tr>
            </thead>
            <tbody>
                @forelse($collections as $collection)
                <tr>
                    <td>{{ $collection->zone_name }}</td>
                    <td>{{ $collection->payment_count }}</td>
                    <td>৳{{ number_format($collection->total_amount, 2) }}</td>
                    <td>{{ $collection->collectors }}</td>
                </tr>
                @empty
                <tr><td colspan="4" class="text-center">No collections found for this period</td></tr>
                @endforelse
            </tbody>
            @if($collections->count() > 0)
            <tfoot>
                <tr class="font-weight-bold">
                    <td>Total</td>
                    <td>{{ $collections->sum('payment_count') }}</td>
                    <td>৳{{ number_format($collections->sum('total_amount'), 2) }}</td>
                    <td>-</td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
</div>
@endsection
