@extends("layouts.client")
@section("title", "Profile Not Found")

@section("content")
<div class="d-flex align-items-center justify-content-center" style="min-height: 60vh;">
    <div class="text-center px-4">
        <div class="mb-4">
            <div class="rounded-circle d-inline-flex align-items-center justify-content-center"
                 style="width:90px;height:90px;background:linear-gradient(135deg,#e74c3c,#c0392b);color:#fff;font-size:36px;">
                <i class="fas fa-user-slash"></i>
            </div>
        </div>
        <h3 class="font-weight-bold text-dark mb-2">Client Profile Not Found</h3>
        <p class="text-muted mb-4" style="max-width:420px;margin:0 auto;">
            Your account is not linked to any active client profile. Please contact your ISP administrator to set up your profile.
        </p>
        <div class="d-flex justify-content-center gap-2" style="gap:10px;">
            <a href="{{ route("login") }}" class="btn btn-outline-secondary">
                <i class="fas fa-sign-out-alt mr-1"></i> Sign Out
            </a>
            <a href="mailto:support@softynex.me" class="btn btn-primary">
                <i class="fas fa-envelope mr-1"></i> Contact Support
            </a>
        </div>
        <div class="mt-4">
            <small class="text-muted">
                <i class="fas fa-info-circle mr-1"></i>
                Error 403 &mdash; Access denied due to missing profile.
            </small>
        </div>
    </div>
</div>
@endsection
