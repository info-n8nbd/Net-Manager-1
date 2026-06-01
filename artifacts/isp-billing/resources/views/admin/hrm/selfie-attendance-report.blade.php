@extends('layouts.admin')

@section('title', 'Selfie Attendance Report')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.hrm.selfie-attendance') }}">Selfie Attendance</a></li>
<li class="breadcrumb-item active">Monthly Report</li>
@endsection

@section('content')
<div class="container-fluid">

    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-calendar-check mr-2"></i>Monthly Selfie Attendance Report</h3>
            <div class="card-tools d-flex flex-wrap align-items-center">
                <form class="form-inline mr-2" method="GET">
                    <select name="month" class="form-control form-control-sm mr-1">
                        @for($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>{{ date('F', mktime(0,0,0,$m)) }}</option>
                        @endfor
                    </select>
                    <select name="year" class="form-control form-control-sm mr-1">
                        @for($y = date('Y'); $y >= date('Y') - 3; $y--)
                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                    @if(auth()->user()->hasAnyRole(['Super Admin', 'superadmin']))
                    <select name="user_id" class="form-control form-control-sm mr-1">
                        <option value="">All Employees</option>
                        @foreach($employees as $emp)
                        @if($emp->user)
                        <option value="{{ $emp->user->id }}" {{ request('user_id') == $emp->user->id ? 'selected' : '' }}>{{ $emp->name }}</option>
                        @endif
                        @endforeach
                    </select>
                    @endif
                    <button type="submit" class="btn btn-sm btn-primary mr-1"><i class="fas fa-search mr-1"></i>Filter</button>
                    <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" class="btn btn-sm btn-success"><i class="fas fa-file-csv mr-1"></i>CSV</a>
                </form>
            </div>
        </div>
    </div>

    @if(!empty($employeeSummary))
    <div class="row mb-3">
        @foreach($employeeSummary as $uid => $sum)
        <div class="col-md-4 col-lg-3 mb-3">
            <div class="card card-outline card-primary mb-0 h-100">
                <div class="card-body text-center py-3">
                    <div class="mb-2"><i class="fas fa-user-circle fa-2x text-primary"></i></div>
                    <h6 class="mb-2 font-weight-bold">{{ $sum['name'] }}</h6>
                    <div class="row text-center mt-2">
                        <div class="col-6">
                            <div class="border-right">
                                <h4 class="text-success mb-0">{{ $sum['days_present'] }}</h4>
                                <small class="text-muted">Present</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <h4 class="text-warning mb-0">{{ $sum['late_count'] }}</h4>
                            <small class="text-muted">Late</small>
                        </div>
                    </div>
                    <div class="row text-center mt-2">
                        <div class="col-6">
                            <div class="border-right">
                                <h6 class="text-danger mb-0">{{ $sum['early_leaves'] }}</h6>
                                <small class="text-muted">Early Leave</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <h6 class="text-info mb-0">{{ $sum['total_hours'] }}</h6>
                            <small class="text-muted">Total Hours</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    @foreach($grouped as $dateKey => $dayAttendances)
    @php
        $dayByUser = $dayAttendances->groupBy('user_id');
    @endphp
    <div class="card mb-3">
        <div class="card-header py-2">
            <h5 class="card-title mb-0">
                <i class="fas fa-calendar-day mr-2 text-primary"></i>
                {{ \Carbon\Carbon::parse($dateKey)->format('l, d F Y') }}
            </h5>
            <div class="float-right">
                <span class="badge badge-success mr-1">{{ $dayAttendances->where('type','check_in')->count() }} Check-in</span>
                <span class="badge badge-danger mr-1">{{ $dayAttendances->where('type','check_out')->count() }} Check-out</span>
                <a href="{{ route('admin.hrm.selfie-attendance.map', ['date' => $dateKey]) }}" class="badge badge-info" target="_blank"><i class="fas fa-map-marked-alt mr-1"></i>Map</a>
            </div>
        </div>
        <div class="card-body p-0">
            <table class="table table-sm table-striped mb-0">
                <thead class="bg-light">
                    <tr>
                        <th width="55">Photo</th>
                        <th>Employee</th>
                        <th>Check In</th>
                        <th>Check Out</th>
                        <th>Working Hours</th>
                        <th>Status</th>
                        <th>Location</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($dayByUser as $uid => $urecs)
                    @php
                        $ci = $urecs->firstWhere('type', 'check_in');
                        $co = $urecs->firstWhere('type', 'check_out');
                        $wh = $workingHours[$dateKey][$uid] ?? null;
                        $isLate = $ci && $ci->isLateCheckIn();
                        $isEarly = $co && $co->isEarlyLeave();
                        $locValid = $ci ? $ci->isValidLocation() : null;
                    @endphp
                    <tr>
                        <td>
                            @if($ci && $ci->selfie_path)
                            <img src="{{ asset('storage/'.$ci->selfie_path) }}" class="img-circle" width="40" height="40" style="object-fit:cover;">
                            @elseif($co && $co->selfie_path)
                            <img src="{{ asset('storage/'.$co->selfie_path) }}" class="img-circle" width="40" height="40" style="object-fit:cover;">
                            @else
                            <div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center" style="width:40px;height:40px;"><i class="fas fa-user text-white"></i></div>
                            @endif
                        </td>
                        <td>
                            <strong>{{ $ci ? ($ci->user->name ?? 'N/A') : ($co->user->name ?? 'N/A') }}</strong>
                            @if($isLate)<br><span class="badge badge-warning badge-sm">Late</span>@endif
                            @if($isEarly)<span class="badge badge-danger badge-sm ml-1">Early Leave</span>@endif
                        </td>
                        <td>
                            @if($ci)
                            <span class="text-success"><i class="fas fa-sign-in-alt mr-1"></i>{{ \Carbon\Carbon::parse($ci->check_in_time)->format('h:i A') }}</span>
                            @else
                            <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            @if($co)
                            <span class="text-danger"><i class="fas fa-sign-out-alt mr-1"></i>{{ \Carbon\Carbon::parse($co->check_in_time)->format('h:i A') }}</span>
                            @else
                            <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            @if($wh)
                            <span class="badge badge-info"><i class="fas fa-hourglass-half mr-1"></i>{{ $wh }}</span>
                            @else
                            <span class="text-muted small">{{ $ci && !$co ? 'No checkout' : '—' }}</span>
                            @endif
                        </td>
                        <td>
                            @if(!$ci)
                            <span class="badge badge-secondary"><i class="fas fa-times mr-1"></i>Absent</span>
                            @elseif($isLate && $isEarly)
                            <span class="badge badge-warning">Late</span> <span class="badge badge-danger">Early Leave</span>
                            @elseif($isLate)
                            <span class="badge badge-warning"><i class="fas fa-clock mr-1"></i>Late</span>
                            @elseif($isEarly)
                            <span class="badge badge-danger"><i class="fas fa-running mr-1"></i>Early Leave</span>
                            @else
                            <span class="badge badge-success"><i class="fas fa-check mr-1"></i>Present</span>
                            @endif
                        </td>
                        <td>
                            @if($ci && $ci->latitude)
                            <a href="{{ $ci->google_maps_url }}" target="_blank" class="text-primary small"><i class="fas fa-map-marker-alt"></i> {{ Str::limit($ci->location_address ?? $ci->latitude.','.$ci->longitude, 25) }}</a>
                            @else
                            <span class="text-muted small">N/A</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endforeach

    @if($grouped->isEmpty())
    <div class="card">
        <div class="card-body text-center py-5 text-muted">
            <i class="fas fa-calendar-times fa-3x mb-3"></i>
            <p>No selfie attendance records found for this period.</p>
        </div>
    </div>
    @endif
</div>
@endsection
