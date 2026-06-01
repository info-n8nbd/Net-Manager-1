@extends('layouts.admin')

@section('title', 'Override Audit Log')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1"><i class="fas fa-history me-2"></i>Override Audit Log</h4>
            <p class="text-muted mb-0">Complete audit trail of all admin override actions</p>
        </div>
        <a href="{{ route('admin.overrides.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>Back to Overrides
        </a>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Category</label>
                    <select name="category" class="form-select">
                        <option value="">All Categories</option>
                        <option value="payment" {{ request('category') == 'payment' ? 'selected' : '' }}>Payment</option>
                        <option value="cash" {{ request('category') == 'cash' ? 'selected' : '' }}>Cash</option>
                        <option value="bill" {{ request('category') == 'bill' ? 'selected' : '' }}>Bill</option>
                        <option value="status" {{ request('category') == 'status' ? 'selected' : '' }}>Status</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Start Date</label>
                    <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">End Date</label>
                    <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-filter me-1"></i>Filter</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Admin</th>
                            <th>Technician</th>
                            <th>Client</th>
                            <th>Category</th>
                            <th>Override Type</th>
                            <th>Reason</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                        <tr>
                            <td>{{ $log->id }}</td>
                            <td>{{ $log->admin->name ?? 'N/A' }}</td>
                            <td>{{ $log->technicianActionLog->technician->name ?? 'N/A' }}</td>
                            <td>{{ $log->client->name ?? 'N/A' }}</td>
                            <td><span class="badge bg-info">{{ ucfirst($log->action_category ?? 'N/A') }}</span></td>
                            <td>{{ $log->override_type ?? 'N/A' }}</td>
                            <td>{{ Str::limit($log->reason ?? '-', 50) }}</td>
                            <td>{{ $log->created_at->format('d-m-y H:i') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted">No audit log entries found</td>
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
