@extends('layouts.admin')

@section('title', 'Company Profile')
@section('breadcrumb')
<li class="breadcrumb-item active">Company Profile</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header"><h3 class="card-title">Company Profile</h3></div>
    <form action="{{ route('admin.settings.company.update') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group"><label>Company Name</label><input type="text" name="name" class="form-control" value="{{ $company->name }}"></div>
                    <div class="form-group"><label>Slogan / Tagline</label><input type="text" name="company_tagline" class="form-control" value="{{ \App\Models\SystemSetting::get('company_tagline', 'Connecting You to the Future') }}" placeholder="e.g. Connecting You to the Future"></div>
                    <div class="form-group"><label>Email</label><input type="email" name="email" class="form-control" value="{{ $company->email }}"></div>
                    <div class="form-group"><label>Phone</label><input type="text" name="phone" class="form-control" value="{{ $company->phone }}"></div>
                    <div class="form-group"><label>Alt Phone</label><input type="text" name="alt_phone" class="form-control" value="{{ $company->alt_phone }}"></div>
                    <div class="form-group"><label>Website</label><input type="text" name="website" class="form-control" value="{{ $company->website }}"></div>
                </div>
                <div class="col-md-6">
                    <div class="form-group"><label>Address</label><textarea name="address" class="form-control" rows="3">{{ $company->address }}</textarea></div>
                    <div class="form-group"><label>BTRC License No</label><input type="text" name="btrc_license" class="form-control" value="{{ $company->btrc_license }}"></div>
                    <div class="form-group"><label>BIN No</label><input type="text" name="bin_no" class="form-control" value="{{ $company->bin_no }}"></div>
                    <div class="form-group"><label>Trade License</label><input type="text" name="trade_license" class="form-control" value="{{ $company->trade_license }}"></div>
                </div>
            </div>
            <div class="form-group"><label>Invoice Footer</label><textarea name="invoice_footer" class="form-control" rows="2">{{ $company->invoice_footer }}</textarea></div>

            <hr>
            <h5 class="font-weight-bold"><i class="fas fa-image mr-1"></i> Company Logo</h5>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label><strong>Main Logo</strong> <small class="text-muted">(for dark backgrounds - sidebar, login)</small></label>
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" id="logo" name="logo" accept="image/*" onchange="previewImage(this, 'preview-logo')">
                            <label class="custom-file-label" for="logo">Choose file...</label>
                        </div>
                        <small class="form-text text-muted">Recommended: 300x120px. PNG, JPG, SVG, WebP. Max 2MB.</small>
                        @if($logo)
                        <div class="mt-2 p-2 bg-dark rounded d-flex align-items-center justify-content-between">
                            <div><small class="text-white mr-2">Current:</small><img src="{{ asset($logo) }}?t={{ time() }}" alt="Logo" style="max-height: 60px; max-width: 80%;"></div>
                            <form method="POST" action="{{ route('admin.settings.company.logo.remove', 'logo') }}" onsubmit="return confirm('Main Logo সরাতে চান?')">@csrf @method('DELETE')<button type="submit" class="btn btn-sm btn-danger ml-2" title="Remove Logo"><i class="fas fa-times"></i></button></form>
                        </div>
                        @endif
                        <div class="mt-2 p-2 bg-dark rounded" id="preview-logo-container" style="display: none;">
                            <small class="text-white">Preview:</small>
                            <img id="preview-logo" src="" alt="Preview" style="max-height: 60px; max-width: 100%;">
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label><strong>Light Logo</strong> <small class="text-muted">(optional - for light backgrounds)</small></label>
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" id="logo_light" name="logo_light" accept="image/*" onchange="previewImage(this, 'preview-logo-light')">
                            <label class="custom-file-label" for="logo_light">Choose file...</label>
                        </div>
                        <small class="form-text text-muted">Use for light backgrounds. Same format requirements.</small>
                        @if($logoLight)
                        <div class="mt-2 p-2 bg-light rounded border d-flex align-items-center justify-content-between">
                            <div><small class="text-dark mr-2">Current:</small><img src="{{ asset($logoLight) }}?t={{ time() }}" alt="Light Logo" style="max-height: 60px; max-width: 80%;"></div>
                            <form method="POST" action="{{ route('admin.settings.company.logo.remove', 'logo_light') }}" onsubmit="return confirm('Light Logo সরাতে চান?')">@csrf @method('DELETE')<button type="submit" class="btn btn-sm btn-danger ml-2" title="Remove Logo"><i class="fas fa-times"></i></button></form>
                        </div>
                        @endif
                        <div class="mt-2 p-2 bg-light rounded border" id="preview-logo-light-container" style="display: none;">
                            <small class="text-dark">Preview:</small>
                            <img id="preview-logo-light" src="" alt="Preview" style="max-height: 60px; max-width: 100%;">
                        </div>
                    </div>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-6">
                    <div class="form-group">
                        <label><strong>Favicon</strong> <small class="text-muted">(browser tab icon - 32x32 or 64x64px)</small></label>
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" id="favicon" name="favicon" accept="image/x-icon,image/png,image/svg+xml,image/gif" onchange="previewImage(this, 'preview-favicon')">
                            <label class="custom-file-label" for="favicon">Choose file...</label>
                        </div>
                        <small class="form-text text-muted">ICO, PNG, SVG, GIF. Recommended: 32x32 or 64x64px.</small>
                        @if($favicon)
                        <div class="mt-2 p-2 bg-light rounded border d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center"><small class="text-dark mr-2">Current:</small><img src="{{ asset($favicon) }}?t={{ time() }}" alt="Favicon" style="height: 32px; width: 32px;"></div>
                            <form method="POST" action="{{ route('admin.settings.company.logo.remove', 'favicon') }}" onsubmit="return confirm('Favicon সরাতে চান?')">@csrf @method('DELETE')<button type="submit" class="btn btn-sm btn-danger ml-2" title="Remove Favicon"><i class="fas fa-times"></i></button></form>
                        </div>
                        @endif
                        <div class="mt-2 p-2 bg-light rounded border d-flex align-items-center" id="preview-favicon-container" style="display: none;">
                            <small class="text-dark mr-2">Preview:</small>
                            <img id="preview-favicon" src="" alt="Preview" style="height: 32px; width: 32px;">
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label><strong>App Icon</strong> <small class="text-muted">(mobile/PWA icon - 192x192px)</small></label>
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" id="app_icon" name="app_icon" accept="image/png,image/svg+xml" onchange="previewImage(this, 'preview-app-icon')">
                            <label class="custom-file-label" for="app_icon">Choose file...</label>
                        </div>
                        <small class="form-text text-muted">PNG or SVG. Recommended: 192x192 or 512x512px.</small>
                        @if($appIcon)
                        <div class="mt-2 p-2 bg-light rounded border d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center"><small class="text-dark mr-2">Current:</small><img src="{{ asset($appIcon) }}?t={{ time() }}" alt="App Icon" style="height: 48px; width: 48px; border-radius: 8px;"></div>
                            <form method="POST" action="{{ route('admin.settings.company.logo.remove', 'app_icon') }}" onsubmit="return confirm('App Icon সরাতে চান?')">@csrf @method('DELETE')<button type="submit" class="btn btn-sm btn-danger ml-2" title="Remove App Icon"><i class="fas fa-times"></i></button></form>
                        </div>
                        @endif
                        <div class="mt-2 p-2 bg-light rounded border d-flex align-items-center" id="preview-app-icon-container" style="display: none;">
                            <small class="text-dark mr-2">Preview:</small>
                            <img id="preview-app-icon" src="" alt="Preview" style="height: 48px; width: 48px; border-radius: 8px;">
                        </div>
                    </div>
                </div>
            </div>

            <hr>
            <h5 class="font-weight-bold"><i class="fas fa-bullhorn mr-1"></i> Client Portal Ticker</h5>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Ticker Label</label>
                        <input type="text" name="ticker_label" class="form-control" value="{{ \App\Models\SystemSetting::get('ticker_label', 'SOFTYNEX') }}" placeholder="e.g. SOFTYNEX">
                        <small class="text-muted">Yellow button text</small>
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="form-group">
                        <label>Ticker Announcement</label>
                        <textarea name="ticker_text" class="form-control" rows="2" placeholder="Scrolling announcement text">{{ \App\Models\SystemSetting::get('ticker_text', '') }}</textarea>
                        <small class="text-muted">Scrolling announcement in client panel. Leave empty to hide ticker.</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer"><button type="submit" class="btn btn-primary">Save Changes</button></div>
    </form>
</div>
@endsection

@push('scripts')
<script>
function previewImage(input, previewId) {
    var preview = document.getElementById(previewId);
    var container = document.getElementById(previewId + '-container');
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            container.style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
        input.nextElementSibling.textContent = input.files[0].name;
    }
}
</script>
@endpush
