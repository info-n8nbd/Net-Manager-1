@extends('layouts.admin')

@section('title', 'Additional Payments')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.clients.index') }}">Clients</a></li>
<li class="breadcrumb-item"><a href="{{ route('admin.clients.show', $client) }}">{{ $client->name }}</a></li>
<li class="breadcrumb-item active">Additional Payments</li>
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title"><i class="fas fa-shopping-cart mr-2"></i>Additional Payments (Equipment, ONU, Adapter, etc.)</h3>
                <a href="{{ route('admin.clients.additional-payments.create', $client) }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus mr-1"></i> Add Payment
                </a>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Receipt No</th>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Description</th>
                            <th class="text-right">Amount</th>
                            <th>Payment Method</th>
                            <th>Collected By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payments as $payment)
                        <tr>
                            <td><code>{{ $payment->receipt_no }}</code></td>
                            <td>{{ $payment->payment_date->format('d-m-Y') }}</td>
                            <td>
                                <span class="badge badge-info">{{ ucfirst($payment->payment_type) }}</span>
                            </td>
                            <td>{{ $payment->description ?? '-' }}</td>
                            <td class="text-right text-success font-weight-bold">৳{{ number_format($payment->amount ?? 0, 2) }}</td>
                            <td>{{ $payment->paymentMethod->name ?? 'Cash' }}</td>
                            <td>{{ $payment->collector->name ?? 'System' }}</td>
                            <td>
                                <form action="{{ route('admin.clients.additional-payments.destroy', [$client, $payment]) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this payment record?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-xs">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                <i class="fas fa-inbox fa-2x mb-2"></i><br>
                                No additional payments recorded
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                    @if($payments->count() > 0)
                    <tfoot>
                        <tr class="bg-light">
                            <td colspan="4" class="text-right font-weight-bold">Total:</td>
                            <td class="text-right font-weight-bold text-success">৳{{ number_format($payments->sum('amount'), 2) }}</td>
                            <td colspan="3"></td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
</div>

<div class="row mt-3">
    <div class="col-12">
        <a href="{{ route('admin.clients.show', $client) }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left mr-1"></i> Back to Client
        </a>
    </div>
</div>
@endsection
