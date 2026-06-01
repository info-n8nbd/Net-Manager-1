@extends('layouts.admin')

@section('title', 'Login Log Info')

@section('content')
<div class="container-fluid">

    {{-- Page Tabs --}}
    <ul class="nav nav-tabs mb-3">
        <li class="nav-item">
            <a class="nav-link active" href="{{ route('admin.security.login-logs') }}">
                <i class="fas fa-clipboard-list mr-1"></i> Login Log Info
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="{{ route('admin.security.active-sessions') }}">
                <i class="fas fa-circle text-success mr-1" style="font-size:10px;"></i> Active Sessions
            </a>
        </li>
    </ul>

    <div class="card shadow-sm">
        <div class="card-header py-2" style="background:#1a3c5e;">
            <h3 class="card-title text-white mb-0"><i class="fas fa-clipboard-list mr-2"></i>User Login History</h3>
            <div class="card-tools d-flex align-items-center">
                <span class="badge badge-light text-dark mr-3">Total: {{ $logs->total() }}</span>
                @php $locationEnabled = \App\Models\SystemSetting::get('location_capture_enabled', '1'); @endphp
                @if(auth()->user()->user_type === 'super_admin')
                <form method="POST" action="{{ route('admin.security.toggle-location-capture') }}" class="mr-2 mb-0">
                    @csrf
                    <button type="submit" class="btn btn-sm {{ $locationEnabled !== '0' ? 'btn-success' : 'btn-secondary' }}"
                        title="{{ $locationEnabled !== '0' ? 'Location Capture: ON (click to disable)' : 'Location Capture: OFF (click to enable)' }}">
                        <i class="fas fa-map-marker-alt mr-1"></i>
                        Location: {{ $locationEnabled !== '0' ? 'ON' : 'OFF' }}
                    </button>
                </form>
                <form method="POST" action="{{ route('admin.security.login-logs.clear') }}" class="mb-0"
                    onsubmit="return confirm('Are you sure you want to clear all login history? This cannot be undone.')">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-danger">
                        <i class="fas fa-trash mr-1"></i>Clear History
                    </button>
                </form>
                @endif
            </div>
        </div>

        {{-- Filter Bar --}}
        <div class="card-body pb-0 pt-3" style="background:#f4f6f9;">
            <form method="GET" class="row g-2 mb-3">
                <div class="col-md-2">
                    <select name="status" class="form-control form-control-sm">
                        <option value="">All Status</option>
                        <option value="success" {{ request('status')=='success'?'selected':'' }}>Success</option>
                        <option value="failed"  {{ request('status')=='failed' ?'selected':'' }}>Failed</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="user_type" class="form-control form-control-sm">
                        <option value="">All User Type</option>
                        <option value="admin"      {{ request('user_type')=='admin'     ?'selected':'' }}>Admin</option>
                        <option value="employee"   {{ request('user_type')=='employee'  ?'selected':'' }}>Employee</option>
                        <option value="client"     {{ request('user_type')=='client'    ?'selected':'' }}>Client</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="text" name="email" class="form-control form-control-sm" placeholder="Search by email/user" value="{{ request('email') }}">
                </div>
                <div class="col-md-2">
                    <input type="text" name="ip_address" class="form-control form-control-sm" placeholder="IP Address" value="{{ request('ip_address') }}">
                </div>
                <div class="col-md-1">
                    <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
                </div>
                <div class="col-md-1">
                    <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-sm mr-1"><i class="fas fa-search"></i> Filter</button>
                    <a href="{{ route('admin.security.login-logs') }}" class="btn btn-secondary btn-sm"><i class="fas fa-undo"></i></a>
                </div>
            </form>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover mb-0" style="font-size:13px;">
                    <thead style="background:#1a3c5e;color:#fff;">
                        <tr>
                            <th style="width:60px;">ID</th>
                            <th>User Id</th>
                            <th>User Name</th>
                            <th style="width:90px;">User Type</th>
                            <th style="width:80px;">Device</th>
                            <th style="width:140px;">Date</th>
                            <th style="width:120px;">IP</th>
                            <th>Browser</th>
                            <th style="width:100px;">Location</th>
                            <th style="width:70px;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                        @php
                            $ua = $log->user_agent ?? '';
                            // Device detection
                            $isMobile = preg_match('/Mobile|Android|iPhone|iPad|iPod/i', $ua);
                            $device   = $isMobile ? 'Mobile' : 'Desktop';
                            // Browser parse
                            $browser  = $ua;
                            if (preg_match('/Firefox\/([\d.]+)/i', $ua, $m))        $browser = 'Firefox/' . $m[1];
                            elseif (preg_match('/Chrome\/([\d.]+)/i', $ua, $m))     $browser = 'Chrome/' . $m[1];
                            elseif (preg_match('/Safari\/([\d.]+)/i', $ua, $m) && !preg_match('/Chrome/i', $ua)) $browser = 'Safari/' . $m[1];
                            elseif (preg_match('/Edg\/([\d.]+)/i', $ua, $m))        $browser = 'Edge/' . $m[1];
                            elseif (preg_match('/OPR\/([\d.]+)/i', $ua, $m))        $browser = 'Opera/' . $m[1];
                            // OS parse
                            $os = 'Unknown OS';
                            if (preg_match('/Windows NT 10/i', $ua))       $os = 'Windows 10';
                            elseif (preg_match('/Windows NT 11/i', $ua))   $os = 'Windows 11';
                            elseif (preg_match('/Android ([\d.]+)/i', $ua, $m)) $os = 'Android ' . $m[1];
                            elseif (preg_match('/iPhone OS ([\d_]+)/i', $ua, $m)) $os = 'iOS ' . str_replace('_','.',$m[1]);
                            elseif (preg_match('/Linux/i', $ua))           $os = 'Linux';
                            elseif (preg_match('/Mac OS X/i', $ua))        $os = 'macOS';
                            // User info
                            $userName  = $log->user->name      ?? 'N/A';
                            $userEmail = $log->user->email     ?? ($log->email ?? 'N/A');
                            $userType  = $log->user->user_type ?? 'N/A';
                        @endphp
                        <tr>
                            <td><strong>{{ $log->id }}</strong></td>
                            <td style="font-size:12px;">{{ $userEmail }}</td>
                            <td>{{ $userName }}</td>
                            <td>
                                @php $tc = ['admin'=>'danger','employee'=>'primary','client'=>'success','reseller'=>'warning']; @endphp
                                <span class="badge badge-{{ $tc[$userType] ?? 'secondary' }}">{{ ucfirst($userType) }}</span>
                            </td>
                            <td>
                                @if($isMobile)
                                    <span class="text-info"><i class="fas fa-mobile-alt mr-1"></i>Mobile</span>
                                @else
                                    <span class="text-secondary"><i class="fas fa-desktop mr-1"></i>Desktop</span>
                                @endif
                            </td>
                            <td style="font-size:12px;">
                                {{ $log->created_at ? $log->created_at->format('d-m-Y H:i:s') : '-' }}
                            </td>
                            <td><code style="font-size:11px;">{{ $log->ip_address }}</code></td>
                            <td style="font-size:11px;">
                                <span title="{{ $ua }}">{{ $browser }} &mdash; {{ $os }}</span>
                            </td>
                            <td style="font-size:11px;">
                                @if($log->latitude && $log->longitude)
                                    <a href="https://www.google.com/maps?q={{ $log->latitude }},{{ $log->longitude }}" target="_blank" title="{{ $log->location_address }}" class="text-primary">
                                        <i class="fas fa-map-marker-alt"></i>
                                        {{ $log->location_address ? Str::limit($log->location_address, 25) : $log->latitude.','.$log->longitude }}
                                    </a>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if($log->status === 'success')
                                    <span class="badge badge-success"><i class="fas fa-check"></i> OK</span>
                                @else
                                    <span class="badge badge-danger"><i class="fas fa-times"></i> Fail</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="10" class="text-center text-muted py-4">No login records found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($logs->hasPages())
        <div class="card-footer clearfix py-2">
            {{ $logs->appends(request()->query())->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
