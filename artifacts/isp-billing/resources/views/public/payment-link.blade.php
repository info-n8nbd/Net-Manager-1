<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Payment - SOFTYNEX</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background: #f0f4f8; min-height: 100vh; }
        .header { background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); padding: 30px 20px; text-align: center; color: white; }
        .header h1 { font-size: 24px; font-weight: 700; }
        .container { max-width: 500px; margin: -20px auto 40px; padding: 0 20px; }
        .card { background: white; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); padding: 30px; }
        .amount-display { text-align: center; padding: 20px; margin-bottom: 20px; }
        .amount-display .label { font-size: 14px; color: #64748b; }
        .amount-display .amount { font-size: 36px; font-weight: 700; color: #1e3c72; }
        .info-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #f0f4f8; font-size: 14px; }
        .info-row:last-child { border-bottom: none; }
        .info-row .label { color: #64748b; }
        .info-row .value { font-weight: 600; color: #1e3c72; }
        .payment-methods { margin: 25px 0; }
        .payment-methods h4 { font-size: 15px; color: #1e3c72; margin-bottom: 12px; }
        .method-option { display: flex; align-items: center; padding: 14px; border: 2px solid #e2e8f0; border-radius: 10px; margin-bottom: 10px; cursor: pointer; transition: all 0.2s; }
        .method-option:hover { border-color: #1e3c72; background: #f8fafc; }
        .method-option.selected { border-color: #1e3c72; background: #eff6ff; }
        .method-option input[type="radio"] { margin-right: 12px; accent-color: #1e3c72; }
        .method-option .method-name { font-weight: 600; color: #334155; }
        .btn { width: 100%; padding: 14px; border: none; border-radius: 10px; font-size: 16px; font-weight: 600; cursor: pointer; transition: all 0.3s; }
        .btn-primary { background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); color: white; }
        .btn-primary:hover { box-shadow: 0 4px 15px rgba(30,60,114,0.4); }
        .btn:disabled { opacity: 0.6; cursor: not-allowed; }
    </style>
</head>
<body>
    <div class="header">
        <h1><i class="fas fa-wifi"></i> SOFTYNEX</h1>
        <p style="opacity: 0.8; font-size: 14px;">Secure Payment</p>
    </div>
    <div class="container">
        <div class="card">
            <div class="amount-display">
                <div class="label">Amount Due</div>
                <div class="amount">৳{{ number_format($link->amount, 2) }}</div>
            </div>
            <div class="info-row">
                <span class="label">Client</span>
                <span class="value">{{ $link->client->name ?? '-' }}</span>
            </div>
            <div class="info-row">
                <span class="label">Description</span>
                <span class="value">{{ $link->description }}</span>
            </div>

            <form action="{{ route('public.payment-link.pay', $link->token) }}" method="POST">
                @csrf
                <div class="payment-methods">
                    <h4>Select Payment Method</h4>
                    <label class="method-option">
                        <input type="radio" name="payment_method" value="bkash" required>
                        <span class="method-name"><i class="fas fa-mobile-alt" style="color: #E2136E;"></i> bKash</span>
                    </label>
                    <label class="method-option">
                        <input type="radio" name="payment_method" value="nagad">
                        <span class="method-name"><i class="fas fa-mobile-alt" style="color: #F26522;"></i> Nagad</span>
                    </label>
                    <label class="method-option">
                        <input type="radio" name="payment_method" value="rocket">
                        <span class="method-name"><i class="fas fa-mobile-alt" style="color: #8C3494;"></i> Rocket</span>
                    </label>
                    <label class="method-option">
                        <input type="radio" name="payment_method" value="card">
                        <span class="method-name"><i class="fas fa-credit-card" style="color: #1e3c72;"></i> Card Payment</span>
                    </label>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-lock"></i> Pay ৳{{ number_format($link->amount, 2) }}
                </button>
            </form>
        </div>
    </div>

    <script>
        document.querySelectorAll('.method-option').forEach(function(option) {
            option.addEventListener('click', function() {
                document.querySelectorAll('.method-option').forEach(function(o) { o.classList.remove('selected'); });
                this.classList.add('selected');
                this.querySelector('input[type="radio"]').checked = true;
            });
        });
    </script>
</body>
</html>
