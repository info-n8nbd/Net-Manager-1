@extends('layouts.client')

@section('title', 'My Profile')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('client.dashboard') }}">Home</a></li>
<li class="breadcrumb-item active">Profile</li>
@endsection

@push('styles')
<style>
    .profile-sidebar {
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        border-left: 4px solid #1a237e;
        overflow: hidden;
    }
    .profile-sidebar-header {
        padding: 20px;
        border-bottom: 1px solid #e9ecef;
    }
    .profile-sidebar-header h4 {
        margin: 0;
        font-weight: 600;
        color: #333;
        font-size: 18px;
    }
    .profile-sidebar-header p {
        margin: 5px 0 0;
        color: #6c757d;
        font-size: 13px;
    }
    .profile-info-list {
        padding: 0;
        margin: 0;
        list-style: none;
    }
    .profile-info-list li {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 20px;
        border-bottom: 1px solid #f0f0f0;
    }
    .profile-info-list li:last-child {
        border-bottom: none;
    }
    .profile-info-list .label {
        font-weight: 600;
        color: #495057;
        font-size: 13px;
    }
    .profile-info-list .value {
        color: #333;
        font-size: 13px;
        text-align: right;
    }
    .profile-info-list .value.highlight {
        color: #dc3545;
        font-weight: 600;
    }
    .status-badge {
        display: inline-block;
        padding: 3px 12px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
    }
    .status-badge.online {
        background: #28a745;
        color: #fff;
    }
    .status-badge.offline {
        background: #dc3545;
        color: #fff;
    }
    .status-badge.active {
        background: #28a745;
        color: #fff;
    }
    .status-badge.inactive {
        background: #dc3545;
        color: #fff;
    }
    .status-badge.expired {
        background: #dc3545;
        color: #fff;
    }
    .profile-sidebar-footer {
        padding: 15px 20px;
        background: #f8f9fa;
    }
    .btn-edit-profile {
        background: linear-gradient(135deg, #1a237e 0%, #3949ab 100%);
        color: #fff;
        border: none;
        padding: 10px 20px;
        border-radius: 6px;
        width: 100%;
        font-weight: 600;
    }
    .btn-edit-profile:hover {
        background: linear-gradient(135deg, #0d1466 0%, #2e3d9e 100%);
        color: #fff;
    }
    .content-card {
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        overflow: hidden;
    }
    .content-card .nav-tabs {
        background: #f8f9fa;
        border: none;
        padding: 10px 10px 0;
    }
    .content-card .nav-tabs .nav-link {
        border: none;
        color: #495057;
        font-weight: 500;
        padding: 10px 20px;
        border-radius: 6px 6px 0 0;
        margin-right: 5px;
    }
    .content-card .nav-tabs .nav-link:hover {
        background: rgba(26, 35, 126, 0.1);
        color: #1a237e;
    }
    .content-card .nav-tabs .nav-link.active {
        background: #1a237e;
        color: #fff;
    }
    .content-card .tab-content {
        padding: 0;
    }
    .data-table {
        width: 100%;
        margin: 0;
    }
    .data-table th {
        background: #1a237e;
        color: #fff;
        font-weight: 600;
        padding: 12px 15px;
        font-size: 13px;
        border: none;
    }
    .data-table td {
        padding: 12px 15px;
        border-bottom: 1px solid #e9ecef;
        font-size: 13px;
        vertical-align: middle;
    }
    .data-table tbody tr:hover {
        background: #f8f9fa;
    }
    .badge-paid {
        background: #28a745;
        color: #fff;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 11px;
    }
    .badge-unpaid {
        background: #dc3545;
        color: #fff;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 11px;
    }
    .badge-partial {
        background: #ffc107;
        color: #333;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 11px;
    }
    .address-section {
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        padding: 20px;
        margin-top: 20px;
    }
    .address-section h5 {
        color: #1a237e;
        font-weight: 600;
        margin-bottom: 10px;
        font-size: 14px;
        text-transform: uppercase;
    }
    .address-section p {
        color: #333;
        margin: 0;
        font-size: 14px;
    }
    .connection-info-card {
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        margin-top: 20px;
        overflow: hidden;
    }
    .connection-info-card .card-header {
        background: linear-gradient(135deg, #1a237e 0%, #3949ab 100%);
        color: #fff;
        padding: 12px 20px;
        font-weight: 600;
    }
    .connection-info-table {
        width: 100%;
    }
    .connection-info-table td {
        padding: 10px 15px;
        border-bottom: 1px solid #f0f0f0;
        font-size: 13px;
    }
    .connection-info-table .label-cell {
        width: 40%;
        font-weight: 600;
        color: #1a237e;
        text-transform: uppercase;
        font-size: 11px;
        background: #f8f9fa;
    }
    .connection-info-table .value-cell {
        width: 60%;
        color: #333;
    }
    .signal-good { color: #28a745; font-weight: 600; }
    .signal-warning { color: #ffc107; font-weight: 600; }
    .signal-bad { color: #dc3545; font-weight: 600; }
    .live-status {
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .live-status .pulse {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        animation: pulse 2s infinite;
    }
    .live-status .pulse.online {
        background: #28a745;
        box-shadow: 0 0 0 0 rgba(40, 167, 69, 0.7);
    }
    .live-status .pulse.offline {
        background: #dc3545;
        box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.7);
    }
    @keyframes pulse {
        0% { box-shadow: 0 0 0 0 rgba(40, 167, 69, 0.7); }
        70% { box-shadow: 0 0 0 8px rgba(40, 167, 69, 0); }
        100% { box-shadow: 0 0 0 0 rgba(40, 167, 69, 0); }
    }
    @media (max-width: 768px) {
        .profile-sidebar { margin-bottom: 20px; }
        .content-card .nav-tabs .nav-link { padding: 8px 12px; font-size: 12px; }
        .data-table th, .data-table td { padding: 8px 10px; font-size: 12px; }
    }
</style>
@endpush

@section('content')
<div class="row">
    <div class="col-lg-6 col-md-7 mx-auto">
        <div class="profile-sidebar">
            <div class="profile-sidebar-header">
                <h4>{{ $client->name }}</h4>
                <p>{{ $client->client_id }}</p>
            </div>
            <ul class="profile-info-list">
                <li>
                    <span class="label">Status</span>
                    <span class="value">
                        <span class="live-status" id="live-status">
                            <span class="pulse {{ $client->status === 'active' ? 'online' : 'offline' }}"></span>
                            <span class="status-badge {{ $client->status === 'active' ? 'online' : 'offline' }}" id="status-text">
                                {{ $client->status === 'active' ? 'ONLINE' : strtoupper($client->status) }}
                            </span>
                        </span>
                    </span>
                </li>
                <li>
                    <span class="label">Father's Name</span>
                    <span class="value">{{ $client->father_husband_name ?? 'N/A' }}</span>
                </li>
                <li>
                    <span class="label">Mother's Name</span>
                    <span class="value">{{ $client->mother_name ?? 'N/A' }}</span>
                </li>
                <li>
                    <span class="label">Date of Birth</span>
                    <span class="value">{{ $client->date_of_birth ? $client->date_of_birth->format('d-m-Y') : 'N/A' }}</span>
                </li>
                <li>
                    <span class="label">Gender</span>
                    <span class="value">{{ $client->gender ? ucfirst($client->gender) : 'N/A' }}</span>
                </li>
                <li>
                    <span class="label">Phone</span>
                    <span class="value">{{ $client->phone }}</span>
                </li>
                <li>
                    <span class="label">Email</span>
                    <span class="value">{{ $client->email ?? 'N/A' }}</span>
                </li>
                <li>
                    <span class="label">Zone</span>
                    <span class="value">{{ $client->zone->name ?? 'N/A' }}</span>
                </li>
                <li>
                    <span class="label">Package</span>
                    <span class="value">{{ $client->package->name ?? 'N/A' }}</span>
                </li>
                <li>
                    <span class="label">Monthly Bill</span>
                    <span class="value">৳{{ number_format($client->package->price ?? 0, 2) }}</span>
                </li>
                <li>
                    <span class="label">Due Amount</span>
                    <span class="value {{ ($client->due_amount ?? 0) > 0 ? 'highlight' : '' }}">৳{{ number_format($client->due_amount ?? 0, 2) }}</span>
                </li>
                <li>
                    <span class="label">Username</span>
                    <span class="value">{{ $client->pppoe_username ?? $client->client_id }}</span>
                </li>
                <li>
                    <span class="label">Joining Date</span>
                    <span class="value">{{ $client->connection_date?->format('d-m-Y') ?? 'N/A' }}</span>
                </li>
            </ul>
            <div class="profile-sidebar-footer">
                <a href="#" class="btn btn-edit-profile" data-toggle="modal" data-target="#editProfileModal">
                    <i class="fas fa-edit mr-2"></i>Edit Profile
                </a>
            </div>
        </div>
        
        <div class="address-section">
            <h5><i class="fas fa-map-marker-alt mr-2"></i>Address</h5>
            <p>{{ $client->address ?? 'No address provided' }}</p>
        </div>

        <div class="connection-info-card">
            <div class="card-header">
                <i class="fas fa-network-wired mr-2"></i>Connection Details
                <span class="float-right" style="font-size:11px;opacity:0.8;"><i class="fas fa-sync-alt mr-1"></i>Live</span>
            </div>
            <table class="connection-info-table">
                <tr>
                    <td class="label-cell">IP Address</td>
                    <td class="value-cell" id="ip-address">Dynamic</td>
                </tr>
                <tr>
                    <td class="label-cell">Uptime</td>
                    <td class="value-cell" id="uptime">N/A</td>
                </tr>
                <tr>
                    <td class="label-cell">Download</td>
                    <td class="value-cell" id="download-speed">&#8212;</td>
                </tr>
                <tr>
                    <td class="label-cell">Upload</td>
                    <td class="value-cell" id="upload-speed">&#8212;</td>
                </tr>
                <tr>
                    <td class="label-cell">Last Logout</td>
                    <td class="value-cell" id="last-logout">N/A</td>
                </tr>
                <tr>
                    <td class="label-cell">ONU Signal</td>
                    <td class="value-cell" id="onu-signal">Loading...</td>
                </tr>
            </table>
        </div>

    </div>
</div>

<div class="modal fade" id="editProfileModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-edit mr-2"></i>Edit Profile</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <form action="{{ route('client.profile.update') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label>Phone Number</label>
                        <input type="text" name="phone" class="form-control" value="{{ $client->phone }}">
                    </div>
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="email" class="form-control" value="{{ $client->email }}">
                    </div>
                    <div class="form-group">
                        <label>Address</label>
                        <textarea name="address" class="form-control" rows="3">{{ $client->address }}</textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function refreshLiveStatus() {
    $.ajax({
        url: '{{ route("client.live-status") }}',
        method: 'GET',
        success: function(data) {
            if (data.online) {
                $('#status-text').removeClass('offline inactive').addClass('online').text('ONLINE');
                $('.pulse').removeClass('offline').addClass('online');
            } else {
                $('#status-text').removeClass('online inactive').addClass('offline').text('OFFLINE');
                $('.pulse').removeClass('online').addClass('offline');
            }
            if (data.ip_address) $('#ip-address').text(data.ip_address);
            if (data.uptime) $('#uptime').text(data.uptime);
            if (data.download_speed) $('#download-speed').text(data.download_speed + ' kbps');
            if (data.upload_speed) $('#upload-speed').text(data.upload_speed + ' kbps');
            if (data.last_logout) $('#last-logout').text(data.last_logout);
            if (data.onu_signal) {
                var signal = parseFloat(data.onu_signal);
                var signalClass = 'signal-good';
                if (signal < -25) signalClass = 'signal-bad';
                else if (signal < -20) signalClass = 'signal-warning';
                $('#onu-signal').html('<span class="' + signalClass + '">' + signal + ' dBm</span>');
            }
        }
    });
}
$(document).ready(function() {
    refreshLiveStatus();
    setInterval(refreshLiveStatus, 1000);
});
</script>
@endpush
