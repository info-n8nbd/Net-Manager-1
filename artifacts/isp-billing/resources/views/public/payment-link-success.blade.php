<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Payment Successful - SOFTYNEX</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background: #f0f4f8; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .card { background: white; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); padding: 40px; max-width: 450px; width: 90%; text-align: center; }
        .icon { width: 80px; height: 80px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; }
        .icon i { font-size: 36px; color: white; }
        h1 { color: #1e3c72; font-size: 22px; margin-bottom: 10px; }
        p { color: #64748b; font-size: 15px; line-height: 1.6; }
        .amount { font-size: 28px; font-weight: 700; color: #10b981; margin: 15px 0; }
        .details { background: #f0fdf4; border-radius: 10px; padding: 15px; margin: 20px 0; text-align: left; }
        .details .row { display: flex; justify-content: space-between; padding: 6px 0; font-size: 14px; }
        .details .row .label { color: #64748b; }
        .details .row .value { font-weight: 600; color: #065f46; }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">
            <i class="fas fa-check"></i>
        </div>
        <h1>Payment Successful!</h1>
        <p>Your payment has been processed successfully.</p>
        <div class="amount">৳{{ number_format($link->amount ?? 0, 2) }}</div>
        <div class="details">
            <div class="row">
                <span class="label">Client</span>
                <span class="value">{{ $link->client->name ?? '-' }}</span>
            </div>
            <div class="row">
                <span class="label">Description</span>
                <span class="value">{{ $link->description ?? '-' }}</span>
            </div>
            <div class="row">
                <span class="label">Transaction Date</span>
                <span class="value">{{ now()->format('d-m-y h:i A') }}</span>
            </div>
        </div>
        <p style="font-size: 13px; margin-top: 10px;">A confirmation has been sent to your phone. You can now close this page.</p>
    </div>
</body>
</html>
