<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Two-Factor Verification - SOFTYNEX</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <style>
        body { background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .verify-card { max-width: 420px; width: 100%; border-radius: 16px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); }
    </style>
</head>
<body>
    <div class="verify-card card">
        <div class="card-header text-center bg-white" style="border-bottom: 2px solid #1e3c72;">
            <h4 class="mb-0"><i class="fas fa-shield-alt text-primary mr-2"></i>Two-Factor Verification</h4>
        </div>
        <div class="card-body p-4">
            <p class="text-muted text-center mb-4">Enter the 6-digit code from your authenticator app to continue.</p>

            <form action="{{ route('admin.2fa.verify') }}" method="POST" id="verifyForm">
                @csrf
                <div class="form-group">
                    <label><i class="fas fa-key mr-1"></i>Authentication Code</label>
                    <input type="text" name="code" class="form-control form-control-lg text-center" maxlength="6" pattern="[0-9]{6}" placeholder="000000" required autofocus style="letter-spacing: 8px; font-size: 24px;">
                    @error('code')
                    <span class="text-danger small">{{ $message }}</span>
                    @enderror
                </div>
                <button type="submit" class="btn btn-primary btn-block btn-lg">
                    <i class="fas fa-sign-in-alt mr-2"></i>Verify & Login
                </button>
            </form>

            <hr>

            <div class="text-center">
                <a href="#" data-toggle="collapse" data-target="#recoverySection" class="text-muted">
                    <i class="fas fa-life-ring mr-1"></i>Use a recovery code instead
                </a>
            </div>

            <div class="collapse mt-3" id="recoverySection">
                <form action="{{ route('admin.2fa.verify.recovery') }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label><i class="fas fa-life-ring mr-1"></i>Recovery Code</label>
                        <input type="text" name="code" class="form-control" placeholder="Enter recovery code" required>
                        @error('code')
                        <span class="text-danger small">{{ $message }}</span>
                        @enderror
                    </div>
                    <button type="submit" class="btn btn-warning btn-block">
                        <i class="fas fa-unlock mr-2"></i>Verify with Recovery Code
                    </button>
                </form>
            </div>
        </div>
        <div class="card-footer text-center text-muted small">
            <form action="{{ route('logout') }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-link text-muted btn-sm">
                    <i class="fas fa-sign-out-alt mr-1"></i>Back to Login
                </button>
            </form>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
