@extends('layouts.admin')

@section('title', 'WhatsApp Templates')

@section('content')
<div class="container-fluid">

    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fab fa-whatsapp mr-2"></i>Message Templates</h3>
            <div class="card-tools">
                <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#createTemplateModal">
                    <i class="fas fa-plus mr-1"></i>Create Template
                </button>
            </div>
        </div>
        <div class="card-body">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Content</th>
                        <th>Variables</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($templates as $template)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $template->name }}</td>
                        <td><span class="badge badge-info">{{ ucfirst($template->type ?? 'text') }}</span></td>
                        <td>{{ Str::limit($template->content, 60) }}</td>
                        <td>
                            @if($template->variables)
                                @foreach(json_decode($template->variables, true) ?? [] as $var)
                                <code class="small">{{ $var }}</code>
                                @endforeach
                            @else
                                -
                            @endif
                        </td>
                        <td>
                            @if($template->is_active)
                                <span class="badge badge-success">Active</span>
                            @else
                                <span class="badge badge-secondary">Inactive</span>
                            @endif
                        </td>
                        <td>
                            <button type="button" class="btn btn-sm btn-info" data-toggle="modal" data-target="#editTemplateModal{{ $template->id }}">
                                <i class="fas fa-edit"></i>
                            </button>
                            <form action="{{ route('admin.whatsapp.templates.delete', $template) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this template?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center">No templates found</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="createTemplateModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('admin.whatsapp.templates.store') }}" method="POST">
                @csrf
                <div class="modal-header bg-primary">
                    <h4 class="modal-title text-white"><i class="fas fa-plus mr-2"></i>Create Template</h4>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Template Name</label>
                                <input type="text" name="name" class="form-control" placeholder="e.g., payment_reminder" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Type</label>
                                <select name="type" class="form-control">
                                    <option value="text">Text</option>
                                    <option value="media">Media</option>
                                    <option value="interactive">Interactive</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Content</label>
                        <textarea name="content" class="form-control" rows="5" placeholder="Hello {name}, your bill of ৳{amount} is due on {date}." required></textarea>
                        <small class="text-muted">Use curly braces for variables, e.g., {name}, {amount}</small>
                    </div>
                    <div class="form-group">
                        <label>Variables (comma-separated)</label>
                        <input type="text" name="variables" class="form-control" placeholder="name, amount, date">
                    </div>
                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="create_is_active" name="is_active" value="1" checked>
                            <label class="custom-control-label" for="create_is_active">Active</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Template</button>
                </div>
            </form>
        </div>
    </div>
</div>

@foreach($templates as $template)
<div class="modal fade" id="editTemplateModal{{ $template->id }}">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('admin.whatsapp.templates.update', $template) }}" method="POST">
                @csrf @method('PUT')
                <div class="modal-header bg-info">
                    <h4 class="modal-title text-white"><i class="fas fa-edit mr-2"></i>Edit Template</h4>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Template Name</label>
                                <input type="text" name="name" class="form-control" value="{{ $template->name }}" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Type</label>
                                <select name="type" class="form-control">
                                    <option value="text" {{ ($template->type ?? '') == 'text' ? 'selected' : '' }}>Text</option>
                                    <option value="media" {{ ($template->type ?? '') == 'media' ? 'selected' : '' }}>Media</option>
                                    <option value="interactive" {{ ($template->type ?? '') == 'interactive' ? 'selected' : '' }}>Interactive</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Content</label>
                        <textarea name="content" class="form-control" rows="5" required>{{ $template->content }}</textarea>
                    </div>
                    <div class="form-group">
                        <label>Variables (comma-separated)</label>
                        <input type="text" name="variables" class="form-control" value="{{ is_array(json_decode($template->variables, true)) ? implode(', ', json_decode($template->variables, true)) : $template->variables }}">
                    </div>
                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="edit_is_active_{{ $template->id }}" name="is_active" value="1" {{ $template->is_active ? 'checked' : '' }}>
                            <label class="custom-control-label" for="edit_is_active_{{ $template->id }}">Active</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info">Update Template</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach
@endsection
