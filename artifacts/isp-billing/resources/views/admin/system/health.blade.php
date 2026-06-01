@extends('layouts.admin')

@section('title', 'System Health')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1"><i class="fas fa-heartbeat me-2"></i>System Health</h4>
            <p class="text-muted mb-0">Monitor system components and background jobs</p>
        </div>
        <form method="POST" action="{{ route('admin.system.health.check') }}" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-primary"><i class="fas fa-sync-alt me-1"></i>Run Health Check</button>
        </form>
    </div>

    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-left-success">
                <div class="card-body text-center">
                    <h5 class="text-success"><i class="fas fa-check-circle"></i></h5>
                    <p class="mb-0">System Status</p>
                    <h4>{{ $latestStatus['overall'] ?? 'Unknown' }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <h5 class="text-info"><i class="fas fa-clock"></i></h5>
                    <p class="mb-0">Last Check</p>
                    <h4>{{ isset($latestStatus['checked_at']) ? \Carbon\Carbon::parse($latestStatus['checked_at'])->diffForHumans() : 'Never' }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <h5 class="text-warning"><i class="fas fa-exclamation-triangle"></i></h5>
                    <p class="mb-0">Failed Jobs</p>
                    <h4>{{ $failedJobs->count() }}</h4>
                </div>
            </div>
        </div>
    </div>

    @if(is_array($latestStatus) && isset($latestStatus['components']))
    <div class="card mb-4">
        <div class="card-header"><h6 class="mb-0">Component Status</h6></div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Component</th>
                            <th>Status</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($latestStatus['components'] as $name => $component)
                        <tr>
                            <td>{{ ucfirst($name) }}</td>
                            <td>
                                @if(($component['status'] ?? '') == 'ok')
                                    <span class="badge bg-success">OK</span>
                                @else
                                    <span class="badge bg-danger">{{ ucfirst($component['status'] ?? 'Error') }}</span>
                                @endif
                            </td>
                            <td>{{ $component['message'] ?? '-' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    @if($failedJobs->count() > 0)
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0">Recent Failed Jobs</h6>
            <a href="{{ route('admin.system.failed-jobs') }}" class="btn btn-sm btn-outline-primary">View All</a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Job</th>
                            <th>Error</th>
                            <th>Failed At</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($failedJobs as $job)
                        <tr>
                            <td>{{ $job->id }}</td>
                            <td>{{ $job->job_name ?? class_basename($job->payload ?? 'Unknown') }}</td>
                            <td>{{ Str::limit($job->error_message ?? $job->exception ?? '-', 80) }}</td>
                            <td>{{ $job->created_at->format('d-m-y H:i') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
