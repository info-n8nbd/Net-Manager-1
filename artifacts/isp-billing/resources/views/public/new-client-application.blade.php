@extends('layouts.auth')

@section('title', 'New Connection Application')

@section('content')
<style>
    .app-wrap { max-width: 520px; margin: 0 auto; }

    .app-card {
        background: #fff;
        border-radius: 10px;
        box-shadow: 0 8px 30px rgba(0,0,0,0.15);
        overflow: hidden;
    }

    .app-steps {
        display: flex;
        background: #1e3c72;
    }
    .app-steps .step-tab {
        flex: 1;
        padding: 12px 6px;
        text-align: center;
        cursor: pointer;
        color: rgba(255,255,255,0.45);
        font-size: 11px;
        font-weight: 600;
        border: none;
        background: none;
        position: relative;
        transition: all 0.3s;
    }
    .app-steps .step-tab.active { color: #fff; background: rgba(255,255,255,0.1); }
    .app-steps .step-tab.active::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 25%;
        right: 25%;
        height: 3px;
        background: #fff;
        border-radius: 3px 3px 0 0;
    }
    .app-steps .step-tab.done { color: rgba(255,255,255,0.8); }
    .app-steps .step-num {
        display: inline-flex;
        width: 22px;
        height: 22px;
        border-radius: 50%;
        align-items: center;
        justify-content: center;
        font-size: 11px;
        font-weight: 700;
        margin-bottom: 2px;
        border: 2px solid rgba(255,255,255,0.25);
    }
    .app-steps .step-tab.active .step-num { background: #fff; color: #1e3c72; border-color: #fff; }
    .app-steps .step-tab.done .step-num { background: rgba(255,255,255,0.3); border-color: transparent; }

    .app-body { padding: 20px; }

    .step-panel { display: none; }
    .step-panel.active { display: block; animation: fadeSlide 0.3s ease; }
    @keyframes fadeSlide { from { opacity: 0; transform: translateX(15px); } to { opacity: 1; transform: translateX(0); } }

    .sec-title {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 13px;
        font-weight: 700;
        color: #1e3c72;
        margin-bottom: 12px;
        padding-bottom: 6px;
        border-bottom: 1px solid #e2e8f0;
    }
    .sec-title i {
        width: 26px;
        height: 26px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 6px;
        font-size: 11px;
        color: #fff;
        background: #1e3c72;
    }

    .f-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 10px; }
    .f-grid.full { grid-template-columns: 1fr; }
    @media (max-width: 480px) { .f-grid { grid-template-columns: 1fr; } }

    .f-field label {
        display: block;
        font-size: 11px;
        font-weight: 600;
        color: #475569;
        margin-bottom: 3px;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }
    .f-field label .req { color: #ef4444; }
    .f-field .f-input {
        width: 100%;
        padding: 8px 10px;
        font-size: 13px;
        border: 1.5px solid #e2e8f0;
        border-radius: 6px;
        background: #f8fafc;
        transition: all 0.2s;
        color: #1e293b;
        outline: none;
        font-family: inherit;
    }
    .f-field .f-input:focus { border-color: #1e3c72; background: #fff; box-shadow: 0 0 0 3px rgba(30,60,114,0.1); }
    .f-field .f-input.is-invalid { border-color: #ef4444; }
    .f-field select.f-input { appearance: auto; }
    .f-field textarea.f-input { min-height: 55px; resize: vertical; }

    .toggle-row { display: flex; gap: 6px; }
    .toggle-row .tog {
        flex: 1;
        padding: 7px;
        border: 1.5px solid #e2e8f0;
        border-radius: 6px;
        background: #f8fafc;
        cursor: pointer;
        text-align: center;
        font-size: 12px;
        font-weight: 600;
        color: #64748b;
        transition: all 0.2s;
    }
    .toggle-row .tog.active { border-color: #1e3c72; background: #1e3c72; color: #fff; }
    .toggle-row input[type="radio"] { display: none; }

    .upload-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin-bottom: 10px; }
    .upload-item { text-align: center; }
    .upload-item label { font-size: 10px; font-weight: 600; color: #475569; margin-bottom: 3px; display: block; text-transform: uppercase; }
    .upload-box {
        width: 100%;
        aspect-ratio: 1;
        border: 2px dashed #cbd5e1;
        border-radius: 8px;
        cursor: pointer;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        transition: all 0.2s;
        background: #f8fafc;
    }
    .upload-box:hover { border-color: #1e3c72; }
    .upload-box img { width: 100%; height: 100%; object-fit: cover; display: none; }
    .upload-box .ph { text-align: center; color: #94a3b8; }
    .upload-box .ph i { font-size: 18px; }
    .upload-box .ph small { font-size: 9px; display: block; margin-top: 2px; font-weight: 600; }

    .f-actions { display: flex; gap: 10px; margin-top: 14px; }
    .f-btn {
        flex: 1;
        padding: 10px 14px;
        border: none;
        border-radius: 6px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        transition: all 0.2s;
        font-family: inherit;
    }
    .btn-back { background: #f1f5f9; color: #475569; }
    .btn-back:hover { background: #e2e8f0; }
    .btn-next { background: #1e3c72; color: #fff; }
    .btn-next:hover { opacity: 0.9; }
    .btn-submit { background: #28a745; color: #fff; }
    .btn-submit:hover { opacity: 0.9; }

    .f-alert { padding: 10px; font-size: 12px; margin-bottom: 10px; border-radius: 6px; background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
    .f-alert ul { margin: 0; padding-left: 16px; }

    .f-divider { height: 1px; background: #e2e8f0; margin: 14px 0; }

    .map-status { display: none; padding: 8px; border-radius: 6px; margin-bottom: 8px; font-size: 12px; }
    .map-btn {
        width: 100%;
        padding: 8px;
        border: 1.5px solid #e2e8f0;
        border-radius: 6px;
        background: #f8fafc;
        cursor: pointer;
        font-size: 12px;
        font-weight: 600;
        color: #475569;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        font-family: inherit;
    }
    .map-btn:hover { border-color: #1e3c72; color: #1e3c72; }
    .map-link {
        display: none;
        width: 100%;
        text-align: center;
        padding: 8px;
        background: #1e3c72;
        color: white;
        border-radius: 6px;
        text-decoration: none;
        font-weight: 600;
        font-size: 12px;
        margin-top: 6px;
    }
    .map-link:hover { color: #fff; }
    .map-hint { font-size: 10px; color: #94a3b8; text-align: center; margin-top: 4px; }

    .app-footer { text-align: center; margin-top: 12px; }
    .app-footer a { color: rgba(255,255,255,0.8); text-decoration: none; font-size: 12px; }
    .app-footer a:hover { color: #fff; text-decoration: underline; }
</style>

<div class="app-wrap">
    <div class="logo">
        <a href="/">
            <div class="logo-circle">
                <img src="{{ asset($siteLogo ?? 'images/logo.png') }}?t={{ time() }}" alt="{{ $companyName ?? 'SOFTYNEX' }}" style="width: 50px; height: auto;">
            </div>
            <div class="brand-name">{{ $companyName ?? 'SOFTYNEX' }}</div>
            <div class="brand-tagline">{{ $companyTagline ?? 'Connecting You to the Future' }}</div>
        </a>
    </div>

    <div class="app-card">
        <div class="app-steps">
            <div class="step-tab active" data-step="1">
                <div class="step-num">1</div>
                <div>Personal</div>
            </div>
            <div class="step-tab" data-step="2">
                <div class="step-num">2</div>
                <div>Address</div>
            </div>
            <div class="step-tab" data-step="3">
                <div class="step-num">3</div>
                <div>Service</div>
            </div>
            <div class="step-tab" data-step="4">
                <div class="step-num">4</div>
                <div>Finish</div>
            </div>
        </div>

        <div class="app-body">
            @if($errors->any())
                <div class="f-alert">
                    <ul>
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('public.application.store') }}" method="POST" id="appForm" enctype="multipart/form-data">
                @csrf

                <div class="step-panel active" data-step="1">
                    <div class="sec-title"><i class="fas fa-user"></i> Personal Details</div>
                    <div class="f-grid">
                        <div class="f-field">
                            <label>First Name <span class="req">*</span></label>
                            <input type="text" name="first_name" class="f-input" value="{{ old('first_name') }}" required placeholder="First name">
                        </div>
                        <div class="f-field">
                            <label>Last Name <span class="req">*</span></label>
                            <input type="text" name="last_name" class="f-input" value="{{ old('last_name') }}" required placeholder="Last name">
                        </div>
                    </div>
                    <div class="f-grid">
                        <div class="f-field">
                            <label>Father's Name</label>
                            <input type="text" name="father_husband_name" class="f-input" value="{{ old('father_husband_name') }}" placeholder="Father's name">
                        </div>
                        <div class="f-field">
                            <label>Mother's Name</label>
                            <input type="text" name="mother_name" class="f-input" value="{{ old('mother_name') }}" placeholder="Mother's name">
                        </div>
                    </div>
                    <div class="f-grid">
                        <div class="f-field">
                            <label>Date of Birth</label>
                            <input type="date" name="date_of_birth" class="f-input" value="{{ old('date_of_birth') }}">
                        </div>
                        <div class="f-field">
                            <label>Occupation</label>
                            <input type="text" name="occupation" class="f-input" value="{{ old('occupation') }}" placeholder="e.g. Business, Student, Service">
                        </div>
                    </div>
                    <div class="f-grid">
                        <div class="f-field">
                            <label>Gender</label>
                            <select name="gender" class="f-input">
                                <option value="">Select</option>
                                <option value="male" {{ old('gender') == 'male' ? 'selected' : '' }}>Male</option>
                                <option value="female" {{ old('gender') == 'female' ? 'selected' : '' }}>Female</option>
                                <option value="other" {{ old('gender') == 'other' ? 'selected' : '' }}>Other</option>
                            </select>
                        </div>
                        <div class="f-field">
                            <label>NID Number <span class="req">*</span></label>
                            <input type="text" name="nid_number" class="f-input" value="{{ old('nid_number') }}" required placeholder="National ID" inputmode="numeric">
                        </div>
                    </div>

                    <div class="f-divider"></div>

                    <div class="sec-title"><i class="fas fa-phone-alt"></i> Contact Info</div>
                    <div class="f-grid">
                        <div class="f-field">
                            <label>Mobile <span class="req">*</span></label>
                            <input type="tel" name="mobile_number" class="f-input" value="{{ old('mobile_number') }}" required placeholder="01XXXXXXXXX">
                        </div>
                        <div class="f-field">
                            <label>Alternate Mobile</label>
                            <input type="tel" name="alternative_mobile" class="f-input" value="{{ old('alternative_mobile') }}" placeholder="Optional">
                        </div>
                    </div>
                    <div class="f-grid full">
                        <div class="f-field">
                            <label>Email</label>
                            <input type="email" name="email" class="f-input" value="{{ old('email') }}" placeholder="your@email.com">
                        </div>
                    </div>

                    <div class="f-actions">
                        <button type="button" class="f-btn btn-next js-next" style="flex:1;">Next <i class="fas fa-arrow-right"></i></button>
                    </div>
                </div>

                <div class="step-panel" data-step="2">
                    <div class="sec-title"><i class="fas fa-map-marker-alt"></i> Location</div>
                    <input type="hidden" name="division" id="division_text" value="{{ old('division') }}">
                    <input type="hidden" name="district" id="district_text" value="{{ old('district') }}">
                    <input type="hidden" name="thana_upazila" id="thana_upazila_text" value="{{ old('thana_upazila') }}">
                    <div class="f-grid">
                        <div class="f-field">
                            <label>Division</label>
                            <select name="division_select" class="f-input" id="sel_division">
                                <option value="">-- Select Division --</option>
                                @foreach($divisions as $div)
                                    <option value="{{ $div }}" {{ old('division_select') == $div ? 'selected' : '' }}>{{ $div }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="f-field">
                            <label>District</label>
                            <select name="district_id" class="f-input" id="sel_district">
                                <option value="">-- Select Division First --</option>
                            </select>
                        </div>
                    </div>
                    <div class="f-grid">
                        <div class="f-field">
                            <label>Thana/Upazila</label>
                            <select name="upazila_id" class="f-input" id="sel_upazila">
                                <option value="">-- Select District First --</option>
                            </select>
                        </div>
                        <div class="f-field">
                            <label>Zone</label>
                            <select name="zone_id" class="f-input" id="sel_zone">
                                <option value="">-- Select Thana/Upazila First --</option>
                            </select>
                        </div>
                    </div>
                    <div class="f-grid">
                        <div class="f-field">
                            <label>Sub Zone</label>
                            <select name="sub_zone_id" class="f-input" id="sel_subzone">
                                <option value="">-- Select Zone First --</option>
                            </select>
                        </div>
                        <div class="f-field">
                            <label>TJ Box</label>
                            <select name="box_id" class="f-input" id="sel_tj">
                                <option value="">-- Select Sub Zone First --</option>
                            </select>
                        </div>
                    </div>
                    <div class="f-grid">
                        <div class="f-field">
                            <label>Nearby Landmark</label>
                            <input type="text" name="nearby_landmark" class="f-input" value="{{ old('nearby_landmark') }}" placeholder="Mosque, School...">
                        </div>
                    </div>
                    <div class="f-grid full">
                        <div class="f-field">
                            <label>Full Address <span class="req">*</span></label>
                            <textarea name="house_road_floor" id="inp_address" class="f-input" required placeholder="House, Road, Floor, Building">{{ old('house_road_floor') }}</textarea>
                        </div>
                    </div>

                    <div class="f-divider"></div>

                    <div class="sec-title"><i class="fas fa-map-pin"></i> Map Location</div>
                    <div id="mapStatus" class="map-status"></div>
                    <input type="hidden" name="google_map_location" id="inp_map_url" value="{{ old('google_map_location') }}">
                    <input type="hidden" name="google_map_lat" id="inp_lat" value="{{ old('google_map_lat') }}">
                    <input type="hidden" name="google_map_lng" id="inp_lng" value="{{ old('google_map_lng') }}">
                    <button type="button" class="map-btn" onclick="getMapLocation()">
                        <i class="fas fa-crosshairs"></i> Auto-detect from Address
                    </button>
                    <a href="#" id="viewMapLink" class="map-link" target="_blank">
                        <i class="fas fa-external-link-alt"></i> View on Google Maps
                    </a>
                    <p class="map-hint">Location will be auto-detected from your address</p>

                    <div class="f-actions">
                        <button type="button" class="f-btn btn-back js-prev"><i class="fas fa-arrow-left"></i> Back</button>
                        <button type="button" class="f-btn btn-next js-next">Next <i class="fas fa-arrow-right"></i></button>
                    </div>
                </div>

                <div class="step-panel" data-step="3">
                    <div class="sec-title"><i class="fas fa-wifi"></i> Package</div>
                    <div class="f-grid">
                        <div class="f-field">
                            <label>Internet Package <span class="req">*</span></label>
                            <select name="package_id" class="f-input" required>
                                <option value="">Select Package</option>
                                @foreach($packages as $package)
                                    <option value="{{ $package->id }}" data-mikrotik-profile="{{ $package->mikrotik_profile }}" {{ old('package_id') == $package->id ? 'selected' : '' }}>
                                        {{ $package->name }} - ৳{{ number_format($package->price) }}/mo
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="f-field">
                            <label>Billing Cycle</label>
                            <select name="billing_cycle" class="f-input">
                                <option value="monthly" {{ old('billing_cycle', 'monthly') == 'monthly' ? 'selected' : '' }}>Monthly</option>
                                <option value="quarterly" {{ old('billing_cycle') == 'quarterly' ? 'selected' : '' }}>Quarterly</option>
                                <option value="yearly" {{ old('billing_cycle') == 'yearly' ? 'selected' : '' }}>Yearly</option>
                            </select>
                        </div>
                    </div>
                    <div class="f-divider"></div>

                    <div class="sec-title"><i class="fas fa-user-cog"></i> Technician</div>
                    <div class="f-grid full">
                        <div class="f-field">
                            <label>Assign Technician(s)</label>
                            <select name="technician_ids[]" class="f-input" multiple style="min-height: 50px;">
                                @foreach($technicians as $technician)
                                    <option value="{{ $technician->id }}" {{ in_array($technician->id, old('technician_ids', [])) ? 'selected' : '' }}>
                                        {{ $technician->name }}
                                    </option>
                                @endforeach
                            </select>
                            <small style="font-size: 10px; color: #94a3b8;">Hold Ctrl/Cmd to select multiple</small>
                        </div>
                    </div>

                    <div class="f-divider"></div>

                    <div class="sec-title"><i class="fas fa-tools"></i> Installation</div>
                    <div class="f-grid">
                        <div class="f-field">
                            <label>Install Fee (৳)</label>
                            <input type="number" name="installation_charge" class="f-input" value="{{ old('installation_charge', 0) }}" min="0">
                        </div>
                        <div class="f-field">
                            <label>Cable Length (m)</label>
                            <input type="number" name="cable_length_meters" class="f-input" value="{{ old('cable_length_meters', 0) }}" min="0">
                        </div>
                    </div>
                    <div class="f-grid">
                        <div class="f-field">
                            <label>ISP ONU</label>
                            <div class="toggle-row">
                                <input type="radio" name="onu_required" value="0" id="onu_no" {{ old('onu_required', '1') == '0' ? 'checked' : '' }}>
                                <label for="onu_no" class="tog {{ old('onu_required', '1') == '0' ? 'active' : '' }}">No</label>
                                <input type="radio" name="onu_required" value="1" id="onu_yes" {{ old('onu_required', '1') == '1' ? 'checked' : '' }}>
                                <label for="onu_yes" class="tog {{ old('onu_required', '1') == '1' ? 'active' : '' }}">Yes</label>
                            </div>
                        </div>
                        <div class="f-field">
                            <label>ISP Router</label>
                            <div class="toggle-row">
                                <input type="radio" name="router_required" value="0" id="router_no" {{ old('router_required', '0') == '0' ? 'checked' : '' }}>
                                <label for="router_no" class="tog {{ old('router_required', '0') == '0' ? 'active' : '' }}">No</label>
                                <input type="radio" name="router_required" value="1" id="router_yes" {{ old('router_required', '0') == '1' ? 'checked' : '' }}>
                                <label for="router_yes" class="tog {{ old('router_required', '0') == '1' ? 'active' : '' }}">Yes</label>
                            </div>
                        </div>
                    </div>
                    <div class="f-grid">
                        <div class="f-field">
                            <label>ISP Fiber/UTP Cable</label>
                            <div class="toggle-row">
                                <input type="radio" name="fiber_required" value="0" id="fiber_no" {{ old('fiber_required', '1') == '0' ? 'checked' : '' }}>
                                <label for="fiber_no" class="tog {{ old('fiber_required', '1') == '0' ? 'active' : '' }}">No</label>
                                <input type="radio" name="fiber_required" value="1" id="fiber_yes" {{ old('fiber_required', '1') == '1' ? 'checked' : '' }}>
                                <label for="fiber_yes" class="tog {{ old('fiber_required', '1') == '1' ? 'active' : '' }}">Yes</label>
                            </div>
                        </div>
                        <div class="f-field">
                            <label>Cable Type</label>
                            <div class="toggle-row">
                                <input type="radio" name="connection_medium" value="utp" id="cable_utp" {{ old('connection_medium', 'fiber') == 'utp' ? 'checked' : '' }}>
                                <label for="cable_utp" class="tog {{ old('connection_medium', 'fiber') == 'utp' ? 'active' : '' }}">UTP</label>
                                <input type="radio" name="connection_medium" value="fiber" id="cable_fiber" {{ old('connection_medium', 'fiber') == 'fiber' ? 'checked' : '' }}>
                                <label for="cable_fiber" class="tog {{ old('connection_medium', 'fiber') == 'fiber' ? 'active' : '' }}">Fiber</label>
                            </div>
                        </div>
                    </div>
                    <div class="f-grid">
                        <div class="f-field">
                            <label>Fiber Brand</label>
                            <input type="text" name="fiber_vendor" class="f-input" value="{{ old('fiber_vendor') }}" placeholder="e.g. Corning, YOFC">
                        </div>
                        <div class="f-field">
                            <label>Fiber Code</label>
                            <input type="text" name="fiber_code" class="f-input" value="{{ old('fiber_code') }}" placeholder="e.g. FC-001">
                        </div>
                    </div>
                    <div class="f-grid">
                        <div class="f-field">
                            <label>Fiber Core Type</label>
                            <input type="text" name="fiber_core_type" class="f-input" value="{{ old('fiber_core_type') }}" placeholder="e.g. 2 Core, 4 Core, 12 Core">
                        </div>
                        <div class="f-field">
                            <label>Fiber Color</label>
                            <div class="input-group">
    <input type="text" name="fiber_color" id="fiber_color_input" class="f-input" value="{{ old('fiber_color') }}" placeholder="Select color" autocomplete="off" readonly style="cursor:pointer;background:#fff;">
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
                    <div class="f-grid">
                        <div class="f-field">
                            <label>Splitter Number</label>
                            <input type="text" name="splitter_number" class="f-input" value="{{ old('splitter_number') }}" placeholder="e.g. SP-01">
                        </div>
                        <div class="f-field">
                            <label>Splitter Color</label>
                            <div class="input-group">
    <input type="text" name="splitter_color" id="splitter_color_input" class="f-input" value="{{ old('splitter_color') }}" placeholder="Select color" autocomplete="off" readonly style="cursor:pointer;background:#fff;">
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
                    <div class="f-grid">
                        <div class="f-field">
                            <label>ONU Brand</label>
                            <input type="text" name="onu_brand" class="f-input" value="{{ old('onu_brand') }}" placeholder="e.g. Huawei, ZTE, VSOL">
                        </div>
                        <div class="f-field">
                            <label>ONU MAC/Serial</label>
                            <input type="text" name="onu_mac" class="f-input" value="{{ old('onu_mac') }}" placeholder="e.g. AA:BB:CC:DD:EE:FF">
                        </div>
                    </div>

                    <div class="f-actions">
                        <button type="button" class="f-btn btn-back js-prev"><i class="fas fa-arrow-left"></i> Back</button>
                        <button type="button" class="f-btn btn-next js-next">Next <i class="fas fa-arrow-right"></i></button>
                    </div>
                </div>

                <div class="step-panel" data-step="4">
                    <div class="sec-title"><i class="fas fa-credit-card"></i> Payment</div>
                    <div class="f-grid">
                        <div class="f-field">
                            <label>Payment Method <span class="req">*</span></label>
                            <select name="payment_method" class="f-input" required>
                                <option value="cash" {{ old('payment_method', 'cash') == 'cash' ? 'selected' : '' }}>Cash</option>
                                <option value="bkash" {{ old('payment_method') == 'bkash' ? 'selected' : '' }}>bKash</option>
                                <option value="nagad" {{ old('payment_method') == 'nagad' ? 'selected' : '' }}>Nagad</option>
                                <option value="rocket" {{ old('payment_method') == 'rocket' ? 'selected' : '' }}>Rocket</option>
                                <option value="bank" {{ old('payment_method') == 'bank' ? 'selected' : '' }}>Bank</option>
                            </select>
                        </div>
                        <div class="f-field">
                            <label>Auto-Inactive Day <span class="req">*</span></label>
                            <select name="auto_inactive_day" class="f-input" required>
                                <option value="">Select Day</option>
                                @for($d = 1; $d <= 31; $d++)
                                    <option value="{{ $d }}" {{ old('auto_inactive_day') == $d ? 'selected' : '' }}>{{ $d }} of month</option>
                                @endfor
                            </select>
                        </div>
                    </div>
                    <div class="f-grid full">
                        <div class="f-field">
                            <label>Additional Notes</label>
                            <textarea name="technician_note" class="f-input" placeholder="Any special requirements or notes">{{ old('technician_note') }}</textarea>
                        </div>
                    </div>

                    <div class="f-divider"></div>

                    <div class="sec-title"><i class="fas fa-camera"></i> Documents</div>
                    <div class="upload-grid">
                        <div class="upload-item">
                            <label>Profile Photo</label>
                            <div class="upload-box" onclick="this.querySelector('input').click()">
                                <input type="file" name="profile_picture" accept="image/*" style="display:none;" onchange="previewImg(this, 'pv_profile')">
                                <img id="pv_profile">
                                <div class="ph" id="ph_profile"><i class="fas fa-user-circle"></i><small>Upload</small></div>
                            </div>
                        </div>
                        <div class="upload-item">
                            <label>NID Front</label>
                            <div class="upload-box" onclick="this.querySelector('input').click()">
                                <input type="file" name="nid_front_image" accept="image/*" style="display:none;" onchange="previewImg(this, 'pv_nid_f')">
                                <img id="pv_nid_f">
                                <div class="ph" id="ph_nid_f"><i class="fas fa-id-card"></i><small>Upload</small></div>
                            </div>
                        </div>
                        <div class="upload-item">
                            <label>NID Back</label>
                            <div class="upload-box" onclick="this.querySelector('input').click()">
                                <input type="file" name="nid_back_image" accept="image/*" style="display:none;" onchange="previewImg(this, 'pv_nid_b')">
                                <img id="pv_nid_b">
                                <div class="ph" id="ph_nid_b"><i class="fas fa-id-card"></i><small>Upload</small></div>
                            </div>
                        </div>
                    </div>

                    <div class="f-actions">
                        <button type="button" class="f-btn btn-back js-prev"><i class="fas fa-arrow-left"></i> Back</button>
                        <button type="submit" class="f-btn btn-submit"><i class="fas fa-paper-plane"></i> Submit Application</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="app-footer">
        <a href="{{ route('login') }}"><i class="fas fa-sign-in-alt"></i> Already a customer? Login here</a>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        flatpickr('.flatpickr-no-default', { dateFormat: 'Y-m-d', altInput: true, altFormat: 'd-m-Y', allowInput: true, disableMobile: true });
    });

    function previewImg(input, pid) {
        var img = document.getElementById(pid);
        var ph = document.getElementById(pid.replace('pv_', 'ph_'));
        if (input.files && input.files[0]) {
            var r = new FileReader();
            r.onload = function(e) { img.src = e.target.result; img.style.display = 'block'; if (ph) ph.style.display = 'none'; };
            r.readAsDataURL(input.files[0]);
        }
    }

    function showMapStatus(msg, type) {
        var el = document.getElementById('mapStatus');
        var s = { info: ['#e0f2fe','#7dd3fc','#0369a1'], success: ['#dcfce7','#86efac','#166534'], warning: ['#fef3c7','#fcd34d','#92400e'], danger: ['#fee2e2','#fca5a5','#b91c1c'] };
        var c = s[type] || s.info;
        el.style.cssText = 'display:block;background:'+c[0]+';border:1px solid '+c[1]+';color:'+c[2];
        el.innerHTML = msg;
    }

    function updateMapLink() {
        var lat = document.getElementById('inp_lat').value, lng = document.getElementById('inp_lng').value;
        var link = document.getElementById('viewMapLink');
        if (lat && lng) { link.href = 'https://www.google.com/maps?q=' + lat + ',' + lng; link.style.display = 'block'; }
        else { link.style.display = 'none'; }
    }

    function getMapLocation() {
        var addr = document.getElementById('inp_address').value.trim();
        if (!addr) { showMapStatus('<i class="fas fa-exclamation-triangle"></i> Enter address first', 'warning'); return; }
        var zoneEl = document.getElementById('sel_zone');
        var zone = zoneEl && zoneEl.selectedOptions[0] ? zoneEl.selectedOptions[0].text : '';
        var q = addr;
        if (zone && zone !== 'Select Zone') q += ', ' + zone;
        q += ', Bangladesh';
        showMapStatus('<i class="fas fa-spinner fa-spin"></i> Finding location...', 'info');
        fetch('https://nominatim.openstreetmap.org/search?format=json&q=' + encodeURIComponent(q) + '&limit=5&countrycodes=bd', { headers: { 'User-Agent': 'SmartOnlineISP/1.0' } })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data && data.length > 0) {
                var res = data[0];
                for (var i = 0; i < data.length; i++) { if (data[i].display_name.toLowerCase().includes(addr.split(' ')[0].toLowerCase())) { res = data[i]; break; } }
                document.getElementById('inp_lat').value = res.lat;
                document.getElementById('inp_lng').value = res.lon;
                document.getElementById('inp_map_url').value = 'https://www.google.com/maps?q=' + res.lat + ',' + res.lon;
                updateMapLink();
                showMapStatus('<i class="fas fa-check-circle"></i> Location found!', 'success');
            } else {
                var simple = addr.replace(/[0-9]/g, '').trim() + ', Bangladesh';
                fetch('https://nominatim.openstreetmap.org/search?format=json&q=' + encodeURIComponent(simple) + '&limit=1&countrycodes=bd', { headers: { 'User-Agent': 'SmartOnlineISP/1.0' } })
                .then(function(r) { return r.json(); })
                .then(function(d2) {
                    if (d2 && d2.length > 0) {
                        document.getElementById('inp_lat').value = d2[0].lat;
                        document.getElementById('inp_lng').value = d2[0].lon;
                        document.getElementById('inp_map_url').value = 'https://www.google.com/maps?q=' + d2[0].lat + ',' + d2[0].lon;
                        updateMapLink();
                        showMapStatus('<i class="fas fa-check-circle"></i> Approximate location found', 'warning');
                    } else { showMapStatus('<i class="fas fa-times-circle"></i> Not found. Try simpler address.', 'danger'); }
                });
            }
        })
        .catch(function() { showMapStatus('<i class="fas fa-times-circle"></i> Error. Try again.', 'danger'); });
    }

    document.addEventListener('DOMContentLoaded', function() {
        updateMapLink();
        var panels = document.querySelectorAll('.step-panel');
        var tabs = document.querySelectorAll('.step-tab');
        var step = 1;

        function goTo(s) {
            panels.forEach(function(p) { p.classList.remove('active'); });
            tabs.forEach(function(t, i) {
                t.classList.remove('active', 'done');
                if (i + 1 < s) t.classList.add('done');
                if (i + 1 === s) t.classList.add('active');
            });
            document.querySelector('.step-panel[data-step="' + s + '"]').classList.add('active');
            step = s;
        }

        function validate(s) {
            var panel = document.querySelector('.step-panel[data-step="' + s + '"]');
            var fields = panel.querySelectorAll('[required]');
            var ok = true;
            fields.forEach(function(f) {
                if (!f.value.trim()) { f.classList.add('is-invalid'); ok = false; } else { f.classList.remove('is-invalid'); }
            });
            return ok;
        }

        document.querySelectorAll('.js-next').forEach(function(btn) { btn.addEventListener('click', function() { if (validate(step)) goTo(step + 1); }); });
        document.querySelectorAll('.js-prev').forEach(function(btn) { btn.addEventListener('click', function() { goTo(step - 1); }); });
        document.querySelectorAll('.tog').forEach(function(btn) {
            btn.addEventListener('click', function() {
                this.parentElement.querySelectorAll('.tog').forEach(function(b) { b.classList.remove('active'); });
                this.classList.add('active');
            });
        });

        (function() {
            var divisionSel = document.getElementById('sel_division');
            var districtSel = document.getElementById('sel_district');
            var upazilaSel = document.getElementById('sel_upazila');
            var zoneSel = document.getElementById('sel_zone');
            var subzoneSel = document.getElementById('sel_subzone');
            var tjSel = document.getElementById('sel_tj');

            function resetFrom(level) {
                if (level <= 2) { districtSel.innerHTML = '<option value="">-- Select Division First --</option>'; }
                if (level <= 3) { upazilaSel.innerHTML = '<option value="">-- Select District First --</option>'; }
                if (level <= 4) { zoneSel.innerHTML = '<option value="">-- Select Thana/Upazila First --</option>'; }
                if (level <= 5) { subzoneSel.innerHTML = '<option value="">-- Select Zone First --</option>'; }
                if (level <= 6) { tjSel.innerHTML = '<option value="">-- Select Sub Zone First --</option>'; }
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
                document.getElementById('division_text').value = this.value ? this.options[this.selectedIndex].text : '';
                document.getElementById('district_text').value = '';
                document.getElementById('thana_upazila_text').value = '';
                if (this.value) {
                    loadOptions('/public-data/districts-by-division/' + encodeURIComponent(this.value), districtSel, '-- Select District --');
                }
            });

            districtSel.addEventListener('change', function() {
                resetFrom(3);
                document.getElementById('district_text').value = this.value ? this.options[this.selectedIndex].text : '';
                document.getElementById('thana_upazila_text').value = '';
                if (this.value) {
                    loadOptions('/public-data/upazilas-by-district/' + this.value, upazilaSel, '-- Select Thana/Upazila --');
                }
            });

            upazilaSel.addEventListener('change', function() {
                resetFrom(4);
                document.getElementById('thana_upazila_text').value = this.value ? this.options[this.selectedIndex].text : '';
                if (this.value) {
                    loadOptions('/public-data/zones-by-upazila/' + this.value, zoneSel, '-- Select Zone --');
                }
            });

            zoneSel.addEventListener('change', function() {
                resetFrom(5);
                if (this.value) {
                    loadOptions('/public-data/areas-by-zone/' + this.value, subzoneSel, '-- Select Sub Zone --');
                }
            });

            subzoneSel.addEventListener('change', function() {
                resetFrom(6);
                if (this.value) {
                    loadOptions('/public-data/boxes-by-area/' + this.value, tjSel, '-- Select TJ Box --');
                }
            });
        })();
    });
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
        // Move all palettes to body to escape overflow:hidden clipping
        document.querySelectorAll(".fiber-color-palette").forEach(function(pal){
            pal.style.position = "fixed";
            pal.style.zIndex = "99999";
            document.body.appendChild(pal);
        });

        function positionPalette(pal, btn){
            var rect = btn.getBoundingClientRect();
            var pw = 220;
            var left = rect.left;
            if(left + pw > window.innerWidth) left = window.innerWidth - pw - 8;
            pal.style.top = (rect.bottom + 4) + "px";
            pal.style.left = left + "px";
        }

        function closeAll(){ document.querySelectorAll(".fiber-color-palette").forEach(function(p){p.style.display="none";}); }

        document.querySelectorAll(".fiber-color-swatch").forEach(function(sw){
            sw.addEventListener("click",function(e){
                e.stopPropagation();
                var tid=this.dataset.target;
                var inp=document.getElementById(tid);
                if(inp){inp.value=this.dataset.color;updateDot(tid);}
                closeAll();
            });
        });

        document.querySelectorAll(".fiber-color-btn").forEach(function(btn){
            btn.addEventListener("click",function(e){
                e.stopPropagation();
                var tid=this.dataset.target;
                var pal=document.getElementById("palette_"+tid);
                var isOpen = pal && pal.style.display === "block";
                closeAll();
                if(pal && !isOpen){
                    positionPalette(pal, btn);
                    pal.style.display = "block";
                }
            });
        });

        document.addEventListener("click",function(e){
            if(!e.target.closest(".fiber-color-btn") && !e.target.closest(".fiber-color-palette")){
                closeAll();
            }
        });

        window.addEventListener("scroll", closeAll, true);
        window.addEventListener("resize", closeAll);

        ["fiber_color_input","splitter_color_input"].forEach(updateDot);

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
