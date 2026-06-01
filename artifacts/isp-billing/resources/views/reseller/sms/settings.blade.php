@extends('layouts.reseller')
@section('title', 'SMS Settings')

@section('content')
<div class="container-fluid py-4">

    {{-- Title --}}
    <div class="row mb-4">
        <div class="col-12">
            <h4 class="mb-0"><i class="fas fa-sms mr-2"></i>SMS Settings</h4>
            <p class="text-muted mb-0">Configure SMS provider and notification settings</p>
            <a href="{{ route('reseller.sms.templates') }}" class="btn btn-outline-primary btn-sm mt-2">
                <i class="fas fa-file-alt mr-1"></i> Edit SMS Templates
            </a>
        </div>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible mb-3">
            <i class="fas fa-check-circle mr-1"></i> {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible mb-3">
            <i class="fas fa-exclamation-circle mr-1"></i> {{ session('error') }}
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    @endif

    {{-- Stat Cards --}}
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <h3 class="mb-0">{{ number_format($stats['total_sent']) }}</h3>
                    <small>Total Sent</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body text-center">
                    <h3 class="mb-0">{{ number_format($stats['total_failed']) }}</h3>
                    <small>Total Failed</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h3 class="mb-0">{{ number_format($stats['today_sent']) }}</h3>
                    <small>Today Sent</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-secondary text-white">
                <div class="card-body text-center">
                    <h3 class="mb-0">{{ number_format($stats['total_skipped']) }}</h3>
                    <small>Total Skipped</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Check SMS Balance --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-info">
                <div class="card-header bg-info text-white d-flex justify-content-between align-items-center flex-wrap" style="gap:8px;">
                    <h5 class="mb-0"><i class="fas fa-wallet mr-2"></i>Check SMS Balance</h5>
                    <button type="button" class="btn btn-light btn-sm" onclick="checkBalance()" id="check-balance-btn">
                        <i class="fas fa-sync-alt mr-1" id="balance-spinner"></i> Check Now
                    </button>
                </div>
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-4 text-center border-right">
                            <h2 class="mb-0 text-info" id="sms-balance">
                                {{ is_numeric($apiStatus['balance'] ?? null) ? number_format($apiStatus['balance'], 2) : ($apiStatus['balance'] ?? 'N/A') }}
                            </h2>
                            <small class="text-muted">Current Balance</small>
                        </div>
                        <div class="col-md-4 text-center border-right">
                            <h5 class="mb-0" id="balance-currency">{{ $apiStatus['currency'] ?? 'BDT' }}</h5>
                            <small class="text-muted">Currency</small>
                        </div>
                        <div class="col-md-4 text-center">
                            <h5 class="mb-0" id="balance-provider">{{ $apiStatus['provider_name'] ?? 'Not Set' }}</h5>
                            <small class="text-muted">Provider</small>
                        </div>
                    </div>
                    <div id="balance-result" class="mt-3"></div>
                    <div id="balance-details" class="mt-2 small text-muted"></div>
                </div>
            </div>
        </div>
    </div>

    <form action="{{ route('reseller.sms.settings.save') }}" method="POST">
        @csrf

        <div class="row">

            {{-- Left Column --}}
            <div class="col-md-6">

                {{-- SMS Provider Configuration --}}
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-cloud mr-2"></i>SMS Provider Configuration</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert {{ $apiStatus['configured'] ? 'alert-success' : 'alert-warning' }} mb-3">
                            <i class="fas {{ $apiStatus['configured'] ? 'fa-check-circle' : 'fa-exclamation-circle' }} mr-2"></i>
                            <strong>Status:</strong> {{ $apiStatus['configured'] ? 'Configured' : 'Not Configured' }}
                            | Provider: <strong>{{ $apiStatus['provider_name'] ?? 'Not Set' }}</strong>
                            @if($apiStatus['sender_id'] ?? null)
                                | Sender: <strong>{{ $apiStatus['sender_id'] }}</strong>
                            @endif
                        </div>

                        <div class="form-group">
                            <label class="form-label">SMS Provider <span class="text-danger">*</span></label>
                            <select class="form-control" name="provider" id="provider" onchange="toggleProviderFields()">
                                @foreach($providers as $key => $name)
                                    <option value="{{ $key }}" {{ $setting->provider === $key ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">API Key <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="sms_api_key" name="api_key"
                                       value="{{ $setting->api_key }}" placeholder="Enter your API Key">
                                <div class="input-group-append">
                                    <button class="btn btn-outline-secondary" type="button" onclick="toggleApiKey()">
                                        <i class="fas fa-eye" id="api-key-icon"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Sender ID</label>
                            <input type="text" class="form-control" name="sender_id"
                                   value="{{ $setting->sender_id }}" placeholder="e.g. SmartISP">
                        </div>

                        {{-- ADN SMS API Secret --}}
                        <div id="adn-fields" style="{{ $setting->provider === 'adnsms' ? '' : 'display:none' }}">
                            <div class="form-group">
                                <label class="form-label">ADN API Secret <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" name="api_url"
                                       value="{{ $setting->api_url }}" placeholder="ADN SMS API Secret">
                            </div>
                        </div>

                        <div id="provider-hint" class="alert alert-info py-2 mt-2" style="display:none; font-size:0.85rem;"></div>
                    </div>
                </div>

                {{-- Custom API Configuration --}}
                <div class="card mb-4" id="custom-api-card" style="{{ $setting->provider === 'custom' ? '' : 'display:none' }}">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0"><i class="fas fa-code mr-2"></i>Custom API Configuration</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info mb-3">
                            <i class="fas fa-info-circle mr-2"></i>Configure your custom SMS API endpoint URL and parameter names.
                        </div>
                        @php $cfg = $setting->getCustomApiConfig(); @endphp
                        <div class="form-group">
                            <label class="form-label">API URL <span class="text-danger">*</span></label>
                            <input type="url" class="form-control" name="custom_api_url"
                                   value="{{ $cfg['url'] ?? '' }}" placeholder="https://api.yourprovider.com/send">
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">HTTP Method</label>
                                    <select class="form-control" name="custom_api_method">
                                        <option value="GET" {{ ($cfg['method'] ?? 'GET') === 'GET' ? 'selected' : '' }}>GET</option>
                                        <option value="POST" {{ ($cfg['method'] ?? '') === 'POST' ? 'selected' : '' }}>POST (Form)</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">API Key Parameter</label>
                                    <input type="text" class="form-control" name="custom_api_key_param"
                                           value="{{ $cfg['api_key_param'] ?? 'apikey' }}" placeholder="apikey">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Phone Parameter</label>
                                    <input type="text" class="form-control" name="custom_phone_param"
                                           value="{{ $cfg['phone_param'] ?? 'number' }}" placeholder="number">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Message Parameter</label>
                                    <input type="text" class="form-control" name="custom_message_param"
                                           value="{{ $cfg['message_param'] ?? 'message' }}" placeholder="message">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Sender ID Parameter</label>
                                    <input type="text" class="form-control" name="custom_sender_id_param"
                                           value="{{ $cfg['sender_id_param'] ?? 'senderid' }}" placeholder="senderid">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Success Keyword</label>
                                    <input type="text" class="form-control" name="custom_success_response"
                                           value="{{ $cfg['success_response'] ?? '' }}" placeholder="e.g. success or 1800">
                                    <small class="text-muted">Keyword to identify successful response</small>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Extra Parameters</label>
                            <input type="text" class="form-control" name="custom_extra_params"
                                   value="{{ $cfg['extra_params'] ?? '' }}" placeholder="type=text&format=json">
                            <small class="text-muted">Format: param1=value1&param2=value2</small>
                        </div>
                    </div>
                </div>

                {{-- Instant API Test --}}
                <div class="card mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-vial mr-2"></i>Instant API Test</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-3">Test if your SMS API is working correctly. Enter a phone number to receive a test message.</p>
                        <div class="row">
                            <div class="col-md-6 mb-2">
                                <input type="text" class="form-control" id="test_phone" placeholder="Phone (e.g., 01712345678)">
                            </div>
                            <div class="col-md-6 mb-2">
                                <button type="button" class="btn btn-success btn-block" onclick="sendTestSms()" id="test-sms-btn">
                                    <i class="fas fa-paper-plane mr-1"></i> Send Test SMS
                                </button>
                            </div>
                        </div>
                        <div class="mb-2">
                            <input type="text" class="form-control" id="test_message"
                                   placeholder="Test message (optional)"
                                   value="Test SMS from ISP Billing System. Your API is working!">
                        </div>
                        <div id="test-result" class="mt-2"></div>
                    </div>
                </div>

            </div>

            {{-- Right Column --}}
            <div class="col-md-6">

                {{-- Global SMS Control --}}
                <div class="card mb-4">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0"><i class="fas fa-power-off mr-2"></i>Global SMS Control</h5>
                    </div>
                    <div class="card-body">
                        <div class="custom-control custom-switch mb-3">
                            <input type="checkbox" class="custom-control-input" id="is_enabled"
                                   name="is_enabled" value="1" {{ $setting->is_enabled ? 'checked' : '' }}>
                            <label class="custom-control-label font-weight-bold" for="is_enabled">
                                Enable SMS System (Master Switch)
                            </label>
                        </div>
                        <div class="alert alert-warning mb-0">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            <strong>Warning:</strong> If disabled, NO SMS will be sent. Billing and payments will continue normally.
                        </div>
                    </div>
                </div>

                {{-- Event-wise SMS Toggle --}}
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-bell mr-2"></i>Event-wise SMS Toggle</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-3">Enable or disable SMS for specific events:</p>
                        @foreach($events as $eventKey => $eventLabel)
                        <div class="custom-control custom-switch mb-2">
                            <input type="checkbox" class="custom-control-input" id="event_{{ $eventKey }}"
                                   name="event_{{ $eventKey }}" value="1"
                                   {{ $setting->isEventEnabled($eventKey) ? 'checked' : '' }}>
                            <label class="custom-control-label" for="event_{{ $eventKey }}">{{ $eventLabel }}</label>
                        </div>
                        @endforeach
                    </div>
                </div>

            </div>
        </div>

        {{-- Bottom Bar --}}
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body d-flex justify-content-between align-items-center flex-wrap" style="gap:8px;">
                        <div>
                            <a href="{{ route('reseller.sms.logs') }}" class="btn btn-outline-primary mr-2">
                                <i class="fas fa-list mr-1"></i> View SMS Logs
                            </a>
                            <a href="{{ route('reseller.sms.bulk') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-paper-plane mr-1"></i> Bulk SMS
                            </a>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-1"></i> Save Settings
                        </button>
                    </div>
                </div>
            </div>
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
    var provider = document.getElementById('provider').value;
    var customCard = document.getElementById('custom-api-card');
    var adnFields  = document.getElementById('adn-fields');
    var hintDiv    = document.getElementById('provider-hint');

    customCard.style.display = (provider === 'custom') ? 'block' : 'none';
    adnFields.style.display  = (provider === 'adnsms') ? 'block' : 'none';

    var hints = {
        'mimsms':    '<i class="fas fa-info-circle mr-1"></i><strong>MimSMS:</strong> API Key field = your MimSMS account email. Sender ID field = Apikey from MimSMS portal (api.mimsms.com).',
        'muthofun':  '<i class="fas fa-info-circle mr-1"></i><strong>Muthofun:</strong> API Key = Token from Developer section at clients.muthobarta.com. Sender ID not required.',
        'adnsms':    '<i class="fas fa-info-circle mr-1"></i><strong>ADN SMS:</strong> Get both API Key and API Secret from portal.adnsms.com. Fill in the API Secret field below.',
        'greenweb':  '<i class="fas fa-info-circle mr-1"></i><strong>Greenweb:</strong> API Key = Token generated from gwb.li/token.',
        'bulksmspro':'<i class="fas fa-info-circle mr-1"></i><strong>BulkSMS Pro:</strong> Configure API Key, Sender ID and Phone Numbers from bulksmspro.net portal.',
        'ssl_sms':   '<i class="fas fa-info-circle mr-1"></i><strong>SSL Wireless:</strong> API Key = api_token, Sender ID = sid (ismsplus.sslwireless.com).',
        'alpha_sms': '<i class="fas fa-info-circle mr-1"></i><strong>Alpha SMS:</strong> API Key from the API section of sms.net.bd panel. Sender ID optional.',
        'elitbuzz':  '<i class="fas fa-info-circle mr-1"></i><strong>Elitbuzz:</strong> API Key = api_key, Sender ID = senderid (msg.elitbuzz-bd.com).',
    };

    if (hints[provider]) {
        hintDiv.innerHTML = hints[provider];
        hintDiv.style.display = 'block';
    } else {
        hintDiv.style.display = 'none';
    }
}

function toggleApiKey() {
    var input = document.getElementById('sms_api_key');
    var icon  = document.getElementById('api-key-icon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}

function checkBalance() {
    var spinner   = document.getElementById('balance-spinner');
    var btn       = document.getElementById('check-balance-btn');
    var resultDiv = document.getElementById('balance-result');
    var detailsDiv= document.getElementById('balance-details');

    spinner.classList.add('fa-spin');
    btn.disabled = true;
    resultDiv.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Checking balance...</div>';

    fetch('{{ route("reseller.sms.balance") }}', {
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function(response) {
        if (!response.ok) throw new Error('Server returned ' + response.status);
        return response.json();
    })
    .then(function(data) {
        var balance = data.balance;
        var balNum  = parseFloat(balance);
        document.getElementById('sms-balance').textContent = isNaN(balNum) ? (balance || 'N/A') : balNum.toFixed(2);

        if (data.currency) {
            document.getElementById('balance-currency').textContent = data.currency;
        }

        if (data.error) {
            var portalHtml = data.portal_url
                ? ' <a href="' + data.portal_url + '" target="_blank" class="btn btn-sm btn-outline-secondary ml-2"><i class="fas fa-external-link-alt mr-1"></i>View ' + (data.portal_name || 'Portal') + '</a>'
                : '';
            resultDiv.innerHTML = '<div class="alert alert-warning py-2 mb-0"><i class="fas fa-exclamation-triangle mr-1"></i> ' + data.error + portalHtml + '</div>';
        } else {
            resultDiv.innerHTML = '<div class="alert alert-success py-2 mb-0"><i class="fas fa-check-circle mr-1"></i> Balance retrieved successfully! Last updated: ' + (data.updated_at || 'Just now') + '</div>';
        }

        if (data.raw_response) {
            detailsDiv.innerHTML = '<details><summary>API Response Details</summary><pre class="mt-2 p-2 bg-light rounded" style="max-height:150px;overflow:auto;">' + JSON.stringify(data.raw_response, null, 2) + '</pre></details>';
        } else {
            detailsDiv.innerHTML = '';
        }

        spinner.classList.remove('fa-spin');
        btn.disabled = false;
    })
    .catch(function(error) {
        resultDiv.innerHTML = '<div class="alert alert-danger py-2 mb-0"><i class="fas fa-times-circle mr-1"></i> Failed to check balance: ' + error.message + '</div>';
        detailsDiv.innerHTML = '';
        spinner.classList.remove('fa-spin');
        btn.disabled = false;
    });
}

function sendTestSms() {
    var phone   = document.getElementById('test_phone').value.trim();
    var message = document.getElementById('test_message').value.trim();
    var btn     = document.getElementById('test-sms-btn');
    var result  = document.getElementById('test-result');

    if (!phone) {
        result.innerHTML = '<div class="alert alert-warning py-2"><i class="fas fa-exclamation-circle mr-1"></i> Please enter a phone number</div>';
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Testing API...';
    result.innerHTML = '';

    fetch('{{ route("reseller.sms.test") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').content : '{{ csrf_token() }}',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ phone: phone, message: message || 'Test SMS from ISP Billing System.' })
    })
    .then(function(r) {
        if (!r.ok) throw new Error('Server error ' + r.status);
        return r.json();
    })
    .then(function(data) {
        var html = '';
        if (data.success) {
            html = '<div class="alert alert-success py-2"><i class="fas fa-check-circle mr-1"></i> <strong>API Working!</strong> Test SMS sent successfully.</div>';
        } else {
            html = '<div class="alert alert-danger py-2"><i class="fas fa-times-circle mr-1"></i> <strong>API Error:</strong> ' + (data.error || data.message || 'Failed to send SMS') + '</div>';
        }
        result.innerHTML = html;
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-paper-plane mr-1"></i> Send Test SMS';
    })
    .catch(function(error) {
        result.innerHTML = '<div class="alert alert-danger py-2"><i class="fas fa-times-circle mr-1"></i> Error: ' + error.message + '</div>';
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-paper-plane mr-1"></i> Send Test SMS';
    });
}
</script>
@endpush
