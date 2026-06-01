@extends('layouts.admin')

@section('title', 'Security Dashboard')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-3">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ $stats['total_users'] ?? 0 }}</h3>
                    <p>Total Users</p>
                </div>
                <div class="icon"><i class="fas fa-users"></i></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ $stats['2fa_enabled'] ?? 0 }}</h3>
                    <p>2FA Enabled</p>
                </div>
                <div class="icon"><i class="fas fa-shield-alt"></i></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ $stats['failed_logins_today'] ?? 0 }}</h3>
                    <p>Failed Logins Today</p>
                </div>
                <div class="icon"><i class="fas fa-exclamation-triangle"></i></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>{{ $stats['blocked_ips'] ?? 0 }}</h3>
                    <p>Blocked IPs</p>
                </div>
                <div class="icon"><i class="fas fa-ban"></i></div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-shield-alt mr-2"></i>Two-Factor Authentication</h3>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h5 class="mb-1">2FA Adoption Rate</h5>
                            <p class="text-muted mb-0">{{ $stats['2fa_enabled'] ?? 0 }} of {{ $stats['total_users'] ?? 0 }} users</p>
                        </div>
                        <div class="text-right">
                            <h3 class="text-success mb-0">
                                {{ ($stats['total_users'] ?? 0) > 0 ? round(($stats['2fa_enabled'] ?? 0) / ($stats['total_users'] ?? 1) * 100) : 0 }}%
                            </h3>
                        </div>
                    </div>
                    <div class="progress" style="height: 10px;">
                        <div class="progress-bar bg-success" style="width: {{ ($stats['total_users'] ?? 0) > 0 ? round(($stats['2fa_enabled'] ?? 0) / ($stats['total_users'] ?? 1) * 100) : 0 }}%"></div>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="{{ route('admin.2fa.setup') }}" class="btn btn-sm btn-primary">
                        <i class="fas fa-cog mr-1"></i>Manage 2FA
                    </a>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-link mr-2"></i>Quick Links</h3>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <a href="{{ route('admin.security.password-policy') }}" class="list-group-item list-group-item-action">
                            <i class="fas fa-key mr-2 text-primary"></i>Password Policy
                            <i class="fas fa-chevron-right float-right mt-1 text-muted"></i>
                        </a>
                        <a href="{{ route('admin.security.login-logs') }}" class="list-group-item list-group-item-action">
                            <i class="fas fa-clipboard-list mr-2 text-info"></i>Login Logs
                            <i class="fas fa-chevron-right float-right mt-1 text-muted"></i>
                        </a>
                        <a href="{{ route('admin.security.ip-restrictions') }}" class="list-group-item list-group-item-action">
                            <i class="fas fa-globe mr-2 text-warning"></i>IP Restrictions
                            <i class="fas fa-chevron-right float-right mt-1 text-muted"></i>
                        </a>
                        <a href="{{ route('admin.security.api-tokens') }}" class="list-group-item list-group-item-action">
                            <i class="fas fa-code mr-2 text-success"></i>API Tokens
                            <i class="fas fa-chevron-right float-right mt-1 text-muted"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-sign-in-alt mr-2"></i>Recent Login Attempts</h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.security.login-logs') }}" class="btn btn-sm btn-info">View All</a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>IP</th>
                                <th>Status</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($stats['recent_logins'] ?? [] as $login)
                            <tr>
                                <td>{{ $login->user->name ?? $login->email ?? 'Unknown' }}</td>
                                <td><code>{{ $login->ip_address }}</code></td>
                                <td>
                                    @if($login->status === 'success')
                                        <span class="badge badge-success">Success</span>
                                    @else
                                        <span class="badge badge-danger">Failed</span>
                                    @endif
                                </td>
                                <td>{{ $login->created_at->diffForHumans() }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="text-center">No recent login attempts</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
