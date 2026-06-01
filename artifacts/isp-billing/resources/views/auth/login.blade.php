@extends('layouts.auth')

@section('title', 'Login')

@section('content')
<div class="login-box">
    <div class="logo">
        <a href="/">
            <div class="logo-container">
                <img src="{{ asset($siteLogo ?? 'images/logo.png') }}?t={{ time() }}" alt="SOFTYNEX Logo">
            </div>
            <h2 class="brand-title">{{ $companyName ?? 'SOFTYNEX' }}</h2>
            <p class="brand-tagline">{{ $companyTagline ?? 'Connecting You to the Future' }}</p>
        </a>
    </div>
    <div class="card">
        <div class="card-body">
            <p class="login-box-msg">Sign in to start your session</p>

            @if(session('status'))
                <div class="alert alert-success">
                    <p class="mb-0">{{ session('status') }}</p>
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger">
                    @foreach($errors->all() as $error)
                        <p class="mb-0">{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form action="{{ route('login') }}" method="post" id="adminLoginForm">
                @csrf
                <input type="hidden" name="_lat" id="_lat">
                <input type="hidden" name="_lng" id="_lng">
                <input type="hidden" name="_location_address" id="_location_address">
                <div class="input-group mb-3">
                    <input type="text" name="email" class="form-control" placeholder="Email or Username" value="{{ old('email') }}" required autocomplete="email">
                    <div class="input-group-append">
                        <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                    </div>
                </div>
                <div class="input-group mb-3">
                    <input type="password" name="password" class="form-control" placeholder="Password" required autocomplete="current-password">
                    <div class="input-group-append">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                    </div>
                </div>
                <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:14px;">
                    <div class="icheck-primary">
                        <input type="checkbox" id="remember" name="remember">
                        <label for="remember">Remember Me</label>
                    </div>
                    <a href="{{ route('admin.forgot') }}" style="font-size:12px;">Forgot password?</a>
                </div>
                <div id="_loc_status" style="font-size:11px;text-align:center;margin-bottom:8px;min-height:16px;"></div>
                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-sign-in-alt"></i> Sign In
                </button>
            </form>

            <hr>

            <div style="display:flex; gap:10px;">
                <a href="{{ route('client.login') }}" class="btn btn-success" style="flex:1; white-space:nowrap; font-size:14px; padding:8px 12px;">
                    <i class="fas fa-user"></i> Client Login
                </a>
                <a href="{{ route('public.application.create') }}" class="btn btn-outline-primary" style="flex:1; white-space:nowrap; font-size:14px; padding:8px 12px;">
                    <i class="fas fa-user-plus"></i> Apply Now
                </a>
            </div>
        </div>
    </div>
</div>

@php $locationEnabled = \App\Models\SystemSetting::getGlobal('location_capture_enabled', '1'); @endphp
@if($locationEnabled !== '0')
<script>
(function () {
    var latF  = document.getElementById('_lat');
    var lngF  = document.getElementById('_lng');
    var addrF = document.getElementById('_location_address');
    var statusEl = document.getElementById('_loc_status');

    function setStatus(msg, color) {
        if (statusEl) { statusEl.textContent = msg; statusEl.style.color = color || '#888'; }
    }

    function reverseGeocode(lat, lng, cb) {
        fetch('https://nominatim.openstreetmap.org/reverse?format=json&lat=' + lat + '&lon=' + lng + '&zoom=14', {
            headers: { 'Accept-Language': 'en' }
        })
        .then(function (r) { return r.json(); })
        .then(function (d) { cb(d.display_name || (lat + ',' + lng)); })
        .catch(function () { cb(lat + ',' + lng); });
    }

    function onSuccess(pos) {
        var lat = pos.coords.latitude.toFixed(6);
        var lng = pos.coords.longitude.toFixed(6);
        latF.value  = lat;
        lngF.value  = lng;
        setStatus('\u{1F4CD} Location captured', '#28a745');
        reverseGeocode(lat, lng, function (addr) { addrF.value = addr; });
    }

    function onError(err) {
        if (err.code === 1) {
            setStatus('\u26A0 Location access denied. Enable location in browser settings.', '#e67e22');
        }
    }

    if (navigator.geolocation) {
        setStatus('Getting location\u2026', '#888');
        navigator.geolocation.getCurrentPosition(onSuccess, onError, {
            timeout: 10000, maximumAge: 60000, enableHighAccuracy: false
        });
    }
})();
</script>
@endif
@endsection
