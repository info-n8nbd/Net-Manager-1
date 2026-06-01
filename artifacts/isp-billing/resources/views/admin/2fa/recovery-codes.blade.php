@extends('layouts.admin')

@section('title', 'Recovery Codes')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8 offset-md-2">

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-life-ring mr-2"></i>Recovery Codes</h3>
                    <div class="card-tools">
                        <form action="{{ route('admin.2fa.regenerate-codes') }}" method="POST" class="d-inline" onsubmit="return confirm('This will invalidate all existing recovery codes. Continue?')">
                            @csrf
                            <button type="submit" class="btn btn-warning btn-sm">
                                <i class="fas fa-sync-alt mr-1"></i>Regenerate Codes
                            </button>
                        </form>
                    </div>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        <strong>Important!</strong> Store these recovery codes in a safe, secure location. Each code can only be used once to sign in if you lose access to your authenticator device.
                    </div>

                    <div class="card card-outline card-primary">
                        <div class="card-body">
                            <div class="row" id="recoveryCodes">
                                @forelse($recoveryCodes as $code)
                                <div class="col-md-4 col-6 mb-2">
                                    <code class="d-block p-2 bg-light text-center rounded" style="font-size: 14px;">{{ $code }}</code>
                                </div>
                                @empty
                                <div class="col-12">
                                    <p class="text-muted text-center">No recovery codes available. Please regenerate.</p>
                                </div>
                                @endforelse
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="button" class="btn btn-primary" onclick="copyAllCodes()">
                                <i class="fas fa-copy mr-1"></i>Copy All Codes
                            </button>
                            <button type="button" class="btn btn-info" onclick="downloadCodes()">
                                <i class="fas fa-download mr-1"></i>Download as Text
                            </button>
                        </div>
                    </div>

                    <div class="mt-3">
                        <p class="text-muted small mb-1"><i class="fas fa-info-circle mr-1"></i>Tips:</p>
                        <ul class="text-muted small">
                            <li>Save these codes in a password manager</li>
                            <li>Print them and store in a secure place</li>
                            <li>Each code can only be used once</li>
                            <li>Regenerating codes will invalidate all previous codes</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function copyAllCodes() {
    var codes = [];
    document.querySelectorAll('#recoveryCodes code').forEach(function(el) {
        codes.push(el.textContent.trim());
    });
    var text = codes.join('\n');
    navigator.clipboard.writeText(text).then(function() {
        alert('Recovery codes copied to clipboard!');
    });
}

function downloadCodes() {
    var codes = [];
    document.querySelectorAll('#recoveryCodes code').forEach(function(el) {
        codes.push(el.textContent.trim());
    });
    var text = "SOFTYNEX - Recovery Codes\n" + "=".repeat(30) + "\n\n" + codes.join('\n') + "\n\nKeep these codes safe. Each code can only be used once.";
    var blob = new Blob([text], {type: 'text/plain'});
    var a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'recovery-codes.txt';
    a.click();
}
</script>
@endpush
@endsection
