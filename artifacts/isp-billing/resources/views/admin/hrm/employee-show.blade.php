@extends('layouts.admin')

@section('title', 'Employee Details')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.hrm.employees') }}">HRM</a></li>
<li class="breadcrumb-item active">{{ $employee->name }}</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-4">
        <div class="card card-primary card-outline">
            <div class="card-body box-profile">
                <div class="text-center">
                    @if($employee->photo)
                    <img class="profile-user-img img-fluid img-circle" src="{{ asset('storage/' . $employee->photo) }}" alt="{{ $employee->name }}" style="width: 100px; height: 100px; object-fit: cover;">
                    @else
                    <img class="profile-user-img img-fluid img-circle" src="https://ui-avatars.com/api/?name={{ urlencode($employee->name) }}&background=1e3a5f&color=fff&size=100" alt="Employee">
                    @endif
                </div>
                <h3 class="profile-username text-center mt-2 mb-0" style="font-size: 16px;">{{ $employee->name }}</h3>
                <p class="text-muted text-center mb-2" style="font-size: 13px;">{{ $employee->designation->name ?? 'Staff' }}</p>

                <table class="table table-sm table-borderless mb-2" style="font-size: 13px;">
                    <tr>
                        <td class="text-muted py-1" style="width: 45%;">Employee ID</td>
                        <td class="py-1 text-right font-weight-bold">{{ $employee->employee_id ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted py-1">Department</td>
                        <td class="py-1 text-right font-weight-bold">{{ $employee->department->name ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted py-1">Joining Date</td>
                        <td class="py-1 text-right font-weight-bold">{{ $employee->joining_date?->format('d-m-Y') ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted py-1">Basic Salary</td>
                        <td class="py-1 text-right font-weight-bold">৳{{ number_format($employee->basic_salary, 0) }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted py-1">Phone</td>
                        <td class="py-1 text-right font-weight-bold">{{ $employee->phone ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted py-1">Email</td>
                        <td class="py-1 text-right font-weight-bold" style="word-break: break-all; font-size: 12px;">{{ $employee->email ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted py-1">NID</td>
                        <td class="py-1 text-right font-weight-bold">{{ $employee->nid ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted py-1">Address</td>
                        <td class="py-1 text-right" style="font-size: 12px;">{{ $employee->address ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted py-1">Status</td>
                        <td class="py-1 text-right">
                            <span class="badge badge-{{ $employee->status === 'active' ? 'success' : 'danger' }}">{{ ucfirst($employee->status) }}</span>
                        </td>
                    </tr>
                </table>

                <a href="{{ route('admin.hrm.employees.cv', $employee) }}" class="btn btn-primary btn-sm btn-block">
                    <i class="fas fa-file-pdf mr-1"></i>Download CV
                </a>
            </div>
        </div>

        <div class="card card-outline card-success">
            <div class="card-header py-2">
                <h3 class="card-title" style="font-size: 14px;"><i class="fas fa-sign-in-alt mr-1"></i>Login Details</h3>
            </div>
            <div class="card-body p-3">
                @if($employee->user)
                <table class="table table-sm table-borderless mb-2" style="font-size: 13px;">
                    <tr>
                        <td class="text-muted py-1" style="width: 35%;">Email</td>
                        <td class="py-1" style="word-break: break-all;">{{ $employee->user->email }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted py-1">Role</td>
                        <td class="py-1">{{ $employee->user->getRoleNames()->implode(', ') ?: 'No role' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted py-1">Status</td>
                        <td class="py-1">
                            @if($employee->user->is_active ?? true)
                            <span class="badge badge-success">Active</span>
                            @else
                            <span class="badge badge-danger">Inactive</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted py-1">Last Login</td>
                        <td class="py-1">{{ $employee->user->last_login_at ? \Carbon\Carbon::parse($employee->user->last_login_at)->format('d-m-y h:i A') : 'Never' }}</td>
                    </tr>
                </table>
                <form action="{{ route('admin.impersonate', $employee->user) }}" method="POST" onsubmit="return confirm('Login as {{ addslashes($employee->name) }}?')">
                    @csrf
                    <button type="submit" class="btn btn-success btn-sm btn-block">
                        <i class="fas fa-sign-in-alt mr-1"></i>Login As {{ $employee->name }}
                    </button>
                </form>
                @else
                <div class="text-center text-muted py-2">
                    <i class="fas fa-user-slash fa-2x mb-1"></i>
                    <p class="mb-0" style="font-size: 13px;">No login account linked</p>
                </div>
                @endif
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card">
            <div class="card-header p-2">
                <ul class="nav nav-pills">
                    <li class="nav-item"><a class="nav-link active" href="#attendance" data-toggle="tab"><i class="fas fa-calendar-check mr-1"></i>Attendance</a></li>
                    <li class="nav-item"><a class="nav-link" href="#salary" data-toggle="tab"><i class="fas fa-money-check-alt mr-1"></i>Salary History</a></li>
                    <li class="nav-item"><a class="nav-link" href="#documents" data-toggle="tab"><i class="fas fa-folder-open mr-1"></i>Documents</a></li>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content">
                    <div class="active tab-pane" id="attendance">
                        <table class="table table-bordered table-striped table-sm">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Check In</th>
                                    <th>Lunch Start</th>
                                    <th>Lunch End</th>
                                    <th>Check Out</th>
                                    <th class="text-center">Work Hours</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($employee->attendances ?? [] as $att)
                                <tr>
                                    <td>{{ $att->attendance_date->format('d-m-Y') }}</td>
                                    <td>
                                        @php
                                            $statusColors = [
                                                'present' => 'success', 'absent' => 'danger', 'late' => 'warning',
                                                'leave' => 'info', 'half_day' => 'secondary', 'remote' => 'primary',
                                                'holiday' => 'dark', 'weekend' => 'light',
                                            ];
                                        @endphp
                                        <span class="badge badge-{{ $statusColors[$att->status] ?? 'secondary' }}">
                                            {{ ucfirst(str_replace('_', ' ', $att->status)) }}
                                        </span>
                                    </td>
                                    <td>{{ $att->check_in ? \Carbon\Carbon::parse($att->check_in)->format('h:i A') : '-' }}</td>
                                    <td>{{ $att->lunch_start ? \Carbon\Carbon::parse($att->lunch_start)->format('h:i A') : '-' }}</td>
                                    <td>{{ $att->lunch_end ? \Carbon\Carbon::parse($att->lunch_end)->format('h:i A') : '-' }}</td>
                                    <td>{{ $att->check_out ? \Carbon\Carbon::parse($att->check_out)->format('h:i A') : '-' }}</td>
                                    <td class="text-center">
                                        @if($att->total_work_hours > 0)
                                        <span class="badge badge-{{ $att->total_work_hours >= 8 ? 'success' : ($att->total_work_hours >= 6 ? 'warning' : 'danger') }}">
                                            {{ number_format($att->total_work_hours, 1) }}h
                                        </span>
                                        @else
                                        -
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="7" class="text-center text-muted">No attendance records</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="tab-pane" id="salary">
                        @if(isset($salaryHistory) && $salaryHistory->count() > 0)
                        <table class="table table-bordered table-striped table-sm">
                            <thead>
                                <tr>
                                    <th>Period</th>
                                    <th class="text-right">Basic</th>
                                    <th class="text-right">Allowances</th>
                                    <th class="text-right">Deductions</th>
                                    <th class="text-right">Net Salary</th>
                                    <th class="text-center">Days</th>
                                    <th class="text-center">Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($salaryHistory as $sal)
                                <tr>
                                    <td>{{ date('F Y', mktime(0,0,0,$sal->month,1,$sal->year)) }}</td>
                                    <td class="text-right">৳{{ number_format($sal->basic_salary, 0) }}</td>
                                    <td class="text-right">৳{{ number_format($sal->allowances ?? 0, 0) }}</td>
                                    <td class="text-right text-danger">৳{{ number_format($sal->deductions ?? 0, 0) }}</td>
                                    <td class="text-right"><strong>৳{{ number_format($sal->net_salary, 0) }}</strong></td>
                                    <td class="text-center">{{ $sal->working_days ?? '-' }}</td>
                                    <td class="text-center">
                                        <span class="badge badge-{{ $sal->status === 'paid' ? 'success' : ($sal->status === 'partial' ? 'info' : 'warning') }}">{{ ucfirst($sal->status) }}</span>
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.hrm.salary.edit', $sal) }}" class="btn btn-xs btn-info"><i class="fas fa-edit"></i></a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                        @else
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-money-check-alt fa-2x mb-2"></i>
                            <p>No salary records found for this employee.</p>
                        </div>
                        @endif
                    </div>
                    
                    <div class="tab-pane" id="documents">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="card h-100 mb-0">
                                    <div class="card-header bg-primary text-white py-2">
                                        <i class="fas fa-user mr-1"></i>Employee Photo
                                    </div>
                                    <div class="card-body text-center p-3">
                                        @if($employee->photo)
                                        <img src="{{ asset('storage/' . $employee->photo) }}" alt="Photo" class="img-fluid rounded" style="max-height: 150px;">
                                        @else
                                        <div class="text-muted py-3">
                                            <i class="fas fa-image fa-2x mb-1"></i>
                                            <p class="mb-0 small">No photo</p>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <div class="card h-100 mb-0">
                                    <div class="card-header bg-info text-white py-2">
                                        <i class="fas fa-file-pdf mr-1"></i>CV / Resume
                                    </div>
                                    <div class="card-body text-center p-3">
                                        @if($employee->cv_file)
                                        <i class="fas fa-file-pdf fa-3x text-danger mb-2"></i>
                                        <p class="mb-2 small">CV Available</p>
                                        <a href="{{ asset('storage/' . $employee->cv_file) }}" class="btn btn-sm btn-primary">
                                            <i class="fas fa-download mr-1"></i>Download
                                        </a>
                                        @else
                                        <div class="text-muted py-3">
                                            <i class="fas fa-file-alt fa-2x mb-1"></i>
                                            <p class="mb-0 small">No CV</p>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <div class="card h-100 mb-0">
                                    <div class="card-header bg-success text-white py-2">
                                        <i class="fas fa-id-card mr-1"></i>NID Front
                                    </div>
                                    <div class="card-body text-center p-3">
                                        @if($employee->nid_photo_front)
                                        <img src="{{ asset('storage/' . $employee->nid_photo_front) }}" alt="NID Front" class="img-fluid rounded" style="max-height: 150px;">
                                        @else
                                        <div class="text-muted py-3">
                                            <i class="fas fa-id-card fa-2x mb-1"></i>
                                            <p class="mb-0 small">No NID front</p>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <div class="card h-100 mb-0">
                                    <div class="card-header bg-warning text-dark py-2">
                                        <i class="fas fa-id-card mr-1"></i>NID Back
                                    </div>
                                    <div class="card-body text-center p-3">
                                        @if($employee->nid_photo_back)
                                        <img src="{{ asset('storage/' . $employee->nid_photo_back) }}" alt="NID Back" class="img-fluid rounded" style="max-height: 150px;">
                                        @else
                                        <div class="text-muted py-3">
                                            <i class="fas fa-id-card fa-2x mb-1"></i>
                                            <p class="mb-0 small">No NID back</p>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
