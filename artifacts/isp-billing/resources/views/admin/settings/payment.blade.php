@extends('layouts.admin')

@section('title', 'Payment Gateways')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="#">Settings</a></li>
<li class="breadcrumb-item active">Payment Gateways</li>
@endsection

@section('content')
<style>
.gw-tabs .nav-link {
    color: #fff;
    font-weight: 600;
    font-size: 13px;
    padding: 10px 18px;
    border: none;
    border-radius: 6px 6px 0 0;
    margin-right: 3px;
    transition: all 0.2s;
    opacity: 0.7;
}
.gw-tabs .nav-link:hover {
    opacity: 0.9;
    color: #fff;
}
.gw-tabs .nav-link.active {
    opacity: 1;
    color: #fff;
    box-shadow: 0 -2px 8px rgba(0,0,0,0.1);
    transform: translateY(-1px);
}
.gw-tabs .nav-link i {
    margin-right: 6px;
}
.gw-tab-ssl { background: #ffffff; }
.gw-tab-ssl.active { background: #f0f4ff; border-bottom: 3px solid #1e3c72; }
.gw-tab-bkash { background: #ffffff; }
.gw-tab-bkash.active { background: #fff0f5; border-bottom: 3px solid #E2136E; }
.gw-tab-nagad { background: #ffffff; }
.gw-tab-nagad.active { background: #fff8f0; border-bottom: 3px solid #F6921E; }
.gw-tab-webhook { background: #6366f1; }
.gw-tab-webhook.active { background: #5558e6; border-bottom: 3px solid #4346c9; }
.gw-tab-manual { background: #1e3c72; }
.gw-tab-manual.active { background: #172f5c; border-bottom: 3px solid #112347; }
.gw-section-title {
    font-size: 12px;
    font-weight: 700;
    color: #1e3c72;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 12px;
    padding-bottom: 6px;
    border-bottom: 2px solid #e2e8f0;
}
.gw-form-row {
    display: flex;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid #f7fafc;
}
.gw-form-row:last-child { border-bottom: none; }
.gw-form-row label {
    font-size: 12px;
    font-weight: 600;
    color: #4a5568;
    min-width: 160px;
    margin-bottom: 0;
}
.gw-form-row .form-control, .gw-form-row select {
    font-size: 12px;
    height: 34px;
    border-radius: 6px;
}
.gw-status-badge {
    display: inline-flex;
    align-items: center;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
}
.gw-status-active { background: #d1fae5; color: #065f46; }
.gw-status-inactive { background: #fee2e2; color: #991b1b; }
.gw-portal-check {
    display: inline-flex;
    align-items: center;
    padding: 4px 10px;
    margin: 2px 4px 2px 0;
    background: #f7fafc;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    font-size: 11px;
}
.gw-portal-check input { margin-right: 6px; }
.gw-save-btn {
    background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
    color: #fff;
    border: none;
    padding: 8px 30px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
}
.gw-save-btn:hover { opacity: 0.9; color: #fff; }
.gw-test-btn {
    background: #f59e0b;
    color: #fff;
    border: none;
    padding: 6px 20px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
}
.gw-test-btn:hover { background: #d97706; color: #fff; }
.webhook-gw-select {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    background: linear-gradient(135deg, #f0f4f8, #e2e8f0);
    border-radius: 8px;
    margin-bottom: 16px;
}
.webhook-gw-select label {
    font-size: 12px;
    font-weight: 700;
    color: #1e3c72;
    margin-bottom: 0;
    white-space: nowrap;
}
.webhook-gw-select select {
    font-size: 12px;
    height: 34px;
    border-radius: 6px;
    min-width: 220px;
}
.webhook-gw-select .gw-info {
    font-size: 11px;
    color: #718096;
    margin-left: auto;
}
</style>

<div class="card" style="border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.06);">
    <div class="card-header p-0" style="background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); border-radius: 12px 12px 0 0;">
        <div class="d-flex align-items-center px-3 py-2">
            <h5 class="mb-0 text-white" style="font-size: 15px; font-weight: 600;">
                <i class="fas fa-credit-card mr-2"></i>Payment Gateways
            </h5>
            <span class="ml-auto text-white-50" style="font-size: 11px;">Setting Up Payment Gateways</span>
        </div>
        <div class="px-2 pt-2" style="border-radius: 0; background: #edf2f7;">
            <ul class="nav gw-tabs" id="paymentTabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link gw-tab-ssl {{ $activeTab == 'sslcommerz' ? 'active' : '' }}" data-toggle="tab" href="#tabSSL" role="tab">
                        <img src="/images/gateways/sslcommerz.png" alt="SSLCommerz" style="height:26px;vertical-align:middle;background:#fff;border-radius:4px;padding:2px 4px;">
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link gw-tab-bkash {{ $activeTab == 'bkash' ? 'active' : '' }}" data-toggle="tab" href="#tabBkash" role="tab">
                        <img src="/images/gateways/bkash.png" alt="bKash" style="height:26px;vertical-align:middle;background:#fff;border-radius:4px;padding:2px 4px;">
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link gw-tab-nagad {{ $activeTab == 'nagad' ? 'active' : '' }}" data-toggle="tab" href="#tabNagad" role="tab">
                        <img src="/images/gateways/nagad.png" alt="Nagad" style="height:36px;vertical-align:middle;background:#fff;border-radius:4px;padding:2px 4px;">
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link gw-tab-webhook {{ $activeTab == 'webhook' ? 'active' : '' }}" data-toggle="tab" href="#tabWebhook" role="tab">
                        <i class="fas fa-link"></i>Webhook / IPN
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link gw-tab-manual {{ $activeTab == 'manual' ? 'active' : '' }}" data-toggle="tab" href="#tabManual" role="tab">
                        <i class="fas fa-mobile-alt"></i>Manual Payment
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <div class="card-body p-3">
        <div class="tab-content">

            <div class="tab-pane fade {{ $activeTab == 'sslcommerz' ? 'show active' : '' }}" id="tabSSL" role="tabpanel">
                <form action="{{ route('admin.settings.payment.sslcommerz.update') }}" method="POST">
                    @csrf
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="gw-section-title"><i class="fas fa-cog mr-1"></i>Gateway Credentials</div>
                            <div class="gw-form-row">
                                <label>Mode <span class="text-danger">*</span></label>
                                <div class="d-flex align-items-center">
                                    <div class="custom-control custom-radio mr-3">
                                        <input type="radio" id="sslSandbox" name="sslcommerz_sandbox" value="1" class="custom-control-input" {{ ($sslSandbox ?? '1') == '1' ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="sslSandbox" style="font-size:12px">Sandbox</label>
                                    </div>
                                    <div class="custom-control custom-radio">
                                        <input type="radio" id="sslLive" name="sslcommerz_sandbox" value="0" class="custom-control-input" {{ ($sslSandbox ?? '1') == '0' ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="sslLive" style="font-size:12px">Live</label>
                                    </div>
                                </div>
                            </div>
                            <div class="gw-form-row">
                                <label>Store ID <span class="text-danger">*</span></label>
                                <input type="text" name="sslcommerz_store_id" class="form-control" value="{{ $sslStoreId }}" placeholder="Store ID" required>
                            </div>
                            <div class="gw-form-row">
                                <label>Store Password <span class="text-danger">*</span></label>
                                <input type="password" name="sslcommerz_store_password" class="form-control" value="{{ $sslStorePassword }}" placeholder="Store Password" required>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="gw-section-title"><i class="fas fa-toggle-on mr-1"></i>Status & Validity</div>
                            <div class="gw-form-row">
                                <label>Status</label>
                                <div class="d-flex align-items-center">
                                    <div class="custom-control custom-radio mr-3">
                                        <input type="radio" id="sslEn" name="sslcommerz_enabled" value="1" class="custom-control-input" {{ ($sslEnabled ?? '0') == '1' ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="sslEn" style="font-size:12px">Enabled</label>
                                    </div>
                                    <div class="custom-control custom-radio">
                                        <input type="radio" id="sslDis" name="sslcommerz_enabled" value="0" class="custom-control-input" {{ ($sslEnabled ?? '0') == '0' ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="sslDis" style="font-size:12px">Disabled</label>
                                    </div>
                                    <span class="ml-3 gw-status-badge {{ ($sslEnabled ?? '0') == '1' ? 'gw-status-active' : 'gw-status-inactive' }}">
                                        <i class="fas fa-circle mr-1" style="font-size:6px"></i>{{ ($sslEnabled ?? '0') == '1' ? 'Active' : 'Inactive' }}
                                    </span>
                                </div>
                            </div>
                            <div class="gw-form-row">
                                <label>Activation Date <span class="text-danger">*</span></label>
                                <input type="date" name="sslcommerz_activation_date" class="form-control" value="{{ $sslActivationDate }}" required>
                            </div>
                            <div class="gw-form-row">
                                <label>Expiry Date</label>
                                <input type="date" name="sslcommerz_expiry_date" class="form-control" value="{{ $sslExpiryDate }}">
                            </div>
                        </div>
                    </div>

                    <div class="d-flex align-items-center mt-3 pt-3" style="border-top: 1px solid #e2e8f0;">
                        <button type="submit" class="gw-save-btn"><i class="fas fa-save mr-2"></i>Save SSLCommerz</button>
                        <button type="button" class="gw-test-btn ml-2" onclick="document.getElementById('sslTestForm').submit()"><i class="fas fa-plug mr-1"></i>Test Connection</button>
                    </div>
                </form>
                <form id="sslTestForm" action="{{ route('admin.settings.payment.sslcommerz.test') }}" method="POST" style="display:none">@csrf</form>
            </div>

            <div class="tab-pane fade {{ $activeTab == 'bkash' ? 'show active' : '' }}" id="tabBkash" role="tabpanel">
                <form action="{{ route('admin.settings.payment.api.update') }}" method="POST">
                    @csrf
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="gw-section-title"><i class="fas fa-cog mr-1"></i>Gateway Credentials</div>
                            <div class="gw-form-row">
                                <label>Gateway Type <span class="text-danger">*</span></label>
                                <select name="bkash_gateway_type" class="form-control" required>
                                    <option value="">Select</option>
                                    <option value="platform_connector" {{ ($bkashGatewayType ?? '') == 'platform_connector' ? 'selected' : '' }}>Platform Connector</option>
                                    <option value="checkout_popup" {{ ($bkashGatewayType ?? '') == 'checkout_popup' ? 'selected' : '' }}>Checkout API (Popup)</option>
                                    <option value="tokenized_web" {{ ($bkashGatewayType ?? '') == 'tokenized_web' ? 'selected' : '' }}>Tokenized API (Web/URL)</option>
                                </select>
                            </div>
                            <div class="gw-form-row">
                                <label>Mode <span class="text-danger">*</span></label>
                                <div class="d-flex align-items-center">
                                    <div class="custom-control custom-radio mr-3">
                                        <input type="radio" id="bkSandbox" name="bkash_sandbox" value="1" class="custom-control-input" {{ ($bkashSandbox ?? '1') == '1' ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="bkSandbox" style="font-size:12px">Sandbox</label>
                                    </div>
                                    <div class="custom-control custom-radio">
                                        <input type="radio" id="bkLive" name="bkash_sandbox" value="0" class="custom-control-input" {{ ($bkashSandbox ?? '1') == '0' ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="bkLive" style="font-size:12px">Live</label>
                                    </div>
                                </div>
                            </div>
                            <div class="gw-form-row">
                                <label>App Key <span class="text-danger">*</span></label>
                                <input type="text" name="bkash_app_key" class="form-control" value="{{ $bkashAppKey }}" placeholder="X-APP-Key" required>
                            </div>
                            <div class="gw-form-row">
                                <label>App Secret <span class="text-danger">*</span></label>
                                <input type="text" name="bkash_app_secret" class="form-control" value="{{ $bkashAppSecret }}" placeholder="Secret Key" required>
                            </div>
                            <div class="gw-form-row">
                                <label>Username <span class="text-danger">*</span></label>
                                <input type="text" name="bkash_username" class="form-control" value="{{ $bkashUsername }}" placeholder="Username" required>
                            </div>
                            <div class="gw-form-row">
                                <label>Password <span class="text-danger">*</span></label>
                                <input type="password" name="bkash_password" class="form-control" value="{{ $bkashPassword }}" placeholder="Password" required>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="gw-section-title"><i class="fas fa-toggle-on mr-1"></i>Status & Validity</div>
                            <div class="gw-form-row">
                                <label>Status</label>
                                <div class="d-flex align-items-center">
                                    <div class="custom-control custom-radio mr-3">
                                        <input type="radio" id="bkEn" name="bkash_enabled" value="1" class="custom-control-input" {{ ($bkashEnabled ?? '0') == '1' ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="bkEn" style="font-size:12px">Enabled</label>
                                    </div>
                                    <div class="custom-control custom-radio">
                                        <input type="radio" id="bkDis" name="bkash_enabled" value="0" class="custom-control-input" {{ ($bkashEnabled ?? '0') == '0' ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="bkDis" style="font-size:12px">Disabled</label>
                                    </div>
                                    <span class="ml-3 gw-status-badge {{ ($bkashEnabled ?? '0') == '1' ? 'gw-status-active' : 'gw-status-inactive' }}">
                                        <i class="fas fa-circle mr-1" style="font-size:6px"></i>{{ ($bkashEnabled ?? '0') == '1' ? 'Active' : 'Inactive' }}
                                    </span>
                                </div>
                            </div>
                            <div class="gw-form-row">
                                <label>Activation Date <span class="text-danger">*</span></label>
                                <input type="date" name="bkash_activation_date" class="form-control" value="{{ $bkashActivationDate }}" required>
                            </div>
                            <div class="gw-form-row">
                                <label>Expiry Date</label>
                                <input type="date" name="bkash_expiry_date" class="form-control" value="{{ $bkashExpiryDate }}">
                            </div>
                        </div>
                    </div>

                    <div class="d-flex align-items-center mt-3 pt-3" style="border-top: 1px solid #e2e8f0;">
                        <button type="submit" class="gw-save-btn" style="background: linear-gradient(135deg, #e2136e, #c1105c);"><i class="fas fa-save mr-2"></i>Save bKash</button>
                        <button type="button" class="gw-test-btn ml-2" onclick="document.getElementById('bkashTestForm').submit()"><i class="fas fa-plug mr-1"></i>Test Connection</button>
                    </div>
                </form>
                <form id="bkashTestForm" action="{{ route('admin.settings.payment.bkash.test') }}" method="POST" style="display:none">@csrf</form>
            </div>

            <div class="tab-pane fade {{ $activeTab == 'nagad' ? 'show active' : '' }}" id="tabNagad" role="tabpanel">
                <form action="{{ route('admin.settings.payment.nagad.update') }}" method="POST">
                    @csrf
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="gw-section-title"><i class="fas fa-cog mr-1"></i>Gateway Credentials</div>
                            <div class="gw-form-row">
                                <label>Mode <span class="text-danger">*</span></label>
                                <div class="d-flex align-items-center">
                                    <div class="custom-control custom-radio mr-3">
                                        <input type="radio" id="ngSandbox" name="nagad_sandbox" value="1" class="custom-control-input" {{ ($nagadSandbox ?? '1') == '1' ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="ngSandbox" style="font-size:12px">Sandbox</label>
                                    </div>
                                    <div class="custom-control custom-radio">
                                        <input type="radio" id="ngLive" name="nagad_sandbox" value="0" class="custom-control-input" {{ ($nagadSandbox ?? '1') == '0' ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="ngLive" style="font-size:12px">Live</label>
                                    </div>
                                </div>
                            </div>
                            <div class="gw-form-row">
                                <label>Merchant ID <span class="text-danger">*</span></label>
                                <input type="text" name="nagad_merchant_id" class="form-control" value="{{ $nagadMerchantId }}" placeholder="Nagad Merchant ID" required>
                            </div>
                            <div class="gw-form-row">
                                <label>Merchant Phone <span class="text-danger">*</span></label>
                                <input type="text" name="nagad_merchant_phone" class="form-control" value="{{ $nagadMerchantPhone }}" placeholder="01XXXXXXXXX" required>
                            </div>
                            <div class="gw-form-row" style="flex-direction: column; align-items: stretch;">
                                <label class="mb-1">PG Public Key <span class="text-danger">*</span> <small class="text-muted">(Nagad provides this)</small></label>
                                <textarea name="nagad_pg_public_key" class="form-control" rows="3" style="font-size: 11px; font-family: monospace; height: auto;" placeholder="Nagad's PG Public Key (RSA)" required>{{ $nagadPgPublicKey }}</textarea>
                            </div>
                            <div class="gw-form-row" style="flex-direction: column; align-items: stretch;">
                                <label class="mb-1">Merchant Private Key <span class="text-danger">*</span> <small class="text-muted">(Your private key)</small></label>
                                <textarea name="nagad_merchant_private_key" class="form-control" rows="3" style="font-size: 11px; font-family: monospace; height: auto;" placeholder="Your RSA Private Key" required>{{ $nagadMerchantPrivateKey }}</textarea>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="gw-section-title"><i class="fas fa-toggle-on mr-1"></i>Status & Validity</div>
                            <div class="gw-form-row">
                                <label>Status</label>
                                <div class="d-flex align-items-center">
                                    <div class="custom-control custom-radio mr-3">
                                        <input type="radio" id="ngEn" name="nagad_enabled" value="1" class="custom-control-input" {{ ($nagadEnabled ?? '0') == '1' ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="ngEn" style="font-size:12px">Enabled</label>
                                    </div>
                                    <div class="custom-control custom-radio">
                                        <input type="radio" id="ngDis" name="nagad_enabled" value="0" class="custom-control-input" {{ ($nagadEnabled ?? '0') == '0' ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="ngDis" style="font-size:12px">Disabled</label>
                                    </div>
                                    <span class="ml-3 gw-status-badge {{ ($nagadEnabled ?? '0') == '1' ? 'gw-status-active' : 'gw-status-inactive' }}">
                                        <i class="fas fa-circle mr-1" style="font-size:6px"></i>{{ ($nagadEnabled ?? '0') == '1' ? 'Active' : 'Inactive' }}
                                    </span>
                                </div>
                            </div>
                            <div class="gw-form-row">
                                <label>Activation Date <span class="text-danger">*</span></label>
                                <input type="date" name="nagad_activation_date" class="form-control" value="{{ $nagadActivationDate }}" required>
                            </div>
                            <div class="gw-form-row">
                                <label>Expiry Date</label>
                                <input type="date" name="nagad_expiry_date" class="form-control" value="{{ $nagadExpiryDate }}">
                            </div>
                            <div class="mt-3 p-2" style="background: #fff7ed; border-radius: 8px; border: 1px solid #fed7aa;">
                                <div style="font-size: 11px; color: #9a3412; line-height: 1.6;">
                                    <i class="fas fa-info-circle mr-1"></i><strong>Nagad Payment Gateway API</strong><br>
                                    <i class="fas fa-check text-success mr-1" style="font-size:9px"></i> RSA encrypted checkout (official API)<br>
                                    <i class="fas fa-check text-success mr-1" style="font-size:9px"></i> Sandbox URL: sandbox.mynagad.com<br>
                                    <i class="fas fa-check text-success mr-1" style="font-size:9px"></i> Live URL: api.mynagad.com<br>
                                    <i class="fas fa-check text-success mr-1" style="font-size:9px"></i> Callback URL: <code style="font-size:10px">{{ route('payment.nagad.callback') }}</code>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex align-items-center mt-3 pt-3" style="border-top: 1px solid #e2e8f0;">
                        <button type="submit" class="gw-save-btn" style="background: linear-gradient(135deg, #f6921e, #e07b15);"><i class="fas fa-save mr-2"></i>Save Nagad</button>
                        <button type="button" class="gw-test-btn ml-2" onclick="document.getElementById('nagadTestForm').submit()"><i class="fas fa-plug mr-1"></i>Test Connection</button>
                    </div>
                </form>
                <form id="nagadTestForm" action="{{ route('admin.settings.payment.nagad.test') }}" method="POST" style="display:none">@csrf</form>
            </div>

            <div class="tab-pane fade {{ $activeTab == 'webhook' ? 'show active' : '' }}" id="tabWebhook" role="tabpanel">
                <div class="row mb-3">
                    <div class="col-12">
                        <div class="p-3" style="background: linear-gradient(135deg, #e8f5e9 0%, #f1f8e9 100%); border-radius: 10px; border-left: 4px solid #43a047;">
                            <div style="font-size: 13px; font-weight: 700; color: #2e7d32; margin-bottom: 6px;">
                                <i class="fas fa-magic mr-1"></i>Auto Payment Verification System
                            </div>
                            <div style="font-size: 11px; color: #555; line-height: 1.8;">
                                Client sends payment via bKash / Nagad / Rocket &mdash; enters <strong>PPPoE Username</strong> in the Reference/Note field &rarr;
                                System automatically matches the client &rarr; Bill is verified &amp; adjusted &rarr; SMS confirmation is sent &rarr; Inactive client is auto-reconnected
                            </div>
                        </div>
                    </div>
                </div>

                <div class="webhook-gw-select">
                    <i class="fas fa-arrow-circle-right" style="color: #1e3c72; font-size: 18px;"></i>
                    <label>Payment Gateway</label>
                    <select id="webhookGatewaySelect" class="form-control" onchange="switchWebhookGateway(this.value)">
                        <option value="">Select</option>
                        <option value="bkash">bKash</option>
                        <option value="nagad">Nagad</option>
                        <option value="rocket">Rocket</option>
                    </select>
                    <span class="gw-info"><i class="fas fa-info-circle mr-1"></i>Enable webhook to auto-verify payments</span>
                </div>

                <div id="webhookFormArea" style="display:none;">
                    <form action="{{ route('admin.settings.payment.webhook.update') }}" method="POST" id="webhookForm">
                        @csrf
                        <input type="hidden" name="gateway" id="webhookGatewayInput" value="">
                        <div class="row">
                            <div class="col-lg-7">
                                <div class="gw-section-title"><i class="fas fa-cog mr-1"></i><span id="webhookGwTitle">Gateway</span> Webhook Settings</div>
                                <div class="gw-form-row">
                                    <label>Webhook Secret Key <small class="text-muted">(optional security)</small></label>
                                    <input type="text" name="webhook_secret" id="wh_webhook_secret" class="form-control" placeholder="Enter X-Webhook-Secret header value">
                                </div>
                                <div class="gw-form-row">
                                    <label>Status</label>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="wh_webhook_enabled" name="webhook_enabled">
                                        <label class="custom-control-label" for="wh_webhook_enabled" style="font-size:12px">Enable Auto-Verify Webhook</label>
                                    </div>
                                </div>
                                <input type="hidden" name="webhook_url" id="wh_webhook_url" value="">
                                <input type="hidden" name="ipn_url" id="wh_ipn_url" value="">
                            </div>
                            <div class="col-lg-5">
                                <div class="gw-section-title"><i class="fas fa-link mr-1"></i>Your Webhook URL</div>
                                <div class="p-2 mb-2" style="background: #eef2ff; border-radius: 8px; border: 1px dashed #6366f1;">
                                    <small class="text-muted d-block mb-1" style="font-size:10px">POST endpoint (set this in your gateway dashboard):</small>
                                    <code id="webhookUrlDisplay" style="font-size: 11px; word-break: break-all; color: #4338ca; font-weight: 600;"></code>
                                    <button type="button" class="btn btn-sm btn-outline-primary mt-1" style="font-size:10px; padding: 2px 8px;" onclick="copyWebhookUrl()">
                                        <i class="fas fa-copy mr-1"></i>Copy
                                    </button>
                                </div>

                                <div class="gw-section-title mt-3"><i class="fas fa-paper-plane mr-1"></i>Expected POST Data</div>
                                <div class="p-2" style="background: #f8fafc; border-radius: 8px; font-size: 10px; font-family: monospace; line-height: 1.8;">
                                    <div id="expectedPayload"></div>
                                </div>

                                <div class="gw-section-title mt-3"><i class="fas fa-info-circle mr-1"></i>How It Works</div>
                                <div class="p-2" style="background: #fffbeb; border-radius: 8px; font-size: 10px; color: #92400e; line-height: 1.6;">
                                    <div><i class="fas fa-check-circle text-success mr-1"></i>Client sends money to your merchant number</div>
                                    <div><i class="fas fa-check-circle text-success mr-1"></i>Reference e PPPoE Username dei</div>
                                    <div><i class="fas fa-check-circle text-success mr-1"></i>Gateway webhook POST pathay ei URL e</div>
                                    <div><i class="fas fa-check-circle text-success mr-1"></i>System auto match + bill verify</div>
                                    <div><i class="fas fa-check-circle text-success mr-1"></i>SMS confirmation + auto-reconnect</div>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex align-items-center mt-3 pt-3" style="border-top: 1px solid #e2e8f0;">
                            <button type="submit" class="gw-save-btn"><i class="fas fa-save mr-2"></i>Save Webhook Settings</button>
                            <a href="{{ route('admin.billing.online-payments') }}?tab=webhook" class="btn btn-sm btn-outline-info ml-3" style="font-size: 11px;">
                                <i class="fas fa-list-alt mr-1"></i>View Webhook Logs
                            </a>
                        </div>
                    </form>
                </div>
                <div id="webhookPlaceholder">
                    <div class="text-center py-4 text-muted">
                        <i class="fas fa-satellite-dish fa-3x mb-3" style="opacity: 0.3;"></i>
                        <p style="font-size: 13px;">Select a payment gateway to configure auto-payment verification</p>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade {{ $activeTab == 'manual' ? 'show active' : '' }}" id="tabManual" role="tabpanel">
                <form action="{{ route('admin.settings.payment.update') }}" method="POST">
                    @csrf
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="gw-section-title"><i class="fas fa-mobile-alt mr-1"></i>Mobile Banking Merchant Numbers</div>
                            <p style="font-size: 11px; color: #718096; margin-bottom: 12px;">Clients will see these numbers when submitting manual payments</p>
                            <div class="gw-form-row">
                                <label><i class="fas fa-circle text-danger mr-1" style="font-size:8px"></i>bKash Number</label>
                                <input type="text" name="bkash_merchant_number" class="form-control" value="{{ $bkashNumber }}" placeholder="01XXXXXXXXX">
                            </div>
                            <div class="gw-form-row">
                                <label><i class="fas fa-circle text-warning mr-1" style="font-size:8px"></i>Nagad Number</label>
                                <input type="text" name="nagad_merchant_number" class="form-control" value="{{ $nagadNumber }}" placeholder="01XXXXXXXXX">
                            </div>
                            <div class="gw-form-row">
                                <label><i class="fas fa-circle mr-1" style="font-size:8px; color:#8b5cf6"></i>Rocket Number</label>
                                <input type="text" name="rocket_merchant_number" class="form-control" value="{{ $rocketNumber }}" placeholder="01XXXXXXXXX">
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="gw-section-title"><i class="fas fa-link mr-1"></i>Quick Links</div>
                            <a href="{{ route('admin.payments.pending') }}" class="d-flex align-items-center p-2 mb-2" style="background: #fef3c7; border-radius: 8px; text-decoration: none; color: #92400e; font-size: 12px; font-weight: 600;">
                                <i class="fas fa-clock mr-2"></i>View Pending Payments
                                <i class="fas fa-chevron-right ml-auto"></i>
                            </a>
                            <a href="{{ route('admin.payments.index') }}" class="d-flex align-items-center p-2 mb-2" style="background: #dbeafe; border-radius: 8px; text-decoration: none; color: #1e40af; font-size: 12px; font-weight: 600;">
                                <i class="fas fa-list mr-2"></i>All Payments
                                <i class="fas fa-chevron-right ml-auto"></i>
                            </a>
                            <a href="{{ route('admin.billing.online-payments') }}" class="d-flex align-items-center p-2" style="background: #d1fae5; border-radius: 8px; text-decoration: none; color: #065f46; font-size: 12px; font-weight: 600;">
                                <i class="fas fa-globe mr-2"></i>Online Payments
                                <i class="fas fa-chevron-right ml-auto"></i>
                            </a>
                        </div>
                    </div>
                    <div class="d-flex align-items-center mt-3 pt-3" style="border-top: 1px solid #e2e8f0;">
                        <button type="submit" class="gw-save-btn"><i class="fas fa-save mr-2"></i>Save Manual Settings</button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</div>

<script>
var webhookData = @json($webhookSettings);
var savedWebhookGateway = '{{ old("gateway", "") }}';
var webhookUrls = {
    bkash: '{{ route("webhook.payment.bkash") }}',
    nagad: '{{ route("webhook.payment.nagad") }}',
    rocket: '{{ route("webhook.payment.rocket") }}'
};
var payloadExamples = {
    bkash: '{\n  "trxID": "BKS20240101XXXX",\n  "amount": "850.00",\n  "customerMsisdn": "01XXXXXXXXX",\n  "payerReference": "<span style=\'color:#e53e3e;font-weight:700\'>PPPoE_Username</span>"\n}',
    nagad: '{\n  "trx_id": "NGD20240101XXXX",\n  "amount": "850.00",\n  "customer_account": "01XXXXXXXXX",\n  "order_id": "<span style=\'color:#e53e3e;font-weight:700\'>PPPoE_Username</span>"\n}',
    rocket: '{\n  "trx_id": "RKT20240101XXXX",\n  "amount": "850.00",\n  "sender": "01XXXXXXXXX",\n  "reference": "<span style=\'color:#e53e3e;font-weight:700\'>PPPoE_Username</span>"\n}'
};

function switchWebhookGateway(gw) {
    if (!gw) {
        document.getElementById('webhookFormArea').style.display = 'none';
        document.getElementById('webhookPlaceholder').style.display = 'block';
        return;
    }
    document.getElementById('webhookFormArea').style.display = 'block';
    document.getElementById('webhookPlaceholder').style.display = 'none';
    document.getElementById('webhookGatewayInput').value = gw;
    document.getElementById('webhookGatewaySelect').value = gw;
    var titles = {bkash:'bKash',nagad:'Nagad',rocket:'Rocket'};
    document.getElementById('webhookGwTitle').textContent = titles[gw] || gw;
    var d = webhookData[gw] || {};
    document.getElementById('wh_webhook_url').value = webhookUrls[gw] || '';
    document.getElementById('wh_ipn_url').value = d.ipn_url || '';
    document.getElementById('wh_webhook_secret').value = d.webhook_secret || '';
    document.getElementById('wh_webhook_enabled').checked = d.webhook_enabled == '1';
    document.getElementById('webhookUrlDisplay').textContent = webhookUrls[gw] || '';
    document.getElementById('expectedPayload').innerHTML = (payloadExamples[gw] || '').replace(/\n/g, '<br>');
}

function copyWebhookUrl() {
    var url = document.getElementById('webhookUrlDisplay').textContent;
    if (navigator.clipboard) {
        navigator.clipboard.writeText(url);
    } else {
        var t = document.createElement('textarea');
        t.value = url;
        document.body.appendChild(t);
        t.select();
        document.execCommand('copy');
        document.body.removeChild(t);
    }
    var btn = event.target.closest('button');
    var orig = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-check mr-1"></i>Copied!';
    setTimeout(function() { btn.innerHTML = orig; }, 2000);
}

document.addEventListener('DOMContentLoaded', function() {
    if (savedWebhookGateway) {
        switchWebhookGateway(savedWebhookGateway);
    }
});
</script>
@endsection
