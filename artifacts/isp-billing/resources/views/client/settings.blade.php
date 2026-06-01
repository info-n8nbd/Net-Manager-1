@extends('layouts.client')

@section('title', 'Settings')

@php
    $prefs = json_decode($client->notification_preferences ?? '{}', true) ?: [];
    $smsEnabled = $prefs['sms'] ?? true;
    $emailEnabled = $prefs['email'] ?? false;
@endphp

@section('content')
<div class="row">
    <div class="col-lg-6">
        <div class="card mb-3">
            <div class="card-header py-2">
                <span style="font-size: 14px;"><i class="fas fa-bell mr-2"></i>Notification Preferences</span>
            </div>
            <div class="card-body">
                <form action="{{ route('client.settings.update') }}" method="POST">
                    @csrf
                    <div class="custom-control custom-switch mb-3">
                        <input type="checkbox" class="custom-control-input" id="notification_sms" name="notification_sms" value="1" 
                            {{ $smsEnabled ? 'checked' : '' }}>
                        <label class="custom-control-label" for="notification_sms" style="font-size: 13px;">
                            <i class="fas fa-sms mr-2"></i>SMS Notifications
                        </label>
                        <small class="form-text text-muted">Receive SMS alerts for payments, due reminders, and outages</small>
                    </div>
                    <div class="custom-control custom-switch mb-3">
                        <input type="checkbox" class="custom-control-input" id="notification_email" name="notification_email" value="1"
                            {{ $emailEnabled ? 'checked' : '' }}>
                        <label class="custom-control-label" for="notification_email" style="font-size: 13px;">
                            <i class="fas fa-envelope mr-2"></i>Email Notifications
                        </label>
                        <small class="form-text text-muted">Receive email notifications for invoices and updates</small>
                    </div>
                    <button type="submit" class="btn btn-primary" style="font-size: 13px;">
                        <i class="fas fa-save mr-2"></i>Save Preferences
                    </button>
                </form>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header py-2">
                <span style="font-size: 14px;"><i class="fas fa-language mr-2"></i>Language</span>
            </div>
            <div class="card-body">
                <form action="{{ route('client.settings.update') }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <select name="language" class="form-control" style="font-size: 13px;">
                            <option value="en" {{ ($client->language ?? 'en') == 'en' ? 'selected' : '' }}>English</option>
                            <option value="bn" {{ ($client->language ?? 'en') == 'bn' ? 'selected' : '' }}>Bangla</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary" style="font-size: 13px;">
                        <i class="fas fa-save mr-2"></i>Save Language
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card mb-3">
            <div class="card-header py-2">
                <span style="font-size: 14px;"><i class="fas fa-lock mr-2"></i>Change Password</span>
            </div>
            <div class="card-body">
                <form action="{{ route('client.password.update') }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label style="font-size: 12px; font-weight: 600;">Current Password</label>
                        <input type="password" name="current_password" class="form-control" required style="font-size: 13px;">
                    </div>
                    <div class="form-group">
                        <label style="font-size: 12px; font-weight: 600;">New Password</label>
                        <input type="password" name="password" class="form-control" required minlength="6" style="font-size: 13px;">
                    </div>
                    <div class="form-group">
                        <label style="font-size: 12px; font-weight: 600;">Confirm New Password</label>
                        <input type="password" name="password_confirmation" class="form-control" required style="font-size: 13px;">
                    </div>
                    <button type="submit" class="btn btn-warning" style="font-size: 13px;">
                        <i class="fas fa-key mr-2"></i>Update Password
                    </button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header py-2">
                <span style="font-size: 14px;"><i class="fas fa-shield-alt mr-2"></i>Security</span>
            </div>
            <div class="card-body" style="font-size: 13px;">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <strong>Active Sessions</strong>
                        <br><small class="text-muted">Manage your logged-in devices</small>
                    </div>
                    <span class="badge badge-success">1 Active</span>
                </div>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-outline-danger btn-sm">
                        <i class="fas fa-sign-out-alt mr-1"></i>Logout All Devices
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
