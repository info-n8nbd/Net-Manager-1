@extends('layouts.admin')
@section('title', 'Individual SMS')
@section('breadcrumb')<li class="breadcrumb-item active">Individual SMS</li>@endsection

@section('content')
@if(!($apiStatus['configured'] ?? false))
<div class="alert alert-warning">
    <i class="fas fa-exclamation-triangle mr-2"></i>
    <strong>SMS Gateway Not Configured.</strong> Please <a href="{{ route('admin.settings.sms') }}" class="alert-link">configure your SMS settings</a> before sending messages.
    <br><small>Provider: {{ $apiStatus['provider_name'] ?? 'Not set' }}</small>
</div>
@else
<div class="alert alert-info">
    <i class="fas fa-info-circle mr-2"></i>
    Provider: <strong>{{ $apiStatus['provider_name'] ?? 'N/A' }}</strong> | Sender ID: <strong>{{ $apiStatus['sender_id'] ?? 'N/A' }}</strong>
</div>
@endif

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-sms mr-2"></i>Send Individual SMS</h3>
    </div>
    <form action="{{ route('admin.sms.individual.send') }}" method="POST">
        @csrf
        <div class="card-body">
            @include('admin.partials.client-search', [
                'inputId' => 'smsClientSearch',
                'hiddenId' => 'smsClientSearchId',
                'label' => 'Select Client',
            ])
            @if(isset($templates) && $templates->count() > 0)
            <div class="form-group">
                <label>Use Template (Optional)</label>
                <select class="form-control" id="template_select">
                    <option value="">-- Custom Message --</option>
                    @foreach($templates as $template)
                    <option value="{{ $template->content }}">{{ $template->name }} ({{ $template->type }})</option>
                    @endforeach
                </select>
            </div>
            @endif
            <div class="form-group">
                <label>Message <span class="text-danger">*</span></label>
                <textarea name="message" class="form-control" rows="4" required id="sms_message">{{ old('message') }}</textarea>
                <small class="text-muted">Characters: <span id="charCount">0</span> | SMS parts: <span id="smsCount">1</span></small>
            </div>
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane mr-1"></i> Send SMS</button>
            <a href="{{ route('admin.sms.logs') }}" class="btn btn-default ml-2"><i class="fas fa-history mr-1"></i> View Logs</a>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
var templateSelect = document.getElementById('template_select');
if (templateSelect) {
    templateSelect.addEventListener('change', function() {
        if (this.value) {
            document.getElementById('sms_message').value = this.value;
            updateCount();
        }
    });
}
var msgEl = document.getElementById('sms_message');
function updateCount() {
    var len = msgEl.value.length;
    document.getElementById('charCount').textContent = len;
    document.getElementById('smsCount').textContent = len <= 160 ? 1 : Math.ceil(len / 153);
}
msgEl.addEventListener('input', updateCount);
updateCount();
</script>
@endpush
