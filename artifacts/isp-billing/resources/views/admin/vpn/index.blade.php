@extends('layouts.admin')

@section('title', 'VPN Accounts')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
<li class="breadcrumb-item">Network</li>
<li class="breadcrumb-item active">VPN Accounts</li>
@endsection

@section('content')
<div class="container-fluid">

    <div class="row">
        <div class="col-md-3 col-sm-6">
            <div class="info-box">
                <span class="info-box-icon bg-info"><i class="fas fa-shield-alt"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Total VPN Accounts</span>
                    <span class="info-box-number">{{ $accounts->total() }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="info-box">
                <span class="info-box-icon bg-success"><i class="fas fa-server"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Main Routers</span>
                    <span class="info-box-number">{{ $mainCount }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="info-box">
                <span class="info-box-icon bg-warning"><i class="fas fa-code-branch"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Branch Routers</span>
                    <span class="info-box-number">{{ $branchCount }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="info-box">
                <span class="info-box-icon bg-primary"><i class="fas fa-check-circle"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Active</span>
                    <span class="info-box-number">{{ $activeCount }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-shield-alt mr-2"></i>VPN Management</h3>
            <div class="card-tools">
                <a href="{{ route('admin.vpn.settings') }}" class="btn btn-secondary btn-sm mr-1">
                    <i class="fas fa-cog mr-1"></i>VPN Server Settings
                </a>
                <a href="{{ route('admin.vpn.create') }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus mr-1"></i>Add VPN Account
                </a>
            </div>
        </div>
        <div class="card-header border-bottom-0 pb-0">
            <form method="GET" action="{{ route('admin.vpn.index') }}" class="form-inline">
                <div class="form-group mr-2">
                    <select name="account_type" class="form-control form-control-sm">
                        <option value="">All Types</option>
                        <option value="main_router" {{ request('account_type') == 'main_router' ? 'selected' : '' }}>Main Router</option>
                        <option value="branch_router" {{ request('account_type') == 'branch_router' ? 'selected' : '' }}>Branch Router</option>
                        <option value="client" {{ request('account_type') == 'client' ? 'selected' : '' }}>Client VPN</option>
                    </select>
                </div>
                <div class="form-group mr-2">
                    <select name="vpn_type" class="form-control form-control-sm">
                        <option value="">All VPN Types</option>
                        @foreach($vpnTypes as $key => $label)
                        <option value="{{ $key }}" {{ request('vpn_type') == $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group mr-2">
                    <select name="status" class="form-control form-control-sm">
                        <option value="">All Status</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
                <div class="form-group mr-2">
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Search..." value="{{ request('search') }}">
                </div>
                <button type="submit" class="btn btn-sm btn-info"><i class="fas fa-search mr-1"></i>Filter</button>
                @if(request()->hasAny(['account_type', 'vpn_type', 'status', 'search']))
                <a href="{{ route('admin.vpn.index') }}" class="btn btn-sm btn-secondary ml-1"><i class="fas fa-times mr-1"></i>Clear</a>
                @endif
            </form>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0">
                    <thead class="bg-dark text-white">
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Type</th>
                            <th>VPN</th>
                            <th>Username</th>
                            <th>Server / IP</th>
                            <th>Linked Routers</th>
                            <th>Status</th>
                            <th width="140">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($accounts as $account)
                        <tr>
                            <td>{{ $loop->iteration + ($accounts->currentPage() - 1) * $accounts->perPage() }}</td>
                            <td>
                                <strong>{{ $account->name ?? $account->username }}</strong>
                                @if($account->client)
                                <br><small class="text-muted">Client: {{ $account->client->name }}</small>
                                @endif
                            </td>
                            <td>
                                @if($account->account_type === 'main_router')
                                    <span class="badge badge-success"><i class="fas fa-server mr-1"></i>Main Router</span>
                                @elseif($account->account_type === 'branch_router')
                                    <span class="badge badge-warning"><i class="fas fa-code-branch mr-1"></i>Branch Router</span>
                                @else
                                    <span class="badge badge-secondary">Client VPN</span>
                                @endif
                            </td>
                            <td>
                                @if($account->vpn_type === 'sstp')
                                    <span class="badge badge-success">SSTP</span>
                                @elseif($account->vpn_type === 'l2tp')
                                    <span class="badge badge-primary">L2TP</span>
                                @elseif($account->vpn_type === 'pptp')
                                    <span class="badge badge-info">PPTP</span>
                                @elseif($account->vpn_type === 'ovpn')
                                    <span class="badge badge-warning">OpenVPN</span>
                                @else
                                    <span class="badge badge-secondary">{{ strtoupper($account->vpn_type) }}</span>
                                @endif
                            </td>
                            <td><code>{{ $account->username }}</code></td>
                            <td>
                                @if($account->server_address)
                                    <small class="text-muted">Server:</small> {{ $account->server_address }}<br>
                                @endif
                                @if($account->remote_address)
                                    <small class="text-muted">VPN IP:</small> <code>{{ $account->remote_address }}</code>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @php $routerCount = $account->linkedRouters->count(); @endphp
                                @if($routerCount > 0)
                                    <span class="badge badge-info">{{ $routerCount }} router(s)</span>
                                    <br>
                                    @foreach($account->linkedRouters->take(3) as $router)
                                        <small>{{ $router->name }}</small>@if(!$loop->last), @endif
                                    @endforeach
                                    @if($routerCount > 3)
                                        <small class="text-muted">+{{ $routerCount - 3 }} more</small>
                                    @endif
                                @else
                                    <span class="text-muted">None</span>
                                @endif
                            </td>
                            <td>
                                @if($account->is_active)
                                    <span class="badge badge-success">Active</span>
                                @else
                                    <span class="badge badge-danger">Inactive</span>
                                @endif
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="{{ route('admin.vpn.show', $account) }}" class="btn btn-sm btn-success" title="View MikroTik Commands">
                                        <i class="fas fa-terminal"></i>
                                    </a>
                                    <form action="{{ route('admin.vpn.toggle', $account) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-{{ $account->is_active ? 'secondary' : 'success' }}" title="{{ $account->is_active ? 'Disable' : 'Enable' }}">
                                            <i class="fas fa-{{ $account->is_active ? 'toggle-off' : 'toggle-on' }}"></i>
                                        </button>
                                    </form>
                                    <a href="{{ route('admin.vpn.edit', $account) }}" class="btn btn-sm btn-info" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="{{ route('admin.vpn.destroy', $account) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this VPN account?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" title="Delete"><i class="fas fa-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center py-4">
                                <i class="fas fa-shield-alt fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No VPN accounts found. Create one to manage your router tunnels.</p>
                                <a href="{{ route('admin.vpn.create') }}" class="btn btn-primary btn-sm"><i class="fas fa-plus mr-1"></i>Add VPN Account</a>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($accounts->hasPages())
        <div class="card-footer">
            {{ $accounts->appends(request()->query())->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
