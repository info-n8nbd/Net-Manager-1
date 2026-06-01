@extends('layouts.admin')

@section('title', 'SMS Settings')

@section('content')
<div class="container-fluid pt-2 pb-3">

    {{-- Stats Row --}}
    <div class="row mb-2">
        <div class="col-6 col-md-3">
            <div class="small-box bg-primary">
                <div class="inner">
                    <h4>{{ number_format($stats['total_sent']) }}</h4>
                    <p>Total Sent</p>
                </div>
                <div class="icon"><i class="fas fa-paper-plane"></i></div>
                
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h4>{{ number_format($stats['total_failed']) }}</h4>
                    <p>Total Failed</p>
                </div>
                <div class="icon"><i class="fas fa-times-circle"></i></div>
                
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="small-box bg-success">
                <div class="inner">
                    <h4>{{ number_format($stats['today_sent']) }}</h4>
                    <p>Today Sent</p>
                </div>
                <div class="icon"><i class="fas fa-check-circle"></i></div>
                
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="small-box bg-secondary">
                <div class="inner">
                    <h4>{{ number_format($stats['total_skipped'] ?? 0) }}</h4>
                    <p>Total Skipped</p>
                </div>
                <div class="icon"><i class="fas fa-forward"></i></div>
                
            </div>
        </div>
    </div>

    {{-- Balance bar --}}
    <div class="card card-outline card-info mb-2">
        <div class="card-body py-2 px-3">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">

                {{-- Left: Balance + Refresh + inline status --}}
                <div class="d-flex align-items-center" style="gap:8px;">
                    <i class="fas fa-wallet text-info"></i>
                    <span class="font-weight-bold text-info" style="font-size:20px;line-height:1;" id="sms-balance">{{ is_numeric($apiStatus['balance'] ?? 0) ? number_format($apiStatus['balance'], 2) : ($apiStatus['balance'] ?? 'N/A') }}</span>
                    <span class="badge badge-info" style="font-size:11px;" id="balance-currency">{{ $apiStatus['currency'] ?? 'BDT' }}</span>
                    <button type="button" class="btn btn-info btn-sm px-2 py-1" onclick="checkBalance()" id="check-balance-btn" title="Refresh Balance">
                        <i class="fas fa-sync-alt" id="balance-spinner" style="font-size:11px;"></i>
                    </button>
                    <span id="balance-result" class="small ml-1"></span>
                </div>

                {{-- Middle: Provider --}}
                <div class="text-muted small" id="balance-provider">
                    <i class="fas fa-cloud mr-1 text-info"></i>
                    <span class="font-weight-bold text-dark">{{ $apiStatus['provider_name'] ?? 'Not Set' }}</span>
                    @if($apiStatus['sender_id'] ?? false)
                        &nbsp;<span class="text-muted">· {{ $apiStatus['sender_id'] }}</span>
                    @endif
                </div>

                {{-- Right: Templates link --}}
                <a href="{{ route('admin.sms.templates') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-file-alt mr-1"></i> Templates
                </a>
            </div>
            <div id="balance-details" class="small text-muted mt-1"></div>
        </div>
    </div>

    <form action="{{ route('admin.settings.sms.update') }}" method="POST">
        @csrf
        @method('PUT')

        <div class="row">
            {{-- LEFT: Provider config --}}
            <div class="col-md-6">
                <div class="card card-primary card-outline mb-2">
                    <div class="card-header py-2">
                        <h6 class="card-title mb-0"><i class="fas fa-cloud mr-1"></i>SMS Provider Configuration</h6>
                    </div>
                    <div class="card-body p-3">
                        <div class="alert {{ $apiStatus['configured'] ? 'alert-success' : 'alert-warning' }} py-2 mb-2 d-flex align-items-center flex-wrap" style="gap:6px 16px; row-gap:4px;">
                            <span class="text-nowrap">
                                <i class="fas {{ $apiStatus['configured'] ? 'fa-check-circle' : 'fa-exclamation-circle' }} mr-1"></i>
                                <strong>{{ $apiStatus['configured'] ? 'Configured' : 'Not Configured' }}</strong>
                            </span>
                            <span class="text-nowrap text-muted" style="font-size:13px;">|</span>
                            <span class="text-nowrap">
                                <i class="fas fa-cloud mr-1" style="font-size:11px;"></i>
                                <strong>{{ $apiStatus['provider_name'] ?? 'Not Set' }}</strong>
                            </span>
                            @if($apiStatus['sender_id'])
                                <span class="text-nowrap text-muted" style="font-size:13px;">|</span>
                                <span class="text-nowrap">
                                    Sender: <strong>{{ $apiStatus['sender_id'] }}</strong>
                                </span>
                            @endif
                        </div>

                        <div class="form-group mb-2">
                            <label class="small font-weight-bold">SMS Provider <span class="text-danger">*</span></label>
                            <select class="form-control form-control-sm" id="sms_provider" name="sms_provider" onchange="toggleProviderFields()">
                                @foreach($providers as $key => $name)
                                    <option value="{{ $key }}" {{ $settings['sms_provider'] == $key ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group mb-2">
                            <label class="small font-weight-bold">API Key <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm">
                                <input type="password" class="form-control" id="sms_api_key" name="sms_api_key" value="{{ $settings['sms_api_key'] }}" placeholder="Enter your API Key">
                                <div class="input-group-append">
                                    <button class="btn btn-outline-secondary" type="button" onclick="toggleApiKey()">
                                        <i class="fas fa-eye" id="api-key-icon"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="form-group mb-2">
                            <label class="small font-weight-bold">Sender ID <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm" id="sms_sender_id" name="sms_sender_id" value="{{ $settings['sms_sender_id'] }}" placeholder="e.g., MyCompany">
                        </div>

                        <div id="twilio-fields" style="display:none">
                            <div class="form-group mb-2">
                                <label class="small font-weight-bold">Twilio Auth Token</label>
                                <input type="password" class="form-control form-control-sm" id="sms_twilio_auth_token" name="sms_twilio_auth_token" value="{{ $settings['sms_twilio_auth_token'] ?? '' }}" placeholder="Twilio Auth Token">
                            </div>
                        </div>
                        <div id="reve-fields" style="display:none">
                            <div class="form-group mb-2">
                                <label class="small font-weight-bold">Reve Secret Key</label>
                                <input type="password" class="form-control form-control-sm" id="sms_reve_secret_key" name="sms_reve_secret_key" value="{{ $settings['sms_reve_secret_key'] ?? '' }}" placeholder="Reve SMS Secret Key">
                            </div>
                        </div>
                        <div id="ajura-fields" style="display:none">
                            <div class="form-group mb-2">
                                <label class="small font-weight-bold">Ajura Secret Key</label>
                                <input type="password" class="form-control form-control-sm" id="sms_ajura_secret_key" name="sms_ajura_secret_key" value="{{ $settings['sms_ajura_secret_key'] ?? '' }}" placeholder="Ajura Tech Secret Key">
                            </div>
                        </div>
                        <div id="adn-fields" style="display:none">
                            <div class="form-group mb-2">
                                <label class="small font-weight-bold">ADN API Secret</label>
                                <input type="password" class="form-control form-control-sm" id="sms_adn_secret_key" name="sms_adn_secret_key" value="{{ $settings['sms_adn_secret_key'] ?? '' }}" placeholder="ADN SMS API Secret">
                            </div>
                        </div>

                        <div id="provider-hint" class="alert alert-info py-2 mb-0" style="display:none;font-size:0.82rem;"></div>
                    </div>
                </div>

                {{-- Custom API (hidden unless custom selected) --}}
                <div class="card card-secondary card-outline mb-2" id="custom-api-card" style="display:none">
                    <div class="card-header py-2">
                        <h6 class="card-title mb-0"><i class="fas fa-code mr-1"></i>Custom API Configuration</h6>
                    </div>
                    <div class="card-body p-3">
                        <div class="form-group mb-2">
                            <label class="small font-weight-bold">API URL <span class="text-danger">*</span></label>
                            <input type="url" class="form-control form-control-sm" id="sms_custom_api_url" name="sms_custom_api_url" value="{{ $settings['sms_custom_api_url'] }}" placeholder="https://api.yourprovider.com/send">
                        </div>
                        <div class="row">
                            <div class="col-6">
                                <div class="form-group mb-2">
                                    <label class="small font-weight-bold">HTTP Method</label>
                                    <select class="form-control form-control-sm" id="sms_custom_api_method" name="sms_custom_api_method">
                                        <option value="GET" {{ $settings['sms_custom_api_method'] == 'GET' ? 'selected' : '' }}>GET</option>
                                        <option value="POST" {{ $settings['sms_custom_api_method'] == 'POST' ? 'selected' : '' }}>POST (Form)</option>
                                        <option value="POST_JSON" {{ $settings['sms_custom_api_method'] == 'POST_JSON' ? 'selected' : '' }}>POST (JSON)</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group mb-2">
                                    <label class="small font-weight-bold">API Key Param</label>
                                    <input type="text" class="form-control form-control-sm" id="sms_custom_api_key_param" name="sms_custom_api_key_param" value="{{ $settings['sms_custom_api_key_param'] }}" placeholder="apikey">
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group mb-2">
                                    <label class="small font-weight-bold">Phone Param</label>
                                    <input type="text" class="form-control form-control-sm" id="sms_custom_phone_param" name="sms_custom_phone_param" value="{{ $settings['sms_custom_phone_param'] }}" placeholder="number">
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group mb-2">
                                    <label class="small font-weight-bold">Message Param</label>
                                    <input type="text" class="form-control form-control-sm" id="sms_custom_message_param" name="sms_custom_message_param" value="{{ $settings['sms_custom_message_param'] }}" placeholder="message">
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group mb-2">
                                    <label class="small font-weight-bold">Sender ID Param</label>
                                    <input type="text" class="form-control form-control-sm" id="sms_custom_sender_id_param" name="sms_custom_sender_id_param" value="{{ $settings['sms_custom_sender_id_param'] }}" placeholder="senderid">
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group mb-2">
                                    <label class="small font-weight-bold">Success Keyword</label>
                                    <input type="text" class="form-control form-control-sm" id="sms_custom_success_response" name="sms_custom_success_response" value="{{ $settings['sms_custom_success_response'] }}" placeholder="success">
                                </div>
                            </div>
                        </div>
                        <div class="form-group mb-2">
                            <label class="small font-weight-bold">Extra Params</label>
                            <input type="text" class="form-control form-control-sm" id="sms_custom_extra_params" name="sms_custom_extra_params" value="{{ $settings['sms_custom_extra_params'] }}" placeholder="type=text&unicode=0">
                        </div>
                        <hr class="my-2">
                        <p class="small font-weight-bold text-info mb-2"><i class="fas fa-wallet mr-1"></i>Balance Check (Optional)</p>
                        <div class="row">
                            <div class="col-8">
                                <div class="form-group mb-2">
                                    <label class="small">Balance URL</label>
                                    <input type="url" class="form-control form-control-sm" id="sms_custom_balance_url" name="sms_custom_balance_url" value="{{ $settings['sms_custom_balance_url'] ?? '' }}" placeholder="https://api.yourprovider.com/balance">
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="form-group mb-2">
                                    <label class="small">Balance Field</label>
                                    <input type="text" class="form-control form-control-sm" id="sms_custom_balance_field" name="sms_custom_balance_field" value="{{ $settings['sms_custom_balance_field'] ?? 'data.current_balance' }}" placeholder="data.balance">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Test SMS --}}
                <div class="card card-success card-outline mb-2">
                    <div class="card-header py-2">
                        <h6 class="card-title mb-0"><i class="fas fa-vial mr-1"></i>Instant API Test</h6>
                    </div>
                    <div class="card-body p-3">
                        <div class="row">
                            <div class="col-7">
                                <input type="text" class="form-control form-control-sm" id="test_phone" placeholder="Phone (e.g., 01712345678)">
                            </div>
                            <div class="col-5">
                                <button type="button" class="btn btn-success btn-sm btn-block" onclick="sendTestSms()" id="test-sms-btn">
                                    <i class="fas fa-paper-plane mr-1"></i> Send Test
                                </button>
                            </div>
                        </div>
                        <div class="mt-2">
                            <input type="text" class="form-control form-control-sm" id="test_message" value="Test SMS from ISP Billing System. Your API is working!" placeholder="Test message (optional)">
                        </div>
                        <div id="test-result" class="mt-2"></div>
                    </div>
                </div>
            </div>

            {{-- RIGHT: Controls --}}
            <div class="col-md-6">
                {{-- Global switch --}}
                <div class="card card-danger card-outline mb-2">
                    <div class="card-header py-2">
                        <h6 class="card-title mb-0"><i class="fas fa-power-off mr-1"></i>Global SMS Control</h6>
                    </div>
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <label class="font-weight-bold mb-0" for="sms_global_enabled">Enable SMS System (Master Switch)</label>
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="sms_global_enabled" name="sms_global_enabled" value="1" {{ $settings['sms_global_enabled'] == '1' ? 'checked' : '' }}>
                                <label class="custom-control-label" for="sms_global_enabled"></label>
                            </div>
                        </div>
                        <div class="alert alert-warning py-2 mb-0 small">
                            <i class="fas fa-exclamation-triangle mr-1"></i>
                            <strong>Warning:</strong> If disabled, NO SMS will be sent. Billing and payments continue normally.
                        </div>
                    </div>
                </div>

                {{-- Event toggles --}}
                <div class="card card-outline mb-2">
                    <div class="card-header py-2">
                        <h6 class="card-title mb-0"><i class="fas fa-bell mr-1"></i>Event-wise SMS Toggle</h6>
                    </div>
                    <div class="card-body p-3">
                        @php
                        $events = [
                            'sms_event_monthly_bill'       => 'Monthly Bill SMS',
                            'sms_event_bill_confirmation'  => 'Bill Confirmation SMS',
                            'sms_event_payment_confirmation' => 'Payment Confirmation SMS',
                            'sms_event_service_suspension' => 'Service Suspension SMS',
                            'sms_event_service_restoration' => 'Service Restoration SMS',
                            'sms_event_advance_payment'    => 'Advance Payment SMS',
                            'sms_event_due_reminder_3day'  => '3-Day Auto-Inactive Reminder SMS',
                            'sms_event_due_reminder_1day'  => '1-Day Auto-Inactive Reminder SMS',
                            'sms_event_auto_inactive'      => 'Auto-Inactive Notice SMS',
                        ];
                        @endphp
                        @foreach($events as $key => $label)
                        <div class="d-flex align-items-center justify-content-between border-bottom py-1">
                            <label class="mb-0 small" for="{{ $key }}">{{ $label }}</label>
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="{{ $key }}" name="{{ $key }}" value="1"
                                    {{ ($settings[$key] ?? '1') == '1' ? 'checked' : '' }}>
                                <label class="custom-control-label" for="{{ $key }}"></label>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

                {{-- Role controls --}}
                <div class="card card-outline mb-2">
                    <div class="card-header py-2">
                        <h6 class="card-title mb-0"><i class="fas fa-users mr-1"></i>Role-wise SMS Control</h6>
                    </div>
                    <div class="card-body p-3">
                        @php
                        $roles = [
                            'sms_role_client'      => 'Send SMS to Clients',
                            'sms_role_technician'  => 'Send SMS to Technicians',
                            'sms_role_admin'       => 'Send SMS to Admin Users',
                        ];
                        @endphp
                        @foreach($roles as $key => $label)
                        <div class="d-flex align-items-center justify-content-between border-bottom py-1">
                            <label class="mb-0 small" for="{{ $key }}">{{ $label }}</label>
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="{{ $key }}" name="{{ $key }}" value="1"
                                    {{ ($settings[$key] ?? '1') == '1' ? 'checked' : '' }}>
                                <label class="custom-control-label" for="{{ $key }}"></label>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- Automation SMS Send Time --}}
                <div class="card card-outline card-info mb-2">
                    <div class="card-header py-2">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-clock mr-1"></i>Automation SMS Send Time
                        </h6>
                    </div>
                    <div class="card-body p-3">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <label class="small font-weight-bold mb-0">Daily Reminder Time</label>
                                <div class="text-muted" style="font-size:0.75rem;">3-day &amp; 1-day auto-inactive reminders send at this time every day</div>
                            </div>
                            <div class="col-auto">
                                <input type="time" name="sms_auto_reminder_time" class="form-control form-control-sm"
                                    value="{{ $settings['sms_auto_reminder_time'] ?? '09:30' }}"
                                    style="width:130px;">
                            </div>
                            <div class="col">
                                <small class="text-muted">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    Default: <strong>09:30</strong> AM &mdash;
                                    Auto-Inactive Notice is sent instantly when line is cut (not scheduled).
                                </small>
                            </div>
                        </div>
                    </div>
                </div>

        {{-- Save bar --}}
        <div class="d-flex justify-content-between align-items-center pt-1">
            <a href="{{ route('admin.sms.logs') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-list mr-1"></i> View SMS Logs
            </a>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save mr-1"></i> Save Settings
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    toggleProviderFields();
});

