@extends('layouts.admin')

@section('title', 'WhatsApp Message Logs')

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fab fa-whatsapp mr-2"></i>Message Logs</h3>
        </div>
        <div class="card-body">
            <form method="GET" class="mb-3">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Status</label>
                            <select name="status" class="form-control">
                                <option value="">All Status</option>
                                <option value="sent" {{ request('status') == 'sent' ? 'selected' : '' }}>Sent</option>
                                <option value="delivered" {{ request('status') == 'delivered' ? 'selected' : '' }}>Delivered</option>
                                <option value="read" {{ request('status') == 'read' ? 'selected' : '' }}>Read</option>
                                <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Failed</option>
                                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>From Date</label>
                            <input type="date" name="from_date" class="form-control" value="{{ request('from_date') }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>To Date</label>
                            <input type="date" name="to_date" class="form-control" value="{{ request('to_date') }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <div>
                                <button type="submit" class="btn btn-primary"><i class="fas fa-search mr-1"></i>Filter</button>
                                <a href="{{ route('admin.whatsapp.logs') }}" class="btn btn-secondary"><i class="fas fa-undo mr-1"></i>Reset</a>
                            </div>
                        </div>
                    </div>
                </div>
            </form>

            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Recipient</th>
                        <th>Template</th>
                        <th>Message</th>
                        <th>Status</th>
                        <th>Sent At</th>
                        <th>Error</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                    <tr>
                        <td>{{ $loop->iteration + ($logs->currentPage() - 1) * $logs->perPage() }}</td>
                        <td>{{ $log->phone_number }}</td>
                        <td>{{ $log->template->name ?? '-' }}</td>
                        <td>{{ Str::limit($log->message, 50) }}</td>
                        <td>
                            @if($log->status === 'sent')
                                <span class="badge badge-success"><i class="fas fa-check mr-1"></i>Sent</span>
                            @elseif($log->status === 'delivered')
                                <span class="badge badge-info"><i class="fas fa-check-double mr-1"></i>Delivered</span>
                            @elseif($log->status === 'read')
                                <span class="badge badge-primary"><i class="fas fa-eye mr-1"></i>Read</span>
                            @elseif($log->status === 'failed')
                                <span class="badge badge-danger"><i class="fas fa-times mr-1"></i>Failed</span>
                            @else
                                <span class="badge badge-warning"><i class="fas fa-clock mr-1"></i>Pending</span>
                            @endif
                        </td>
                        <td>{{ $log->created_at->format('d-m-y H:i') }}</td>
                        <td>{{ $log->error_message ?? '-' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center">No message logs found</td></tr>
                    @endforelse
                </tbody>
            </table>
            {{ $logs->appends(request()->query())->links() }}
        </div>
    </div>
</div>
@endsection
