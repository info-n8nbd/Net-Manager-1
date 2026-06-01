@extends('layouts.admin')

@section('title', 'RADIUS Settings')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
<li class="breadcrumb-item">Network</li>
<li class="breadcrumb-item active">RADIUS Settings</li>
@endsection

@section('content')
<form action="{{ route('admin.network.radius-settings.update') }}" method="POST">
    @csrf

    <div class="row">
        <div class="col-md-12 mb-3">
            <div class="card card-outline card-primary">
                <div class="card-header d-flex align-items-center">
                    <h3 class="card-title"><i class="fas fa-shield-alt mr-2"></i>RADIUS Integration</h3>
                    <div class="ml-auto">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="enabled" name="enabled" value="1" {{ !empty($settings->enabled) ? 'checked' : '' }}>
                            <label class="custom-control-label" for="enabled">Enable RADIUS</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card card-outline card-info">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-server mr-2"></i>Server Settings</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="server_host">Server Host</label>
                        <input type="text" class="form-control" id="server_host" name="server_host" value="{{ $settings->server_host ?? '127.0.0.1' }}" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="auth_port">Auth Port</label>
                                <input type="number" class="form-control" id="auth_port" name="auth_port" value="{{ $settings->auth_port ?? 1812 }}" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="acct_port">Accounting Port</label>
                                <input type="number" class="form-control" id="acct_port" name="acct_port" value="{{ $settings->acct_port ?? 1813 }}" required>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="secret">Shared Secret</label>
                        <input type="password" class="form-control" id="secret" name="secret" placeholder="Leave blank to keep current">
                    </div>
                    <div class="form-group">
                        <label for="nas_identifier">NAS Identifier</label>
                        <input type="text" class="form-control" id="nas_identifier" name="nas_identifier" value="{{ $settings->nas_identifier ?? '' }}" placeholder="e.g., ISP-NAS-01">
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card card-outline card-warning">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-database mr-2"></i>Database Settings</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="db_host">Database Host</label>
                        <input type="text" class="form-control" id="db_host" name="db_host" value="{{ $settings->db_host ?? '127.0.0.1' }}" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="db_port">Database Port</label>
                                <input type="number" class="form-control" id="db_port" name="db_port" value="{{ $settings->db_port ?? 3306 }}" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="db_name">Database Name</label>
                                <input type="text" class="form-control" id="db_name" name="db_name" value="{{ $settings->db_name ?? 'radius' }}" required>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="db_user">Database User</label>
                        <input type="text" class="form-control" id="db_user" name="db_user" value="{{ $settings->db_user ?? 'radius' }}" required>
                    </div>
                    <div class="form-group">
                        <label for="db_password">Database Password</label>
                        <input type="password" class="form-control" id="db_password" name="db_password" placeholder="Leave blank to keep current">
                    </div>
                    <button type="button" class="btn btn-outline-info btn-block" id="btnTestConnection">
                        <i class="fas fa-plug mr-1"></i>Test Connection
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card card-outline card-success">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-sync mr-2"></i>Sync Options</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="default_auth_type">Default Authentication Type</label>
                        <select class="form-control" id="default_auth_type" name="default_auth_type" required>
                            <option value="PAP" {{ ($settings->default_auth_type ?? '') == 'PAP' ? 'selected' : '' }}>PAP</option>
                            <option value="CHAP" {{ ($settings->default_auth_type ?? '') == 'CHAP' ? 'selected' : '' }}>CHAP</option>
                            <option value="MS-CHAPv2" {{ ($settings->default_auth_type ?? '') == 'MS-CHAPv2' ? 'selected' : '' }}>MS-CHAPv2</option>
                        </select>
                    </div>
                    <div class="custom-control custom-switch mb-3">
                        <input type="checkbox" class="custom-control-input" id="auto_sync_clients" name="auto_sync_clients" value="1" {{ !empty($settings->auto_sync_clients) ? 'checked' : '' }}>
                        <label class="custom-control-label" for="auto_sync_clients">Auto Sync Clients to RADIUS</label>
                    </div>
                    <div class="custom-control custom-switch mb-3">
                        <input type="checkbox" class="custom-control-input" id="auto_sync_packages" name="auto_sync_packages" value="1" {{ !empty($settings->auto_sync_packages) ? 'checked' : '' }}>
                        <label class="custom-control-label" for="auto_sync_packages">Auto Sync Packages to RADIUS Groups</label>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card card-outline card-danger">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-cloud-upload-alt mr-2"></i>Manual Sync</h3>
                </div>
                <div class="card-body">
                    <p class="text-muted">Manually sync all active clients and packages to the RADIUS server.</p>
                    <button type="button" class="btn btn-info btn-block mb-2" id="btnSyncClients">
                        <i class="fas fa-users mr-1"></i>Sync All Clients
                    </button>
                    <button type="button" class="btn btn-success btn-block" id="btnSyncPackages">
                        <i class="fas fa-box mr-1"></i>Sync All Packages
                    </button>
                    <div id="syncResult" class="mt-3" style="display:none;">
                        <div class="alert" id="syncAlert"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <button type="submit" class="btn btn-primary btn-lg">
                <i class="fas fa-save mr-1"></i>Save Settings
            </button>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
$(function() {
    var csrfToken = $('meta[name="csrf-token"]').attr('content');

    $('#btnTestConnection').click(function() {
        var btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Testing...');
        $.ajax({
            url: '{{ route("admin.network.radius-settings.test") }}',
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    btn.removeClass('btn-outline-info btn-outline-danger').addClass('btn-outline-success').html('<i class="fas fa-check mr-1"></i>Connection OK');
                } else {
                    btn.removeClass('btn-outline-info btn-outline-success').addClass('btn-outline-danger').html('<i class="fas fa-times mr-1"></i>' + res.message);
                }
            },
            error: function() {
                btn.removeClass('btn-outline-info btn-outline-success').addClass('btn-outline-danger').html('<i class="fas fa-times mr-1"></i>Connection Failed');
            },
            complete: function() {
                btn.prop('disabled', false);
                setTimeout(function() {
                    btn.removeClass('btn-outline-success btn-outline-danger').addClass('btn-outline-info').html('<i class="fas fa-plug mr-1"></i>Test Connection');
                }, 5000);
            }
        });
    });

    function doSync(url, btnSelector) {
        var btn = $(btnSelector);
        btn.prop('disabled', true).prepend('<i class="fas fa-spinner fa-spin mr-1"></i>');
        $.ajax({
            url: url,
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            dataType: 'json',
            success: function(res) {
                $('#syncResult').show();
                var alertDiv = $('#syncAlert');
                alertDiv.removeClass('alert-success alert-danger');
                if (res.success) {
                    alertDiv.addClass('alert-success').html('<i class="fas fa-check-circle mr-1"></i>' + res.message);
                } else {
                    alertDiv.addClass('alert-danger').html('<i class="fas fa-exclamation-circle mr-1"></i>' + res.message);
                }
            },
            error: function() {
                $('#syncResult').show();
                $('#syncAlert').removeClass('alert-success').addClass('alert-danger').html('<i class="fas fa-exclamation-circle mr-1"></i>Sync request failed.');
            },
            complete: function() {
                btn.prop('disabled', false).find('.fa-spinner').remove();
            }
        });
    }

    $('#btnSyncClients').click(function() {
        doSync('{{ route("admin.network.radius-settings.sync-clients") }}', '#btnSyncClients');
    });

    $('#btnSyncPackages').click(function() {
        doSync('{{ route("admin.network.radius-settings.sync-packages") }}', '#btnSyncPackages');
    });
});
</script>
@endpush