function toggleProviderFields() {
    const provider = document.getElementById('sms_provider').value;
    const customApiCard = document.getElementById('custom-api-card');
    const twilioFields = document.getElementById('twilio-fields');
    const reveFields = document.getElementById('reve-fields');
    const ajuraFields = document.getElementById('ajura-fields');
    const adnFields = document.getElementById('adn-fields');
    const hintDiv = document.getElementById('provider-hint');

    customApiCard.style.display = provider === 'custom' ? 'block' : 'none';
    twilioFields.style.display = provider === 'twilio' ? 'block' : 'none';
    reveFields.style.display = provider === 'reve_sms' ? 'block' : 'none';
    ajuraFields.style.display = provider === 'ajuratech' ? 'block' : 'none';
    adnFields.style.display = provider === 'adnsms' ? 'block' : 'none';

    const hints = {
        'mimsms':       '<i class="fas fa-info-circle me-1"></i><strong>MimSMS:</strong> API Key field = your MimSMS account email. Sender ID field = the Apikey from the MimSMS portal.',
        'muthofun':     '<i class="fas fa-info-circle me-1"></i><strong>Muthofun:</strong> API Key = Token from the Developer section at clients.muthobarta.com. No Sender ID required (token-based auth).',
        'adnsms':       '<i class="fas fa-info-circle me-1"></i><strong>ADN SMS:</strong> Get both API Key and API Secret from portal.adnsms.com.',
        'greenweb':     '<i class="fas fa-info-circle me-1"></i><strong>Greenweb:</strong> API Key = Token generated from gwb.li/token.',
        'bulksmspro':   '<i class="fas fa-info-circle me-1"></i><strong>BulkSMS Pro:</strong> Configure API Key, Sender ID and Phone Numbers parameter from the bulksmspro.net portal.',
        'ssl_sms':      '<i class="fas fa-info-circle me-1"></i><strong>SSL Wireless:</strong> API Key = api_token, Sender ID = sid (from ismsplus.sslwireless.com).',
        'alpha_sms':    '<i class="fas fa-info-circle me-1"></i><strong>Alpha SMS:</strong> API Key = from the API section of the sms.net.bd panel. Sender ID is optional.',
        'onnorokom':    '<i class="fas fa-info-circle me-1"></i><strong>Onnorokom:</strong> API Key = apiKey, Sender ID = maskName (from onnorokomsms.com).',
        'elitbuzz':     '<i class="fas fa-info-circle me-1"></i><strong>Elitbuzz:</strong> API Key = api_key, Sender ID = senderid (msg.elitbuzz-bd.com).',
    };

    if (hints[provider]) {
        hintDiv.innerHTML = hints[provider];
        hintDiv.style.display = 'block';
    } else {
        hintDiv.style.display = 'none';
    }
}

