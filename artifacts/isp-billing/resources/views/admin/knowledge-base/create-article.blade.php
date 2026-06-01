@extends('layouts.admin')

@section('title', 'Create Article')

@section('content')
<div class="container-fluid">
    <div class="card card-primary">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-plus-circle mr-2"></i>Create New Article</h3>
        </div>
        <form action="{{ route('admin.knowledge-base.articles.store') }}" method="POST">
            @csrf
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
                    <div class="col-md-8">
                        <div class="form-group">
                            <label><i class="fas fa-heading mr-1"></i>Title</label>
                            <input type="text" name="title" class="form-control" value="{{ old('title') }}" placeholder="Article title" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label><i class="fas fa-folder mr-1"></i>Category</label>
                            <select name="category_id" class="form-control" required>
                                <option value="">Select Category</option>
                                @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ old('category_id', request('category')) == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-align-left mr-1"></i>Content</label>
                    <textarea name="content" class="form-control" rows="15" placeholder="Write your article content here... HTML is supported." required>{{ old('content') }}</textarea>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label><i class="fas fa-users mr-1"></i>Audience</label>
                            <select name="audience" class="form-control" required>
                                <option value="all" {{ old('audience') == 'all' ? 'selected' : '' }}>All Users</option>
                                <option value="admin" {{ old('audience') == 'admin' ? 'selected' : '' }}>Admin Only</option>
                                <option value="client" {{ old('audience') == 'client' ? 'selected' : '' }}>Clients Only</option>
                                <option value="public" {{ old('audience') == 'public' ? 'selected' : '' }}>Public</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Featured</label>
                            <div class="custom-control custom-switch mt-2">
                                <input type="checkbox" class="custom-control-input" id="is_featured" name="is_featured" value="1" {{ old('is_featured') ? 'checked' : '' }}>
                                <label class="custom-control-label" for="is_featured">Mark as Featured</label>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Published</label>
                            <div class="custom-control custom-switch mt-2">
                                <input type="checkbox" class="custom-control-input" id="is_published" name="is_published" value="1" checked>
                                <label class="custom-control-label" for="is_published">Publish Immediately</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <a href="{{ route('admin.knowledge-base.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left mr-1"></i>Back
                </a>
                <button type="submit" class="btn btn-primary float-right">
                    <i class="fas fa-save mr-1"></i>Create Article
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
