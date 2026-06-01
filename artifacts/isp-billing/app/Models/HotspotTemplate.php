<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HotspotTemplate extends Model
{
    protected $fillable = [
        'name', 'slug', 'html_content', 'css_content', 'js_content',
        'logo_path', 'background_image', 'settings', 'is_active',
        'is_default', 'mikrotik_device_id', 'created_by',
    ];

    protected $casts = [
        'settings' => 'array',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    public function mikrotikDevice()
    {
        return $this->belongsTo(MikrotikDevice::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getRenderedHtml(): string
    {
        $html = $this->html_content;
        if ($this->css_content) {
            $html = str_replace('</head>', '<style>' . $this->css_content . '</style></head>', $html);
        }
        if ($this->js_content) {
            $html = str_replace('</body>', '<script>' . $this->js_content . '</script></body>', $html);
        }
        return $html;
    }

    public static function getDefaultTemplate(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Hotspot Login</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, sans-serif; background: linear-gradient(135deg, #1a365d 0%, #2d5a87 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .login-box { background: white; border-radius: 12px; padding: 40px; width: 380px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); }
        .logo { text-align: center; margin-bottom: 20px; }
        .logo img { max-width: 120px; }
        h2 { text-align: center; color: #1a365d; margin-bottom: 25px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; color: #555; font-size: 14px; }
        input[type="text"], input[type="password"] { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; }
        input:focus { outline: none; border-color: #2d5a87; }
        .btn { width: 100%; padding: 12px; background: #1a365d; color: white; border: none; border-radius: 6px; font-size: 16px; cursor: pointer; }
        .btn:hover { background: #2d5a87; }
        .error { color: red; text-align: center; margin-top: 10px; font-size: 13px; }
        .footer { text-align: center; margin-top: 15px; color: #999; font-size: 12px; }
    </style>
</head>
<body>
    <div class="login-box">
        <div class="logo"><img src="$(logo-url)" alt="Logo"></div>
        <h2>Internet Login</h2>
        <form name="sendin" action="$(link-login-only)" method="post">
            <input type="hidden" name="dst" value="$(link-orig)" />
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" value="$(username)" />
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" />
            </div>
            <button type="submit" class="btn">Connect</button>
            <p class="error">$(error)</p>
        </form>
        <p class="footer">Powered by Smart Billing</p>
    </div>
</body>
</html>
HTML;
    }
}
