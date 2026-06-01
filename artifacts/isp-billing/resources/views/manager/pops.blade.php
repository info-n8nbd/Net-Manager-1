@extends('layouts.manager')

@section('title', 'POPs')

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-broadcast-tower mr-2"></i>Points of Presence (POPs)</h3>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Location</th>
                        <th>Clients</th>
                        <th>Bandwidth</th>
                        <th>Status</th>
                        <th>Uptime</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pops ?? [] as $pop)
                    <tr>
                        <td>{{ $pop->name }}</td>
                        <td>{{ $pop->location ?? 'N/A' }}</td>
                        <td>{{ $pop->clients_count ?? 0 }}</td>
                        <td>{{ $pop->bandwidth ?? 'N/A' }}</td>
                        <td>
                            <span class="badge badge-{{ $pop->status == 'active' ? 'success' : 'danger' }}">
                                {{ ucfirst($pop->status ?? 'unknown') }}
                            </span>
                        </td>
                        <td>{{ $pop->uptime ?? 'N/A' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted">No POPs found</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if(isset($pops) && $pops->hasPages())
        <div class="mt-3">
            {{ $pops->appends(request()->query())->links() }}
        </div>
        @endif
    </div>
</div>
@endsection