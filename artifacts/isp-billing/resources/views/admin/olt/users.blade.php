@extends('layouts.admin')
@section('title', 'OLT Users')
@section('breadcrumb')<li class="breadcrumb-item active">OLT Users</li>@endsection

@section('content')
<div class="container-fluid py-2">

    {{-- Stat Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-md-4 col-6">
            <div class="card text-center border-0 shadow-sm">
                <div class="card-body py-3">
                    <div class="fs-3 fw-bold text-primary">{{ $stats['total'] }}</div>
                    <div class="small text-muted">Total OLT Users</div>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-6">
            <div class="card text-center border-0 shadow-sm">
                <div class="card-body py-3">
                    <div class="fs-3 fw-bold text-success">{{ $stats['online'] }}</div>
                    <div class="small text-muted">Online</div>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-6">
            <div class="card text-center border-0 shadow-sm">
                <div class="card-body py-3">
                    <div class="fs-3 fw-bold text-danger">{{ $stats['offline'] }}</div>
                    <div class="small text-muted">Offline</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter + Table --}}
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small mb-1">Search</label>
                    <input type="text" name="search" class="form-control form-control-sm"
                        value="{{ request('search') }}" placeholder="Client name, ID, ONU serial...">
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">ONU Status</label>
                    <select name="onu_status" class="form-select form-select-sm">
                        <option value="">All</option>
                        <option value="online"  @selected(request('onu_status')=='online')>Online</option>
                        <option value="offline" @selected(request('onu_status')=='offline')>Offline</option>
                        <option value="unknown" @selected(request('onu_status')=='unknown')>Unknown</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1">OLT Device</label>
                    <select name="olt_device_id" class="form-select form-select-sm">
                        <option value="">All Devices</option>
                        @foreach($oltDevices as $olt)
                            <option value="{{ $olt->id }}" @selected(request('olt_device_id')==$olt->id)>{{ $olt->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search me-1"></i>Filter</button>
                    <a href="{{ route('admin.olt.users') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
                </div>
            </form>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">#</th>
                            <th>Client</th>
                            <th>ONU Serial</th>
                            <th>OLT Device</th>
                            <th>Port</th>
                            <th>RX Power</th>
                            <th>ONU Status</th>
                            <th>Client Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($users as $user)
                        <tr>
                            <td class="ps-3 text-muted small">{{ $loop->iteration }}</td>
                            <td>
                                <a href="{{ route('admin.clients.show', $user->id) }}" class="text-decoration-none fw-semibold font-monospace">
                                    {{ $user->client_username ?? $user->client_code ?? '—' }}
                                </a>
                                <div class="small text-muted">{{ $user->client_name }}</div>
                            </td>
                            <td>
                                <span class="font-monospace small">{{ $user->onu_serial ?? '—' }}</span>
                                @if($user->onu_mac)
                                    <div class="small text-muted">{{ $user->onu_mac }}</div>
                                @endif
                            </td>
                            <td>
                                @if($user->olt_name)
                                    <span class="fw-semibold">{{ $user->olt_name }}</span>
                                    <div class="small text-muted">{{ $user->olt_ip }}</div>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="small">{{ $user->olt_port ?? '—' }}</td>
                            <td class="small">
                                @if($user->onu_rx_power !== null)
                                    @php
                                        $rx = (float)$user->onu_rx_power;
                                        $rxClass = $rx >= -15 ? 'success' : ($rx >= -20 ? 'warning' : 'danger');
                                    @endphp
                                    <span class="text-{{ $rxClass }}">{{ $rx }} dBm</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if($user->onu_status === 'online')
                                    <span class="badge bg-success"><i class="fas fa-circle me-1" style="font-size:8px"></i>Online</span>
                                @elseif($user->onu_status === 'offline')
                                    <span class="badge bg-danger"><i class="fas fa-circle me-1" style="font-size:8px"></i>Offline</span>
                                @else
                                    <span class="badge bg-secondary">Unknown</span>
                                @endif
                            </td>
                            <td>
                                @if($user->client_status === 'active')
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">Active</span>
                                @elseif($user->client_status === 'suspended')
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle">Suspended</span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">{{ ucfirst($user->client_status) }}</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('admin.clients.show', $user->id) }}" class="btn btn-sm btn-outline-primary py-0 px-2">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                <i class="fas fa-network-wired fa-2x mb-2 d-block opacity-30"></i>
                                No OLT users found
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($users->hasPages())
        <div class="card-footer bg-white">{{ $users->appends(request()->query())->links() }}</div>
        @endif
    </div>
</div>
@endsection

