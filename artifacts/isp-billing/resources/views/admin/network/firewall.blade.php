@extends('layouts.admin')

@section('title', 'Firewall Rules')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
<li class="breadcrumb-item">Network</li>
<li class="breadcrumb-item active">Firewall Rules</li>
@endsection

@section('content')
<div class="row mb-3">
    <div class="col-md-4">
        <div class="form-group">
            <label>Select Device</label>
            <select class="form-control" id="deviceSelector" onchange="switchDevice(this.value)">
                @foreach($devices as $d)
                    <option value="{{ $d->id }}" {{ $d->id == $device->id ? 'selected' : '' }}>{{ $d->name }} ({{ $d->host }})</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="col-md-8 d-flex align-items-end">
        <div class="btn-group mb-3">
            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addRuleModal">
                <i class="fas fa-plus mr-1"></i>Add Rule
            </button>
            <button type="button" class="btn btn-info" id="btnSyncFirewall">
                <i class="fas fa-sync mr-1"></i>Sync from MikroTik Router
            </button>
            <a href="{{ route('admin.network.nat', $device) }}" class="btn btn-warning">
                <i class="fas fa-exchange-alt mr-1"></i>NAT Rules
            </a>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-fire mr-2"></i>Firewall Filter Rules - {{ $device->name }}</h3>
        <div class="card-tools">
            <span class="badge badge-info">{{ $rules->count() }} rules</span>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped table-hover mb-0">
                <thead class="bg-dark text-white">
                    <tr>
                        <th>#</th>
                        <th>Chain</th>
                        <th>Action</th>
                        <th>Protocol</th>
                        <th>Src Address</th>
                        <th>Dst Address</th>
                        <th>Port</th>
                        <th>Comment</th>
                        <th>Status</th>
                        <th width="120">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rules as $index => $rule)
                    <tr id="rule-row-{{ $rule->id }}">
                        <td>{{ $index + 1 }}</td>
                        <td><span class="badge badge-primary">{{ $rule->chain }}</span></td>
                        <td>
                            @if($rule->action == 'accept')
                                <span class="badge badge-success">{{ $rule->action }}</span>
                            @elseif($rule->action == 'drop')
                                <span class="badge badge-danger">{{ $rule->action }}</span>
                            @elseif($rule->action == 'reject')
                                <span class="badge badge-warning">{{ $rule->action }}</span>
                            @else
                                <span class="badge badge-secondary">{{ $rule->action }}</span>
                            @endif
                        </td>
                        <td>{{ $rule->protocol ?? 'any' }}</td>
                        <td><code>{{ $rule->src_address ?? 'any' }}</code></td>
                        <td><code>{{ $rule->dst_address ?? 'any' }}</code></td>
                        <td>{{ $rule->src_port ?? $rule->dst_port ?? '-' }}</td>
                        <td>{{ $rule->comment ?? '-' }}</td>
                        <td>
                            @if($rule->is_enabled)
                                <span class="badge badge-success">Enabled</span>
                            @else
                                <span class="badge badge-danger">Disabled</span>
                            @endif
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-{{ $rule->is_enabled ? 'warning' : 'success' }}" onclick="toggleRule({{ $rule->id }})" title="{{ $rule->is_enabled ? 'Disable' : 'Enable' }}">
                                    <i class="fas fa-{{ $rule->is_enabled ? 'pause' : 'play' }}"></i>
                                </button>
                                <button type="button" class="btn btn-outline-danger" onclick="deleteRule({{ $rule->id }})" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="text-center py-4">
                            <i class="fas fa-fire fa-3x text-muted mb-3 d-block"></i>
                            <p class="text-muted">No firewall rules found. Click "Sync from MikroTik Router" to import existing rules.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="addRuleModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-plus mr-2"></i>Add Firewall Rule</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <form id="addRuleForm">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Chain <span class="text-danger">*</span></label>
                                <select class="form-control" name="chain" required>
                                    <option value="forward">forward</option>
                                    <option value="input">input</option>
                                    <option value="output">output</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Action <span class="text-danger">*</span></label>
                                <select class="form-control" name="action" required>
                                    <option value="accept">accept</option>
                                    <option value="drop">drop</option>
                                    <option value="reject">reject</option>
                                    <option value="log">log</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Protocol</label>
                                <select class="form-control" name="protocol">
                                    <option value="">Any</option>
                                    <option value="tcp">TCP</option>
                                    <option value="udp">UDP</option>
                                    <option value="icmp">ICMP</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Source Address</label>
                                <input type="text" class="form-control" name="src_address" placeholder="e.g., 192.168.1.0/24">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Destination Address</label>
                                <input type="text" class="form-control" name="dst_address" placeholder="e.g., 10.0.0.0/8">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Source Port</label>
                                <input type="text" class="form-control" name="src_port" placeholder="e.g., 8080">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Destination Port</label>
                                <input type="text" class="form-control" name="dst_port" placeholder="e.g., 80,443">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Comment</label>
                                <input type="text" class="form-control" name="comment" placeholder="Rule description">
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="btnSaveRule">
                    <i class="fas fa-save mr-1"></i>Create Rule
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
var csrfToken = $('meta[name="csrf-token"]').attr('content');
var deviceId = {{ $device->id }};

function switchDevice(id) {
    window.location.href = '/admin/network/firewall/' + id;
}

$('#btnSyncFirewall').click(function() {
    var btn = $(this);
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Syncing...');
    $.ajax({
        url: '/admin/network/firewall/' + deviceId + '/sync',
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken },
        dataType: 'json',
        success: function(res) {
            if (res.success) {
                alert(res.message);
                location.reload();
            } else {
                alert('Error: ' + res.message);
            }
        },
        error: function() { alert('Sync request failed.'); },
        complete: function() {
            btn.prop('disabled', false).html('<i class="fas fa-sync mr-1"></i>Sync from MikroTik Router');
        }
    });
});

$('#btnSaveRule').click(function() {
    var btn = $(this);
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Creating...');
    $.ajax({
        url: '/admin/network/firewall/' + deviceId + '/store',
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken },
        data: $('#addRuleForm').serialize(),
        dataType: 'json',
        success: function(res) {
            if (res.success) {
                $('#addRuleModal').modal('hide');
                location.reload();
            } else {
                alert('Error: ' + res.message);
            }
        },
        error: function(xhr) {
            var msg = 'Failed to create rule.';
            if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
            alert(msg);
        },
        complete: function() {
            btn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i>Create Rule');
        }
    });
});

function toggleRule(ruleId) {
    $.ajax({
        url: '/admin/network/firewall/' + deviceId + '/' + ruleId + '/toggle',
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken },
        dataType: 'json',
        success: function(res) {
            if (res.success) {
                location.reload();
            } else {
                alert('Error: ' + res.message);
            }
        },
        error: function() { alert('Toggle request failed.'); }
    });
}

function deleteRule(ruleId) {
    smartConfirm({message:'Delete this firewall rule?',isDelete:true}, function() {
        $.ajax({
            url: '/admin/network/firewall/' + deviceId + '/' + ruleId,
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    $('#rule-row-' + ruleId).fadeOut(300, function() { $(this).remove(); });
                } else {
                    smartAlert('Error: ' + res.message, 'error');
                }
            },
            error: function() { smartAlert('Delete request failed.', 'error'); }
        });
    });
}
</script>
@endpush
