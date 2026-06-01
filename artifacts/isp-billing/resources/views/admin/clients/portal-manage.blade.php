@extends('layouts.admin')
@section('title', 'Client Portal Management')
@section('breadcrumb')<li class="breadcrumb-item active">Portal Management</li>@endsection
@section('content')

@if(session('success'))
<div class="alert alert-success alert-dismissible"><i class="fas fa-check-circle mr-1"></i>{{ session('success') }}<button type="button" class="close" data-dismiss="alert">&times;</button></div>
@endif

<div class="row">
    <div class="col-md-5">
        <div class="card mb-3">
            <div class="card-header py-2"><h6 class="mb-0"><i class="fas fa-cog mr-2"></i>Portal Settings</h6></div>
            <div class="card-body">
                <form action="{{ route('admin.client.portal.update') }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="portalEnabled" name="portal_enabled" {{ $settings['portal_enabled'] == '1' ? 'checked' : '' }}>
                            <label class="custom-control-label" for="portalEnabled">Enable Client Portal</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold" style="font-size:12px;">Features</label>
                        <div class="custom-control custom-checkbox mb-1">
                            <input type="checkbox" class="custom-control-input" id="viewBills" name="view_bills" {{ $settings['view_bills'] == '1' ? 'checked' : '' }}>
                            <label class="custom-control-label" for="viewBills">View Bills</label>
                        </div>
                        <div class="custom-control custom-checkbox mb-1">
                            <input type="checkbox" class="custom-control-input" id="payOnline" name="pay_online" {{ $settings['pay_online'] == '1' ? 'checked' : '' }}>
                            <label class="custom-control-label" for="payOnline">Online Payment</label>
                        </div>
                        <div class="custom-control custom-checkbox mb-1">
                            <input type="checkbox" class="custom-control-input" id="createTickets" name="create_tickets" {{ $settings['create_tickets'] == '1' ? 'checked' : '' }}>
                            <label class="custom-control-label" for="createTickets">Create Support Tickets</label>
                        </div>
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="changePackage" name="change_package" {{ $settings['change_package'] == '1' ? 'checked' : '' }}>
                            <label class="custom-control-label" for="changePackage">Request Package Change</label>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save mr-1"></i>Save Settings</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-7">
        <div class="card mb-3">
            <div class="card-header py-2 d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="fas fa-bullhorn mr-2 text-warning"></i>Client Notice Bar</h6>
                <small class="text-muted">Scrolling ticker shown to all clients</small>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.client.portal.notices.store') }}" method="POST" class="mb-3">
                    @csrf
                    <div class="input-group">
                        <input type="text" name="content" class="form-control" placeholder="Type a notice message..." required style="font-size:13px;">
                        <div class="input-group-append">
                            <button type="submit" class="btn btn-warning"><i class="fas fa-plus mr-1"></i>Add Notice</button>
                        </div>
                    </div>
                    <small class="text-muted" style="font-size:11px;">This notice will appear in the scrolling ticker bar on the client portal.</small>
                </form>

                @if($notices->count() > 0)
                <div class="table-responsive">
                    <table class="table table-sm table-bordered" style="font-size:12px;">
                        <thead class="thead-light">
                            <tr><th>Notice</th><th width="70" class="text-center">Status</th><th width="70" class="text-center">Delete</th></tr>
                        </thead>
                        <tbody>
                            @foreach($notices as $notice)
                            <tr class="{{ $notice->is_active ? '' : 'table-secondary' }}">
                                <td style="word-break:break-word;">{{ $notice->content }}</td>
                                <td class="text-center">
                                    <form action="{{ route('admin.client.portal.notices.toggle', $notice->id) }}" method="POST">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="btn btn-xs {{ $notice->is_active ? 'btn-success' : 'btn-secondary' }}" style="font-size:10px;padding:2px 8px;">
                                            {{ $notice->is_active ? 'Active' : 'Off' }}
                                        </button>
                                    </form>
                                </td>
                                <td class="text-center">
                                    <form action="{{ route('admin.client.portal.notices.delete', $notice->id) }}" method="POST" onsubmit="return confirm('Delete this notice?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-xs btn-danger" style="font-size:10px;padding:2px 8px;"><i class="fas fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center text-muted py-3" style="font-size:12px;">
                    <i class="fas fa-bullhorn fa-2x mb-2 d-block" style="opacity:0.4;"></i>
                    No notices added yet. Add one above to display on client portal.
                </div>
                @endif
            </div>
        </div>

        @if($notices->where('is_active',1)->count() > 0)
        <div class="card">
            <div class="card-header py-2"><h6 class="mb-0"><i class="fas fa-eye mr-2"></i>Live Preview</h6></div>
            <div class="card-body p-2">
                <div style="background:#1e3c72;display:flex;align-items:center;border-radius:6px;overflow:hidden;height:36px;">
                    <div style="background:#f59e0b;color:#fff;padding:0 12px;font-size:11px;font-weight:700;white-space:nowrap;height:100%;display:flex;align-items:center;gap:6px;flex-shrink:0;">
                        <i class="fas fa-bullhorn"></i> NOTICE
                    </div>
                    <div style="overflow:hidden;flex:1;padding:0 12px;">
                        <span style="color:#fff;font-size:12px;white-space:nowrap;">
                            {{ $notices->where('is_active',1)->pluck('content')->implode(' ◆ ') }}
                        </span>
                    </div>
                </div>
                <small class="text-muted" style="font-size:10px;">This is how it will appear in the client portal as a scrolling bar.</small>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
