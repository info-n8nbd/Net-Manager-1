@extends('layouts.admin')
@section('title', 'ZTP Settings')
@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card shadow-sm">
                <div class="card-header d-flex align-items-center" style="background: linear-gradient(135deg,#1a73e8,#0056b3); color:#fff;">
                    <i class="fas fa-magic mr-2"></i>
                    <strong>Zero-Touch Provisioning (ZTP) Settings</strong>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif
                    <p class="text-muted mb-4">
                        ZTP automatically configures MikroTik PPPoE/Static/DHCP users and ONU devices when a new client is added with <strong>Active</strong> status — no manual setup needed.
                    </p>
                    <form method="POST" action="{{ route('admin.settings.ztp.save') }}">
                        @csrf
                        <div class="form-group d-flex justify-content-between align-items-center border-bottom pb-3 mb-3">
                            <div>
                                <strong>Enable ZTP</strong>
                                <div class="text-muted small">Auto-provision new clients when status is set to Active</div>
                            </div>
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="ztp_enabled" name="ztp_enabled" value="1" {{ $ztpEnabled ? 'checked' : '' }}>
                                <label class="custom-control-label" for="ztp_enabled"></label>
                            </div>
                        </div>
                        <div class="form-group d-flex justify-content-between align-items-center border-bottom pb-3 mb-3">
                            <div>
                                <strong>MikroTik Auto-Provision</strong>
                                <div class="text-muted small">Automatically create PPPoE secret / queue on MikroTik router</div>
                            </div>
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="ztp_mikrotik_enabled" name="ztp_mikrotik_enabled" value="1" {{ $ztpMikrotik ? 'checked' : '' }}>
                                <label class="custom-control-label" for="ztp_mikrotik_enabled"></label>
                            </div>
                        </div>
                        <div class="form-group d-flex justify-content-between align-items-center pb-3 mb-3">
                            <div>
                                <strong>ONU Auto-Provision</strong>
                                <div class="text-muted small">Automatically register ONU on OLT device (BDCOM / VSOL etc.)</div>
                            </div>
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="ztp_onu_enabled" name="ztp_onu_enabled" value="1" {{ $ztpOnu ? 'checked' : '' }}>
                                <label class="custom-control-label" for="ztp_onu_enabled"></label>
                            </div>
                        </div>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle mr-1"></i>
                            ZTP runs as a background queue job — it won't slow down client creation. You can trigger it manually from the client detail page anytime.
                        </div>
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-save mr-1"></i> Save ZTP Settings
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
