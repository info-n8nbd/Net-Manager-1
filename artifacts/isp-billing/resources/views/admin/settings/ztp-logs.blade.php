@extends('layouts.admin')
@section('title', 'ZTP Activity Log')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.settings.ztp') }}">ZTP Settings</a></li>
<li class="breadcrumb-item active">Activity Log</li>
@endsection

@section('content')
<div class="container-fluid py-4">

    {{-- Header --}}
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h4 class="mb-0" style="font-weight:700;"><i class="fas fa-magic mr-2" style="color:#6610f2;"></i>ZTP Activity Log</h4>
            <small class="text-muted">Zero-Touch Provisioning history for all clients</small>
        </div>
        <a href="{{ route('admin.settings.ztp') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-cog mr-1"></i> ZTP Settings
        </a>
    </div>

    {{-- Summary Cards --}}
    @php
        $allLogs    = \App\Models\Client::whereNotNull('provision_status')->whereNotNull('provision_attempted_at');
        $totalSuccess = (clone $allLogs)->where('provision_status','success')->count();
        $totalPartial = (clone $allLogs)->where('provision_status','partial')->count();
        $totalFailed  = (clone $allLogs)->where('provision_status','failed')->count();
        $totalPending = (clone $allLogs)->whereIn('provision_status',['pending','provisioning'])->count();
    @endphp
    <div class="row mb-4">
        <div class="col-6 col-md-3 mb-2">
            <div class="card border-0 shadow-sm text-center py-3">
                <div style="font-size:28px;font-weight:800;color:#28a745;">{{ $totalSuccess }}</div>
                <div class="text-muted small">Provisioned</div>
            </div>
        </div>
        <div class="col-6 col-md-3 mb-2">
            <div class="card border-0 shadow-sm text-center py-3">
                <div style="font-size:28px;font-weight:800;color:#fd7e14;">{{ $totalPartial }}</div>
                <div class="text-muted small">Partial</div>
            </div>
        </div>
        <div class="col-6 col-md-3 mb-2">
            <div class="card border-0 shadow-sm text-center py-3">
                <div style="font-size:28px;font-weight:800;color:#dc3545;">{{ $totalFailed }}</div>
                <div class="text-muted small">Failed</div>
            </div>
        </div>
        <div class="col-6 col-md-3 mb-2">
            <div class="card border-0 shadow-sm text-center py-3">
                <div style="font-size:28px;font-weight:800;color:#6c757d;">{{ $totalPending }}</div>
                <div class="text-muted small">Pending / Running</div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <form method="GET" class="card shadow-sm mb-3">
        <div class="card-body py-2">
            <div class="row align-items-center">
                <div class="col-md-5 mb-2 mb-md-0">
                    <input type="text" name="search" class="form-control form-control-sm"
                        placeholder="Search by name or client ID..."
                        value="{{ request('search') }}">
                </div>
                <div class="col-md-3 mb-2 mb-md-0">
                    <select name="status" class="form-control form-control-sm">
                        <option value="">All Statuses</option>
                        <option value="success"      {{ request('status')==='success'      ? 'selected' : '' }}>Provisioned</option>
                        <option value="partial"      {{ request('status')==='partial'      ? 'selected' : '' }}>Partial</option>
                        <option value="failed"       {{ request('status')==='failed'       ? 'selected' : '' }}>Failed</option>
                        <option value="pending"      {{ request('status')==='pending'      ? 'selected' : '' }}>Pending</option>
                        <option value="provisioning" {{ request('status')==='provisioning' ? 'selected' : '' }}>In Progress</option>
                    </select>
                </div>
                <div class="col-md-4 d-flex" style="gap:6px;">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search mr-1"></i>Filter</button>
                    <a href="{{ route('admin.settings.ztp.logs') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
                </div>
            </div>
        </div>
    </form>

    {{-- Table --}}
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" style="font-size:13px;">
                    <thead style="background:#f4f6fb;">
                        <tr>
                            <th style="padding:10px 14px;">Client</th>
                            <th>Status</th>
                            <th>Last Attempted</th>
                            <th>MikroTik / ONU Result</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $client)
                        @php
                            $badge = match($client->provision_status) {
                                'success'      => ['bg'=>'#28a745','label'=>'Provisioned','icon'=>'fa-check-circle'],
                                'partial'      => ['bg'=>'#fd7e14','label'=>'Partial',    'icon'=>'fa-exclamation-circle'],
                                'provisioning' => ['bg'=>'#007bff','label'=>'In Progress','icon'=>'fa-spinner fa-spin'],
                                'pending'      => ['bg'=>'#6c757d','label'=>'Pending',    'icon'=>'fa-clock'],
                                'failed'       => ['bg'=>'#dc3545','label'=>'Failed',     'icon'=>'fa-times-circle'],
                                default        => ['bg'=>'#adb5bd','label'=>'Unknown',    'icon'=>'fa-question-circle'],
                            };
                            $logLines = $client->provision_log ? array_filter(explode("\n", $client->provision_log)) : [];
                        @endphp
                        <tr>
                            <td style="padding:10px 14px;">
                                <a href="{{ route('admin.clients.show', $client) }}" style="font-weight:600;color:#333;">{{ $client->name }}</a>
                                <div style="font-size:11px;color:#999;">{{ $client->client_id ?? '—' }}</div>
                            </td>
                            <td>
                                <span style="background:{{ $badge['bg'] }};color:#fff;padding:2px 10px;border-radius:20px;font-size:11px;font-weight:600;white-space:nowrap;">
                                    <i class="fas {{ $badge['icon'] }} mr-1"></i>{{ $badge['label'] }}
                                </span>
                            </td>
                            <td style="white-space:nowrap;color:#555;">
                                {{ $client->provision_attempted_at
                                    ? \Carbon\Carbon::parse($client->provision_attempted_at)->timezone('Asia/Dhaka')->format('d M Y, h:i A')
                                    : '—' }}
                            </td>
                            <td style="max-width:320px;">
                                @if(count($logLines))
                                    @foreach($logLines as $line)
                                        @php
                                            $lineColor = str_contains($line,'ERROR') ? '#dc3545' : (str_contains($line,'WARNING') ? '#fd7e14' : '#555');
                                        @endphp
                                        <div style="font-size:11px;color:{{ $lineColor }};font-family:monospace;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="{{ $line }}">{{ $line }}</div>
                                    @endforeach
                                @else
                                    <span class="text-muted" style="font-size:11px;">—</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <a href="{{ route('admin.clients.show', $client) }}" class="btn btn-xs btn-outline-primary" style="font-size:11px;padding:2px 8px;">
                                    <i class="fas fa-eye"></i> View
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">
                                <i class="fas fa-magic fa-2x mb-2 d-block" style="color:#dee2e6;"></i>
                                No ZTP activity found. ZTP runs automatically when new clients are added.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($logs->hasPages())
        <div class="card-footer d-flex justify-content-between align-items-center py-2">
            <small class="text-muted">Showing {{ $logs->firstItem() }}–{{ $logs->lastItem() }} of {{ $logs->total() }} entries</small>
            {{ $logs->links() }}
        </div>
        @endif
    </div>

</div>
@endsection
