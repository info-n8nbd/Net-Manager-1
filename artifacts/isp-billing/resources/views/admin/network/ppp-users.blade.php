@extends('layouts.admin')

@section('title', 'PPPoE All Clients - ' . $device->name)
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
<li class="breadcrumb-item"><a href="{{ route('admin.network.devices') }}">MikroTik Devices</a></li>
<li class="breadcrumb-item"><a href="{{ route('admin.network.devices.detail', $device) }}">{{ $device->name }}</a></li>
<li class="breadcrumb-item active">PPPoE All Clients</li>
@endsection

@section('content')
@php
    $totalUsers = count($secrets);
    $activeUsersCount = collect($secrets)->filter(fn($s) => ($s['disabled'] ?? 'no') !== 'yes')->count();
    $disabledUsers = $totalUsers - $activeUsersCount;
    $routerUsernames = collect($secrets)->pluck('name')->toArray();
    $syncedUsers = collect($secrets)->filter(fn($s) => in_array($s['name'], $billingUsers))->count();
    $onlyOnRouter = collect($secrets)->filter(fn($s) => !in_array($s['name'], $billingUsers))->count();
    $onlyInBilling = collect($billingUsers)->filter(fn($u) => !in_array($u, $routerUsernames))->count();
    $onlineCount = collect($secrets)->filter(fn($s) => isset($s['active_session']))->count();
@endphp

