@extends('layouts.admin')

@section('title', 'Client Restore')

@section('content')
<div class="container-fluid">

    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-user-clock mr-2"></i>Deleted Clients - Restore</h3>
        </div>
        <div class="card-body">
            <form method="GET" class="mb-3">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Search</label>
                            <input type="text" name="search" class="form-control" placeholder="Name, ID, Phone..." value="{{ request('search') }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Zone</label>
                            <select name="zone_id" class="form-control">
                                <option value="">All Zones</option>
                                @foreach($zones as $zone)
                                <option value="{{ $zone->id }}" {{ request('zone_id') == $zone->id ? 'selected' : '' }}>{{ $zone->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <div>
                                <button type="submit" class="btn btn-primary"><i class="fas fa-search mr-1"></i>Search</button>
                                <a href="{{ route('admin.client-recovery.index') }}" class="btn btn-secondary"><i class="fas fa-undo mr-1"></i>Reset</a>
                            </div>
                        </div>
                    </div>
                </div>
            </form>

            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Client ID</th>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>Zone</th>
                        <th>Package</th>
                        <th>Deleted At</th>
                        <th>Deleted By</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($clients as $client)
                    <tr>
                        <td>{{ $client->client_id ?? $client->id }}</td>
                        <td>{{ $client->name }}</td>
                        <td>{{ $client->phone ?? '-' }}</td>
                        <td>{{ $client->zone->name ?? 'N/A' }}</td>
                        <td>{{ $client->package->name ?? 'N/A' }}</td>
                        <td>{{ $client->deleted_at_custom ? \Carbon\Carbon::parse($client->deleted_at_custom)->format('d-m-y H:i') : '-' }}</td>
                        <td>{{ $client->deletedBy->name ?? 'N/A' }}</td>
                        <td>
                            <form action="{{ route('admin.client-recovery.recover', $client) }}" method="POST" class="d-inline" onsubmit="return confirm('Restore this client account? All previous history will be restored.')">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-success" title="Restore">
                                    <i class="fas fa-undo mr-1"></i>Restore
                                </button>
                            </form>
                            <a href="{{ route('admin.client-recovery.history', $client) }}" class="btn btn-sm btn-info" title="History">
                                <i class="fas fa-history"></i>
                            </a>
                            <form action="{{ route('admin.client-recovery.permanent-delete', $client) }}" method="POST" class="d-inline" onsubmit="return confirm('PERMANENT DELETE: This will remove this client from database and MikroTik forever. This action CANNOT be undone. Are you sure?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger" title="Permanent Delete">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center">No deleted clients found</td></tr>
                    @endforelse
                </tbody>
            </table>
            {{ $clients->appends(request()->query())->links() }}
        </div>
    </div>
</div>
@endsection
