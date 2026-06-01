@extends('layouts.admin')

@section('title', 'Voice Call Settings')

@section('content')
<div class="container-fluid py-2">

<ul class="nav nav-tabs mb-3" id="vcsTabs">
    <li class="nav-item">
        <a class="nav-link active" data-toggle="tab" href="#tab-provider"><i class="fas fa-phone mr-1"></i>Cloud Provider</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-toggle="tab" href="#tab-pbx"><i class="fas fa-server mr-1 text-success"></i>PBX / Softphone</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-toggle="tab" href="#tab-auto"><i class="fas fa-robot mr-1 text-info"></i>Automation</a>
    </li>
</ul>

<div class="tab-content">

    {{-- ===== TAB 1: Cloud Provider (Twilio/Vonage/Plivo) ===== --}}
    <div class="tab-pane fade show active" id="tab-provider">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h6 class="mb-0"><i class="fas fa-cloud mr-2 text-primary"></i>Cloud Voice Provider (Twilio / Vonage / Plivo)</h6>
                <small class="text-muted">For automated IVR calls (bill reminders, notifications). Requires paid provider account.</small>
            </div>
            <form action="{{ route('admin.voice-calls.settings.update') }}" method="POST">
                @csrf @method('PUT')
                <div class="card-body">
                    @if($errors->any())
                        <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
                    @endif
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Provider <span class="text-danger">*</span></label>
                                <select name="provider" class="form-control" required>
                                    <option value="">Select Provider</option>
                                    <option value="twilio" {{ old('provider', $settings->provider ?? '') == 'twilio' ? 'selected' : '' }}>Twilio</option>
                                    <option value="vonage" {{ old('provider', $settings->provider ?? '') == 'vonage' ? 'selected' : '' }}>Vonage</option>
                                    <option value="plivo"  {{ old('provider', $settings->provider ?? '') == 'plivo'  ? 'selected' : '' }}>Plivo</option>
                                    <option value="pbx"    {{ old('provider', $settings->provider ?? '') == 'pbx'   ? 'selected' : '' }}>PBX (Asterisk)</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>From Number</label>
                                <input type="text" name="from_number" class="form-control" value="{{ old('from_number', $settings->from_number ?? '') }}" placeholder="+880XXXXXXXXXX">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Credentials (JSON)</label>
                        @php
                            $credVal = old('credentials');
                            if ($credVal === null) {
                                $raw = $settings->credentials ?? null;
                                $credVal = is_array($raw) ? json_encode($raw, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : ($raw ?? '');
                            }
                        @endphp
                        <textarea name="credentials" class="form-control" rows="3" placeholder='{"account_sid": "AC...", "auth_token": "..."}'>{{ $credVal }}</textarea>
                        <small class="text-muted">Twilio: {"account_sid":"ACxxx","auth_token":"xxx"} | Vonage: {"api_key":"xxx","api_secret":"xxx"}</small>
                    </div>
                    <div class="custom-control custom-switch">
                        <input type="checkbox" name="is_active" class="custom-control-input" id="isActive" value="1" {{ old('is_active', $settings->is_active ?? false) ? 'checked' : '' }}>
                        <label class="custom-control-label" for="isActive">Enable Cloud Provider</label>
                    </div>
                </div>
                <div class="card-footer bg-white">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i>Save Cloud Settings</button>
                    <a href="{{ route('admin.voice-calls.index') }}" class="btn btn-default ml-2">Back</a>
                </div>
            </form>
        </div>
    </div>

    {{-- ===== TAB 2: PBX / Softphone ===== --}}
    <div class="tab-pane fade" id="tab-pbx">
        <div class="row">
            {{-- Left: PBX Status + Credentials --}}
            <div class="col-md-7">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><i class="fas fa-phone-alt mr-2 text-success"></i>PBX Softphone (Free WebRTC Calling)</h6>
                        <span class="badge badge-success"><i class="fas fa-check-circle mr-1"></i>Asterisk Running</span>
                    </div>
                    <form action="{{ route('admin.voice-calls.pbx.update') }}" method="POST">
                        @csrf @method('PUT')
                        <div class="card-body">
                            <div class="alert alert-success border-0" style="background:#f0fff4;">
                                <i class="fas fa-info-circle mr-2"></i>
                                <strong>Free PBX system is active!</strong> Make calls directly from your browser using the phone widget (bottom-right corner).
                                For outbound calls to mobile numbers, configure a SIP trunk below.
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label><i class="fas fa-globe mr-1"></i>WebSocket URL (auto-detected)</label>
                                        <input type="text" name="pbx_ws_url" class="form-control"
                                            value="{{ old('pbx_ws_url', $settings->pbx_ws_url ?? '') }}"
                                            placeholder="wss://softynex.me/pbx/ws">
                                        <small class="text-muted">Browser connects to Asterisk via this URL. Leave blank for auto-detect.</small>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label><i class="fas fa-user mr-1"></i>SIP Extension (username)</label>
                                        <input type="text" name="pbx_extension" class="form-control"
                                            value="{{ old('pbx_extension', $settings->getRawOriginal('pbx_extension') ?? 'softynex_admin') }}"
                                            placeholder="softynex_admin">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label><i class="fas fa-key mr-1"></i>SIP Password</label>
                                        <div class="input-group">
                                            <input type="password" name="pbx_password" class="form-control" id="pbxPassField"
                                                value="{{ old('pbx_password', $settings->getRawOriginal('pbx_password') ?? 'PbxSoft@2024!') }}"
                                                placeholder="SIP password">
                                            <div class="input-group-append">
                                                <button type="button" class="btn btn-outline-secondary" onclick="togglePbxPass()">
                                                    <i class="fas fa-eye" id="pbxPassEye"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <hr>
                            <h6 class="text-muted"><i class="fas fa-network-wired mr-1"></i>SIP Trunk (for outbound calls to mobile/landline) — Optional</h6>
                            <p class="text-muted small mb-2">
                                Free SIP trunk options: <strong>Zadarma</strong> (zadarma.com — free account), <strong>Google Voice</strong> (US), or any SIP provider.
                                Configure JSON below with your trunk credentials.
                            </p>
                            @php
                                $trunkVal = old('pbx_trunk');
                                if ($trunkVal === null) {
                                    $raw = $settings->pbx_trunk ?? null;
                                    $trunkVal = is_array($raw) ? json_encode($raw, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : ($raw ?? '');
                                }
                            @endphp
                            <textarea name="pbx_trunk" class="form-control" rows="4"
                                placeholder='{"host":"sip.zadarma.com","username":"YOUR_USER","password":"YOUR_PASS","caller_id":"+8801XXXXXXXXX"}'>{{ $trunkVal }}</textarea>
                            <small class="text-muted">
                                Zadarma: {"host":"sip.zadarma.com","username":"XXXXX","password":"XXXXX","caller_id":"+880..."}<br>
                                VoIP.ms: {"host":"sip.voip.ms","username":"XXXXX","password":"XXXXX","caller_id":"..."}
                            </small>
                        </div>
                        <div class="card-footer bg-white">
                            <button type="submit" class="btn btn-success"><i class="fas fa-save mr-1"></i>Save PBX Settings</button>
                            <button type="button" class="btn btn-info ml-2" onclick="testSoftphone()">
                                <i class="fas fa-phone mr-1"></i>Test Softphone
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Right: Quick Guide --}}
            <div class="col-md-5">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white">
                        <h6 class="mb-0"><i class="fas fa-book mr-2 text-primary"></i>How to Use</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-flex mb-3">
                            <div class="mr-3"><span class="badge badge-success" style="width:28px;height:28px;line-height:28px;border-radius:50%;font-size:14px;">1</span></div>
                            <div>
                                <strong>Click the phone icon</strong><br>
                                <small class="text-muted">Look for the green phone button at the bottom-right corner of any page.</small>
                            </div>
                        </div>
                        <div class="d-flex mb-3">
                            <div class="mr-3"><span class="badge badge-info" style="width:28px;height:28px;line-height:28px;border-radius:50%;font-size:14px;">2</span></div>
                            <div>
                                <strong>Allow microphone</strong><br>
                                <small class="text-muted">Browser will ask for microphone permission. Click Allow to enable calling.</small>
                            </div>
                        </div>
                        <div class="d-flex mb-3">
                            <div class="mr-3"><span class="badge badge-warning" style="width:28px;height:28px;line-height:28px;border-radius:50%;font-size:14px;">3</span></div>
                            <div>
                                <strong>Dial or click-to-call</strong><br>
                                <small class="text-muted">Type a number in the dialer, or click the <i class="fas fa-phone-alt text-success"></i> button next to any client phone number.</small>
                            </div>
                        </div>
                        <div class="d-flex mb-3">
                            <div class="mr-3"><span class="badge badge-secondary" style="width:28px;height:28px;line-height:28px;border-radius:50%;font-size:14px;">4</span></div>
                            <div>
                                <strong>For external calls</strong><br>
                                <small class="text-muted">Add a FREE SIP trunk (Zadarma) to call actual mobile/landline numbers. Register at <a href="https://zadarma.com" target="_blank">zadarma.com</a>.</small>
                            </div>
                        </div>
                        <hr>
                        <div class="bg-light rounded p-2">
                            <small><strong>Asterisk Status:</strong> <span class="text-success"><i class="fas fa-circle" style="font-size:8px;"></i> Running</span></small><br>
                            <small><strong>WebSocket:</strong> wss://{{ request()->getHost() }}/pbx/ws</small><br>
                            <small><strong>Extension:</strong> softynex_admin</small><br>
                            <small><strong>Internal Echo Test:</strong> Dial 9999</small>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mt-3">
                    <div class="card-header bg-white">
                        <h6 class="mb-0"><i class="fas fa-users mr-2 text-info"></i>Staff Extensions</h6>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm mb-0">
                            <thead class="table-light"><tr><th>Ext</th><th>Username</th><th>Password</th><th>Use</th></tr></thead>
                            <tbody>
                                <tr><td>1001</td><td>softynex_admin</td><td><code>PbxSoft@2024!</code></td><td><span class="badge badge-success">Admin</span></td></tr>
                                <tr><td>1002</td><td>user_1002</td><td><code>PbxUser@2024!</code></td><td><span class="badge badge-secondary">Staff</span></td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== TAB 3: Automation ===== --}}
    <div class="tab-pane fade" id="tab-auto">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h6 class="mb-0"><i class="fas fa-robot mr-2 text-info"></i>Automated Call Triggers</h6>
            </div>
            <form action="{{ route('admin.voice-calls.settings.update') }}" method="POST">
                @csrf @method('PUT')
                <input type="hidden" name="provider" value="{{ $settings->provider ?? 'twilio' }}">
                <input type="hidden" name="from_number" value="{{ $settings->from_number ?? '' }}">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" name="auto_bill_reminder" class="custom-control-input" id="autoBill" value="1" {{ ($settings->auto_bill_reminder ?? false) ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="autoBill"><i class="fas fa-file-invoice-dollar mr-1 text-warning"></i>Auto Bill Reminder</label>
                                </div>
                                <small class="text-muted ml-4">Call client automatically when bill is due</small>
                            </div>
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" name="auto_expiry_warning" class="custom-control-input" id="autoExpiry" value="1" {{ ($settings->auto_expiry_warning ?? false) ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="autoExpiry"><i class="fas fa-clock mr-1 text-danger"></i>Auto Expiry Warning</label>
                                </div>
                                <small class="text-muted ml-4">Call before package expires</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" name="auto_payment_confirmation" class="custom-control-input" id="autoPayment" value="1" {{ ($settings->auto_payment_confirmation ?? false) ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="autoPayment"><i class="fas fa-check-circle mr-1 text-success"></i>Auto Payment Confirmation</label>
                                </div>
                                <small class="text-muted ml-4">Call after payment received</small>
                            </div>
                            <div class="form-group">
                                <label>Reminder Days Before Due</label>
                                <input type="number" name="reminder_days_before" class="form-control" value="{{ $settings->reminder_days_before ?? 3 }}" min="1" max="30" style="max-width:120px;">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-white">
                    <button type="submit" class="btn btn-info"><i class="fas fa-save mr-1"></i>Save Automation Settings</button>
                </div>
            </form>
        </div>
    </div>

</div>
</div>
@endsection

@push('scripts')
<script>
function togglePbxPass() {
    var f = document.getElementById('pbxPassField');
    var e = document.getElementById('pbxPassEye');
    if (f.type === 'password') { f.type = 'text'; e.className = 'fas fa-eye-slash'; }
    else { f.type = 'password'; e.className = 'fas fa-eye'; }
}
function testSoftphone() {
    if (window.pbxDial) {
        window.pbxDial('9999');
        alert('Dialing echo test (9999)...\nYou should hear your own voice back. Open the phone widget at bottom-right.');
    } else {
        alert('Softphone widget not loaded yet. Please refresh the page and try again.');
    }
}
// Activate PBX tab if hash says so
if (window.location.hash === '#pbx') {
    document.querySelector('[href="#tab-pbx"]').click();
}
</script>
@endpush
