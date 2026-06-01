@extends('layouts.reseller')

@section('title', 'Due Report')
@section('breadcrumb')
<li class="breadcrumb-item active">Due Report</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-exclamation-triangle mr-2"></i>Due Report</h3>
    </div>
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-4 mb-2">
                <div class="stat-box bg-danger text-white text-center py-3 rounded h-100">
                    <div class="stat-number">৳{{ number_format($totalDue ?? 0, 0) }}</div>
                    <div class="stat-label">Total Due</div>
                </div>
            </div>
            <div class="col-4 mb-2">
                <div class="stat-box bg-warning text-dark text-center py-3 rounded h-100">
                    <div class="stat-number">{{ $clients->count() }}</div>
                    <div class="stat-label">Clients</div>
                </div>
            </div>
            <div class="col-4 mb-2">
                <div class="stat-box bg-info text-white text-center py-3 rounded h-100">
                    <div class="stat-number">৳{{ $clients->count() > 0 ? number_format($totalDue / $clients->count(), 0) : '0' }}</div>
                    <div class="stat-label">Average</div>
                </div>
            </div>
        </div>
        
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Client ID</th>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>Package</th>
                        <th class="text-right">Monthly Bill (৳)</th>
                        <th class="text-right">Due Amount (৳)</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($clients as $client)
                    <tr>
                        <td><code>{{ $client->client_id }}</code></td>
                        <td>{{ $client->name }}</td>
                        <td>
                            <a href="tel:{{ $client->phone }}">{{ $client->phone }}</a>
                        </td>
                        <td>{{ $client->package->name ?? 'N/A' }}</td>
                        <td class="text-right">৳{{ number_format($client->monthly_fee ?? 0) }}</td>
                        <td class="text-right text-danger">
                            <strong>৳{{ number_format($client->due_amount ?? 0, 2) }}</strong>
                        </td>
                        <td>
                            <a href="{{ route('reseller.clients.show', $client) }}" class="btn btn-sm btn-info">
                                <i class="fas fa-eye"></i> View
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center text-success py-4">
                            <i class="fas fa-check-circle fa-2x mb-2"></i><br>
                            No clients with outstanding dues
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="bg-light">
                        <th colspan="5">Total</th>
                        <th class="text-right text-danger">৳{{ number_format($totalDue ?? 0, 2) }}</th>
                        <th></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@endsection
