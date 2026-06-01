@extends('layouts.admin')

@section('title', 'Logo Settings')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
<li class="breadcrumb-item active">Logo Settings</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-image mr-2"></i>Upload Logo</h3>
            </div>
            <form action="{{ route('admin.settings.logo.update') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-2"></i>
                        Upload your company logo here. The logo will be displayed across all panels including Admin, Technician, Client, Reseller, and Login pages.
                    </div>
                    
                    <div class="form-group">
                        <label for="logo"><strong>Main Logo</strong> (for dark backgrounds - sidebar, login page)</label>
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" id="logo" name="logo" accept="image/*" onchange="previewImage(this, 'preview-logo')">
                            <label class="custom-file-label" for="logo">Choose file...</label>
                        </div>
                        <small class="form-text text-muted">Recommended size: 300x120 pixels. Supported formats: PNG, JPG, GIF, SVG, WebP. Max size: 2MB.</small>
                        @if($logo)
                        <div class="mt-3 p-3 bg-dark rounded" id="current-logo-container">
                            <p class="text-white mb-2"><small>Current Logo:</small></p>
                            <img src="{{ asset($logo) }}?t={{ time() }}" alt="Current Logo" style="max-height: 80px; max-width: 100%;">
                        </div>
                        @endif
                        <div class="mt-3 p-3 bg-dark rounded" id="preview-logo-container" style="display: none;">
                            <p class="text-white mb-2"><small>New Logo Preview:</small></p>
                            <img id="preview-logo" src="" alt="Preview" style="max-height: 80px; max-width: 100%;">
                        </div>
                    </div>

                    <hr>

                    <div class="form-group">
                        <label for="logo_light"><strong>Light Logo</strong> (optional - for light backgrounds)</label>
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" id="logo_light" name="logo_light" accept="image/*" onchange="previewImage(this, 'preview-logo-light')">
                            <label class="custom-file-label" for="logo_light">Choose file...</label>
                        </div>
                        <small class="form-text text-muted">Use this if you need a different logo for light backgrounds.</small>
                        @if($logoLight)
                        <div class="mt-3 p-3 bg-light rounded border" id="current-logo-light-container">
                            <p class="text-dark mb-2"><small>Current Light Logo:</small></p>
                            <img src="{{ asset($logoLight) }}?t={{ time() }}" alt="Current Light Logo" style="max-height: 80px; max-width: 100%;">
                        </div>
                        @endif
                        <div class="mt-3 p-3 bg-light rounded border" id="preview-logo-light-container" style="display: none;">
                            <p class="text-dark mb-2"><small>New Light Logo Preview:</small></p>
                            <img id="preview-logo-light" src="" alt="Preview" style="max-height: 80px; max-width: 100%;">
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-1"></i> Save Logo
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-eye mr-2"></i>Logo Preview</h3>
            </div>
            <div class="card-body">
                <p class="text-muted mb-3">Your logo will appear in:</p>
                <ul class="list-unstyled">
                    <li class="mb-2"><i class="fas fa-check-circle text-success mr-2"></i>Admin Dashboard Sidebar</li>
                    <li class="mb-2"><i class="fas fa-check-circle text-success mr-2"></i>Technician Panel</li>
                    <li class="mb-2"><i class="fas fa-check-circle text-success mr-2"></i>Client Portal</li>
                    <li class="mb-2"><i class="fas fa-check-circle text-success mr-2"></i>Reseller Panel</li>
                    <li class="mb-2"><i class="fas fa-check-circle text-success mr-2"></i>Login/Auth Pages</li>
                    <li class="mb-2"><i class="fas fa-check-circle text-success mr-2"></i>Invoices & Reports</li>
                </ul>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-lightbulb mr-2"></i>Tips</h3>
            </div>
            <div class="card-body">
                <ul class="text-muted small">
                    <li class="mb-2">Use a PNG with transparent background for best results</li>
                    <li class="mb-2">Keep the logo simple and readable at small sizes</li>
                    <li class="mb-2">For dark sidebars, use a light-colored logo</li>
                    <li class="mb-2">Aspect ratio around 2.5:1 works best (e.g., 300x120)</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function previewImage(input, previewId) {
    const preview = document.getElementById(previewId);
    const container = document.getElementById(previewId + '-container');
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            container.style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
        
        const label = input.nextElementSibling;
        label.textContent = input.files[0].name;
    }
}
</script>
@endpush
