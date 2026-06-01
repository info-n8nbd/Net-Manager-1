@extends('layouts.admin')

@section('title', 'System Settings')
@section('breadcrumb')
<li class="breadcrumb-item active">System Settings</li>
@endsection

@push('styles')
<style>
.sys-section {
    background:#fff;
    border:1px solid #e9ecef;
    border-radius:8px;
    margin-bottom:14px;
    overflow:hidden;
}
.sys-section-header {
    display:flex;
    align-items:center;
    gap:10px;
    padding:10px 16px;
    background:#f8f9fa;
    border-bottom:1px solid #e9ecef;
}
.sys-section-header .sec-icon {
    width:30px;height:30px;border-radius:6px;
    display:flex;align-items:center;justify-content:center;
    font-size:14px;color:#fff;flex-shrink:0;
}
.sys-section-header .sec-title {
    font-size:13px;font-weight:700;color:#2d3436;margin:0;
}
.sys-section-header .sec-desc {
    font-size:11px;color:#888;margin:0;
}
.sys-section-body { padding:14px 16px; }
.sys-label { font-size:12px;font-weight:600;color:#495057;margin-bottom:4px;display:block; }
.sys-hint  { font-size:11px;color:#aaa;margin-top:3px;display:block;line-height:1.4; }
.form-control-sys { font-size:13px;height:34px;border-radius:6px;border:1px solid #dee2e6;padding:4px 10px; }
select.form-control-sys { height:34px; }
textarea.form-control-sys { height:auto; }
.tg-card { border:1px solid #e9ecef;border-radius:6px;margin-bottom:6px;overflow:hidden; }
.tg-card-header { display:flex;align-items:center;gap:8px;padding:8px 12px;cursor:pointer;background:#fafafa;border-bottom:1px solid #e9ecef; }
.tg-card-header:hover { background:#f1f3f5; }
.tg-card-header .tg-label { font-size:12px;font-weight:600;color:#2d3436;margin:0; }
.tg-card-body { padding:10px 14px;display:none; }
.tg-card-body.show { display:block; }
.badge-dot { width:8px;height:8px;border-radius:50%;display:inline-block; }
</style>
@endpush

@section('content')
<form action="{{ route('admin.settings.system.update') }}" method="POST">
@csrf

{{-- ───── Header ───── --}}
<div class="d-flex align-items-center justify-content-between mb-3">
    <h5 class="mb-0 font-weight-bold" style="font-size:16px;"><i class="fas fa-cog mr-2 text-primary"></i>System Settings</h5>
    <button type="submit" class="btn btn-primary btn-sm px-4">
        <i class="fas fa-save mr-1"></i>Save Settings
    </button>
</div>

{{-- ───── 1. Client Code ───── --}}
<div class="sys-section">
    <div class="sys-section-header">
        <div class="sec-icon" style="background:#6c5ce7;"><i class="fas fa-id-badge"></i></div>
        <div>
            <div class="sec-title">Client Code Settings</div>
            <div class="sec-desc">Auto-generated client ID format</div>
        </div>
    </div>
    <div class="sys-section-body">
        <div class="row">
            <div class="col-md-3">
                <label class="sys-label">Start Number</label>
                <input type="number" name="client_code_start" class="form-control form-control-sys" value="{{ $settings['client_code_start'] ?? 1 }}" min="1">
                <span class="sys-hint">e.g. 1 → 00001</span>
            </div>
            <div class="col-md-3">
                <label class="sys-label">Code Digits</label>
                <input type="number" name="client_code_digits" class="form-control form-control-sys" value="{{ $settings['client_code_digits'] ?? 5 }}" min="1" max="10">
                <span class="sys-hint">5 = 00001, 6 = 000001</span>
            </div>
            <div class="col-md-3">
                <label class="sys-label">Last Generated Code</label>
                <input type="text" class="form-control form-control-sys bg-light" value="{{ \App\Models\Client::getLastGeneratedClientCode() ?? 'None yet' }}" readonly>
            </div>
        </div>
    </div>
</div>

{{-- ───── 2. Import Behavior ───── --}}
<div class="sys-section">
    <div class="sys-section-header">
        <div class="sec-icon" style="background:#00b894;"><i class="fas fa-file-import"></i></div>
        <div>
            <div class="sec-title">Import Client Code Behavior</div>
            <div class="sec-desc">How codes are assigned during CSV/XLSX bulk import</div>
        </div>
    </div>
    <div class="sys-section-body">
        <div class="row">
            <div class="col-md-5">
                <label class="sys-label">Import Mode</label>
                <select name="import_client_code_mode" class="form-control form-control-sys">
                    <option value="auto" {{ ($settings['import_client_code_mode'] ?? 'auto') == 'auto' ? 'selected' : '' }}>Auto-generate codes sequentially</option>
                    <option value="use_imported" {{ ($settings['import_client_code_mode'] ?? '') == 'use_imported' ? 'selected' : '' }}>Use imported client codes from file</option>
                    <option value="manual_start" {{ ($settings['import_client_code_mode'] ?? '') == 'manual_start' ? 'selected' : '' }}>Start auto-generation from manual number</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="sys-label">Import Start Number <span class="text-muted font-weight-normal">(manual mode only)</span></label>
                <input type="number" name="import_code_start_number" class="form-control form-control-sys" value="{{ $settings['import_code_start_number'] ?? '' }}" min="1" placeholder="e.g. 5000">
            </div>
        </div>
    </div>
</div>

{{-- ───── 3. Login Animation ───── --}}
<div class="sys-section">
    <div class="sys-section-header">
        <div class="sec-icon" style="background:#fdcb6e;color:#2d3436;"><i class="fas fa-magic"></i></div>
        <div>
            <div class="sec-title">Login Page Animation</div>
            <div class="sec-desc">Floating background words on the login screen</div>
        </div>
    </div>
    <div class="sys-section-body">
        <div class="row">
            <div class="col-md-5">
                <label class="sys-label">Animation Words</label>
                <input type="text" name="login_animation_words" class="form-control form-control-sys" value="{{ $settings['login_animation_words'] ?? '' }}" placeholder="e.g. Smart, Online, ISP, Network">
                <span class="sys-hint">Comma-separated. Leave empty to use company name.</span>
            </div>
            <div class="col-md-3">
                <label class="sys-label">Opacity (%)</label>
                <div class="input-group" style="height:34px;">
                    <input type="number" name="login_animation_opacity" class="form-control form-control-sys" min="0" max="100" value="{{ $settings['login_animation_opacity'] ?? 40 }}">
                    <div class="input-group-append"><span class="input-group-text" style="height:34px;font-size:12px;">%</span></div>
                </div>
                <span class="sys-hint">0 = Hidden, 100 = Full visible</span>
            </div>
            <div class="col-md-4">
                <label class="sys-label">Preview</label>
                <div style="background:#f8f9fa;border:1px solid #dee2e6;border-radius:6px;padding:7px 10px;font-size:12px;border-left:3px solid #74b9ff;">
                    Current: <strong id="preview-words">{{ !empty($settings['login_animation_words']) ? $settings['login_animation_words'] : ($settings['company_name'] ?? 'SOFTYNEX') }}</strong>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ───── 4. Announcement ───── --}}
<div class="sys-section">
    <div class="sys-section-header">
        <div class="sec-icon" style="background:#e17055;"><i class="fas fa-bullhorn"></i></div>
        <div>
            <div class="sec-title">Announcement Ticker</div>
            <div class="sec-desc">Scrolling banner at the top of every admin page</div>
        </div>
    </div>
    <div class="sys-section-body">
        <div class="row">
            <div class="col-md-2">
                <label class="sys-label">Status</label>
                <select name="notice_enabled" class="form-control form-control-sys">
                    <option value="0" {{ ($settings['notice_enabled'] ?? '0') == '0' ? 'selected' : '' }}>Off</option>
                    <option value="1" {{ ($settings['notice_enabled'] ?? '0') == '1' ? 'selected' : '' }}>On</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="sys-label">Type</label>
                <select name="notice_type" class="form-control form-control-sys">
                    <option value="info" {{ ($settings['notice_type'] ?? 'info') == 'info' ? 'selected' : '' }}>Info</option>
                    <option value="warning" {{ ($settings['notice_type'] ?? 'info') == 'warning' ? 'selected' : '' }}>Warning</option>
                    <option value="critical" {{ ($settings['notice_type'] ?? 'info') == 'critical' ? 'selected' : '' }}>Critical</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="sys-label">Speed</label>
                <select name="notice_speed" class="form-control form-control-sys">
                    <option value="slow" {{ ($settings['notice_speed'] ?? 'normal') == 'slow' ? 'selected' : '' }}>Slow</option>
                    <option value="normal" {{ ($settings['notice_speed'] ?? 'normal') == 'normal' ? 'selected' : '' }}>Normal</option>
                    <option value="fast" {{ ($settings['notice_speed'] ?? 'normal') == 'fast' ? 'selected' : '' }}>Fast</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="sys-label">Announcement Text</label>
                <textarea name="notice_text" class="form-control form-control-sys" rows="2" placeholder="Enter one announcement per line. Multiple lines rotate every 8 sec.">{{ $settings['notice_text'] ?? '' }}</textarea>
                <span class="sys-hint">One per line — multiple lines auto-rotate every 8 seconds.</span>
            </div>
        </div>
    </div>
</div>

{{-- ───── Device Alert Notifications ───── --}}
<div class="sys-section">
    <div class="sys-section-header">
        <div class="sec-icon" style="background:#d63031;"><i class="fas fa-server"></i></div>
        <div>
            <div class="sec-title">Device Alert Notifications (Email &amp; SMS)</div>
            <div class="sec-desc">Get notified when MikroTik or OLT goes down or comes back online</div>
        </div>
    </div>
    <div class="sys-section-body">
        <div class="alert alert-info py-2 px-3 mb-3" style="font-size:11px;border-radius:6px;">
            <i class="fas fa-info-circle mr-1"></i>
            When a MikroTik router or OLT device goes <strong>down or comes back online</strong>, an email and SMS will be sent to the contacts below.
            Email uses the SMTP settings configured in your server. SMS uses the gateway configured in SMS Settings.
        </div>
        <div class="row">
            <div class="col-md-5">
                <label class="sys-label"><i class="fas fa-envelope mr-1 text-danger"></i> Alert Email Address</label>
                <input type="email" name="device_alert_email"
                    class="form-control form-control-sys"
                    value="{{ $settings['device_alert_email'] ?? '' }}"
                    placeholder="admin@yourcompany.com">
                <span class="sys-hint">Down/Up alerts will be emailed here. Leave blank to disable email alerts.</span>
            </div>
            <div class="col-md-4">
                <label class="sys-label"><i class="fas fa-mobile-alt mr-1 text-danger"></i> Alert Phone Number (SMS)</label>
                <input type="text" name="device_alert_phone"
                    class="form-control form-control-sys"
                    value="{{ $settings['device_alert_phone'] ?? '' }}"
                    placeholder="01XXXXXXXXX">
                <span class="sys-hint">SMS alert number. Leave blank to disable SMS alerts.</span>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <div class="p-2 rounded" style="background:#fff3cd;border:1px solid #ffc107;font-size:11px;color:#856404;width:100%;">
                    <i class="fas fa-lightbulb mr-1"></i>
                    <strong>Where does this come from?</strong><br>
                    Enter your admin email and phone number above. The system will use these contacts for all device up/down alerts.
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ───── 5. SMS ───── --}}
<div class="sys-section">
    <div class="sys-section-header">
        <div class="sec-icon" style="background:#0984e3;"><i class="fas fa-sms"></i></div>
        <div>
            <div class="sec-title">SMS Settings</div>
            <div class="sec-desc">Gateway configuration for SMS notifications</div>
        </div>
    </div>
    <div class="sys-section-body">
        <div class="row">
            <div class="col-md-4">
                <label class="sys-label">SMS Gateway</label>
                <select name="sms_gateway" class="form-control form-control-sys">
                    <option value="none" {{ ($settings['sms_gateway'] ?? '') == 'none' ? 'selected' : '' }}>None</option>
                    <option value="bulksmsbd" {{ ($settings['sms_gateway'] ?? '') == 'bulksmsbd' ? 'selected' : '' }}>BulkSMSBD</option>
                    <option value="greenweb" {{ ($settings['sms_gateway'] ?? '') == 'greenweb' ? 'selected' : '' }}>Green Web</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="sys-label">API Key</label>
                <input type="text" name="sms_api_key" class="form-control form-control-sys" value="{{ $settings['sms_api_key'] ?? '' }}" placeholder="Enter your SMS gateway API key">
            </div>
        </div>
    </div>
</div>

{{-- ───── 6. Telegram ───── --}}
<div class="sys-section">
    <div class="sys-section-header">
        <div class="sec-icon" style="background:#0088cc;"><i class="fab fa-telegram"></i></div>
        <div>
            <div class="sec-title">Telegram Notification Bots</div>
            <div class="sec-desc">Each type can have its own bot. Others fall back to the Alert Bot (Default).</div>
        </div>
    </div>
    <div class="sys-section-body pt-2">
        <div class="alert alert-info py-2 px-3 mb-3" style="font-size:11px;border-radius:6px;">
            <i class="fas fa-info-circle mr-1"></i>Create bots via <code>@BotFather</code> on Telegram. Get Chat ID by adding <code>@RawDataBot</code> to your group.
        </div>
        @php
        $telegramChannels = [
            ['key'=>'alert','label'=>'Alert Bot (Default Fallback)','desc'=>'Default for all notifications if specific bot not set.','icon'=>'fas fa-bell','color'=>'#e17055'],
            ['key'=>'ticket','label'=>'Ticket Notifications','desc'=>'Support ticket assignments and updates.','icon'=>'fas fa-ticket-alt','color'=>'#6c5ce7'],
            ['key'=>'client_status','label'=>'Client Status / Connect / Disconnect','desc'=>'Client status changes and connect/disconnect alerts.','icon'=>'fas fa-user-check','color'=>'#00b894'],
            ['key'=>'payment','label'=>'Payment Notifications','desc'=>'Manual, webhook, and online MFS payments.','icon'=>'fas fa-money-bill-wave','color'=>'#00b894'],
            ['key'=>'client_creation','label'=>'Client Creation','desc'=>'New client registration alerts.','icon'=>'fas fa-user-plus','color'=>'#0984e3'],
            ['key'=>'collection_transfer','label'=>'Collection Transfer','desc'=>'Staff collection submissions and verifications.','icon'=>'fas fa-exchange-alt','color'=>'#fdcb6e'],
            ['key'=>'expense','label'=>'Expense','desc'=>'New expense entries.','icon'=>'fas fa-file-invoice-dollar','color'=>'#d63031'],
            ['key'=>'purchase','label'=>'Store Purchase','desc'=>'New vendor product purchases.','icon'=>'fas fa-shopping-cart','color'=>'#0984e3'],
            ['key'=>'sale','label'=>'Sale / POS','desc'=>'Product and service sales.','icon'=>'fas fa-cash-register','color'=>'#00b894'],
            ['key'=>'inventory','label'=>'Inventory (Product Out / Fiber Out)','desc'=>'Product issuance and fiber/cable usage.','icon'=>'fas fa-boxes','color'=>'#fdcb6e'],
            ['key'=>'reseller_recharge','label'=>'Reseller Recharge','desc'=>'Reseller wallet and client recharges.','icon'=>'fas fa-sync-alt','color'=>'#0984e3'],
            ['key'=>'onu_ping','label'=>'ONU Down / Up Alert (Client Details)','desc'=>'When any client ONU goes offline or comes back online — shows ONU serial, port, signal, PPPoE ID, client name, phone & area.','icon'=>'fas fa-satellite-dish','color'=>'#e17055'],
            ['key'=>'device_alert','label'=>'Device Up/Down Alert','desc'=>'MikroTik/OLT up and down alerts.','icon'=>'fas fa-server','color'=>'#d63031'],
            ['key'=>'public_form','label'=>'Public Form (Apply Now)','desc'=>'New connection applications from the public form.','icon'=>'fas fa-file-alt','color'=>'#0984e3'],
        ];
        @endphp

        @foreach($telegramChannels as $i => $ch)
        @php
            $configured = ($settings["telegram_{$ch['key']}_bot_token"] ?? '') && ($settings["telegram_{$ch['key']}_group_id"] ?? '');
        @endphp
        <div class="tg-card">
            <div class="tg-card-header tg-toggle" data-target="tg_{{ $ch['key'] }}">
                <span style="width:22px;height:22px;border-radius:5px;background:{{ $ch['color'] }};display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="{{ $ch['icon'] }} text-white" style="font-size:11px;"></i>
                </span>
                <span class="tg-label">{{ $ch['label'] }}</span>
                @if($configured)
                    <span class="badge badge-success ml-auto" style="font-size:10px;">Configured</span>
                @elseif($ch['key'] === 'alert')
                    <span class="badge badge-warning ml-auto" style="font-size:10px;">Not Set</span>
                @else
                    <span class="badge badge-light ml-auto" style="font-size:10px;color:#888;">Using Default</span>
                @endif
                <i class="fas fa-chevron-down ml-2 text-muted" style="font-size:10px;"></i>
            </div>
            <div class="tg-card-body {{ $i === 0 ? 'show' : '' }}" id="tg_{{ $ch['key'] }}">
                <p class="text-muted mb-2" style="font-size:11px;">{{ $ch['desc'] }}</p>
                <div class="row">
                    <div class="col-md-6">
                        <label class="sys-label">Bot Token</label>
                        <input type="text" name="telegram_{{ $ch['key'] }}_bot_token" class="form-control form-control-sys" value="{{ $settings["telegram_{$ch['key']}_bot_token"] ?? '' }}" placeholder="From @BotFather">
                    </div>
                    <div class="col-md-6">
                        <label class="sys-label">Group Chat ID</label>
                        <input type="text" name="telegram_{{ $ch['key'] }}_group_id" class="form-control form-control-sys" value="{{ $settings["telegram_{$ch['key']}_group_id"] ?? '' }}" placeholder="e.g. -1001234567890">
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>

{{-- Save footer --}}
<div class="d-flex justify-content-end mb-4">
    <button type="submit" class="btn btn-primary px-5">
        <i class="fas fa-save mr-2"></i>Save All Settings
    </button>
</div>

</form>
@endsection

@push('scripts')
<script>
// Telegram accordion toggle
$(document).on('click', '.tg-toggle', function() {
    var id = $(this).data('target');
    var body = $('#' + id);
    body.toggleClass('show');
});
// Preview words live update
$('input[name="login_animation_words"]').on('input', function() {
    var val = $(this).val().trim();
    $('#preview-words').text(val || '{{ $settings["company_name"] ?? "SOFTYNEX" }}');
});
</script>
@endpush
