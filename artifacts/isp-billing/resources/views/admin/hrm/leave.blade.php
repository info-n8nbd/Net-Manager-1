@extends('layouts.admin')

@section('title', 'Leave Management')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.hrm.employees') }}">HRM</a></li>
<li class="breadcrumb-item active">Leave Management</li>
@endsection

@section('content')
<style>
.leave-stat-card {
    border-radius: 10px;
    padding: 15px;
    text-align: center;
    color: #fff;
    margin-bottom: 15px;
}
.leave-stat-card .stat-num { font-size: 24px; font-weight: 700; }
.leave-stat-card .stat-label { font-size: 12px; opacity: 0.9; }
.leave-type-badge {
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
    text-transform: capitalize;
}
</style>

<div class="row mb-3">
    <div class="col-md-3 col-6">
        <div class="leave-stat-card" style="background: linear-gradient(135deg, #f39c12, #e67e22);">
            <div class="stat-num">{{ ($leaves ?? collect())->where('status', 'pending')->count() }}</div>
            <div class="stat-label">Pending</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="leave-stat-card" style="background: linear-gradient(135deg, #27ae60, #2ecc71);">
            <div class="stat-num">{{ ($leaves ?? collect())->where('status', 'approved')->count() }}</div>
            <div class="stat-label">Approved</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="leave-stat-card" style="background: linear-gradient(135deg, #e74c3c, #c0392b);">
            <div class="stat-num">{{ ($leaves ?? collect())->where('status', 'rejected')->count() }}</div>
            <div class="stat-label">Rejected</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="leave-stat-card" style="background: linear-gradient(135deg, #3498db, #2980b9);">
            <div class="stat-num">{{ ($leaves ?? collect())->count() }}</div>
            <div class="stat-label">Total</div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-calendar-plus mr-2"></i>Apply for Leave</h3>
            </div>
            <form action="{{ route('admin.hrm.leave.store') }}" method="POST">
                @csrf
                <div class="card-body">
                    <div class="form-group">
                        <label>Employee <span class="text-danger">*</span></label>
                        <select name="employee_id" class="form-control" required>
                            <option value="">Select Employee</option>
                            @foreach($employees ?? [] as $emp)
                            <option value="{{ $emp->id }}">{{ $emp->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Leave Type <span class="text-danger">*</span></label>
                        <select name="leave_type" class="form-control" required>
                            @foreach(\App\Models\DropdownOption::getOptions('leave_types') as $opt)
                            <option value="{{ \Illuminate\Support\Str::slug($opt, '_') }}">{{ $opt }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>From Date <span class="text-danger">*</span></label>
                                <input type="date" name="start_date" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>To Date <span class="text-danger">*</span></label>
                                <input type="date" name="end_date" class="form-control" required>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Reason</label>
                        <textarea name="reason" class="form-control" rows="2" placeholder="Why is this leave needed?"></textarea>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-save mr-1"></i>Submit Request</button>
                </div>
            </form>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card card-info card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-list mr-2"></i>Leave Requests</h3>
                <div class="card-tools">
                    <form method="GET" class="form-inline">
                        <select name="status_filter" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
                            <option value="">All Status</option>
                            <option value="pending" {{ request('status_filter') == 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="approved" {{ request('status_filter') == 'approved' ? 'selected' : '' }}>Approved</option>
                            <option value="rejected" {{ request('status_filter') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                        </select>
                    </form>
                </div>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover table-striped">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Type</th>
                            <th>From</th>
                            <th>To</th>
                            <th>Days</th>
                            <th>Reason</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($leaves ?? [] as $leave)
                        <tr>
                            <td>{{ $leave->employee->name ?? 'N/A' }}</td>
                            <td><span class="leave-type-badge" style="background: #eef2ff; color: #3b5bdb;">{{ ucfirst(str_replace('_', ' ', $leave->leave_type)) }}</span></td>
                            <td>{{ $leave->start_date->format('d-m-Y') }}</td>
                            <td>{{ $leave->end_date->format('d-m-Y') }}</td>
                            <td><span class="badge badge-secondary">{{ $leave->total_days }}</span></td>
                            <td style="max-width: 150px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $leave->reason }}">{{ $leave->reason ?? '-' }}</td>
                            <td>
                                <span class="badge badge-{{ $leave->status === 'approved' ? 'success' : ($leave->status === 'rejected' ? 'danger' : 'warning') }}">
                                    {{ ucfirst($leave->status) }}
                                </span>
                                @if($leave->status === 'approved' && $leave->approvedBy)
                                <br><small class="text-muted">by {{ $leave->approvedBy->name }}</small>
                                @endif
                                @if($leave->status === 'rejected' && $leave->admin_remarks)
                                <br><small class="text-danger" title="{{ $leave->admin_remarks }}"><i class="fas fa-comment mr-1"></i>{{ Str::limit($leave->admin_remarks, 25) }}</small>
                                @endif
                            </td>
                            <td>
                                @if($leave->status === 'pending')
                                <form action="{{ route('admin.hrm.leave.approve', $leave) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-success" title="Approve"><i class="fas fa-check"></i></button>
                                </form>
                                <button type="button" class="btn btn-sm btn-danger" title="Reject" data-toggle="modal" data-target="#rejectModal{{ $leave->id }}"><i class="fas fa-times"></i></button>
                                @else
                                <span class="text-muted">-</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                <i class="fas fa-calendar-check fa-2x mb-2 d-block"></i>
                                No leave requests found
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if(method_exists($leaves, 'hasPages') && $leaves->hasPages())
            <div class="card-footer">{{ $leaves->appends(request()->query())->links() }}</div>
            @endif
        </div>
    </div>
</div>

@foreach($leaves ?? [] as $leave)
@if($leave->status === 'pending')
<div class="modal fade" id="rejectModal{{ $leave->id }}" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.hrm.leave.reject', $leave) }}" method="POST">
                @csrf
                <div class="modal-header bg-danger">
                    <h5 class="modal-title text-white"><i class="fas fa-times-circle mr-1"></i>Reject Leave</h5>
                    <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <p><strong>Employee:</strong> {{ $leave->employee->name ?? 'N/A' }}</p>
                    <p><strong>Period:</strong> {{ $leave->start_date->format('d-m-Y') }} to {{ $leave->end_date->format('d-m-Y') }} ({{ $leave->total_days }} days)</p>
                    @if($leave->reason)
                    <p><strong>Reason:</strong> {{ $leave->reason }}</p>
                    @endif
                    <div class="form-group">
                        <label>Rejection Reason <span class="text-danger">*</span></label>
                        <textarea name="remarks" class="form-control" rows="3" required placeholder="Why is this leave being rejected?"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger"><i class="fas fa-times mr-1"></i>Reject Leave</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endforeach
@endsection
