@extends('layouts.admin')

@section('title', 'Footer Settings')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('superadmin.dashboard') }}">Super Admin</a></li>
    <li class="breadcrumb-item active">Footer Settings</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">

        {{-- Success Alert --}}
        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
        @endif

        {{-- Info Banner --}}
        <div class="alert alert-warning mb-3" style="border-left: 4px solid #f39c12;">
            <i class="fas fa-lock mr-2"></i>
            <strong>Super Admin Only.</strong> This footer will be shown on all company panels. No company can edit this.
        </div>

        {{-- Main Card --}}
        <div class="card card-primary card-outline shadow-sm">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-edit mr-2"></i>Global Footer Text
                </h3>
                <div class="card-tools">
                    <span class="badge badge-primary">Super Admin Control</span>
                </div>
            </div>
            <form action="{{ route('superadmin.footer-settings.update') }}" method="POST">
                @csrf
                <div class="card-body">

                    <div class="form-group">
                        <label class="font-weight-bold">Footer Text</label>
                        <p class="text-muted small mb-2">
                            <i class="fas fa-info-circle"></i>
                            Writing <code>{year}</code> will automatically replace it with the current year.
                            You can use HTML tags (bold, links etc.).
                        </p>
                        <textarea name="footer_text" class="form-control @error('footer_text') is-invalid @enderror"
                            rows="3" maxlength="500"
                            placeholder="e.g. &copy; {year} <strong>SOFTYNEX</strong>. All Rights Reserved.">{{ old('footer_text', $footerText) }}</textarea>
                        @error('footer_text')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted"><span id="char-count">{{ strlen($footerText) }}</span>/500 characters</small>
                    </div>

                    {{-- Live Preview --}}
                    <div class="form-group">
                        <label class="font-weight-bold">Live Preview</label>
                        <div class="border rounded p-3 text-center bg-light" style="font-size:14px; color:#6c757d;">
                            <div id="footer-preview">{!! nl2br(e($footerText)) !!}</div>
                        </div>
                    </div>

                    {{-- Quick Templates --}}
                    <div class="form-group">
                        <label class="font-weight-bold">Quick Templates</label>
                        <div class="d-flex flex-wrap gap-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary mr-1 mb-1 tpl-btn"
                                data-tpl="&copy; {year} <strong>SOFTYNEX</strong>. All Rights Reserved.">
                                Default
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary mr-1 mb-1 tpl-btn"
                                data-tpl="&copy; {year} <strong>SOFTYNEX</strong>. Powered by <a href='https://softynex.me' target='_blank'>Softynex</a>">
                                With Link
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary mr-1 mb-1 tpl-btn"
                                data-tpl="&copy; {year} SOFTYNEX ISP Management. All Rights Reserved. | Developed by Softynex Team">
                                Full Text
                            </button>
                        </div>
                    </div>

                </div>
                <div class="card-footer d-flex justify-content-between align-items-center">
                    <small class="text-muted">
                        <i class="fas fa-shield-alt mr-1 text-success"></i>
                        Changes take effect immediately across all company panels.
                    </small>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-1"></i> Save Footer
                    </button>
                </div>
            </form>
        </div>

        {{-- Current Footer Display --}}
        <div class="card card-outline card-secondary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-eye mr-2"></i>Current Live Footer (All Panels)</h3>
            </div>
            <div class="card-body p-0">
                <div class="text-center py-3 px-4" style="background:#f8f9fa; border-bottom:1px solid #dee2e6; font-size:14px; color:#6c757d;">
                    {!! str_replace('{year}', date('Y'), $footerText ?: '&copy; ' . date('Y') . ' <strong>SOFTYNEX</strong>. All Rights Reserved.') !!}
                </div>
            </div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    const $textarea = $('textarea[name="footer_text"]');
    const $preview = $('#footer-preview');
    const $count = $('#char-count');

    function updatePreview() {
        let txt = $textarea.val().replace(/\{year\}/g, new Date().getFullYear());
        $preview.html(txt || '<span class="text-muted">Footer preview will appear here...</span>');
        $count.text($textarea.val().length);
    }

    $textarea.on('input', updatePreview);

    // Quick template buttons
    $('.tpl-btn').on('click', function() {
        $textarea.val($(this).data('tpl'));
        updatePreview();
    });

    updatePreview();
});
</script>
@endpush
