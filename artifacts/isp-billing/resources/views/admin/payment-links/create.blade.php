@extends('layouts.admin')

@section('title', 'Create Payment Link')

@section('content')
<div class="card card-primary">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-plus mr-2"></i>Create Payment Link</h3>
    </div>
    <form action="{{ route('admin.payment-links.store') }}" method="POST">
        @csrf
        <div class="card-body">
            @if($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <div class="row">
                <div class="col-md-6">
                    @include('admin.partials.client-search', [
                        'inputId' => 'payLinkClientSearch',
                        'hiddenId' => 'payLinkClientSearchId',
                        'selectedValue' => old('client_id', $selectedClient->id ?? ''),
                        'selectedName' => old('client_id', $selectedClient->id ?? '') ? (($selectedClient->name ?? '') . ' (' . ($selectedClient->username ?? $selectedClient->client_id ?? '') . ')') : '',
                    ])
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Amount (৳) <span class="text-danger">*</span></label>
                        <input type="number" name="amount" id="amountInput" class="form-control @error('amount') is-invalid @enderror" value="{{ old('amount', $autoAmount ?? '') }}" step="0.01" required>
                        @error('amount')<span class="invalid-feedback">{{ $message }}</span>@enderror
                        <small class="text-muted" id="dueHint" style="display:none;">Client due: <strong id="dueHintAmount">৳0</strong> <a href="#" id="useDueBtn" class="ml-2">Use due amount</a></small>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="2">{{ old('description', 'Due payment') }}</textarea>
                @error('description')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" name="send_sms" class="custom-control-input" id="sendSms" value="1" {{ old('send_sms') ? 'checked' : '' }}>
                            <label class="custom-control-label" for="sendSms">Send SMS notification</label>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" name="send_whatsapp" class="custom-control-input" id="sendWhatsapp" value="1" {{ old('send_whatsapp') ? 'checked' : '' }}>
                            <label class="custom-control-label" for="sendWhatsapp">Send WhatsApp notification</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Create Link</button>
            <a href="{{ route('admin.payment-links.index') }}" class="btn btn-default">Cancel</a>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var useDueBtn = document.getElementById('useDueBtn');
    if (useDueBtn) {
        useDueBtn.addEventListener('click', function(e) {
            e.preventDefault();
        });
    }
});
</script>
@endpush
