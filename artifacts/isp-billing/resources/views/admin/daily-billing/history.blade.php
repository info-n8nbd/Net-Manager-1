@extends('layouts.admin')

@section('title', 'Billing History')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title"><i class="fas fa-history mr-2"></i>Billing History: {{ $reseller->name }}</h3>
        <a href="{{ route('admin.daily-billing.index') }}" class="btn btn-default btn-sm"><i class="fas fa-arrow-left mr-1"></i> Back</a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover table-sm">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Date</th>
                        <th>Active Clients</th>
                        <th>Minutes</th>
                        <th>Amount</th>
                        <th>Wallet Before</th>
                        <th>Wallet After</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $totalAmount = 0;
                        $totalClients = 0;
                        $totalMinutes = 0;
                    @endphp
                    @forelse($bills ?? [] as $bill)
                    @php
                        $totalAmount += $bill->amount;
                        $totalClients += $bill->active_clients;
                        $totalMinutes += $bill->minutes ?? 0;
                    @endphp
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $bill->billing_date->format('d-m-Y') }}</td>
                        <td>{{ $bill->active_clients }}</td>
                        <td>{{ number_format($bill->minutes ?? 0) }}</td>
                        <td>৳{{ number_format($bill->amount, 2) }}</td>
                        <td>৳{{ number_format($bill->wallet_before, 2) }}</td>
                        <td>৳{{ number_format($bill->wallet_after, 2) }}</td>
                        <td>
                            @if($bill->status == 'paid')
                                <span class="badge badge-success">Paid</span>
                            @elseif($bill->status == 'pending')
                                <span class="badge badge-warning">Pending</span>
                            @elseif($bill->status == 'failed')
                                <span class="badge badge-danger">Failed</span>
                            @else
                                <span class="badge badge-secondary">{{ ucfirst($bill->status) }}</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center text-muted">No billing history found</td></tr>
                    @endforelse
                </tbody>
                @if(($bills ?? collect())->count() > 0)
                <tfoot>
                    <tr class="font-weight-bold bg-light">
                        <td colspan="2">Totals</td>
                        <td>{{ $totalClients }}</td>
                        <td>{{ number_format($totalMinutes) }}</td>
                        <td>৳{{ number_format($totalAmount, 2) }}</td>
                        <td colspan="3"></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>
@endsection
