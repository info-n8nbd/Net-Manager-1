@extends('layouts.reseller')
@section('title', 'SMS Logs')

@section('content')
<div class="card card-outline card-secondary">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-list mr-2"></i>SMS Logs</h3>
        <div class="card-tools">
            <a href="{{ route('reseller.sms.settings') }}" class="btn btn-sm btn-outline-primary mr-1">
                <i class="fas fa-cog mr-1"></i> Settings
            </a>
            <a href="{{ route('reseller.sms.bulk') }}" class="btn btn-sm btn-outline-primary">
                <i class="fas fa-paper-plane mr-1"></i> Bulk SMS
            </a>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card-body border-bottom py-2">
        <form method="GET" class="form-inline flex-wrap gap-2">
            <div class="form-group mr-2 mb-1">
                <select name="status" class="form-control form-control-sm">
                    <option value="">All Status</option>
                    <option value="sent" {{ request('status') === 'sent' ? 'selected' : '' }}>Sent</option>
                    <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
                    <option value="skipped" {{ request('status') === 'skipped' ? 'selected' : '' }}>Skipped</option>
                </select>
            </div>
            <div class="form-group mr-2 mb-1">
                <select name="event_type" class="form-control form-control-sm">
                    <option value="">All Events</option>
                    @foreach($events as $key => $label)
                        <option value="{{ $key }}" {{ request('event_type') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                    <option value="bulk" {{ request('event_type') === 'bulk' ? 'selected' : '' }}>Bulk SMS</option>
                    <option value="manual" {{ request('event_type') === 'manual' ? 'selected' : '' }}>Manual</option>
                </select>
            </div>
            <div class="form-group mr-2 mb-1">
                <input type="date" name="date" class="form-control form-control-sm" value="{{ request('date') }}">
            </div>
            <button type="submit" class="btn btn-sm btn-outline-secondary mb-1">
                <i class="fas fa-filter mr-1"></i> Filter
            </button>
            @if(request()->hasAny(['status', 'event_type', 'date']))
                <a href="{{ route('reseller.sms.logs') }}" class="btn btn-sm btn-outline-secondary mb-1 ml-1">
                    <i class="fas fa-times mr-1"></i> Reset
                </a>
            @endif
        </form>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>#</th>
                        <th>Date</th>
                        <th>Client</th>
                        <th>Phone</th>
                        <th>Event</th>
                        <th>Message</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                    <tr>
                        <td>{{ $log->id }}</td>
                        <td><small>{{ $log->created_at->format('d-m-y H:i') }}</small></td>
                        <td>
                            @if($log->client)
                                <small>{{ $log->client->name }}</small>
                            @else
                                <small class="text-muted">-</small>
                            @endif
                        </td>
                        <td><small>{{ $log->phone }}</small></td>
                        <td>
                            <span class="badge badge-light">
                                {{ $events[$log->event_type] ?? ucfirst($log->event_type ?? '-') }}
                            </span>
                        </td>
                        <td style="max-width:250px;">
                            <small class="text-truncate d-block" title="{{ $log->message }}">{{ Str::limit($log->message, 60) }}</small>
                            @if($log->skip_reason)
                                <small class="text-muted"><i class="fas fa-info-circle"></i> {{ $log->skip_reason }}</small>
                            @endif
                        </td>
                        <td>
                            @if($log->status === 'sent')
                                <span class="badge badge-success">Sent</span>
                            @elseif($log->status === 'failed')
                                <span class="badge badge-danger" title="{{ $log->response }}">Failed</span>
                            @else
                                <span class="badge badge-secondary">Skipped</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                            No SMS logs found.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($logs->hasPages())
    <div class="card-footer">
        {{ $logs->appends(request()->query())->links() }}
    </div>
    @endif
</div>
@endsection
