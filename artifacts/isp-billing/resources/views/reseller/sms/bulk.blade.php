@extends('layouts.reseller')
@section('title', 'Bulk SMS')

@section('content')
<div class="row">
    <div class="col-lg-8">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-paper-plane mr-2 text-primary"></i>Send Bulk SMS</h3>
                <div class="card-tools">
                    <a href="{{ route('reseller.sms.settings') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-cog mr-1"></i> Settings
                    </a>
                    <a href="{{ route('reseller.sms.logs') }}" class="btn btn-sm btn-outline-secondary ml-1">
                        <i class="fas fa-list mr-1"></i> Logs
                    </a>
                </div>
            </div>
            <form method="POST" action="{{ route('reseller.sms.bulk.send') }}">
                @csrf
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible"><i class="fas fa-check-circle mr-1"></i> {{ session('success') }} <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button></div>
                    @endif
                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible"><i class="fas fa-exclamation-circle mr-1"></i> {{ session('error') }} <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button></div>
                    @endif

                    @if(isset($templates) && $templates->count() > 0)
                    <div class="form-group">
                        <label class="font-weight-bold"><i class="fas fa-file-alt mr-1 text-info"></i>Use Template <small class="text-muted font-weight-normal">(Optional)</small></label>
                        <select class="form-control" id="templateSelect">
                            <option value="">-- Write Custom Message --</option>
                            @foreach($templates as $tpl)
                            <option value="{{ $tpl->content }}">[{{ ucfirst($tpl->type) }}] {{ $tpl->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    <div class="form-group">
                        <label class="font-weight-bold">SMS Message <span class="text-danger">*</span></label>
                        <textarea name="message" id="bulkMessage" class="form-control" rows="4" required
                                  placeholder="Type your SMS message here... Variables: {name} {phone} {package} {company}">{{ old('message') }}</textarea>
                        <small class="text-muted"><span id="charCount">0</span> characters (<span id="smsCount">1</span> SMS)</small>
                    </div>

                    <div class="form-group">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="font-weight-bold mb-0">Select Recipients <span class="text-danger">*</span></label>
                            <div>
                                <button type="button" class="btn btn-xs btn-outline-primary mr-1" onclick="selectAll(true)">Select All</button>
                                <button type="button" class="btn btn-xs btn-outline-secondary" onclick="selectAll(false)">Deselect All</button>
                            </div>
                        </div>

                        <div class="table-responsive" style="max-height: 350px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 4px;">
                            <table class="table table-sm table-hover mb-0">
                                <thead class="thead-light sticky-top">
                                    <tr>
                                        <th width="40"><input type="checkbox" id="checkAll" onchange="selectAll(this.checked)"></th>
                                        <th>Name</th>
                                        <th>Phone</th>
                                        <th>Package</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($clients as $client)
                                    <tr>
                                        <td>
                                            <input type="checkbox" name="client_ids[]" value="{{ $client->id }}"
                                                   class="client-check" {{ old('client_ids') && in_array($client->id, old('client_ids', [])) ? 'checked' : '' }}>
                                        </td>
                                        <td>{{ $client->name }}</td>
                                        <td>{{ $client->phone }}</td>
                                        <td><small>{{ $client->package->name ?? '-' }}</small></td>
                                        <td>
                                            <span class="badge badge-{{ $client->status === 'active' ? 'success' : 'secondary' }}">
                                                {{ ucfirst($client->status) }}
                                            </span>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="5" class="text-center text-muted py-3">No clients found.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <small class="text-muted">Total <strong>{{ $clients->count() }}</strong> clients (active with phone). <span id="selectedCount">0</span> selected.</small>
                        @error('client_ids')
                            <div class="text-danger"><small>{{ $message }}</small></div>
                        @enderror
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary" onclick="return confirmSend()">
                        <i class="fas fa-paper-plane mr-1"></i> Send SMS
                    </button>
                    <a href="{{ route('reseller.sms.logs') }}" class="btn btn-default ml-2">
                        <i class="fas fa-history mr-1"></i> View SMS Logs
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card card-outline card-info">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-info-circle mr-2"></i>Instructions</h3></div>
            <div class="card-body">
                <ul class="list-unstyled mb-0">
                    <li class="mb-2"><i class="fas fa-check text-success mr-2"></i>Use variables: <code>{name}</code>, <code>{phone}</code>, <code>{package}</code>, <code>{company}</code></li>
                    <li class="mb-2"><i class="fas fa-check text-success mr-2"></i>Messages over 160 characters will count as multiple SMS.</li>
                    <li class="mb-2"><i class="fas fa-check text-success mr-2"></i>Only active clients with phone numbers are shown.</li>
                    <li class="mb-2"><i class="fas fa-info-circle text-info mr-2"></i>SMS API must be configured before sending.</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
var bulkMsg = document.getElementById('bulkMessage');
bulkMsg.addEventListener('input', function() {
    var len = this.value.length;
    document.getElementById('charCount').textContent = len;
    document.getElementById('smsCount').textContent = Math.ceil(len / 160) || 1;
});

var tplSelect = document.getElementById('templateSelect');
if (tplSelect) {
    tplSelect.addEventListener('change', function() {
        if (this.value) { bulkMsg.value = this.value; bulkMsg.dispatchEvent(new Event('input')); }
    });
}

function selectAll(state) {
    document.querySelectorAll('.client-check').forEach(cb => cb.checked = state);
    document.getElementById('checkAll').checked = state;
    updateCount();
}

document.querySelectorAll('.client-check').forEach(cb => cb.addEventListener('change', updateCount));

function updateCount() {
    var c = document.querySelectorAll('.client-check:checked').length;
    document.getElementById('selectedCount').textContent = c;
}

function confirmSend() {
    var sel = document.querySelectorAll('.client-check:checked').length;
    if (sel === 0) { smartAlert('Please select at least one client.', 'warning'); return false; }
    return confirm('Send SMS to ' + sel + ' client(s)?');
}
</script>
@endpush