<style>
.ppp-table th { font-size: 12px; padding: 8px 6px; white-space: nowrap; }
.ppp-table td { font-size: 12px; padding: 6px; vertical-align: middle; }
.ppp-table .user-id { color: #dc3545; font-weight: 600; cursor: pointer; }
.ppp-table .user-id:hover { text-decoration: underline; }
.ppp-table .user-pass { color: #666; font-size: 11px; }
.ppp-table .user-cell { color: #666; font-size: 11px; }
.ppp-table .pkg-name { color: #28a745; font-weight: 500; font-size: 11px; }
.ppp-table .mismatch-text { color: #dc3545; font-size: 11px; }
.ppp-table .not-found-text { color: #dc3545; font-size: 11px; }
.ppp-stats-badge { display: inline-block; padding: 4px 10px; border: 1px solid #dee2e6; border-radius: 4px; margin-right: 8px; font-size: 13px; background: #f8f9fa; }
.ppp-stats-badge strong { color: #dc3545; }
.ppp-header-actions .btn { margin-left: 4px; }
.ppp-table .mac-col { font-size: 11px; color: #e74c3c; }
.ppp-table .ip-col { font-size: 11px; }
</style>

<div class="row mb-3">
    <div class="col-12">
        <div class="card card-outline card-primary mb-0">
            <div class="card-header py-2">
                <div class="d-flex flex-wrap align-items-center justify-content-between">
                    <h3 class="card-title mb-0">
                        <i class="fas fa-wifi mr-2"></i>PPPoE All Clients
                    </h3>
                    <div class="ppp-header-actions d-flex flex-wrap align-items-center">
                        <button type="button" class="btn btn-outline-primary btn-sm" id="btn-sync-ppp" onclick="syncWithBilling()">
                            <i class="fas fa-sync-alt mr-1"></i>Sync
                        </button>
                        <a href="{{ route('admin.network.devices.detail', $device) }}" class="btn btn-info btn-sm">
                            <i class="fas fa-server mr-1"></i>Router Detail
                        </a>
                        <a href="{{ route('admin.network.devices') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left mr-1"></i>Back
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body py-2">
                <div class="d-flex flex-wrap align-items-center mb-2">
                    <span class="ppp-stats-badge">Mikrotik: <strong>{{ $totalUsers }}</strong></span>
                    <span class="ppp-stats-badge">Application: <strong>{{ $billingCount }}</strong></span>

                    <span class="mx-2">
                        [ Add Own Client: <a href="{{ route('admin.clients.create', ['mikrotik_device_id' => $device->id, 'connection_type' => 'pppoe']) }}" class="btn btn-warning btn-xs px-2 py-0" style="font-size:14px; font-weight:bold; line-height:20px;">+</a> ]
                    </span>
                    <span class="mx-2">
                        [ Add Reseller Client: <a href="{{ route('admin.clients.create', ['mikrotik_device_id' => $device->id, 'connection_type' => 'pppoe', 'type' => 'reseller']) }}" class="btn btn-primary btn-xs px-2 py-0" style="font-size:14px; font-weight:bold; line-height:20px;">+</a> ]
                    </span>
                    <span class="mx-2">
                        [ Sync Package: <button type="button" class="btn btn-warning btn-xs px-2 py-0" style="font-size:14px; font-weight:bold; line-height:20px;" onclick="syncPackages()"><i class="fas fa-sync-alt"></i></button> ]
                    </span>
                </div>

                <div class="d-flex flex-wrap align-items-center">
                    <span class="ppp-stats-badge">Active: <strong class="text-success">{{ $activeUsersCount }}</strong></span>
                    <span class="ppp-stats-badge">Disabled: <strong>{{ $disabledUsers }}</strong></span>
                    <span class="ppp-stats-badge">Online: <strong class="text-primary">{{ $onlineCount }}</strong></span>
                    <span class="ppp-stats-badge">Synced: <strong class="text-success">{{ $syncedUsers }}</strong></span>
                    <span class="ppp-stats-badge">Only Router: <strong>{{ $onlyOnRouter }}</strong></span>
                    <span class="ppp-stats-badge">Only Billing: <strong>{{ $onlyInBilling }}</strong></span>

                    <div class="ml-auto mt-1 mt-md-0">
                        <div class="input-group input-group-sm" style="width: 220px;">
                            <input type="text" id="ppp-search" class="form-control" placeholder="Search...">
                            <div class="input-group-append">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="sync-results" class="mt-2" style="display:none;">
                    <div class="alert alert-sm mb-0 py-1 px-2" id="sync-alert">
                        <small id="sync-message"></small>
                    </div>
                </div>
                <div id="pkg-sync-results" class="mt-2" style="display:none;">
                    <div class="alert alert-sm mb-0 py-1 px-2" id="pkg-sync-alert">
                        <small id="pkg-sync-message"></small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover mb-0 ppp-table" id="ppp-users-table">
                        <thead>
                            <tr class="bg-light">
                                <th>ID/Pass/Name/Cell</th>
                                <th>Zone/Address</th>
                                <th>Package</th>
                                <th>Deadline</th>
                                <th>Mac</th>
                                <th>Network IP/App IP</th>
                                <th>Last_Loged_Out</th>
                                <th>MisMatch</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($secrets as $index => $secret)
                            @php
                                $username = $secret['name'] ?? 'N/A';
                                $password = $secret['password'] ?? '';
                                $profile = $secret['profile'] ?? 'default';
                                $comment = $secret['comment'] ?? '';
                                $disabled = ($secret['disabled'] ?? 'no') === 'yes';
                                $lastLoggedOut = $secret['last-logged-out'] ?? '';
                                $callerIdFromSecret = $secret['caller-id'] ?? '';
                                $hasClient = isset($secret['billing_client']) && is_object($secret['billing_client']);
                                $client = $hasClient ? $secret['billing_client'] : null;
                                $hasActiveSession = isset($secret['active_session']);
                                $session = $hasActiveSession ? $secret['active_session'] : null;

                                $clientName = $client ? $client->name : '';
                                $clientPhone = $client ? $client->phone : '';
                                $clientZone = ($client && $client->zone) ? $client->zone->name : '';
                                $clientAddress = $client ? $client->address : '';
                                $clientPackage = ($client && $client->package) ? $client->package->name : '';
                                $clientExpire = $client ? ($client->expire_date ? \Carbon\Carbon::parse($client->expire_date)->format('Y-m-d') : '') : '';
                                $clientStatus = $client ? $client->status : '';

                                $sessionAddress = $session['address'] ?? '';
                                $sessionCallerId = $session['caller-id'] ?? $callerIdFromSecret;
                                $sessionUptime = $session['uptime'] ?? '';
                                $appIp = $client ? ($client->ip_address ?? '') : '';
                                $remoteAddress = $secret['remote-address'] ?? '';

                                $inBilling = in_array($username, $billingUsers);
                            @endphp
                            <tr class="{{ $disabled ? 'table-secondary' : '' }}">
                                <td>
                                    <span class="user-id">{{ $username }}</span><br>
                                    <span class="user-pass">{{ $password }}</span>
                                    @if($clientName)
                                        <br><span class="text-dark" style="font-size:11px;">{{ $clientName }}</span>
                                    @endif
                                    @if($clientPhone)
                                        <br><span class="user-cell">{{ $clientPhone }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if($clientZone)
                                        <span class="text-primary" style="font-size:11px;">{{ $clientZone }}</span>
                                    @endif
                                    @if($clientAddress)
                                        <br><span style="font-size:11px;">{{ Str::limit($clientAddress, 40) }}</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="pkg-name">{{ $profile }}</span>
                                    @if($clientPackage && $clientPackage !== $profile)
                                        <br><span style="font-size:10px; color:#6c757d;">App: {{ $clientPackage }}</span>
                                    @endif
                                </td>
                                <td style="font-size:11px;">
                                    @if($clientExpire)
                                        {{ $clientExpire }}
                                    @endif
                                </td>
                                <td class="mac-col">
                                    @if($sessionCallerId)
                                        {{ $sessionCallerId }}
                                    @endif
                                </td>
                                <td class="ip-col">
                                    @if($sessionAddress)
                                        <span class="text-primary">{{ $sessionAddress }}</span>
                                    @elseif($remoteAddress)
                                        <span class="text-muted">{{ $remoteAddress }}</span>
                                    @endif
                                    @if($appIp)
                                        <br><small class="text-info">App: {{ $appIp }}</small>
                                    @endif
                                    @if($hasActiveSession && isset($session['uptime']))
                                        <br><small class="text-muted">Up: {{ $session['uptime'] }}</small>
                                    @endif
                                </td>
                                <td style="font-size:11px;">
                                    @if($lastLoggedOut)
                                        {{ $lastLoggedOut }}
                                    @endif
                                </td>
                                <td>
                                    @if(!$inBilling)
                                        <span class="mismatch-text">Not found in application database</span>
                                    @elseif($clientPackage && $clientPackage !== $profile)
                                        <span class="mismatch-text">Package mismatch: {{ $profile }} vs {{ $clientPackage }}</span>
                                    @else
                                        <span class="text-success" style="font-size:11px;"><i class="fas fa-check"></i> Matched</span>
                                    @endif
                                </td>
                                <td>
                                    @if($inBilling && $client)
                                        @if($clientStatus === 'active')
                                            <span class="badge badge-success">Active</span>
                                        @elseif($clientStatus === 'inactive')
                                            <span class="badge badge-danger">Inactive</span>
                                        @else
                                            <span class="badge badge-secondary">{{ ucfirst($clientStatus) }}</span>
                                        @endif
                                    @endif
                                    @if($disabled)
                                        <span class="badge badge-dark">Disabled</span>
                                    @endif
                                </td>
                                <td>
                                    @if($inBilling && $client)
                                        <a href="{{ route('admin.clients.show', $client) }}" class="text-primary" style="font-size:11px;">
                                            <i class="fas fa-eye mr-1"></i>View
                                        </a>
                                    @else
                                        <button type="button" class="btn btn-xs btn-outline-success btn-import-ppp"
                                            data-username="{{ e($username) }}"
                                            data-profile="{{ e($profile) }}"
                                            data-password="{{ e($password) }}"
                                            data-status="{{ $disabled ? 'inactive' : 'active' }}"
                                            style="font-size:11px;">
                                            <i class="fas fa-plus mr-1"></i>Import
                                        </button>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="10" class="text-center py-4">
                                    <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">No PPP secrets found on this router.</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer py-1 text-muted" style="font-size:12px;">
                Showing <span id="visible-count">{{ $totalUsers }}</span> of {{ $totalUsers }} users from <strong>{{ $device->name }}</strong> ({{ $device->host }})
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="importModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <form method="POST" action="{{ route('admin.network.devices.import-ppp-user', $device) }}">
            @csrf
            <div class="modal-content">
                <div class="modal-header bg-success">
                    <h5 class="modal-title text-white"><i class="fas fa-plus-circle mr-2"></i>Import MikroTik User to Billing</h5>
                    <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-1"></i>
                        Importing user <strong id="import-username-display"></strong> from router <strong>{{ $device->name }}</strong>.
                    </div>

                    @if($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>PPPoE Username <span class="text-danger">*</span></label>
                                <input type="text" name="username" id="import-username" class="form-control" readonly>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>PPPoE Password</label>
                                <input type="text" name="pppoe_password" id="import-password" class="form-control" placeholder="From router">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Client Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="import-name" class="form-control" placeholder="First name" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Last Name</label>
                                <input type="text" name="last_name" id="import-last-name" class="form-control" placeholder="Last name">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Phone Number <span class="text-danger">*</span></label>
                                <input type="text" name="phone" class="form-control" placeholder="01XXXXXXXXX" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Address</label>
                                <input type="text" name="address" class="form-control" placeholder="Client address">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Zone <span class="text-danger">*</span></label>
                                <select name="zone_id" id="import-zone" class="form-control" required onchange="updateAreas(this.value)">
                                    <option value="">Select Zone</option>
                                    @foreach($zones as $zone)
                                    <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Area</label>
                                <select name="area_id" id="import-area" class="form-control">
                                    <option value="">Select Area</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>TJ/Box</label>
                                <select name="box_id" class="form-control">
                                    <option value="">Select Box</option>
                                    @foreach($boxes as $box)
                                    <option value="{{ $box->id }}">{{ $box->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Package <span class="text-danger">*</span></label>
                                <select name="package_id" id="import-package" class="form-control" required onchange="updateMonthlyFee(this)">
                                    <option value="">Select Package</option>
                                    @foreach($packages as $pkg)
                                    <option value="{{ $pkg->id }}" data-price="{{ $pkg->price }}" data-name="{{ $pkg->name }}">{{ $pkg->name }} - {{ number_format($pkg->price) }} BDT</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Monthly Bill</label>
                                <input type="number" name="monthly_fee" id="import-monthly-fee" class="form-control" step="0.01" min="0">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Status <span class="text-danger">*</span></label>
                                <select name="status" id="import-status" class="form-control" required>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <hr class="my-2">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group mb-0">
                                <label><i class="fas fa-tools mr-1 text-warning"></i>Installation Charge</label>
                                <select name="install_charge_status" id="import-install-status" class="form-control" onchange="toggleImportInstallAmount(this.value)">
                                    <option value="free">Free (No Charge)</option>
                                    <option value="due">Due (Not Paid Yet)</option>
                                    <option value="paid">Paid (Already Collected)</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4" id="import-install-amount-wrap" style="display:none;">
                            <div class="form-group mb-0">
                                <label>Charge Amount (৳) <span class="text-danger">*</span></label>
                                <input type="number" name="install_charge_amount" id="import-install-amount" class="form-control" min="0" step="1" placeholder="e.g. 1000">
                            </div>
                        </div>
                        <div class="col-md-4" id="import-install-paid-wrap" style="display:none;">
                            <div class="form-group mb-0">
                                <label>Payment Method</label>
                                <select name="install_payment_method" id="import-payment-method" class="form-control" onchange="toggleImportPaymentFields(this.value)">
                                    @foreach(auth()->user()->allowedPaymentMethods() as $pm)
                                    <option value="{{ strtolower($pm->name) }}">{{ $pm->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row mt-2" id="import-payment-extra-fields" style="display:none;">
                        <div class="col-md-6">
                            <div class="form-group mb-0">
                                <label>Account Number <small class="text-muted">(e.g. bKash/Nagad number)</small></label>
                                <input type="text" name="install_account_number" id="import-account-number"
                                    class="form-control" placeholder="e.g. 017XXXXXXXX">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-0">
                                <label>Transaction Number <small class="text-muted">(TxID / Reference)</small></label>
                                <input type="text" name="install_transaction_number" id="import-transaction-number"
                                    class="form-control" placeholder="e.g. TRX123456789">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times mr-1"></i>Cancel</button>
                    <button type="submit" class="btn btn-success"><i class="fas fa-plus-circle mr-1"></i>Import to Billing</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
var zonesData = @json($zones);

$(document).ready(function() {
    $('#ppp-search').on('keyup', function() {
        var value = $(this).val().toLowerCase();
        var visibleCount = 0;
        $('#ppp-users-table tbody tr').each(function() {
            var row = $(this);
            var text = row.text().toLowerCase();
            if (text.indexOf(value) > -1) {
                row.show();
                visibleCount++;
            } else {
                row.hide();
            }
        });
        $('#visible-count').text(visibleCount);
    });

    $(document).on('click', '.btn-import-ppp', function() {
        var btn = $(this);
        var username = btn.data('username');
        var profile = btn.data('profile');
        var password = btn.data('password');
        var status = btn.data('status');

        $('#import-username').val(username);
        $('#import-username-display').text(username);
        $('#import-password').val(password);
        $('#import-name').val('');
        $('#import-status').val(status);
        $('#import-install-status').val('free');
        toggleImportInstallAmount('free');

        var profileLower = String(profile).toLowerCase();
        var packageSelect = $('#import-package');
        packageSelect.val('');
        packageSelect.find('option').each(function() {
            if ($(this).data('name') && String($(this).data('name')).toLowerCase() === profileLower) {
                packageSelect.val($(this).val());
                $('#import-monthly-fee').val($(this).data('price'));
            }
        });

        $('#importModal').modal('show');
    });

    @if($errors->any())
    $('#importModal').modal('show');
    @endif
});

function toggleImportPaymentFields(val) {
    if (!val || val === 'cash') {
        $('#import-payment-extra-fields').hide();
        $('#import-account-number, #import-transaction-number').val('');
    } else {
        $('#import-payment-extra-fields').show();
    }
}
function toggleImportInstallAmount(val) {
    if (val === 'free') {
        $('#import-install-amount-wrap').hide();
        $('#import-install-paid-wrap').hide();
        $('#import-payment-extra-fields').hide();
        $('#import-account-number, #import-transaction-number').val('');
        $('#import-install-amount').prop('required', false);
    } else if (val === 'due') {
        $('#import-install-amount-wrap').show();
        $('#import-install-paid-wrap').hide();
        $('#import-payment-extra-fields').hide();
        $('#import-account-number, #import-transaction-number').val('');
        $('#import-install-amount').prop('required', true);
    } else if (val === 'paid') {
        $('#import-install-amount-wrap').show();
        $('#import-install-paid-wrap').show();
        $('#import-install-amount').prop('required', true);
        // Re-check payment method to show/hide extra fields
        var pm = $('#import-payment-method').val();
        toggleImportPaymentFields(pm || 'cash');
    }
}

function updateAreas(zoneId) {
    var areaSelect = $('#import-area');
    areaSelect.html('<option value="">Select Area</option>');
    if (!zoneId) return;
    var zone = zonesData.find(function(z) { return z.id == zoneId; });
    if (zone && zone.areas) {
        zone.areas.forEach(function(area) {
            areaSelect.append('<option value="' + area.id + '">' + area.name + '</option>');
        });
    }
}

function updateMonthlyFee(select) {
    var option = $(select).find(':selected');
    if (option.data('price')) {
        $('#import-monthly-fee').val(option.data('price'));
    }
}

function syncPackages() {
    var btn = (window.event && window.event.target) ? window.event.target.closest('button') : document.querySelector('[onclick*="syncPackages"]');
    var origHtml = btn ? btn.innerHTML : '';
    smartConfirm({message:'Sync all billing packages as PPP profiles to this router?',confirmText:'Sync'}, function() {
    if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>'; }

    $.ajax({
        url: '{{ route("admin.network.devices.sync-packages", $device) }}',
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        success: function(response) {
            btn.disabled = false;
            btn.innerHTML = origHtml;
            var alertClass = response.success ? 'alert-success' : 'alert-danger';
            $('#pkg-sync-alert').removeClass('alert-success alert-danger').addClass(alertClass);
            $('#pkg-sync-message').html('<strong>' + (response.success ? 'Done!' : 'Failed!') + '</strong> ' + (response.message || ''));
            $('#pkg-sync-results').slideDown();
            setTimeout(function() { $('#pkg-sync-results').slideUp(); }, 8000);
        },
        error: function(xhr) {
            btn.disabled = false;
            btn.innerHTML = origHtml;
            $('#pkg-sync-alert').removeClass('alert-success alert-danger').addClass('alert-danger');
            var msg = 'An error occurred.';
            if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
            $('#pkg-sync-message').html('<strong>Error!</strong> ' + msg);
            $('#pkg-sync-results').slideDown();
        }
    });
    }); // end smartConfirm
}

function syncWithBilling() {
    var btn = $('#btn-sync-ppp');
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Syncing...');

    $.ajax({
        url: '{{ route("admin.network.devices.sync-ppp", $device) }}',
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        success: function(response) {
            btn.prop('disabled', false).html('<i class="fas fa-sync-alt mr-1"></i>Sync');
            var alertClass = response.success ? 'alert-success' : 'alert-danger';
            $('#sync-alert').removeClass('alert-success alert-danger alert-warning alert-info').addClass(alertClass);

            var html = '<strong>' + (response.success ? 'Sync Complete!' : 'Sync Failed!') + '</strong> ';
            if (response.message) html += response.message;
            if (response.details) {
                html += ' | Synced: ' + (response.details.synced || 0);
                html += ' | Only Router: ' + (response.details.only_on_router || 0);
                html += ' | Only Billing: ' + (response.details.only_in_billing || 0);
            }
            $('#sync-message').html(html);
            $('#sync-results').slideDown();
        },
        error: function(xhr) {
            btn.prop('disabled', false).html('<i class="fas fa-sync-alt mr-1"></i>Sync');
            $('#sync-alert').removeClass('alert-success alert-danger alert-warning alert-info').addClass('alert-danger');
            var msg = 'An error occurred while syncing.';
            if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
            $('#sync-message').html('<strong>Error!</strong> ' + msg);
            $('#sync-results').slideDown();
        }
    });
}
</script>
@endpush
