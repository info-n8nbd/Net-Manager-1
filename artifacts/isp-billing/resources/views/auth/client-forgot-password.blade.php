@extends('layouts.auth')

@section('title', 'Forgot Password')

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
            <p class="login-box-msg">Forgot Password</p>
            <p style="font-size:12px; color:#64748b; text-align:center; margin-bottom:16px;">
                Enter your Account Number, Email, or Username along with your registered phone number to reset your password.
            </p>

            @if($errors->any())
                <div class="alert alert-danger">
                    @foreach($errors->all() as $error)
                        <p class="mb-0">{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form action="{{ route('client.forgot.verify') }}" method="post">
                @csrf
                <div class="input-group mb-3">
                    <input type="text" name="identifier" class="form-control" placeholder="Account No / Email / Username" value="{{ old('identifier') }}" required>
                    <div class="input-group-append">
                        <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                    </div>
                </div>
                <div class="input-group mb-3">
                    <input type="text" name="phone" class="form-control" placeholder="Registered Phone Number" value="{{ old('phone') }}" required>
                    <div class="input-group-append">
                        <span class="input-group-text"><i class="fas fa-phone"></i></span>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-search"></i> Verify & Continue
                </button>
            </form>

            <hr>

            <div style="text-align:center;">
                <a href="{{ route('client.login') }}" style="font-size:13px;">
                    <i class="fas fa-arrow-left"></i> Back to Client Login
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
