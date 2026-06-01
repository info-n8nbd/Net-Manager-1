@extends('layouts.manager')

@section('title', 'Resellers')

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-store mr-2"></i>Resellers in Branch</h3>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>Total Clients</th>
                        <th>Active Clients</th>
                        <th>Wallet Balance</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($resellers ?? [] as $reseller)
                    <tr>
                        <td>{{ $reseller->name }}</td>
                        <td>{{ $reseller->phone ?? 'N/A' }}</td>
                        <td>{{ $reseller->clients_count ?? 0 }}</td>
                        <td>{{ $reseller->active_clients_count ?? 0 }}</td>
                        <td>৳{{ number_format($reseller->wallet_balance ?? 0, 2) }}</td>
                        <td>
                            <span class="badge badge-{{ $reseller->status == 'active' ? 'success' : 'secondary' }}">
                                {{ ucfirst($reseller->status ?? 'active') }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted">No resellers found</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if(isset($resellers) && $resellers->hasPages())
        <div class="mt-3">
            {{ $resellers->appends(request()->query())->links() }}
        </div>
        @endif
    </div>
</div>
@endsection