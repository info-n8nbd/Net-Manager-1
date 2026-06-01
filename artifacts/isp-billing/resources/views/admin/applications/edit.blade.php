@extends('layouts.admin')

@section('title', 'Edit Application')

@section('content')
        <form action="{{ route('admin.applications.update', $application) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            
            <div class="row">
                <div class="col-md-8">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-user"></i> Personal Information</h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                @php
                                    $appNameParts = explode(' ', old('full_name_english', $application->full_name_english), 2);
                                @endphp
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>First Name <span class="text-danger">*</span></label>
                                        <input type="text" name="first_name" class="form-control" value="{{ old('first_name', $appNameParts[0] ?? '') }}" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Last Name <span class="text-danger">*</span></label>
                                        <input type="text" name="last_name" class="form-control" value="{{ old('last_name', $appNameParts[1] ?? '') }}" required>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Father/Husband Name</label>
                                        <input type="text" name="father_husband_name" class="form-control" value="{{ old('father_husband_name', $application->father_husband_name) }}">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Mother Name</label>
                                        <input type="text" name="mother_name" class="form-control" value="{{ old('mother_name', $application->mother_name) }}">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Date of Birth</label>
                                        <input type="date" name="date_of_birth" class="form-control" value="{{ old('date_of_birth', $application->date_of_birth?->format('Y-m-d')) }}">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Gender</label>
                                        <select name="gender" class="form-control">
                                            <option value="male" {{ old('gender', $application->gender) == 'male' ? 'selected' : '' }}>Male</option>
                                            <option value="female" {{ old('gender', $application->gender) == 'female' ? 'selected' : '' }}>Female</option>
                                            <option value="other" {{ old('gender', $application->gender) == 'other' ? 'selected' : '' }}>Other</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Occupation</label>
                                        <input type="text" name="occupation" class="form-control" value="{{ old('occupation', $application->occupation) }}" placeholder="e.g. Business, Student">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Email</label>
                                        <input type="email" name="email" class="form-control" value="{{ old('email', $application->email) }}">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Mobile Number <span class="text-danger">*</span></label>
                                        <input type="text" name="mobile_number" class="form-control" value="{{ old('mobile_number', $application->mobile_number) }}" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Alternative Mobile</label>
                                        <input type="text" name="alternative_mobile" class="form-control" value="{{ old('alternative_mobile', $application->alternative_mobile) }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card card-info">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-map-marker-alt"></i> Address Information</h3>
                        </div>
                        <div class="card-body">
                            <input type="hidden" name="division" id="division_text" value="{{ old('division', $application->division) }}">
                            <input type="hidden" name="district" id="district_text" value="{{ old('district', $application->district) }}">
                            <input type="hidden" name="thana_upazila" id="thana_upazila_text" value="{{ old('thana_upazila', $application->thana_upazila) }}">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Division</label>
                                        <select name="division_select" class="form-control" id="division_id">
                                            <option value="">-- Select Division --</option>
                                            @foreach($divisions as $div)
                                            <option value="{{ $div }}" {{ old('division', $application->division) == $div ? 'selected' : '' }}>{{ $div }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>District</label>
                                        <select name="district_id" class="form-control" id="district_id_sel">
                                            <option value="">-- Select Division First --</option>
                                            @if($application->division || $application->district_id)
                                                @foreach($districts->filter(fn($d) => $application->division ? $d->division == $application->division : true) as $dist)
                                                <option value="{{ $dist->id }}" {{ old('district_id', $application->district_id) == $dist->id ? 'selected' : '' }}>{{ $dist->name }}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Thana/Upazila</label>
                                        <select name="upazila_id" class="form-control" id="upazila_id">
                                            <option value="">-- Select District First --</option>
                                            @if($application->district_id)
                                                @foreach($upazilas->where('district_id', $application->district_id) as $upz)
                                                <option value="{{ $upz->id }}" {{ old('upazila_id', $application->upazila_id) == $upz->id ? 'selected' : '' }}>{{ $upz->name }}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Zone</label>
                                        <select name="zone_id" class="form-control" id="zone_id">
                                            <option value="">-- Select Thana/Upazila First --</option>
                                            @if($application->upazila_id || $application->zone_id)
                                                @php
                                                    $appUpazilaId = $application->upazila_id ?? ($application->zone ? $application->zone->upazila_id : null);
                                                    $filteredZones = $appUpazilaId ? $zones->where('upazila_id', $appUpazilaId) : $zones;
                                                @endphp
                                                @foreach($filteredZones as $zone)
                                                <option value="{{ $zone->id }}" {{ old('zone_id', $application->zone_id) == $zone->id ? 'selected' : '' }}>{{ $zone->name }}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Sub Zone</label>
                                        <select name="sub_zone_id" class="form-control" id="area_id">
                                            <option value="">-- Select Zone First --</option>
                                            @if($application->zone_id)
                                                @foreach($areas->where('zone_id', $application->zone_id) as $area)
                                                <option value="{{ $area->id }}" {{ old('sub_zone_id', $application->sub_zone_id) == $area->id ? 'selected' : '' }}>{{ $area->name }}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>TJ Box</label>
                                        <select name="box_id" class="form-control" id="box_id">
                                            <option value="">-- Select Sub Zone First --</option>
                                            @if($application->sub_zone_id)
                                                @foreach($boxes->where('area_id', $application->sub_zone_id) as $box)
                                                <option value="{{ $box->id }}" {{ old('box_id', $application->box_id) == $box->id ? 'selected' : '' }}>{{ $box->name }}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>House/Road/Floor</label>
                                <textarea name="house_road_floor" class="form-control" rows="2">{{ old('house_road_floor', $application->house_road_floor) }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card card-success">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-wifi"></i> Connection Details</h3>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label>Connection Type</label>
                                <select name="connection_type" class="form-control">
                                    <option value="PPPoE" {{ old('connection_type', $application->connection_type) == 'PPPoE' ? 'selected' : '' }}>PPPoE</option>
                                    <option value="Static IP" {{ old('connection_type', $application->connection_type) == 'Static IP' ? 'selected' : '' }}>Static IP</option>
                                    <option value="DHCP" {{ old('connection_type', $application->connection_type) == 'DHCP' ? 'selected' : '' }}>DHCP</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Package</label>
                                <select name="package_id" class="form-control" id="app_package_id">
                                    <option value="">Select Package</option>
                                    @foreach($packages as $package)
                                    <option value="{{ $package->id }}" data-mikrotik-profile="{{ $package->mikrotik_profile }}" {{ old('package_id', $application->package_id) == $package->id ? 'selected' : '' }}>
                                        {{ $package->name }} - ৳{{ number_format($package->price) }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label>MikroTik Profile <small class="text-muted">(Speed)</small></label>
                                <select name="mikrotik_profile_name" class="form-control" id="app_mikrotik_profile">
                                    <option value="">-- Select Profile --</option>
                                    @foreach($availableProfiles ?? [] as $profile)
                                    <option value="{{ $profile }}" {{ old('mikrotik_profile_name', $application->mikrotik_profile_name) == $profile ? 'selected' : '' }}>{{ $profile }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Billing Cycle</label>
                                <select name="billing_cycle" class="form-control">
                                    <option value="monthly" {{ old('billing_cycle', $application->billing_cycle) == 'monthly' ? 'selected' : '' }}>Monthly</option>
                                    <option value="quarterly" {{ old('billing_cycle', $application->billing_cycle) == 'quarterly' ? 'selected' : '' }}>Quarterly</option>
                                    <option value="half_yearly" {{ old('billing_cycle', $application->billing_cycle) == 'half_yearly' ? 'selected' : '' }}>Half Yearly</option>
                                    <option value="yearly" {{ old('billing_cycle', $application->billing_cycle) == 'yearly' ? 'selected' : '' }}>Yearly</option>
                                </select>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Installation Charge (৳)</label>
                                        <input type="number" name="installation_charge" class="form-control" value="{{ old('installation_charge', $application->installation_charge) }}" min="0">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Cable Length (m)</label>
                                        <input type="number" name="cable_length_meters" class="form-control" value="{{ old('cable_length_meters', $application->cable_length_meters) }}" min="0">
                                    </div>
                                </div>
                            </div>
                            <div class="form-group mb-3">
                                <div class="custom-control custom-switch">
                                    <input type="hidden" name="is_free" value="0">
                                    <input type="checkbox" class="custom-control-input" id="is_free" name="is_free" value="1" {{ old('is_free', $application->is_free) ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="is_free" style="font-weight: 600; color: #6c757d; text-transform: uppercase;">FREE CLIENT (NO MONTHLY BILL)</label>
                                </div>
                            </div>
                            <div class="form-group" id="free_reason_group" style="{{ old('is_free', $application->is_free) ? '' : 'display:none' }}">
                                <label>Free Reason</label>
                                <input type="text" name="free_reason" class="form-control" value="{{ old('free_reason', $application->free_reason) }}" placeholder="e.g. Owner, Staff, Mosque">
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="onu_required" name="onu_required" value="1" {{ old('onu_required', $application->onu_required) ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="onu_required"><i class="fas fa-barcode mr-1"></i> ISP ONU</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="router_required" name="router_required" value="1" {{ old('router_required', $application->router_required) ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="router_required"><i class="fas fa-wifi mr-1"></i> ISP Router</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="fiber_required" name="fiber_required" value="1" {{ old('fiber_required', $application->fiber_required ?? true) ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="fiber_required"><i class="fas fa-project-diagram mr-1"></i> ISP Fiber/UTP Cable</label>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Cable Type</label>
                                        <select name="connection_medium" class="form-control">
                                            <option value="fiber" {{ old('connection_medium', $application->connection_medium) == 'fiber' ? 'selected' : '' }}>Optical Fiber</option>
                                            <option value="utp" {{ old('connection_medium', $application->connection_medium) == 'utp' ? 'selected' : '' }}>UTP Cable</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>IP Type</label>
                                        <select name="ip_type" class="form-control">
                                            <option value="dynamic" {{ old('ip_type', $application->ip_type) == 'dynamic' ? 'selected' : '' }}>Dynamic</option>
                                            <option value="static" {{ old('ip_type', $application->ip_type) == 'static' ? 'selected' : '' }}>Static</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card card-warning">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-handshake"></i> Referral Partner</h3>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label>Referral Partner</label>
                                <select name="agent_id" class="form-control" id="appAgentSelect">
                                    <option value="" data-commission="">No Referral Partner</option>
                                    @foreach($agents ?? [] as $agent)
                                    <option value="{{ $agent->id }}" data-commission="{{ $agent->default_commission }}">{{ $agent->name }} {{ $agent->personal_contact ? '(' . $agent->personal_contact . ')' : '' }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Commission (%)</label>
                                <input type="number" name="commission_percent" id="appCommissionPercent" class="form-control" step="0.01" min="0" max="100" value="" placeholder="0.00">
                            </div>
                        </div>
                    </div>

                    <div class="card card-info">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-satellite-dish"></i> Fiber & ONU Details</h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Fiber Brand</label>
                                        <input type="text" name="fiber_vendor" class="form-control" value="{{ old('fiber_vendor', $application->fiber_vendor) }}" placeholder="e.g. Corning, YOFC">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Fiber Code</label>
                                        <input type="text" name="fiber_code" class="form-control" value="{{ old('fiber_code', $application->fiber_code) }}" placeholder="e.g. FBR-001">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Fiber Core Type</label>
                                        <input type="text" name="fiber_core_type" class="form-control" value="{{ old('fiber_core_type', $application->fiber_core_type) }}" placeholder="e.g. 2 Core, 4 Core, 12 Core">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Fiber Color</label>
                                        <div class="input-group">
    <input type="text" name="fiber_color" id="fiber_color_input" class="form-control" value="{{ old('fiber_color', $application->fiber_color) }}" placeholder="e.g. Blue, Red" autocomplete="off">
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
                                        <input type="text" name="splitter_number" class="form-control" value="{{ old('splitter_number', $application->splitter_number) }}" placeholder="e.g. SP-01">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Splitter Color</label>
                                        <div class="input-group">
    <input type="text" name="splitter_color" id="splitter_color_input" class="form-control" value="{{ old('splitter_color', $application->splitter_color) }}" placeholder="e.g. Green, Yellow" autocomplete="off">
    <div class="input-group-append">
        <button class="btn btn-outline-secondary fiber-color-btn" type="button" data-target="splitter_color_input" style="padding:0 8px;">
            <span class="fiber-dot" id="dot_splitter_color_input" style="display:inline-block;width:18px;height:18px;border-radius:3px;background:#ccc;border:1px solid #aaa;vertical-align:middle;"></span>
        </button>
    </div>
    <div class="fiber-color-palette" id="palette_splitter_color_input" style="display:none;position:absolute;top:100%;left:0;z-index:9999;background:#fff;border:1px solid #ddd;border-radius:6px;padding:8px;box-shadow:0 4px 12px rgba(0,0,0,0.15);width:170px;">
        <div style="display:flex;flex-wrap:wrap;"><span class="fiber-color-swatch" data-color="Blue" data-hex="#0057B8" data-target="splitter_color_input" style="display:inline-block;width:28px;height:28px;background:#0057B8;border-radius:4px;cursor:pointer;margin:2px;border:1px solid rgba(0,0,0,0.15);" title="Blue"></span><span class="fiber-color-swatch" data-color="Orange" data-hex="#FF8C00" data-target="splitter_color_input" style="display:inline-block;width:28px;height:28px;background:#FF8C00;border-radius:4px;cursor:pointer;margin:2px;border:1px solid rgba(0,0,0,0.15);" title="Orange"></span><span class="fiber-color-swatch" data-color="Green" data-hex="#28a745" data-target="splitter_color_input" style="display:inline-block;width:28px;height:28px;background:#28a745;border-radius:4px;cursor:pointer;margin:2px;border:1px solid rgba(0,0,0,0.15);" title="Green"></span><span class="fiber-color-swatch" data-color="Brown" data-hex="#8B4513" data-target="splitter_color_input" style="display:inline-block;width:28px;height:28px;background:#8B4513;border-radius:4px;cursor:pointer;margin:2px;border:1px solid rgba(0,0,0,0.15);" title="Brown"></span><span class="fiber-color-swatch" data-color="Slate" data-hex="#708090" data-target="splitter_color_input" style="display:inline-block;width:28px;height:28px;background:#708090;border-radius:4px;cursor:pointer;margin:2px;border:1px solid rgba(0,0,0,0.15);" title="Slate"></span><span class="fiber-color-swatch" data-color="White" data-hex="#e9ecef" data-target="splitter_color_input" style="display:inline-block;width:28px;height:28px;background:#e9ecef;border-radius:4px;cursor:pointer;margin:2px;border:1px solid rgba(0,0,0,0.15);" title="White"></span><span class="fiber-color-swatch" data-color="Red" data-hex="#dc3545" data-target="splitter_color_input" style="display:inline-block;width:28px;height:28px;background:#dc3545;border-radius:4px;cursor:pointer;margin:2px;border:1px solid rgba(0,0,0,0.15);" title="Red"></span><span class="fiber-color-swatch" data-color="Black" data-hex="#222222" data-target="splitter_color_input" style="display:inline-block;width:28px;height:28px;background:#222222;border-radius:4px;cursor:pointer;margin:2px;border:1px solid rgba(0,0,0,0.15);" title="Black"></span><span class="fiber-color-swatch" data-color="Yellow" data-hex="#ffc107" data-target="splitter_color_input" style="display:inline-block;width:28px;height:28px;background:#ffc107;border-radius:4px;cursor:pointer;margin:2px;border:1px solid rgba(0,0,0,0.15);" title="Yellow"></span><span class="fiber-color-swatch" data-color="Violet" data-hex="#6f42c1" data-target="splitter_color_input" style="display:inline-block;width:28px;height:28px;background:#6f42c1;border-radius:4px;cursor:pointer;margin:2px;border:1px solid rgba(0,0,0,0.15);" title="Violet"></span><span class="fiber-color-swatch" data-color="Rose" data-hex="#e83e8c" data-target="splitter_color_input" style="display:inline-block;width:28px;height:28px;background:#e83e8c;border-radius:4px;cursor:pointer;margin:2px;border:1px solid rgba(0,0,0,0.15);" title="Rose"></span><span class="fiber-color-swatch" data-color="Aqua" data-hex="#20c997" data-target="splitter_color_input" style="display:inline-block;width:28px;height:28px;background:#20c997;border-radius:4px;cursor:pointer;margin:2px;border:1px solid rgba(0,0,0,0.15);" title="Aqua"></span></div>
        <div style="margin-top:4px;font-size:10px;color:#999;">Click a color to select</div>
    </div>
</div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>ONU Brand</label>
                                        <input type="text" name="onu_brand" class="form-control" value="{{ old('onu_brand', $application->onu_brand) }}" placeholder="e.g. Huawei, ZTE, VSOL">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>ONU MAC</label>
                                        <input type="text" name="onu_mac" class="form-control" value="{{ old('onu_mac', $application->onu_mac) }}" placeholder="e.g. AA:BB:CC:DD:EE:FF">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>ONU Serial</label>
                                        <input type="text" name="onu_serial" class="form-control" value="{{ old('onu_serial', $application->onu_serial) }}" placeholder="e.g. HWTC12345678">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card card-warning">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-sticky-note"></i> Admin Note</h3>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <textarea name="admin_note" class="form-control" rows="3">{{ old('admin_note', $application->admin_note) }}</textarea>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block btn-lg">
                        <i class="fas fa-save"></i> Update Application
                    </button>
                    <a href="{{ route('admin.applications.show', $application) }}" class="btn btn-secondary btn-block">
                        Cancel
                    </a>
                </div>
            </div>
        </form>
    </div>
</section>
@endsection

@push('scripts')
<script>
(function() {
    var divisionSel = document.getElementById('division_id');
    var districtSel = document.getElementById('district_id_sel');
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

    function syncHiddenTexts() {
        document.getElementById('division_text').value = divisionSel.value || '';
        var distOpt = districtSel.selectedOptions[0];
        document.getElementById('district_text').value = (distOpt && distOpt.value) ? distOpt.text : '';
        var upzOpt = upazilaSel.selectedOptions[0];
        document.getElementById('thana_upazila_text').value = (upzOpt && upzOpt.value) ? upzOpt.text : '';
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
        syncHiddenTexts();
        if (this.value) {
            loadOptions('/admin/api/districts-by-division/' + encodeURIComponent(this.value), districtSel, '-- Select District --');
        }
    });

    districtSel.addEventListener('change', function() {
        resetFrom(3);
        syncHiddenTexts();
        if (this.value) {
            loadOptions('/admin/api/upazilas-by-district/' + this.value, upazilaSel, '-- Select Thana/Upazila --');
        }
    });

    upazilaSel.addEventListener('change', function() {
        resetFrom(4);
        syncHiddenTexts();
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
            loadOptions('/admin/api/boxes-by-area/' + this.value, boxSel, '-- Select TJ Box --');
        }
    });
})();

document.getElementById('is_free').addEventListener('change', function() {
    document.getElementById('free_reason_group').style.display = this.checked ? '' : 'none';
});

$('#appAgentSelect').on('change', function() {
    var commission = $(this).find('option:selected').data('commission');
    if (commission) {
        $('#appCommissionPercent').val(commission);
    } else {
        $('#appCommissionPercent').val('');
    }
});

$('#app_package_id').on('change', function() {
    var profile = $(this).find(':selected').data('mikrotik-profile');
    if (profile) {
        if ($('#app_mikrotik_profile option[value="' + profile + '"]').length) {
            $('#app_mikrotik_profile').val(profile);
        } else {
            $('#app_mikrotik_profile').val('');
        }
    } else {
        $('#app_mikrotik_profile').val('');
    }
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
    }, 150);
})();
</script>

@endpush
