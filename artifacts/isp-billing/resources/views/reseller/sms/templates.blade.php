@extends('layouts.reseller')

@section('title', 'SMS Templates')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('reseller.sms.settings') }}">SMS</a></li>
<li class="breadcrumb-item active">SMS Templates</li>
@endsection

@section('content')
@php
$types = [
    'general'     => ['label' => 'General',     'icon' => 'fas fa-comment',           'color' => '#6366f1'],
    'welcome'     => ['label' => 'Welcome',     'icon' => 'fas fa-hand-sparkles',      'color' => '#3b82f6'],
    'billing'     => ['label' => 'Billing',     'icon' => 'fas fa-file-invoice-dollar','color' => '#10b981'],
    'payment'     => ['label' => 'Payment',     'icon' => 'fas fa-money-bill-wave',    'color' => '#059669'],
    'reminder'    => ['label' => 'Reminder',    'icon' => 'fas fa-bell',              'color' => '#f59e0b'],
    'status'      => ['label' => 'Status',      'icon' => 'fas fa-info-circle',        'color' => '#0ea5e9'],
    'promotion'   => ['label' => 'Promotion',   'icon' => 'fas fa-bullhorn',           'color' => '#ea580c'],
    'maintenance' => ['label' => 'Maintenance', 'icon' => 'fas fa-tools',              'color' => '#78716c'],
    'greeting'    => ['label' => 'Greeting',    'icon' => 'fas fa-gift',               'color' => '#e11d48'],
];
@endphp

<!-- Type Filter -->
<div class="row mb-3">
    <div class="col-12">
        <div class="d-flex flex-wrap" style="gap:8px;">
            <a href="{{ route('reseller.sms.templates') }}" class="btn btn-sm {{ !request('type') ? 'btn-primary' : 'btn-outline-secondary' }}" style="border-radius:20px;padding:5px 14px;font-size:0.8rem;">
                <i class="fas fa-th-large mr-1"></i> All <span class="badge badge-light ml-1">{{ $templates->total() }}</span>
            </a>
            @foreach($types as $key => $info)
            <a href="{{ route('reseller.sms.templates', ['type' => $key]) }}"
               class="btn btn-sm {{ request('type') == $key ? '' : 'btn-outline-secondary' }}"
               style="border-radius:20px;padding:5px 14px;font-size:0.8rem;{{ request('type') == $key ? 'background:'.$info['color'].';border-color:'.$info['color'].';color:#fff;' : '' }}">
                <i class="{{ $info['icon'] }} mr-1"></i> {{ $info['label'] }}
            </a>
            @endforeach
        </div>
    </div>
</div>

