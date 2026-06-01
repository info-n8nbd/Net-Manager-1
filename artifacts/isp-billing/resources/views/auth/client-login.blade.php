@extends("layouts.auth")

@section("title", "Client Login")

@section("content")
<div class="login-box">
    <div class="logo">
        <a href="/">
            <div class="logo-container">
                <img src="{{ asset($siteLogo ?? 'images/logo.png') }}?t={{ time() }}" alt="{{ $companyName ?? 'SOFTYNEX' }} Logo">
            </div>
            <h2 class="brand-title">{{ $companyName ?? 'SOFTYNEX' }}</h2>
            <p class="brand-tagline">{{ $companyTagline ?? 'Smart Solutions. Seamless Growth' }}</p>
        </a>
    </div>
    <div class="card">
        <div class="card-body">
            <p class="login-box-msg">Client Portal</p>

            @if(session("status"))
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <p class="mb-0">{{ session("status") }}</p>
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <div>@foreach($errors->all() as $error)<p class="mb-0">{{ $error }}</p>@endforeach</div>
                </div>
            @endif

            <form action="{{ route("client.login.submit") }}" method="post" id="clientLoginForm">
                @csrf
                <input type="hidden" name="_lat" id="_lat">
                <input type="hidden" name="_lng" id="_lng">
                <input type="hidden" name="_location_address" id="_location_address">
                <div class="input-wrap">
                    <input type="text" name="username" class="form-control" placeholder="PPPoE Username"
                           value="{{ old("username") }}" required>
                    <span class="input-icon"><i class="fas fa-user"></i></span>
                </div>
                <div class="input-wrap">
                    <input type="password" name="pppoe_password" class="form-control"
                           placeholder="PPPoE Password" required>
                    <button type="button" class="toggle-pw"><i class="fas fa-eye"></i></button>
                </div>
                <div class="form-footer-row">
                    <div class="remember-wrap">
                        <input type="checkbox" id="remember" name="remember">
                        <label for="remember">Remember me</label>
                    </div>
                    <a href="{{ route("client.forgot") }}" class="forgot-link">Forgot password?</a>
                </div>
                <div id="_loc_status" style="font-size:11px;text-align:center;margin-bottom:8px;min-height:16px;"></div>
                <button type="submit" class="btn-signin">
                    <i class="fas fa-sign-in-alt"></i> Sign In
                </button>
            </form>

            <div class="divider">or</div>

            <div class="btn-secondary-row">
                <a href="{{ route("login") }}" class="btn-alt">
                    <i class="fas fa-user-shield"></i> Admin Login
                </a>
                <a href="{{ route("public.application.create") }}" class="btn-alt">
                    <i class="fas fa-user-plus"></i> Apply Now
                </a>
            </div>
        </div>
    </div>
</div>

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
@endsection
