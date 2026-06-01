@extends('layouts.admin')

@section('title', 'Edit Client')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.clients.index') }}">Clients</a></li>
<li class="breadcrumb-item active">Edit</li>
@endsection

@push('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<style>
    .section-title { font-size: 12px; font-weight: 700; color: #495057; margin-bottom: 8px; padding-bottom: 4px; border-bottom: 2px solid #e9ecef; }
    .section-title i { color: #007bff; margin-right: 5px; }
    .compact-form .form-group { margin-bottom: 7px; }
    .compact-form .row { margin-left: -5px; margin-right: -5px; }
    .compact-form .row > [class*="col-"] { padding-left: 5px; padding-right: 5px; }
    .compact-form label { font-size: 10.5px; font-weight: 600; color: #6c757d; margin-bottom: 1px; text-transform: uppercase; letter-spacing: 0.2px; display: block; }
    .compact-form .form-control, .compact-form .custom-file-label { font-size: 12px; padding: 3px 7px; height: calc(1.6rem + 2px); }
    .compact-form textarea.form-control { height: auto; min-height: 50px; }
    .compact-form .form-control-file { font-size: 11px; }
    .compact-form select.form-control { padding: 3px 6px; }
    .compact-form .input-group .btn { padding: 3px 8px; font-size: 12px; }
    .compact-form .input-group-append .btn { height: calc(1.6rem + 2px); }
    .compact-form .form-text, .compact-form small { font-size: 10px; line-height: 1.3; }
    .compact-form .custom-control-label { font-size: 12px; }
    .compact-form .custom-control { padding-top: 1px; }
    .section-card { border: 1px solid #dee2e6; border-radius: 5px; padding: 10px 12px; margin-bottom: 10px; background: #fff; }
    .section-card.highlight { border-left: 3px solid #007bff; }
    .compact-form .alert { padding: 5px 10px; font-size: 11px; }
    .compact-form .input-group > .form-control { height: calc(1.6rem + 2px); }
</style>
@endpush

@section('content')
<form action="{{ route('admin.clients.update', $client) }}" method="POST" enctype="multipart/form-data" class="compact-form">
    @csrf
    @method('PUT')
    @if($errors->any())
    <div class="alert alert-danger py-2 mb-3">
        <ul class="mb-0 small">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
    @endif

    <div class="row">
        <div class="col-lg-4 col-md-6">
            <div class="section-card highlight">
                <div class="section-title"><i class="fas fa-user"></i>Personal Info</div>
                @php
                    $editNameParts = explode(' ', old('name', $client->name), 2);
                @endphp
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>First Name <span class="text-danger">*</span></label>
                            <input type="text" name="first_name" class="form-control" value="{{ old('first_name', $editNameParts[0] ?? '') }}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Last Name <span class="text-danger">*</span></label>
                            <input type="text" name="last_name" class="form-control" value="{{ old('last_name', $editNameParts[1] ?? '') }}" required>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Father's Name</label>
                            <input type="text" name="father_husband_name" class="form-control" value="{{ old('father_husband_name', $client->father_husband_name) }}">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Mother's Name</label>
                            <input type="text" name="mother_name" class="form-control" value="{{ old('mother_name', $client->mother_name) }}">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Date of Birth</label>
                            <input type="text" name="date_of_birth" class="form-control flatpickr-date" value="{{ old('date_of_birth', $client->date_of_birth ? $client->date_of_birth->format('Y-m-d') : '') }}">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Gender</label>
                            <select name="gender" class="form-control">
                                <option value="">Select</option>
                                <option value="male" {{ old('gender', $client->gender) == 'male' ? 'selected' : '' }}>Male</option>
                                <option value="female" {{ old('gender', $client->gender) == 'female' ? 'selected' : '' }}>Female</option>
                                <option value="other" {{ old('gender', $client->gender) == 'other' ? 'selected' : '' }}>Other</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Phone <span class="text-danger">*</span></label>
                            <input type="text" name="phone" class="form-control" value="{{ old('phone', $client->phone) }}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Alt Phone</label>
                            <input type="text" name="alt_phone" class="form-control" value="{{ old('alt_phone', $client->alt_phone) }}">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" class="form-control" value="{{ old('email', $client->email) }}">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>NID Number</label>
                            <input type="text" name="nid" class="form-control" value="{{ old('nid', $client->nid) }}">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Occupation</label>
                            <input type="text" name="occupation" class="form-control" value="{{ old('occupation', $client->occupation) }}" placeholder="e.g. Business">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Client Type</label>
                            <select name="client_type" class="form-control">
                                <option value="regular" {{ old('client_type', $client->client_type) == 'regular' ? 'selected' : '' }}>Regular</option>
                                <option value="corporate" {{ old('client_type', $client->client_type) == 'corporate' ? 'selected' : '' }}>Corporate</option>
                                <option value="home" {{ old('client_type', $client->client_type) == 'home' ? 'selected' : '' }}>Home</option>
                                <option value="business" {{ old('client_type', $client->client_type) == 'business' ? 'selected' : '' }}>Business</option>
                                <option value="government" {{ old('client_type', $client->client_type) == 'government' ? 'selected' : '' }}>Government</option>
                                <option value="vip" {{ old('client_type', $client->client_type) == 'vip' ? 'selected' : '' }}>VIP</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label>Address <span class="text-danger">*</span></label>
                    <textarea name="address" class="form-control" rows="2" required id="address">{{ old('address', $client->address) }}</textarea>
                </div>
            </div>

            <div class="section-card">
                <div class="section-title"><i class="fas fa-id-card"></i>Documents</div>
                <div class="form-group">
                    <label>Profile Picture</label>
                    <input type="file" name="profile_picture" class="form-control-file" accept="image/*">
                    @if($client->profile_picture)<small class="text-muted">Current: <a href="{{ asset('storage/'.$client->profile_picture) }}" target="_blank">View</a></small>@endif
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>NID Front</label>
                            <input type="file" name="nid_front_image" class="form-control-file" accept="image/*">
                            @if($client->nid_front_image)<small class="text-muted"><a href="{{ asset('storage/'.$client->nid_front_image) }}" target="_blank">View</a></small>@endif
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>NID Back</label>
                            <input type="file" name="nid_back_image" class="form-control-file" accept="image/*">
                            @if($client->nid_back_image)<small class="text-muted"><a href="{{ asset('storage/'.$client->nid_back_image) }}" target="_blank">View</a></small>@endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="section-card">
                <div class="section-title"><i class="fas fa-map-marker-alt"></i>Location</div>
                <div class="alert alert-info py-1 mb-2 small" id="locationStatus" style="display: none;"></div>
                <input type="hidden" name="google_map_location" id="google_map_location" value="{{ old('google_map_location', $client->google_map_location) }}">
                <div class="row">
                    <div class="col-md-5">
                        <div class="form-group">
                            <label>Latitude</label>
                            <input type="text" name="google_map_lat" class="form-control" value="{{ old('google_map_lat', $client->google_map_lat) }}" id="lat" readonly>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="form-group">
                            <label>Longitude</label>
                            <input type="text" name="google_map_lng" class="form-control" value="{{ old('google_map_lng', $client->google_map_lng) }}" id="lng" readonly>
                        </div>
                    </div>
                    <div class="col-md-2 d-flex align-items-end pb-2" style="gap:5px;">
                        <button type="button" class="btn btn-outline-info btn-sm" onclick="getLocationFromAddress()" title="Auto-search from address"><i class="fas fa-search-location"></i></button>
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="openMapPicker()" title="Pick exact location on map"><i class="fas fa-map-pin"></i></button>
                    </div>
                </div>
                <a href="#" id="viewMapBtn" target="_blank" class="btn btn-success btn-sm btn-block" style="display: none;"><i class="fas fa-external-link-alt mr-1"></i>View on Map</a>

                <!-- Map Picker Modal -->
                <div class="modal fade" id="mapPickerModal" tabindex="-1" role="dialog" aria-hidden="true">
                    <div class="modal-dialog modal-xl" role="document">
                        <div class="modal-content">
                            <div class="modal-header py-2">
                                <h5 class="modal-title"><i class="fas fa-map-marked-alt mr-2 text-primary"></i>Pick Exact Location</h5>
                                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                            </div>
                            <div class="modal-body p-0">
                                <div style="padding:8px 12px;background:#f8f9fa;border-bottom:1px solid #dee2e6;display:flex;gap:8px;align-items:center;">
                                    <input type="text" id="mapSearchInput" class="form-control form-control-sm" placeholder="Search address... e.g. Dhakeshwari Rd, Dhaka" style="flex:1;">
                                    <button class="btn btn-sm btn-primary" onclick="searchOnMap()"><i class="fas fa-search"></i> Search</button>
                                    <span style="font-size:12px;color:#6c757d;white-space:nowrap;"><i class="fas fa-info-circle"></i> Click map for exact pin</span>
                                </div>
                                <div id="leafletMap" style="height:460px;width:100%;"></div>
                            </div>
                            <div class="modal-footer py-2">
                                <div style="flex:1;font-size:13px;" id="mapPickedCoords">No location selected yet. Click on the map to pick.</div>
                                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancel</button>
                                <button type="button" class="btn btn-success btn-sm" id="confirmMapPick" onclick="confirmMapLocation()" disabled><i class="fas fa-check mr-1"></i>Use This Location</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-md-6">
            <div class="section-card highlight">
                <div class="section-title"><i class="fas fa-network-wired"></i>Connection & Auth</div>
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Client Code</label>
                            <input type="text" name="client_id" class="form-control" value="{{ old('client_id', $client->client_id) }}">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Connection Type <span class="text-danger">*</span></label>
                            <select name="connection_type" class="form-control" required>
                                <option value="pppoe" {{ old('connection_type', $client->connection_type) == 'pppoe' ? 'selected' : '' }}>PPPoE</option>
                                <option value="static" {{ old('connection_type', $client->connection_type) == 'static' ? 'selected' : '' }}>Static IP</option>
                                <option value="dhcp" {{ old('connection_type', $client->connection_type) == 'dhcp' ? 'selected' : '' }}>DHCP</option>
                                <option value="hotspot" {{ old('connection_type', $client->connection_type) == 'hotspot' ? 'selected' : '' }}>Hotspot</option>
                                <option value="queue" {{ old('connection_type', $client->connection_type) == 'queue' ? 'selected' : '' }}>Queue</option>
                                <option value="invoice" {{ old('connection_type', $client->connection_type) == 'invoice' ? 'selected' : '' }}>Invoice Only</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Cable Type</label>
                            <select name="connection_medium" class="form-control">
                                <option value="utp" {{ old('connection_medium', $client->connection_medium) == 'utp' ? 'selected' : '' }}>UTP Cable</option>
                                <option value="fiber" {{ old('connection_medium', $client->connection_medium) == 'fiber' ? 'selected' : '' }}>Optical Fiber</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label>Username <span class="text-danger">*</span></label>
                    <input type="text" name="username" class="form-control" value="{{ old('username', $client->username) }}" required>
                </div>
                <div class="form-group">
                    <label>PPPoE Password</label>
                    <div class="input-group">
                        <input type="password" name="pppoe_password" class="form-control" value="{{ old('pppoe_password', $client->pppoe_password) }}" id="pppoe_password">
                        <div class="input-group-append">
                            <button type="button" class="btn btn-outline-secondary toggle-password" data-target="#pppoe_password" title="Show/Hide" tabindex="-1"><i class="fas fa-eye"></i></button>
                            <button type="button" class="btn btn-secondary" onclick="generatePassword()" title="Generate"><i class="fas fa-random"></i></button>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>IP Address</label>
                            <input type="text" name="ip_address" class="form-control" value="{{ old('ip_address', $client->ip_address) }}">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>MikroTik Router</label>
                            <select name="mikrotik_device_id" class="form-control" id="mikrotik_device_id">
                                <option value="" data-device-name="">Select</option>
                                @if(isset($mikrotikDevices))
                                @foreach($mikrotikDevices as $device)
                                <option value="{{ $device->id }}" data-device-name="{{ $device->name }}" {{ old('mikrotik_device_id', $client->mikrotik_device_id) == $device->id ? 'selected' : '' }}>{{ $device->name }}</option>
                                @endforeach
                                @endif
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-8">
                        <div class="form-group">
                            <label>Referral Partner</label>
                            <select name="agent_id" class="form-control" id="editAgentSelect">
                                <option value="" data-commission="">No Referral Partner</option>
                                @foreach($agents ?? [] as $agent)
                                <option value="{{ $agent->id }}" data-commission="{{ $agent->default_commission }}" {{ old('agent_id', $client->agent_id) == $agent->id ? 'selected' : '' }}>{{ $agent->name }} {{ $agent->personal_contact ? '(' . $agent->personal_contact . ')' : '' }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Commission (%)</label>
                            <input type="number" name="commission_percent" id="editCommissionPercent" class="form-control" step="0.01" min="0" max="100" value="{{ old('commission_percent', $client->commission_percent) }}" placeholder="0.00">
                        </div>
                    </div>
                </div>
            </div>

            <div class="section-card">
                <div class="section-title"><i class="fas fa-map-signs"></i>Location Hierarchy</div>
                @php
                    $clientDistrict = $client->district_id ? $districts->firstWhere('id', $client->district_id) : null;
                    $clientDivision = $clientDistrict ? $clientDistrict->division : null;
                    $clientUpazilaId = $client->upazila_id ?? ($client->zone ? $client->zone->upazila_id : null);
                @endphp
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Division</label>
                            <select name="division_select" class="form-control" id="division_id">
                                <option value="">-- Select Division --</option>
                                @foreach($divisions as $div)
                                <option value="{{ $div }}" {{ $clientDivision == $div ? 'selected' : '' }}>{{ $div }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>District</label>
                            <select name="district_id" class="form-control" id="district_id">
                                <option value="">-- Select Division First --</option>
                                @if($clientDivision)
                                    @foreach($districts->where('division', $clientDivision) as $dist)
                                    <option value="{{ $dist->id }}" {{ old('district_id', $client->district_id) == $dist->id ? 'selected' : '' }}>{{ $dist->name }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Thana/Upazila</label>
                            <select name="upazila_id" class="form-control" id="upazila_id">
                                <option value="">-- Select District First --</option>
                                @if($client->district_id)
                                    @foreach($upazilas->where('district_id', $client->district_id) as $upz)
                                    <option value="{{ $upz->id }}" {{ old('upazila_id', $clientUpazilaId) == $upz->id ? 'selected' : '' }}>{{ $upz->name }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Zone</label>
                            <select name="zone_id" class="form-control" id="zone_id">
                                <option value="">-- Select Thana/Upazila First --</option>
                                @if($clientUpazilaId)
                                    @foreach($zones->where('upazila_id', $clientUpazilaId) as $zone)
                                    <option value="{{ $zone->id }}" {{ old('zone_id', $client->zone_id) == $zone->id ? 'selected' : '' }}>{{ $zone->name }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Sub Zone</label>
                            <select name="area_id" class="form-control" id="area_id">
                                <option value="">-- Select Zone First --</option>
                                @if($client->zone_id && isset($areas))
                                    @foreach($areas->where('zone_id', $client->zone_id) as $area)
                                    <option value="{{ $area->id }}" {{ old('area_id', $client->area_id) == $area->id ? 'selected' : '' }}>{{ $area->name }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>TJ</label>
                            <select name="box_id" class="form-control" id="box_id">
                                <option value="">-- Select Sub Zone First --</option>
                                @if($client->area_id && isset($boxes))
                                    @foreach($boxes->where('area_id', $client->area_id) as $box)
                                    <option value="{{ $box->id }}" {{ old('box_id', $client->box_id) == $box->id ? 'selected' : '' }}>{{ $box->name }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                    </div>
                </div>
            </div>


            <div class="section-card">
                <div class="section-title"><i class="fas fa-satellite-dish"></i>ONU/OLT Device</div>
                <div class="alert alert-info py-1 mb-2" style="font-size:12px; border-radius:6px;">
                    <i class="fas fa-info-circle mr-1"></i>
                    Type ONU details manually, or click <strong>"Pick ONU"</strong> to select from OLT-detected ONU list.
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>OLT Device</label>
                            <select name="olt_device_id" id="edit_olt_device_id" class="form-control">
                                <option value="">Select</option>
                                @if(isset($oltDevices))
                                @foreach($oltDevices as $olt)
                                <option value="{{ $olt->id }}" {{ old('olt_device_id', $client->olt_device_id) == $olt->id ? 'selected' : '' }}>{{ $olt->name }} ({{ $olt->ip_address }})</option>
                                @endforeach
                                @endif
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6 d-flex align-items-end pb-1">
                        <div class="form-group d-flex w-100" style="gap:6px;">
                            <button type="button" class="btn btn-primary flex-grow-1" id="btnPickOnu" style="font-weight:600;">
                                <i class="fas fa-search mr-1"></i>Pick ONU
                            </button>
                            @if($client->onu_serial || $client->onu_mac)
                            <button type="button" class="btn btn-outline-danger" onclick="unselectOnu()" title="Unselect ONU">
                                <i class="fas fa-times mr-1"></i>Unselect
                            </button>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>ONU Serial <small class="text-muted">(auto-filled)</small></label>
                            <input type="text" name="onu_serial" id="edit_onu_serial" class="form-control" value="{{ old('onu_serial', $client->onu_serial) }}" placeholder="e.g. BDCOM2057AF13AE4C">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>ONU MAC <small class="text-muted">(auto-filled)</small></label>
                            <input type="text" name="onu_mac" id="edit_onu_mac" class="form-control" value="{{ old('onu_mac', $client->onu_mac) }}" placeholder="e.g. 20:57:AF:13:AE:4C">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>ONU Model</label>
                            <input type="text" name="onu_model" id="edit_onu_model" class="form-control" value="{{ old('onu_model', $client->onu_model) }}" placeholder="e.g. ZTE FHR1">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>OLT Port <small class="text-muted">(auto-filled)</small></label>
                            <input type="text" name="olt_port" id="edit_olt_port" class="form-control" value="{{ old('olt_port', $client->olt_port) }}" placeholder="e.g. EPON0/2">
                        </div>
                    </div>
                </div>
            </div>

            <div class="section-card">
                <div class="section-title"><i class="fas fa-project-diagram"></i>Fiber & ONU</div>
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Fiber Brand</label>
                            <input type="text" name="fiber_vendor" class="form-control" value="{{ old('fiber_vendor', $client->fiber_vendor) }}" placeholder="e.g. Corning">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Fiber Code</label>
                            <input type="text" name="fiber_code" class="form-control" value="{{ old('fiber_code', $client->fiber_code) }}" placeholder="e.g. FC-001">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Fiber Core Type</label>
                            <input type="text" name="fiber_core_type" class="form-control" value="{{ old('fiber_core_type', $client->fiber_core_type) }}" placeholder="e.g. 2 Core">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Fiber Color</label>
                            <div class="input-group">
    <input type="text" name="fiber_color" id="fiber_color_input" class="form-control" value="{{ old('fiber_color', $client->fiber_color) }}" readonly placeholder="e.g. Blue" autocomplete="off">
    <div class="input-group-append">
        <button class="btn btn-outline-secondary fiber-color-btn" type="button" data-target="fiber_color_input" style="padding:0 8px;">
            <span class="fiber-dot" id="dot_fiber_color_input" style="display:inline-block;width:18px;height:18px;border-radius:3px;background:#ccc;border:1px solid #aaa;vertical-align:middle;"></span>
        </button>
    </div>
    <div class="fiber-color-palette" id="palette_fiber_color_input" style="display:none;position:absolute;top:100%;left:0;z-index:9999;background:#fff;border:1px solid #ddd;border-radius:6px;padding:8px;box-shadow:0 4px 12px rgba(0,0,0,0.15);width:170px;">
        <div style="display:flex;flex-wrap:wrap;"><span class="fiber-color-swatch" data-color="Blue" data-hex="#0057B8" data-target="fiber_color_input" style="display:inline-block;width:28px;height:28px;background:#0057B8;border-radius:4px;cursor:pointer;margin:2px;border:1px solid rgba(0,0,0,0.15);" title="Blue"></span><span class="fiber-color-swatch" data-color="Orange" data-hex="#FF8C00" data-target="fiber_color_input" style="display:inline-block;width:28px;height:28px;background:#FF8C00;border-radius:4px;cursor:pointer;margin:2px;border:1px solid rgba(0,0,0,0.15);" title="Orange"></span><span class="fiber-color-swatch" data-color="Green" data-hex="#28a745" data-target="fiber_color_input" style="display:inline-block;width:28px;height:28px;background:#28a745;border-radius:4px;cursor:pointer;margin:2px;border:1px solid rgba(0,0,0,0.15);" title="Green"></span><span class="fiber-color-swatch" data-color="Brown" data-hex="#8B4513" data-target="fiber_color_input" style="display:inline-block;width:28px;height:28px;background:#8B4513;border-radius:4px;cursor:pointer;margin:2px;border:1px solid rgba(0,0,0,0.15);" title="Brown"></span><span class="fiber-color-swatch" data-color="Slate" data-hex="#708090" data-target="fiber_color_input" style="display:inline-block;width:28px;height:28px;background:#708090;border-radius:4px;cursor:pointer;margin:2px;border:1px solid rgba(0,0,0,0.15);" title="Slate"></span><span class="fiber-color-swatch" data-color="White" data-hex="#e9ecef" data-target="fiber_color_input" style="display:inline-block;width:28px;height:28px;background:#e9ecef;border-radius:4px;cursor:pointer;margin:2px;border:1px solid rgba(0,0,0,0.15);" title="White"></span><span class="fiber-color-swatch" data-color="Red" data-hex="#dc3545" data-target="fiber_color_input" style="display:inline-block;width:28px;height:28px;background:#dc3545;border-radius:4px;cursor:pointer;margin:2px;border:1px solid rgba(0,0,0,0.15);" title="Red"></span><span class="fiber-color-swatch" data-color="Black" data-hex="#222222" data-target="fiber_color_input" style="display:inline-block;width:28px;height:28px;background:#222222;border-radius:4px;cursor:pointer;margin:2px;border:1px solid rgba(0,0,0,0.15);" title="Black"></span><span class="fiber-color-swatch" data-color="Yellow" data-hex="#ffc107" data-target="fiber_color_input" style="display:inline-block;width:28px;height:28px;background:#ffc107;border-radius:4px;cursor:pointer;margin:2px;border:1px solid rgba(0,0,0,0.15);" title="Yellow"></span><span class="fiber-color-swatch" data-color="Violet" data-hex="#6f42c1" data-target="fiber_color_input" style="display:inline-block;width:28px;height:28px;background:#6f42c1;border-radius:4px;cursor:pointer;margin:2px;border:1px solid rgba(0,0,0,0.15);" title="Violet"></span><span class="fiber-color-swatch" data-color="Rose" data-hex="#e83e8c" data-target="fiber_color_input" style="display:inline-block;width:28px;height:28px;background:#e83e8c;border-radius:4px;cursor:pointer;margin:2px;border:1px solid rgba(0,0,0,0.15);" title="Rose"></span><span class="fiber-color-swatch" data-color="Aqua" data-hex="#20c997" data-target="fiber_color_input" style="display:inline-block;width:28px;height:28px;background:#20c997;border-radius:4px;cursor:pointer;margin:2px;border:1px solid rgba(0,0,0,0.15);" title="Aqua"></span></div>
        <div style="margin-top:4px;font-size:10px;color:#999;">Click a color to select</div>
    </div>
</div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Splitter Number</label>
                            <input type="text" name="splitter_number" class="form-control" value="{{ old('splitter_number', $client->splitter_number) }}" placeholder="e.g. SP-01">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Splitter Color</label>
                            <div class="input-group">
    <input type="text" name="splitter_color" id="splitter_color_input" class="form-control" value="{{ old('splitter_color', $client->splitter_color) }}" readonly placeholder="e.g. Green" autocomplete="off">
    <div class="input-group-append">
        <button class="btn btn-outline-secondary fiber-color-btn" type="button" data-target="splitter_color_input" style="padding:0 8px;">
            <span class="fiber-dot" id="dot_splitter_color_input" style="display:inline-block;width:18px;height:18px;border-radius:3px;background:#ccc;border:1px solid #aaa;vertical-align:middle;"></span>
        </button>
    </div>
    <div class="fiber-color-palette" id="palette_splitter_color_input" style="display:none;position:absolute;top:100%;left:0;z-index:9999;background:#fff;border:1px solid #ddd;border-radius:6px;padding:8px;box-shadow:0 4px 12px rgba(0,0,0,0.15);width:170px;">
        <div style="display:flex;flex-wrap:wrap;"><span class="fiber-color-swatch" data-color="Blue" data-hex="#0057B8" data-target="splitter_color_input" style="display:inline-block;width:28px;height:28px;background:#0057B8;border-radius:4px;cursor:pointer;margin:2px;border:1px solid rgba(0,0,0,0.15);" title="Blue"></span><span class="fiber-color-swatch" data-color="Orange" data-hex="#FF8C00" data-target="splitter_color_input" style="display:inline-block;width:28px;height:28px;background:#FF8C00;border-radius:4px;cursor:pointer;margin:2px;border:1px solid rgba(0,0,0,0.15);" title="Orange"></span><span class="fiber-color-swatch" data-color="Green" data-hex="#28a745" data-target="splitter_color_input" style="display:inline-block;width:28px;height:28px;background:#28a745;border-radius:4px;cursor:pointer;margin:2px;border:1px solid rgba(0,0,0,0.15);" title="Green"></span><span class="fiber-color-swatch" data-color="Brown" data-hex="#8B4513" data-target="splitter_color_input" style="display:inline-block;width:28px;height:28px;background:#8B4513;border-radius:4px;cursor:pointer;margin:2px;border:1px solid rgba(0,0,0,0.15);" title="Brown"></span><span class="fiber-color-swatch" data-color="Gray" data-hex="#808080" data-target="splitter_color_input" style="display:inline-block;width:28px;height:28px;background:#808080;border-radius:4px;cursor:pointer;margin:2px;border:1px solid rgba(0,0,0,0.15);" title="Gray"></span><span class="fiber-color-swatch" data-color="White" data-hex="#e9ecef" data-target="splitter_color_input" style="display:inline-block;width:28px;height:28px;background:#e9ecef;border-radius:4px;cursor:pointer;margin:2px;border:1px solid rgba(0,0,0,0.15);" title="White"></span><span class="fiber-color-swatch" data-color="Red" data-hex="#dc3545" data-target="splitter_color_input" style="display:inline-block;width:28px;height:28px;background:#dc3545;border-radius:4px;cursor:pointer;margin:2px;border:1px solid rgba(0,0,0,0.15);" title="Red"></span><span class="fiber-color-swatch" data-color="Black" data-hex="#222222" data-target="splitter_color_input" style="display:inline-block;width:28px;height:28px;background:#222222;border-radius:4px;cursor:pointer;margin:2px;border:1px solid rgba(0,0,0,0.15);" title="Black"></span></div>
        <div style="margin-top:4px;font-size:10px;color:#999;">Click a color to select</div>
    </div>
</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>ONU Brand</label>
                            <input type="text" name="onu_brand" class="form-control" value="{{ old('onu_brand', $client->onu_brand) }}" placeholder="e.g. Huawei">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>ONU MAC/Serial</label>
                            <input type="text" class="form-control" id="fiber_onu_mac_display" value="{{ old('onu_mac', $client->onu_mac) }}" placeholder="AA:BB:CC:DD:EE:FF" oninput="$('#edit_onu_mac').val(this.value)">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-md-12">
            <div class="section-card highlight">
                <div class="section-title"><i class="fas fa-money-bill-wave"></i>Billing & Package</div>
                <div class="form-group">
                    <label>Package <span class="text-danger">*</span></label>
                    <select name="package_id" class="form-control" required id="package_id">
                        <option value="">Select Package</option>
                        @foreach($packages as $package)
                        <option value="{{ $package->id }}" data-price="{{ $package->price }}" data-mikrotik-profile="{{ $package->mikrotik_profile }}" data-source-device="{{ $package->boundProfile->source_device ?? '' }}" {{ old('package_id', $client->package_id) == $package->id ? 'selected' : '' }}>{{ $package->name }} - ৳{{ number_format($package->price ?? 0, 2) }} ({{ $package->bandwidth }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>Profile Name <small class="text-muted">(MikroTik Speed)</small></label>
                    <select name="mikrotik_profile_name" class="form-control" id="mikrotik_profile_name">
                        <option value="">-- Select Profile --</option>
                        @foreach($availableProfiles as $profile)
                        <option value="{{ $profile->name }}" data-source-device="{{ $profile->source_device }}" {{ old('mikrotik_profile_name', $client->mikrotik_profile_name) == $profile->name ? 'selected' : '' }}>{{ $profile->name }}</option>
                        @endforeach
                    </select>
                    <small class="text-muted">Speed profile on router. Bill comes from Package price.</small>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Monthly Bill (৳)</label>
                            <input type="number" name="monthly_fee" class="form-control" value="{{ old('monthly_fee', $client->monthly_fee) }}" id="monthly_fee" step="0.01">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Permanent Discount (৳)</label>
                            <input type="number" name="permanent_discount" class="form-control" value="{{ old('permanent_discount', $client->permanent_discount) }}" step="0.01" min="0">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label><i class="fas fa-file-invoice text-primary mr-1"></i>Bill Generate Date</label>
                            <select name="billing_date" class="form-control">
                                @for($d = 1; $d <= 31; $d++)
                                    <option value="{{ $d }}" {{ old('billing_date', $client->billing_date) == $d ? 'selected' : '' }}>{{ $d }}</option>
                                @endfor
                            </select>
                            <small class="text-muted">Day of month when bill is auto-generated</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label><i class="fas fa-exclamation-triangle text-danger mr-1"></i>Auto-Inactive Date</label>
                            <select name="payment_deadline" class="form-control">
                                @for($d = 1; $d <= 31; $d++)
                                    <option value="{{ $d }}" {{ old('payment_deadline', $client->payment_deadline ?? 15) == $d ? 'selected' : '' }}>{{ $d }}</option>
                                @endfor
                            </select>
                            <small class="text-muted">Day — auto-inactivated if unpaid</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Status</label>
                            <select name="status" id="editStatusSelect" class="form-control">
                                <option value="active" {{ old('status', $client->status) == 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ old('status', $client->status) == 'inactive' ? 'selected' : '' }}>Inactive</option>
                                <option value="pending" {{ old('status', $client->status) == 'pending' ? 'selected' : '' }}>Pending</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12" id="disableReasonCol" style="{{ old('status', $client->status) === 'inactive' ? '' : 'display:none;' }}">
                        <div class="form-group">
                            <label>Disable Reason <small class="text-muted">(optional)</small></label>
                            <div class="row">
                                <div class="col-md-6">
                                    <select class="form-control mb-1" id="disableReasonPreset" onchange="if(this.value){document.getElementById('statusReasonInput').value=this.value;}">
                                        <option value="">-- Select reason --</option>
                                        @foreach($disableReasons as $r)
                                        <option value="{{ $r }}" {{ old('status_reason') == $r ? 'selected' : '' }}>{{ $r }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <input type="text" name="status_reason" id="statusReasonInput" class="form-control" placeholder="Or type a reason..." value="{{ old('status_reason', $client->auto_inactive_note) }}">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-12" id="enableReasonCol" style="{{ old('status', $client->status) === 'active' && $client->getOriginal('status') === 'inactive' ? '' : 'display:none;' }}">
                        <div class="form-group">
                            <label>Enable Reason <small class="text-muted">(optional)</small></label>
                            <div class="row">
                                <div class="col-md-6">
                                    <select class="form-control mb-1" id="enableReasonPreset" onchange="if(this.value){document.getElementById('enableReasonInput').value=this.value;}">
                                        <option value="">-- Select reason --</option>
                                        @foreach($enableReasons as $r)
                                        <option value="{{ $r }}" {{ old('status_reason') == $r ? 'selected' : '' }}>{{ $r }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <input type="text" name="status_reason" id="enableReasonInput" class="form-control" placeholder="Or type a reason..." value="{{ old('status_reason') }}">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Joining Date</label>
                            <input type="text" name="connection_date" class="form-control flatpickr-date" value="{{ old('connection_date', $client->connection_date ? $client->connection_date->format('Y-m-d') : '') }}">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Connection Expiry Date <small class="text-muted">(optional)</small></label>
                            <div class="input-group">
                                <input type="text" id="expireDateEdit" name="expire_date" class="form-control flatpickr-date" value="{{ old('expire_date', $client->expire_date ? $client->expire_date->format('Y-m-d') : '') }}">
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('expireDateEdit')._flatpickr.clear();" title="Clear date"><i class="fas fa-times"></i></button>
                                </div>
                            </div>
                            <small class="text-muted">Leave blank for no expiry</small>
                        </div>
                    </div>
                </div>
                @if($client->temp_deadline_date)
                <div class="p-2 bg-warning-light border border-warning rounded mb-2" style="background: #fff3cd;">
                    <small class="text-dark"><i class="fas fa-calendar-plus text-warning"></i> <strong>Extended:</strong> {{ \Carbon\Carbon::parse($client->temp_deadline_date)->format('d-m-Y') }}
                    @if($client->temp_deadline_reason) | {{ $client->temp_deadline_reason }}@endif</small>
                </div>
                @endif
                <div class="custom-control custom-switch mb-2">
                    <input type="hidden" name="is_free" value="0">
                    <input type="checkbox" class="custom-control-input" id="is_free" name="is_free" value="1" {{ old('is_free', $client->is_free) ? 'checked' : '' }}>
                    <label class="custom-control-label" for="is_free">Free Client (No Monthly Bill)</label>
                </div>
                <div class="form-group" id="free_reason_group" style="{{ old('is_free', $client->is_free) ? '' : 'display:none' }}">
                    <label>Free Reason</label>
                    <input type="text" name="free_reason" class="form-control" value="{{ old('free_reason', $client->free_reason) }}" placeholder="e.g. Owner, Staff, Mosque">
                </div>
            </div>

            <div class="section-card">
                <div class="section-title"><i class="fas fa-tools"></i>Installation</div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Install Charge (৳)</label>
                            <input type="number" name="installation_charge" class="form-control" value="{{ old('installation_charge', $client->installation_charge ?? 0) }}" step="0.01" min="0">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Cable Length (m)</label>
                            <input type="number" name="cable_length_meters" class="form-control" value="{{ old('cable_length_meters', $client->cable_length_meters) }}" min="0">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="onu_required" name="onu_required" value="1" {{ old('onu_required', $client->onu_required) ? 'checked' : '' }}>
                            <label class="custom-control-label" for="onu_required">ISP ONU</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="router_required" name="router_required" value="1" {{ old('router_required', $client->router_required) ? 'checked' : '' }}>
                            <label class="custom-control-label" for="router_required">ISP Router</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="fiber_required" name="fiber_required" value="1" {{ old('fiber_required', $client->fiber_required ?? true) ? 'checked' : '' }}>
                            <label class="custom-control-label" for="fiber_required">ISP Fiber/UTP Cable</label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="section-card">
                <div class="section-title"><i class="fas fa-comment-alt"></i>Remarks</div>
                <div class="form-group mb-0">
                    <textarea name="remarks" class="form-control" rows="2" placeholder="Any additional notes...">{{ old('remarks', $client->remarks) }}</textarea>
                </div>
            </div>

            @if($client->technician_note)
            <div class="section-card" style="border-left: 3px solid #ffc107;">
                <div class="section-title"><i class="fas fa-sticky-note" style="color: #ffc107;"></i>Technician Note</div>
                <div class="p-2 bg-light rounded small" style="white-space: pre-wrap;">{{ $client->technician_note }}</div>
            </div>
            @endif
        </div>
    </div>

    <div class="bg-white border-top p-3 sticky-bottom" style="position: sticky; bottom: 0; z-index: 10; box-shadow: 0 -2px 8px rgba(0,0,0,0.08);">
        <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i>Update Client</button>
        <a href="{{ route('admin.clients.show', $client) }}" class="btn btn-secondary">Cancel</a>
    </div>
</form>

{{-- ONU Picker Modal --}}
<div class="modal fade" id="onuPickerModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background:linear-gradient(135deg,#1a1a2e,#16213e); color:#fff; padding:12px 20px;">
                <h5 class="modal-title"><i class="fas fa-broadcast-tower mr-2"></i>Pick ONU from OLT Scan</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body p-3">
                <div class="input-group mb-3">
                    <input type="text" id="onuPickerSearch" class="form-control" placeholder="Search by Serial / MAC / Port...">
                    <div class="input-group-append">
                        <div class="input-group-text">
                            <div class="custom-control custom-checkbox mb-0">
                                <input type="checkbox" class="custom-control-input" id="onuPickerUnassignedOnly">
                                <label class="custom-control-label" for="onuPickerUnassignedOnly" style="font-size:12px;">Only Unassigned</label>
                            </div>
                        </div>
                        <button type="button" class="btn btn-primary" id="onuPickerSearchBtn"><i class="fas fa-search"></i></button>
                    </div>
                </div>
                <div id="onuPickerLoading" class="text-center py-4" style="display:none;">
                    <i class="fas fa-spinner fa-spin fa-2x text-info"></i>
                    <p class="mt-2 text-muted">Loading OLT list...</p>
                </div>
                <div id="onuPickerTableWrap" style="max-height:400px; overflow-y:auto;">
                    <table class="table table-sm table-hover table-bordered mb-0" id="onuPickerTable">
                        <thead style="background:#f0f0f0; position:sticky; top:0;">
                            <tr>
                                <th>Serial</th>
                                <th>MAC</th>
                                <th>Port</th>
                                <th>Model</th>
                                <th>RX Power</th>
                                <th>Status</th>
                                <th>Client</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="onuPickerBody">
                            <tr><td colspan="8" class="text-center text-muted py-3">Click search or scan to load ONUs...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer py-2 d-flex justify-content-between align-items-center flex-wrap">
                <small class="text-muted"><i class="fas fa-info-circle mr-1"></i>Selecting an ONU will auto-fill the form</small>
                <div>
                    <button type="button" class="btn btn-warning btn-sm mr-2" id="btnScanOltOnu" onclick="scanOltAndRefresh()">
                        <i class="fas fa-sync-alt mr-1"></i>OLT Scan
                    </button>
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- OLT Scan Select Modal --}}
<div class="modal fade" id="oltScanSelectModal" tabindex="-1" role="dialog" style="z-index:1060;">
    <div class="modal-dialog modal-dialog-centered" style="max-width:460px;" role="document">
        <div class="modal-content" style="border:none; border-radius:16px; overflow:hidden; box-shadow:0 20px 60px rgba(0,0,0,0.25);">
            <div class="modal-header" style="background:linear-gradient(135deg,#1a1a2e 0%,#16213e 50%,#0f3460 100%); color:#fff; padding:20px 24px; border:none;">
                <div class="d-flex align-items-center">
                    <div style="width:42px;height:42px;background:rgba(255,255,255,0.15);border-radius:10px;display:flex;align-items:center;justify-content:center;margin-right:14px;">
                        <i class="fas fa-satellite-dish" style="font-size:20px;color:#7ee8fa;"></i>
                    </div>
                    <div>
                        <h5 class="mb-0" style="font-weight:700;font-size:17px;letter-spacing:0.3px;">OLT Scan</h5>
                        <small style="opacity:0.7;font-size:12px;">Select an OLT device to scan</small>
                    </div>
                </div>
                <button type="button" class="close text-white ml-auto" data-dismiss="modal" style="opacity:0.7;font-size:22px;"><span>&times;</span></button>
            </div>
            <div class="modal-body" style="padding:24px;">
                <div id="oltScanCardList" style="display:flex;flex-direction:column;gap:10px;max-height:320px;overflow-y:auto;padding-right:2px;"></div>
                <div id="oltScanNoSelection" class="text-danger small mt-2" style="display:none;">
                    <i class="fas fa-exclamation-circle mr-1"></i>Please select an OLT first.
                </div>
            </div>
            <div class="modal-footer" style="padding:16px 24px;border-top:1px solid #f0f0f0;background:#fafafa;">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal" style="border-radius:8px;padding:8px 20px;font-weight:600;">
                    <i class="fas fa-times mr-1"></i>Cancel
                </button>
                <button type="button" class="btn btn-primary btn-sm" id="btnConfirmOltScan" style="border-radius:8px;padding:8px 22px;font-weight:600;background:linear-gradient(135deg,#667eea,#764ba2);border:none;box-shadow:0 4px 12px rgba(102,126,234,0.4);">
                    <i class="fas fa-sync-alt mr-1"></i>Scan
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.olt-scan-card { display:block; cursor:pointer; margin:0; }
.olt-scan-card-inner {
    display:flex; align-items:center; padding:14px 16px;
    border:2px solid #e9ecef; border-radius:12px; background:#fff;
    transition:all 0.2s ease; position:relative;
}
.olt-scan-card:hover .olt-scan-card-inner { border-color:#667eea; background:#f8f7ff; }
.olt-scan-card input:checked ~ .olt-scan-card-inner {
    border-color:#667eea; background:linear-gradient(135deg,#f0eeff,#e8f0fe);
    box-shadow:0 4px 16px rgba(102,126,234,0.2);
}
.olt-scan-card-icon {
    width:40px; height:40px; border-radius:10px; display:flex; align-items:center;
    justify-content:center; background:linear-gradient(135deg,#667eea,#764ba2);
    margin-right:14px; flex-shrink:0;
}
.olt-scan-card-icon i { color:#fff; font-size:16px; }
.olt-scan-card-name { font-weight:600; font-size:15px; color:#1a1a2e; flex:1; }
.olt-scan-card-check { color:#667eea; font-size:18px; opacity:0; transition:opacity 0.2s; }
.olt-scan-card input:checked ~ .olt-scan-card-inner .olt-scan-card-check { opacity:1; }
</style>

<script>
$(document).on('click', '.olt-scan-card', function(){
    $(this).find('.olt-scan-radio').prop('checked', true);
    $('#oltScanNoSelection').hide();
});
$('#btnConfirmOltScan').on('click', function(){
    var selected = $('input[name="oltScanChoice"]:checked').val();
    if (!selected) { $('#oltScanNoSelection').show(); return; }
    $('#oltScanSelectModal').modal('hide');
    setTimeout(function(){ doScanOlt(selected); }, 350);
});
$('#oltScanSelectModal').on('hidden.bs.modal', function(){
    $('input[name="oltScanChoice"]').prop('checked', false);
    $('#oltScanNoSelection').hide();
});
</script>


@endsection

@push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
document.getElementById('editStatusSelect').addEventListener('change', function() {
    var disableCol = document.getElementById('disableReasonCol');
    var enableCol = document.getElementById('enableReasonCol');
    disableCol.style.display = this.value === 'inactive' ? '' : 'none';
    enableCol.style.display = this.value === 'active' ? '' : 'none';
});
</script>
<script>
$(document).on('click', '.toggle-password', function() {
    var input = $($(this).data('target'));
    var icon = $(this).find('i');
    if (input.attr('type') === 'password') {
        input.attr('type', 'text');
        icon.removeClass('fa-eye').addClass('fa-eye-slash');
    } else {
        input.attr('type', 'password');
        icon.removeClass('fa-eye-slash').addClass('fa-eye');
    }
});

$('#editAgentSelect').on('change', function() {
    var commission = $(this).find('option:selected').data('commission');
    if (commission && !$('#editCommissionPercent').val()) {
        $('#editCommissionPercent').val(commission);
    } else if (!$(this).val()) {
        $('#editCommissionPercent').val('');
    }
});

document.getElementById('is_free').addEventListener('change', function() {
    document.getElementById('free_reason_group').style.display = this.checked ? '' : 'none';
    var monthlyFeeField = document.getElementById('monthly_fee');
    if (this.checked) {
        monthlyFeeField.dataset.originalValue = monthlyFeeField.value;
        monthlyFeeField.value = '0.00';
        monthlyFeeField.setAttribute('readonly', 'readonly');
    } else {
        monthlyFeeField.value = monthlyFeeField.dataset.originalValue || monthlyFeeField.value;
        monthlyFeeField.removeAttribute('readonly');
    }
});

(function() {
    var divisionSel = document.getElementById('division_id');
    var districtSel = document.getElementById('district_id');
    var upazilaSel = document.getElementById('upazila_id');
    var zoneSel = document.getElementById('zone_id');
    var areaSel = document.getElementById('area_id');
    var boxSel = document.getElementById('box_id');

    function resetFrom(level) {
        if (level <= 2) { districtSel.innerHTML = '<option value="">-- Select Division First --</option>'; }
        if (level <= 3) { upazilaSel.innerHTML = '<option value="">-- Select District First --</option>'; }
        if (level <= 4) { zoneSel.innerHTML = '<option value="">-- Select Thana/Upazila First --</option>'; }
        if (level <= 5) { areaSel.innerHTML = '<option value="">-- Select Zone First --</option>'; }
        if (level <= 6) { boxSel.innerHTML = '<option value="">-- Select Sub Zone First --</option>'; }
    }

    function loadOptions(url, select, placeholder, callback) {
        select.innerHTML = '<option value="">Loading...</option>';
        fetch(url)
            .then(function(r) { return r.json(); })
            .then(function(data) {
                select.innerHTML = '<option value="">' + placeholder + '</option>';
                data.forEach(function(item) {
                    var opt = document.createElement('option');
                    opt.value = item.id;
                    opt.textContent = item.name;
                    select.appendChild(opt);
                });
                if (callback) callback(data);
            })
            .catch(function() {
                select.innerHTML = '<option value="">' + placeholder + '</option>';
            });
    }

    divisionSel.addEventListener('change', function() {
        resetFrom(2);
        if (this.value) {
            loadOptions('/admin/api/districts-by-division/' + encodeURIComponent(this.value), districtSel, '-- Select District --');
        }
    });

    districtSel.addEventListener('change', function() {
        resetFrom(3);
        if (this.value) {
            loadOptions('/admin/api/upazilas-by-district/' + this.value, upazilaSel, '-- Select Thana/Upazila --');
        }
    });

    upazilaSel.addEventListener('change', function() {
        resetFrom(4);
        if (this.value) {
            loadOptions('/admin/api/zones-by-upazila/' + this.value, zoneSel, '-- Select Zone --');
        }
    });

    zoneSel.addEventListener('change', function() {
        resetFrom(5);
        if (this.value) {
            loadOptions('/admin/api/areas-by-zone/' + this.value, areaSel, '-- Select Sub Zone --');
        }
    });

    areaSel.addEventListener('change', function() {
        resetFrom(6);
        if (this.value) {
            loadOptions('/admin/api/boxes-by-area/' + this.value, boxSel, '-- Select TJ --');
        }
    });
})();

function generatePassword() {
    var chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789';
    var p = '';
    for (var i = 0; i < 8; i++) p += chars.charAt(Math.floor(Math.random() * chars.length));
    document.getElementById('pppoe_password').value = p;
}

function updateMapButton() {
    var lat = document.getElementById('lat').value, lng = document.getElementById('lng').value;
    var btn = document.getElementById('viewMapBtn');
    if (lat && lng) { btn.href = 'https://www.google.com/maps?q=' + lat + ',' + lng; btn.style.display = 'block'; }
    else { btn.style.display = 'none'; }
}

function showLocationStatus(msg, type) {
    var s = document.getElementById('locationStatus');
    s.className = 'alert py-1 mb-2 small alert-' + type;
    s.innerHTML = msg; s.style.display = 'block';
}

function getLocationFromAddress() {
    var address = document.getElementById('address').value.trim();
    if (!address) { showLocationStatus('<i class="fas fa-exclamation-triangle mr-1"></i>Enter address first.', 'warning'); return; }
    showLocationStatus('<i class="fas fa-spinner fa-spin mr-1"></i>Searching...', 'info');
    var key = '37e22662c7614c5996dacf00d6279b95';
    var q = address + ', Dhaka, Bangladesh';
    fetch('https://api.opencagedata.com/geocode/v1/json?q=' + encodeURIComponent(q) + '&key=' + key + '&countrycode=bd&limit=5&language=en&no_annotations=1')
    .then(r=>r.json()).then(function(d){
        if (d.results && d.results.length > 0) {
            var r = d.results[0];
            document.getElementById('lat').value = r.geometry.lat.toFixed(6);
            document.getElementById('lng').value = r.geometry.lng.toFixed(6);
            document.getElementById('google_map_location').value = 'https://www.google.com/maps/search/?api=1&query=' + encodeURIComponent(r.formatted);
            updateMapButton();
            showLocationStatus('<i class="fas fa-check-circle mr-1"></i>Found: ' + r.formatted, 'success');
        } else {
            showLocationStatus('<i class="fas fa-times-circle mr-1"></i>Not found. Try with area name.', 'danger');
        }
    }).catch(function(){ showLocationStatus('<i class="fas fa-times-circle mr-1"></i>Error. Check internet.', 'danger'); });
}

updateMapButton();

function filterByRouter(routerName) {
    var packageSelect = document.getElementById('package_id');
    var profileSelect = document.getElementById('mikrotik_profile_name');
    var currentPkg = packageSelect.value;
    var currentProfile = profileSelect.value;

    for (var i = 0; i < packageSelect.options.length; i++) {
        var opt = packageSelect.options[i];
        if (!opt.value) continue;
        var src = opt.dataset.sourceDevice || '';
        if (!routerName || src === routerName) {
            opt.style.display = '';
            opt.disabled = false;
        } else {
            opt.style.display = 'none';
            opt.disabled = true;
            if (currentPkg === opt.value) packageSelect.value = '';
        }
    }

    for (var i = 0; i < profileSelect.options.length; i++) {
        var opt = profileSelect.options[i];
        if (!opt.value) continue;
        var src = opt.dataset.sourceDevice || '';
        if (!routerName || src === routerName) {
            opt.style.display = '';
            opt.disabled = false;
        } else {
            opt.style.display = 'none';
            opt.disabled = true;
            if (currentProfile === opt.value) profileSelect.value = '';
        }
    }
}

document.getElementById('mikrotik_device_id').addEventListener('change', function() {
    var opt = this.selectedOptions[0];
    var routerName = opt ? (opt.dataset.deviceName || '') : '';
    filterByRouter(routerName);
});

document.getElementById('package_id').addEventListener('change', function() {
    var opt = this.selectedOptions[0];
    var monthlyFee = document.getElementById('monthly_fee');
    if (monthlyFee && opt.dataset.price) monthlyFee.value = opt.dataset.price;
    var profile = opt.dataset.mikrotikProfile || '';
    var profileSelect = document.getElementById('mikrotik_profile_name');
    if (profile) {
        var found = false;
        for (var i = 0; i < profileSelect.options.length; i++) {
            if (profileSelect.options[i].value === profile && !profileSelect.options[i].disabled) {
                profileSelect.value = profile;
                found = true;
                break;
            }
        }
        if (!found) profileSelect.value = '';
    } else {
        profileSelect.value = '';
    }
});

(function() {
    var routerSelect = document.getElementById('mikrotik_device_id');
    var selectedOpt = routerSelect.selectedOptions[0];
    if (selectedOpt && selectedOpt.value) {
        var routerName = selectedOpt.dataset.deviceName || '';
        if (routerName) filterByRouter(routerName);
    }
})();
</script>

<script>
// ====== ONU Picker ======
$('#btnPickOnu').on('click', function() {
    _cachedOnus = null;
    $('#onuPickerModal').modal('show');
    loadOnuPickerList();
});

$('#onuPickerSearchBtn').on('click', function() {
    if (_cachedOnus && _cachedOnus.length > 0) {
        renderOnuTable(_cachedOnus);
    } else {
        _cachedOnus = null;
        loadOnuPickerList();
    }
});

$('#onuPickerUnassignedOnly').on('change', function() {
    if (_cachedOnus && _cachedOnus.length > 0) {
        renderOnuTable(_cachedOnus);
    } else {
        _cachedOnus = null;
        loadOnuPickerList();
    }
});

$('#onuPickerSearch').on('keypress', function(e) {
    if (e.which === 13) {
        e.preventDefault();
        if (_cachedOnus && _cachedOnus.length > 0) {
            renderOnuTable(_cachedOnus);
        } else {
            _cachedOnus = null;
            loadOnuPickerList();
        }
    }
});

function renderOnuTable(onus) {
    if (!onus || onus.length === 0) {
        $('#onuPickerBody').html('<tr><td colspan="8" class="text-center text-muted py-3"><i class="fas fa-info-circle mr-2"></i>No ONUs found. Run OLT Scan first.</td></tr>');
        return;
    }
    var search = ($('#onuPickerSearch').val() || '').toLowerCase();
    var unassignedOnly = $('#onuPickerUnassignedOnly').is(':checked');
    var html = '';
    var count = 0;
    $.each(onus, function(i, o) {
        if (unassignedOnly && o.client_id) return true;
        if (search && !(
            (o.serial_number || '').toLowerCase().indexOf(search) >= 0 ||
            (o.mac_address || '').toLowerCase().indexOf(search) >= 0 ||
            (o.pon_port || '').toLowerCase().indexOf(search) >= 0 ||
            (o.model || '').toLowerCase().indexOf(search) >= 0
        )) return true;
        var statusBadge = o.status === 'online'
            ? '<span class="badge badge-success" style="font-size:11px;">Online</span>'
            : '<span class="badge badge-secondary" style="font-size:11px;">Offline</span>';
        var clientInfo = o.client_username
            ? '<span class="badge badge-warning" style="font-size:11px;"><i class="fas fa-user mr-1"></i>' + o.client_username + '</span>'
            : '<span class="text-muted" style="font-size:11px;">—</span>';
        var rxStr = o.rx_power !== null ? o.rx_power + ' dBm' : '—';
        var rxColor = o.rx_power !== null
            ? (o.rx_power >= -15 ? 'color:#28a745' : (o.rx_power >= -20 ? 'color:#17a2b8' : 'color:#ffc107'))
            : '';
        html += '<tr style="cursor:pointer;" onclick="pickOnu(' + JSON.stringify(o).replace(/"/g, '&quot;') + ')">';
        html += '<td><code style="font-size:12px;">' + (o.serial_number || '—') + '</code></td>';
        html += '<td><code style="font-size:11px;">' + (o.mac_address || '—') + '</code></td>';
        html += '<td><strong class="text-success">' + (o.pon_port || '—') + '</strong></td>';
        html += '<td>' + (o.model || '—') + '</td>';
        html += '<td style="' + rxColor + '; font-weight:600;">' + rxStr + '</td>';
        html += '<td>' + statusBadge + '</td>';
        html += '<td>' + clientInfo + '</td>';
        html += '<td><button type="button" class="btn btn-xs btn-success" style="white-space:nowrap; padding:2px 8px; font-size:12px;"><i class="fas fa-check mr-1"></i>Select</button></td>';
        html += '</tr>';
        count++;
    });
    if (count === 0) {
        html = '<tr><td colspan="8" class="text-center text-muted py-3"><i class="fas fa-info-circle mr-2"></i>No matching ONUs found.</td></tr>';
    }
    $('#onuPickerBody').html(html);
}

var _cachedOnus = null;

function loadOnuPickerList() {
    if (_cachedOnus && _cachedOnus.length > 0) {
        renderOnuTable(_cachedOnus);
        $('#onuPickerLoading').hide();
        return;
    }

    $('#onuPickerLoading').show();
    $('#onuPickerBody').html('');

    var xhr = new XMLHttpRequest();
    var params = 'search=' + encodeURIComponent($('#onuPickerSearch').val() || '') +
                 '&unassigned_only=' + ($('#onuPickerUnassignedOnly').is(':checked') ? '1' : '0') +
                 '&olt_device_id=' + encodeURIComponent($('#edit_olt_device_id').val() || '');
    xhr.open('GET', '/admin/network/onus/picker-list?' + params, true);
    xhr.setRequestHeader('Accept', 'application/json');
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    xhr.timeout = 15000;

    xhr.onload = function() {
        $('#onuPickerLoading').hide();
        try {
            var res = JSON.parse(xhr.responseText);
            if (res.success && res.data && res.data.length > 0) {
                _cachedOnus = res.data;
                renderOnuTable(res.data);
            } else if (res.success) {
                $('#onuPickerBody').html('<tr><td colspan="8" class="text-center text-muted py-3"><i class="fas fa-info-circle mr-2"></i>No ONUs found. Run OLT Scan first.</td></tr>');
            } else {
                $('#onuPickerBody').html('<tr><td colspan="8" class="text-center text-danger py-3">' + (res.message || 'Error loading ONUs') + '</td></tr>');
            }
        } catch(e) {
            if (xhr.status === 401 || xhr.status === 419) {
                $('#onuPickerBody').html('<tr><td colspan="8" class="text-center text-warning py-3"><i class="fas fa-lock mr-2"></i>Session expired. Please refresh the page.</td></tr>');
            } else {
                $('#onuPickerBody').html('<tr><td colspan="8" class="text-center text-danger py-3"><i class="fas fa-exclamation-triangle mr-2"></i>Failed to load ONU list.</td></tr>');
            }
        }
    };

    xhr.onerror = function() {
        $('#onuPickerLoading').hide();
        $('#onuPickerBody').html('<tr><td colspan="8" class="text-center text-danger py-3"><i class="fas fa-exclamation-triangle mr-2"></i>Network error. Check connection.</td></tr>');
    };

    xhr.ontimeout = function() {
        $('#onuPickerLoading').hide();
        $('#onuPickerBody').html('<tr><td colspan="8" class="text-center text-danger py-3"><i class="fas fa-exclamation-triangle mr-2"></i>Request timeout.</td></tr>');
    };

    xhr.send();
}

function pickOnu(o) {
    if (o.serial_number) $('#edit_onu_serial').val(o.serial_number);
    if (o.mac_address)   { $('#edit_onu_mac').val(o.mac_address); $('#fiber_onu_mac_display').val(o.mac_address); }
    if (o.pon_port)      $('#edit_olt_port').val(o.pon_port);
    if (o.model)         $('#edit_onu_model').val(o.model);
    if (o.olt_device_id) $('#edit_olt_device_id').val(o.olt_device_id);

    $('#onuPickerModal').modal('hide');
    toastr.success('ONU "' + (o.serial_number || o.mac_address) + '" selected! Save the form to apply.', 'ONU Selected');

    $('#edit_onu_serial').css({'border-color':'#28a745','box-shadow':'0 0 0 0.2rem rgba(40,167,69,0.25)'});
    setTimeout(function() { $('#edit_onu_serial').css({'border-color':'','box-shadow':''}); }, 3000);
}

function unselectOnu() {
    Swal.fire({
        title: 'Unselect ONU?',
        text: 'ONU Serial, MAC, Port, Model will be cleared. Save the form to detach ONU from this client.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Yes, Unselect',
        cancelButtonText: 'Cancel'
    }).then(function(result) {
        if (result.isConfirmed) {
            $('#edit_onu_serial').val('');
            $('#edit_onu_mac').val('');
            $('#edit_olt_port').val('');
            $('#edit_onu_model').val('');
            $('#edit_olt_device_id').val('');
            $('#fiber_onu_mac_display').val('');
            $('#edit_onu_serial').css({'border-color':'#dc3545','box-shadow':'0 0 0 0.2rem rgba(220,53,69,0.25)'});
            setTimeout(function() { $('#edit_onu_serial').css({'border-color':'','box-shadow':''}); }, 3000);
            toastr.info('ONU cleared. Save the form to apply.', 'ONU Unselected');
        }
    });
}

var _oltDevices = @json($oltDevices->map(function($o){ return ['id'=>$o->id,'name'=>$o->name]; }));

function scanOltAndRefresh() {
    if (!_oltDevices || _oltDevices.length === 0) {
        toastr.error('No OLT devices found.', 'Error');
        return;
    }

    var oltId = null;
    if (_oltDevices.length === 1) {
        oltId = _oltDevices[0].id;
        doScanOlt(oltId);
    } else {
        $('#onuPickerModal').modal('hide');
        var html = '';
        _oltDevices.forEach(function(d){
            html += '<label class="olt-scan-card" for="oltCard_' + d.id + '">' +
                '<input type="radio" name="oltScanChoice" id="oltCard_' + d.id + '" value="' + d.id + '" class="d-none olt-scan-radio">' +
                '<div class="olt-scan-card-inner">' +
                '<div class="olt-scan-card-icon"><i class="fas fa-broadcast-tower"></i></div>' +
                '<div class="olt-scan-card-name">' + d.name + '</div>' +
                '<div class="olt-scan-card-check"><i class="fas fa-check-circle"></i></div>' +
                '</div>' +
                '</label>';
        });
        $('#oltScanCardList').html(html);
        $('#oltScanSelectModal').modal('show');
    }
}

function doScanOlt(oltId) {
    var btn = $('#btnScanOltOnu');
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Scanning...');

    $('#onuPickerModal').modal('hide');

    Swal.fire({
        title: 'OLT Scan',
        html: '<div class="text-center"><i class="fas fa-spinner fa-spin fa-2x text-primary mb-2"></i><p>Connecting to OLT and fetching ONU data...<br><small class="text-muted">(may take 10-30 seconds)</small></p></div>',
        allowOutsideClick: false,
        showConfirmButton: false
    });

    var xhr = new XMLHttpRequest();
    xhr.open('POST', '/admin/network/olt/' + oltId + '/sync-onus', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.setRequestHeader('X-CSRF-TOKEN', $('meta[name="csrf-token"]').attr('content'));
    xhr.setRequestHeader('Accept', 'application/json');
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    xhr.timeout = 90000;

    xhr.onload = function() {
        btn.prop('disabled', false).html('<i class="fas fa-sync-alt mr-1"></i>OLT Scan');
        try {
            var res = JSON.parse(xhr.responseText);
            if (res.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Scan Complete!',
                    text: res.message || 'ONU data synced.',
                    timer: 2000,
                    showConfirmButton: false
                });
                if (res.onus && res.onus.length > 0) {
                    _cachedOnus = res.onus;
                }
                setTimeout(function() {
                    $('#onuPickerModal').modal('show');
                    if (_cachedOnus && _cachedOnus.length > 0) {
                        renderOnuTable(_cachedOnus);
                    } else {
                        loadOnuPickerList();
                    }
                }, 2100);
            } else {
                Swal.fire('Warning', res.message || 'OLT Scan issue.', 'warning').then(function() {
                    $('#onuPickerModal').modal('show');
                });
            }
        } catch(e) {
            if (xhr.status === 401 || xhr.status === 419) {
                Swal.fire('Session Expired', 'Please refresh the page and login again.', 'error');
            } else {
                Swal.fire('Error', 'Invalid response from server (HTTP ' + xhr.status + '). Please refresh and try again.', 'error');
            }
        }
    };

    xhr.onerror = function() {
        btn.prop('disabled', false).html('<i class="fas fa-sync-alt mr-1"></i>OLT Scan');
        Swal.fire('Network Error', 'Could not connect to server. Check your internet connection.', 'error');
    };

    xhr.ontimeout = function() {
        btn.prop('disabled', false).html('<i class="fas fa-sync-alt mr-1"></i>OLT Scan');
        Swal.fire('Timeout', 'OLT not responding. Check if OLT is online and reachable.', 'error');
    };

    xhr.send('_token=' + encodeURIComponent($('meta[name="csrf-token"]').attr('content')));
}
</script>
<script>
// ── OpenCage Address Autocomplete ──
(function(){
    var _oc_timer = null, _oc_key = '37e22662c7614c5996dacf00d6279b95';
    var addrEl = document.getElementById('address');
    if (!addrEl) return;

    // Create suggestions dropdown
    var sug = document.createElement('div');
    sug.id = 'ocSuggestions';
    sug.style.cssText = 'position:absolute;z-index:9999;background:#fff;border:1px solid #ced4da;border-radius:6px;box-shadow:0 4px 16px rgba(0,0,0,0.12);max-height:220px;overflow-y:auto;display:none;width:100%;left:0;top:100%;margin-top:2px;';
    var wrapper = addrEl.parentNode;
    if (wrapper.style.position !== 'relative') wrapper.style.position = 'relative';
    wrapper.appendChild(sug);

    addrEl.addEventListener('input', function(){
        clearTimeout(_oc_timer);
        var q = addrEl.value.trim();
        if (q.length < 4) { sug.style.display='none'; return; }
        _oc_timer = setTimeout(function(){
            fetch('https://api.opencagedata.com/geocode/v1/json?q=' + encodeURIComponent(q + ' Bangladesh') +
                  '&key=' + _oc_key + '&countrycode=bd&limit=6&language=en&no_annotations=1&abbrv=1')
            .then(r=>r.json()).then(function(d){
                sug.innerHTML = '';
                if (!d.results || !d.results.length) { sug.style.display='none'; return; }
                d.results.forEach(function(r){
                    var item = document.createElement('div');
                    item.style.cssText = 'padding:8px 12px;cursor:pointer;font-size:13px;border-bottom:1px solid #f1f5f9;color:#1e293b;';
                    item.innerHTML = '<i class="fas fa-map-marker-alt text-danger mr-2" style="font-size:11px;"></i>' + r.formatted;
                    item.addEventListener('mouseenter', function(){ item.style.background='#f0f9ff'; });
                    item.addEventListener('mouseleave', function(){ item.style.background='#fff'; });
                    item.addEventListener('mousedown', function(e){
                        e.preventDefault();
                        addrEl.value = r.formatted;
                        document.getElementById('lat').value = r.geometry.lat.toFixed(6);
                        document.getElementById('lng').value = r.geometry.lng.toFixed(6);
                        document.getElementById('google_map_location').value = 'https://www.google.com/maps/search/?api=1&query=' + encodeURIComponent(r.formatted);
                        updateMapButton();
                        showLocationStatus('<i class="fas fa-check-circle mr-1"></i>' + r.formatted, 'success');
                        sug.style.display = 'none';
                    });
                    sug.appendChild(item);
                });
                sug.style.display = 'block';
            }).catch(function(){});
        }, 550);
    });

    addrEl.addEventListener('blur', function(){ setTimeout(function(){ sug.style.display='none'; }, 200); });
    addrEl.addEventListener('focus', function(){ if(sug.children.length) sug.style.display='block'; });
})();
</script>

<script>
(function() {
    var fiberColors = {
        'blue':'#0057B8','orange':'#FF8C00','green':'#28a745','brown':'#8B4513',
        'slate':'#708090','white':'#e9ecef','red':'#dc3545','black':'#222222',
        'yellow':'#ffc107','violet':'#6f42c1','rose':'#e83e8c','aqua':'#20c997'
    };
    function updateDot(inputId) {
        var val = (document.getElementById(inputId)||{}).value || '';
        var dot = document.getElementById('dot_' + inputId);
        if (!dot) return;
        var hex = fiberColors[val.toLowerCase().trim()];
        dot.style.background = hex || '#ccc';
        dot.style.border = val.toLowerCase()==='white' ? '1px solid #aaa' : '1px solid rgba(0,0,0,0.15)';
    }
    function closeAllPalettes() {
        document.querySelectorAll('.fiber-color-palette').forEach(function(p){ p.style.display='none'; });
    }
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.input-group')) closeAllPalettes();
        if (e.target.classList.contains('fiber-color-btn') || e.target.closest('.fiber-color-btn')) {
            e.stopPropagation();
            var btn = e.target.classList.contains('fiber-color-btn') ? e.target : e.target.closest('.fiber-color-btn');
            var targetId = btn.dataset.target;
            var palette = document.getElementById('palette_' + targetId);
            var isOpen = palette.style.display !== 'none';
            closeAllPalettes();
            if (!isOpen) palette.style.display = 'block';
        }
        if (e.target.classList.contains('fiber-color-swatch')) {
            var swatch = e.target;
            var targetId = swatch.dataset.target;
            var input = document.getElementById(targetId);
            if (input) { input.value = swatch.dataset.color; updateDot(targetId); }
            closeAllPalettes();
        }
    });
    document.querySelectorAll('[id$="_color_input"], #fiber_color_input, #splitter_color_input').forEach(function(inp) {
        inp.addEventListener('input', function(){ updateDot(inp.id); });
        updateDot(inp.id);
    });
    // init all fiber input dots on page load
    setTimeout(function(){
        ['fiber_color_input','splitter_color_input'].forEach(updateDot);
        // Click on readonly input also opens palette
        ['fiber_color_input','splitter_color_input'].forEach(function(id){
            var inp = document.getElementById(id);
            if(inp) inp.addEventListener('click', function(e){
                e.stopPropagation();
                var btn = this.closest('.input-group').querySelector('.fiber-color-btn');
                if(btn) btn.click();
            });
        });
    }, 100);
})();
</script>

@endpush