<div class="card card-primary card-outline">
    <div class="card-header d-flex align-items-center">
        <h3 class="card-title"><i class="fas fa-list mr-2"></i>{{ request('type') ? ucfirst(request('type')).' Templates' : 'All Templates' }}</h3>
        <div class="card-tools ml-auto">
            <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addTemplateModal">
                <i class="fas fa-plus mr-1"></i> New Template
            </button>
        </div>
    </div>
    <div class="card-body p-0">
        @if(session('success'))
        <div class="alert alert-success m-3"><i class="fas fa-check-circle mr-2"></i>{{ session('success') }}</div>
        @endif
        @if(session('error'))
        <div class="alert alert-danger m-3"><i class="fas fa-times-circle mr-2"></i>{{ session('error') }}</div>
        @endif

        @if($templates->isEmpty())
        <div class="text-center py-5 text-muted">
            <i class="fas fa-file-alt fa-3x mb-3"></i>
            <p>No templates found. Use the button above to add a new template.</p>
        </div>
        @else
        <table class="table table-hover mb-0">
            <thead class="thead-light">
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Type</th>
                    <th>Message</th>
                    <th>Created</th>
                    <th class="text-center">Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($templates as $i => $tpl)
                <tr>
                    <td>{{ $templates->firstItem() + $i }}</td>
                    <td><strong>{{ $tpl->name }}</strong></td>
                    <td>
                        @php $ti = $types[$tpl->type] ?? ['label' => ucfirst($tpl->type), 'color' => '#6b7280', 'icon' => 'fas fa-tag']; @endphp
                        <span class="badge" style="background:{{ $ti['color'] }};color:#fff;padding:4px 8px;border-radius:12px;">
                            <i class="{{ $ti['icon'] }} mr-1"></i>{{ $ti['label'] }}
                        </span>
                    </td>
                    <td style="max-width:350px;">
                        <span class="text-truncate d-inline-block" style="max-width:320px;" title="{{ $tpl->content }}">{{ $tpl->content }}</span>
                        <span class="badge badge-light ml-1">{{ strlen($tpl->content) }} chars</span>
                    </td>
                    <td><small class="text-muted">{{ $tpl->created_at->format('d M Y') }}</small></td>
                    <td class="text-center">
                        <button class="btn btn-xs btn-info mr-1 btn-edit-template"
                            data-id="{{ $tpl->id }}"
                            data-name="{{ $tpl->name }}"
                            data-type="{{ $tpl->type }}"
                            data-content="{{ $tpl->content }}"
                            title="Edit"><i class="fas fa-edit"></i></button>
                        <form action="{{ route('reseller.sms.templates.destroy', $tpl) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this template?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-xs btn-danger" title="Delete"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>
    @if($templates->hasPages())
    <div class="card-footer">{{ $templates->links() }}</div>
    @endif
</div>

<!-- Add Template Modal -->
<div class="modal fade" id="addTemplateModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('reseller.sms.templates.store') }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header bg-primary">
                    <h5 class="modal-title text-white"><i class="fas fa-plus-circle mr-2"></i>New SMS Template</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Template Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Bill Reminder" required>
                    </div>
                    <div class="form-group">
                        <label>Type <span class="text-danger">*</span></label>
                        <select name="type" class="form-control" required>
                            @foreach($types as $key => $info)
                            <option value="{{ $key }}">{{ $info['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Message <span class="text-danger">*</span></label>
                        <textarea name="content" class="form-control" rows="5" required placeholder="Type your SMS text here..."></textarea>
                        <small class="text-muted">Variables: <code>{name}</code> <code>{phone}</code> <code>{package}</code> <code>{amount}</code> <code>{due}</code> <code>{due_date}</code> <code>{company}</code> <code>{month}</code></small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Save</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Edit Template Modal -->
<div class="modal fade" id="editTemplateModal" tabindex="-1">
    <div class="modal-dialog">
        <form id="editTemplateForm" method="POST">
            @csrf @method('PUT')
            <div class="modal-content">
                <div class="modal-header bg-info">
                    <h5 class="modal-title text-white"><i class="fas fa-edit mr-2"></i>Edit Template</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Template Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="editName" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Type <span class="text-danger">*</span></label>
                        <select name="type" id="editType" class="form-control" required>
                            @foreach($types as $key => $info)
                            <option value="{{ $key }}">{{ $info['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Message <span class="text-danger">*</span></label>
                        <textarea name="content" id="editContent" class="form-control" rows="5" required></textarea>
                        <small class="text-muted">Variables: <code>{name}</code> <code>{phone}</code> <code>{package}</code> <code>{amount}</code> <code>{due}</code> <code>{due_date}</code> <code>{company}</code> <code>{month}</code></small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info"><i class="fas fa-save mr-1"></i> Update</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function(){
    $('.btn-edit-template').on('click', function(){
        var id      = $(this).data('id');
        var name    = $(this).data('name');
        var type    = $(this).data('type');
        var content = $(this).data('content');
        $('#editTemplateForm').attr('action', '/reseller/sms/templates/' + id);
        $('#editName').val(name);
        $('#editType').val(type);
        $('#editContent').val(content);
        $('#editTemplateModal').modal('show');
    });

    @if($errors->any())
    $('#addTemplateModal').modal('show');
    @endif
});
</script>
@endpush
