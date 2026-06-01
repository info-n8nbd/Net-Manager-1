@extends('layouts.admin')

@section('title', 'Add Client')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.clients.index') }}">Clients</a></li>
<li class="breadcrumb-item active">Add</li>
@endsection

@push('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<style>
    .section-title { font-size: 14px; font-weight: 700; color: #495057; margin-bottom: 12px; padding-bottom: 6px; border-bottom: 2px solid #e9ecef; }
    .section-title i { color: #007bff; margin-right: 6px; }
    .compact-form .form-group { margin-bottom: 10px; }
    .compact-form label { font-size: 12px; font-weight: 600; color: #6c757d; margin-bottom: 2px; text-transform: uppercase; letter-spacing: 0.3px; }
    .compact-form .form-control, .compact-form .custom-file-label { font-size: 13px; padding: 4px 8px; height: calc(1.8rem + 2px); }
    .compact-form textarea.form-control { height: auto; }
    .compact-form .form-control-file { font-size: 12px; }
    .compact-form select.form-control { padding: 4px 8px; }
    .compact-form .input-group .btn { padding: 4px 10px; font-size: 13px; }
    .compact-form .form-text, .compact-form small { font-size: 11px; }
    .compact-form .custom-control-label { font-size: 13px; }
    .section-card { border: 1px solid #dee2e6; border-radius: 6px; padding: 14px; margin-bottom: 14px; background: #fff; }
    .section-card.highlight { border-left: 3px solid #007bff; }
</style>
@endpush

@section('content')
<form action="{{ route('admin.clients.store') }}" method="POST" enctype="multipart/form-data" class="compact-form">
    @csrf
    @if($errors->any())
    <div class="alert alert-danger py-2 mb-3">
        <ul class="mb-0 small">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
    @endif

    <div class="row">
        <div class="col-lg-4 col-md-6">
            <div class="section-card highlight">
                <div class="section-title"><i class="fas fa-user"></i>Personal Info</div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>First Name <span class="text-danger">*</span></label>
                            <input type="text" name="first_name" class="form-control" value="{{ old('first_name') }}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Last Name <span class="text-danger">*</span></label>
                            <input type="text" name="last_name" class="form-control" value="{{ old('last_name') }}" required>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Father's Name</label>
                            <input type="text" name="father_husband_name" class="form-control" value="{{ old('father_husband_name') }}">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Mother's Name</label>
                            <input type="text" name="mother_name" class="form-control" value="{{ old('mother_name') }}">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Date of Birth</label>
                            <input type="text" name="date_of_birth" class="form-control flatpickr-date" value="{{ old('date_of_birth') }}">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Gender</label>
                            <select name="gender" class="form-control">
                                <option value="">Select</option>
                                <option value="male" {{ old('gender') == 'male' ? 'selected' : '' }}>Male</option>
                                <option value="female" {{ old('gender') == 'female' ? 'selected' : '' }}>Female</option>
                                <option value="other" {{ old('gender') == 'other' ? 'selected' : '' }}>Other</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Phone <span class="text-danger">*</span></label>
                            <input type="text" name="phone" class="form-control" value="{{ old('phone') }}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Alt Phone</label>
                            <input type="text" name="alt_phone" class="form-control" value="{{ old('alt_phone') }}">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" class="form-control" value="{{ old('email') }}">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>NID Number</label>
                            <input type="text" name="nid" class="form-control" value="{{ old('nid') }}">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Occupation</label>
                            <input type="text" name="occupation" class="form-control" value="{{ old('occupation') }}" placeholder="e.g. Business">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Client Type</label>
                            <select name="client_type" class="form-control">
                                <option value="regular" {{ old('client_type') == 'regular' ? 'selected' : '' }}>Regular</option>
                                <option value="corporate" {{ old('client_type') == 'corporate' ? 'selected' : '' }}>Corporate</option>
                                <option value="home" {{ old('client_type') == 'home' ? 'selected' : '' }}>Home</option>
                                <option value="business" {{ old('client_type') == 'business' ? 'selected' : '' }}>Business</option>
                                <option value="government" {{ old('client_type') == 'government' ? 'selected' : '' }}>Government</option>
                                <option value="vip" {{ old('client_type') == 'vip' ? 'selected' : '' }}>VIP</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label>Address <span class="text-danger">*</span></label>
                    <textarea name="address" class="form-control" rows="2" required id="address">{{ old('address') }}</textarea>
                </div>
            </div>

            <div class="section-card">
                <div class="section-title"><i class="fas fa-id-card"></i>Documents</div>
                <div class="form-group">
                    <label>Profile Picture</label>
                    <input type="file" name="profile_picture" class="form-control-file" accept="image/*">
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>NID Front</label>
                            <input type="file" name="nid_front_image" class="form-control-file" accept="image/*">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>NID Back</label>
                            <input type="file" name="nid_back_image" class="form-control-file" accept="image/*">
                        </div>
                    </div>
                </div>
            </div>

            <div class="section-card">
                <div class="section-title"><i class="fas fa-map-marker-alt"></i>Location</div>
                <div class="alert alert-info py-1 mb-2 small" id="locationStatus" style="display: none;"></div>
                <input type="hidden" name="google_map_location" id="google_map_location" value="{{ old('google_map_location') }}">
                <div class="row">
                    <div class="col-md-5">
                        <div class="form-group">
                            <label>Latitude</label>
                            <input type="text" name="google_map_lat" class="form-control" value="{{ old('google_map_lat') }}" id="lat" readonly>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="form-group">
                            <label>Longitude</label>
                            <input type="text" name="google_map_lng" class="form-control" value="{{ old('google_map_lng') }}" id="lng" readonly>
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
                            <label>Client Code <small class="text-muted" id="nextCodeHint"></small></label>
                            <input type="text" name="client_id" class="form-control" value="{{ old('client_id') }}" id="clientCodeField" placeholder="Auto (generating...)">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Connection Type <span class="text-danger">*</span></label>
                            <select name="connection_type" class="form-control" required>
                                <option value="pppoe" {{ old('connection_type') == 'pppoe' ? 'selected' : '' }}>PPPoE</option>
                                <option value="static" {{ old('connection_type') == 'static' ? 'selected' : '' }}>Static IP</option>
                                <option value="dhcp" {{ old('connection_type') == 'dhcp' ? 'selected' : '' }}>DHCP</option>
                                <option value="hotspot" {{ old('connection_type') == 'hotspot' ? 'selected' : '' }}>Hotspot</option>
                                <option value="queue" {{ old('connection_type') == 'queue' ? 'selected' : '' }}>Queue</option>
                                <option value="invoice" {{ old('connection_type') == 'invoice' ? 'selected' : '' }}>Invoice Only</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Cable Type</label>
                            <select name="connection_medium" class="form-control">
                                <option value="utp" {{ old('connection_medium', 'fiber') == 'utp' ? 'selected' : '' }}>UTP Cable</option>
                                <option value="fiber" {{ old('connection_medium', 'fiber') == 'fiber' ? 'selected' : '' }}>Optical Fiber</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label>Username <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="text" name="username" id="usernameField" class="form-control" value="{{ old('username') }}" required placeholder="e.g. johndoe">
                        <div class="input-group-append">
                            <button type="button" class="btn btn-outline-secondary" onclick="usePhoneAsUsername()" title="Fill from First+Last name"><i class="fas fa-user"></i></button>
                            <button type="button" class="btn btn-outline-primary" onclick="suggestNextUsername()" title="Auto-suggest next username"><i class="fas fa-magic"></i></button>
                        </div>
                    </div>
                    <small class="text-muted">User icon = fill from name &nbsp;|&nbsp; Wand icon = auto-suggest sequential</small>
                </div>
                <div class="form-group">
                    <label>PPPoE Password</label>
                    <div class="input-group">
                        <input type="text" name="pppoe_password" class="form-control" value="{{ old('pppoe_password') }}" id="pppoe_password">
                        <div class="input-group-append">
                            <button type="button" class="btn btn-secondary" onclick="generatePassword()" title="Generate"><i class="fas fa-random"></i></button>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>IP Address</label>
                            <input type="text" name="ip_address" class="form-control" value="{{ old('ip_address') }}">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>MikroTik Router</label>
                            <select name="mikrotik_device_id" class="form-control">
                                <option value="">Auto</option>
                                @foreach($mikrotikDevices ?? [] as $device)
                                <option value="{{ $device->id }}" {{ old('mikrotik_device_id') == $device->id ? 'selected' : '' }}>{{ $device->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="form-group mb-0">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" name="mac_binding_enabled" value="1" class="custom-control-input" id="mac_binding" {{ old('mac_binding_enabled') ? 'checked' : '' }}>
                        <label class="custom-control-label" for="mac_binding">Enable MAC Binding</label>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-8">
                        <div class="form-group">
                            <label>Referral Partner</label>
                            <select name="agent_id" class="form-control" id="createAgentSelect">
                                <option value="" data-commission="">No Referral Partner</option>
                                @foreach($agents ?? [] as $agent)
                                <option value="{{ $agent->id }}" data-commission="{{ $agent->default_commission }}" {{ old('agent_id') == $agent->id ? 'selected' : '' }}>{{ $agent->name }} {{ $agent->personal_contact ? '(' . $agent->personal_contact . ')' : '' }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Commission (%)</label>
                            <input type="number" name="commission_percent" id="createCommissionPercent" class="form-control" step="0.01" min="0" max="100" value="{{ old('commission_percent') }}" placeholder="0.00">
                        </div>
                    </div>
                </div>
            </div>

            <div class="section-card">
                <div class="section-title"><i class="fas fa-map-signs"></i>Location Hierarchy</div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Division</label>
                            <select name="division_select" class="form-control" id="division_id">
                                <option value="">-- Select Division --</option>
                                @foreach($divisions as $div)
                                <option value="{{ $div }}" {{ old('division_select') == $div ? 'selected' : '' }}>{{ $div }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>District</label>
                            <select name="district_id" class="form-control" id="district_id">
                                <option value="">-- Select Division First --</option>
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
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Zone</label>
                            <select name="zone_id" class="form-control" id="zone_id">
                                <option value="">-- Select Thana/Upazila First --</option>
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
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>TJ Box</label>
                            <select name="box_id" class="form-control" id="box_id">
                                <option value="">-- Select Sub Zone First --</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label>Nearby Landmark</label>
                    <input type="text" name="nearby_landmark" class="form-control" value="{{ old('nearby_landmark') }}" placeholder="Mosque, School, Market...">
                </div>
            </div>

            <div class="section-card">
                <div class="section-title"><i class="fas fa-tools"></i>Installation & Service</div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Install Fee (৳)</label>
                            <input type="number" name="installation_charge" class="form-control" value="{{ old('installation_charge', 0) }}" min="0" step="0.01">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Cable Length (m)</label>
                            <input type="number" name="cable_length_meters" class="form-control" value="{{ old('cable_length_meters', 0) }}" min="0">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>ISP ONU</label>
                            <div class="d-flex">
                                <div class="custom-control custom-radio mr-3">
                                    <input type="radio" name="onu_required" value="0" class="custom-control-input" id="onu_no" {{ old('onu_required','1')=='0'?'checked':'' }}>
                                    <label class="custom-control-label" for="onu_no">No</label>
                                </div>
                                <div class="custom-control custom-radio">
                                    <input type="radio" name="onu_required" value="1" class="custom-control-input" id="onu_yes" {{ old('onu_required','1')=='1'?'checked':'' }}>
                                    <label class="custom-control-label" for="onu_yes">Yes</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>ISP Router</label>
                            <div class="d-flex">
                                <div class="custom-control custom-radio mr-3">
                                    <input type="radio" name="router_required" value="0" class="custom-control-input" id="router_no" {{ old('router_required','0')=='0'?'checked':'' }}>
                                    <label class="custom-control-label" for="router_no">No</label>
                                </div>
                                <div class="custom-control custom-radio">
                                    <input type="radio" name="router_required" value="1" class="custom-control-input" id="router_yes" {{ old('router_required')=='1'?'checked':'' }}>
                                    <label class="custom-control-label" for="router_yes">Yes</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>ISP Fiber/Cable</label>
                            <div class="d-flex">
                                <div class="custom-control custom-radio mr-3">
                                    <input type="radio" name="fiber_required" value="0" class="custom-control-input" id="fiber_no" {{ old('fiber_required','1')=='0'?'checked':'' }}>
                                    <label class="custom-control-label" for="fiber_no">No</label>
                                </div>
                                <div class="custom-control custom-radio">
                                    <input type="radio" name="fiber_required" value="1" class="custom-control-input" id="fiber_yes" {{ old('fiber_required','1')=='1'?'checked':'' }}>
                                    <label class="custom-control-label" for="fiber_yes">Yes</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @if(isset($technicians) && $technicians->count())
                <div class="form-group mb-0">
                    <label>Assign Technician <small class="text-muted">(Ctrl+Click for multiple)</small></label>
                    <select name="assigned_technicians[]" class="form-control" multiple size="{{ min($technicians->count() + 1, 5) }}" style="height: auto;">
                        @foreach($technicians as $tech)
                        <option value="{{ $tech->user_id }}" {{ in_array($tech->user_id, old('assigned_technicians', [])) ? 'selected' : '' }}>{{ $tech->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
            </div>

            <div class="section-card">
                <div class="section-title"><i class="fas fa-project-diagram"></i>Fiber & ONU</div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Fiber Brand</label>
                            <input type="text" name="fiber_vendor" class="form-control" value="{{ old('fiber_vendor') }}" placeholder="e.g. Corning, YOFC">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Fiber Code</label>
                            <input type="text" name="fiber_code" class="form-control" value="{{ old('fiber_code') }}" placeholder="e.g. FC-001">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Fiber Core Type</label>
                            <input type="text" name="fiber_core_type" class="form-control" value="{{ old('fiber_core_type') }}" placeholder="e.g. 2 Core">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Fiber Color</label>
                            <div class="input-group">
    <input type="text" name="fiber_color" id="fiber_color_input" class="form-control" value="{{ old('fiber_color') }}" placeholder="Select color" autocomplete="off" readonly style="cursor:pointer;background:#fff;">
    <div class="input-group-append">
        <button class="btn btn-outline-secondary fiber-color-btn" type="button" data-target="fiber_color_input" style="padding:0 8px;">
            <span class="fiber-dot" id="dot_fiber_color_input" style="display:inline-block;width:18px;height:18px;border-radius:3px;background:#ccc;border:1px solid #aaa;vertical-align:middle;"></span>
        </button>
    </div>
    <div class="fiber-color-palette" id="palette_fiber_color_input" style="display:none;position:absolute;top:100%;left:0;z-index:9999;background:#fff;border:1px solid #ddd;border-radius:6px;padding:8px;box-shadow:0 4px 12px rgba(0,0,0,0.15);width:220px;">
        <div style="display:flex;flex-wrap:wrap;"><span class="fiber-color-swatch" data-color="Blue" data-hex="#0057B8" data-target="fiber_color_input" style="display:inline-block;width:28px;height:28px;background:#0057B8;border-radius:4px;cursor:pointer;margin:2px;border:1px solid rgba(0,0,0,0.15);" title="Blue"></span><span class="fiber-color-swatch" data-color="Orange" data-hex="#FF8C00" data-target="fiber_color_input" style="display:inline-block;width:28px;height:28px;background:#FF8C00;border-radius:4px;cursor:pointer;margin:2px;border:1px solid rgba(0,0,0,0.15);" title="Orange"></span><span class="fiber-color-swatch" data-color="Green" data-hex="#28a745" data-target="fiber_color_input" style="display:inline-block;width:28px;height:28px;background:#28a745;border-radius:4px;cursor:pointer;margin:2px;border:1px solid rgba(0,0,0,0.15);" title="Green"></span><span class="fiber-color-swatch" data-color="Brown" data-hex="#8B4513" data-target="fiber_color_input" style="display:inline-block;width:28px;height:28px;background:#8B4513;border-radius:4px;cursor:pointer;margin:2px;border:1px solid rgba(0,0,0,0.15);" title="Brown"></span><span class="fiber-color-swatch" data-color="Slate" data-hex="#708090" data-target="fiber_color_input" style="display:inline-block;width:28px;height:28px;background:#708090;border-radius:4px;cursor:pointer;margin:2px;border:1px solid rgba(0,0,0,0.15);" title="Slate"></span><span class="fiber-color-swatch" data-color="White" data-hex="#e9ecef" data-target="fiber_color_input" style="display:inline-block;width:28px;height:28px;background:#e9ecef;border-radius:4px;cursor:pointer;margin:2px;border:1px solid rgba(0,0,0,0.15);" title="White"></span><span class="fiber-color-swatch" data-color="Red" data-hex="#dc3545" data-target="fiber_color_input" style="display:inline-block;width:28px;height:28px;background:#dc3545;border-radius:4px;cursor:pointer;margin:2px;border:1px solid rgba(0,0,0,0.15);" title="Red"></span><span class="fiber-color-swatch" data-color="Black" data-hex="#222222" data-target="fiber_color_input" style="display:inline-block;width:28px;height:28px;background:#222222;border-radius:4px;cursor:pointer;margin:2px;border:1px solid rgba(0,0,0,0.15);" title="Black"></span><span class="fiber-color-swatch" data-color="Yellow" data-hex="#ffc107" data-target="fiber_color_input" style="display:inline-block;width:28px;height:28px;background:#ffc107;border-radius:4px;cursor:pointer;margin:2px;border:1px solid rgba(0,0,0,0.15);" title="Yellow"></span><span class="fiber-color-swatch" data-color="Violet" data-hex="#6f42c1" data-target="fiber_color_input" style="display:inline-block;width:28px;height:28px;background:#6f42c1;border-radius:4px;cursor:pointer;margin:2px;border:1px solid rgba(0,0,0,0.15);" title="Violet"></span><span class="fiber-color-swatch" data-color="Rose" data-hex="#e83e8c" data-target="fiber_color_input" style="display:inline-block;width:28px;height:28px;background:#e83e8c;border-radius:4px;cursor:pointer;margin:2px;border:1px solid rgba(0,0,0,0.15);" title="Rose"></span><span class="fiber-color-swatch" data-color="Aqua" data-hex="#20c997" data-target="fiber_color_input" style="display:inline-block;width:28px;height:28px;background:#20c997;border-radius:4px;cursor:pointer;margin:2px;border:1px solid rgba(0,0,0,0.15);" title="Aqua"></span></div>
        <div style="margin-top:4px;font-size:10px;color:#999;">Click a color to select</div>
    </div>
</div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Splitter Number</label>
                            <input type="text" name="splitter_number" class="form-control" value="{{ old('splitter_number') }}" placeholder="e.g. SP-01">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Splitter Color</label>
                            <div class="input-group">
    <input type="text" name="splitter_color" id="splitter_color_input" class="form-control" value="{{ old('splitter_color') }}" placeholder="Select color" autocomplete="off" readonly style="cursor:pointer;background:#fff;">
    <div class="input-group-append">
        <button class="btn btn-outline-secondary fiber-color-btn" type="button" data-target="splitter_color_input" style="padding:0 8px;">
            <span class="fiber-dot" id="dot_splitter_color_input" style="display:inline-block;width:18px;height:18px;border-radius:3px;background:#ccc;border:1px solid #aaa;vertical-align:middle;"></span>
        </button>
    </div>
    <div class="fiber-color-palette" id="palette_splitter_color_input" style="display:none;position:absolute;top:100%;left:0;z-index:9999;background:#fff;border:1px solid #ddd;border-radius:6px;padding:8px;box-shadow:0 4px 12px rgba(0,0,0,0.15);width:220px;">
        <div style="display:flex;flex-wrap:wrap;"><span class="fiber-color-swatch" data-color="Blue" data-hex="#0057B8" data-target="splitter_color_input" style="display:inline-block;width:28px;height:28px;background:#0057B8;border-radius:4px;cursor:pointer;margin:2px;border:1px solid rgba(0,0,0,0.15);" title="Blue"></span><span class="fiber-color-swatch" data-color="Orange" data-hex="#FF8C00" data-target="splitter_color_input" style="display:inline-block;width:28px;height:28px;background:#FF8C00;border-radius:4px;cursor:pointer;margin:2px;border:1px solid rgba(0,0,0,0.15);" title="Orange"></span><span class="fiber-color-swatch" data-color="Green" data-hex="#28a745" data-target="splitter_color_input" style="display:inline-block;width:28px;height:28px;background:#28a745;border-radius:4px;cursor:pointer;margin:2px;border:1px solid rgba(0,0,0,0.15);" title="Green"></span><span class="fiber-color-swatch" data-color="Brown" data-hex="#8B4513" data-target="splitter_color_input" style="display:inline-block;width:28px;height:28px;background:#8B4513;border-radius:4px;cursor:pointer;margin:2px;border:1px solid rgba(0,0,0,0.15);" title="Brown"></span><span class="fiber-color-swatch" data-color="Gray" data-hex="#808080" data-target="splitter_color_input" style="display:inline-block;width:28px;height:28px;background:#808080;border-radius:4px;cursor:pointer;margin:2px;border:1px solid rgba(0,0,0,0.15);" title="Gray"></span><span class="fiber-color-swatch" data-color="White" data-hex="#e9ecef" data-target="splitter_color_input" style="display:inline-block;width:28px;height:28px;background:#e9ecef;border-radius:4px;cursor:pointer;margin:2px;border:1px solid rgba(0,0,0,0.15);" title="White"></span><span class="fiber-color-swatch" data-color="Red" data-hex="#dc3545" data-target="splitter_color_input" style="display:inline-block;width:28px;height:28px;background:#dc3545;border-radius:4px;cursor:pointer;margin:2px;border:1px solid rgba(0,0,0,0.15);" title="Red"></span><span class="fiber-color-swatch" data-color="Black" data-hex="#222222" data-target="splitter_color_input" style="display:inline-block;width:28px;height:28px;background:#222222;border-radius:4px;cursor:pointer;margin:2px;border:1px solid rgba(0,0,0,0.15);" title="Black"></span></div>
        <div style="margin-top:4px;font-size:10px;color:#999;">Click a color to select</div>
    </div>
</div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>ONU Brand</label>
                            <input type="text" name="onu_brand" class="form-control" value="{{ old('onu_brand') }}" placeholder="e.g. Huawei">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>ONU MAC/Serial</label>
                            <input type="text" name="onu_mac" class="form-control" value="{{ old('onu_mac') }}" placeholder="AA:BB:CC:DD:EE:FF">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-md-12">
            <div class="section-card highlight">
                <div class="section-title"><i class="fas fa-money-bill-wave"></i>Billing & Package</div>
                <div class="row">
                    <div class="col-md-8">
                        <div class="form-group">
                            <label>Package <span class="text-danger">*</span></label>
                            <select name="package_id" class="form-control" required id="package_id">
                                <option value="">Select Package</option>
                                @foreach($packages as $package)
                                <option value="{{ $package->id }}" data-price="{{ $package->price }}" data-mikrotik-profile="{{ $package->mikrotik_profile }}">{{ $package->name }} - ৳{{ number_format($package->price ?? 0, 2) }} ({{ $package->bandwidth }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Billing Cycle</label>
                            <select name="billing_cycle" class="form-control">
                                <option value="monthly" {{ old('billing_cycle','monthly')=='monthly'?'selected':'' }}>Monthly</option>
                                <option value="quarterly" {{ old('billing_cycle')=='quarterly'?'selected':'' }}>Quarterly</option>
                                <option value="half_yearly" {{ old('billing_cycle')=='half_yearly'?'selected':'' }}>Half Yearly</option>
                                <option value="yearly" {{ old('billing_cycle')=='yearly'?'selected':'' }}>Yearly</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label>Profile Name <small class="text-muted">(MikroTik Speed)</small></label>
                    <select name="mikrotik_profile_name" class="form-control" id="mikrotik_profile_name">
                        <option value="">-- Select Profile --</option>
                        @foreach($availableProfiles as $profile)
                        <option value="{{ $profile }}" {{ old('mikrotik_profile_name') == $profile ? 'selected' : '' }}>{{ $profile }}</option>
                        @endforeach
                    </select>
                    <small class="text-muted">Speed profile on router. Bill comes from Package price.</small>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Monthly Bill (৳)</label>
                            <input type="number" name="monthly_fee" class="form-control" value="{{ old('monthly_fee', 0) }}" id="monthly_fee" step="0.01" readonly>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Permanent Discount (৳)</label>
                            <input type="number" name="permanent_discount" class="form-control" value="{{ old('permanent_discount', 0) }}" step="0.01" min="0">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label><i class="fas fa-file-invoice text-primary mr-1"></i>Bill Generate Date</label>
                            <select name="billing_date" class="form-control">
                                @for($d = 1; $d <= 31; $d++)
                                    <option value="{{ $d }}" {{ old('billing_date', 1) == $d ? 'selected' : '' }}>{{ $d }}</option>
                                @endfor
                            </select>
                            <small class="text-muted">Day of month when bill is auto-generated</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label><i class="fas fa-exclamation-triangle text-danger mr-1"></i>Auto-Inactive Date</label>
                            <select name="payment_deadline" class="form-control">
                                @for($d = 1; $d <= 31; $d++)
                                    <option value="{{ $d }}" {{ old('payment_deadline', 15) == $d ? 'selected' : '' }}>{{ $d }}</option>
                                @endfor
                            </select>
                            <small class="text-muted">Day of month — client auto-inactivated if bill unpaid</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group" id="billing-start-group">
                            <label>
                                First Bill From
                                <small class="text-muted">(Leave blank = bill from current month)</small>
                            </label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <div class="input-group-text">
                                        <input type="checkbox" id="skip-current-month" title="Skip current month billing">
                                    </div>
                                </div>
                                <input type="month" name="billing_starts_at" id="billing_starts_at"
                                    class="form-control"
                                    value="{{ old('billing_starts_at') }}"
                                    placeholder="YYYY-MM"
                                    disabled>
                            </div>
                            <small id="billing-start-hint" class="text-info" style="display:none;"></small>
                        </div>
                    </div>
                </div>
                <script>
                (function(){
                    var today = new Date();
                    var day = today.getDate();
                    var checkbox = document.getElementById('skip-current-month');
                    var input = document.getElementById('billing_starts_at');
                    var hint = document.getElementById('billing-start-hint');

                    function nextMonthFirst() {
                        var d = new Date(today.getFullYear(), today.getMonth() + 1, 1);
                        var y = d.getFullYear();
                        var m = String(d.getMonth() + 1).padStart(2, '0');
                        return y + '-' + m;
                    }

                    function currentMonth() {
                        var y = today.getFullYear();
                        var m = String(today.getMonth() + 1).padStart(2, '0');
                        return y + '-' + m;
                    }

                    // Auto-suggest skip if today is 25th or later
                    if (day >= 25) {
                        checkbox.checked = true;
                        input.disabled = false;
                        input.value = nextMonthFirst();
                        hint.style.display = 'block';
                        hint.textContent = 'Auto-suggested: billing starts next month (client added on day ' + day + ')';
                    }

                    checkbox.addEventListener('change', function() {
                        if (this.checked) {
                            input.disabled = false;
                            input.value = nextMonthFirst();
                            hint.style.display = 'block';
                            hint.textContent = 'First bill: ' + input.value;
                        } else {
                            input.disabled = true;
                            input.value = '';
                            hint.style.display = 'none';
                        }
                    });

                    input.addEventListener('change', function() {
                        if (this.value) {
                            hint.textContent = 'First bill will be generated in: ' + this.value;
                        }
                    });

                    // Ensure disabled input value is not submitted
                    input.closest('form') && input.closest('form').addEventListener('submit', function() {
                        if (input.disabled) input.removeAttribute('name');
                    });
                })();
                </script>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Joining Date</label>
                            <input type="text" name="connection_date" class="form-control flatpickr-date" value="{{ old('connection_date', date('Y-m-d')) }}">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Connection Expiry Date <small class="text-muted">(optional)</small></label>
                            <div class="input-group">
                                <input type="text" id="expireDateCreate" name="expire_date" class="form-control flatpickr-date" value="{{ old('expire_date') }}">
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('expireDateCreate')._flatpickr.clear();" title="Clear date"><i class="fas fa-times"></i></button>
                                </div>
                            </div>
                            <small class="text-muted">Leave blank for no expiry</small>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Bill Calculation</label>
                            <select name="bill_calculation_type" class="form-control">
                                <option value="auto">Auto</option>
                                <option value="manual">Manual</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Status</label>
                            <select name="status" class="form-control">
                                <option value="pending" {{ old('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="active" {{ old('status', 'active') == 'active' ? 'selected' : '' }}>Active</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="custom-control custom-switch mb-2">
                    <input type="hidden" name="is_free" value="0">
                    <input type="checkbox" class="custom-control-input" id="is_free" name="is_free" value="1" {{ old('is_free') ? 'checked' : '' }}>
                    <label class="custom-control-label" for="is_free">Free Client (No Monthly Bill)</label>
                </div>
                <div class="form-group" id="free_reason_group" style="{{ old('is_free') ? '' : 'display:none' }}">
                    <label>Free Reason</label>
                    <input type="text" name="free_reason" class="form-control" value="{{ old('free_reason') }}" placeholder="e.g. Owner, Staff, Mosque">
                </div>
            </div>

            <div class="section-card">
                <div class="section-title"><i class="fas fa-comment-alt"></i>Remarks</div>
                <div class="form-group">
                    <label>General Remarks</label>
                    <textarea name="remarks" class="form-control" rows="2" placeholder="Any additional notes...">{{ old('remarks') }}</textarea>
                </div>
                <div class="form-group mb-0">
                    <label>Technician Note</label>
                    <textarea name="technician_note" class="form-control" rows="2" placeholder="Special requirements or installation notes...">{{ old('technician_note') }}</textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white border-top p-3 sticky-bottom" style="position: sticky; bottom: 0; z-index: 10; box-shadow: 0 -2px 8px rgba(0,0,0,0.08);">
        <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i>Save Client</button>
        <button type="submit" name="save_and_send_sms" value="1" class="btn btn-success"><i class="fas fa-sms mr-1"></i>Save & Send Login SMS</button>
        <a href="{{ route('admin.clients.index') }}" class="btn btn-secondary">Cancel</a>
    </div>
</form>
@endsection

@push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
const SUGGEST_URL = '{{ route("admin.clients.suggest-credentials") }}';

function loadSuggestedCredentials() {
    fetch(SUGGEST_URL, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(r => r.json())
        .then(data => {
            var codeField = document.getElementById('clientCodeField');
            if (codeField && !codeField.value) {
                codeField.placeholder = 'Auto (' + data.next_client_code + ')';
            }
            var hint = document.getElementById('nextCodeHint');
            if (hint) hint.textContent = '(next: ' + data.next_client_code + ')';
            window._suggestedUsername = data.next_username;
        })
        .catch(function(){});
}

function suggestNextUsername() {
    fetch(SUGGEST_URL, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(r => r.json())
        .then(data => {
            document.getElementById('usernameField').value = data.next_username;
        })
        .catch(function(){ alert('Could not fetch suggestion'); });
}

function buildUsernameFromName() {
    var first = (document.querySelector('input[name="first_name"]')?.value || '').trim().toLowerCase();
    var last  = (document.querySelector('input[name="last_name"]')?.value  || '').trim().toLowerCase();
    var combined = (first + last).replace(/[^a-z0-9]/g, '');
    return combined || null;
}

function usePhoneAsUsername() {
    var name = buildUsernameFromName();
    if (name) {
        document.getElementById('usernameField').value = name;
        var pwdField = document.getElementById('pppoe_password');
        if (pwdField && !pwdField.value) pwdField.value = name;
    } else {
        alert('Please enter First Name and Last Name first');
    }
}

function autoFillFromName() {
    var un = document.getElementById('usernameField');
    var pw = document.getElementById('pppoe_password');
    var name = buildUsernameFromName();
    if (name) {
        if (un && !un.value) un.value = name;
        if (pw && !pw.value) pw.value = name;
    }
}

document.addEventListener('DOMContentLoaded', function() {
    loadSuggestedCredentials();
    ['input[name="first_name"]', 'input[name="last_name"]'].forEach(function(sel) {
        var el = document.querySelector(sel);
        if (el) el.addEventListener('blur', autoFillFromName);
    });
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

$('#createAgentSelect').on('change', function() {
    var commission = $(this).find('option:selected').data('commission');
    if (commission) {
        $('#createCommissionPercent').val(commission);
    } else {
        $('#createCommissionPercent').val('');
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

document.getElementById('package_id').addEventListener('change', function() {
    document.getElementById('monthly_fee').value = this.selectedOptions[0].dataset.price || 0;
    var profile = this.selectedOptions[0].dataset.mikrotikProfile;
    var profileSelect = document.getElementById('mikrotik_profile_name');
    if (profile) {
        var found = false;
        for (var i = 0; i < profileSelect.options.length; i++) {
            if (profileSelect.options[i].value === profile) {
                profileSelect.selectedIndex = i;
                found = true;
                break;
            }
        }
        if (!found) profileSelect.selectedIndex = 0;
    } else {
        profileSelect.selectedIndex = 0;
    }
});

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
(function(){
    var colorMap = {"Blue":"#0057B8","Orange":"#FF8C00","Green":"#28a745","Brown":"#8B4513","Slate":"#708090","Gray":"#808080","White":"#e9ecef","Red":"#dc3545","Black":"#222222","Yellow":"#ffc107","Violet":"#6f42c1","Rose":"#e83e8c","Aqua":"#20c997"};
    function updateDot(id){
        var inp=document.getElementById(id);
        var dot=document.getElementById("dot_"+id);
        if(!inp||!dot)return;
        var v=inp.value.trim();
        dot.style.background=colorMap[v]||(v?"#aaa":"#ccc");
    }
    function initColorPickers(){
        document.querySelectorAll(".fiber-color-swatch").forEach(function(sw){
            sw.addEventListener("click",function(e){
                e.stopPropagation();
                var tid=this.dataset.target;
                var inp=document.getElementById(tid);
                if(inp){inp.value=this.dataset.color;updateDot(tid);}
                document.querySelectorAll(".fiber-color-palette").forEach(function(p){p.style.display="none";});
            });
        });
        document.querySelectorAll(".fiber-color-btn").forEach(function(btn){
            btn.addEventListener("click",function(e){
                e.stopPropagation();
                var tid=this.dataset.target;
                var pal=document.getElementById("palette_"+tid);
                document.querySelectorAll(".fiber-color-palette").forEach(function(p){
                    if(p.id!=="palette_"+tid)p.style.display="none";
                });
                if(pal)pal.style.display=(pal.style.display==="none"||pal.style.display==="")?"block":"none";
            });
        });
        document.addEventListener("click",function(e){
            if(!e.target.closest(".fiber-color-btn") && !e.target.closest(".fiber-color-palette")){
                document.querySelectorAll(".fiber-color-palette").forEach(function(p){p.style.display="none";});
            }
        });
        ["fiber_color_input","splitter_color_input"].forEach(updateDot);
        // Click on readonly input also opens palette
        ['fiber_color_input','splitter_color_input'].forEach(function(id){
            var inp = document.getElementById(id);
            if(inp) inp.addEventListener('click', function(e){
                e.stopPropagation();
                var btn = this.closest('.input-group').querySelector('.fiber-color-btn');
                if(btn) btn.click();
            });
        });
    }
    if(document.readyState==="loading"){document.addEventListener("DOMContentLoaded",initColorPickers);}
    else{initColorPickers();}
})();
</script>

@endpush
