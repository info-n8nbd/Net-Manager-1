@extends('layouts.admin')

@section('title', 'ONU Detail - ' . $onu->serial_number)
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
<li class="breadcrumb-item"><a href="{{ route('admin.olt.index') }}">OLT Devices</a></li>
@if($olt)
<li class="breadcrumb-item"><a href="{{ route('admin.olt.dashboard', $olt) }}">{{ $olt->name }}</a></li>
<li class="breadcrumb-item"><a href="{{ route('admin.olt.onus', $olt) }}">ONUs</a></li>
@endif
<li class="breadcrumb-item active">{{ $onu->serial_number }}</li>
@endsection

@section('content')
<div class="row mb-3">
    <div class="col-md-12">
        <div class="d-flex justify-content-between align-items-center">
            <h4 class="mb-0">
                <i class="fas fa-microchip mr-2"></i>ONU Detail: <code>{{ $onu->serial_number }}</code>
                @if($onu->status === 'online')
                    <span class="badge badge-success ml-2">Online</span>
                @elseif($onu->status === 'offline')
                    <span class="badge badge-danger ml-2">Offline</span>
                @else
                    <span class="badge badge-secondary ml-2">{{ ucfirst($onu->status) }}</span>
                @endif
            </h4>
            <div>
                @if($olt)
                <a href="{{ route('admin.olt.onus', $olt) }}" class="btn btn-default btn-sm"><i class="fas fa-arrow-left mr-1"></i>Back to ONUs</a>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-signal mr-2"></i>Signal Levels</h3>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="text-muted mb-1">Rx Power (ONU)</label>
                    <h4 class="{{ ($onu->rx_power ?? -99) >= -20 ? 'text-success' : (($onu->rx_power ?? -99) >= -25 ? 'text-warning' : 'text-danger') }}">
                        {{ $onu->rx_power ? $onu->rx_power . ' dBm' : 'N/A' }}
                    </h4>
                </div>
                <div class="mb-3">
                    <label class="text-muted mb-1">Tx Power (ONU)</label>
                    <h4>{{ $onu->tx_power ? $onu->tx_power . ' dBm' : 'N/A' }}</h4>
                </div>
                <div class="mb-3">
                    <label class="text-muted mb-1">Signal Quality</label>
                    @php
                        $sq = $onu->signal_quality ?? ($onu->rx_power >= -15 ? 'excellent' : ($onu->rx_power >= -20 ? 'good' : ($onu->rx_power >= -25 ? 'fair' : 'poor')));
                    @endphp
                    @if($sq === 'excellent')
                        <span class="badge badge-success badge-lg p-2">Excellent</span>
                    @elseif($sq === 'good')
                        <span class="badge badge-info badge-lg p-2">Good</span>
                    @elseif($sq === 'fair')
                        <span class="badge badge-warning badge-lg p-2">Fair</span>
                    @else
                        <span class="badge badge-danger badge-lg p-2">Poor</span>
                    @endif
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-thermometer-half mr-2"></i>Device Status</h3>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="text-muted mb-1">Temperature</label>
                    <h4>{{ $onu->temperature ? $onu->temperature . ' °C' : 'N/A' }}</h4>
                </div>
                <div class="mb-3">
                    <label class="text-muted mb-1">Uptime</label>
                    <h4>
                        @if($onu->uptime_seconds)
                            @php
                                $days = floor($onu->uptime_seconds / 86400);
                                $hours = floor(($onu->uptime_seconds % 86400) / 3600);
                                $mins = floor(($onu->uptime_seconds % 3600) / 60);
                            @endphp
                            {{ $days }}d {{ $hours }}h {{ $mins }}m
                        @else
                            N/A
                        @endif
                    </h4>
                </div>
                <div class="mb-3">
                    <label class="text-muted mb-1">Last Online</label>
                    <p>{{ $onu->last_online ? \Carbon\Carbon::parse($onu->last_online)->format('d-m-y H:i:s') : 'N/A' }}</p>
                </div>
                <div class="mb-3">
                    <label class="text-muted mb-1">Last Offline</label>
                    <p>{{ $onu->last_offline ? \Carbon\Carbon::parse($onu->last_offline)->format('d-m-y H:i:s') : 'N/A' }}</p>
                </div>
                <div class="mb-3">
                    <label class="text-muted mb-1">Down Reason</label>
                    <p>{{ $onu->down_reason ?? 'N/A' }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-info-circle mr-2"></i>ONU Information</h3>
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless">
                    <tr><td class="text-muted">Serial Number</td><td><code>{{ $onu->serial_number }}</code></td></tr>
                    <tr><td class="text-muted">OLT Device</td><td>{{ $onu->olt_name ?? 'N/A' }}</td></tr>
                    <tr><td class="text-muted">OLT Vendor</td><td><span class="badge badge-info">{{ ucfirst($onu->olt_vendor ?? 'N/A') }}</span></td></tr>
                    <tr><td class="text-muted">OLT IP</td><td><code>{{ $onu->olt_ip ?? 'N/A' }}</code></td></tr>
                    <tr><td class="text-muted">PON Port</td><td>{{ $onu->pon_port }}</td></tr>
                    <tr><td class="text-muted">ONU Number</td><td>{{ $onu->onu_number ?? 'N/A' }}</td></tr>
                    <tr><td class="text-muted">Model</td><td>{{ $onu->model ?? 'N/A' }}</td></tr>
                    <tr><td class="text-muted">MAC Address</td><td><code>{{ $onu->mac_address ?? 'N/A' }}</code></td></tr>
                    <tr><td class="text-muted">IP Address</td><td>{{ $onu->ip_address ?? 'N/A' }}</td></tr>
                    <tr><td class="text-muted">VLAN</td><td>{{ $onu->vlan ?? 'N/A' }}</td></tr>
                    <tr><td class="text-muted">Line Profile</td><td>{{ $onu->line_profile ?? 'N/A' }}</td></tr>
                    <tr><td class="text-muted">Service Profile</td><td>{{ $onu->service_profile ?? 'N/A' }}</td></tr>
                    <tr><td class="text-muted">Description</td><td>{{ $onu->description ?? 'N/A' }}</td></tr>
                    <tr><td class="text-muted">Provisioned</td><td>
                        @if($onu->is_provisioned)
                            <span class="badge badge-success">Yes</span>
                        @else
                            <span class="badge badge-secondary">No</span>
                        @endif
                    </td></tr>
                    <tr><td class="text-muted">Provisioned At</td><td>{{ $onu->provisioned_at ? \Carbon\Carbon::parse($onu->provisioned_at)->format('d-m-y H:i') : 'N/A' }}</td></tr>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-user mr-2"></i>Client Information</h3>
            </div>
            <div class="card-body">
                @if($onu->client_name)
                <table class="table table-sm table-borderless">
                    <tr><td class="text-muted">Name</td><td><strong>{{ $onu->client_name }}</strong></td></tr>
                    <tr><td class="text-muted">Client ID</td><td><code>{{ $onu->client_code ?? 'N/A' }}</code></td></tr>
                    <tr><td class="text-muted">Phone</td><td>{{ $onu->client_phone ?? 'N/A' }}</td></tr>
                    <tr><td class="text-muted">Email</td><td>{{ $onu->client_email ?? 'N/A' }}</td></tr>
                </table>
                <a href="{{ ($onu->client_id ? route('admin.clients.show', $onu->client_id) : '#') }}" class="btn btn-primary btn-block btn-sm">
                    <i class="fas fa-user mr-1"></i>View Client Profile
                </a>
                @else
                <div class="text-center py-4">
                    <i class="fas fa-user-slash fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No client assigned</p>
                    <button class="btn btn-primary btn-sm" onclick="showAssignModal()">
                        <i class="fas fa-user-plus mr-1"></i>Assign Client
                    </button>
                </div>
                @endif
            </div>
        </div>

        @if($onu->status !== 'decommissioned')
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-tools mr-2"></i>Actions</h3>
            </div>
            <div class="card-body">
                <button class="btn btn-danger btn-block mb-2" onclick="decommission()">
                    <i class="fas fa-trash mr-1"></i>Decommission ONU
                </button>
            </div>
        </div>
        @endif
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-history mr-2"></i>Provision Log</h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Action</th>
                                <th>Result</th>
                                <th>By</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($provisionLogs as $log)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($log->created_at)->format('d-m-y H:i') }}</td>
                                <td>
                                    @if($log->action === 'provision')
                                        <span class="badge badge-success">Provision</span>
                                    @else
                                        <span class="badge badge-danger">Decommission</span>
                                    @endif
                                </td>
                                <td>
                                    @if($log->result === 'success')
                                        <span class="badge badge-success">Success</span>
                                    @else
                                        <span class="badge badge-danger">Failed</span>
                                    @endif
                                </td>
                                <td>{{ $log->performed_by_name ?? 'System' }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="text-center py-3 text-muted">No provision logs</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-redo mr-2"></i>Reboot History</h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Type</th>
                                <th>Reason</th>
                                <th>Result</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($rebootHistory as $reboot)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($reboot->rebooted_at)->format('d-m-y H:i') }}</td>
                                <td><span class="badge badge-info">{{ ucfirst($reboot->reboot_type) }}</span></td>
                                <td>{{ \Illuminate\Support\Str::limit($reboot->reason, 40) ?: 'N/A' }}</td>
                                <td>
                                    @if($reboot->result === 'success')
                                        <span class="badge badge-success">Success</span>
                                    @elseif($reboot->result === 'pending')
                                        <span class="badge badge-warning">Pending</span>
                                    @else
                                        <span class="badge badge-danger">Failed</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="text-center py-3 text-muted">No reboot history</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-bell mr-2"></i>Alarm History</h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Type</th>
                                <th>Severity</th>
                                <th>Rx Power</th>
                                <th>Status</th>
                                <th>Resolved At</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($alarms as $alarm)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($alarm->created_at)->format('d-m-y H:i') }}</td>
                                <td><span class="badge badge-warning">{{ $alarm->alarm_type }}</span></td>
                                <td>
                                    @if($alarm->severity === 'critical')
                                        <span class="badge badge-danger">Critical</span>
                                    @elseif($alarm->severity === 'major')
                                        <span class="badge badge-warning">Major</span>
                                    @else
                                        <span class="badge badge-info">{{ ucfirst($alarm->severity) }}</span>
                                    @endif
                                </td>
                                <td>{{ $alarm->rx_power ? $alarm->rx_power . ' dBm' : 'N/A' }}</td>
                                <td>
                                    @if($alarm->is_resolved)
                                        <span class="badge badge-success">Resolved</span>
                                    @else
                                        <span class="badge badge-danger">Active</span>
                                    @endif
                                </td>
                                <td>{{ $alarm->resolved_at ? \Carbon\Carbon::parse($alarm->resolved_at)->format('d-m-y H:i') : '-' }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="6" class="text-center py-3 text-muted">No alarm history</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="assignModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-user-plus mr-2"></i>Assign ONU to Client</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                @include('admin.partials.client-search', [
                    'inputId' => 'assignClientSearch',
                    'hiddenId' => 'assignClientId',
                    'fieldName' => 'client_id',
                    'label' => 'Client',
                    'required' => true,
                ])
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="submitAssign()">
                    <i class="fas fa-check mr-1"></i>Assign
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function showAssignModal() {
    $('#assignClientSearch').val('');
    $('#assignClientId').val('');
    $('#assignModal').modal('show');
}

function submitAssign() {
    var clientId = $('#assignClientId').val();
    if (!clientId) { alert('Please select a client'); return; }

    $.ajax({
        url: '/admin/network/onu/{{ $onu->id }}/assign',
        type: 'POST',
        data: {client_id: clientId},
        headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
        success: function(res) {
            if (res.success) {
                location.reload();
            } else {
                alert(res.message || 'Assignment failed');
            }
        },
        error: function() { alert('Error assigning ONU'); }
    });
}

function decommission() {
    var reason = prompt('Enter reason for decommissioning:');
    if (reason === null) return;

    $.ajax({
        url: '/admin/network/onu/{{ $onu->id }}/decommission',
        type: 'POST',
        data: {reason: reason},
        headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
        success: function(res) {
            if (res.success) {
                alert('ONU decommissioned successfully');
                location.reload();
            } else {
                alert(res.message || 'Decommission failed');
            }
        },
        error: function() { alert('Error decommissioning ONU'); }
    });
}
</script>
@endpush
