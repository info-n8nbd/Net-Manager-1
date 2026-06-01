@extends('layouts.admin')

@section('title', 'Admin Overrides')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1"><i class="fas fa-shield-alt me-2"></i>Admin Overrides</h4>
            <p class="text-muted mb-0">Review and override technician actions</p>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Category</label>
                    <select name="category" class="form-select">
                        <option value="">All Categories</option>
                        <option value="payment" {{ request('category') == 'payment' ? 'selected' : '' }}>Payment</option>
                        <option value="cash" {{ request('category') == 'cash' ? 'selected' : '' }}>Cash Settlement</option>
                        <option value="bill" {{ request('category') == 'bill' ? 'selected' : '' }}>Bill</option>
                        <option value="status" {{ request('category') == 'status' ? 'selected' : '' }}>Client Status</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="overridden" {{ request('status') == 'overridden' ? 'selected' : '' }}>Overridden</option>
                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
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
                            <th>Technician</th>
                            <th>Client</th>
                            <th>Category</th>
                            <th>Action</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($actions as $action)
                        <tr>
                            <td>{{ $action->id }}</td>
                            <td>{{ $action->technician->name ?? 'N/A' }}</td>
                            <td>{{ $action->client->name ?? 'N/A' }}</td>
                            <td><span class="badge bg-info">{{ ucfirst($action->action_category ?? 'N/A') }}</span></td>
                            <td>{{ $action->action_type ?? 'N/A' }}</td>
                            <td>
                                @if($action->status == 'pending')
                                    <span class="badge bg-warning">Pending</span>
                                @elseif($action->status == 'overridden')
                                    <span class="badge bg-primary">Overridden</span>
                                @else
                                    <span class="badge bg-success">{{ ucfirst($action->status) }}</span>
                                @endif
                            </td>
                            <td>{{ $action->created_at->format('d-m-y H:i') }}</td>
                            <td>
                                <a href="{{ route('admin.overrides.show', $action) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted">No action logs found</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($actions->hasPages())
            <div class="mt-3">{{ $actions->withQueryString()->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
