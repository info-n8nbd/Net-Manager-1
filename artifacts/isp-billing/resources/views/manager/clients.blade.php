@extends('layouts.manager')

@section('title', 'Clients')

@section('content')
<style>
.table thead th {
    background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%) !important;
    color: white !important;
    font-weight: 500;
    font-size: 12px;
    white-space: nowrap;
    border-color: #1e3c72 !important;
}
.table td {
    font-size: 13px;
    vertical-align: middle;
}
</style>
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-users mr-2"></i>Clients</h3>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('manager.clients') }}" class="mb-3">
            <div class="row">
                <div class="col-md-3">
                    <input type="text" name="search" class="form-control" placeholder="Search by name, username, phone..." value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-control">
                        <option value="">All Status</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="reseller_id" class="form-control">
                        <option value="">All Resellers</option>
                        @foreach($resellers ?? [] as $reseller)
                        <option value="{{ $reseller->id }}" {{ request('reseller_id') == $reseller->id ? 'selected' : '' }}>{{ $reseller->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-search mr-1"></i> Filter</button>
                </div>
            </div>
        </form>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Username</th>
                        <th>Phone</th>
                        <th>Package</th>
                        <th>Reseller</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($clients ?? [] as $client)
                    <tr>
                        <td>{{ $client->name }}</td>
                        <td>{{ $client->username }}</td>
                        <td>{{ $client->phone }}</td>
                        <td>{{ $client->package->name ?? 'N/A' }}</td>
                        <td>{{ $client->reseller->name ?? 'N/A' }}</td>
                        <td>
                            <span class="badge badge-{{ $client->status == 'active' ? 'success' : ($client->status == 'inactive' ? 'danger' : 'secondary') }}">
                                {{ ucfirst($client->status) }}
                            </span>
                        </td>
                        <td>
                            <a href="{{ route('manager.clients.show', $client) }}" class="btn btn-info btn-sm">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted">No clients found</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if(isset($clients) && $clients->hasPages())
        <div class="mt-3">
            {{ $clients->appends(request()->query())->links() }}
        </div>
        @endif
    </div>
</div>
@endsection