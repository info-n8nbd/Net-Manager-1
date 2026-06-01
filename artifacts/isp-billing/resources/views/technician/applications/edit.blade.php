@extends('layouts.technician')

@section('title', 'Edit Application')

@section('content')
<section class="content">
    <div class="container-fluid">
        @if($application->status == 'need_correction')
        <div class="alert alert-warning">
            <h5><i class="fas fa-exclamation-triangle"></i> Correction Required</h5>
            <p>{{ $application->correction_request }}</p>
        </div>
        @endif

        <form action="{{ route('technician.applications.update', $application) }}" method="POST" enctype="multipart/form-data">
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
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Full Name (Bangla) <span class="text-danger">*</span></label>
                                        <input type="text" name="full_name_bangla" class="form-control" value="{{ old('full_name_bangla', $application->full_name_bangla) }}" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Full Name (English) <span class="text-danger">*</span></label>
                                        <input type="text" name="full_name_english" class="form-control" value="{{ old('full_name_english', $application->full_name_english) }}" required>
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

                    <div class="card card-danger">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-id-card"></i> NID Information</h3>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label>NID Number <span class="text-danger">*</span></label>
                                <input type="text" name="nid_number" class="form-control" value="{{ old('nid_number', $application->nid_number) }}" required>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>NID Front Image</label>
                                        @if($application->nid_front_image)
                                        <div class="mb-2">
                                            <img src="{{ asset('storage/' . $application->nid_front_image) }}" class="img-thumbnail" style="max-height: 100px;">
                                        </div>
                                        @endif
                                        <input type="file" name="nid_front_image" class="form-control-file" accept="image/*">
                                        <small class="text-muted">Leave empty to keep current image</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>NID Back Image</label>
                                        @if($application->nid_back_image)
                                        <div class="mb-2">
                                            <img src="{{ asset('storage/' . $application->nid_back_image) }}" class="img-thumbnail" style="max-height: 100px;">
                                        </div>
                                        @endif
                                        <input type="file" name="nid_back_image" class="form-control-file" accept="image/*">
                                        <small class="text-muted">Leave empty to keep current image</small>
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
                                        <input type="text" name="division" class="form-control" value="{{ old('division', $application->division) }}">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>District</label>
                                        <input type="text" name="district" class="form-control" value="{{ old('district', $application->district) }}">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Thana/Upazila</label>
                                        <input type="text" name="thana_upazila" class="form-control" value="{{ old('thana_upazila', $application->thana_upazila) }}">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Zone</label>
                                        <select name="zone_id" id="zone_id" class="form-control">
                                            <option value="">Select Zone</option>
                                            @foreach($zones as $zone)
                                            <option value="{{ $zone->id }}" {{ old('zone_id', $application->zone_id) == $zone->id ? 'selected' : '' }}>{{ $zone->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Sub-Zone</label>
                                        <select name="sub_zone_id" id="sub_zone_id" class="form-control">
                                            <option value="">Select Sub-Zone</option>
                                            @foreach($subZones as $sz)
                                            <option value="{{ $sz->id }}" {{ old('sub_zone_id', $application->sub_zone_id) == $sz->id ? 'selected' : '' }}>{{ $sz->name }}</option>
                                            @endforeach
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
                                <select name="package_id" class="form-control">
                                    <option value="">Select Package</option>
                                    @foreach($packages as $package)
                                    <option value="{{ $package->id }}" {{ old('package_id', $application->package_id) == $package->id ? 'selected' : '' }}>
                                        {{ $package->name }} - ৳{{ number_format($package->price ?? 0) }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label><i class="fas fa-tachometer-alt mr-1"></i> MikroTik Profile</label>
                                <select name="mikrotik_profile_name" class="form-control">
                                    <option value="">-- Select Profile --</option>
                                    @foreach($availableProfiles ?? [] as $profile)
                                    <option value="{{ $profile }}" {{ old('mikrotik_profile_name', $application->mikrotik_profile_name) == $profile ? 'selected' : '' }}>{{ $profile }}</option>
                                    @endforeach
                                </select>
                                <small class="text-muted">MikroTik speed profile for this connection</small>
                            </div>
                            <div class="form-group">
                                <label>Installation Charge</label>
                                <input type="number" name="installation_charge" class="form-control" value="{{ old('installation_charge', $application->installation_charge) }}">
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
    <input type="text" name="fiber_color" id="fiber_color_input" class="form-control" value="{{ old('fiber_color', $application->fiber_color) }}" readonly placeholder="e.g. Blue, Red" autocomplete="off">
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
    <input type="text" name="splitter_color" id="splitter_color_input" class="form-control" value="{{ old('splitter_color', $application->splitter_color) }}" readonly placeholder="e.g. Green, Yellow" autocomplete="off">
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
                                        <input type="text" name="onu_brand" class="form-control" value="{{ old('onu_brand', $application->onu_brand) }}" placeholder="e.g. Huawei, ZTE, VSOL">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>ONU MAC/Serial</label>
                                        <input type="text" name="onu_mac" class="form-control" value="{{ old('onu_mac', $application->onu_mac) }}" placeholder="e.g. AA:BB:CC:DD:EE:FF">
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
                                <label>Installation Feasibility</label>
                                <select name="installation_feasibility" class="form-control">
                                    <option value="1" {{ old('installation_feasibility', $application->installation_feasibility) ? 'selected' : '' }}>Yes - Feasible</option>
                                    <option value="0" {{ !old('installation_feasibility', $application->installation_feasibility) ? 'selected' : '' }}>No - Not Feasible</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Technician Note</label>
                                <textarea name="technician_note" class="form-control" rows="3">{{ old('technician_note', $application->technician_note) }}</textarea>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block btn-lg">
                        <i class="fas fa-save"></i> Update Application
                    </button>
                    <a href="{{ route('technician.applications.show', $application) }}" class="btn btn-secondary btn-block">
                        Cancel
                    </a>
                </div>
            </div>
        </form>
    </div>
</section>
@endsection

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
