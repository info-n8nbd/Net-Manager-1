@extends('layouts.billman')
@section('title', 'Client Profile')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('billman.clients') }}">Clients</a></li>
<li class="breadcrumb-item active">{{ $client->name }}</li>
@endsection

@push('styles')
<style>
.content-wrapper .card-body table td,
.content-wrapper .card-body table th {
    font-size: 14px !important;
    padding: 6px 10px !important;
}
.content-wrapper .card-header h5,
.content-wrapper .card-header h6,
.content-wrapper .card-body h5,
.content-wrapper .card-body h6 {
    font-size: 16px !important;
}
.content-wrapper .card-body .btn {
    font-size: 13px !important;
}
.content-wrapper .card-body p,
.content-wrapper .card-body span,
.content-wrapper .card-body label,
.content-wrapper .card-body strong,
.content-wrapper .card-body a,
.content-wrapper .card-body div {
    font-size: 14px;
}
.content-wrapper .card-body small {
    font-size: 12px !important;
}
.content-wrapper .card-body code {
    font-size: 13px !important;
}
.info-cube {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 12px;
    padding: 15px;
    color: #fff;
    margin-bottom: 15px;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
    transition: transform 0.2s ease;
}
.info-cube:hover { transform: translateY(-2px); }
.info-cube.success { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); box-shadow: 0 4px 15px rgba(17, 153, 142, 0.3); }
.info-cube.danger { background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%); box-shadow: 0 4px 15px rgba(235, 51, 73, 0.3); }
.info-cube.warning { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); box-shadow: 0 4px 15px rgba(240, 147, 251, 0.3); }
.info-cube.info { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); box-shadow: 0 4px 15px rgba(79, 172, 254, 0.3); }
.info-cube .cube-icon { font-size: 24px; opacity: 0.9; }
.info-cube .cube-label { font-size: 11px; text-transform: uppercase; opacity: 0.85; }
.info-cube .cube-value { font-size: 16px; font-weight: 600; margin-top: 5px; }

