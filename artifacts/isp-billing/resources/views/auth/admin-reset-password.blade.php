@extends('layouts.auth')

@section('title', 'Reset Password')

@section('content')
<div class="login-box">
    <div class="logo">
        <a href="/">
            <div class="logo-container">
                <img src="{{ asset($siteLogo ?? 'images/logo.png') }}?t={{ time() }}" alt="Logo">
            </div>
            <h2 class="brand-title">{{ $companyName ?? 'SOFTYNEX' }}</h2>
            <p class="brand-tagline">{{ $companyTagline ?? 'Connecting You to the Future' }}</p>
        </a>
    </div>
    <div class="card">
        <div class="card-body">
            <p class="login-box-msg">Set New Password</p>

            <div style="background:#f0fdf4; border:1px solid #bbf7d0; border-radius:8px; padding:12px; margin-bottom:16px;">
                <p style="font-size:12px; color:#166534; margin:0;">
                    <i class="fas fa-check-circle"></i> Identity verified for <strong>{{ $user_name ?? '' }}</strong>
                    @if(!empty($user_email))
                        <br><small>Email: {{ $user_email }}</small>
                    @endif
                </p>
            </div>

            @if($errors->any())
                <div class="alert alert-danger">
                    @foreach($errors->all() as $error)
                        <p class="mb-0">{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form action="{{ route('admin.forgot.reset') }}" method="post">
                @csrf
                <div class="input-group mb-3">
                    <input type="password" name="password" class="form-control" placeholder="New Password" required minlength="6">
                    <div class="input-group-append">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                    </div>
                </div>
                <div class="input-group mb-3">
                    <input type="password" name="password_confirmation" class="form-control" placeholder="Confirm New Password" required minlength="6">
                    <div class="input-group-append">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-key"></i> Reset Password
                </button>
            </form>

            <hr>

            <div style="text-align:center;">
                <a href="{{ route('admin.forgot') }}" style="font-size:13px;">
                    <i class="fas fa-arrow-left"></i> Start Over
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
