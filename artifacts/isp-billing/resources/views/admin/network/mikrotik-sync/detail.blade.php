@extends('layouts.admin')

@section('title', 'Sync Run #' . $run->id . ' - ' . ($run->device->name ?? ''))
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
<li class="breadcrumb-item"><a href="{{ route('admin.network.devices') }}">MikroTik Devices</a></li>
<li class="breadcrumb-item"><a href="{{ route('admin.mikrotik-sync.index') }}">Two-Way Sync</a></li>
<li class="breadcrumb-item active">Run #{{ $run->id }}</li>
@endsection

@section('content')
<div class="row mb-3">
    <div class="col-12 d-flex flex-wrap align-items-center justify-content-between">
        <div>
            <h4 class="mb-0">
                <i class="fas fa-sync-alt mr-2"></i>Sync Run #{{ $run->id }} - {{ $run->device->name ?? 'Unknown' }}
                <small class="text-muted ml-2">({{ $run->device->host ?? '' }})</small>
            </h4>
            <small class="text-muted">
                {{ $run->created_at->format('d-m-y H:i:s') }} | 
                Mode: <strong>{{ $run->mode === 'dry_run' ? 'Dry Run' : 'Applied' }}</strong> |
                By: {{ $run->initiator->name ?? 'System' }}
            </small>
        </div>
        <div class="mt-2 mt-md-0">
            @if($stats['pending'] > 0)
                <form action="{{ route('admin.mikrotik-sync.apply-all', $run) }}" method="POST" class="d-inline"
                    onsubmit="return confirm('Apply ALL {{ $stats['pending'] }} pending actions? This will modify the router.')">
                    @csrf
                    <button type="submit" class="btn btn-success btn-sm">
                        <i class="fas fa-check-double mr-1"></i>Apply All Pending ({{ $stats['pending'] }})
                    </button>
                </form>
            @endif
            <a href="{{ route('admin.mikrotik-sync.index') }}" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left mr-1"></i>Back to Sync History
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-2 col-sm-4 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>{{ $stats['synced'] }}</h3>
                <p>In Sync</p>
            </div>
            <div class="icon"><i class="fas fa-check-circle"></i></div>
        </div>
    </div>
    <div class="col-lg-2 col-sm-4 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3>{{ $stats['only_on_router'] }}</h3>
                <p>Ghost Users</p>
            </div>
            <div class="icon"><i class="fas fa-ghost"></i></div>
        </div>
    </div>
    <div class="col-lg-2 col-sm-4 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>{{ $stats['only_in_billing'] }}</h3>
                <p>Billing Only</p>
            </div>
            <div class="icon"><i class="fas fa-file-invoice-dollar"></i></div>
        </div>
    </div>
    <div class="col-lg-2 col-sm-4 col-6">
        <div class="small-box bg-danger">
            <div class="inner">
                <h3>{{ $stats['mismatch'] }}</h3>
                <p>Mismatches</p>
            </div>
            <div class="icon"><i class="fas fa-exclamation-triangle"></i></div>
        </div>
    </div>
    <div class="col-lg-2 col-sm-4 col-6">
        <div class="small-box bg-olive">
            <div class="inner">
                <h3>{{ $stats['applied'] }}</h3>
                <p>Applied</p>
            </div>
            <div class="icon"><i class="fas fa-check"></i></div>
        </div>
    </div>
    <div class="col-lg-2 col-sm-4 col-6">
        <div class="small-box bg-maroon">
            <div class="inner">
                <h3>{{ $stats['failed'] }}</h3>
                <p>Failed</p>
            </div>
            <div class="icon"><i class="fas fa-times"></i></div>
        </div>
    </div>
</div>

@if($run->error_message)
<div class="alert alert-danger">
    <i class="fas fa-exclamation-circle mr-1"></i>
    <strong>Error:</strong> {{ $run->error_message }}
</div>
@endif

@php
    $ghostItems = $run->items->where('diff_type', 'only_on_router');
    $mismatchItems = $run->items->where('diff_type', 'mismatch');
    $billingOnlyItems = $run->items->where('diff_type', 'only_in_billing');
    $syncedItems = $run->items->where('diff_type', 'synced');
@endphp

