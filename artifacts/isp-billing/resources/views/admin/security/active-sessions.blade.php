@extends('layouts.admin')

@section('title', 'Active Sessions')

@section('content')
<div class="container-fluid">

    {{-- Page Tabs --}}
    <ul class="nav nav-tabs mb-3">
        <li class="nav-item">
            <a class="nav-link" href="{{ route('admin.security.login-logs') }}">
                <i class="fas fa-clipboard-list mr-1"></i> Login Log Info
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link active" href="{{ route('admin.security.active-sessions') }}">
                <i class="fas fa-users mr-1"></i> Active Sessions
                @if($totalActive > 0)
                    <span class="badge badge-success ml-1">{{ $totalActive }}</span>
                @endif
            </a>
        </li>
    </ul>

    <div class="card shadow-sm">
        <div class="card-header py-2 d-flex align-items-center justify-content-between" style="background:#1a3c5e;">
            <h3 class="card-title text-white mb-0">
                <i class="fas fa-circle text-success mr-2" style="font-size:10px;"></i>
                Currently Active Users
            </h3>
            <div class="d-flex align-items-center">
                <span class="badge badge-light text-dark mr-2">
                    <i class="fas fa-users mr-1"></i> {{ $totalActive }} Online
                </span>
                <button onclick="location.reload()" class="btn btn-sm btn-outline-light">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
            </div>
        </div>

        <div class="card-body p-0">
            @if($sessions->isEmpty())
                <div class="text-center py-5">
                    <i class="fas fa-user-slash fa-3x text-muted mb-3"></i>
                    <p class="text-muted mb-0">No active sessions right now.</p>
                    <small class="text-muted">Users will appear here once they log in.</small>
                </div>
            @else
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover mb-0" style="font-size:13px;">
                    <thead style="background:#1a3c5e;color:#fff;">
                        <tr>
                            <th>#</th>
                            <th>User</th>
                            <th>Type</th>
                            <th>Device</th>
                            <th>IP Address</th>
                            <th>Logged In At</th>
                            <th>Last Activity</th>
                            <th>Duration</th>
                            <th style="width:100px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($sessions as $i => $session)
                        @php
                            $user      = $session->user;
                            $typeColor = ['admin'=>'danger','super_admin'=>'dark','employee'=>'primary','client'=>'success','reseller'=>'warning','technician'=>'info'];
                            $tc        = $typeColor[$user->user_type ?? ''] ?? 'secondary';
                            $duration  = $session->login_at ? $session->login_at->diffForHumans(null, true) : '-';
                            $lastAct   = $session->last_activity_at ? $session->last_activity_at->diffForHumans() : '-';
                            $isCurrentUser = auth()->id() == ($user->id ?? 0);
                        @endphp
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <span class="mr-2" style="width:8px;height:8px;border-radius:50%;background:#28a745;display:inline-block;"></span>
                                    <div>
                                        <strong>{{ $user->name ?? 'Unknown' }}</strong>
                                        <br><small class="text-muted">{{ $user->email ?? '-' }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge badge-{{ $tc }}">{{ ucfirst(str_replace('_', ' ', $user->user_type ?? '-')) }}</span>
                            </td>
                            <td>
                                @if($session->device_type === 'mobile')
                                    <i class="fas fa-mobile-alt text-info mr-1"></i> Mobile
                                @elseif($session->device_type === 'tablet')
                                    <i class="fas fa-tablet-alt text-warning mr-1"></i> Tablet
                                @else
                                    <i class="fas fa-desktop text-secondary mr-1"></i> Desktop
                                @endif
                            </td>
                            <td><code style="font-size:11px;">{{ $session->ip_address }}</code></td>
                            <td style="font-size:12px;">{{ $session->login_at ? $session->login_at->format('d-m-Y H:i:s') : '-' }}</td>
                            <td style="font-size:12px;">{{ $lastAct }}</td>
                            <td><span class="badge badge-light text-dark">{{ $duration }}</span></td>
                            <td>
                                @if($isCurrentUser)
                                    <span class="badge badge-success"><i class="fas fa-check mr-1"></i>You</span>
                                @else
                                    <form method="POST" action="{{ route('admin.security.active-sessions.force-logout', $session->id) }}" onsubmit="return confirm('Force logout this user?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-xs">
                                            <i class="fas fa-sign-out-alt mr-1"></i>Force Logout
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>

        <div class="card-footer text-muted" style="font-size:12px;">
            <i class="fas fa-info-circle mr-1"></i>
            Session timeout: {{ config('session.lifetime', 30) }} minutes. Page auto-refreshes every 30 seconds.
        </div>
    </div>
</div>

<script>
    setTimeout(function(){ location.reload(); }, 30000);
</script>
@endsection
