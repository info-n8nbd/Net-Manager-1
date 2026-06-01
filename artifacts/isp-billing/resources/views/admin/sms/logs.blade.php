@extends('layouts.admin')

@section('title', 'SMS Logs')
@section('breadcrumb')
<li class="breadcrumb-item active">SMS Logs</li>
@endsection

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <div>
                <h4 class="mb-0"><i class="fas fa-history me-2"></i>SMS Logs</h4>
                <p class="text-muted mb-0">View all SMS sending attempts and their status</p>
            </div>
            <a href="{{ route('admin.settings.sms') }}" class="btn btn-outline-primary">
                <i class="fas fa-cog me-2"></i>SMS Settings
            </a>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Filters</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.sms.logs') }}" method="GET" class="row g-3">
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="sent" {{ request('status') == 'sent' ? 'selected' : '' }}>Sent</option>
                        <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Failed</option>
                        <option value="skipped" {{ request('status') == 'skipped' ? 'selected' : '' }}>Skipped</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Event Type</label>
                    <select name="event_type" class="form-select">
                        <option value="">All Events</option>
                        @foreach($eventTypes ?? [] as $key => $label)
                            <option value="{{ $key }}" {{ request('event_type') == $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">From Date</label>
                    <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">To Date</label>
                    <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Phone or message">
                </div>
                <div class="col-md-2 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i></button>
                    <a href="{{ route('admin.sms.logs') }}" class="btn btn-outline-secondary"><i class="fas fa-times"></i></a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Date/Time</th>
                            <th>Phone</th>
                            <th>Client</th>
                            <th>Event</th>
                            <th>Status</th>
                            <th>Message</th>
                            <th>Skip Reason</th>
                            <th>Sent By</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                            <tr>
                                <td class="text-nowrap">{{ $log->created_at->format('d-m-y H:i') }}</td>
                                <td>{{ $log->phone }}</td>
                                <td>
                                    @if($log->client)
                                        <a href="{{ route('admin.clients.show', $log->client) }}">{{ $log->client->name }}</a>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-info">{{ ucwords(str_replace('_', ' ', $log->event_type ?? 'N/A')) }}</span>
                                </td>
                                <td>
                                    @if($log->status == 'sent')
                                        <span class="badge bg-success">Sent</span>
                                    @elseif($log->status == 'failed')
                                        <span class="badge bg-danger">Failed</span>
                                    @elseif($log->status == 'skipped')
                                        <span class="badge bg-warning text-dark">Skipped</span>
                                    @else
                                        <span class="badge bg-secondary">{{ ucfirst($log->status) }}</span>
                                    @endif
                                </td>
                                <td style="max-width: 300px;">
                                    <span class="text-truncate d-inline-block" style="max-width: 280px;" title="{{ $log->message }}">
                                        {{ Str::limit($log->message, 50) }}
                                    </span>
                                </td>
                                <td>
                                    @if($log->skip_reason)
                                        <span class="text-danger" title="{{ $log->skip_reason }}">
                                            {{ Str::limit($log->skip_reason, 30) }}
                                        </span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>{{ $log->sentBy->name ?? 'System' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">
                                    <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                                    No SMS logs found
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($logs->hasPages())
            <div class="card-footer">
                {{ $logs->withQueryString()->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
