@extends('layouts.admin')

@section('title', 'WhatsApp Settings')

@section('content')
<div class="container-fluid">

    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card card-success">
                <div class="card-header">
                    <h3 class="card-title"><i class="fab fa-whatsapp mr-2"></i>WhatsApp Configuration</h3>
                </div>
                <form action="{{ route('admin.whatsapp.settings.update') }}" method="POST">
                    @csrf @method('PUT')
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

                        <div class="form-group">
                            <label><i class="fas fa-server mr-1"></i>Provider</label>
                            <select name="provider" class="form-control" required>
                                <option value="whatsapp_business" {{ ($settings->provider ?? '') == 'whatsapp_business' ? 'selected' : '' }}>WhatsApp Business API</option>
                                <option value="twilio" {{ ($settings->provider ?? '') == 'twilio' ? 'selected' : '' }}>Twilio</option>
                                <option value="ultramsg" {{ ($settings->provider ?? '') == 'ultramsg' ? 'selected' : '' }}>UltraMsg</option>
                                <option value="waha" {{ ($settings->provider ?? '') == 'waha' ? 'selected' : '' }}>WAHA</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label><i class="fas fa-link mr-1"></i>API URL</label>
                            <input type="url" name="api_url" class="form-control" value="{{ $settings->api_url ?? '' }}" placeholder="https://api.example.com/v1">
                        </div>

                        <div class="form-group">
                            <label><i class="fas fa-key mr-1"></i>API Key</label>
                            <input type="text" name="api_key" class="form-control" value="{{ $settings->api_key ?? '' }}" placeholder="Enter API Key">
                        </div>

                        <div class="form-group">
                            <label><i class="fas fa-phone mr-1"></i>Phone Number ID</label>
                            <input type="text" name="phone_number_id" class="form-control" value="{{ $settings->phone_number_id ?? '' }}" placeholder="Phone Number ID from provider">
                        </div>

                        <div class="form-group">
                            <label><i class="fas fa-lock mr-1"></i>Access Token</label>
                            <input type="text" name="access_token" class="form-control" value="{{ $settings->access_token ?? '' }}" placeholder="Access Token">
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label><i class="fas fa-tachometer-alt mr-1"></i>Daily Message Limit</label>
                                    <input type="number" name="daily_limit" class="form-control" value="{{ $settings->daily_limit ?? 1000 }}" min="1">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label><i class="fas fa-toggle-on mr-1"></i>Status</label>
                                    <div class="custom-control custom-switch mt-2">
                                        <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1" {{ ($settings->is_active ?? false) ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="is_active">Active</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save mr-1"></i>Save Settings
                        </button>
                        <button type="button" class="btn btn-info float-right" onclick="testConnection()">
                            <i class="fas fa-plug mr-1"></i>Test Connection
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function testConnection() {
    alert('Testing WhatsApp connection... This feature will send a test message to verify the configuration.');
}
</script>
@endpush
@endsection
