@extends('layouts.admin')

@section('title', 'SMS Auto Process')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.sms.templates') }}">SMS</a></li>
<li class="breadcrumb-item active">Auto Process</li>
@endsection

@section('content')
<form action="{{ route('admin.sms.auto-process.save') }}" method="POST">
    @csrf
    <div class="row">
        <div class="col-md-6">
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-cog mr-2"></i>Bill Generation SMS</h3>
                </div>
                <div class="card-body">
                    <div class="custom-control custom-switch mb-3">
                        <input type="checkbox" class="custom-control-input" id="billGenSms" name="sms_auto_bill_generation" value="1" {{ $settings['sms_auto_bill_generation'] ?? false ? 'checked' : '' }}>
                        <label class="custom-control-label" for="billGenSms">Enable Bill Generation SMS</label>
                    </div>
                    <p class="text-muted">Send SMS to all clients when bills are generated with their bill amount and due date.</p>
                </div>
            </div>
            
            <div class="card card-warning card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-bell mr-2"></i>Deadline Reminders</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <div class="custom-control custom-switch mb-2">
                            <input type="checkbox" class="custom-control-input" id="reminder1" name="sms_auto_reminder_3day" value="1" {{ $settings['sms_auto_reminder_3day'] ?? false ? 'checked' : '' }}>
                            <label class="custom-control-label" for="reminder1">1st Reminder (3 days before deadline)</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="custom-control custom-switch mb-2">
                            <input type="checkbox" class="custom-control-input" id="reminder2" name="sms_auto_reminder_1day" value="1" {{ $settings['sms_auto_reminder_1day'] ?? false ? 'checked' : '' }}>
                            <label class="custom-control-label" for="reminder2">2nd Reminder (1 day before deadline)</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="disconnectSms" name="sms_auto_disconnection" value="1" {{ $settings['sms_auto_disconnection'] ?? false ? 'checked' : '' }}>
                            <label class="custom-control-label" for="disconnectSms">Disconnection SMS (on deadline)</label>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card card-success card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-mobile-alt mr-2"></i>Online Payment SMS</h3>
                </div>
                <div class="card-body">
                    <div class="custom-control custom-switch mb-3">
                        <input type="checkbox" class="custom-control-input" id="onlinePaySms" name="sms_auto_online_payment" value="1" {{ $settings['sms_auto_online_payment'] ?? false ? 'checked' : '' }}>
                        <label class="custom-control-label" for="onlinePaySms">Send SMS on Online Payment</label>
                    </div>
                    <p class="text-muted">Automatically send confirmation SMS with TrxID when client pays via bKash/Rocket/iPay.</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card card-danger card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-server mr-2"></i>Device Down Notification</h3>
                </div>
                <div class="card-body">
                    <div class="custom-control custom-switch mb-3">
                        <input type="checkbox" class="custom-control-input" id="deviceDownSms" name="sms_auto_device_down" value="1" {{ $settings['sms_auto_device_down'] ?? false ? 'checked' : '' }}>
                        <label class="custom-control-label" for="deviceDownSms">Enable Device Down SMS</label>
                    </div>
                    <div class="form-group">
                        <label>Notify Admin Phone</label>
                        <input type="text" class="form-control" name="sms_auto_admin_phone" value="{{ $settings['sms_auto_admin_phone'] ?? '' }}" placeholder="+8801XXXXXXXXX">
                    </div>
                    <p class="text-muted">Send SMS when any ONU/Router goes offline.</p>
                </div>
            </div>
            
            <div class="card card-info card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-envelope mr-2"></i>New Client Welcome SMS</h3>
                </div>
                <div class="card-body">
                    <div class="custom-control custom-switch mb-3">
                        <input type="checkbox" class="custom-control-input" id="welcomeSms" name="sms_auto_welcome" value="1" {{ $settings['sms_auto_welcome'] ?? false ? 'checked' : '' }}>
                        <label class="custom-control-label" for="welcomeSms">Send Welcome SMS to New Clients</label>
                    </div>
                    <p class="text-muted">Automatically send credentials and welcome message when a new client is created.</p>
                </div>
            </div>
            
            <div class="card card-secondary card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-clock mr-2"></i>Schedule Time</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label>Reminder SMS Time</label>
                        <input type="time" class="form-control" name="sms_auto_reminder_time" value="{{ $settings['sms_auto_reminder_time'] ?? '09:00' }}">
                    </div>
                    <div class="form-group">
                        <label>Device Check Interval (minutes)</label>
                        <select class="form-control" name="sms_auto_device_check_interval">
                            <option value="1" {{ ($settings['sms_auto_device_check_interval'] ?? '5') == '1' ? 'selected' : '' }}>Every 1 minute</option>
                            <option value="5" {{ ($settings['sms_auto_device_check_interval'] ?? '5') == '5' ? 'selected' : '' }}>Every 5 minutes</option>
                            <option value="15" {{ ($settings['sms_auto_device_check_interval'] ?? '5') == '15' ? 'selected' : '' }}>Every 15 minutes</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i>Save All Settings</button>
                    <a href="{{ route('admin.sms.auto-process') }}?run_now=1" class="btn btn-success float-right" onclick="return confirm('This will send due reminders to all clients with unpaid bills. Continue?')"><i class="fas fa-play mr-1"></i>Send Due Reminders Now</a>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection
