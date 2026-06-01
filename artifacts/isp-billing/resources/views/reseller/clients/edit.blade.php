@extends('layouts.reseller')
@section('title', 'Edit Client')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('reseller.clients') }}">Clients</a></li>
<li class="breadcrumb-item active">Edit {{ $client->name }}</li>
@endsection

@push('styles')
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
<form action="{{ route('reseller.clients.update', $client) }}" method="POST" enctype="multipart/form-data" class="compact-form">
    @csrf @method('PUT')
    @if($errors->any())
    <div class="alert alert-danger py-2 mb-3">
        <ul class="mb-0 small">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
    @endif

    @php
        $remainingDays = $client->expire_date ? max(0, (int)\Carbon\Carbon::now()->startOfDay()->diffInDays($client->expire_date->startOfDay(), false)) : 0;
        $perDayCharge = ($client->monthly_fee > 0) ? round($client->monthly_fee / 30, 4) : 0;
        $validityDate = $client->expire_date ? $client->expire_date->format('n/j/Y') : '';
    @endphp

    <div class="section-card highlight mb-3">
        <div class="section-title"><i class="fas fa-cogs"></i>Service Information</div>
        <div class="row align-items-end">
            <div class="col-md-1">
                <div class="form-group mb-0">
                    <label>P/R.D <i class="fas fa-info-circle text-info" title="Check to change Package or Remaining Days" data-toggle="tooltip"></i></label>
                    <div class="mt-2">
                        <input type="checkbox" name="prd_change" id="prd_change" value="1" {{ old('prd_change') ? 'checked' : '' }} style="width:18px;height:18px;cursor:pointer;">
                    </div>
                </div>
            </div>

            {{-- Current package shown when prd_change is unchecked --}}
            <div class="col-md-3" id="prd_current_pkg_display">
                <div class="form-group mb-0">
                    <label class="text-muted">Current Package</label>
                    <div class="form-control bg-light text-muted" style="font-size:13px;">
                        {{ $client->package ? $client->package->name . ' — ' . number_format($client->monthly_fee ?? $client->package->price, 0) . ' TK' : '—' }}
                        @if($client->expire_date)
                        <br><small>Expires: {{ $client->expire_date->format('d M Y') }} ({{ $remainingDays }}d left)</small>
                        @endif
                    </div>
                    {{-- Keep hidden input so package_id is always submitted (current value) --}}
                    <input type="hidden" name="package_id" id="package_id_hidden" value="{{ $client->package_id }}">
                </div>
            </div>

            {{-- Package change fields shown only when prd_change is checked --}}
            <div id="prd_change_fields" style="display:none;" class="col-md-9 p-0">
                <div class="row align-items-end mx-0">
                    <div class="col-md-4">
                        <div class="form-group mb-0">
                            <label>New Package <span class="text-danger">*</span></label>
                            <select name="package_id" id="service_package_id" class="form-control">
                                <option value="">-- Select Package --</option>
                                @foreach($packages as $package)
                                <option value="{{ $package->id }}" data-price="{{ $package->display_price ?? $package->price }}" {{ old('package_id', $client->package_id) == $package->id ? 'selected' : '' }}>{{ $package->name }} — {{ number_format($package->display_price ?? $package->price, 0) }} TK</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group mb-0">
                            <label>Remaining Days <i class="fas fa-info-circle text-muted" title="New expire = today + days entered" data-toggle="tooltip"></i></label>
                            <input type="number" name="remaining_days" id="remaining_days" class="form-control" value="{{ old('remaining_days', $remainingDays) }}" min="0" max="365">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group mb-0">
                            <label>Per Day <i class="fas fa-info-circle text-muted" title="Monthly fee ÷ 30" data-toggle="tooltip"></i></label>
                            <input type="text" id="per_day_charge" class="form-control bg-light" value="{{ $perDayCharge }}" readonly tabindex="-1">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group mb-0">
                            <label>New Expiry</label>
                            <input type="text" id="validity_to_date" class="form-control bg-light" value="{{ $validityDate }}" readonly tabindex="-1" style="font-size:12px;">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-3">
                <div class="form-group">
                    <label>Username/IP</label>
                    <input type="text" name="username" class="form-control" value="{{ old('username', $client->username) }}" required>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Password <span class="text-danger">*</span></label>
                    <input type="text" name="pppoe_password" class="form-control" value="{{ old('pppoe_password', $client->pppoe_password) }}">
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label>Monthly Bill <span class="text-danger">*</span></label>
                    <input type="number" name="monthly_fee" id="monthly_fee" class="form-control" value="{{ old('monthly_fee', $client->monthly_fee) }}" step="0.01">
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label>Auto Renewal <i class="fas fa-info-circle text-muted" title="Automatically renew on expiry"></i></label>
                    <div class="mt-1">
                        <input type="hidden" name="auto_renewal" value="0">
                        <input type="checkbox" name="auto_renewal" value="1" {{ old('auto_renewal', $client->auto_renewal) ? 'checked' : '' }}>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-2">
                <div class="form-group">
                    <label>Client Type <span class="text-danger">*</span></label>
                    <select name="client_type" class="form-control">
                        <option value="home" {{ old('client_type', $client->client_type) == 'home' ? 'selected' : '' }}>Home</option>
                        <option value="regular" {{ old('client_type', $client->client_type) == 'regular' ? 'selected' : '' }}>Regular</option>
                        <option value="corporate" {{ old('client_type', $client->client_type) == 'corporate' ? 'selected' : '' }}>Corporate</option>
                        <option value="business" {{ old('client_type', $client->client_type) == 'business' ? 'selected' : '' }}>Business</option>
                        <option value="government" {{ old('client_type', $client->client_type) == 'government' ? 'selected' : '' }}>Government</option>
                        <option value="vip" {{ old('client_type', $client->client_type) == 'vip' ? 'selected' : '' }}>VIP</option>
                    </select>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label>Billing Status <span class="text-danger">*</span></label>
                    <select name="status" class="form-control">
                        <option value="active" {{ old('status', $client->status) == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ old('status', $client->status) == 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label>Remaining Days <i class="fas fa-info-circle text-muted" title="Current days left until expiry. Changing this updates the Expire Date in Billing Settings below." data-toggle="tooltip"></i></label>
                    <input type="number" name="expire_days" id="expire_days" class="form-control" value="{{ $remainingDays }}" min="0" title="Days until expiry">
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Joining Date <small class="text-muted">(No relation in billing)</small> <i class="fas fa-info-circle text-muted"></i></label>
                    <input type="date" name="connection_date" class="form-control" value="{{ old('connection_date', $client->connection_date ? (is_string($client->connection_date) ? $client->connection_date : $client->connection_date->format('Y-m-d')) : '') }}">
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Effective To</label>
                    <input type="date" name="effective_to" class="form-control" value="{{ old('effective_to', $client->effective_to ? (is_string($client->effective_to) ? $client->effective_to : $client->effective_to->format('Y-m-d')) : '') }}">
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-4 col-md-6">
            <div class="section-card highlight">
                <div class="section-title"><i class="fas fa-user"></i>Personal Info</div>
                <div class="form-group">
                    <label>Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $client->name) }}" required>
                    @error('name')<span class="invalid-feedback">{{ $message }}</span>@enderror
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
                            <input type="date" name="date_of_birth" class="form-control" value="{{ old('date_of_birth', $client->date_of_birth ? (is_string($client->date_of_birth) ? $client->date_of_birth : $client->date_of_birth->format('Y-m-d')) : '') }}">
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
                <div class="form-group">
                    <label>Occupation</label>
                    <input type="text" name="occupation" class="form-control" value="{{ old('occupation', $client->occupation) }}" placeholder="e.g. Business">
                </div>
                <div class="form-group">
                    <label>Address <span class="text-danger">*</span></label>
                    <textarea name="address" class="form-control" rows="2" required>{{ old('address', $client->address) }}</textarea>
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
                <div class="row">
                    <div class="col-md-5">
                        <div class="form-group">
                            <label>Latitude</label>
                            <input type="text" name="google_map_lat" class="form-control" value="{{ old('google_map_lat', $client->google_map_lat) }}" id="lat">
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="form-group">
                            <label>Longitude</label>
                            <input type="text" name="google_map_lng" class="form-control" value="{{ old('google_map_lng', $client->google_map_lng) }}" id="lng">
                        </div>
                    </div>
                    <div class="col-md-2 d-flex align-items-end pb-2">
                        <button type="button" class="btn btn-outline-info btn-sm" onclick="detectLocation()" title="Auto-detect Location"><i class="fas fa-crosshairs"></i></button>
                    </div>
                </div>
                @if($client->google_map_lat && $client->google_map_lng)
                <a href="https://www.google.com/maps?q={{ $client->google_map_lat }},{{ $client->google_map_lng }}" target="_blank" class="btn btn-success btn-sm btn-block"><i class="fas fa-external-link-alt mr-1"></i>View on Map</a>
                @endif
            </div>
        </div>

        <div class="col-lg-4 col-md-6">
            <div class="section-card highlight">
                <div class="section-title"><i class="fas fa-network-wired"></i>Connection & Auth</div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Connection Type</label>
                            <select name="connection_type" class="form-control">
                                <option value="pppoe" {{ old('connection_type', $client->connection_type) == 'pppoe' ? 'selected' : '' }}>PPPoE</option>
                                <option value="static" {{ old('connection_type', $client->connection_type) == 'static' ? 'selected' : '' }}>Static IP</option>
                                <option value="dhcp" {{ old('connection_type', $client->connection_type) == 'dhcp' ? 'selected' : '' }}>DHCP</option>
                                <option value="hotspot" {{ old('connection_type', $client->connection_type) == 'hotspot' ? 'selected' : '' }}>Hotspot</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Cable Type</label>
                            <select name="connection_medium" class="form-control">
                                <option value="utp" {{ old('connection_medium', $client->connection_medium) == 'utp' ? 'selected' : '' }}>UTP Cable</option>
                                <option value="fiber" {{ old('connection_medium', $client->connection_medium) == 'fiber' ? 'selected' : '' }}>Optical Fiber</option>
                            </select>
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
                            <label>MAC Address</label>
                            <input type="text" name="mac_address" class="form-control" value="{{ old('mac_address', $client->mac_address) }}">
                        </div>
                    </div>
                </div>
                <div class="custom-control custom-checkbox mb-2">
                    <input type="checkbox" class="custom-control-input" id="mac_binding_enabled" name="mac_binding_enabled" value="1" {{ old('mac_binding_enabled', $client->mac_binding_enabled) ? 'checked' : '' }}>
                    <label class="custom-control-label" for="mac_binding_enabled">MAC Binding Enabled</label>
                </div>
            </div>

            <div class="section-card">
                <div class="section-title"><i class="fas fa-map-signs"></i>Zone & Area</div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Zone <span class="text-danger">*</span></label>
                            <select name="zone_id" id="zone_id" class="form-control" required>
                                <option value="">Select Zone</option>
                                @foreach($zones as $zone)
                                <option value="{{ $zone->id }}" {{ old('zone_id', $client->zone_id) == $zone->id ? 'selected' : '' }}>{{ $zone->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Subzone</label>
                            <select name="area_id" id="area_id" class="form-control">
                                <option value="">Select Subzone</option>
                                @foreach($zones as $zone)
                                    @foreach($zone->areas as $area)
                                    <option value="{{ $area->id }}" {{ old('area_id', $client->area_id) == $area->id ? 'selected' : '' }}>{{ $area->name }}</option>
                                    @endforeach
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>TJ/Box</label>
                            <select name="box_id" class="form-control">
                                <option value="">Select TJ</option>
                                @foreach($boxes as $box)
                                <option value="{{ $box->id }}" {{ old('box_id', $client->box_id) == $box->id ? 'selected' : '' }}>{{ $box->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="section-card">
                <div class="section-title"><i class="fas fa-satellite-dish"></i>ONU/OLT Info</div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>ONU Serial</label>
                            <input type="text" name="onu_serial" class="form-control" value="{{ old('onu_serial', $client->onu_serial) }}">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>ONU Model</label>
                            <input type="text" name="onu_model" class="form-control" value="{{ old('onu_model', $client->onu_model) }}">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>ONU Brand</label>
                            <input type="text" name="onu_brand" class="form-control" value="{{ old('onu_brand', $client->onu_brand) }}" placeholder="e.g. Huawei">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>ONU MAC</label>
                            <input type="text" name="onu_mac" class="form-control" value="{{ old('onu_mac', $client->onu_mac) }}" placeholder="AA:BB:CC:DD:EE:FF">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>OLT Device</label>
                            <select name="olt_device_id" class="form-control select2">
                                <option value="">-- No OLT --</option>
                                @foreach($availableOlts ?? [] as $oltDev)
                                <option value="{{ $oltDev->id }}" {{ old('olt_device_id', $client->olt_device_id) == $oltDev->id ? 'selected' : '' }}>
                                    {{ $oltDev->name }} ({{ $oltDev->ip_address }})@if($oltDev->vendor) — {{ ucfirst($oltDev->vendor) }}@endif
                                </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>OLT Port</label>
                            <input type="text" name="olt_port" class="form-control" value="{{ old('olt_port', $client->olt_port) }}" placeholder="e.g. 0/1/1">
                        </div>
                    </div>
                </div>
            </div>

            <div class="section-card">
                <div class="section-title"><i class="fas fa-project-diagram"></i>Fiber Details</div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Fiber Brand</label>
                            <input type="text" name="fiber_vendor" class="form-control" value="{{ old('fiber_vendor', $client->fiber_vendor) }}" placeholder="e.g. Corning">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Fiber Code</label>
                            <input type="text" name="fiber_code" class="form-control" value="{{ old('fiber_code', $client->fiber_code) }}" placeholder="e.g. FC-001">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Fiber Core Type</label>
                            <input type="text" name="fiber_core_type" class="form-control" value="{{ old('fiber_core_type', $client->fiber_core_type) }}" placeholder="e.g. 2 Core">
                        </div>
                    </div>
                    <div class="col-md-6">
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
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Splitter Number</label>
                            <input type="text" name="splitter_number" class="form-control" value="{{ old('splitter_number', $client->splitter_number) }}" placeholder="e.g. SP-01">
                        </div>
                    </div>
                    <div class="col-md-6">
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
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-md-12">
            <div class="section-card highlight">
                <div class="section-title"><i class="fas fa-money-bill-wave"></i>Billing Settings</div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Discount (৳)</label>
                            <input type="number" name="permanent_discount" class="form-control" value="{{ old('permanent_discount', $client->permanent_discount) }}" step="0.01" min="0">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Billing Date</label>
                            <select name="billing_date" class="form-control">
                                @for($i = 1; $i <= 28; $i++)
                                <option value="{{ $i }}" {{ old('billing_date', $client->billing_date) == $i ? 'selected' : '' }}>{{ $i }}</option>
                                @endfor
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Payment Deadline (days)</label>
                            <input type="number" name="payment_deadline" class="form-control" value="{{ old('payment_deadline', $client->payment_deadline) }}">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Expire Date</label>
                            <div class="input-group">
                                <input type="date" name="expire_date" id="expire_date_input" class="form-control" value="{{ old('expire_date') }}">
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('expire_date_input').value=''" title="Clear date"><i class="fas fa-times"></i></button>
                                </div>
                            </div>
                            <small class="text-muted">Leave empty for no expiry. Select manually if needed.</small>
                        </div>
                    </div>
                </div>
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
                            <label class="custom-control-label" for="fiber_required">ISP Fiber/UTP</label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="section-card">
                <div class="section-title"><i class="fas fa-comment-alt"></i>Remarks</div>
                <div class="form-group mb-1">
                    <textarea name="remarks" class="form-control" rows="2" placeholder="Any additional notes...">{{ old('remarks', $client->remarks) }}</textarea>
                </div>
                <div class="form-group mb-0">
                    <label>Notes</label>
                    <textarea name="notes" class="form-control" rows="2">{{ old('notes', $client->notes) }}</textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white border-top p-3 sticky-bottom" style="position: sticky; bottom: 0; z-index: 10; box-shadow: 0 -2px 8px rgba(0,0,0,0.08);">
        <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i>Update Client</button>
        <a href="{{ route('reseller.clients.show', $client) }}" class="btn btn-secondary">Cancel</a>
    </div>
</form>
<form action="{{ route('reseller.clients.delete', $client) }}" method="POST" class="d-inline" style="position: sticky; bottom: 0; z-index: 10; float: right; margin-top: -48px; margin-right: 16px;" onsubmit="return confirm('Are you sure you want to delete this client? This will also delete all bills and payments.');">
    @csrf @method('DELETE')
    <button type="submit" class="btn btn-danger"><i class="fas fa-trash mr-1"></i>Delete Client</button>
</form>
@endsection

@push('scripts')
<script>
document.getElementById('is_free').addEventListener('change', function() {
    document.getElementById('free_reason_group').style.display = this.checked ? '' : 'none';
});

function detectLocation() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(position) {
            document.getElementById('lat').value = position.coords.latitude.toFixed(6);
            document.getElementById('lng').value = position.coords.longitude.toFixed(6);
        }, function() {
            alert('Unable to detect location. Please enter manually.');
        });
    } else {
        alert('Geolocation is not supported by this browser.');
    }
}

// P/R.D toggle — show/hide package change fields
function setPrdToggle(checked) {
    var currentDisplay = document.getElementById('prd_current_pkg_display');
    var changeFields   = document.getElementById('prd_change_fields');
    var pkgHidden      = document.getElementById('package_id_hidden');
    var pkgSelect      = document.getElementById('service_package_id');
    var remDays        = document.getElementById('remaining_days');

    if (checked) {
        currentDisplay.style.display = 'none';
        changeFields.style.display   = '';
        if (pkgHidden) pkgHidden.disabled = true;
        if (pkgSelect) pkgSelect.disabled = false;
        if (remDays)   remDays.disabled   = false;
    } else {
        currentDisplay.style.display = '';
        changeFields.style.display   = 'none';
        if (pkgHidden) pkgHidden.disabled = false;
        if (pkgSelect) pkgSelect.disabled = true;
        if (remDays)   remDays.disabled   = true;
    }
}

document.getElementById('prd_change').addEventListener('change', function() {
    setPrdToggle(this.checked);
});

// Initialize on page load
setPrdToggle(document.getElementById('prd_change').checked);

function updateServiceCalc() {
    var monthlyFee  = parseFloat(document.getElementById('monthly_fee').value) || 0;
    var remDaysEl   = document.getElementById('remaining_days');
    var remainingDays = remDaysEl ? (parseInt(remDaysEl.value) || 0) : 0;
    var perDay = monthlyFee > 0 ? (monthlyFee / 30).toFixed(4) : '0';
    var perDayEl = document.getElementById('per_day_charge');
    if (perDayEl) perDayEl.value = perDay;

    var validityEl = document.getElementById('validity_to_date');
    if (validityEl) {
        if (remainingDays > 0) {
            var today    = new Date();
            var validity = new Date(today);
            validity.setDate(validity.getDate() + remainingDays);
            validityEl.value = (validity.getMonth()+1) + '/' + validity.getDate() + '/' + validity.getFullYear();
        } else {
            validityEl.value = '';
        }
    }
}

document.getElementById('monthly_fee').addEventListener('input', updateServiceCalc);

var remDaysEl = document.getElementById('remaining_days');
if (remDaysEl) remDaysEl.addEventListener('input', updateServiceCalc);

var expireDaysEl = document.getElementById('expire_days');
if (expireDaysEl) expireDaysEl.addEventListener('input', function() {
    var rd = document.getElementById('remaining_days');
    if (rd) { rd.value = this.value; updateServiceCalc(); }
});

var pkgSelect = document.getElementById('service_package_id');
if (pkgSelect) pkgSelect.addEventListener('change', function() {
    var sel   = this.options[this.selectedIndex];
    var price = parseFloat(sel.getAttribute('data-price')) || 0;
    document.getElementById('monthly_fee').value = price;
    updateServiceCalc();
});
</script>

<script>
(function() {
    var fiberColors = {
        'blue':'#0057B8','orange':'#FF8C00','green':'#28a745','brown':'#8B4513',
        'slate':'#708090','white':'#e9ecef','red':'#dc3545','black':'#222222',
        'yellow':'#ffc107','violet':'#6f42c1','rose':'#e83e8c','aqua':'#20c997'
    };
    function updateDot(inputId) {
        var inp = document.getElementById(inputId);
        var dot = document.getElementById('dot_' + inputId);
        if (!inp || !dot) return;
        var hex = fiberColors[(inp.value||'').toLowerCase().trim()];
        dot.style.background = hex || '#ccc';
        dot.style.border = (inp.value||'').toLowerCase().trim()==='white' ? '1px solid #aaa' : '1px solid rgba(0,0,0,0.15)';
    }
    function closeAllPalettes() {
        document.querySelectorAll('.fiber-color-palette').forEach(function(p){ p.style.display='none'; });
    }
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.input-group')) closeAllPalettes();
        var btn = e.target.classList.contains('fiber-color-btn') ? e.target : e.target.closest('.fiber-color-btn');
        if (btn) {
            e.stopPropagation();
            var palette = document.getElementById('palette_' + btn.dataset.target);
            var open = palette && palette.style.display !== 'none';
            closeAllPalettes();
            if (palette && !open) palette.style.display = 'block';
        }
        if (e.target.classList.contains('fiber-color-swatch')) {
            var sw = e.target;
            var inp = document.getElementById(sw.dataset.target);
            if (inp) { inp.value = sw.dataset.color; updateDot(sw.dataset.target); }
            closeAllPalettes();
        }
    });
    document.addEventListener('input', function(e) {
        if (e.target.id === 'fiber_color_input' || e.target.id === 'splitter_color_input') {
            updateDot(e.target.id);
        }
    });
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
    }, 150);
})();
</script>

@endpush
