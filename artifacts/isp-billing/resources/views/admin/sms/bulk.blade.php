@extends('layouts.admin')

@section('title', 'Bulk SMS')
@section('breadcrumb')
<li class="breadcrumb-item active">Bulk SMS</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header"><h3 class="card-title">Send Bulk SMS</h3></div>
    <form action="{{ route('admin.sms.bulk.send') }}" method="POST">
        @csrf
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Select Template</label>
                        <select class="form-control" id="template_select">
                            <option value="">Custom Message</option>
                            @foreach($templates as $template)
                            <option value="{{ $template->content }}">{{ $template->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Message *</label>
                        <textarea name="message" class="form-control" rows="5" id="message" required></textarea>
                        <small class="text-muted">Characters: <span id="charCount">0</span></small>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Select Recipients</label>
                        <div style="max-height: 300px; overflow-y: auto; border: 1px solid #ddd; padding: 10px;">
                            @foreach($clients as $client)
                            <div class="form-check">
                                <input type="checkbox" name="recipients[]" value="{{ $client->id }}" class="form-check-input" id="client{{ $client->id }}">
                                <label class="form-check-label" for="client{{ $client->id }}">{{ $client->name }} ({{ $client->phone }})</label>
                            </div>
                            @endforeach
                        </div>
                        <button type="button" class="btn btn-sm btn-secondary mt-2" onclick="document.querySelectorAll('input[name=\'recipients[]\']').forEach(i => i.checked = true)">Select All</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer"><button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Send SMS</button></div>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('template_select').addEventListener('change', function() {
    document.getElementById('message').value = this.value;
});
document.getElementById('message').addEventListener('input', function() {
    document.getElementById('charCount').textContent = this.value.length;
});
</script>
@endpush
