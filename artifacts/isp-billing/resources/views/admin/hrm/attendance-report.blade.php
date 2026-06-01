@extends('layouts.admin')

@section('title', 'Attendance Report')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.hrm.attendance') }}">HRM</a></li>
<li class="breadcrumb-item"><a href="{{ route('admin.hrm.attendance') }}">Attendance</a></li>
<li class="breadcrumb-item active">Monthly Report</li>
@endsection

@section('content')
<style>
.report-card {
    border: none;
    border-radius: 10px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.06);
}
.report-card .card-header {
    background: #fff;
    border-bottom: 1px solid #f0f0f0;
    padding: 15px 20px;
    border-radius: 10px 10px 0 0 !important;
}
.summary-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}
.summary-badge .dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    display: inline-block;
}
.emp-report-card {
    border: 1px solid #eee;
    border-radius: 10px;
    padding: 20px;
    margin-bottom: 20px;
    background: #fff;
    transition: box-shadow 0.15s;
}
.emp-report-card:hover { box-shadow: 0 4px 15px rgba(0,0,0,0.08); }
.progress-thin { height: 6px; border-radius: 3px; }
.day-cell {
    display: inline-flex;
    width: 26px;
    height: 26px;
    align-items: center;
    justify-content: center;
    border-radius: 5px;
    font-size: 10px;
    font-weight: 600;
    margin: 1px;
    cursor: default;
}
.day-present { background: #d4edda; color: #155724; }
.day-absent { background: #f8d7da; color: #721c24; }
.day-late { background: #fff3cd; color: #856404; }
.day-leave { background: #cce5ff; color: #004085; }
.day-half_day { background: #e2e3e5; color: #383d41; }
.day-remote { background: #d1ecf1; color: #0c5460; }
.day-holiday { background: #c3e6cb; color: #155724; }
.day-weekend { background: #f5f5f5; color: #999; }
.day-empty { background: transparent; color: #ccc; }
.stat-item { text-align: center; padding: 8px; }
.stat-item .stat-val { font-size: 22px; font-weight: 700; }
.stat-item .stat-lbl { font-size: 11px; color: #888; text-transform: uppercase; letter-spacing: 0.3px; }
</style>

<div class="card report-card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
        <div>
            <h5 class="mb-0" style="font-weight: 700; color: #2d3436;">
                <i class="fas fa-chart-bar mr-2" style="color: #007bff;"></i>Monthly Attendance Report
            </h5>
            <small class="text-muted">{{ \Carbon\Carbon::create($year, $month)->format('F Y') }} &middot; {{ $daysInMonth }} days</small>
        </div>
        <div class="d-flex align-items-center flex-wrap" style="gap: 8px;">
            <form method="GET" class="d-flex align-items-center" style="gap: 6px;">
                <select name="month" class="form-control form-control-sm" style="border-radius: 8px; width: 120px;">
                    @for($m = 1; $m <= 12; $m++)
                    <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>{{ \Carbon\Carbon::create(null, $m)->format('F') }}</option>
                    @endfor
                </select>
                <select name="year" class="form-control form-control-sm" style="border-radius: 8px; width: 90px;">
                    @for($y = date('Y'); $y >= date('Y') - 3; $y--)
                    <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
                <select name="employee_id" class="form-control form-control-sm" style="border-radius: 8px; width: 160px;">
                    <option value="">All Employees</option>
                    @foreach($employees as $emp)
                    <option value="{{ $emp->id }}" {{ $employeeId == $emp->id ? 'selected' : '' }}>{{ $emp->name }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-sm btn-primary" style="border-radius: 8px;">
                    <i class="fas fa-filter"></i> Filter
                </button>
            </form>
            <a href="{{ route('admin.hrm.attendance') }}" class="btn btn-sm btn-outline-secondary" style="border-radius: 8px;">
                <i class="fas fa-arrow-left mr-1"></i>Daily View
            </a>
        </div>
    </div>
    <div class="card-body">
        @if(empty($reportData))
            <div class="text-center py-5">
                <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                <p class="text-muted">No attendance records found for the selected period.</p>
            </div>
        @endif

        @foreach($reportData as $empId => $data)
        @php
            $emp = $data['employee'];
            $records = $data['records']->keyBy(fn($r) => $r->attendance_date->day);
            $attendanceRate = $daysInMonth > 0 ? round(($data['effective_days'] / $daysInMonth) * 100) : 0;
            $colors = ['#3498db','#2ecc71','#e74c3c','#9b59b6','#f39c12','#1abc9c'];
            $bgColor = $colors[$loop->index % count($colors)];
            $initials = collect(explode(' ', $emp->name))->map(fn($w) => strtoupper(substr($w, 0, 1)))->take(2)->join('');
        @endphp
        <div class="emp-report-card">
            <div class="row">
                <div class="col-md-3">
                    <div class="d-flex align-items-center mb-3" style="gap: 12px;">
                        <div style="width: 48px; height: 48px; border-radius: 50%; background: {{ $bgColor }}; display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 700; font-size: 18px;">{{ $initials }}</div>
                        <div>
                            <h6 class="mb-0" style="font-weight: 700;">{{ $emp->name }}</h6>
                            <small class="text-muted">{{ $emp->designation->name ?? '' }} {{ $emp->department ? '· '.$emp->department->name : '' }}</small>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-4 stat-item">
                            <div class="stat-val text-success">{{ $data['present'] }}</div>
                            <div class="stat-lbl">Present</div>
                        </div>
                        <div class="col-4 stat-item">
                            <div class="stat-val text-danger">{{ $data['absent'] }}</div>
                            <div class="stat-lbl">Absent</div>
                        </div>
                        <div class="col-4 stat-item">
                            <div class="stat-val text-warning">{{ $data['late'] }}</div>
                            <div class="stat-lbl">Late</div>
                        </div>
                    </div>
                    <div class="row mt-1">
                        <div class="col-4 stat-item">
                            <div class="stat-val text-primary">{{ $data['leave'] }}</div>
                            <div class="stat-lbl">Leave</div>
                        </div>
                        <div class="col-4 stat-item">
                            <div class="stat-val text-info">{{ $data['remote'] }}</div>
                            <div class="stat-lbl">Remote</div>
                        </div>
                        <div class="col-4 stat-item">
                            <div class="stat-val" style="color: #6c757d;">{{ $data['half_day'] }}</div>
                            <div class="stat-lbl">Half Day</div>
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="d-flex justify-content-between mb-1">
                            <small class="text-muted">Attendance Rate</small>
                            <small class="font-weight-bold">{{ $attendanceRate }}%</small>
                        </div>
                        <div class="progress progress-thin">
                            <div class="progress-bar {{ $attendanceRate >= 80 ? 'bg-success' : ($attendanceRate >= 60 ? 'bg-warning' : 'bg-danger') }}" style="width: {{ $attendanceRate }}%"></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-5">
                    <h6 class="mb-2" style="font-size: 12px; color: #888; text-transform: uppercase; letter-spacing: 0.5px;">Daily Calendar</h6>
                    <div class="d-flex flex-wrap">
                        @for($d = 1; $d <= $daysInMonth; $d++)
                            @php $rec = $records[$d] ?? null; @endphp
                            <div class="day-cell {{ $rec ? 'day-'.$rec->status : 'day-empty' }}" title="Day {{ $d }}: {{ $rec ? ucfirst($rec->status) : 'No record' }}">
                                {{ $d }}
                            </div>
                        @endfor
                    </div>
                    <div class="mt-2 d-flex flex-wrap" style="gap: 8px;">
                        <small><span class="day-cell day-present" style="width:14px;height:14px;font-size:0;"></span> Present</small>
                        <small><span class="day-cell day-absent" style="width:14px;height:14px;font-size:0;"></span> Absent</small>
                        <small><span class="day-cell day-late" style="width:14px;height:14px;font-size:0;"></span> Late</small>
                        <small><span class="day-cell day-leave" style="width:14px;height:14px;font-size:0;"></span> Leave</small>
                        <small><span class="day-cell day-remote" style="width:14px;height:14px;font-size:0;"></span> Remote</small>
                    </div>
                </div>
                <div class="col-md-4">
                    <h6 class="mb-2" style="font-size: 12px; color: #888; text-transform: uppercase; letter-spacing: 0.5px;">Time Summary</h6>
                    <table class="table table-sm table-borderless mb-0" style="font-size: 13px;">
                        <tr>
                            <td class="text-muted">Total Work Hours</td>
                            <td class="text-right font-weight-bold">{{ number_format($data['total_hours'], 1) }}h</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Overtime Hours</td>
                            <td class="text-right font-weight-bold text-primary">{{ number_format($data['overtime_hours'], 1) }}h</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Total Late Minutes</td>
                            <td class="text-right font-weight-bold {{ $data['total_late_min'] > 0 ? 'text-warning' : '' }}">{{ $data['total_late_min'] }} min</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Effective Days</td>
                            <td class="text-right font-weight-bold">{{ $data['effective_days'] }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Avg Hours/Day</td>
                            <td class="text-right font-weight-bold">
                                {{ $data['effective_days'] > 0 ? number_format($data['total_hours'] / $data['effective_days'], 1) : '0.0' }}h
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endsection
