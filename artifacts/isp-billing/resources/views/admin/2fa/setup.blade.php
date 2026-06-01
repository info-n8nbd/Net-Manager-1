@extends('layouts.admin')

@section('title', 'Two-Factor Authentication Setup')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8 offset-md-2">

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-shield-alt mr-2"></i>Setup Two-Factor Authentication</h3>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-2"></i>
                        Two-factor authentication adds an extra layer of security to your account. You will need an authenticator app like Google Authenticator or Authy.
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <h5><i class="fas fa-key mr-2"></i>Step 1: Add to Authenticator App</h5>
                            <p class="text-muted">Scan the QR code or manually enter the secret key in your authenticator app.</p>

                            <div class="card card-outline card-primary mb-3">
                                <div class="card-header">
                                    <h6 class="card-title mb-0">Manual Entry Key</h6>
                                </div>
                                <div class="card-body">
                                    <div class="input-group">
                                        <input type="text" class="form-control font-weight-bold" value="{{ $secret }}" id="secretKey" readonly>
                                        <div class="input-group-append">
                                            <button class="btn btn-outline-primary" type="button" onclick="copyToClipboard('secretKey')">
                                                <i class="fas fa-copy"></i> Copy
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card card-outline card-info mb-3">
                                <div class="card-header">
                                    <h6 class="card-title mb-0">OTPAuth URL</h6>
                                </div>
                                <div class="card-body">
                                    <div class="input-group">
                                        <input type="text" class="form-control small" value="{{ $qrCodeUrl }}" id="qrUrl" readonly>
                                        <div class="input-group-append">
                                            <button class="btn btn-outline-info" type="button" onclick="copyToClipboard('qrUrl')">
                                                <i class="fas fa-copy"></i> Copy
                                            </button>
                                        </div>
                                    </div>
                                    <small class="text-muted mt-1 d-block">Paste this URL into your authenticator app if it supports URL import.</small>
                                </div>
                            </div>

                            <p class="text-muted small">
                                <strong>Account:</strong> {{ $user->email }}
                            </p>
                        </div>

                        <div class="col-md-6">
                            <h5><i class="fas fa-check-circle mr-2"></i>Step 2: Verify Code</h5>
                            <p class="text-muted">Enter the 6-digit code from your authenticator app to verify setup.</p>

                            <form action="{{ route('admin.2fa.enable') }}" method="POST">
                                @csrf
                                <div class="form-group">
                                    <label>Authentication Code</label>
                                    <input type="text" name="code" class="form-control form-control-lg text-center" maxlength="6" pattern="[0-9]{6}" placeholder="000000" required autofocus style="letter-spacing: 8px; font-size: 24px;">
                                    @error('code')
                                    <span class="text-danger small">{{ $message }}</span>
                                    @enderror
                                </div>
                                <button type="submit" class="btn btn-success btn-block">
                                    <i class="fas fa-check mr-2"></i>Enable Two-Factor Authentication
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            @if(isset($recoveryCodes) && count($recoveryCodes) > 0)
            <div class="card card-outline card-warning">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-life-ring mr-2"></i>Recovery Codes</h3>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        <strong>Important!</strong> Store these recovery codes in a safe place. Each code can only be used once.
                    </div>
                    <div class="row">
                        @foreach($recoveryCodes as $code)
                        <div class="col-md-4 mb-2">
                            <code class="d-block p-2 bg-light text-center">{{ $code }}</code>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
function copyToClipboard(elementId) {
    var input = document.getElementById(elementId);
    input.select();
    input.setSelectionRange(0, 99999);
    document.execCommand('copy');
    alert('Copied to clipboard!');
}
</script>
@endpush
@endsection
