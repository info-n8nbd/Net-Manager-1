@extends('layouts.admin')

@section('title', 'Integrity Check History')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1"><i class="fas fa-shield-alt me-2"></i>Integrity Check History</h4>
            <p class="text-muted mb-0">History of all data integrity verification runs</p>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Run By</th>
                            <th>Status</th>
                            <th>Total Checks</th>
                            <th>Passed</th>
                            <th>Failed</th>
                            <th>Issues Found</th>
                            <th>Duration</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                        <tr>
                            <td>{{ $log->id }}</td>
                            <td>{{ $log->runBy->name ?? 'System' }}</td>
                            <td>
                                @if($log->status == 'passed')
                                    <span class="badge bg-success">Passed</span>
                                @elseif($log->status == 'failed')
                                    <span class="badge bg-danger">Failed</span>
                                @else
                                    <span class="badge bg-warning">{{ ucfirst($log->status ?? 'Unknown') }}</span>
                                @endif
                            </td>
                            <td>{{ $log->total_checks ?? 0 }}</td>
                            <td class="text-success">{{ $log->passed_checks ?? 0 }}</td>
                            <td class="text-danger">{{ $log->failed_checks ?? 0 }}</td>
                            <td>{{ $log->issues_count ?? 0 }}</td>
                            <td>{{ $log->duration ?? 'N/A' }}s</td>
                            <td>{{ $log->created_at->format('d-m-y H:i') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted">No integrity check history found</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($logs->hasPages())
            <div class="mt-3">{{ $logs->withQueryString()->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
