<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1">
    <meta name="theme-color" content="#1e3c72">
    <title>Quick Pay – {{ $companyName }}</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        *{margin:0;padding:0;box-sizing:border-box;-webkit-tap-highlight-color:transparent;}
        body{
            font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;
            min-height:100vh;
            background:linear-gradient(160deg,#1e3c72 0%,#2a5298 100%);
            display:flex;align-items:center;justify-content:center;padding:20px;
        }
        .card{
            background:#fff;border-radius:24px;padding:48px 28px;
            width:100%;max-width:280px;text-align:center;
            box-shadow:0 20px 60px rgba(0,0,0,0.3);
        }
        .logo-wrap{margin-bottom:28px;}
        .logo-img{
            width:80px;height:80px;border-radius:16px;
            object-fit:contain;
            box-shadow:0 4px 16px rgba(0,0,0,0.12);
            background:#f8fafc;padding:4px;
        }
        .logo-fallback{
            width:80px;height:80px;border-radius:50%;
            background:linear-gradient(135deg,#1e3c72,#2a5298);
            display:flex;align-items:center;justify-content:center;
            margin:0 auto;
        }
        .logo-fallback i{font-size:34px;color:#fff;}
        h2{font-size:17px;font-weight:700;color:#1e3c72;margin-top:10px;margin-bottom:2px;}
        .sub{font-size:12px;color:#94a3b8;margin-bottom:32px;}
        .err{background:#fee2e2;color:#dc2626;padding:10px 14px;border-radius:10px;font-size:12px;margin-bottom:16px;text-align:left;display:flex;align-items:center;gap:8px;}
        .inp-wrap{position:relative;margin-bottom:14px;}
        .inp-wrap i{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#94a3b8;}
        input{
            width:100%;padding:10px 10px 10px 38px;
            border:2px solid #e2e8f0;border-radius:12px;
            font-size:14px;color:#1e293b;outline:none;background:#f8fafc;
        }
        input:focus{border-color:#2a5298;background:#fff;}
        input::placeholder{color:#cbd5e1;}
        button{
            width:100%;padding:11px;border:none;border-radius:12px;cursor:pointer;
            background:linear-gradient(135deg,#1e3c72,#2a5298);
            color:#fff;font-size:14px;font-weight:700;
            box-shadow:0 4px 16px rgba(30,60,114,0.35);
        }
        button:active{transform:scale(0.97);}
    </style>
</head>
<body>
<div class="card">
    <div class="logo-wrap">
        @if($siteLogo)
        <img src="{{ $siteLogo }}" alt="{{ $companyName }}" class="logo-img"
             onerror="this.style.display='none';document.getElementById('logo-fb').style.display='flex';">
        <div id="logo-fb" class="logo-fallback" style="display:none;margin:0 auto;">
            <i class="fas fa-wifi"></i>
        </div>
        @else
        <div class="logo-fallback"><i class="fas fa-wifi"></i></div>
        @endif
        <h2>{{ $companyName }}</h2>
        <p class="sub">Quick Bill Payment</p>
    </div>

    @if(session('error'))
    <div class="err"><i class="fas fa-exclamation-circle"></i>{{ session('error') }}</div>
    @endif

    <form action="{{ route('self-service.lookup') }}" method="POST">
        @csrf
        <div class="inp-wrap">
            <i class="fas fa-id-card"></i>
            <input type="text" name="identifier" placeholder="Enter Client ID (e.g. 00001)"
                   autocomplete="off" autofocus required value="{{ old('identifier') }}">
        </div>
        <button type="submit">Continue <i class="fas fa-arrow-right"></i></button>
    </form>
</div>
</body>
</html>
