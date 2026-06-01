@extends('layouts.technician')

@section('title', 'New Connection Application')

@section('content')
<section class="content">
    <div class="container-fluid">
        <form action="{{ route('technician.applications.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            
            <div class="row">
                <div class="col-md-8">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-user"></i> Personal Information</h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Full Name (Bangla) <span class="text-danger">*</span></label>
                                        <input type="text" name="full_name_bangla" class="form-control @error('full_name_bangla') is-invalid @enderror" value="{{ old('full_name_bangla') }}" required>
                                        @error('full_name_bangla')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Full Name (English) <span class="text-danger">*</span></label>
                                        <input type="text" name="full_name_english" class="form-control @error('full_name_english') is-invalid @enderror" value="{{ old('full_name_english') }}" required>
                                        @error('full_name_english')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Father/Husband Name</label>
                                        <input type="text" name="father_husband_name" class="form-control" value="{{ old('father_husband_name') }}">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Mother Name</label>
                                        <input type="text" name="mother_name" class="form-control" value="{{ old('mother_name') }}">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Date of Birth</label>
                                        <input type="date" name="date_of_birth" class="form-control" value="{{ old('date_of_birth') }}">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Gender</label>
                                        <select name="gender" class="form-control">
                                            <option value="male" {{ old('gender') == 'male' ? 'selected' : '' }}>Male</option>
                                            <option value="female" {{ old('gender') == 'female' ? 'selected' : '' }}>Female</option>
                                            <option value="other" {{ old('gender') == 'other' ? 'selected' : '' }}>Other</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Email</label>
                                        <input type="email" name="email" class="form-control" value="{{ old('email') }}">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Mobile Number <span class="text-danger">*</span></label>
                                        <input type="text" name="mobile_number" class="form-control @error('mobile_number') is-invalid @enderror" value="{{ old('mobile_number') }}" required>
                                        @error('mobile_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Alternative Mobile</label>
                                        <input type="text" name="alternative_mobile" class="form-control" value="{{ old('alternative_mobile') }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card card-danger">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-id-card"></i> NID Information (Mandatory)</h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label>NID Number <span class="text-danger">*</span></label>
                                        <input type="text" name="nid_number" class="form-control @error('nid_number') is-invalid @enderror" value="{{ old('nid_number') }}" required>
                                        @error('nid_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>NID Front Image <span class="text-danger">*</span></label>
                                        <input type="file" name="nid_front_image" class="form-control-file @error('nid_front_image') is-invalid @enderror" accept="image/*" required>
                                        @error('nid_front_image')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>NID Back Image <span class="text-danger">*</span></label>
                                        <input type="file" name="nid_back_image" class="form-control-file @error('nid_back_image') is-invalid @enderror" accept="image/*" required>
                                        @error('nid_back_image')<div class="invalid-feedback">{{ $message }}</div>@enderror
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
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Division</label>
                                        <input type="text" name="division" class="form-control" value="{{ old('division') }}">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>District</label>
                                        <input type="text" name="district" class="form-control" value="{{ old('district') }}">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Thana/Upazila</label>
                                        <input type="text" name="thana_upazila" class="form-control" value="{{ old('thana_upazila') }}">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Union/Ward</label>
                                        <input type="text" name="union_ward" class="form-control" value="{{ old('union_ward') }}">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Zone</label>
                                        <select name="zone_id" id="zone_id" class="form-control">
                                            <option value="">Select Zone</option>
                                            @foreach($zones as $zone)
                                            <option value="{{ $zone->id }}" {{ old('zone_id') == $zone->id ? 'selected' : '' }}>{{ $zone->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Sub-Zone</label>
                                        <select name="sub_zone_id" id="sub_zone_id" class="form-control">
                                            <option value="">Select Sub-Zone</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label>House/Road/Floor</label>
                                        <textarea name="house_road_floor" class="form-control" rows="2">{{ old('house_road_floor') }}</textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Latitude</label>
                                        <input type="text" name="latitude" class="form-control" value="{{ old('latitude') }}">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Longitude</label>
                                        <input type="text" name="longitude" class="form-control" value="{{ old('longitude') }}">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Nearby Landmark</label>
                                        <input type="text" name="nearby_landmark" class="form-control" value="{{ old('nearby_landmark') }}">
                                    </div>
                                </div>
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
                                <label>Connection Type <span class="text-danger">*</span></label>
                                <select name="connection_type" class="form-control" required>
                                    <option value="PPPoE" {{ old('connection_type', 'PPPoE') == 'PPPoE' ? 'selected' : '' }}>PPPoE</option>
                                    <option value="Static IP" {{ old('connection_type') == 'Static IP' ? 'selected' : '' }}>Static IP</option>
                                    <option value="DHCP" {{ old('connection_type') == 'DHCP' ? 'selected' : '' }}>DHCP</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Cable Type</label>
                                <select name="connection_medium" class="form-control">
                                    <option value="utp" {{ old('connection_medium') == 'utp' ? 'selected' : '' }}>UTP Cable</option>
                                    <option value="fiber" {{ old('connection_medium') == 'fiber' ? 'selected' : '' }}>Optical Fiber</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Package</label>
                                <select name="package_id" class="form-control">
                                    <option value="">Select Package</option>
                                    @foreach($packages as $package)
                                    <option value="{{ $package->id }}" {{ old('package_id') == $package->id ? 'selected' : '' }}>
                                        {{ $package->name }} - ৳{{ number_format($package->price ?? 0) }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Billing Cycle</label>
                                <select name="billing_cycle" class="form-control">
                                    <option value="monthly">Monthly</option>
                                    <option value="quarterly">Quarterly</option>
                                    <option value="half_yearly">Half Yearly</option>
                                    <option value="yearly">Yearly</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Installation Charge</label>
                                <input type="number" name="installation_charge" class="form-control" value="{{ old('installation_charge', 0) }}">
                            </div>
                            <div class="form-group">
                                <label>IP Type</label>
                                <select name="ip_type" class="form-control">
                                    <option value="dynamic">Dynamic</option>
                                    <option value="static">Static</option>
                                </select>
                            </div>
                            <div class="row">
                                <div class="col-6">
                                    <div class="form-check">
                                        <input type="checkbox" name="onu_required" class="form-check-input" value="1" {{ old('onu_required') ? 'checked' : '' }}>
                                        <label class="form-check-label">ONU Required</label>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-group mb-0">
                                        <label class="form-check-label">Cable Type</label>
                                        <select name="connection_medium" class="form-control form-control-sm">
                                            <option value="utp" {{ old('connection_medium') == 'utp' ? 'selected' : '' }}>UTP Cable</option>
                                            <option value="fiber" {{ old('connection_medium') == 'fiber' ? 'selected' : '' }}>Optical Fiber</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group mt-3">
                                <label>Cable Length (meters)</label>
                                <input type="number" name="cable_length_meters" class="form-control" value="{{ old('cable_length_meters') }}">
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
                                        <input type="text" name="fiber_vendor" class="form-control" value="{{ old('fiber_vendor') }}" placeholder="e.g. Corning, YOFC">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Fiber Code</label>
                                        <input type="text" name="fiber_code" class="form-control" value="{{ old('fiber_code') }}" placeholder="e.g. FBR-001">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Fiber Core Type</label>
                                        <input type="text" name="fiber_core_type" class="form-control" value="{{ old('fiber_core_type') }}" placeholder="e.g. 2 Core, 4 Core, 12 Core">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Fiber Color</label>
                                        <div class="input-group">
    <input type="text" name="fiber_color" id="fiber_color_input" class="form-control" value="{{ old('fiber_color') }}" placeholder="e.g. Blue, Red" autocomplete="off" readonly>
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
                                        <input type="text" name="splitter_number" class="form-control" value="{{ old('splitter_number') }}" placeholder="e.g. SP-01">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Splitter Color</label>
                                        <div class="input-group">
    <input type="text" name="splitter_color" id="splitter_color_input" class="form-control" value="{{ old('splitter_color') }}" placeholder="e.g. Green, Yellow" autocomplete="off" readonly>
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
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>ONU Brand</label>
                                        <input type="text" name="onu_brand" class="form-control" value="{{ old('onu_brand') }}" placeholder="e.g. Huawei, ZTE, VSOL">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>ONU MAC/Serial</label>
                                        <input type="text" name="onu_mac" class="form-control" value="{{ old('onu_mac') }}" placeholder="e.g. AA:BB:CC:DD:EE:FF">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card card-warning">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-clipboard-check"></i> Technician Assessment</h3>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label>Installation Feasibility <span class="text-danger">*</span></label>
                                <select name="installation_feasibility" class="form-control" required>
                                    <option value="1" {{ old('installation_feasibility', '1') == '1' ? 'selected' : '' }}>Yes - Feasible</option>
                                    <option value="0" {{ old('installation_feasibility') == '0' ? 'selected' : '' }}>No - Not Feasible</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Technician Note</label>
                                <textarea name="technician_note" class="form-control" rows="3">{{ old('technician_note') }}</textarea>
                            </div>
                        </div>
                    </div>

                    <div class="card card-secondary">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-file-upload"></i> Documents</h3>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label>House Owner Permission</label>
                                <input type="file" name="house_owner_permission" class="form-control-file" accept="image/*,.pdf">
                            </div>
                            <div class="form-group">
                                <label>Installation Place Photo</label>
                                <input type="file" name="installation_place_photo" class="form-control-file" accept="image/*">
                            </div>
                            <div class="form-group">
                                <label>Customer Signature</label>
                                <input type="file" name="customer_signature" class="form-control-file" accept="image/*">
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block btn-lg">
                        <i class="fas fa-save"></i> Save Application
                    </button>
                </div>
            </div>
        </form>
    </div>
</section>
@endsection

@push('scripts')
<script>
document.getElementById('zone_id').addEventListener('change', function() {
    const zoneId = this.value;
    const subZoneSelect = document.getElementById('sub_zone_id');
    subZoneSelect.innerHTML = '<option value="">Loading...</option>';
    
    if (zoneId) {
        fetch(`{{ route('technician.applications.sub-zones') }}?zone_id=${zoneId}`)
            .then(response => response.json())
            .then(data => {
                subZoneSelect.innerHTML = '<option value="">Select Sub-Zone</option>';
                data.forEach(sz => {
                    subZoneSelect.innerHTML += `<option value="${sz.id}">${sz.name}</option>`;
                });
            });
    } else {
        subZoneSelect.innerHTML = '<option value="">Select Sub-Zone</option>';
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
