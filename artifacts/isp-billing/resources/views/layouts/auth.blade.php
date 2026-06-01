<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, viewport-fit=cover">
    <meta name="theme-color" content="#1e3c72">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="format-detection" content="telephone=no">
    <title>@yield('title') - {{ $companyName ?? 'SOFTYNEX' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        *, *::before, *::after {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        html {
            font-size: 16px;
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
        }
        body {
            font-family: 'Poppins', 'Noto Sans Bengali', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #1e3c72;
            min-height: 100vh;
            min-height: 100dvh;
            overflow-x: hidden;
            line-height: 1.5;
            color: #333;
        }
        
        .wrapper {
            position: relative;
            z-index: 10;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            min-height: 100dvh;
            justify-content: center;
            align-items: center;
            padding: 24px 16px;
            padding-top: max(24px, env(safe-area-inset-top));
            padding-bottom: max(24px, env(safe-area-inset-bottom));
        }
        
        .login-box {
            width: 100%;
            max-width: 380px;
            margin: 0 auto;
        }
        
        .application-container {
            width: 100%;
            max-width: 480px;
            margin: 0 auto;
        }
        
        .logo {
            text-align: center;
            margin-bottom: 16px;
        }
        .logo a {
            text-decoration: none;
            display: inline-block;
        }
        .logo-container {
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 8px;
        }
        .logo-container img {
            max-width: 100%;
            width: 150px;
            height: auto;
        }
        .logo .brand-title {
            color: #fff;
            font-size: 20px;
            font-weight: 700;
            letter-spacing: 2px;
            margin: 0 0 2px 0;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }
        .logo .brand-tagline {
            color: rgba(255, 255, 255, 0.85);
            font-size: 10px;
            font-weight: 400;
            font-style: italic;
            letter-spacing: 0.5px;
            margin: 0;
        }
        .brand-header {
            text-align: center;
            margin-bottom: 24px;
            padding: 20px;
        }
        .brand-header a {
            text-decoration: none;
            display: inline-block;
        }
        .brand-title {
            color: #fff;
            font-size: 32px;
            font-weight: 700;
            letter-spacing: 2px;
            margin: 0 0 8px 0;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }
        .brand-header .brand-tagline {
            color: rgba(255, 255, 255, 0.9);
            font-size: 14px;
            font-weight: 400;
            font-style: italic;
            letter-spacing: 0.5px;
        }
        .login-footer {
            text-align: center;
            margin-top: 24px;
            padding: 16px;
        }
        .login-footer .brand-title {
            color: #fff;
            font-size: 24px;
            font-weight: 700;
            letter-spacing: 2px;
            margin: 0 0 6px 0;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }
        .login-footer .brand-tagline {
            color: rgba(255, 255, 255, 0.85);
            font-size: 13px;
            font-weight: 400;
            font-style: italic;
            letter-spacing: 0.5px;
            margin: 0;
        }
        .logo-circle {
            width: 80px;
            height: 80px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 12px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.2);
        }
        .logo-circle i {
            font-size: 36px;
            color: #1e3c72;
        }
        .brand-name {
            color: #fff;
            font-size: 24px;
            font-weight: 700;
            letter-spacing: 1px;
        }
        .brand-tagline {
            color: rgba(255, 255, 255, 0.8);
            font-size: 12px;
            letter-spacing: 0.5px;
            margin-top: 4px;
        }
        
        .card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
            overflow: hidden;
            position: relative;
        }
        .card-body {
            padding: 18px;
        }
        .card-header {
            background: #1e3c72;
            border: none;
            text-align: center;
            padding: 12px 18px;
            color: white;
        }
        .card-header h3, .card-title {
            color: white;
            font-size: 16px;
            font-weight: 600;
            margin: 0;
            width: 100%;
        }
        
        .login-box-msg {
            color: #1e3c72;
            font-weight: 600;
            font-size: 14px;
            text-align: center;
            margin-bottom: 16px;
        }
        
        .form-group {
            margin-bottom: 10px;
        }
        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #475569;
            margin-bottom: 6px;
        }
        .form-group label .text-danger {
            color: #ef4444;
        }
        .form-control {
            width: 100%;
            padding: 10px 14px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
            color: #333;
            background: #f8fafc;
            transition: all 0.2s;
            -webkit-appearance: none;
            appearance: none;
        }
        .form-control:focus {
            outline: none;
            border-color: #1e3c72;
            background: white;
            box-shadow: 0 0 0 3px rgba(30, 60, 114, 0.1);
        }
        .form-control::placeholder {
            color: #94a3b8;
        }
        
        .input-group {
            display: flex;
            width: 100%;
        }
        .input-group .form-control {
            border-radius: 8px 0 0 8px;
            flex: 1;
            min-width: 0;
        }
        .input-group-append {
            display: flex;
        }
        .input-group-text {
            background: #1e3c72;
            color: #fff;
            border: none;
            border-radius: 0 8px 8px 0;
            padding: 0 14px;
            display: flex;
            align-items: center;
            font-size: 13px;
        }
        
        select.form-control {
            background: #f8fafc url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23333' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e") no-repeat right 12px center/12px;
            padding-right: 36px;
            cursor: pointer;
        }
        textarea.form-control {
            resize: vertical;
            min-height: 70px;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 10px 18px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            transition: all 0.2s;
            -webkit-tap-highlight-color: transparent;
            text-decoration: none;
        }
        .btn-block {
            width: 100%;
        }
        .btn-lg {
            padding: 14px 24px;
            font-size: 16px;
        }
        .btn-primary {
            background: #1e3c72;
            color: #fff;
            box-shadow: 0 4px 15px rgba(30, 60, 114, 0.3);
        }
        .btn-primary:hover, .btn-primary:focus, .btn-primary:active {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(30, 60, 114, 0.4);
        }
        .btn-success {
            background: #28a745;
            color: #fff;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
        }
        .btn-success:hover, .btn-success:focus, .btn-success:active {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
        }
        .btn-outline-primary {
            background: transparent;
            border: 2px solid #1e3c72;
            color: #1e3c72;
        }
        .btn-outline-primary:hover, .btn-outline-primary:active {
            background: #1e3c72;
            color: #fff;
        }
        
        .row {
            display: flex;
            flex-wrap: wrap;
            margin: 0 -6px;
        }
        .col-12 {
            flex: 0 0 100%;
            max-width: 100%;
            padding: 0 6px;
        }
        .col-md-4, .col-md-6 {
            flex: 0 0 100%;
            max-width: 100%;
            padding: 0 6px;
        }
        
        .icheck-primary {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .icheck-primary input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: #1e3c72;
            cursor: pointer;
        }
        .icheck-primary label {
            font-size: 14px;
            color: #475569;
            margin: 0;
            cursor: pointer;
        }
        
        .alert {
            padding: 14px 16px;
            border-radius: 10px;
            margin-bottom: 16px;
            font-size: 13px;
        }
        .alert-danger {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #b91c1c;
        }
        .alert-info {
            background: #e0f2fe;
            border: 1px solid #bae6fd;
            color: #075985;
        }
        .alert-success {
            background: #d1fae5;
            border: 1px solid #a7f3d0;
            color: #065f46;
        }
        .alert ul {
            margin: 0;
            padding-left: 18px;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .badge-success { background: #d1fae5; color: #065f46; }
        .badge-info { background: #e0f2fe; color: #075985; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
        .badge-secondary { background: #f1f5f9; color: #475569; }
        
        .table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        .table th, .table td {
            padding: 12px 14px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: top;
        }
        .table th {
            font-weight: 600;
            color: #475569;
            background: #f8fafc;
        }
        
        .bg-light { background: #f8fafc; }
        .text-center { text-align: center; }
        .text-muted { color: #64748b !important; }
        .text-primary { color: #1e3c72 !important; }
        .text-success { color: #10b981 !important; }
        .text-danger { color: #ef4444 !important; }
        
        .mb-0 { margin-bottom: 0 !important; }
        .mb-1 { margin-bottom: 4px !important; }
        .mb-2 { margin-bottom: 8px !important; }
        .mb-3 { margin-bottom: 16px !important; }
        .mb-4 { margin-bottom: 24px !important; }
        .mt-2 { margin-top: 8px !important; }
        .mt-3 { margin-top: 16px !important; }
        .mt-4 { margin-top: 24px !important; }
        .mx-2 { margin-left: 8px !important; margin-right: 8px !important; }
        .p-3 { padding: 16px !important; }
        .rounded { border-radius: 10px !important; }
        .small { font-size: 12px; }
        
        a {
            color: #1e3c72;
            text-decoration: none;
            font-weight: 500;
        }
        a:hover {
            color: #34495e;
            text-decoration: underline;
        }
        hr {
            border: none;
            border-top: 1px solid #e2e8f0;
            margin: 14px 0;
        }
        
        .copyright {
            text-align: center;
            padding: 14px;
            color: rgba(255, 255, 255, 0.7);
            font-size: 11px;
            margin-top: 16px;
        }
        .copyright a {
            color: rgba(255, 255, 255, 0.9);
        }
        .copyright a:hover {
            color: white;
        }
        .copyright p {
            margin: 4px 0;
        }
        
        @media (min-width: 480px) {
            .wrapper {
                padding: 32px 20px;
            }
            .logo-circle {
                width: 90px;
                height: 90px;
            }
            .logo-circle img {
                width: 55px;
            }
            .logo-circle i {
                font-size: 40px;
            }
            .brand-name {
                font-size: 26px;
            }
            .card-body {
                padding: 32px;
            }
        }
        
        @media (min-width: 640px) {
            .col-md-6 {
                flex: 0 0 50%;
                max-width: 50%;
            }
            .col-md-4 {
                flex: 0 0 33.333%;
                max-width: 33.333%;
            }
        }
        
        @media (max-width: 359px) {
            html {
                font-size: 14px;
            }
            .card-body {
                padding: 20px 16px;
            }
        }

        @keyframes authFadeInUp {
            from {
                opacity: 0;
                transform: translateY(18px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-box,
        .application-container {
            animation: authFadeInUp 0.6s cubic-bezier(0.22, 1, 0.36, 1) both;
        }

        .login-box .card,
        .application-container .card {
            animation: authFadeInUp 0.6s cubic-bezier(0.22, 1, 0.36, 1) 0.15s both;
        }

        .login-box .copyright,
        .application-container .copyright {
            animation: authFadeInUp 0.5s cubic-bezier(0.22, 1, 0.36, 1) 0.3s both;
        }

        .form-control {
            transition: border-color 0.25s ease, background-color 0.25s ease, box-shadow 0.25s ease;
        }
        .form-control:focus {
            box-shadow: 0 0 0 3px rgba(30, 60, 114, 0.12), 0 1px 3px rgba(0, 0, 0, 0.06);
        }

        .input-group-text {
            transition: background 0.25s ease;
        }
        .input-group:focus-within .input-group-text {
            background: #34495e;
        }

        .btn {
            transition: transform 0.2s cubic-bezier(0.22, 1, 0.36, 1), box-shadow 0.2s ease, opacity 0.15s ease;
        }
        .btn:active {
            transform: translateY(0) scale(0.97) !important;
            opacity: 0.92;
        }


        /* Client Login Custom Styles */
        .input-wrap {
            position: relative;
            margin-bottom: 12px;
        }
        .input-wrap .form-control {
            padding-right: 44px;
        }
        .input-wrap .input-icon {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            pointer-events: none;
            font-size: 14px;
        }
        .input-wrap .toggle-pw {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #94a3b8;
            cursor: pointer;
            padding: 2px;
            display: flex;
            align-items: center;
            font-size: 14px;
            line-height: 1;
        }
        .input-wrap .toggle-pw:hover { color: #1e3c72; }
        .btn-signin {
            width: 100%;
            padding: 12px;
            background: #1e3c72;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 4px;
            transition: all 0.2s;
        }
        .btn-signin:hover { background: #16305e; transform: translateY(-1px); }
        .btn-signin:active { transform: translateY(0); }
        .form-footer-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 14px;
        }
        .remember-wrap {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: #475569;
        }
        .remember-wrap input[type="checkbox"] {
            width: 16px;
            height: 16px;
            accent-color: #1e3c72;
        }
        .forgot-link {
            font-size: 12px;
            color: #1e3c72;
            font-weight: 500;
        }
        .divider {
            text-align: center;
            position: relative;
            margin: 16px 0;
            color: #94a3b8;
            font-size: 12px;
        }
        .divider::before, .divider::after {
            content: "";
            position: absolute;
            top: 50%;
            width: 43%;
            height: 1px;
            background: #e2e8f0;
        }
        .divider::before { left: 0; }
        .divider::after { right: 0; }
        .btn-secondary-row {
            display: flex;
            gap: 10px;
        }
        .btn-alt {
            flex: 1;
            padding: 9px 12px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            text-decoration: none;
            transition: all 0.2s;
            border: 2px solid #1e3c72;
            color: #1e3c72;
            background: transparent;
            white-space: nowrap;
        }
        .btn-alt:first-child {
            background: #28a745;
            border-color: #28a745;
            color: #fff;
        }
        .btn-alt:hover { transform: translateY(-1px); opacity: 0.92; text-decoration: none; }

        #particles-canvas {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
            pointer-events: none;
        }

        .wrapper {
            position: relative;
            z-index: 10;
        }

        @media (prefers-reduced-motion: reduce) {
            .login-box,
            .application-container,
            .login-box .card,
            .application-container .card,
            .login-box .copyright,
            .application-container .copyright {
                animation: none;
            }
            #particles-canvas {
                display: none;
            }
        }
    </style>
    <link rel="stylesheet" href="{{ asset('css/ui-lite.css') . '?v=' . time() }}">
    <style>:root { --theme-p1: #1e3c72; --theme-p2: #2a5298; }</style>
</head>
<body>
    <canvas id="particles-canvas"></canvas>
    <div class="wrapper">
        @yield('content')
        <div class="copyright">
            <p>&copy; {{ date('Y') }} <a href="/">{{ $companyName ?? 'SOFTYNEX' }}</a>. All Rights Reserved.</p>
        </div>
    </div>
    @stack('scripts')
    <script>
    (function() {
        var c = document.getElementById('particles-canvas');
        if (!c || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
        var animationOpacityCheck = {{ $loginAnimationOpacity ?? 40 }};
        if (animationOpacityCheck <= 0) { c.style.display = 'none'; return; }
        var gO = animationOpacityCheck / 100;
        var ctx = c.getContext('2d');
        var t = 0;
        var stars = [];
        var shootingStars = [];
        var bubbles = [];

        function resize() {
            c.width = window.innerWidth;
            c.height = window.innerHeight;
            initStars();
            initBubbles();
        }

        function initStars() {
            stars = [];
            var count = Math.max(60, Math.min(180, Math.floor(c.width * c.height / 8000)));
            for (var i = 0; i < count; i++) stars.push(createStar());
        }

        function createStar() {
            return {
                x: Math.random() * c.width,
                y: Math.random() * c.height,
                size: 0.5 + Math.random() * 2,
                twinkleSpeed: 1 + Math.random() * 3,
                phase: Math.random() * Math.PI * 2,
                brightness: 0.3 + Math.random() * 0.7
            };
        }

        function initBubbles() {
            bubbles = [];
            var count = Math.max(8, Math.min(20, Math.floor(c.width / 80)));
            for (var i = 0; i < count; i++) bubbles.push(createBubble());
        }

        function createBubble() {
            var colors = [
                { r: 79, g: 195, b: 247 },
                { r: 0, g: 230, b: 180 },
                { r: 160, g: 120, b: 255 },
                { r: 255, g: 120, b: 200 },
                { r: 0, g: 200, b: 220 }
            ];
            var col = colors[Math.floor(Math.random() * colors.length)];
            return {
                x: Math.random() * c.width,
                y: Math.random() * c.height,
                radius: 20 + Math.random() * 80,
                vx: (Math.random() - 0.5) * 0.3,
                vy: -0.1 - Math.random() * 0.3,
                color: col,
                phase: Math.random() * Math.PI * 2,
                opacity: (0.03 + Math.random() * 0.06) * gO
            };
        }

        function createShootingStar() {
            var fromRight = Math.random() < 0.5;
            return {
                x: fromRight ? c.width + 20 : -20,
                y: Math.random() * c.height * 0.6,
                angle: fromRight ? Math.PI + (Math.random() * 0.5 - 0.25) : (Math.random() * 0.5 - 0.25),
                speed: 4 + Math.random() * 6,
                length: 60 + Math.random() * 100,
                life: 1,
                decay: 0.008 + Math.random() * 0.008,
                width: 1 + Math.random() * 1.5
            };
        }

        function drawAuroraWaves() {
            var waveCount = 4;
            var waveColors = [
                [79, 195, 247],
                [0, 230, 180],
                [160, 120, 255],
                [0, 200, 220]
            ];

            for (var w = 0; w < waveCount; w++) {
                var col = waveColors[w];
                var baseY = c.height * (0.3 + w * 0.15);
                var amplitude = 30 + w * 15;
                var frequency = 0.003 - w * 0.0004;
                var speed = 0.4 + w * 0.15;
                var waveAlpha = (0.04 + w * 0.01) * gO;

                ctx.beginPath();
                ctx.moveTo(0, c.height);

                for (var x = 0; x <= c.width; x += 3) {
                    var y = baseY
                        + Math.sin(x * frequency + t * speed) * amplitude
                        + Math.sin(x * frequency * 2.3 + t * speed * 1.4) * amplitude * 0.4
                        + Math.cos(x * frequency * 0.7 + t * speed * 0.6) * amplitude * 0.3;
                    if (x === 0) ctx.moveTo(x, y);
                    else ctx.lineTo(x, y);
                }

                ctx.lineTo(c.width, c.height);
                ctx.lineTo(0, c.height);
                ctx.closePath();

                var grad = ctx.createLinearGradient(0, baseY - amplitude * 2, 0, baseY + amplitude * 3);
                grad.addColorStop(0, 'rgba(' + col[0] + ',' + col[1] + ',' + col[2] + ', 0)');
                grad.addColorStop(0.3, 'rgba(' + col[0] + ',' + col[1] + ',' + col[2] + ',' + waveAlpha * 1.5 + ')');
                grad.addColorStop(0.6, 'rgba(' + col[0] + ',' + col[1] + ',' + col[2] + ',' + waveAlpha + ')');
                grad.addColorStop(1, 'rgba(' + col[0] + ',' + col[1] + ',' + col[2] + ', 0)');
                ctx.fillStyle = grad;
                ctx.fill();

                ctx.beginPath();
                for (var x = 0; x <= c.width; x += 3) {
                    var y = baseY
                        + Math.sin(x * frequency + t * speed) * amplitude
                        + Math.sin(x * frequency * 2.3 + t * speed * 1.4) * amplitude * 0.4
                        + Math.cos(x * frequency * 0.7 + t * speed * 0.6) * amplitude * 0.3;
                    if (x === 0) ctx.moveTo(x, y);
                    else ctx.lineTo(x, y);
                }
                ctx.strokeStyle = 'rgba(' + col[0] + ',' + col[1] + ',' + col[2] + ',' + waveAlpha * 3 + ')';
                ctx.lineWidth = 1.5;
                ctx.stroke();
            }
        }

        function drawStars() {
            for (var i = 0; i < stars.length; i++) {
                var s = stars[i];
                var twinkle = (Math.sin(t * s.twinkleSpeed + s.phase) + 1) * 0.5;
                var alpha = s.brightness * (0.3 + twinkle * 0.7) * gO;
                var size = s.size * (0.7 + twinkle * 0.3);

                ctx.beginPath();
                ctx.arc(s.x, s.y, size, 0, Math.PI * 2);
                ctx.fillStyle = 'rgba(255, 255, 255, ' + alpha + ')';
                ctx.fill();

                if (s.size > 1.5 && twinkle > 0.7) {
                    ctx.save();
                    ctx.globalAlpha = alpha * 0.3;
                    ctx.beginPath();
                    ctx.moveTo(s.x - size * 3, s.y);
                    ctx.lineTo(s.x + size * 3, s.y);
                    ctx.moveTo(s.x, s.y - size * 3);
                    ctx.lineTo(s.x, s.y + size * 3);
                    ctx.strokeStyle = '#fff';
                    ctx.lineWidth = 0.5;
                    ctx.stroke();
                    ctx.restore();
                }
            }
        }

        function drawBubbles() {
            for (var i = 0; i < bubbles.length; i++) {
                var b = bubbles[i];
                b.x += b.vx + Math.sin(t * 0.5 + b.phase) * 0.2;
                b.y += b.vy;
                if (b.y < -b.radius * 2) { b.y = c.height + b.radius; b.x = Math.random() * c.width; }
                if (b.x < -b.radius) b.x = c.width + b.radius;
                if (b.x > c.width + b.radius) b.x = -b.radius;

                var pulse = 0.8 + Math.sin(t * 1.5 + b.phase) * 0.2;
                var grad = ctx.createRadialGradient(b.x, b.y, 0, b.x, b.y, b.radius);
                grad.addColorStop(0, 'rgba(' + b.color.r + ',' + b.color.g + ',' + b.color.b + ',' + b.opacity * pulse * 1.5 + ')');
                grad.addColorStop(0.5, 'rgba(' + b.color.r + ',' + b.color.g + ',' + b.color.b + ',' + b.opacity * pulse * 0.5 + ')');
                grad.addColorStop(1, 'rgba(' + b.color.r + ',' + b.color.g + ',' + b.color.b + ', 0)');
                ctx.beginPath();
                ctx.arc(b.x, b.y, b.radius * pulse, 0, Math.PI * 2);
                ctx.fillStyle = grad;
                ctx.fill();
            }
        }

        function drawShootingStars() {
            if (Math.random() < 0.005 && shootingStars.length < 3) {
                shootingStars.push(createShootingStar());
            }

            for (var i = shootingStars.length - 1; i >= 0; i--) {
                var ss = shootingStars[i];
                ss.x += Math.cos(ss.angle) * ss.speed;
                ss.y += Math.sin(ss.angle) * ss.speed;
                ss.life -= ss.decay;

                if (ss.life <= 0 || ss.x < -200 || ss.x > c.width + 200 || ss.y > c.height + 100) {
                    shootingStars.splice(i, 1);
                    continue;
                }

                var tailX = ss.x - Math.cos(ss.angle) * ss.length * ss.life;
                var tailY = ss.y - Math.sin(ss.angle) * ss.length * ss.life;

                var grad = ctx.createLinearGradient(tailX, tailY, ss.x, ss.y);
                grad.addColorStop(0, 'rgba(255, 255, 255, 0)');
                grad.addColorStop(0.6, 'rgba(79, 195, 247, ' + ss.life * 0.3 * gO + ')');
                grad.addColorStop(1, 'rgba(255, 255, 255, ' + ss.life * 0.9 * gO + ')');

                ctx.beginPath();
                ctx.moveTo(tailX, tailY);
                ctx.lineTo(ss.x, ss.y);
                ctx.strokeStyle = grad;
                ctx.lineWidth = ss.width * ss.life;
                ctx.stroke();

                ctx.save();
                ctx.shadowColor = 'rgba(79, 195, 247, 0.8)';
                ctx.shadowBlur = 10 * ss.life;
                ctx.beginPath();
                ctx.arc(ss.x, ss.y, ss.width * 1.5 * ss.life, 0, Math.PI * 2);
                ctx.fillStyle = 'rgba(255, 255, 255, ' + ss.life * 0.9 * gO + ')';
                ctx.fill();
                ctx.restore();
            }
        }

        resize();
        window.addEventListener('resize', resize);

        function animate() {
            t += 0.016;
            ctx.clearRect(0, 0, c.width, c.height);

            drawBubbles();
            drawAuroraWaves();
            drawStars();
            drawShootingStars();

            requestAnimationFrame(animate);
        }
        animate();
    })();
    </script>
<script src="/js/new-tab-links.js?v={{ time() }}"></script>
@include('components.flatpickr-datepicker')
</body>
</html>
