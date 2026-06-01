@extends('layouts.sub-reseller')

@section('title', 'Clients')

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-users mr-2"></i>Clients</h3>
        <div class="card-tools">
            <a href="{{ route('sub-reseller.clients.create') }}" class="btn btn-primary btn-sm">
                <i class="fas fa-user-plus mr-1"></i> Add Client
            </a>
        </div>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('sub-reseller.clients') }}" class="mb-3">
            <div class="row">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control" placeholder="Search by name, username, phone..." value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-control">
                        <option value="">All Status</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
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
                        <td>
                            <span class="badge badge-{{ $client->status == 'active' ? 'success' : ($client->status == 'inactive' ? 'danger' : 'secondary') }}">
                                {{ ucfirst($client->status) }}
                            </span>
                        </td>
                        <td>
                            <a href="{{ route('sub-reseller.clients.show', $client) }}" class="btn btn-info btn-sm">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('sub-reseller.clients.edit', $client) }}" class="btn btn-warning btn-sm">
                                <i class="fas fa-edit"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted">No clients found</td>
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