@if($ghostItems->count() > 0)
<div class="card card-warning card-outline">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-ghost mr-2"></i>Ghost Users (On Router Only) - {{ $ghostItems->count() }}</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
        </div>
    </div>
    <div class="card-body p-0">
        <table class="table table-sm table-striped mb-0">
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Router Profile</th>
                    <th>Router Status</th>
                    <th>Comment</th>
                    <th>Action Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($ghostItems as $item)
                <tr>
                    <td><strong>{{ $item->username }}</strong></td>
                    <td><span class="badge badge-secondary">{{ $item->router_profile }}</span></td>
                    <td>
                        @if($item->router_disabled)
                            <span class="badge badge-danger">Disabled</span>
                        @else
                            <span class="badge badge-success">Enabled</span>
                        @endif
                    </td>
                    <td>{{ $item->client_name ?? '-' }}</td>
                    <td>
                        @include('admin.network.mikrotik-sync._action_badge', ['item' => $item])
                    </td>
                    <td>
                        @if($item->action_status === 'pending')
                            <form action="{{ route('admin.mikrotik-sync.apply-action', $item) }}" method="POST" class="d-inline">
                                @csrf
                                <input type="hidden" name="action" value="disable_on_router">
                                <button type="submit" class="btn btn-xs btn-warning" title="Disable on Router"
                                    onclick="return confirm('Disable {{ $item->username }} on router?')">
                                    <i class="fas fa-ban"></i> Disable
                                </button>
                            </form>
                            <form action="{{ route('admin.mikrotik-sync.skip-action', $item) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-xs btn-secondary" title="Skip">
                                    <i class="fas fa-forward"></i> Skip
                                </button>
                            </form>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

@if($mismatchItems->count() > 0)
<div class="card card-danger card-outline">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-exclamation-triangle mr-2"></i>Mismatches - {{ $mismatchItems->count() }}</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
        </div>
    </div>
    <div class="card-body p-0">
        <table class="table table-sm table-striped mb-0">
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Client</th>
                    <th>Router Profile</th>
                    <th>Billing Profile</th>
                    <th>Router Status</th>
                    <th>Billing Status</th>
                    <th>Proposed Action</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($mismatchItems as $item)
                <tr>
                    <td><strong>{{ $item->username }}</strong></td>
                    <td>
                        @if($item->client_id)
                            <a href="{{ route('admin.clients.show', $item->client_id) }}">{{ $item->client_code }} - {{ $item->client_name }}</a>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                    <td>
                        <span class="badge badge-secondary">{{ $item->router_profile }}</span>
                        @if(strtolower($item->router_profile ?? '') !== strtolower($item->billing_profile ?? ''))
                            <i class="fas fa-not-equal text-danger ml-1" title="Profile mismatch"></i>
                        @endif
                    </td>
                    <td><span class="badge badge-primary">{{ $item->billing_profile }}</span></td>
                    <td>
                        @if($item->router_disabled)
                            <span class="badge badge-danger">Disabled</span>
                        @else
                            <span class="badge badge-success">Enabled</span>
                        @endif
                    </td>
                    <td><span class="badge badge-{{ $item->billing_status === 'active' ? 'success' : 'warning' }}">{{ ucfirst($item->billing_status) }}</span></td>
                    <td>
                        @php $actionLabel = str_replace('_', ' ', ucfirst($item->proposed_action ?? 'review')); @endphp
                        <span class="badge badge-outline-primary">{{ $actionLabel }}</span>
                    </td>
                    <td>@include('admin.network.mikrotik-sync._action_badge', ['item' => $item])</td>
                    <td>
                        @if($item->action_status === 'pending' && $item->proposed_action)
                            <form action="{{ route('admin.mikrotik-sync.apply-action', $item) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-xs btn-primary" title="Apply proposed action"
                                    onclick="return confirm('Apply {{ $actionLabel }} for {{ $item->username }}?')">
                                    <i class="fas fa-play"></i> Apply
                                </button>
                            </form>
                            <form action="{{ route('admin.mikrotik-sync.skip-action', $item) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-xs btn-secondary" title="Skip">
                                    <i class="fas fa-forward"></i>
                                </button>
                            </form>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

