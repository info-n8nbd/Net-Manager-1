@extends('layouts.client')

@section('title', 'Request Package Change')

@section('content')
<div class="row">
    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-header py-2">
                <span style="font-size: 14px;"><i class="fas fa-exchange-alt mr-2"></i>Request Package Change</span>
            </div>
            <div class="card-body">
                <div class="alert alert-info" style="font-size: 13px;">
                    <i class="fas fa-info-circle mr-2"></i>
                    Select your desired package and submit the request. Our team will process it and contact you for confirmation.
                </div>

                <div class="card mb-4" style="border: 2px solid #1e3c72;">
                    <div class="card-header bg-gradient-primary text-white py-2">
                        <strong>Your Current Package</strong>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-6">
                                <small class="text-muted">Package Name</small>
                                <h5>{{ $currentPackage->name ?? 'N/A' }}</h5>
                            </div>
                            <div class="col-6 text-right">
                                <small class="text-muted">Monthly Bill</small>
                                <h5 class="text-success">৳{{ number_format($currentPackage->price ?? 0, 0) }}</h5>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-12">
                                <small class="text-muted">Speed</small>
                                <p class="mb-0">
                                    <i class="fas fa-download text-primary"></i> {{ $currentPackage->download_speed ?? 0 }} Mbps / 
                                    <i class="fas fa-upload text-info"></i> {{ $currentPackage->upload_speed ?? 0 }} Mbps
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <form action="{{ route('client.package-change.submit') }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label style="font-weight: 600;"><i class="fas fa-box-open mr-2"></i>Select New Package</label>
                        @if($packages->count() > 0)
                        <div class="row">
                            @foreach($packages as $package)
                            <div class="col-md-6 mb-3">
                                <div class="card package-option h-100" style="cursor: pointer; transition: all 0.3s;">
                                    <div class="card-body">
                                        <div class="custom-control custom-radio">
                                            <input type="radio" id="package{{ $package->id }}" name="package_id" value="{{ $package->id }}" class="custom-control-input" required>
                                            <label class="custom-control-label w-100" for="package{{ $package->id }}">
                                                <strong>{{ $package->name }}</strong>
                                                <span class="float-right text-success font-weight-bold">৳{{ number_format($package->price ?? 0, 0) }}/mo</span>
                                            </label>
                                        </div>
                                        <div class="mt-2" style="font-size: 12px;">
                                            <i class="fas fa-download text-primary"></i> {{ $package->download_speed }} Mbps / 
                                            <i class="fas fa-upload text-info"></i> {{ $package->upload_speed }} Mbps
                                        </div>
                                        @if($package->price > ($currentPackage->price ?? 0))
                                        <span class="badge badge-warning mt-2">Upgrade</span>
                                        @else
                                        <span class="badge badge-secondary mt-2">Downgrade</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        @else
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle mr-2"></i>No other packages available at this time.
                        </div>
                        @endif
                    </div>

                    <div class="form-group">
                        <label style="font-weight: 600;"><i class="fas fa-comment-alt mr-2"></i>Reason for Change (Optional)</label>
                        <textarea name="reason" class="form-control" rows="3" placeholder="Tell us why you want to change your package..."></textarea>
                    </div>

                    @if($packages->count() > 0)
                    <button type="submit" class="btn btn-primary btn-block" style="padding: 12px;">
                        <i class="fas fa-paper-plane mr-2"></i>Submit Package Change Request
                    </button>
                    @endif
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header bg-info py-2">
                <span class="text-white" style="font-size: 14px;"><i class="fas fa-question-circle mr-2"></i>How It Works</span>
            </div>
            <div class="card-body" style="font-size: 13px;">
                <ol class="mb-0 pl-3">
                    <li class="mb-2">Select your desired package</li>
                    <li class="mb-2">Submit the change request</li>
                    <li class="mb-2">Our team reviews your request</li>
                    <li class="mb-2">We'll contact you for confirmation</li>
                    <li>Package change takes effect on your next billing cycle</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<style>
.package-option:hover {
    border-color: #1e3c72 !important;
    box-shadow: 0 4px 15px rgba(30,60,114,0.2);
}
.package-option:has(.custom-control-input:checked) {
    border-color: #1e3c72 !important;
    background: #f0f7ff;
}
</style>
@endsection