function refreshBalance() {
    checkBalance();
}

function checkBalance() {
    const spinner = document.getElementById('balance-spinner');
    const btn = document.getElementById('check-balance-btn');
    const resultDiv = document.getElementById('balance-result');
    const detailsDiv = document.getElementById('balance-details');
    
    spinner.classList.add('fa-spin');
    btn.disabled = true;
    resultDiv.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Checking balance...</div>';
    
    fetch('{{ route("admin.settings.sms.balance") }}', {
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    })
        .then(response => {
            if (!response.ok) throw new Error('Server returned ' + response.status);
            const ct = response.headers.get('content-type') || '';
            if (!ct.includes('application/json')) throw new Error('SMS API not configured or server error');
            return response.json();
        })
        .then(data => {
            const balance = data.balance;
            var balNum = parseFloat(balance);
            document.getElementById('sms-balance').textContent = isNaN(balNum) ? (balance || 'N/A') : balNum.toFixed(2);
            
            if (data.currency) {
                document.getElementById('balance-currency').textContent = data.currency;
            }
            
            if (data.error) {
                resultDiv.innerHTML = `<span class="text-warning"><i class="fas fa-exclamation-triangle mr-1"></i>${data.error}</span>`;
            } else {
                resultDiv.innerHTML = `<span class="text-success"><i class="fas fa-check mr-1"></i>Updated</span>`;
                setTimeout(() => { resultDiv.innerHTML = ''; }, 3000);
            }
            detailsDiv.innerHTML = '';
            
            spinner.classList.remove('fa-spin');
            btn.disabled = false;
        })
        .catch(error => {
            console.error('Error:', error);
            resultDiv.innerHTML = `<span class="text-danger small"><i class="fas fa-times-circle mr-1"></i>${error.message}</span>`;
            detailsDiv.innerHTML = '';
            spinner.classList.remove('fa-spin');
            btn.disabled = false;
        });
}

