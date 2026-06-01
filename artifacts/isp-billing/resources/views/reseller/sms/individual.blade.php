@extends('layouts.reseller')

@section('title', 'Individual SMS')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('reseller.sms.settings') }}">SMS</a></li>
<li class="breadcrumb-item active">Individual SMS</li>
@endsection

@section('content')
@if(!($apiStatus['configured'] ?? false))
<div class="alert alert-warning">
    <i class="fas fa-exclamation-triangle mr-2"></i>
    <strong>SMS Gateway not configured.</strong>
    Set your API Key and Provider from <a href="{{ route('reseller.sms.settings') }}" class="alert-link">SMS Settings</a>.
    <br><small>Provider: {{ $apiStatus['provider_name'] ?? 'Not Set' }}</small>
</div>
@else
<div class="alert alert-info">
    <i class="fas fa-info-circle mr-2"></i>
    Provider: <strong>{{ $apiStatus['provider_name'] ?? 'N/A' }}</strong>
    @if($apiStatus['sender_id'] ?? '')
    | Sender ID: <strong>{{ $apiStatus['sender_id'] }}</strong>
    @endif
    | SMS <span class="badge badge-{{ $apiStatus['enabled'] ? 'success' : 'secondary' }}">{{ $apiStatus['enabled'] ? 'Active' : 'Disabled' }}</span>
</div>
@endif

<div class="row">
    <div class="col-md-7">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-sms mr-2"></i>Send Individual SMS</h3>
            </div>
            <form action="{{ route('reseller.sms.individual.send') }}" method="POST">
                @csrf
                <div class="card-body">
                    @if(session('success'))
                    <div class="alert alert-success"><i class="fas fa-check-circle mr-2"></i>{{ session('success') }}</div>
                    @endif
                    @if(session('error'))
                    <div class="alert alert-danger"><i class="fas fa-times-circle mr-2"></i>{{ session('error') }}</div>
                    @endif

                    <div class="form-group">
                        <label><i class="fas fa-user mr-1 text-primary"></i> Select Client <small class="text-muted">(or enter a number below)</small></label>
                        <select name="client_id" class="form-control select2" id="clientSelect" style="width:100%">
                            <option value="">-- Select Client --</option>
                            @foreach($clients as $client)
                            <option value="{{ $client->id }}" data-phone="{{ $client->phone }}">
                                {{ $client->name }} ({{ $client->phone ?? 'No phone' }}) — {{ $client->username }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-phone mr-1 text-success"></i> Or any number <small class="text-muted">(Custom)</small></label>
                        <input type="text" name="custom_phone" id="customPhone" class="form-control" placeholder="01xxxxxxxxx" value="{{ old('custom_phone') }}">
                        <small class="text-muted">Leave blank if a client is selected above.</small>
                    </div>

                    @if($templates->count() > 0)
                    <div class="form-group">
                        <label><i class="fas fa-file-alt mr-1 text-info"></i> Use Template <small class="text-muted">(Optional)</small></label>
                        <select class="form-control" id="templateSelect">
                            <option value="">-- Write Custom Message --</option>
                            @foreach($templates as $tpl)
                            <option value="{{ $tpl->content }}">[{{ ucfirst($tpl->type) }}] {{ $tpl->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    <div class="form-group">
                        <label><i class="fas fa-comment mr-1 text-warning"></i> Message <span class="text-danger">*</span></label>
                        <textarea name="message" id="smsMessage" class="form-control" rows="5" required placeholder="Type your SMS here...">{{ old('message') }}</textarea>
                        <small class="text-muted">
                            Characters: <span id="charCount" class="font-weight-bold text-primary">0</span> |
                            SMS Parts: <span id="smsCount" class="font-weight-bold text-success">1</span> |
                            Variables: <code>{name}</code> <code>{phone}</code> <code>{package}</code> <code>{amount}</code>
                        </small>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary" {{ !($apiStatus['enabled'] ?? false) ? 'disabled' : '' }}>
                        <i class="fas fa-paper-plane mr-1"></i> Send SMS
                    </button>
                    <a href="{{ route('reseller.sms.logs') }}" class="btn btn-default ml-2">
                        <i class="fas fa-history mr-1"></i> View SMS Logs
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="col-md-5">
        <div class="card card-outline card-info">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-info-circle mr-2"></i>Instructions</h3>
            </div>
            <div class="card-body" style="font-size:0.9rem;">
                <p><strong>If a client is selected:</strong> SMS will be sent to that client's phone number.</p>
                <p><strong>If a custom number is entered:</strong> SMS will be sent to that number. It will appear as "Custom" in the logs.</p>
                <hr>
                <p class="mb-1"><strong>Template Variables:</strong></p>
                <ul class="list-unstyled mb-0" style="font-size:0.85rem;">
                    <li><code>{name}</code> — Client name</li>
                    <li><code>{phone}</code> — Phone number</li>
                    <li><code>{package}</code> — Package</li>
                    <li><code>{company}</code> — Your name</li>
                    <li><code>{amount}</code> — Amount</li>
                    <li><code>{due}</code> — Due amount</li>
                    <li><code>{due_date}</code> — Due date</li>
                </ul>
            </div>
        </div>

        <div class="card card-outline card-success">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-link mr-2"></i>Quick Links</h3>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item"><a href="{{ route('reseller.sms.bulk') }}"><i class="fas fa-paper-plane mr-2 text-primary"></i>Send Bulk SMS</a></li>
                    <li class="list-group-item"><a href="{{ route('reseller.sms.templates') }}"><i class="fas fa-file-alt mr-2 text-info"></i>SMS Templates</a></li>
                    <li class="list-group-item"><a href="{{ route('reseller.sms.logs') }}"><i class="fas fa-list mr-2 text-warning"></i>SMS Logs</a></li>
                    <li class="list-group-item"><a href="{{ route('reseller.sms.settings') }}"><i class="fas fa-cog mr-2 text-secondary"></i>SMS Settings</a></li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function(){
    if($.fn.select2) $('#clientSelect').select2({ placeholder: '-- Select Client --', allowClear: true });

    $('#clientSelect').on('change', function(){
        if($(this).val()) $('#customPhone').val('');
    });

    $('#customPhone').on('input', function(){
        if($(this).val()) { $('#clientSelect').val('').trigger('change'); }
    });

    $('#templateSelect').on('change', function(){
        if($(this).val()) $('#smsMessage').val($(this).val());
        updateCount();
    });

    function updateCount(){
        var len = $('#smsMessage').val().length;
        $('#charCount').text(len);
        $('#smsCount').text(Math.ceil(len/160) || 1);
    }
    $('#smsMessage').on('input', updateCount);
    updateCount();
});
</script>
@endpush
