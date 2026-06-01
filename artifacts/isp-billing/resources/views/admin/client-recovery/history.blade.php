@extends('layouts.admin')

@section('title', 'Restore History')

@section('content')
<div class="container-fluid">
    <div class="card card-outline card-info mb-3">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-user mr-2"></i>Client Details</h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3"><strong>Client ID:</strong> {{ $client->client_id ?? $client->id }}</div>
                <div class="col-md-3"><strong>Name:</strong> {{ $client->name }}</div>
                <div class="col-md-3"><strong>Phone:</strong> {{ $client->phone ?? '-' }}</div>
                <div class="col-md-3"><strong>Zone:</strong> {{ $client->zone->name ?? 'N/A' }}</div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-history mr-2"></i>Restore History</h3>
            <div class="card-tools">
                <a href="{{ route('admin.client-recovery.index') }}" class="btn btn-secondary btn-sm">
                    <i class="fas fa-arrow-left mr-1"></i>Back to Client Restore
                </a>
            </div>
        </div>
        <div class="card-body">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Action</th>
                        <th>Performed By</th>
                        <th>Reason</th>
                        <th>Details</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($history as $record)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>
                            @if($record->action === 'deleted')
                                <span class="badge badge-danger"><i class="fas fa-trash mr-1"></i>Deleted</span>
                            @elseif($record->action === 'recovered')
                                <span class="badge badge-success"><i class="fas fa-undo mr-1"></i>Restored</span>
                            @else
                                <span class="badge badge-secondary">{{ ucfirst($record->action) }}</span>
                            @endif
                        </td>
                        <td>{{ $record->user->name ?? 'System' }}</td>
                        <td>{{ $record->reason ?? '-' }}</td>
                        <td>{{ $record->details ?? '-' }}</td>
                        <td>{{ $record->created_at->format('d-m-y H:i') }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center">No restore history found</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
