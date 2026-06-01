@extends('layouts.admin')

@section('title', 'Articles - ' . $category->name)

@section('content')
<div class="container-fluid">

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas {{ $category->icon ?? 'fa-folder' }} mr-2"></i>
                {{ $category->name }} - Articles
            </h3>
            <div class="card-tools">
                <a href="{{ route('admin.knowledge-base.index') }}" class="btn btn-secondary btn-sm mr-1">
                    <i class="fas fa-arrow-left mr-1"></i>Back
                </a>
                <a href="{{ route('admin.knowledge-base.articles.create', ['category' => $category->id]) }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus mr-1"></i>New Article
                </a>
            </div>
        </div>
        <div class="card-body">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Title</th>
                        <th>Audience</th>
                        <th>Featured</th>
                        <th>Views</th>
                        <th>Status</th>
                        <th>Last Updated</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($articles as $article)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>
                            <strong>{{ $article->title }}</strong>
                            @if($article->is_featured)
                                <i class="fas fa-star text-warning ml-1" title="Featured"></i>
                            @endif
                        </td>
                        <td>
                            @if($article->audience === 'admin')
                                <span class="badge badge-primary">Admin</span>
                            @elseif($article->audience === 'client')
                                <span class="badge badge-info">Client</span>
                            @elseif($article->audience === 'public')
                                <span class="badge badge-success">Public</span>
                            @else
                                <span class="badge badge-secondary">{{ ucfirst($article->audience ?? 'all') }}</span>
                            @endif
                        </td>
                        <td>
                            @if($article->is_featured)
                                <span class="badge badge-warning">Yes</span>
                            @else
                                <span class="text-muted">No</span>
                            @endif
                        </td>
                        <td>{{ $article->views ?? 0 }}</td>
                        <td>
                            @if($article->is_published)
                                <span class="badge badge-success">Published</span>
                            @else
                                <span class="badge badge-secondary">Draft</span>
                            @endif
                        </td>
                        <td>{{ $article->updated_at->format('d-m-Y') }}</td>
                        <td>
                            <a href="{{ route('admin.knowledge-base.articles.edit', $article) }}" class="btn btn-sm btn-info" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form action="{{ route('admin.knowledge-base.articles.destroy', $article) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this article?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger" title="Delete"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center">No articles in this category</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
