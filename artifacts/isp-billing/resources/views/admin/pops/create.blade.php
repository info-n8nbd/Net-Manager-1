@extends("layouts.admin")

@section("title", "Add POP")

@section("content")
<div class="card card-primary">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-plus mr-2"></i>Add POP</h3>
    </div>
    <form action="{{ route("admin.pops.store") }}" method="POST">
        @csrf
        <div class="card-body">
            @if($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                </div>
            @endif

            {{-- SOURCE TYPE --}}
            <div class="form-group">
                <label class="d-block"><strong>POP Type</strong></label>
                <div class="btn-group btn-group-toggle d-flex flex-wrap" data-toggle="buttons" id="sourceToggle">
                    <label class="btn btn-outline-success active flex-fill text-center">
                        <input type="radio" name="pop_source" value="standalone" checked> <i class="fas fa-server mr-1"></i> Standalone POP
                    </label>
                    <label class="btn btn-outline-secondary flex-fill text-center">
                        <input type="radio" name="pop_source" value="mac_reseller"> <i class="fas fa-desktop mr-1"></i> MAC Reseller as POP
                    </label>
                    <label class="btn btn-outline-primary flex-fill text-center">
                        <input type="radio" name="pop_source" value="bandwidth_reseller"> <i class="fas fa-network-wired mr-1"></i> Bandwidth Reseller as POP
                    </label>
                </div>
            </div>

            {{-- RESELLER PICKER --}}
            <div id="resellerPickerSection" class="card bg-light mb-3" style="display:none;">
                <div class="card-body py-2">
                    <div class="form-group mb-0">
                        <label><i class="fas fa-users mr-1"></i>Select Reseller to Declare as POP <span class="text-danger">*</span></label>
                        <select name="reseller_id" id="resellerSelect" class="form-control @error("reseller_id") is-invalid @enderror">
                            <option value="">-- Choose Reseller --</option>
                            @foreach($resellers as $r)
                                <option value="{{ $r->id }}"
                                    data-name="{{ $r->company_name ?? $r->name }}"
                                    data-phone="{{ $r->phone }}"
                                    data-address="{{ $r->address }}"
                                    data-area="{{ $r->area_name_en }}"
                                    data-type="{{ $r->type }}"
                                    data-bandwidth="{{ $r->raw_bandwidth ?? 0 }}"
                                    {{ old("reseller_id") == $r->id ? "selected" : "" }}>
                                    {{ $r->company_name ?? $r->name }} ({{ $r->reseller_id }}) — {{ ucfirst($r->type) }}
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted">Only resellers not yet linked to a POP are shown. Selecting one auto-fills the form below.</small>
                        @error("reseller_id")<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>

            <hr class="mt-0">

            {{-- POP DETAILS --}}
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>POP Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="fieldName" class="form-control @error("name") is-invalid @enderror" value="{{ old("name") }}" required>
                        @error("name")<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>POP Code <span class="text-danger">*</span></label>
                        <input type="text" name="code" id="fieldCode" class="form-control @error("code") is-invalid @enderror" value="{{ old("code") }}" required placeholder="e.g. POP-MIRPUR-01">
                        @error("code")<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Branch</label>
                        <select name="branch_id" class="form-control">
                            <option value="">Select Branch</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" {{ old("branch_id") == $branch->id ? "selected" : "" }}>{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Zone</label>
                        <select name="zone_id" class="form-control">
                            <option value="">Select Zone</option>
                            @foreach($zones as $zone)
                                <option value="{{ $zone->id }}" {{ old("zone_id") == $zone->id ? "selected" : "" }}>{{ $zone->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label>Address</label>
                <textarea name="address" id="fieldAddress" class="form-control" rows="2">{{ old("address") }}</textarea>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Contact Person</label>
                        <input type="text" name="contact_person" id="fieldContactPerson" class="form-control" value="{{ old("contact_person") }}">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Contact Phone</label>
                        <input type="text" name="contact_phone" id="fieldContactPhone" class="form-control" value="{{ old("contact_phone") }}">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Total Bandwidth (Mbps)</label>
                        <input type="number" name="total_bandwidth_mbps" id="fieldBandwidth" class="form-control" value="{{ old("total_bandwidth_mbps") }}" min="0">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Uplink Provider</label>
                        <input type="text" name="uplink_provider" class="form-control" value="{{ old("uplink_provider") }}">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Uplink Type</label>
                        <input type="text" name="uplink_type" class="form-control" value="{{ old("uplink_type") }}" placeholder="Fiber, Radio…">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>MikroTik Device</label>
                        <select name="mikrotik_device_id" class="form-control">
                            <option value="">None</option>
                            @foreach($devices ?? [] as $device)
                                <option value="{{ $device->id }}" {{ old("mikrotik_device_id") == $device->id ? "selected" : "" }}>{{ $device->name }} ({{ $device->host }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="active" {{ old("status","active") === "active" ? "selected" : "" }}>Active</option>
                            <option value="inactive" {{ old("status") === "inactive" ? "selected" : "" }}>Inactive</option>
                            <option value="maintenance" {{ old("status") === "maintenance" ? "selected" : "" }}>Maintenance</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Save POP</button>
            <a href="{{ route("admin.pops.index") }}" class="btn btn-default ml-2">Cancel</a>
        </div>
    </form>
</div>

@push("scripts")
<script>
$(function() {
    function toggleResellerPicker(val) {
        if (val === "standalone") {
            $("#resellerPickerSection").slideUp(200);
        } else {
            $("#resellerPickerSection").slideDown(200);
        }
    }

    $("input[name=pop_source]").on("change", function() {
        toggleResellerPicker($(this).val());
    });

    $("#resellerSelect").on("change", function() {
        var o = $("option:selected", this);
        if (!o.val()) return;
        var name = o.data("name") || "";
        var phone = o.data("phone") || "";
        var address = o.data("address") || "";
        var area = o.data("area") || "";
        var bw = parseInt(o.data("bandwidth")) || 0;

        if (!$("#fieldName").val()) $("#fieldName").val(name + " POP");
        if (!$("#fieldContactPhone").val()) $("#fieldContactPhone").val(phone);
        if (!$("#fieldAddress").val()) {
            var addr = [address, area].filter(Boolean).join(", ");
            $("#fieldAddress").val(addr);
        }
        if (!$("#fieldBandwidth").val() || $("#fieldBandwidth").val() === "0") {
            if (bw > 0) $("#fieldBandwidth").val(bw);
        }
        if (!$("#fieldCode").val()) {
            var code = "POP-" + name.replace(/[^A-Za-z0-9]+/g, "-").toUpperCase().slice(0,14).replace(/-$/, "");
            $("#fieldCode").val(code);
        }
    });
});
</script>
@endpush
@endsection