function toggleApiKey() {
    const input = document.getElementById('sms_api_key');
    const icon = document.getElementById('api-key-icon');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

function sendTestSms() {
    const phone = document.getElementById('test_phone').value;
    const message = document.getElementById('test_message').value || 'Test SMS from ISP Billing System. Your API is working!';
    const btn = document.getElementById('test-sms-btn');
    const resultDiv = document.getElementById('test-result');
    
    if (!phone) {
        resultDiv.innerHTML = '<div class="alert alert-warning py-2"><i class="fas fa-exclamation-circle me-1"></i> Please enter a phone number</div>';
        return;
    }
    
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Testing API...';
    resultDiv.innerHTML = '';
    
    fetch('{{ route("admin.settings.sms.test") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            phone: phone,
            message: message
        })
    })
    .then(response => response.json())
    .then(data => {
        let html = '';
        if (data.success) {
            html = `
                <div class="alert alert-success py-2">
                    <i class="fas fa-check-circle me-1"></i> <strong>API Working!</strong> Test SMS sent successfully.
                    <br><small class="text-muted">Response time: ${data.response_time || 'N/A'}</small>
                </div>
            `;
            refreshBalance();
        } else {
            html = `
                <div class="alert alert-danger py-2">
                    <i class="fas fa-times-circle me-1"></i> <strong>API Error:</strong> ${data.error || 'Failed to send SMS'}
                    ${data.response ? '<br><small class="text-muted">Response: ' + data.response.substring(0, 200) + '</small>' : ''}
                </div>
            `;
        }
        resultDiv.innerHTML = html;
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-paper-plane me-1"></i> Send Test SMS';
    })
    .catch(error => {
        resultDiv.innerHTML = '<div class="alert alert-danger py-2"><i class="fas fa-times-circle me-1"></i> Error: ' + error.message + '</div>';
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-paper-plane me-1"></i> Send Test SMS';
    });
}

setInterval(refreshBalance, 300000);
</script>
@endpush
