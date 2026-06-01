<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Sign Up - SOFTYNEX</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background: #f0f4f8; min-height: 100vh; }
        .header { background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); padding: 40px 20px; text-align: center; color: white; }
        .header h1 { font-size: 28px; font-weight: 700; margin-bottom: 5px; }
        .header p { font-size: 14px; opacity: 0.8; }
        .container { max-width: 480px; margin: -30px auto 40px; padding: 0 20px; }
        .card { background: white; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); padding: 30px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 6px; color: #1e3c72; font-size: 14px; }
        .form-control { width: 100%; padding: 12px 16px; border: 2px solid #e2e8f0; border-radius: 10px; font-size: 15px; transition: all 0.2s; outline: none; }
        .form-control:focus { border-color: #1e3c72; box-shadow: 0 0 0 3px rgba(30,60,114,0.1); }
        select.form-control { appearance: none; background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23475569' d='M6 8L1 3h10z'/%3E%3C/svg%3E") no-repeat right 14px center white; }
        .btn { width: 100%; padding: 14px; border: none; border-radius: 10px; font-size: 16px; font-weight: 600; cursor: pointer; transition: all 0.3s; }
        .btn-primary { background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); color: white; }
        .btn-primary:hover { box-shadow: 0 4px 15px rgba(30,60,114,0.4); transform: translateY(-1px); }
        .btn-success { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; }
        .btn:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }
        .otp-inputs { display: flex; gap: 10px; justify-content: center; margin: 20px 0; }
        .otp-input { width: 50px; height: 55px; text-align: center; font-size: 22px; font-weight: 700; border: 2px solid #e2e8f0; border-radius: 10px; outline: none; }
        .otp-input:focus { border-color: #1e3c72; box-shadow: 0 0 0 3px rgba(30,60,114,0.1); }
        .step { display: none; }
        .step.active { display: block; }
        .alert { padding: 12px 16px; border-radius: 10px; margin-bottom: 15px; font-size: 14px; }
        .alert-danger { background: #fee2e2; color: #991b1b; }
        .alert-success { background: #d1fae5; color: #065f46; }
        .step-indicator { display: flex; justify-content: center; gap: 8px; margin-bottom: 25px; }
        .step-dot { width: 10px; height: 10px; border-radius: 50%; background: #e2e8f0; transition: all 0.3s; }
        .step-dot.active { background: #1e3c72; width: 30px; border-radius: 5px; }
        .phone-input-group { display: flex; gap: 8px; }
        .phone-prefix { padding: 12px 14px; background: #f0f4f8; border: 2px solid #e2e8f0; border-radius: 10px; font-weight: 600; color: #475569; white-space: nowrap; }
    </style>
</head>
<body>
    <div class="header">
        <h1><i class="fas fa-wifi"></i> SOFTYNEX</h1>
        <p>Create your account in minutes</p>
    </div>
    <div class="container">
        <div class="card">
            <div class="step-indicator">
                <div class="step-dot active" id="dot1"></div>
                <div class="step-dot" id="dot2"></div>
                <div class="step-dot" id="dot3"></div>
            </div>

            <div id="alertBox"></div>

            <div class="step active" id="step1">
                <h3 style="text-align:center; color:#1e3c72; margin-bottom:20px;">Enter Your Phone Number</h3>
                <div class="form-group">
                    <label>Phone Number</label>
                    <div class="phone-input-group">
                        <span class="phone-prefix">+880</span>
                        <input type="tel" id="phone" class="form-control" placeholder="1XXXXXXXXX" maxlength="10" pattern="[0-9]*" inputmode="numeric">
                    </div>
                </div>
                <button type="button" class="btn btn-primary" id="sendOtpBtn" onclick="sendOtp()">
                    <i class="fas fa-paper-plane"></i> Send OTP
                </button>
            </div>

            <div class="step" id="step2">
                <h3 style="text-align:center; color:#1e3c72; margin-bottom:20px;">Verify OTP</h3>
                <p style="text-align:center; color:#64748b; margin-bottom:15px;">Enter the 6-digit code sent to your phone</p>
                <div class="otp-inputs">
                    <input type="text" class="otp-input" maxlength="1" inputmode="numeric" pattern="[0-9]">
                    <input type="text" class="otp-input" maxlength="1" inputmode="numeric" pattern="[0-9]">
                    <input type="text" class="otp-input" maxlength="1" inputmode="numeric" pattern="[0-9]">
                    <input type="text" class="otp-input" maxlength="1" inputmode="numeric" pattern="[0-9]">
                    <input type="text" class="otp-input" maxlength="1" inputmode="numeric" pattern="[0-9]">
                    <input type="text" class="otp-input" maxlength="1" inputmode="numeric" pattern="[0-9]">
                </div>
                <button type="button" class="btn btn-primary" id="verifyOtpBtn" onclick="verifyOtp()">
                    <i class="fas fa-check-circle"></i> Verify
                </button>
            </div>

            <div class="step" id="step3">
                <h3 style="text-align:center; color:#1e3c72; margin-bottom:20px;">Complete Registration</h3>
                <form id="registrationForm">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="name" class="form-control" placeholder="Enter your full name" required>
                    </div>
                    <div class="form-group">
                        <label>Phone Number</label>
                        <input type="text" id="confirmedPhone" class="form-control" readonly>
                    </div>
                    <div class="form-group">
                        <label>Package</label>
                        <select name="package_id" class="form-control" required>
                            <option value="">Select a package</option>
                            @foreach($packages ?? [] as $package)
                                <option value="{{ $package->id }}">{{ $package->name }} - ৳{{ number_format($package->price) }}/month</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Address</label>
                        <textarea name="address" class="form-control" rows="3" placeholder="Enter your full address" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-user-plus"></i> Complete Registration
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        var verifiedPhone = '';
        var otpToken = '';

        function showAlert(msg, type) {
            document.getElementById('alertBox').innerHTML = '<div class="alert alert-' + type + '">' + msg + '</div>';
        }

        function goToStep(step) {
            document.querySelectorAll('.step').forEach(function(s) { s.classList.remove('active'); });
            document.querySelectorAll('.step-dot').forEach(function(d) { d.classList.remove('active'); });
            document.getElementById('step' + step).classList.add('active');
            document.getElementById('dot' + step).classList.add('active');
            document.getElementById('alertBox').innerHTML = '';
        }

        function sendOtp() {
            var phone = document.getElementById('phone').value.trim();
            if (phone.length < 10) { showAlert('Please enter a valid phone number', 'danger'); return; }
            var btn = document.getElementById('sendOtpBtn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
            fetch('/api/otp/send', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                body: JSON.stringify({ phone: '0' + phone })
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-paper-plane"></i> Send OTP';
                if (data.success) {
                    verifiedPhone = '0' + phone;
                    goToStep(2);
                    document.querySelectorAll('.otp-input')[0].focus();
                } else {
                    showAlert(data.message || 'Failed to send OTP', 'danger');
                }
            })
            .catch(function() {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-paper-plane"></i> Send OTP';
                showAlert('Network error. Please try again.', 'danger');
            });
        }

        document.querySelectorAll('.otp-input').forEach(function(input, index, inputs) {
            input.addEventListener('input', function() {
                this.value = this.value.replace(/[^0-9]/g, '');
                if (this.value && index < inputs.length - 1) inputs[index + 1].focus();
            });
            input.addEventListener('keydown', function(e) {
                if (e.key === 'Backspace' && !this.value && index > 0) inputs[index - 1].focus();
            });
        });

        function verifyOtp() {
            var otp = '';
            document.querySelectorAll('.otp-input').forEach(function(i) { otp += i.value; });
            if (otp.length !== 6) { showAlert('Please enter the complete 6-digit OTP', 'danger'); return; }
            var btn = document.getElementById('verifyOtpBtn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verifying...';
            fetch('/api/otp/verify', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                body: JSON.stringify({ phone: verifiedPhone, otp: otp })
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-check-circle"></i> Verify';
                if (data.success) {
                    otpToken = data.token || '';
                    document.getElementById('confirmedPhone').value = verifiedPhone;
                    goToStep(3);
                } else {
                    showAlert(data.message || 'Invalid OTP', 'danger');
                }
            })
            .catch(function() {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-check-circle"></i> Verify';
                showAlert('Network error. Please try again.', 'danger');
            });
        }

        document.getElementById('registrationForm').addEventListener('submit', function(e) {
            e.preventDefault();
            var formData = new FormData(this);
            formData.append('phone', verifiedPhone);
            formData.append('otp_token', otpToken);
            var btn = this.querySelector('button[type="submit"]');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Registering...';
            fetch('/api/public/signup', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                body: formData
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-user-plus"></i> Complete Registration';
                if (data.success) {
                    window.location.href = data.redirect || '/signup/success?username=' + (data.username || '');
                } else {
                    showAlert(data.message || 'Registration failed', 'danger');
                }
            })
            .catch(function() {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-user-plus"></i> Complete Registration';
                showAlert('Network error. Please try again.', 'danger');
            });
        });
    </script>
</body>
</html>
