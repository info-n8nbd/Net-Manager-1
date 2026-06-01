@extends('layouts.admin')

@section('title', 'ONU Provisioning - ' . $olt->name)
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
<li class="breadcrumb-item"><a href="{{ route('admin.olt.index') }}">OLT Devices</a></li>
<li class="breadcrumb-item"><a href="{{ route('admin.olt.dashboard', $olt) }}">{{ $olt->name }}</a></li>
<li class="breadcrumb-item active">ONU Provisioning</li>
@endsection

@section('content')
<div class="row mb-3">
    <div class="col-md-12">
        <div class="d-flex justify-content-between align-items-center">
            <h4 class="mb-0"><i class="fas fa-plus-circle mr-2"></i>ONU Provisioning - {{ $olt->name }}</h4>
            <a href="{{ route('admin.olt.onus', $olt) }}" class="btn btn-default btn-sm">
                <i class="fas fa-arrow-left mr-1"></i>Back to ONU List
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-7">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h3 class="card-title"><i class="fas fa-broadcast-tower mr-2"></i>Discovered Unregistered ONUs</h3>
                <div class="card-tools">
                    <button class="btn btn-sm btn-light" onclick="discoverOnus()" id="btnDiscover">
                        <i class="fas fa-sync-alt mr-1"></i>Scan Now
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <div id="discoveryLoading" class="text-center py-4 d-none">
                    <i class="fas fa-spinner fa-spin fa-2x text-success"></i>
                    <p class="mt-2 text-muted">Scanning for unregistered ONUs...</p>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th></th>
                                <th>Serial Number</th>
                                <th>Port</th>
                                <th>Vendor</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="discoveredList">
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">
                                    <i class="fas fa-search fa-2x mb-2"></i>
                                    <p>Click "Scan Now" to discover unregistered ONUs</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-5">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h3 class="card-title"><i class="fas fa-cog mr-2"></i>Provision ONU</h3>
            </div>
            <div class="card-body">
                <form id="provisionForm">
                    @csrf
                    <div class="form-group">
                        <label><i class="fas fa-barcode mr-1"></i>Serial Number <span class="text-danger">*</span></label>
                        <input type="text" name="serial" id="provSerial" class="form-control" placeholder="Enter or select from discovered list" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-plug mr-1"></i>Port <span class="text-danger">*</span></label>
                        <input type="text" name="port" id="provPort" class="form-control" placeholder="e.g., 0/0/1" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-layer-group mr-1"></i>Line Profile</label>
                        <input type="text" name="line_profile" class="form-control" value="default" placeholder="Line profile name">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-cogs mr-1"></i>Service Profile</label>
                        <input type="text" name="service_profile" class="form-control" value="default" placeholder="Service profile name">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-tag mr-1"></i>VLAN</label>
                        <input type="text" name="vlan" class="form-control" placeholder="VLAN ID (optional)">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-comment mr-1"></i>Description</label>
                        <input type="text" name="description" class="form-control" placeholder="ONU description">
                    </div>
                    @include('admin.partials.client-search', [
                        'inputId' => 'onuProvClientSearch',
                        'hiddenId' => 'onuProvClientSearchId',
                        'fieldName' => 'client_id',
                        'label' => 'Assign to Client',
                        'required' => false,
                    ])
                    <button type="button" class="btn btn-success btn-block" onclick="submitProvision()" id="btnProvision">
                        <i class="fas fa-check-circle mr-1"></i>Provision ONU
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
var oltId = {{ $olt->id }};

function discoverOnus() {
    var btn = $('#btnDiscover');
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Scanning...');
    $('#discoveryLoading').removeClass('d-none');

    $.ajax({
        url: '{{ route("admin.olt.discover-onus", $olt) }}',
        type: 'POST',
        headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
        success: function(res) {
            btn.prop('disabled', false).html('<i class="fas fa-sync-alt mr-1"></i>Scan Now');
            $('#discoveryLoading').addClass('d-none');

            if (res.success && res.data.length > 0) {
                var html = '';
                res.data.forEach(function(onu, idx) {
                    html += '<tr>';
                    html += '<td><input type="radio" name="selectedOnu" value="' + idx + '" onclick="selectOnu(\'' + onu.serial + '\', \'' + onu.port + '\')"></td>';
                    html += '<td><code>' + onu.serial + '</code></td>';
                    html += '<td>' + onu.port + '</td>';
                    html += '<td><span class="badge badge-info">' + (onu.vendor || 'unknown') + '</span></td>';
                    html += '<td><button type="button" class="btn btn-success btn-sm" onclick="selectOnu(\'' + onu.serial + '\', \'' + onu.port + '\')"><i class="fas fa-arrow-right"></i></button></td>';
                    html += '</tr>';
                });
                $('#discoveredList').html(html);
            } else {
                $('#discoveredList').html('<tr><td colspan="5" class="text-center py-4 text-muted">No unregistered ONUs found</td></tr>');
            }
        },
        error: function() {
            btn.prop('disabled', false).html('<i class="fas fa-sync-alt mr-1"></i>Scan Now');
            $('#discoveryLoading').addClass('d-none');
            alert('Error scanning for ONUs');
        }
    });
}

function selectOnu(serial, port) {
    $('#provSerial').val(serial);
    $('#provPort').val(port);
}

function submitProvision() {
    var serial = $('#provSerial').val();
    var port = $('#provPort').val();
    if (!serial || !port) { alert('Serial number and port are required'); return; }

    var btn = $('#btnProvision');
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Provisioning...');

    $.ajax({
        url: '{{ route("admin.olt.provision-onu", $olt) }}',
        type: 'POST',
        data: $('#provisionForm').serialize(),
        headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
        success: function(res) {
            btn.prop('disabled', false).html('<i class="fas fa-check-circle mr-1"></i>Provision ONU');
            if (res.success) {
                alert('ONU provisioned successfully!');
                window.location.href = '{{ route("admin.olt.onus", $olt) }}';
            } else {
                alert(res.message || 'Provisioning failed');
            }
        },
        error: function(xhr) {
            btn.prop('disabled', false).html('<i class="fas fa-check-circle mr-1"></i>Provision ONU');
            var msg = 'Error provisioning ONU';
            if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
            alert(msg);
        }
    });
}

</script>
@endpush
