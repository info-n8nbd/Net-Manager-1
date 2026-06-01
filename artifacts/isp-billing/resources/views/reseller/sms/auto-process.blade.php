@extends('layouts.reseller')

@section('title', 'SMS Auto Process')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('reseller.sms.settings') }}">SMS</a></li>
<li class="breadcrumb-item active">Auto Process</li>
@endsection

@section('content')
@if(session('success'))
<div class="alert alert-success"><i class="fas fa-check-circle mr-2"></i>{{ session('success') }}</div>
@endif

<div class="alert alert-info">
    <i class="fas fa-info-circle mr-2"></i>
    <strong>Auto Process:</strong> When the settings below are enabled, SMS will be sent automatically when the corresponding event occurs.
    To send SMS, enable SMS and configure the API from <a href="{{ route('reseller.sms.settings') }}" class="alert-link">SMS Settings</a>.
</div>

<form action="{{ route('reseller.sms.auto-process.save') }}" method="POST">
    @csrf
    <div class="row">
        <div class="col-md-6">
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-bolt mr-2"></i>Connection & Payment</h3>
                </div>
                <div class="card-body">
                    <div class="custom-control custom-switch mb-3">
                        <input type="checkbox" class="custom-control-input" id="autoLineActivated" name="auto_line_activated" value="1" {{ ($autoSettings['line_activated'] ?? false) ? 'checked' : '' }}>
                        <label class="custom-control-label" for="autoLineActivated">New Connection SMS</label>
                        <p class="text-muted small mb-0">Sends a welcome SMS when a new client is connected.</p>
                    </div>

                    <div class="custom-control custom-switch mb-3">
                        <input type="checkbox" class="custom-control-input" id="autoPayment" name="auto_payment_received" value="1" {{ ($autoSettings['payment_received'] ?? false) ? 'checked' : '' }}>
                        <label class="custom-control-label" for="autoPayment">Payment Confirmation SMS</label>
                        <p class="text-muted small mb-0">Sends a confirmation SMS to the client after receiving payment.</p>
                    </div>

                    <div class="custom-control custom-switch mb-3">
                        <input type="checkbox" class="custom-control-input" id="autoLineDeactivated" name="auto_line_deactivated" value="1" {{ ($autoSettings['line_deactivated'] ?? false) ? 'checked' : '' }}>
                        <label class="custom-control-label" for="autoLineDeactivated">Line Deactivation SMS</label>
                        <p class="text-muted small mb-0">Sends a notification SMS when a client's line is deactivated.</p>
                    </div>
                </div>
            </div>

            <div class="card card-success card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-file-invoice-dollar mr-2"></i>Billing</h3>
                </div>
                <div class="card-body">
                    <div class="custom-control custom-switch mb-3">
                        <input type="checkbox" class="custom-control-input" id="autoBillGen" name="auto_bill_generation" value="1" {{ ($autoSettings['bill_generation'] ?? false) ? 'checked' : '' }}>
                        <label class="custom-control-label" for="autoBillGen">Bill Generation SMS</label>
                        <p class="text-muted small mb-0">Sends an SMS to the client when a monthly bill is generated.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card card-warning card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-bell mr-2"></i>Due Reminders</h3>
                </div>
                <div class="card-body">
                    <p class="text-muted small">A daily Cron Job must be running to send due reminder SMS.</p>

                    <div class="custom-control custom-switch mb-3">
                        <input type="checkbox" class="custom-control-input" id="reminder3day" name="auto_reminder_3day" value="1" {{ ($autoSettings['reminder_3day'] ?? false) ? 'checked' : '' }}>
                        <label class="custom-control-label" for="reminder3day">1st Reminder (3 days before due date)</label>
                    </div>

                    <div class="custom-control custom-switch mb-3">
                        <input type="checkbox" class="custom-control-input" id="reminder1day" name="auto_reminder_1day" value="1" {{ ($autoSettings['reminder_1day'] ?? false) ? 'checked' : '' }}>
                        <label class="custom-control-label" for="reminder1day">2nd Reminder (1 day before due date)</label>
                    </div>

                    <div class="custom-control custom-switch mb-3">
                        <input type="checkbox" class="custom-control-input" id="reminderDeadline" name="auto_reminder_deadline" value="1" {{ ($autoSettings['reminder_deadline'] ?? false) ? 'checked' : '' }}>
                        <label class="custom-control-label" for="reminderDeadline">Due Date SMS (on deadline)</label>
                    </div>
                </div>
            </div>

            <div class="card card-outline card-secondary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-file-alt mr-2"></i>Event Templates</h3>
                </div>
                <div class="card-body p-0">
                    <p class="p-3 text-muted small mb-0">Customize the message for each Auto SMS event from <a href="{{ route('reseller.sms.settings') }}#templates">SMS Settings</a> — Event Templates section.</p>
                    <ul class="list-group list-group-flush">
                        @foreach(\App\Services\ResellerSmsService::EVENT_LABELS as $event => $label)
                        @php $setting_obj = $setting; @endphp
                        <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                            <span><i class="fas fa-comment-dots mr-2 text-muted"></i>{{ $label }}</span>
                            <span class="badge {{ $setting_obj->isEventEnabled($event) ? 'badge-success' : 'badge-secondary' }}">
                                {{ $setting_obj->isEventEnabled($event) ? 'On' : 'Off' }}
                            </span>
                        </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <button type="submit" class="btn btn-primary btn-lg">
                <i class="fas fa-save mr-2"></i>Save Auto Process Settings
            </button>
            <a href="{{ route('reseller.sms.settings') }}" class="btn btn-default btn-lg ml-2">
                <i class="fas fa-cog mr-1"></i> SMS Settings
            </a>
        </div>
    </div>
</form>
@endsection
