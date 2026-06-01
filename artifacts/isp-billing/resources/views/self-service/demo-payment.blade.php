<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demo Payment - SOFTYNEX</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { 
            min-height: 100vh; 
            background: linear-gradient(135deg, #e50695 0%, #d90166 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 20px;
            padding: 40px;
            max-width: 400px;
            width: 100%;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        .logo {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #e50695 0%, #d90166 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }
        .logo i { color: white; font-size: 36px; }
        h1 { color: #1e293b; font-size: 24px; margin-bottom: 10px; }
        .demo-badge {
            background: #fef3c7;
            color: #92400e;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 20px;
        }
        .amount {
            font-size: 48px;
            font-weight: 700;
            color: #e50695;
            margin: 20px 0;
        }
        .amount small { font-size: 20px; }
        .info {
            background: #f8fafc;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            text-align: left;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e2e8f0;
            font-size: 14px;
        }
        .info-row:last-child { border-bottom: none; }
        .info-row .label { color: #64748b; }
        .info-row .value { color: #1e293b; font-weight: 600; }
        .btn {
            width: 100%;
            padding: 16px;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
            margin-bottom: 10px;
        }
        .btn-confirm {
            background: linear-gradient(135deg, #e50695 0%, #d90166 100%);
            color: white;
        }
        .btn-confirm:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(229, 6, 149, 0.4); }
        .btn-cancel {
            background: #f1f5f9;
            color: #64748b;
        }
        .btn-cancel:hover { background: #e2e8f0; }
        .note {
            font-size: 12px;
            color: #94a3b8;
            margin-top: 20px;
        }
        .note i { color: #f59e0b; }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <i class="fas fa-mobile-alt"></i>
        </div>
        <h1>bKash Payment</h1>
        <span class="demo-badge"><i class="fas fa-info-circle"></i> Demo Mode</span>
        
        <div class="amount">
            <small>৳</small>{{ number_format($transaction->amount, 0) }}
        </div>

        <div class="info">
            <div class="info-row">
                <span class="label">Client ID</span>
                <span class="value">{{ $client->client_id }}</span>
            </div>
            <div class="info-row">
                <span class="label">Transaction ID</span>
                <span class="value">{{ $transaction->transaction_id }}</span>
            </div>
            <div class="info-row">
                <span class="label">Payment For</span>
                <span class="value">Internet Bill</span>
            </div>
        </div>

        <form action="{{ route('self-service.payment.demo.confirm', ['transaction_id' => $transaction->transaction_id]) }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-confirm">
                <i class="fas fa-check-circle"></i> Confirm Payment
            </button>
        </form>

        <a href="{{ $transaction->gateway_response['return_url'] ?? route('self-service.index') }}" class="btn btn-cancel" style="display: block; text-decoration: none;">
            <i class="fas fa-times"></i> Cancel
        </a>

        <p class="note">
            <i class="fas fa-exclamation-triangle"></i> This is a demo payment. In production, you will be redirected to bKash app.
        </p>
    </div>
</body>
</html>
