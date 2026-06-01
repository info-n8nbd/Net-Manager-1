@extends('layouts.admin')

@section('title', 'Email Templates')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1"><i class="fas fa-envelope me-2"></i>Email Templates</h4>
            <p class="text-muted mb-0">Manage HTML email templates for automated notifications</p>
        </div>
    </div>

    <div class="row">
        @forelse($templates as $template)
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">
                        <i class="fas fa-file-alt me-2 text-primary"></i>
                        {{ ucwords(str_replace('_', ' ', $template->name)) }}
                    </h6>
                    <span class="badge {{ $template->is_active ? 'bg-success' : 'bg-secondary' }}">
                        {{ $template->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-2"><strong>Subject:</strong></p>
                    <p class="mb-3">{{ $template->subject }}</p>
                    
                    @if($template->variables)
                    <p class="text-muted small mb-2"><strong>Variables:</strong></p>
                    <div class="d-flex flex-wrap gap-1 mb-3">
                        @foreach(json_decode($template->variables, true) ?? [] as $var)
                        <code class="small">@{{ $var }}</code>
                        @endforeach
                    </div>
                    @endif
                </div>
                <div class="card-footer bg-transparent">
                    <a href="{{ route('admin.settings.email-templates.edit', $template) }}" class="btn btn-sm btn-primary">
                        <i class="fas fa-edit me-1"></i>Edit Template
                    </a>
                </div>
            </div>
        </div>
        @empty
        <div class="col-12">
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                No email templates found. Run the seeder to create default templates.
            </div>
        </div>
        @endforelse
    </div>
</div>
@endsection
