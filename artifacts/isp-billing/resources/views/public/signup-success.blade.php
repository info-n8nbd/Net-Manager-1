<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Registration Successful - SOFTYNEX</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background: #f0f4f8; min-height: 100vh; display: flex; flex-direction: column; align-items: center; justify-content: center; }
        .success-card { background: white; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); padding: 40px; max-width: 480px; width: 90%; text-align: center; }
        .success-icon { width: 80px; height: 80px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; }
        .success-icon i { font-size: 36px; color: white; }
        h1 { color: #1e3c72; font-size: 24px; margin-bottom: 10px; }
        .username-box { background: #f0f4f8; border: 2px dashed #1e3c72; border-radius: 10px; padding: 15px; margin: 20px 0; }
        .username-box label { font-size: 13px; color: #64748b; display: block; margin-bottom: 5px; }
        .username-box .username { font-size: 24px; font-weight: 700; color: #1e3c72; letter-spacing: 1px; }
        .instructions { text-align: left; background: #e0f2fe; border-radius: 10px; padding: 20px; margin-top: 20px; }
        .instructions h4 { color: #075985; margin-bottom: 10px; font-size: 14px; }
        .instructions ul { list-style: none; padding: 0; }
        .instructions li { padding: 6px 0; color: #475569; font-size: 13px; }
        .instructions li i { color: #0ea5e9; margin-right: 8px; width: 16px; }
    </style>
</head>
<body>
    <div class="success-card">
        <div class="success-icon">
            <i class="fas fa-check"></i>
        </div>
        <h1>Registration Successful!</h1>
        <p style="color: #64748b;">Your account has been created successfully</p>

        <div class="username-box">
            <label>Your Username</label>
            <div class="username">{{ $username ?? request('username', 'N/A') }}</div>
        </div>

        <div class="instructions">
            <h4><i class="fas fa-info-circle"></i> What's Next?</h4>
            <ul>
                <li><i class="fas fa-check-circle"></i> Your connection will be activated within 24 hours</li>
                <li><i class="fas fa-check-circle"></i> A technician will visit for installation</li>
                <li><i class="fas fa-check-circle"></i> Use your username to login to the client portal</li>
                <li><i class="fas fa-check-circle"></i> Contact support if you have any questions</li>
            </ul>
        </div>
    </div>
</body>
</html>
