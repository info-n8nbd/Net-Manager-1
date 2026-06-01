@extends('layouts.auth')

@section('title', 'Application Submitted')

@section('content')
<div class="application-container">
    <div class="logo">
        <a href="/">
            <div class="logo-container">
                <img src="{{ asset($siteLogo ?? 'images/logo.png') }}?t={{ time() }}" alt="{{ $companyName ?? 'SOFTYNEX' }}">
            </div>
            <p class="brand-title" style="font-size: 20px;">{{ $companyName ?? 'SOFTYNEX' }}</p>
            <p class="brand-tagline">{{ $companyTagline ?? 'Connecting You to the Future' }}</p>
        </a>
    </div>

    <div class="card">
        <div class="card-body text-center" style="padding: 30px;">
            <div class="mb-3">
                <i class="fas fa-check-circle text-success" style="font-size: 3.5rem;"></i>
            </div>
            <h3 class="text-success mb-3" style="font-weight: 700;">Thank You!</h3>
            <h5 class="mb-3" style="font-weight: 600;">Your application has been submitted successfully.</h5>
            
            <p class="text-muted mb-4" style="font-size: 14px;">
                We have received your connection request. Our team will review your application and contact you shortly at <strong>{{ $application->mobile_number }}</strong>.
            </p>

            <div class="mt-4">
                <a href="{{ route('login') }}" class="btn btn-primary btn-lg">
                    <i class="fas fa-home"></i> Back to Home
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
