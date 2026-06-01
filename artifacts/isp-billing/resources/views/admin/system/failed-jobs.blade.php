@extends('layouts.admin')

@section('title', 'Failed Jobs')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1"><i class="fas fa-exclamation-triangle me-2"></i>Failed Jobs</h4>
            <p class="text-muted mb-0">Background jobs that failed to execute</p>
        </div>
        <a href="{{ route('admin.system.health') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>Back to System Health
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Job Name</th>
                            <th>Queue</th>
                            <th>Error Message</th>
                            <th>Attempts</th>
                            <th>Status</th>
                            <th>Failed At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($jobs as $job)
                        <tr>
                            <td>{{ $job->id }}</td>
                            <td>{{ $job->job_name ?? class_basename($job->payload ?? 'Unknown') }}</td>
                            <td>{{ $job->queue ?? 'default' }}</td>
                            <td>
                                <span title="{{ $job->error_message ?? $job->exception ?? '' }}">
                                    {{ Str::limit($job->error_message ?? $job->exception ?? '-', 60) }}
                                </span>
                            </td>
                            <td>{{ $job->attempts ?? 1 }}</td>
                            <td>
                                @if(($job->status ?? 'failed') == 'failed')
                                    <span class="badge bg-danger">Failed</span>
                                @elseif($job->status == 'retrying')
                                    <span class="badge bg-warning">Retrying</span>
                                @else
                                    <span class="badge bg-secondary">{{ ucfirst($job->status) }}</span>
                                @endif
                            </td>
                            <td>{{ $job->created_at->format('d-m-y H:i') }}</td>
                            <td>
                                @if(($job->status ?? 'failed') == 'failed')
                                <form method="POST" action="{{ route('admin.system.failed-jobs') }}/{{ $job->id }}/retry" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-warning" title="Retry">
                                        <i class="fas fa-redo"></i>
                                    </button>
                                </form>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted">No failed jobs found</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($jobs->hasPages())
            <div class="mt-3">{{ $jobs->withQueryString()->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
