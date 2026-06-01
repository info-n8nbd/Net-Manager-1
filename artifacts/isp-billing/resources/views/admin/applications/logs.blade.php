@extends('layouts.admin')

@section('title', 'Application Status Logs')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="#">Applications</a></li>
<li class="breadcrumb-item"><a href="{{ route('admin.applications.show', $application) }}">{{ $application->application_number }}</a></li>
<li class="breadcrumb-item active">Status Logs</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Status Change Logs - {{ $application->application_number }} ({{ $application->full_name_english }})</h3>
    </div>
    <div class="card-body">
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>Date & Time</th>
                    <th>Previous Status</th>
                    <th>New Status</th>
                    <th>Changed By</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                <tr>
                    <td>{{ $log->created_at->format('d-m-y H:i') }}</td>
                    <td>
                        <span class="badge badge-secondary">{{ ucfirst(str_replace('_', ' ', $log->from_status ?? 'N/A')) }}</span>
                    </td>
                    <td>
                        <span class="badge badge-primary">{{ ucfirst(str_replace('_', ' ', $log->to_status ?? 'N/A')) }}</span>
                    </td>
                    <td>{{ $log->changedByUser->name ?? 'System' }}</td>
                    <td>{{ $log->remarks ?? '-' }}</td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center">No status change logs found</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
