<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>@yield('title') - {{ config('app.name', 'ISP Billing') }}</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            html, body {
                background: linear-gradient(135deg, #1a1c2e 0%, #16213e 50%, #0f3460 100%);
                color: #e0e0e0;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                height: 100vh;
                overflow: hidden;
            }
            .container {
                display: flex;
                align-items: center;
                justify-content: center;
                height: 100vh;
                padding: 20px;
            }
            .error-card {
                text-align: center;
                max-width: 500px;
                background: rgba(255,255,255,0.05);
                border: 1px solid rgba(255,255,255,0.1);
                border-radius: 16px;
                padding: 48px 40px;
                backdrop-filter: blur(10px);
            }
            .error-code {
                font-size: 96px;
                font-weight: 700;
                color: #4fc3f7;
                line-height: 1;
                margin-bottom: 8px;
            }
            .error-title {
                font-size: 24px;
                font-weight: 600;
                color: #fff;
                margin-bottom: 12px;
            }
            .error-message {
                font-size: 16px;
                color: #9e9e9e;
                margin-bottom: 32px;
                line-height: 1.5;
            }
            .btn-home {
                display: inline-block;
                padding: 12px 32px;
                background: #4fc3f7;
                color: #1a1c2e;
                text-decoration: none;
                border-radius: 8px;
                font-weight: 600;
                font-size: 14px;
                transition: background 0.2s;
            }
            .btn-home:hover {
                background: #29b6f6;
            }
            .company-name {
                margin-top: 24px;
                font-size: 12px;
                color: #616161;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="error-card">
                <div class="error-code">@yield('code')</div>
                <div class="error-title">@yield('title')</div>
                <div class="error-message">@yield('message')</div>
                <a href="{{ url('/') }}" class="btn-home">Go to Homepage</a>
                <div class="company-name">{{ config('app.name', 'ISP Billing System') }}</div>
            </div>
        </div>
    </body>
</html>
