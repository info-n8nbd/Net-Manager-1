@extends('layouts.admin')

@section('title', 'Product Categories - Inventory')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="#">Inventory</a></li>
<li class="breadcrumb-item active">Categories</li>
@endsection

@section('content')
<div class="card card-primary card-outline">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-list mr-2"></i>Category List</h3>
        <div class="card-tools">
            <a href="{{ route('admin.inventory.product-categories.create') }}" class="btn btn-primary btn-sm">
                <i class="fas fa-plus mr-1"></i> Add Category
            </a>
        </div>
    </div>
    <div class="card-body p-0">
        <table class="table table-bordered table-hover mb-0">
            <thead class="thead-light">
                <tr>
                    <th width="50">#</th>
                    <th>Category Name</th>
                    <th>Description</th>
                    <th width="100" class="text-center">Types</th>
                    <th width="150" class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($categories as $index => $cat)
                @php
                    $types = $typesByCategory->get($cat->name, collect());
                @endphp
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>
                        <i class="fas fa-folder text-warning mr-1"></i>
                        <strong>{{ $cat->name }}</strong>
                    </td>
                    <td>{{ $cat->description ?? '-' }}</td>
                    <td class="text-center">
                        @if($types->count() > 0)
                        <a href="#" class="badge badge-info toggle-types" data-target=".types-{{ $cat->id }}" title="Click to show/hide types">
                            <i class="fas fa-chevron-right type-arrow mr-1" style="transition: transform 0.2s;"></i>{{ $types->count() }}
                        </a>
                        @else
                        <span class="badge badge-secondary">0</span>
                        @endif
                    </td>
                    <td class="text-center">
                        <a href="{{ route('admin.inventory.product-categories.edit', $cat->id) }}" class="btn btn-warning btn-sm" title="Edit">
                            <i class="fas fa-edit"></i>
                        </a>
                        <form action="{{ route('admin.inventory.product-categories.destroy', $cat->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this category?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @foreach($types as $type)
                <tr class="types-{{ $cat->id }}" style="display:none; background-color: #f8f9fa;">
                    <td></td>
                    <td colspan="2" class="pl-4">
                        <i class="fas fa-level-up-alt fa-rotate-90 text-muted mr-2"></i>
                        <span class="badge badge-primary">{{ $cat->name }} Type</span>
                        {{ $type->sub_category }}
                        <small class="text-muted ml-2">({{ $type->count }} items)</small>
                    </td>
                    <td></td>
                    <td></td>
                </tr>
                @endforeach
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).on('click', '.toggle-types', function(e) {
    e.preventDefault();
    var target = $(this).data('target');
    var $arrow = $(this).find('.type-arrow');
    $(target).toggle();
    if ($(target).first().is(':visible')) {
        $arrow.css('transform', 'rotate(90deg)');
    } else {
        $arrow.css('transform', 'rotate(0deg)');
    }
});
</script>
@endpush
