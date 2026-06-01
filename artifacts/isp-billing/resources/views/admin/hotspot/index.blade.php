@extends('layouts.admin')

@section('title', 'Hotspot Templates')

@section('content')
<div class="container-fluid">

    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-wifi mr-2"></i>Hotspot Login Templates</h3>
            <div class="card-tools">
                <a href="{{ route('admin.hotspot.create') }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus mr-1"></i>Create Template
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                @forelse($templates as $template)
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">{{ $template->name }}</h6>
                            <div>
                                @if($template->is_default)
                                    <span class="badge badge-primary">Default</span>
                                @endif
                                @if($template->is_active)
                                    <span class="badge badge-success">Active</span>
                                @else
                                    <span class="badge badge-secondary">Inactive</span>
                                @endif
                            </div>
                        </div>
                        <div class="card-body p-2">
                            <div class="border rounded bg-light text-center p-3" style="min-height: 150px; overflow: hidden;">
                                <iframe srcdoc="{{ htmlspecialchars($template->html_content ?? '<p>No preview</p>') }}" style="width: 100%; height: 140px; border: none; pointer-events: none; transform: scale(0.5); transform-origin: top center;"></iframe>
                            </div>
                        </div>
                        <div class="card-footer">
                            <a href="{{ route('admin.hotspot.preview', $template) }}" class="btn btn-sm btn-info" title="Preview">
                                <i class="fas fa-eye mr-1"></i>Preview
                            </a>
                            <a href="{{ route('admin.hotspot.edit', $template) }}" class="btn btn-sm btn-warning" title="Edit">
                                <i class="fas fa-edit mr-1"></i>Edit
                            </a>
                            @if(!$template->is_default)
                            <form action="{{ route('admin.hotspot.destroy', $template) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this template?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger" title="Delete"><i class="fas fa-trash"></i></button>
                            </form>
                            <form action="{{ route('admin.hotspot.default', $template) }}" method="POST" class="d-inline">
                                @csrf @method('PATCH')
                                <button type="submit" class="btn btn-sm btn-primary" title="Set as Default"><i class="fas fa-star"></i></button>
                            </form>
                            @endif
                        </div>
                    </div>
                </div>
                @empty
                <div class="col-12">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-2"></i>No hotspot templates found. Create your first template to get started.
                    </div>
                </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
