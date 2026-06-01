@extends('layouts.admin')

@section('title', 'Attendance Management')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.hrm.attendance') }}">HRM</a></li>
<li class="breadcrumb-item active">Attendance</li>
@endsection

@section('content')
<style>
.att-stat-card {
    border-radius: 8px;
    padding: 15px 18px;
    text-align: center;
    transition: transform 0.15s;
    border: none;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}
.att-stat-card:hover { transform: translateY(-2px); }
.att-stat-card .stat-number { font-size: 28px; font-weight: 700; line-height: 1.2; }
.att-stat-card .stat-label { font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; opacity: 0.85; margin-top: 3px; }
.att-stat-present { background: linear-gradient(135deg, #28a745, #20c997); color: #fff; }
.att-stat-absent { background: linear-gradient(135deg, #dc3545, #e74c3c); color: #fff; }
.att-stat-leave { background: linear-gradient(135deg, #ffc107, #f39c12); color: #fff; }
.att-stat-total { background: linear-gradient(135deg, #007bff, #6610f2); color: #fff; }
.att-stat-unmarked { background: linear-gradient(135deg, #6c757d, #495057); color: #fff; }

.attendance-table th { 
    background: #f8f9fa; 
    font-size: 11px; 
    text-transform: uppercase; 
    letter-spacing: 0.5px; 
    color: #555;
    white-space: nowrap;
    padding: 10px 8px !important;
    border-bottom: 2px solid #dee2e6 !important;
}
.attendance-table td { 
    vertical-align: middle !important; 
    padding: 8px !important;
    font-size: 13px;
}
.attendance-table .emp-info .emp-name { font-weight: 600; color: #2d3436; font-size: 13px; }
.attendance-table .emp-info .emp-meta { font-size: 11px; color: #95a5a6; margin-top: 1px; }

.status-badge {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}
.status-present { background: #d4edda; color: #155724; }
.status-absent { background: #f8d7da; color: #721c24; }
.status-late { background: #fff3cd; color: #856404; }
.status-leave { background: #cce5ff; color: #004085; }
.status-half_day { background: #e2e3e5; color: #383d41; }
.status-remote { background: #d1ecf1; color: #0c5460; }
.status-holiday { background: #d4edda; color: #155724; }
.status-weekend { background: #e2e3e5; color: #6c757d; }

.time-input {
    border: 1px solid #e0e0e0 !important;
    border-radius: 6px !important;
    padding: 4px 6px !important;
    font-size: 12px !important;
    text-align: center;
    transition: border-color 0.2s;
    background: #fff;
    width: 100%;
    min-width: 90px;
}
.time-input:focus { border-color: #007bff !important; box-shadow: 0 0 0 2px rgba(0,123,255,0.15) !important; }
.time-input:disabled { background: #f5f5f5; }

.status-select {
    border: 1px solid #e0e0e0 !important;
    border-radius: 6px !important;
    font-size: 12px !important;
    padding: 4px 6px !important;
    background: #fff;
    min-width: 100px;
    cursor: pointer;
}
.status-select:focus { border-color: #007bff !important; box-shadow: 0 0 0 2px rgba(0,123,255,0.15) !important; }

.emp-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 14px;
    color: #fff;
    flex-shrink: 0;
}

.date-nav { display: flex; align-items: center; gap: 8px; }
.date-nav .btn-nav {
    width: 34px;
    height: 34px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    border: 1px solid #dee2e6;
    background: #fff;
    color: #555;
    cursor: pointer;
    transition: all 0.15s;
    font-size: 14px;
}
.date-nav .btn-nav:hover { background: #007bff; color: #fff; border-color: #007bff; }

.card-modern {
    border: none;
    border-radius: 10px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.06);
}
.card-modern .card-header {
    background: #fff;
    border-bottom: 1px solid #f0f0f0;
    padding: 15px 20px;
    border-radius: 10px 10px 0 0 !important;
}

.monthly-mini-bar {
    display: inline-block;
    height: 6px;
    border-radius: 3px;
    margin-right: 2px;
}

.lunch-group {
    background: #fef9e7;
    border-radius: 6px;
    padding: 2px;
}

.section-divider {
    border-left: 2px solid #e0e0e0;
}

.row-hover:hover { background: #f8f9ff !important; }

.work-hours-pill {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 10px;
    font-size: 11px;
    font-weight: 600;
}
.hours-normal { background: #d4edda; color: #155724; }
.hours-overtime { background: #cce5ff; color: #004085; }
.hours-under { background: #fff3cd; color: #856404; }

.remarks-input {
    border: 1px solid #e8e8e8 !important;
    border-radius: 6px !important;
    font-size: 11px !important;
    padding: 4px 8px !important;
    resize: none;
    width: 100%;
    min-width: 100px;
}

.btn-save-attendance {
    background: linear-gradient(135deg, #007bff, #0056b3);
    border: none;
    border-radius: 8px;
    padding: 10px 30px;
    font-weight: 600;
    font-size: 14px;
    letter-spacing: 0.3px;
    box-shadow: 0 3px 10px rgba(0,123,255,0.3);
    transition: all 0.2s;
}
.btn-save-attendance:hover { transform: translateY(-1px); box-shadow: 0 5px 15px rgba(0,123,255,0.4); }

@media (max-width: 1200px) {
    .attendance-table { font-size: 12px; }
    .time-input, .status-select { font-size: 11px !important; min-width: 80px; }
}
</style>

<div class="row mb-3">
    <div class="col-lg-2 col-md-4 col-6 mb-2">
        <div class="att-stat-card att-stat-total">
            <div class="stat-number">{{ $todaySummary['total'] }}</div>
            <div class="stat-label">Total Employees</div>
        </div>
    </div>
    <div class="col-lg-2 col-md-4 col-6 mb-2">
        <div class="att-stat-card att-stat-present">
            <div class="stat-number">{{ $todaySummary['present'] }}</div>
            <div class="stat-label">Present Today</div>
        </div>
    </div>
    <div class="col-lg-2 col-md-4 col-6 mb-2">
        <div class="att-stat-card att-stat-absent">
            <div class="stat-number">{{ $todaySummary['absent'] }}</div>
            <div class="stat-label">Absent</div>
        </div>
    </div>
    <div class="col-lg-2 col-md-4 col-6 mb-2">
        <div class="att-stat-card att-stat-leave">
            <div class="stat-number">{{ $todaySummary['leave'] }}</div>
            <div class="stat-label">On Leave</div>
        </div>
    </div>
    <div class="col-lg-2 col-md-4 col-6 mb-2">
        <div class="att-stat-card" style="background: linear-gradient(135deg, #17a2b8, #138496); color: #fff;">
            <div class="stat-number">{{ $todaySummary['half_day'] }}</div>
            <div class="stat-label">Half Day</div>
        </div>
    </div>
    <div class="col-lg-2 col-md-4 col-6 mb-2">
        <div class="att-stat-card att-stat-unmarked">
            <div class="stat-number">{{ $todaySummary['not_marked'] }}</div>
            <div class="stat-label">Not Marked</div>
        </div>
    </div>
</div>

<div class="card card-modern">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
        <div>
            <h5 class="mb-0" style="font-weight: 700; color: #2d3436;">
                <i class="fas fa-calendar-check mr-2" style="color: #007bff;"></i>Daily Attendance
            </h5>
            <small class="text-muted">{{ \Carbon\Carbon::parse($date)->format('l, F d, Y') }}</small>
        </div>
        <div class="d-flex align-items-center flex-wrap" style="gap: 10px;">
            <div class="date-nav">
                @php
                    $prevDate = \Carbon\Carbon::parse($date)->subDay()->format('Y-m-d');
                    $nextDate = \Carbon\Carbon::parse($date)->addDay()->format('Y-m-d');
                @endphp
                <a href="{{ route('admin.hrm.attendance', ['date' => $prevDate]) }}" class="btn-nav" title="Previous Day">
                    <i class="fas fa-chevron-left"></i>
                </a>
                <form method="GET" class="d-flex align-items-center" style="gap: 6px;">
                    <input type="date" name="date" class="form-control form-control-sm" style="border-radius: 8px; width: 150px;" value="{{ $date }}">
                    <button type="submit" class="btn btn-sm btn-primary" style="border-radius: 8px; padding: 5px 14px;">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
                <a href="{{ route('admin.hrm.attendance', ['date' => $nextDate]) }}" class="btn-nav" title="Next Day">
                    <i class="fas fa-chevron-right"></i>
                </a>
                <a href="{{ route('admin.hrm.attendance') }}" class="btn btn-sm btn-outline-secondary" style="border-radius: 8px;" title="Go to Today">
                    Today
                </a>
            </div>
            <a href="{{ route('admin.hrm.attendance.report', ['month' => \Carbon\Carbon::parse($date)->month, 'year' => \Carbon\Carbon::parse($date)->year]) }}" class="btn btn-sm btn-outline-info" style="border-radius: 8px;">
                <i class="fas fa-chart-bar mr-1"></i>Monthly Report
            </a>
        </div>
    </div>

    @if($__canAction('hrm', 'manage_attendance'))
    <form action="{{ route('admin.hrm.attendance.store') }}" method="POST" id="attendanceForm">
        @csrf
        <input type="hidden" name="attendance_date" value="{{ $date }}">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered attendance-table mb-0">
                    <thead>
                        <tr>
                            <th style="min-width: 180px;" rowspan="2">Employee</th>
                            <th style="min-width: 110px;" rowspan="2">Status</th>
                            <th style="min-width: 100px;" rowspan="2">Check In</th>
                            <th colspan="2" class="text-center" style="background: #e67e22 !important; color: #fff !important; min-width: 200px; font-weight: 700; letter-spacing: 0.5px; border-bottom: 2px solid #d35400 !important;">
                                &#x1F37D; Lunch Break
                            </th>
                            <th style="min-width: 100px;" rowspan="2">Check Out</th>
                            <th style="min-width: 80px;" class="text-center" rowspan="2">Work Hours</th>
                            <th style="min-width: 110px;" rowspan="2">Remarks</th>
                            <th style="min-width: 160px; background: #1565c0 !important; color: #fff !important; font-weight: 700; letter-spacing: 0.5px;" rowspan="2">&#x1F4F7; Selfie Check</th>
                        </tr>
                        <tr>
                            <th style="background: #f39c12 !important; color: #fff !important; min-width: 100px; font-size: 10px; text-align:center; padding: 5px 4px !important;">&#9660; Start</th>
                            <th style="background: #f39c12 !important; color: #fff !important; min-width: 100px; font-size: 10px; text-align:center; padding: 5px 4px !important;">&#9660; End</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($employees as $index => $emp)
                        @php
                            $att = $attendances[$emp->id] ?? null;
                            $colors = ['#3498db','#2ecc71','#e74c3c','#9b59b6','#f39c12','#1abc9c','#e67e22','#2980b9'];
                            $bgColor = $colors[$index % count($colors)];
                            $initials = collect(explode(' ', $emp->name))->map(fn($w) => strtoupper(substr($w, 0, 1)))->take(2)->join('');
                            $ms = $monthlySummary[$emp->id] ?? [];
                        @endphp
                        <tr class="row-hover" data-employee="{{ $emp->id }}" style="{{ !$att ? 'background-color:#fffde7;' : '' }}">
                            <td>
                                <div class="d-flex align-items-center" style="gap: 10px;">
                                    <div class="emp-avatar" style="background: {{ $bgColor }};">{{ $initials }}</div>
                                    <div class="emp-info">
                                        <div class="emp-name">{{ $emp->name }}</div>
                                        <div class="emp-meta">
                                            {{ $emp->designation->name ?? '' }}
                                            @if($emp->department) &middot; {{ $emp->department->name }} @endif
                                        </div>
                                        <div style="margin-top: 2px;">
                                            @if(($ms['present'] ?? 0) > 0)
                                                <span class="monthly-mini-bar" style="width: {{ min(($ms['present'] ?? 0) * 4, 60) }}px; background: #28a745;" title="{{ $ms['present'] ?? 0 }} present this month"></span>
                                            @endif
                                            @if(($ms['absent'] ?? 0) > 0)
                                                <span class="monthly-mini-bar" style="width: {{ min(($ms['absent'] ?? 0) * 4, 30) }}px; background: #dc3545;" title="{{ $ms['absent'] ?? 0 }} absent"></span>
                                            @endif
                                            @if(($ms['late'] ?? 0) > 0)
                                                <span class="monthly-mini-bar" style="width: {{ min(($ms['late'] ?? 0) * 4, 30) }}px; background: #ffc107;" title="{{ $ms['late'] ?? 0 }} late"></span>
                                            @endif
                                            @if(($ms['leave'] ?? 0) > 0)
                                                <span class="monthly-mini-bar" style="width: {{ min(($ms['leave'] ?? 0) * 4, 30) }}px; background: #007bff;" title="{{ $ms['leave'] ?? 0 }} leave"></span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <select name="attendance[{{ $emp->id }}]" class="form-control status-select status-trigger" data-emp="{{ $emp->id }}" data-saved="{{ $att ? '1' : '0' }}">
                                    <option value="present" {{ ($att?->status ?? 'present') == 'present' ? 'selected' : '' }}>&#9679; Present</option>
                                    <option value="absent" {{ $att?->status == 'absent' ? 'selected' : '' }}>&#9679; Absent</option>
                                    <option value="late" {{ $att?->status == 'late' ? 'selected' : '' }}>&#9679; Late</option>
                                    <option value="half_day" {{ $att?->status == 'half_day' ? 'selected' : '' }}>&#9679; Half Day</option>
                                    <option value="leave" {{ $att?->status == 'leave' ? 'selected' : '' }}>&#9679; Leave</option>
                                    <option value="remote" {{ $att?->status == 'remote' ? 'selected' : '' }}>&#9679; Remote</option>
                                    <option value="holiday" {{ $att?->status == 'holiday' ? 'selected' : '' }}>&#9679; Holiday</option>
                                    <option value="weekend" {{ $att?->status == 'weekend' ? 'selected' : '' }}>&#9679; Weekend</option>
                                </select>
                            </td>
                            <td>
                                <input type="time" name="check_in[{{ $emp->id }}]" class="form-control time-input time-field" data-emp="{{ $emp->id }}" value="{{ $att?->check_in ? \Carbon\Carbon::parse($att->check_in)->format('H:i') : '' }}">
                            </td>
                            <td class="lunch-group">
                                <input type="time" name="lunch_start[{{ $emp->id }}]" class="form-control time-input time-field" data-emp="{{ $emp->id }}" value="{{ $att?->lunch_start ? \Carbon\Carbon::parse($att->lunch_start)->format('H:i') : '' }}" placeholder="Start">
                            </td>
                            <td class="lunch-group">
                                <input type="time" name="lunch_end[{{ $emp->id }}]" class="form-control time-input time-field" data-emp="{{ $emp->id }}" value="{{ $att?->lunch_end ? \Carbon\Carbon::parse($att->lunch_end)->format('H:i') : '' }}" placeholder="End">
                            </td>
                            <td>
                                <input type="time" name="check_out[{{ $emp->id }}]" class="form-control time-input time-field" data-emp="{{ $emp->id }}" value="{{ $att?->check_out ? \Carbon\Carbon::parse($att->check_out)->format('H:i') : '' }}">
                            </td>
                            <td class="text-center">
                                <span class="work-hours-display" data-emp="{{ $emp->id }}">
                                    @if($att && $att->total_work_hours > 0)
                                        @php $wh = $att->total_work_hours; @endphp
                                        <span class="work-hours-pill {{ $wh >= 9 ? 'hours-overtime' : ($wh >= 7 ? 'hours-normal' : 'hours-under') }}">
                                            {{ number_format($wh, 1) }}h
                                        </span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </span>
                            </td>
                            <td>
                                <textarea name="remarks[{{ $emp->id }}]" class="form-control remarks-input" rows="1" placeholder="Notes...">{{ $att?->remarks }}</textarea>
                            </td>
                            @php
                                $empSelfies = $selfieRecords[$emp->user_id] ?? collect();
                                $selfieIn  = $empSelfies->firstWhere('type', 'check_in');
                                $selfieOut = $empSelfies->firstWhere('type', 'check_out');
                            @endphp
                            <td style="background: #f0f9ff;">
                                @if($selfieIn || $selfieOut)
                                <div class="d-flex align-items-center" style="gap:6px;">
                                    @if($selfieIn && $selfieIn->selfie_path)
                                    <img src="{{ asset('storage/'.$selfieIn->selfie_path) }}" class="img-circle" width="36" height="36" style="object-fit:cover;cursor:pointer;" title="Check-in selfie" onclick="window.open(this.src)">
                                    @endif
                                    <div style="font-size:11px;line-height:1.4;">
                                        @if($selfieIn)
                                        <div><span class="badge badge-success badge-sm">In</span> <strong>{{ \Carbon\Carbon::parse($selfieIn->check_in_time)->format('h:i A') }}</strong></div>
                                        @endif
                                        @if($selfieOut)
                                        <div><span class="badge badge-danger badge-sm">Out</span> <strong>{{ \Carbon\Carbon::parse($selfieOut->check_in_time)->format('h:i A') }}</strong></div>
                                        @endif
                                        @if($selfieIn && $selfieOut)
                                        @php
                                            $sIn  = \Carbon\Carbon::parse($date.' '.$selfieIn->check_in_time);
                                            $sOut = \Carbon\Carbon::parse($date.' '.$selfieOut->check_in_time);
                                            $mins = $sIn->diffInMinutes($sOut);
                                        @endphp
                                        <div class="text-info"><i class="fas fa-clock"></i> {{ floor($mins/60) }}h {{ $mins%60 }}m</div>
                                        @endif
                                    </div>
                                </div>
                                @else
                                <span class="text-muted small"><i class="fas fa-camera-slash"></i> No selfie</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer d-flex justify-content-between align-items-center" style="background: #f8f9fa; border-top: 1px solid #eee; padding: 15px 20px;">
            <div>
                <button type="button" class="btn btn-sm btn-outline-secondary mr-2" id="markAllPresent" style="border-radius: 6px;">
                    <i class="fas fa-check-double mr-1"></i>Mark All Present
                </button>
                <button type="button" class="btn btn-sm btn-outline-warning" id="setDefaultTimes" style="border-radius: 6px;">
                    <i class="fas fa-clock mr-1"></i>Set Default Times
                </button>
            </div>
            <button type="submit" class="btn btn-primary btn-save-attendance">
                <i class="fas fa-save mr-2"></i>Save Attendance
            </button>
        </div>
    </form>
    @else
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered attendance-table mb-0">
                <thead>
                    <tr>
                        <th style="min-width: 180px;">Employee</th>
                        <th>Status</th>
                        <th>Check In</th>
                        <th>Lunch Start</th>
                        <th>Lunch End</th>
                        <th>Check Out</th>
                        <th class="text-center">Work Hours</th>
                        <th>Remarks</th>
                        <th style="background: #e8f4f8 !important;"><i class="fas fa-camera mr-1 text-info"></i>Selfie</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($employees as $index => $emp)
                    @php
                        $att = $attendances[$emp->id] ?? null;
                        $colors = ['#3498db','#2ecc71','#e74c3c','#9b59b6','#f39c12','#1abc9c','#e67e22','#2980b9'];
                        $bgColor = $colors[$index % count($colors)];
                        $initials = collect(explode(' ', $emp->name))->map(fn($w) => strtoupper(substr($w, 0, 1)))->take(2)->join('');
                        $empSelfies2 = $selfieRecords[$emp->user_id] ?? collect();
                        $selfieIn2   = $empSelfies2->firstWhere('type', 'check_in');
                        $selfieOut2  = $empSelfies2->firstWhere('type', 'check_out');
                    @endphp
                    <tr>
                        <td>
                            <div class="d-flex align-items-center" style="gap: 10px;">
                                <div class="emp-avatar" style="background: {{ $bgColor }};">{{ $initials }}</div>
                                <div class="emp-info">
                                    <div class="emp-name">{{ $emp->name }}</div>
                                    <div class="emp-meta">{{ $emp->designation->name ?? '' }}</div>
                                </div>
                            </div>
                        </td>
                        <td><span class="status-badge status-{{ $att?->status ?? 'present' }}">{{ ucfirst(str_replace('_', ' ', $att?->status ?? 'Not Marked')) }}</span></td>
                        <td>{{ $att?->check_in ? \Carbon\Carbon::parse($att->check_in)->format('h:i A') : '-' }}</td>
                        <td>{{ $att?->lunch_start ? \Carbon\Carbon::parse($att->lunch_start)->format('h:i A') : '-' }}</td>
                        <td>{{ $att?->lunch_end ? \Carbon\Carbon::parse($att->lunch_end)->format('h:i A') : '-' }}</td>
                        <td>{{ $att?->check_out ? \Carbon\Carbon::parse($att->check_out)->format('h:i A') : '-' }}</td>
                        <td class="text-center">
                            @if($att && $att->total_work_hours > 0)
                                <span class="work-hours-pill {{ $att->total_work_hours >= 9 ? 'hours-overtime' : ($att->total_work_hours >= 7 ? 'hours-normal' : 'hours-under') }}">
                                    {{ number_format($att->total_work_hours, 1) }}h
                                </span>
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ $att?->remarks ?? '-' }}</td>
                        <td style="background: #f0f9ff;">
                            @if($selfieIn2 || $selfieOut2)
                            <div class="d-flex align-items-center" style="gap:5px;">
                                @if($selfieIn2 && $selfieIn2->selfie_path)
                                <img src="{{ asset('storage/'.$selfieIn2->selfie_path) }}" class="img-circle" width="32" height="32" style="object-fit:cover;" title="Selfie">
                                @endif
                                <div style="font-size:11px;">
                                    @if($selfieIn2)<div><span class="badge badge-success badge-sm">In</span> {{ \Carbon\Carbon::parse($selfieIn2->check_in_time)->format('h:i A') }}</div>@endif
                                    @if($selfieOut2)<div><span class="badge badge-danger badge-sm">Out</span> {{ \Carbon\Carbon::parse($selfieOut2->check_in_time)->format('h:i A') }}</div>@endif
                                </div>
                            </div>
                            @else
                            <span class="text-muted small">—</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const statusColors = {
        'present': '#28a745', 'absent': '#dc3545', 'late': '#ffc107',
        'half_day': '#6c757d', 'leave': '#007bff', 'remote': '#17a2b8',
        'holiday': '#28a745', 'weekend': '#6c757d'
    };
    const noTimeStatuses = ['absent', 'leave', 'holiday', 'weekend'];

    document.querySelectorAll('.status-trigger').forEach(function(sel) {
        updateStatusColor(sel);
        toggleTimeFields(sel);
        sel.addEventListener('change', function() {
            updateStatusColor(this);
            toggleTimeFields(this);
        });
    });

    function updateStatusColor(sel) {
        const color = statusColors[sel.value] || '#6c757d';
        sel.style.borderLeftWidth = '3px';
        sel.style.borderLeftColor = color;
    }

    function toggleTimeFields(sel) {
        const empId = sel.dataset.emp;
        const row = sel.closest('tr');
        const timeInputs = row.querySelectorAll('.time-field');
        const disabled = noTimeStatuses.includes(sel.value);
        timeInputs.forEach(function(input) {
            input.disabled = disabled;
            if (disabled) input.value = '';
        });
    }

    document.querySelectorAll('.time-field').forEach(function(input) {
        input.addEventListener('change', function() {
            const empId = this.dataset.emp;
            calculateHours(empId);
        });
    });

    function calculateHours(empId) {
        const row = document.querySelector('tr[data-employee="' + empId + '"]');
        if (!row) return;
        const checkIn = row.querySelector('input[name="check_in[' + empId + ']"]').value;
        const checkOut = row.querySelector('input[name="check_out[' + empId + ']"]').value;
        const lunchStart = row.querySelector('input[name="lunch_start[' + empId + ']"]').value;
        const lunchEnd = row.querySelector('input[name="lunch_end[' + empId + ']"]').value;
        const display = row.querySelector('.work-hours-display');

        if (!checkIn || !checkOut) {
            display.innerHTML = '<span class="text-muted">-</span>';
            return;
        }

        let totalMin = timeDiff(checkIn, checkOut);
        if (lunchStart && lunchEnd) {
            totalMin -= timeDiff(lunchStart, lunchEnd);
        }
        const hours = Math.max(0, totalMin / 60).toFixed(1);
        let cls = hours >= 9 ? 'hours-overtime' : (hours >= 7 ? 'hours-normal' : 'hours-under');
        display.innerHTML = '<span class="work-hours-pill ' + cls + '">' + hours + 'h</span>';
    }

    function timeDiff(t1, t2) {
        const [h1, m1] = t1.split(':').map(Number);
        const [h2, m2] = t2.split(':').map(Number);
        return (h2 * 60 + m2) - (h1 * 60 + m1);
    }

    document.getElementById('markAllPresent')?.addEventListener('click', function() {
        document.querySelectorAll('.status-trigger').forEach(function(sel) {
            sel.value = 'present';
            updateStatusColor(sel);
            toggleTimeFields(sel);
        });
    });

    document.getElementById('setDefaultTimes')?.addEventListener('click', function() {
        document.querySelectorAll('.status-trigger').forEach(function(sel) {
            if (noTimeStatuses.includes(sel.value)) return;
            const empId = sel.dataset.emp;
            const row = document.querySelector('tr[data-employee="' + empId + '"]');
            if (!row) return;
            const ci = row.querySelector('input[name="check_in[' + empId + ']"]');
            const co = row.querySelector('input[name="check_out[' + empId + ']"]');
            const ls = row.querySelector('input[name="lunch_start[' + empId + ']"]');
            const le = row.querySelector('input[name="lunch_end[' + empId + ']"]');
            if (!ci.value) ci.value = '09:00';
            if (!co.value) co.value = '17:00';
            if (!ls.value) ls.value = '13:00';
            if (!le.value) le.value = '14:00';
            calculateHours(empId);
        });
    });

    document.getElementById('attendanceForm')?.addEventListener('submit', function(e) {
        const btn = this.querySelector('.btn-save-attendance');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Saving...';
    });
});
</script>
@endpush
