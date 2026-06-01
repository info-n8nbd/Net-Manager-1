@extends('layouts.admin')

@section('title', 'MikroTik Action Logs')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">MikroTik Action Logs</h1>
        <div>
            @if($stats['failed'] > 0)
            <form action="{{ route('admin.mikrotik.retry-failed') }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-warning">
                    <i class="fas fa-redo mr-1"></i> Retry Failed ({{ $stats['failed'] }})
                </button>
            </form>
            @endif
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-2">
            <div class="card border-left-primary">
                <div class="card-body py-3">
                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Actions</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($stats['total']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-left-success">
                <div class="card-body py-3">
                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Successful</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($stats['success']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-left-danger">
                <div class="card-body py-3">
                    <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Failed</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($stats['failed']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-left-warning">
                <div class="card-body py-3">
                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Pending</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($stats['pending']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-left-info">
                <div class="card-body py-3">
                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Today</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($stats['today']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-left-danger">
                <div class="card-body py-3">
                    <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Failed Today</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($stats['failed_today']) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-filter mr-1"></i> Filters
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.mikrotik.logs') }}">
                <div class="row">
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Status</label>
                            <select name="status" class="form-control">
                                <option value="">All Statuses</option>
                                <option value="success" {{ request('status') == 'success' ? 'selected' : '' }}>Success</option>
                                <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Failed</option>
                                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="retrying" {{ request('status') == 'retrying' ? 'selected' : '' }}>Retrying</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Trigger Source</label>
                            <select name="trigger_source" class="form-control">
                                <option value="">All Sources</option>
                                @foreach($triggerSources as $key => $label)
                                <option value="{{ $key }}" {{ request('trigger_source') == $key ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Action</label>
                            <select name="action" class="form-control">
                                <option value="">All Actions</option>
                                <option value="enable" {{ request('action') == 'enable' ? 'selected' : '' }}>Enable</option>
                                <option value="disable" {{ request('action') == 'disable' ? 'selected' : '' }}>Disable</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>From Date</label>
                            <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>To Date</label>
                            <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Search</label>
                            <input type="text" name="search" class="form-control" placeholder="Username, IP..." value="{{ request('search') }}">
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-search mr-1"></i> Filter</button>
                <a href="{{ route('admin.mikrotik.logs') }}" class="btn btn-secondary"><i class="fas fa-times mr-1"></i> Clear</a>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <i class="fas fa-list mr-1"></i> Action Logs ({{ $logs->total() }})
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Time</th>
                            <th>Client</th>
                            <th>Action</th>
                            <th>Trigger</th>
                            <th>Router IP</th>
                            <th>Status</th>
                            <th>Retries</th>
                            <th>Error</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                        <tr>
                            <td class="small">{{ $log->created_at->format('d-m-y H:i:s') }}</td>
                            <td>
                                @if($log->client)
                                <a href="{{ route('admin.clients.show', $log->client) }}">{{ $log->client->name }}</a>
                                <br><small class="text-muted">{{ $log->username }}</small>
                                @else
                                <span class="text-muted">{{ $log->username ?? 'N/A' }}</span>
                                @endif
                            </td>
                            <td>
                                @if($log->action == 'enable')
                                <span class="badge badge-success"><i class="fas fa-play mr-1"></i>Enable</span>
                                @elseif($log->action == 'disable')
                                <span class="badge badge-danger"><i class="fas fa-stop mr-1"></i>Disable</span>
                                @else
                                <span class="badge badge-secondary">{{ ucfirst($log->action) }}</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge badge-info">{{ $triggerSources[$log->trigger_source] ?? $log->trigger_source }}</span>
                                @if($log->triggeredBy)
                                <br><small class="text-muted">by {{ $log->triggeredBy->name }}</small>
                                @endif
                            </td>
                            <td><code>{{ $log->router_ip ?? 'N/A' }}</code></td>
                            <td>
                                @if($log->status == 'success')
                                <span class="badge badge-success"><i class="fas fa-check mr-1"></i>Success</span>
                                @elseif($log->status == 'failed')
                                <span class="badge badge-danger"><i class="fas fa-times mr-1"></i>Failed</span>
                                @elseif($log->status == 'pending')
                                <span class="badge badge-warning"><i class="fas fa-clock mr-1"></i>Pending</span>
                                @elseif($log->status == 'retrying')
                                <span class="badge badge-info"><i class="fas fa-sync mr-1"></i>Retrying</span>
                                @endif
                            </td>
                            <td>{{ $log->retry_count }}</td>
                            <td class="small text-danger" style="max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $log->error_message }}">
                                {{ $log->error_message ?? '-' }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">No action logs found</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($logs->hasPages())
        <div class="card-footer">
            <div class="d-flex flex-wrap justify-content-between align-items-center mt-2 mb-1">
    <div class="d-flex align-items-center">
        <label class="small text-muted mb-0 mr-2" style="white-space:nowrap;">SHOW</label>
        <select class="form-control form-control-sm bulk-show-entries" style="width:80px;">
            <option value="20" {{ request('per_page',20)==20?'selected':'' }}>20</option>
            <option value="50" {{ request('per_page',20)==50?'selected':'' }}>50</option>
            <option value="100" {{ request('per_page',20)==100?'selected':'' }}>100</option>
            <option value="200" {{ request('per_page',20)==200?'selected':'' }}>200</option>
        </select>
        <label class="small text-muted mb-0 ml-2" style="white-space:nowrap;">ENTRIES</label>
    </div>
    <div>
        {{ $logs->appends(request()->query())->links() }}
    </div>
</div>
<script>
(function(){
    var sel = document.querySelector('.bulk-show-entries');
    if(sel){ sel.addEventListener('change',function(){
        var p=new URLSearchParams(window.location.search);
        p.set('per_page',this.value); p.delete('page');
        window.location=window.location.pathname+'?'+p.toString();
    }); }
})();
</script>
        </div>
        @endif
    </div>
</div>
@endsection
