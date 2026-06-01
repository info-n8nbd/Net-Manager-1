@extends('layouts.admin')

@section('title', 'Email Settings')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.email.index') }}">Email System</a></li>
    <li class="breadcrumb-item active">SMTP Settings</li>
@endsection

@section('content')
<div class="container-fluid py-2">

    <div class="d-flex justify-content-end mb-3">
        <a href="{{ route('admin.email.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i>Back to Dashboard
        </a>
    </div>

    <div class="row">
        <div class="col-lg-8">

            {{-- SMTP Config --}}
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0"><i class="fas fa-server me-2"></i>SMTP Configuration</h6>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.email.settings.save') }}">
                        @csrf

                        <div class="d-flex align-items-center mb-4 p-3 bg-light rounded">
                            <div class="form-check form-switch me-3">
                                <input class="form-check-input" type="checkbox" name="is_active" id="is_active"
                                       {{ $settings->is_active ? 'checked' : '' }} style="width:2.5rem;height:1.3rem;">
                            </div>
                            <label for="is_active" class="form-check-label fw-semibold fs-6">
                                Email System {{ $settings->is_active ? '✅ Active' : '❌ Inactive' }}
                            </label>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-8">
                                <label class="form-label fw-semibold">SMTP Host <span class="text-danger">*</span></label>
                                <input type="text" name="host" class="form-control" value="{{ old('host', $settings->host ?? 'smtp.gmail.com') }}" placeholder="smtp.gmail.com" required>
                                <div class="form-text">Gmail: smtp.gmail.com | Outlook: smtp-mail.outlook.com</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Port <span class="text-danger">*</span></label>
                                <input type="number" name="port" class="form-control" value="{{ old('port', $settings->port ?? 587) }}" required>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Username (Email) <span class="text-danger">*</span></label>
                                <input type="text" name="username" class="form-control" value="{{ old('username', $settings->username ?? '') }}" placeholder="your@gmail.com" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Password / App Password <span class="text-danger">*</span></label>
                                <input type="password" name="password" class="form-control" placeholder="{{ $settings->password ? '••••••• (type to change)' : 'App Password' }}">
                                <div class="form-text">Use App Password for Gmail.</div>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Encryption</label>
                                <select name="encryption" class="form-select">
                                    <option value="tls" @selected(($settings->encryption ?? 'tls') === 'tls')>TLS (Port 587)</option>
                                    <option value="ssl" @selected(($settings->encryption ?? '') === 'ssl')>SSL (Port 465)</option>
                                    <option value="none" @selected(($settings->encryption ?? '') === 'none')>None</option>
                                </select>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label fw-semibold">From Email <span class="text-danger">*</span></label>
                                <input type="email" name="from_address" class="form-control" value="{{ old('from_address', $settings->from_address ?? 'noreply@isp.com') }}" required>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold">From Name</label>
                            <input type="text" name="from_name" class="form-control" value="{{ old('from_name', $settings->from_name ?? 'SOFTYNEX') }}">
                        </div>

                        <hr class="my-4">

                        <h6 class="fw-bold mb-3"><i class="fas fa-robot me-2 text-success"></i>Auto-Reminder Settings</h6>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <div class="card border p-3 h-100">
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" name="auto_bill_reminder" id="auto_bill"
                                               {{ ($settings->auto_bill_reminder ?? false) ? 'checked' : '' }}>
                                        <label class="form-check-label fw-semibold" for="auto_bill">
                                            <i class="fas fa-file-invoice-dollar text-warning me-1"></i>Auto Bill Reminder
                                        </label>
                                    </div>
                                    <p class="text-muted small mb-2">Sends daily auto email for unpaid bills every morning.</p>
                                    <label class="form-label small">How many days before due date?</label>
                                    <div class="input-group input-group-sm">
                                        <input type="number" name="bill_reminder_days_before" class="form-control" value="{{ $settings->bill_reminder_days_before ?? 3 }}" min="1" max="30">
                                        <span class="input-group-text">days before</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card border p-3 h-100">
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" name="auto_expiry_warning" id="auto_expiry"
                                               {{ ($settings->auto_expiry_warning ?? false) ? 'checked' : '' }}>
                                        <label class="form-check-label fw-semibold" for="auto_expiry">
                                            <i class="fas fa-clock text-info me-1"></i>Auto Expiry Warning
                                        </label>
                                    </div>
                                    <p class="text-muted small mb-2">Sends auto warning email before package expiry.</p>
                                    <label class="form-label small">How many days before expiry?</label>
                                    <div class="input-group input-group-sm">
                                        <input type="number" name="expiry_warning_days_before" class="form-control" value="{{ $settings->expiry_warning_days_before ?? 3 }}" min="1" max="30">
                                        <span class="input-group-text">days before</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card border p-3 mb-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="auto_payment_confirmation" id="auto_payment"
                                       {{ ($settings->auto_payment_confirmation ?? true) ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="auto_payment">
                                    <i class="fas fa-check-circle text-success me-1"></i>Auto Payment Confirmation
                                    <span class="text-muted fw-normal small ms-2">— Automatically sends a receipt email after payment</span>
                                </label>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>Save Settings
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Test Email --}}
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="fas fa-vial me-2 text-warning"></i>Send Test Email</h6>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.email.test') }}">
                        @csrf
                        <div class="input-group">
                            <input type="email" name="test_email" class="form-control" placeholder="test@example.com" required>
                            <button type="submit" class="btn btn-warning">
                                <i class="fas fa-paper-plane me-1"></i>Test Send
                            </button>
                        </div>
                        <div class="form-text">Send this email to verify that your settings are correct.</div>
                    </form>
                </div>
            </div>

        </div>

        {{-- Help Sidebar --}}
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 mb-3">
                <div class="card-header bg-warning text-dark">
                    <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Gmail Setup Guide</h6>
                </div>
                <div class="card-body small">
                    <ol class="ps-3 mb-0">
                        <li class="mb-2">Log in to your Gmail account</li>
                        <li class="mb-2">Enable <strong>2-Step Verification</strong></li>
                        <li class="mb-2">Go to <strong>App Passwords</strong>: <br><code>myaccount.google.com → Security → App Passwords</code></li>
                        <li class="mb-2">Select <strong>Mail</strong> + <strong>Windows Computer</strong> and click <strong>Generate</strong></li>
                        <li class="mb-2">Paste the 16-digit password here</li>
                    </ol>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-3">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0"><i class="fas fa-server me-2"></i>Popular SMTP Settings</h6>
                </div>
                <div class="card-body small">
                    <table class="table table-sm table-borderless mb-0">
                        <tr class="border-bottom"><td class="fw-bold">Gmail</td><td>smtp.gmail.com:587 TLS</td></tr>
                        <tr class="border-bottom"><td class="fw-bold">Outlook</td><td>smtp-mail.outlook.com:587</td></tr>
                        <tr class="border-bottom"><td class="fw-bold">Yahoo</td><td>smtp.mail.yahoo.com:587</td></tr>
                        <tr><td class="fw-bold">Custom</td><td>mail.yourdomain.com:587</td></tr>
                    </table>
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-header bg-success text-white">
                    <h6 class="mb-0"><i class="fas fa-envelope me-2"></i>Email Types</h6>
                </div>
                <div class="card-body small">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2"><span class="badge bg-warning text-dark me-1">Bill Reminder</span> — When there is an unpaid bill</li>
                        <li class="mb-2"><span class="badge bg-info me-1">Expiry Warning</span> — Before package expiry</li>
                        <li class="mb-2"><span class="badge bg-success me-1">Payment</span> — Receipt after payment</li>
                        <li class="mb-2"><span class="badge bg-danger me-1">Suspension</span> — On connection suspension</li>
                        <li><span class="badge bg-primary me-1">Welcome</span> — On new connection</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
