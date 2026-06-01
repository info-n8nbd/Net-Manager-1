@extends('layouts.admin')

@section('title', 'Add OLT Device')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
<li class="breadcrumb-item"><a href="{{ route('admin.olt.index') }}">OLT Devices</a></li>
<li class="breadcrumb-item active">Add New</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-plus-circle mr-2"></i>Add New OLT Device</h3>
            </div>
            <form action="{{ route('admin.olt.store') }}" method="POST">
                @csrf
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Device Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Vendor <span class="text-danger">*</span></label>
                                <select name="vendor" class="form-control @error('vendor') is-invalid @enderror" required>
                                    @foreach($vendors as $key => $name)
                                        <option value="{{ $key }}" {{ old('vendor') == $key ? 'selected' : '' }}>{{ $name }}</option>
                                    @endforeach
                                </select>
                                @error('vendor')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>PON Type <small class="text-muted ml-1">(auto-detected on save)</small></label>
                                <div class="input-group">
                                    <select name="pon_type" id="pon_type" class="form-control @error('pon_type') is-invalid @enderror">
                                        @foreach(\App\Models\OltDevice::PON_TYPES as $key => $name)
                                            <option value="{{ $key }}" {{ old('pon_type', 'epon') == $key ? 'selected' : '' }}>{{ $name }}</option>
                                        @endforeach
                                    </select>
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-outline-info" id="btnDetectPon" title="Auto-detect from device">
                                            <i class="fas fa-magic"></i> Detect
                                        </button>
                                    </div>
                                </div>
                                <small class="form-text" id="pon_detect_status"></small>
                                @error('pon_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Connection Type <span class="text-danger">*</span></label>
                                <select name="connection_type" id="connection_type" class="form-control @error('connection_type') is-invalid @enderror" required>
                                    @foreach(\App\Models\OltDevice::CONNECTION_TYPES as $key => $name)
                                        <option value="{{ $key }}" {{ old('connection_type', 'telnet') == $key ? 'selected' : '' }}>{{ $name }}</option>
                                    @endforeach
                                </select>
                                @error('connection_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>IP Address <span class="text-danger">*</span></label>
                                <input type="text" name="ip_address" class="form-control @error('ip_address') is-invalid @enderror" value="{{ old('ip_address') }}" placeholder="192.168.1.1" required>
                                @error('ip_address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label id="port_label">Telnet Port <span class="text-danger">*</span></label>
                                <input type="number" name="port" id="main_port" class="form-control @error('port') is-invalid @enderror" value="{{ old('port', 23) }}" required>
                                @error('port')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                    <div class="row" id="credentials_section">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Username <span class="text-danger cred-required">*</span></label>
                                <input type="text" name="username" class="form-control @error('username') is-invalid @enderror" value="{{ old('username') }}">
                                @error('username')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Password <span class="text-danger cred-required">*</span></label>
                                <input type="password" name="password" class="form-control @error('password') is-invalid @enderror">
                                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                    <div class="form-group mt-2">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="snmp_enabled" name="snmp_enabled" value="1" {{ old('snmp_enabled') ? 'checked' : '' }}>
                            <label class="custom-control-label" for="snmp_enabled">Enable SNMP Monitoring (for ONU polling, system health, traffic stats)</label>
                        </div>
                    </div>
                    <div id="snmp_section" style="display:none;">
                        <hr>
                        <h6><i class="fas fa-network-wired mr-1"></i> SNMP Settings</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Community <span class="text-danger">*</span></label>
                                    <input type="text" name="snmp_community" class="form-control" value="{{ old('snmp_community', 'public') }}" placeholder="public">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Model</label>
                                <input type="text" name="model" class="form-control" value="{{ old('model') }}" placeholder="e.g., MA5680T">
                            </div>
                        </div>
                        <div class="col-md-4" id="total_ports_row">
                            <div class="form-group">
                                <label>Total Ports <small class="text-muted" id="total_ports_note">(Auto-detected for SNMP)</small></label>
                                <input type="number" name="total_ports" id="total_ports_input" class="form-control @error('total_ports') is-invalid @enderror" value="{{ old('total_ports', 8) }}">
                                @error('total_ports')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Location</label>
                                <input type="text" name="location" class="form-control" value="{{ old('location') }}" placeholder="e.g., Main Office">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Notes</label>
                        <textarea name="notes" class="form-control" rows="3" placeholder="Additional notes...">{{ old('notes') }}</textarea>
                    </div>
                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                            <label class="custom-control-label" for="is_active">Active</label>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-2"></i>Save Device</button>
                    <a href="{{ route('admin.olt.index') }}" class="btn btn-secondary"><i class="fas fa-arrow-left mr-2"></i>Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function() {
    var defaults = {telnet: 23, ssh: 22, http: 80, snmp: 161};
    var portLabels = {
        telnet: 'Telnet Port',
        ssh:    'SSH Port',
        http:   'HTTP Port',
        snmp:   'SNMP Port'
    };

    function updatePortLabel(val) {
        var label = (portLabels[val] || 'Port') + ' <span class="text-danger">*</span>';
        $('#port_label').html(label);
    }

    function toggleSections() {
        var connType = $('#connection_type').val();
        var snmpEnabled = $('#snmp_enabled').is(':checked');
        var isSNMP = connType === 'snmp';

        if (isSNMP || snmpEnabled) {
            $('#snmp_section').show();
        } else {
            $('#snmp_section').hide();
        }

        if (isSNMP) {
            $('#credentials_section').hide();
            $('#credentials_section input').removeAttr('required');
            $('#total_ports_note').show();
            $('#total_ports_input').removeAttr('required');
        } else {
            $('#credentials_section').show();
            $('input[name="username"]').attr('required', true);
            $('input[name="password"]').attr('required', true);
            $('#total_ports_note').hide();
            $('#total_ports_input').attr('required', true);
        }
    }

    toggleSections();

    $('#snmp_enabled').on('change', toggleSections);

    $('#connection_type').on('change', function() {
        var val = $(this).val();
        var portVal = defaults[val] || 23;
        $('#main_port').val(portVal);
        updatePortLabel(val);
        if (val === 'snmp') {
            $('#snmp_enabled').prop('checked', true);
            $('input[name="snmp_port"]').val(portVal);
        }
        toggleSections();
    }).trigger('change');

    // Auto-detect PON type button
    $('#btnDetectPon').on('click', function() {
        var ip = $('input[name="ip_address"]').val().trim();
        var snmpPort = $('input[name="snmp_port"]').val() || 161;
        var community = $('input[name="snmp_community"]').val() || 'public';
        var vendor = $('select[name="vendor"]').val();
        if (!ip) { alert('Please enter IP address first.'); return; }
        var btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Detecting...');
        $('#pon_detect_status').text('');
        $.ajax({
            url: '{{ route("admin.olt.detect-pon-type") }}',
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            data: { ip_address: ip, snmp_port: snmpPort, snmp_community: community, vendor: vendor },
            success: function(res) {
                if (res.success) {
                    $('#pon_type').val(res.pon_type);
                    $('#pon_detect_status').html('<span class="text-success"><i class="fas fa-check-circle mr-1"></i>Auto-detected: <strong>' + res.pon_type_label + '</strong>' + (res.system_description ? ' — ' + res.system_description : '') + '</span>');
                } else {
                    $('#pon_detect_status').html('<span class="text-danger"><i class="fas fa-times-circle mr-1"></i>' + (res.message || 'Detection failed') + '</span>');
                }
            },
            error: function() {
                $('#pon_detect_status').html('<span class="text-danger"><i class="fas fa-times-circle mr-1"></i>Connection failed. Check IP and SNMP settings.</span>');
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="fas fa-magic"></i> Detect');
            }
        });
    });
});
</script>
@endpush
