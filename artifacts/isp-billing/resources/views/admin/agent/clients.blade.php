@extends('layouts.admin')
@section('title', 'Clients under ' . $agent->name)
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.agent.index') }}">Referral Partner</a></li>
<li class="breadcrumb-item active">{{ $agent->name }} — Clients</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-4">
        <div class="card card-outline card-primary">
            <div class="card-body text-center">
                <h5 class="mb-1">{{ $agent->name }}</h5>
                <p class="text-muted mb-2">Partner ID: {{ $agent->agent_id }}</p>
                <span class="badge badge-{{ $agent->status === 'active' ? 'success' : 'danger' }} mb-2" style="font-size: 13px; padding: 5px 12px;">
                    Commission {{ $agent->status === 'active' ? 'ON' : 'OFF' }}
                </span>
                <hr>
                <div class="row text-center">
                    <div class="col-6">
                        <h4 class="text-primary mb-0">{{ $clients->count() }}</h4>
                        <small class="text-muted">Total Clients</small>
                    </div>
                    <div class="col-6">
                        <h4 class="text-info mb-0">{{ number_format($agent->default_commission, 2) }}%</h4>
                        <small class="text-muted">Default Commission</small>
                    </div>
                </div>
                <hr>
                <div class="row text-center">
                    <div class="col-12">
                        <h5 class="text-warning mb-0">৳{{ number_format($totalCommission, 2) }}</h5>
                        <small class="text-muted">Total Commission Earned (Clients)</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card card-outline card-success">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-users mr-2"></i>Clients under {{ $agent->name }}</h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover mb-0" id="agentClientTable">
                        <thead class="bg-success text-white">
                            <tr>
                                <th>Client ID</th>
                                <th>Name</th>
                                <th>Username</th>
                                <th>Phone</th>
                                <th>Package</th>
                                <th>Monthly Fee</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($clients as $client)
                            <tr>
                                <td>{{ $client->client_id }}</td>
                                <td><strong>{{ $client->name }}</strong></td>
                                <td>{{ $client->username ?? '-' }}</td>
                                <td>{{ $client->phone ?? '-' }}</td>
                                <td>{{ $client->package->name ?? '-' }}</td>
                                <td>৳{{ number_format($client->monthly_fee ?? 0, 2) }}</td>
                                <td>
                                    <span class="badge badge-{{ $client->status === 'active' ? 'success' : ($client->status === 'inactive' ? 'warning' : 'danger') }}">
                                        {{ ucfirst($client->status) }}
                                    </span>
                                </td>
                                <td>
                                    <a href="{{ route('admin.clients.show', $client) }}" class="btn btn-info btn-xs" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('admin.clients.edit', $client) }}" class="btn btn-warning btn-xs" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-3">No clients assigned to this referral partner</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    if ($('#agentClientTable tbody tr td[colspan]').length === 0) {
        $('#agentClientTable').DataTable({
            "responsive": true,
            "autoWidth": false,
            "pageLength": 25
        });
    }
});
</script>
@endpush