@if($billingOnlyItems->count() > 0)
<div class="card card-info card-outline">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-file-invoice-dollar mr-2"></i>Billing Only (Missing From Router) - {{ $billingOnlyItems->count() }}</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
        </div>
    </div>
    <div class="card-body p-0">
        <table class="table table-sm table-striped mb-0">
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Client</th>
                    <th>Billing Profile</th>
                    <th>Billing Status</th>
                    <th>Proposed Action</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($billingOnlyItems as $item)
                <tr>
                    <td><strong>{{ $item->username }}</strong></td>
                    <td>
                        @if($item->client_id)
                            <a href="{{ route('admin.clients.show', $item->client_id) }}">{{ $item->client_code }} - {{ $item->client_name }}</a>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                    <td><span class="badge badge-primary">{{ $item->billing_profile }}</span></td>
                    <td><span class="badge badge-{{ $item->billing_status === 'active' ? 'success' : 'warning' }}">{{ ucfirst($item->billing_status ?? '-') }}</span></td>
                    <td>
                        @php $actionLabel = str_replace('_', ' ', ucfirst($item->proposed_action ?? '-')); @endphp
                        <span class="badge badge-info">{{ $actionLabel }}</span>
                    </td>
                    <td>@include('admin.network.mikrotik-sync._action_badge', ['item' => $item])</td>
                    <td>
                        @if($item->action_status === 'pending' && $item->proposed_action)
                            <form action="{{ route('admin.mikrotik-sync.apply-action', $item) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-xs btn-success" title="Create on Router"
                                    onclick="return confirm('Create {{ $item->username }} on router with profile {{ $item->billing_profile }}?')">
                                    <i class="fas fa-plus"></i> Create
                                </button>
                            </form>
                            <form action="{{ route('admin.mikrotik-sync.skip-action', $item) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-xs btn-secondary" title="Skip">
                                    <i class="fas fa-forward"></i>
                                </button>
                            </form>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

@if($syncedItems->count() > 0)
<div class="card card-success card-outline collapsed-card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-check-circle mr-2"></i>In Sync - {{ $syncedItems->count() }}</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-plus"></i></button>
        </div>
    </div>
    <div class="card-body p-0" style="display: none;">
        <table class="table table-sm table-striped mb-0">
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Client</th>
                    <th>Profile</th>
                    <th>Router Status</th>
                    <th>Billing Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($syncedItems as $item)
                <tr>
                    <td>{{ $item->username }}</td>
                    <td>
                        @if($item->client_id)
                            <a href="{{ route('admin.clients.show', $item->client_id) }}">{{ $item->client_code }} - {{ $item->client_name }}</a>
                        @else
                            {{ $item->client_name ?? '-' }}
                        @endif
                    </td>
                    <td><span class="badge badge-success">{{ $item->router_profile }}</span></td>
                    <td>
                        @if($item->router_disabled)
                            <span class="badge badge-danger">Disabled</span>
                        @else
                            <span class="badge badge-success">Enabled</span>
                        @endif
                    </td>
                    <td><span class="badge badge-success">{{ ucfirst($item->billing_status) }}</span></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-info-circle mr-2"></i>Run Summary</h3>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <table class="table table-sm">
                    <tr><td><strong>Run ID:</strong></td><td>#{{ $run->id }}</td></tr>
                    <tr><td><strong>Router:</strong></td><td>{{ $run->device->name ?? 'N/A' }} ({{ $run->device->host ?? '' }})</td></tr>
                    <tr><td><strong>Mode:</strong></td><td>{{ $run->mode === 'dry_run' ? 'Dry Run' : 'Apply' }}</td></tr>
                    <tr><td><strong>Status:</strong></td><td>
                        @if($run->status === 'completed')
                            <span class="badge badge-success">Completed</span>
                        @elseif($run->status === 'running')
                            <span class="badge badge-warning">Running</span>
                        @else
                            <span class="badge badge-danger">Failed</span>
                        @endif
                    </td></tr>
                </table>
            </div>
            <div class="col-md-6">
                <table class="table table-sm">
                    <tr><td><strong>Started:</strong></td><td>{{ $run->started_at ? $run->started_at->format('d-m-y H:i:s') : '-' }}</td></tr>
                    <tr><td><strong>Completed:</strong></td><td>{{ $run->completed_at ? $run->completed_at->format('d-m-y H:i:s') : '-' }}</td></tr>
                    <tr><td><strong>Duration:</strong></td><td>
                        @if($run->started_at && $run->completed_at)
                            {{ $run->started_at->diffForHumans($run->completed_at, true) }}
                        @else
                            -
                        @endif
                    </td></tr>
                    <tr><td><strong>Initiated By:</strong></td><td>{{ $run->initiator->name ?? 'System' }}</td></tr>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