.bill-card { background: #fff; border-radius: 10px; padding: 12px 15px; margin-bottom: 10px; border-left: 4px solid #17a2b8; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
.bill-card.paid { border-left-color: #28a745; }
.bill-card.unpaid { border-left-color: #dc3545; }
.bill-card.partial { border-left-color: #ffc107; }
.bill-card .bill-period { font-weight: 600; color: #333; font-size: 14px; }
.bill-card .bill-date { font-size: 11px; color: #888; }
.bill-card .bill-amounts { display: flex; justify-content: space-between; margin-top: 8px; flex-wrap: wrap; gap: 8px; }
.bill-card .amount-box { text-align: center; flex: 1; min-width: 60px; }
.bill-card .amount-label { font-size: 10px; color: #888; text-transform: uppercase; }
.bill-card .amount-value { font-size: 14px; font-weight: 600; }

@media (max-width: 767px) {
    .info-cube { padding: 12px; }
    .info-cube .cube-icon { font-size: 20px; }
    .info-cube .cube-value { font-size: 14px; }
}

.content-wrapper .card-body strong,
.content-wrapper .card-body .font-weight-bold,
.content-wrapper .bg-light strong,
.content-wrapper .bg-light .font-weight-bold {
    font-weight: 400 !important;
    color: #333 !important;
}
.content-wrapper .card-body .text-primary,
.content-wrapper .card-body .text-success,
.content-wrapper .card-body .text-danger,
.content-wrapper .card-body .text-warning,
.content-wrapper .card-body .text-info,
.content-wrapper .bg-light .text-primary,
.content-wrapper .bg-light .text-success,
.content-wrapper .bg-light .text-danger,
.content-wrapper .bg-light .text-warning,
.content-wrapper .bg-light .text-info {
    color: #333 !important;
}
.content-wrapper .card-header strong,
.content-wrapper .card-header .font-weight-bold,
.content-wrapper .card-header h5,
.content-wrapper .card-header h6 {
    font-weight: 600 !important;
}
.content-wrapper .badge {
    font-weight: 400 !important;
}
</style>
@endpush

@section('content')
<div class="row">
    <div class="col-md-4">
        <div class="card card-primary card-outline">
            <div class="card-body box-profile">
                <div class="text-center mb-2">
                    @if($client->profile_picture)
                        <img src="{{ asset('storage/' . $client->profile_picture) }}" alt="Profile" class="rounded-circle" style="width: 65px; height: 65px; object-fit: cover; border: 2px solid #dee2e6;">
                    @else
                        <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-primary text-white" style="width: 55px; height: 55px; font-size: 22px;">
                            {{ strtoupper(substr($client->name, 0, 1)) }}
                        </div>
                    @endif
                </div>
                <h5 class="profile-username text-center mb-0">{{ $client->name }}</h5>
                <p class="text-muted text-center mb-1" style="font-size: 12px; font-weight: 300;">Client Code: {{ $client->client_id }}</p>
                <p class="text-center mb-2">
                    <span class="badge badge-{{ $client->status == 'active' ? 'success' : ($client->status == 'inactive' ? 'warning' : 'danger') }}" style="font-size: 13px; padding: 5px 12px;">
                        <i class="fas fa-{{ $client->status == 'active' ? 'check-circle' : 'times-circle' }} mr-1"></i>
                        {{ ucfirst($client->status) }}
                    </span>
                    <span class="badge badge-{{ $client->connection_status == 'online' ? 'info' : 'secondary' }} ml-1" style="font-size: 13px; padding: 5px 12px;">
                        <i class="fas fa-wifi mr-1"></i>
                        {{ ucfirst($client->connection_status ?? 'offline') }}
                    </span>
                    @if($client->is_free)
                    <br><span class="badge badge-info px-2 py-1 mt-1"><i class="fas fa-gift mr-1"></i>Free Client</span>
                    @endif
                </p>
                @if($client->google_map_lat && $client->google_map_lng)
                <a href="https://www.google.com/maps?q={{ $client->google_map_lat }},{{ $client->google_map_lng }}" target="_blank" class="btn btn-sm btn-outline-success btn-block mt-1">
                    <i class="fas fa-map-marker-alt mr-1"></i>View Map
                </a>
                @elseif($client->google_map_location)
                <a href="{{ $client->google_map_location }}" target="_blank" class="btn btn-sm btn-outline-success btn-block mt-1">
                    <i class="fas fa-map-marker-alt mr-1"></i>View Map
                </a>
                @endif
            </div>
        </div>

        @php
            $billing = $client->getBillingData();
            $clientTotalDue = $billing['due'];
            $remainingAdvance = $billing['advance'];
        @endphp
        <div class="card card-outline card-info">
            <div class="card-header py-2">
                <h5 class="mb-0 font-weight-bold"><i class="fas fa-money-bill-wave mr-2"></i>Billing Summary</h5>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <tr><td class="w-50"><strong>Monthly Bill</strong></td><td class="text-right text-primary font-weight-bold">৳{{ number_format($client->monthly_fee ?? 0, 2) }}</td></tr>
                    <tr class="{{ $clientTotalDue > 0 ? 'table-danger' : 'table-success' }}">
                        <td><strong>Due</strong></td>
                        <td class="text-right {{ $clientTotalDue > 0 ? 'text-danger' : 'text-success' }} font-weight-bold">৳{{ number_format($clientTotalDue ?? 0, 2) }}</td>
                    </tr>
                    @if($remainingAdvance > 0)
                    <tr class="table-success">
                        <td><strong>Advance</strong></td>
                        <td class="text-right text-success font-weight-bold">৳{{ number_format($remainingAdvance ?? 0, 2) }}</td>
                    </tr>
                    @endif
                    @if($client->is_free)
                    <tr class="table-info"><td><strong>Free Client</strong></td><td class="text-right"><span class="badge badge-info">Yes</span></td></tr>
                    <tr><td><strong>Free Reason</strong></td><td class="text-right">{{ $client->free_reason ?? 'N/A' }}</td></tr>
                    @endif
                </table>
            </div>
        </div>

        <button type="button" class="btn btn-sm btn-success btn-block mb-3" data-toggle="modal" data-target="#billSubmitModal">
            <i class="fas fa-money-bill-wave mr-1"></i>Bill Submit
            @if($clientTotalDue > 0)
            <span class="badge badge-light ml-1">৳{{ number_format($clientTotalDue ?? 0) }}</span>
            @endif
        </button>
    </div>

    <div class="col-md-8">
        <div class="card card-outline card-primary mb-3">
            <div class="card-header py-2">
                <h5 class="mb-0 font-weight-bold"><i class="fas fa-user mr-2"></i>1. Personal & Contact Information</h5>
            </div>
            <div class="card-body p-2">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-sm table-striped mb-0">
                            <tr><td class="w-40"><strong>Full Name</strong></td><td>{{ $client->name }}</td></tr>
                            <tr><td><strong>Father's Name</strong></td><td>{{ $client->father_husband_name ?? 'N/A' }}</td></tr>
                            <tr><td><strong>Mother's Name</strong></td><td>{{ $client->mother_name ?? 'N/A' }}</td></tr>
                            <tr><td><strong>Date of Birth</strong></td><td>{{ $client->date_of_birth ? $client->date_of_birth->format('d-m-Y') : 'N/A' }}</td></tr>
                            <tr><td><strong>Gender</strong></td><td>{{ $client->gender ? ucfirst($client->gender) : 'N/A' }}</td></tr>
                            <tr><td><strong>NID Number</strong></td><td>{{ $client->nid ?? 'N/A' }}</td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-sm table-striped mb-0">
                            <tr><td class="w-40"><strong>Mobile</strong></td><td><a href="tel:{{ $client->phone }}">{{ $client->phone }}</a></td></tr>
                            <tr><td><strong>Alt. Mobile</strong></td><td>{{ $client->alt_phone ?? 'N/A' }}</td></tr>
                            <tr><td><strong>Email</strong></td><td>{{ $client->email ?? 'N/A' }}</td></tr>
                            <tr><td><strong>Occupation</strong></td><td>{{ $client->occupation ?? 'N/A' }}</td></tr>
                            @if($client->custom_status)
                            <tr><td><strong>Custom Status</strong></td><td><span class="badge badge-secondary">{{ $client->custom_status }}</span></td></tr>
                            @endif
                            @if($client->assigned_to)
                            <tr><td><strong>Assigned To</strong></td><td>{{ $client->assignedTo->name ?? 'N/A' }}</td></tr>
                            @endif
                            @if($client->effective_to)
                            <tr><td><strong>Effective To</strong></td><td>{{ $client->effective_to->format('d-m-Y') }}</td></tr>
                            @endif
                            @if($client->non_effective_from)
                            <tr><td><strong>Non Effective From</strong></td><td>{{ $client->non_effective_from->format('d-m-Y') }}</td></tr>
                            @endif
                        </table>
                    </div>
                </div>
            </div>
        </div>

        @if($client->nid_front_image || $client->nid_back_image)
        <div class="card card-outline card-secondary mb-3">
            <div class="card-header py-2">
                <h5 class="mb-0 font-weight-bold"><i class="fas fa-id-card mr-2"></i>NID Documents</h5>
            </div>
            <div class="card-body p-2">
                <div class="row">
                    @if($client->nid_front_image)
                    <div class="col-6">
                        <small class="text-muted d-block mb-1">NID Front</small>
                        <a href="{{ asset('storage/' . $client->nid_front_image) }}">
                            <img src="{{ asset('storage/' . $client->nid_front_image) }}" class="img-fluid rounded" style="max-height: 120px; cursor: pointer; border: 1px solid #dee2e6;">
                        </a>
                    </div>
                    @endif
                    @if($client->nid_back_image)
                    <div class="col-6">
                        <small class="text-muted d-block mb-1">NID Back</small>
                        <a href="{{ asset('storage/' . $client->nid_back_image) }}">
                            <img src="{{ asset('storage/' . $client->nid_back_image) }}" class="img-fluid rounded" style="max-height: 120px; cursor: pointer; border: 1px solid #dee2e6;">
                        </a>
                    </div>
                    @endif
                </div>
            </div>
        </div>
        @endif

        <div class="card card-outline card-success mb-3">
            <div class="card-header py-2">
                <h5 class="mb-0 font-weight-bold"><i class="fas fa-map-marker-alt mr-2"></i>2. Address Details</h5>
            </div>
            <div class="card-body p-2">
                <table class="table table-sm table-striped mb-0">
                    <tr><td style="width: 20%"><strong>Zone</strong></td><td>{{ $client->zone->name ?? 'N/A' }}</td></tr>
                    <tr><td><strong>Full Address</strong></td><td>{{ $client->address }}</td></tr>
                    <tr><td><strong>Landmark</strong></td><td>{{ $client->nearby_landmark ?? 'N/A' }}</td></tr>
                    <tr>
                        <td><strong>Location</strong></td>
                        <td>
                            @if($client->google_map_lat && $client->google_map_lng)
                                <a href="https://www.google.com/maps?q={{ $client->google_map_lat }},{{ $client->google_map_lng }}" target="_blank" class="btn btn-info btn-sm">
                                    <i class="fas fa-map-marker-alt mr-1"></i>View Map
                                </a>
                            @elseif($client->google_map_location)
                                <a href="{{ $client->google_map_location }}" target="_blank" class="btn btn-info btn-sm">
                                    <i class="fas fa-map-marker-alt mr-1"></i>View Map
                                </a>
                            @else
                                <span class="text-muted">No location set</span>
                            @endif
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="card card-outline card-info mb-3">
            <div class="card-header py-2">
                <h5 class="mb-0 font-weight-bold"><i class="fas fa-network-wired mr-2"></i>3. Connection Details</h5>
            </div>
            <div class="card-body p-2">
                <div class="d-flex flex-wrap justify-content-around mb-2 text-center" style="gap: 6px;">
                    <div class="bg-white border rounded py-2 px-3 flex-fill">
                        <small class="text-muted d-block">Package</small>
                        <strong class="text-primary">{{ $client->package->name ?? 'N/A' }}</strong>
                    </div>
                    <div class="bg-white border rounded py-2 px-3 flex-fill">
                        <small class="text-muted d-block">Status</small>
                        <strong class="{{ $client->connection_status == 'online' ? 'text-success' : 'text-danger' }}">{{ strtoupper($client->connection_status ?? 'OFFLINE') }}</strong>
                    </div>
                    <div class="bg-white border rounded py-2 px-3 flex-fill">
                        <small class="text-muted d-block">Type</small>
                        <strong>{{ strtoupper($client->connection_type ?? 'PPPoE') }}</strong>
                    </div>
                    <div class="bg-white border rounded py-2 px-3 flex-fill">
                        <small class="text-muted d-block">Install Date</small>
                        <strong>{{ $client->connection_date ? $client->connection_date->format('d-m-Y') : 'N/A' }}</strong>
                    </div>
                    <div class="bg-white border rounded py-2 px-3 flex-fill">
                        <small class="text-muted d-block">Deadline</small>
                        @if($client->expire_date)
                            <strong>{{ $client->expire_date->format('d-m-Y') }}</strong>
                        @else
                            <strong class="text-muted">N/A</strong>
                        @endif
                    </div>
                    <div class="bg-white border rounded py-2 px-3 flex-fill">
                        <small class="text-muted d-block">Connection Age</small>
                        <strong>@if($client->connection_date){{ $client->connection_date->diffForHumans(null, true, true) }}@else N/A @endif</strong>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-sm table-striped mb-0">
                            <tr><td class="w-40"><strong>Username</strong></td><td><code>{{ $client->username }}</code></td></tr>
                            <tr><td><strong>Password</strong></td><td><code>{{ $client->pppoe_password ?? '••••••' }}</code></td></tr>
                            <tr><td><strong>MAC Address</strong></td><td><code>{{ $client->mac_address ?? 'N/A' }}</code></td></tr>
                            <tr><td><strong>IP Address</strong></td><td><code>{{ $client->ip_address ?? 'Dynamic' }}</code></td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        @if($onuRecord ?? null)
                        <div class="d-flex justify-content-end mb-1">
                            <span id="billOnuSyncSource" class="mr-2" style="font-size:11px;line-height:26px;display:none;"></span>
                            <button type="button" class="btn btn-sm btn-success" id="refreshOnuDiagBill"
                                data-onu-id="{{ $onuRecord->id ?? '' }}"
                                style="font-size:11px; padding:2px 10px; font-weight:600;">
                                <i class="fas fa-sync-alt mr-1" id="refreshOnuIconBill"></i>ONU Sync
                            </button>
                        </div>
                        @endif
                        <table class="table table-sm table-striped mb-0">
                            <tr><td class="w-40"><strong>ONU Serial</strong></td><td><code id="bill_onu_serial">{{ $client->onu_serial ?? 'N/A' }}</code></td></tr>
                            <tr><td><strong>ONU Model</strong></td><td id="bill_onu_model">{{ $client->onu_model ?? 'N/A' }}</td></tr>
                            <tr><td><strong>OLT Port</strong></td><td id="bill_olt_port">{{ $client->olt_port ?? 'N/A' }}</td></tr>
                            <tr><td><strong>RX Power</strong></td><td id="bill_rx_power">{{ $client->onu_rx_power ? $client->onu_rx_power . ' dBm' : 'N/A' }}</td></tr>
                            <tr>
                                <td><strong>Router Brand</strong></td>
                                <td>
                                    @if($client->router_brand)
                                        <span class="text-info"><i class="fas fa-wifi mr-1"></i>{{ $client->router_brand }}</span>
                                    @else
                                        <span class="text-muted">{{ $client->mac_address ? 'Unknown manufacturer' : 'No MAC address' }}</span>
                                    @endif
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="card card-outline card-primary mb-3">
            <div class="card-header py-2">
                <h5 class="mb-0 font-weight-bold"><i class="fas fa-tools mr-2"></i>4. Installation Details</h5>
            </div>
            <div class="card-body p-2">
                <div class="d-flex flex-wrap justify-content-around text-center" style="gap: 6px;">
                    <div class="bg-white border rounded py-2 px-3 flex-fill">
                        <small class="text-muted d-block">Install Charge</small>
                        <strong class="text-primary">৳{{ number_format($client->installation_charge ?? 0) }}</strong>
                    </div>
                    <div class="bg-white border rounded py-2 px-3 flex-fill">
                        <small class="text-muted d-block">Cable Length</small>
                        <strong>{{ $client->cable_length_meters ? intval($client->cable_length_meters) . ' m' : 'N/A' }}</strong>
                    </div>
                    <div class="bg-white border rounded py-2 px-3 flex-fill">
                        <small class="text-muted d-block">ONU Source</small>
                        <span class="badge badge-{{ $client->onu_required ? 'info' : 'warning' }}">{{ $client->onu_required ? 'Company ONU' : 'Client ONU' }}</span>
                    </div>
                    <div class="bg-white border rounded py-2 px-3 flex-fill">
                        <small class="text-muted d-block">Cable Type</small>
                        <span class="badge badge-{{ $client->connection_medium == 'fiber' ? 'info' : 'primary' }}">{{ $client->connection_medium == 'fiber' ? 'Optical Fiber' : 'UTP Cable' }}</span>
                    </div>
                    <div class="bg-white border rounded py-2 px-3 flex-fill">
                        <small class="text-muted d-block">Technician</small>
                        <strong>{{ $client->application && $client->application->technician ? $client->application->technician->name : 'N/A' }}</strong>
                    </div>
                </div>
                <div class="mt-2">
                    <table class="table table-sm table-bordered mb-0" style="font-size: 13px;">
                        <thead class="thead-light">
                            <tr><th colspan="4" class="text-center py-1"><i class="fas fa-project-diagram mr-1"></i>Fiber & ONU Info</th></tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="font-weight-bold" style="width:20%">Fiber Brand</td>
                                <td>{{ $client->fiber_vendor ?: '—' }}</td>
                                <td class="font-weight-bold" style="width:20%">Fiber Code</td>
                                <td>{{ $client->fiber_code ?: '—' }}</td>
                            </tr>
                            <tr>
                                <td class="font-weight-bold">Fiber Core Type</td>
                                <td>{{ $client->fiber_core_type ?: '—' }}</td>
                                <td class="font-weight-bold">Fiber Color</td>
                                @php $__fcmb=["Blue"=>"#0057B8","Orange"=>"#FF8C00","Green"=>"#28a745","Brown"=>"#8B4513","Slate"=>"#708090","Gray"=>"#808080","White"=>"#e9ecef","Red"=>"#dc3545","Black"=>"#222222","Yellow"=>"#ffc107","Violet"=>"#6f42c1","Rose"=>"#e83e8c","Aqua"=>"#20c997"]; $__fchb=$__fcmb[$client->fiber_color]??null; @endphp<td>@if($__fchb)<span style="display:inline-block;width:13px;height:13px;border-radius:3px;border:1px solid rgba(0,0,0,0.2);vertical-align:middle;margin-right:3px;"background:{$__fchb};"></span>@endif{{ $client->fiber_color ?: '—' }}</td>
                            </tr>
                            <tr>
                                <td class="font-weight-bold">Splitter Number</td>
                                <td>{{ $client->splitter_number ?: '—' }}</td>
                                <td class="font-weight-bold">Splitter Color</td>
                                @php $__scmb=["Blue"=>"#0057B8","Orange"=>"#FF8C00","Green"=>"#28a745","Brown"=>"#8B4513","Slate"=>"#708090","Gray"=>"#808080","White"=>"#e9ecef","Red"=>"#dc3545","Black"=>"#222222","Yellow"=>"#ffc107","Violet"=>"#6f42c1","Rose"=>"#e83e8c","Aqua"=>"#20c997"]; $__schb=$__scmb[$client->splitter_color]??null; @endphp<td>@if($__schb)<span style="display:inline-block;width:13px;height:13px;border-radius:3px;border:1px solid rgba(0,0,0,0.2);vertical-align:middle;margin-right:3px;"background:{$__schb};"></span>@endif{{ $client->splitter_color ?: '—' }}</td>
                            </tr>
                            <tr>
                                <td class="font-weight-bold">ONU Brand</td>
                                <td>{{ $client->onu_brand ?: '—' }}</td>
                                <td class="font-weight-bold">ONU MAC/Serial</td>
                                <td>{{ $client->onu_mac ? $client->onu_mac : '—' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                @if($client->remarks)
                <div class="mt-2 p-2 rounded" style="background: #f8f9fa; border-left: 3px solid #6c757d;">
                    <small class="text-muted d-block mb-1" style="font-size: 11px; font-weight: 600; text-transform: uppercase;">
                        <i class="fas fa-comment-alt mr-1"></i> Additional Note
                    </small>
                    <span style="font-size: 13px;">{{ $client->remarks }}</span>
                </div>
                @endif
                <div class="mt-2 p-2 rounded" style="background: #fff8e1; border-left: 3px solid #ff6f00;">
                    <small class="text-muted d-block mb-1" style="font-size: 11px; font-weight: 600; text-transform: uppercase;">
                        <i class="fas fa-sticky-note mr-1 text-warning"></i> Additional Note
                    </small>
                    <span style="font-size: 13px;">{{ $client->technician_note ?: '—' }}</span>
                </div>
                @if($client->auto_inactive_note)
                <div class="mt-2 p-2 rounded" style="background: #fff5f5; border-left: 3px solid #dc3545;">
                    <small class="text-muted d-block mb-1" style="font-size: 11px; font-weight: 600; text-transform: uppercase;">
                        <i class="fas fa-exclamation-triangle mr-1 text-danger"></i> System Note
                    </small>
                    <span style="font-size: 13px;">{{ $client->auto_inactive_note }}</span>
                </div>
                @endif
            </div>
        </div>

        <div class="card card-outline card-warning mb-3">
            <div class="card-header py-2">
                <h5 class="mb-0 font-weight-bold"><i class="fas fa-toggle-on mr-2"></i>5. Active / Inactive History</h5>
            </div>
            <div class="card-body p-2">
                @php 
                    $statusLogs = $client->statusLogs()
                        ->with('changedBy')
                        ->orderBy('created_at', 'desc')
                        ->take(5)
                        ->get(); 
                @endphp
                @if($statusLogs->count() > 0)
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th style="width: 140px;">Date & Time</th>
                                <th style="width: 90px;">Status</th>
                                <th>Reason</th>
                                <th style="width: 100px;">By</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($statusLogs as $log)
                            <tr>
                                <td class="small">{{ $log->created_at->format('d-m-Y h:i A') }}</td>
                                <td>
                                    @if($log->new_status == 'active')
                                        <span class="badge badge-success">Active</span>
                                    @elseif($log->new_status == 'inactive')
                                        <span class="badge badge-warning">Inactive</span>
                                    @else
                                        <span class="badge badge-secondary">{{ ucfirst($log->new_status ?? 'N/A') }}</span>
                                    @endif
                                </td>
                                <td class="small">{{ $log->reason ?? '—' }}</td>
                                <td class="small">{{ $log->changedBy->name ?? 'System' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center text-muted py-2"><i class="fas fa-history mr-1"></i>No status changes recorded</div>
                @endif
            </div>
        </div>

        <div class="card card-outline card-success mb-3">
            <div class="card-header py-2">
                <h5 class="mb-0 font-weight-bold"><i class="fas fa-file-invoice-dollar mr-2"></i>6. Billing History</h5>
            </div>
            <div class="card-body p-2">
                @php 
                    $historyData = $client->getBillingData();
                    $bills = $historyData['bills'];
                    $billDetails = $historyData['bill_details'];
                    $totalBills = $historyData['total_bills'];
                    $totalPayments = $historyData['total_payments'];
                    $totalDue = $historyData['due'];
                    $remainingAdv = $historyData['advance'];
                    $advancePayments = $historyData['advance_payments'] ?? collect();
                @endphp
                <div class="d-flex flex-wrap justify-content-around mb-2 text-center" style="gap: 6px;">
                    <div class="bg-white border rounded py-2 px-3 flex-fill">
                        <span class="text-muted d-block" style="font-size: 12px; font-weight: 600;">Total Bills</span>
                        <div class="font-weight-bold">৳{{ number_format($totalBills ?? 0) }}</div>
                    </div>
                    <div class="bg-white border rounded py-2 px-3 flex-fill">
                        <span class="text-muted d-block" style="font-size: 12px; font-weight: 600;">Total Paid</span>
                        <div class="font-weight-bold">৳{{ number_format($totalPayments ?? 0) }}</div>
                    </div>
                    <div class="bg-white border rounded py-2 px-3 flex-fill">
                        <span class="text-muted d-block" style="font-size: 12px; font-weight: 600;">Due</span>
                        <div class="font-weight-bold text-danger">৳{{ number_format($totalDue ?? 0) }}</div>
                    </div>
                    <div class="bg-white border rounded py-2 px-3 flex-fill">
                        <span class="text-muted d-block" style="font-size: 12px; font-weight: 600;">Last Payment</span>
                        @php $lastPay = $client->payments->sortByDesc('created_at')->first(); @endphp
                        <div class="font-weight-bold">{{ $lastPay ? $lastPay->created_at->format('d-m-Y') : 'N/A' }}</div>
                    </div>
                    @if($remainingAdv > 0)
                    <div class="bg-white border rounded py-2 px-3 flex-fill">
                        <small class="text-muted d-block">Advance</small>
                        <div class="font-weight-bold text-success">৳{{ number_format($remainingAdv ?? 0) }}</div>
                    </div>
                    @endif
                </div>
                @if($advancePayments->count() > 0)
                <div class="border rounded mb-2 overflow-hidden" style="border-left: 4px solid #17a2b8 !important;">
                    <div class="d-flex justify-content-between align-items-center px-2 py-1 bg-light border-bottom small">
                        <span><i class="fas fa-wallet mr-1"></i><strong>Advance Payments</strong> (Not linked to any bill)</span>
                        <span class="badge bg-info text-white">{{ $advancePayments->count() }} payment(s)</span>
                    </div>
                    @foreach($advancePayments as $payment)
                    <div class="px-2 py-1 border-top small bg-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <span style="font-size: 11px; font-weight: 500;"><i class="fas fa-money-bill-wave text-info mr-1"></i>{{ $payment->payment_date->format('d-m-Y') }} {{ $payment->created_at->format('h:i A') }} {{ $payment->paymentMethod->name ?? 'Cash' }} @if($payment->collectedBy)by {{ $payment->collectedBy->name }}@endif</span>
                            <strong class="text-info">৳{{ number_format($payment->amount ?? 0) }}</strong>
                        </div>
                        @if($payment->transaction_id || $payment->remarks)
                        <div class="ml-3" style="font-size: 11px;">
                            @if($payment->transaction_id)<span class="badge badge-info badge-sm"><i class="fas fa-hashtag"></i> TrxID: {{ $payment->transaction_id }}</span> @endif
                            @if($payment->remarks)<small class="text-muted d-block" style="font-size: 11px; margin-top: 1px;"><i class="fas fa-info-circle mr-1"></i>{{ $payment->remarks }}</small>@endif
                        </div>
                        @endif
                    </div>
                    @endforeach
                </div>
                @endif
                @if($bills->count() > 0)
                    @foreach($bills as $bill)
                    @php
                        $detail = $billDetails[$bill->id] ?? null;
                        $billDue = $detail ? $detail['effective_due'] : ($bill->total_amount - $bill->paid_amount);
                        $advAlloc = (float) ($bill->advance_used ?? ($detail ? $detail['advance_allocated'] : 0));
                        $isPaid = $detail ? $detail['is_paid'] : ($bill->status == 'paid');
                    @endphp
                    <div class="border rounded mb-2 overflow-hidden" style="border-left: 4px solid {{ $isPaid ? '#28a745' : '#dc3545' }} !important;">
                        <div class="d-flex justify-content-between align-items-center px-2 py-1 bg-light small">
                            <span style="font-size: 13px; font-weight: 600;"><i class="fas fa-file-invoice text-primary mr-1"></i>{{ $bill->created_at->format('d-m-y h:i A') }} {{ $bill->bill_month }} ৳{{ number_format($bill->total_amount ?? 0) }} Bill Generate</span>
                            @if($isPaid)
                            <span class="badge bg-success">PAID</span>
                            @endif
                        </div>
                        @foreach($bill->payments as $payment)
                        <div class="px-2 py-1 border-top small bg-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <span style="font-size: 11px; font-weight: 500;"><i class="fas fa-money-bill-wave text-success mr-1"></i>{{ $payment->payment_date->format('d-m-Y') }} {{ $payment->created_at->format('h:i A') }} {{ $payment->paymentMethod->name ?? 'Cash' }} @if($payment->collectedBy)by {{ $payment->collectedBy->name }}@endif</span>
                                <strong class="text-success">৳{{ number_format($payment->amount ?? 0) }}</strong>
                            </div>
                            @if($payment->transaction_id || $payment->remarks)
                            <div class="ml-3" style="font-size: 11px;">
                                @if($payment->transaction_id)<span class="badge badge-info badge-sm"><i class="fas fa-hashtag"></i> TrxID: {{ $payment->transaction_id }}</span> @endif
                                @if($payment->remarks)<small class="text-muted d-block" style="font-size: 11px; margin-top: 1px;"><i class="fas fa-info-circle mr-1"></i>{{ $payment->remarks }}</small>@endif
                            </div>
                            @endif
                        </div>
                        @endforeach
                        @if($advAlloc > 0)
                        <div class="d-flex justify-content-between align-items-center px-2 py-1 border-top small bg-white">
                            <span><i class="fas fa-wallet text-info mr-1"></i>Advance Balance Applied @if($bill->advance_applied_at)<span class="text-muted ml-1">({{ $bill->advance_applied_at->format('d-m-y h:i A') }})</span>@endif</span>
                            <strong class="text-info">৳{{ number_format($advAlloc ?? 0) }}</strong>
                        </div>
                        @endif
                        @if($bill->payments->count() == 0 && $advAlloc == 0)
                        <div class="px-2 py-1 border-top text-danger small bg-white"><i class="fas fa-exclamation-circle mr-1"></i>No payment yet</div>
                        @endif
                    </div>
                    @endforeach
                @elseif($advancePayments->count() == 0)
                <div class="text-center text-muted py-3"><i class="fas fa-file-invoice fa-2x mb-2"></i><p class="mb-0">No bills generated</p></div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="billSubmitModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            @php
                $modalData = $client->getBillingSummary();
                $modalDue = $modalData['due'];
                $modalRemainAdv = $modalData['advance'];
            @endphp
            <form action="{{ route('billman.collect-payment', $client) }}" method="POST" id="billSubmitForm">
                @csrf
                <input type="hidden" name="redirect_to" value="{{ url()->previous() }}">
                <div class="modal-header bg-success text-white py-2">
                    <h5 class="modal-title"><i class="fas fa-money-bill-wave mr-2"></i>Bill Submit</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info py-2">
                        <div class="row">
                            <div class="col-6">
                                <strong>Client:</strong> {{ $client->name }}<br>
                                <strong>ID:</strong> {{ $client->client_id }}
                            </div>
                            <div class="col-6 text-right">
                                <strong>Due:</strong> <span class="{{ $modalDue > 0 ? 'text-danger' : 'text-success' }} font-weight-bold">৳{{ number_format($modalDue ?? 0, 2) }}</span>
                                @if($modalRemainAdv > 0)
                                <br><strong>Advance:</strong> <span class="text-success">৳{{ number_format($modalRemainAdv ?? 0, 2) }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-money-bill mr-1"></i> Amount (৳)</label>
                        <input type="number" name="amount" id="bs_amount_billman" class="form-control form-control-lg text-center" 
                               required min="1" step="1" value="{{ (int) max(1, $modalDue) }}" onchange="checkBillmanAmountDiff()" onkeyup="checkBillmanAmountDiff()">
                        <small class="text-muted">Enter amount collected from client</small>
                        <small id="bs_amount_warning_billman" class="text-warning mt-1" style="display: none;"><i class="fas fa-exclamation-triangle mr-1"></i>Amount differs from due - Note required!</small>
                        <div id="billman_reason_wrapper" style="display: none;" class="mt-2">
                            <input type="text" name="notes_reason" id="bs_reason_billman" class="form-control" placeholder="Why is the amount different from due?">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-credit-card mr-1"></i> Payment Method</label>
                        <select name="payment_method_id" class="form-control billman-payment-method-select" required>
                            @foreach(auth()->user()->allowedPaymentMethods() as $method)
                            <option value="{{ $method->id }}" data-name="{{ strtolower($method->name) }}">{{ $method->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="billman-online-payment-fields" style="display: none;">
                        <div class="form-group">
                            <label><i class="fas fa-hashtag mr-1"></i> Transaction ID (TrxID) <span class="text-danger">*</span></label>
                            <input type="text" name="transaction_id" class="form-control billman-trx-id" placeholder="Enter transaction ID">
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-mobile-alt mr-1"></i> <span class="billman-mobile-label">bKash</span> Number <span class="text-danger">*</span></label>
                            <input type="text" name="mobile_payment_number" class="form-control billman-mobile-number" placeholder="Enter the bKash/Nagad/Rocket number" maxlength="15">
                            <small class="text-muted">The mobile payment number used for this transaction</small>
                        </div>
                    </div>

                    <div class="form-group billman-cash-receipt-field" style="display: none;">
                        <label><i class="fas fa-receipt mr-1"></i> Money Receipt No.</label>
                        <input type="text" name="money_receipt_no" class="form-control" placeholder="Enter money receipt number">
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-sticky-note mr-1"></i> Notes</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Payment notes..."></textarea>
                    </div>
                    
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="bs_send_sms_billman" name="send_sms" value="1" checked>
                        <label class="custom-control-label" for="bs_send_sms_billman">
                            <i class="fas fa-sms mr-1"></i> Send SMS notification
                        </label>
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check mr-1"></i>Submit Payment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@include('components.payment-receipt-modal')

<script>
$('#refreshOnuDiagBill').on('click', function() {
    var onuId = $(this).data('onu-id');
    if (!onuId) { toastr.warning('No ONU linked to this client.'); return; }
    var $btn = $(this), $icon = $('#refreshOnuIconBill');
    $btn.prop('disabled', true); $icon.addClass('fa-spin');
    $('#billOnuSyncSource').html('<i class="fas fa-spinner fa-spin text-info mr-1"></i><small>Syncing...</small>').show();
    $.ajax({
        url: '/billman/network/onu/' + onuId + '/live-info?_t=' + Date.now(),
        type: 'GET', cache: false, timeout: 15000,
        success: function(res) {
            $btn.prop('disabled', false); $icon.removeClass('fa-spin');
            if (!res.success || !res.data) { toastr.error('OLT sync failed.'); return; }
            var d = res.data;
            if (d.port)        $('#bill_olt_port').text(d.port + (d.onu_number ? ':'+d.onu_number : ''));
            if (d.rx_power !== null && d.rx_power !== undefined) {
                var rxColor = d.rx_power >= -15 ? '#28a745' : (d.rx_power >= -20 ? '#17a2b8' : (d.rx_power >= -25 ? '#ffc107' : '#dc3545'));
                $('#bill_rx_power').html('<span style="color:'+rxColor+';font-weight:600;">'+parseFloat(d.rx_power).toFixed(2)+' dBm</span>');
            }
            if (d.status) {
                var sBadge = d.status === 'online'
                    ? '<span class="badge badge-success">ONLINE</span>'
                    : '<span class="badge badge-danger">OFFLINE</span>';
            }
            $('#billOnuSyncSource').html('<i class="fas fa-check-circle text-success mr-1"></i><small>Live — '+(new Date().toLocaleTimeString())+'</small>').show();
            toastr.success('ONU data synced!');
        },
        error: function() {
            $btn.prop('disabled', false); $icon.removeClass('fa-spin');
            $('#billOnuSyncSource').html('<i class="fas fa-exclamation-triangle text-warning mr-1"></i><small>Sync failed</small>').show();
            toastr.error('OLT sync failed.');
        }
    });
});
</script>
@endsection

@push('scripts')
<script>
var billmanDueAmount = {{ $modalDue ?? 0 }};
function checkBillmanAmountDiff() {
    var amount = parseFloat(document.getElementById('bs_amount_billman').value) || 0;
    var isDifferent = billmanDueAmount > 0 && Math.abs(amount - billmanDueAmount) > 0.01;
    document.getElementById('bs_amount_warning_billman').style.display = isDifferent ? 'block' : 'none';
    var reasonWrapper = document.getElementById('billman_reason_wrapper');
    var reasonInput = document.getElementById('bs_reason_billman');
    if (reasonWrapper) reasonWrapper.style.display = isDifferent ? 'block' : 'none';
    if (reasonInput) reasonInput.required = isDifferent;
    return isDifferent;
}

var billmanBillForm = document.getElementById("billSubmitForm");
if (billmanBillForm) {
    billmanBillForm.addEventListener("submit", function(e) {
        e.preventDefault();
        if (checkBillmanAmountDiff() === true) return;
        var formEl = billmanBillForm;
        var btn = jQuery(formEl).find("[type=submit]");
        btn.prop("disabled", true).text("Processing...");
        jQuery.ajax({
            url: formEl.action,
            method: "POST",
            data: jQuery(formEl).serialize(),
            headers: { "Accept": "application/json" },
            success: function(res) {
                if (res.success) {
                    jQuery("#billSubmitModal").modal("hide");
                    if (res.thermal_url) window.open(res.thermal_url, "_blank");
                    toastr.success(res.message || "Payment collected!");
                    setTimeout(function() { location.reload(); }, 1500);
                } else {
                    toastr.error(res.message || "Payment failed.");
                    btn.prop("disabled", false).text("Collect Payment");
                }
            },
            error: function(xhr) {
                var msg = xhr.responseJSON ? (xhr.responseJSON.message || "Payment failed.") : "Payment failed.";
                toastr.error(msg);
                btn.prop("disabled", false).text("Collect Payment");
            }
        });
    });
}

(function(){
    var mobileMethods = ['bkash', 'nagad', 'rocket'];
    function checkBillmanPaymentMethod() {
        var sel = $('.billman-payment-method-select');
        var methodName = (sel.find('option:selected').data('name') || '').toLowerCase();
        var isMobile = mobileMethods.some(function(m) { return methodName.indexOf(m) !== -1; });
        if (isMobile) {
            $('.billman-online-payment-fields').show();
            $('.billman-mobile-number').prop('required', true);
            $('.billman-trx-id').prop('required', true);
            $('.billman-mobile-label').text(sel.find('option:selected').text().trim());
        } else {
            $('.billman-online-payment-fields').hide();
            $('.billman-mobile-number').prop('required', false);
            $('.billman-trx-id').prop('required', false);
        }
        var isCash = methodName.indexOf('cash') !== -1;
        if (isCash) {
            $('.billman-cash-receipt-field').show();
        } else {
            $('.billman-cash-receipt-field').hide().find('input').val('');
        }
    }
    $('.billman-payment-method-select').on('change', checkBillmanPaymentMethod);
    checkBillmanPaymentMethod();
})();
</script>
@endpush
