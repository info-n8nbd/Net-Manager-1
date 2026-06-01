@extends('layouts.admin')

@section('title', 'Edit Hotspot Template')

@section('content')
<div class="container-fluid">

    <div class="card card-warning">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-edit mr-2"></i>Edit Hotspot Template: {{ $template->name }}</h3>
        </div>
        <form action="{{ route('admin.hotspot.update', $template) }}" method="POST">
            @csrf @method('PUT')
            <div class="card-body">
                @if($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label><i class="fas fa-tag mr-1"></i>Template Name</label>
                            <input type="text" name="name" class="form-control" value="{{ old('name', $template->name) }}" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Active</label>
                            <div class="custom-control custom-switch mt-2">
                                <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1" {{ $template->is_active ? 'checked' : '' }}>
                                <label class="custom-control-label" for="is_active">Active</label>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Set as Default</label>
                            <div class="custom-control custom-switch mt-2">
                                <input type="checkbox" class="custom-control-input" id="is_default" name="is_default" value="1" {{ $template->is_default ? 'checked' : '' }}>
                                <label class="custom-control-label" for="is_default">Default</label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-code mr-1"></i>HTML Content</label>
                    <textarea name="html_content" class="form-control" rows="20" style="font-family: monospace; font-size: 13px;">{{ old('html_content', $template->html_content) }}</textarea>
                    <small class="text-muted">Use MikroTik hotspot variables: $(username), $(password), $(link-login), $(link-orig), $(error)</small>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label><i class="fas fa-paint-brush mr-1"></i>Custom CSS</label>
                            <textarea name="css_content" class="form-control" rows="8" style="font-family: monospace; font-size: 13px;">{{ old('css_content', $template->css_content) }}</textarea>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label><i class="fab fa-js mr-1"></i>Custom JavaScript</label>
                            <textarea name="js_content" class="form-control" rows="8" style="font-family: monospace; font-size: 13px;">{{ old('js_content', $template->js_content) }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <a href="{{ route('admin.hotspot.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left mr-1"></i>Back
                </a>
                <div class="float-right">
                    <a href="{{ route('admin.hotspot.preview', $template) }}" class="btn btn-info">
                        <i class="fas fa-eye mr-1"></i>Preview
                    </a>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-save mr-1"></i>Update Template
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
