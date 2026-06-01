@extends('layouts.admin')

@section('title', 'Submit New Connection Application')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.applications.index') }}">Applications</a></li>
<li class="breadcrumb-item active">Submit New Application</li>
@endsection

@section('content')

        <form action="{{ route('admin.applications.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="row">
                <div class="col-md-8">

                    {{-- Personal Information --}}
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-user mr-1"></i> Personal Information</h3>
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
                                        <label>Father / Husband Name</label>
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
                                        <label>Mobile Number <span class="text-danger">*</span></label>
                                        <input type="text" name="mobile_number" class="form-control @error('mobile_number') is-invalid @enderror" value="{{ old('mobile_number') }}" required>
                                        @error('mobile_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Alternative Mobile</label>
                                        <input type="text" name="alternative_mobile" class="form-control" value="{{ old('alternative_mobile') }}">
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
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>NID Number <span class="text-danger">*</span></label>
                                        <input type="text" name="nid_number" class="form-control @error('nid_number') is-invalid @enderror" value="{{ old('nid_number') }}" required>
                                        @error('nid_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>NID Front Image <span class="text-danger">*</span></label>
                                        <input type="file" name="nid_front_image" class="form-control-file @error('nid_front_image') is-invalid @enderror" accept="image/*" required>
                                        @error('nid_front_image')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>NID Back Image <span class="text-danger">*</span></label>
                                        <input type="file" name="nid_back_image" class="form-control-file @error('nid_back_image') is-invalid @enderror" accept="image/*" required>
                                        @error('nid_back_image')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Profile / Installation Photo</label>
                                        <input type="file" name="profile_picture" class="form-control-file" accept="image/*">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Gender</label>
                                        <select name="gender" class="form-control">
                                            <option value="male" {{ old('gender') == 'male' ? 'selected' : '' }}>Male</option>
                                            <option value="female" {{ old('gender') == 'female' ? 'selected' : '' }}>Female</option>
                                            <option value="other" {{ old('gender') == 'other' ? 'selected' : '' }}>Other</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Date of Birth</label>
                                        <input type="date" name="date_of_birth" class="form-control" value="{{ old('date_of_birth') }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Address --}}
                    <div class="card card-info">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-map-marker-alt mr-1"></i> Address</h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label>House / Road / Floor <span class="text-danger">*</span></label>
                                        <textarea name="house_road_floor" class="form-control @error('house_road_floor') is-invalid @enderror" rows="2">{{ old('house_road_floor') }}</textarea>
                                        @error('house_road_floor')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                            </div>
                            <div class="row">
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
                                        <label>Sub-Zone / Area</label>
                                        <select name="sub_zone_id" id="sub_zone_id" class="form-control">
                                            <option value="">Select Sub-Zone</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Nearby Landmark</label>
                                        <input type="text" name="nearby_landmark" class="form-control" value="{{ old('nearby_landmark') }}">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Latitude</label>
                                        <input type="text" name="latitude" class="form-control" value="{{ old('latitude') }}" placeholder="e.g. 23.8103">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Longitude</label>
                                        <input type="text" name="longitude" class="form-control" value="{{ old('longitude') }}" placeholder="e.g. 90.4125">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Occupation</label>
                                        <input type="text" name="occupation" class="form-control" value="{{ old('occupation') }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Technical Notes --}}
                    <div class="card card-warning">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-tools mr-1"></i> Technical Notes</h3>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label>Note / Remarks</label>
                                <textarea name="technician_note" class="form-control" rows="3" placeholder="Any special notes about this connection...">{{ old('technician_note') }}</textarea>
                            </div>
                            <div class="form-group">
                                <div class="icheck-primary d-inline">
                                    <input type="hidden" name="installation_feasibility" value="0">
                                    <input type="checkbox" id="installation_feasibility" name="installation_feasibility" value="1" {{ old('installation_feasibility', '1') ? 'checked' : '' }}>
                                    <label for="installation_feasibility">Installation is feasible <span class="text-danger">*</span></label>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="col-md-4">

                    {{-- Connection Details --}}
                    <div class="card card-success">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-wifi mr-1"></i> Connection Details</h3>
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
                                    <option value="fiber" {{ old('connection_medium', 'fiber') == 'fiber' ? 'selected' : '' }}>Optical Fiber</option>
                                    <option value="utp" {{ old('connection_medium') == 'utp' ? 'selected' : '' }}>UTP Cable</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Package</label>
                                <select name="package_id" class="form-control">
                                    <option value="">Select Package</option>
                                    @foreach($packages as $package)
                                    <option value="{{ $package->id }}" {{ old('package_id') == $package->id ? 'selected' : '' }}>
                                        {{ $package->name }} — ৳{{ number_format($package->price ?? 0) }}
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
                                <label>Installation Charge (৳)</label>
                                <input type="number" name="installation_charge" class="form-control" value="{{ old('installation_charge', 0) }}" min="0">
                            </div>
                            <div class="form-group">
                                <label>Expire Date <span class="text-danger">*</span></label>
                                <input type="date" name="expire_date" class="form-control @error('expire_date') is-invalid @enderror" value="{{ old('expire_date') }}" required>
                                @error('expire_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="form-group">
                                <label>Payment Method</label>
                                <select name="payment_method" class="form-control">
                                    <option value="cash">Cash</option>
                                    <option value="bkash">bKash</option>
                                    <option value="nagad">Nagad</option>
                                    <option value="rocket">Rocket</option>
                                    <option value="bank">Bank</option>
                                    <option value="others">Others</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    {{-- Fiber Details (optional) --}}
                    <div class="card card-secondary">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-network-wired mr-1"></i> Fiber / ONU Details <small class="ml-1">(optional)</small></h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-6">
                                    <div class="form-group">
                                        <label>Fiber Code</label>
                                        <input type="text" name="fiber_code" class="form-control" value="{{ old('fiber_code') }}">
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-group">
                                        <label>Fiber Color</label>
                                        <div class="input-group">
    <input type="text" name="fiber_color" id="fiber_color_input" class="form-control" value="{{ old('fiber_color') }}" placeholder="e.g. Blue" autocomplete="off">
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
                                <div class="col-6">
                                    <div class="form-group">
                                        <label>Splitter No.</label>
                                        <input type="text" name="splitter_number" class="form-control" value="{{ old('splitter_number') }}">
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-group">
                                        <label>ONU MAC</label>
                                        <input type="text" name="onu_mac" class="form-control" value="{{ old('onu_mac') }}">
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-group">
                                        <label>ONU Brand</label>
                                        <input type="text" name="onu_brand" class="form-control" value="{{ old('onu_brand') }}">
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-group">
                                        <label>Cable Length (m)</label>
                                        <input type="number" name="cable_length_meters" class="form-control" value="{{ old('cable_length_meters') }}" min="0">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Submit --}}
                    <div class="card">
                        <div class="card-body">
                            <button type="submit" class="btn btn-success btn-block btn-lg">
                                <i class="fas fa-paper-plane mr-1"></i> Submit Application
                            </button>
                            <a href="{{ route('admin.applications.index') }}" class="btn btn-secondary btn-block mt-2">
                                <i class="fas fa-times mr-1"></i> Cancel
                            </a>
                            <p class="text-muted text-sm mt-2 mb-0">
                                <i class="fas fa-info-circle mr-1"></i>
                                Application will be submitted with status <strong>Draft</strong>. Admin will review and approve.
                            </p>
                        </div>
                    </div>

                </div>
            </div>

        </form>

    </div>
</section>

@push('scripts')
<script>
document.getElementById('zone_id')?.addEventListener('change', function() {
    const zoneId = this.value;
    const subZoneSelect = document.getElementById('sub_zone_id');
    subZoneSelect.innerHTML = '<option value="">Loading...</option>';

    if (!zoneId) {
        subZoneSelect.innerHTML = '<option value="">Select Sub-Zone</option>';
        return;
    }

    fetch(`/admin/zones/${zoneId}/areas`)
        .then(r => r.json())
        .then(areas => {
            let html = '<option value="">Select Sub-Zone</option>';
            areas.forEach(a => {
                html += `<option value="${a.id}">${a.name}</option>`;
            });
            subZoneSelect.innerHTML = html;
        })
        .catch(() => {
            subZoneSelect.innerHTML = '<option value="">Select Sub-Zone</option>';
        });
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
@endsection
