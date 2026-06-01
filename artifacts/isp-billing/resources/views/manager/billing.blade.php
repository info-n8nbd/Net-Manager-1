@extends('layouts.manager')

@section('title', 'Billing')

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-file-invoice-dollar mr-2"></i>Bills</h3>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Client</th>
                        <th>Reseller</th>
                        <th>Amount</th>
                        <th>Due Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($bills ?? [] as $bill)
                    <tr>
                        <td>{{ $bill->id }}</td>
                        <td>{{ $bill->client->name ?? 'N/A' }}</td>
                        <td>{{ $bill->reseller->name ?? 'N/A' }}</td>
                        <td>৳{{ number_format($bill->amount) }}</td>
                        <td>{{ $bill->due_date ? $bill->due_date->format('d-m-Y') : 'N/A' }}</td>
                        <td>
                            <span class="badge badge-{{ $bill->status == 'paid' ? 'success' : ($bill->status == 'overdue' ? 'danger' : 'warning') }}">
                                {{ ucfirst($bill->status ?? 'pending') }}
                            </span>
                        </td>
                        <td>
                            <a href="{{ route('manager.billing.show', $bill) }}" class="btn btn-info btn-sm">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('manager.billing.print', $bill) }}" class="btn btn-secondary btn-sm">
                                <i class="fas fa-print"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted">No bills found</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if(isset($bills) && $bills->hasPages())
        <div class="mt-3">
            {{ $bills->appends(request()->query())->links() }}
        </div>
        @endif
    </div>
</div>
@endsection