@extends('layouts.admin')

@section('title', 'SMS Templates')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.settings.sms') }}">SMS Settings</a></li>
<li class="breadcrumb-item active">SMS Templates</li>
@endsection

@section('content')
<div class="container-fluid">
    @php
        $types = [
            'welcome' => ['label' => 'Welcome', 'icon' => 'fas fa-hand-sparkles', 'color' => '#3b82f6'],
            'billing' => ['label' => 'Billing', 'icon' => 'fas fa-file-invoice-dollar', 'color' => '#10b981'],
            'payment' => ['label' => 'Payment', 'icon' => 'fas fa-money-bill-wave', 'color' => '#059669'],
            'reminder' => ['label' => 'Reminder', 'icon' => 'fas fa-bell', 'color' => '#f59e0b'],
            'status' => ['label' => 'Status', 'icon' => 'fas fa-info-circle', 'color' => '#6366f1'],
            'application' => ['label' => 'Application', 'icon' => 'fas fa-file-alt', 'color' => '#8b5cf6'],
            'package' => ['label' => 'Package', 'icon' => 'fas fa-cube', 'color' => '#0ea5e9'],
            'support' => ['label' => 'Support', 'icon' => 'fas fa-headset', 'color' => '#ec4899'],
            'maintenance' => ['label' => 'Maintenance', 'icon' => 'fas fa-tools', 'color' => '#78716c'],
            'greeting' => ['label' => 'Greeting', 'icon' => 'fas fa-gift', 'color' => '#e11d48'],
            'security' => ['label' => 'Security', 'icon' => 'fas fa-shield-alt', 'color' => '#dc2626'],
            'promotion' => ['label' => 'Promotion', 'icon' => 'fas fa-bullhorn', 'color' => '#ea580c'],
            'contract' => ['label' => 'Contract', 'icon' => 'fas fa-file-signature', 'color' => '#64748b'],
        ];
    @endphp

    <div class="row mb-3">
        <div class="col-12">
            <div class="d-flex flex-wrap" style="gap:8px;">
                <a href="{{ route('admin.sms.templates') }}" class="btn btn-sm {{ !request('type') ? 'btn-primary' : 'btn-outline-secondary' }}" style="border-radius:20px; padding:5px 14px; font-size:0.8rem;">
                    <i class="fas fa-th-large mr-1"></i> All <span class="badge badge-light ml-1">{{ $templates->total() }}</span>
                </a>
                @foreach($types as $key => $info)
                    <a href="{{ route('admin.sms.templates', ['type' => $key]) }}"
                       class="btn btn-sm {{ request('type') == $key ? '' : 'btn-outline-secondary' }}"
                       style="border-radius:20px; padding:5px 14px; font-size:0.8rem; {{ request('type') == $key ? 'background:'.$info['color'].'; border-color:'.$info['color'].'; color:#fff;' : '' }}">
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
                <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addTemplateModal">
                    <i class="fas fa-plus mr-1"></i> Add New Template
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th style="width: 5%">#</th>
                            <th style="width: 15%">Name</th>
                            <th style="width: 10%">Type</th>
                            <th style="width: 10%">Code</th>
                            <th style="width: 40%">Content</th>
                            <th style="width: 8%">Status</th>
                            <th style="width: 12%">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- Automation Reminder Templates (stored in SystemSetting) --}}
                        @php
                            $autoRows = [
                                ['key' => 'sms_template_due_reminder_3day', 'name' => '3-Day Reminder', 'code' => 'due_reminder_3day', 'icon' => 'fas fa-bell', 'color' => '#f59e0b', 'placeholder' => 'Dear {name}, your bill Tk {due_amount} is due. Line inactive on {auto_inactive_date} ({days_left} days left). - {company}'],
                                ['key' => 'sms_template_due_reminder_1day', 'name' => '1-Day Reminder', 'code' => 'due_reminder_1day', 'icon' => 'fas fa-exclamation-triangle', 'color' => '#f59e0b', 'placeholder' => 'Dear {name}, your bill Tk {due_amount} is unpaid. Line inactive TOMORROW ({auto_inactive_date}). Pay now! - {company}'],
                                ['key' => 'sms_template_auto_inactive', 'name' => 'Auto-Inactive Notice', 'code' => 'auto_inactive', 'icon' => 'fas fa-ban', 'color' => '#ef4444', 'placeholder' => 'Dear {name}, your line is auto-inactivated. Bill due: Tk {due_amount}. Pay to restore. - {company}'],
                            ];
                        @endphp
                        @foreach($autoRows as $row)
                        <tr style="background:#fffbeb;" data-auto-row="{{ $row['key'] }}">
                            <td class="text-center align-middle">
                                <i class="{{ $row['icon'] }}" style="color:{{ $row['color'] }};"></i>
                            </td>
                            <td class="align-middle">
                                <strong style="font-size:0.82rem;">{{ $row['name'] }}</strong>
                                <br><small class="text-muted" style="font-size:0.72rem;">Auto scheduler</small>
                            </td>
                            <td class="align-middle">
                                <span class="badge" style="background:#f59e0b;color:#fff;font-size:0.7rem;"><i class="fas fa-clock mr-1"></i>automation</span>
                            </td>
                            <td class="align-middle"><code style="font-size:0.75rem;">{{ $row['code'] }}</code></td>
                            <td class="align-middle">
                                <textarea id="auto_{{ $row['key'] }}" class="form-control form-control-sm auto-tpl-textarea" rows="2"
                                    style="font-size:0.78rem;resize:vertical;" data-key="{{ $row['key'] }}"
                                    placeholder="{{ $row['placeholder'] }}">{{ \App\Models\SystemSetting::get($row['key'], '') }}</textarea>
                                <small class="text-muted" style="font-size:0.68rem;">
                                    <code>{name}</code> <code>{due_amount}</code> <code>{days_left}</code> <code>{auto_inactive_date}</code> <code>{company}</code>
                                </small>
                            </td>
                            <td class="align-middle">
                                <span class="badge badge-success">Active</span>
                            </td>
                            <td class="align-middle">
                                <button type="button"
                                    class="btn btn-warning btn-sm btn-save-auto-tpl"
                                    data-key="{{ $row['key'] }}"
                                    data-url="{{ route('admin.sms.auto-templates.save') }}"
                                    title="Save this template">
                                    <i class="fas fa-save"></i>
                                </button>
                            </td>
                        </tr>
                        @endforeach
                        {{-- End Automation Templates --}}

                        @forelse($templates as $index => $template)
                        <tr>
                            <td>{{ $templates->firstItem() + $index }}</td>
                            <td><strong>{{ $template->name }}</strong></td>
                            <td>
                                @php $tInfo = $types[$template->type] ?? ['label' => ucfirst($template->type), 'icon' => 'fas fa-sms', 'color' => '#6b7280']; @endphp
                                <span class="badge" style="background:{{ $tInfo['color'] }}; color:#fff;">
                                    <i class="{{ $tInfo['icon'] }} mr-1"></i>{{ $tInfo['label'] }}
                                </span>
                            </td>
                            <td><code>{{ $template->code }}</code></td>
                            <td>
                                <small>{{ Str::limit($template->content, 100) }}</small>
                                @if(strlen($template->content) > 100)
                                    <a href="#" data-toggle="modal" data-target="#viewModal{{ $template->id }}" class="text-primary">[View Full]</a>
                                @endif
                            </td>
                            <td>
                                <span class="badge badge-{{ $template->is_active ? 'success' : 'danger' }}">
                                    {{ $template->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm btn-info" data-toggle="modal" data-target="#editModal{{ $template->id }}" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-danger" data-toggle="modal" data-target="#deleteModal{{ $template->id }}" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>

                        <div class="modal fade" id="viewModal{{ $template->id }}" tabindex="-1">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header bg-info text-white">
                                        <h5 class="modal-title">{{ $template->name }}</h5>
                                        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <strong>Type:</strong> <span class="badge" style="background:{{ $tInfo['color'] }}; color:#fff;">{{ $tInfo['label'] }}</span>
                                            <strong class="ml-3">Code:</strong> <code>{{ $template->code }}</code>
                                            <strong class="ml-3">Status:</strong> 
                                            <span class="badge badge-{{ $template->is_active ? 'success' : 'danger' }}">{{ $template->is_active ? 'Active' : 'Inactive' }}</span>
                                        </div>
                                        <div class="alert alert-secondary">
                                            <strong>Message Content:</strong><br>
                                            {{ $template->content }}
                                        </div>
                                        <div class="alert alert-info">
                                            <strong>Available Variables:</strong><br>
                                            <code>{pppoe_id}</code>, <code>{client_name}</code>, <code>{client_id}</code>, <code>{package}</code>, <code>{amount}</code>, <code>{bill_amount}</code>, 
                                            <code>{due_amount}</code>, <code>{due_date}</code>, <code>{bill_month}</code>, <code>{bill_no}</code>, <code>{payment_no}</code>, 
                                            <code>{advance_balance}</code>, <code>{advance_used}</code>, <code>{monthly_fee}</code>, <code>{payment_link}</code>, <code>{company_name}</code>,
                                            <code>{username}</code>, <code>{balance}</code>, <code>{phone}</code>,
                                            <code>{reason}</code>, <code>{otp}</code>, <code>{new_password}</code>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="modal fade" id="editModal{{ $template->id }}" tabindex="-1">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <form action="{{ route('admin.sms.templates.update', $template->id) }}" method="POST">
                                        @csrf
                                        @method('PUT')
                                        <div class="modal-header bg-info text-white">
                                            <h5 class="modal-title"><i class="fas fa-edit mr-2"></i>Edit Template</h5>
                                            <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label>Template Name <span class="text-danger">*</span></label>
                                                        <input type="text" name="name" class="form-control" value="{{ $template->name }}" required>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label>Type <span class="text-danger">*</span></label>
                                                        <select name="type" class="form-control" required>
                                                            @foreach($types as $key => $info)
                                                                <option value="{{ $key }}" {{ $template->type == $key ? 'selected' : '' }}>{{ $info['label'] }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label>Code <span class="text-danger">*</span></label>
                                                        @php
                                                            $systemCodes = [
                                                                'monthly_bill' => 'monthly_bill (Monthly Bill SMS)',
                                                                'payment_received' => 'payment_received (Payment Confirmation)',
                                                                'advance_payment' => 'advance_payment (Advance Payment)',
                                                                'due_reminder' => 'due_reminder (Due Reminder)',
                                                                'disconnect_notice' => 'disconnect_notice (Disconnect Notice)',
                                                                'reconnect_notice' => 'reconnect_notice (Reconnect Notice)',
                                                                'welcome_message' => 'welcome_message (Welcome Message)',
                                                                'new_client_welcome' => 'new_client_welcome (New Client Welcome)',
                                                                'application_submitted' => 'application_submitted (Application Submitted)',
                                                                'application_correction' => 'application_correction (Application Correction)',
                                                                'application_approved' => 'application_approved (Application Approved)',
                                                                'application_rejected' => 'application_rejected (Application Rejected)',
                                                                'application_status' => 'application_status (Application Status)',
                                                                'ticket_update' => 'ticket_update (Ticket Update)',
                                                                'custom' => 'custom (Custom Template)',
                                                            ];
                                                        @endphp
                                                        <select name="code" class="form-control" required>
                                                            @foreach($systemCodes as $codeKey => $codeLabel)
                                                                <option value="{{ $codeKey }}" {{ $template->code == $codeKey ? 'selected' : '' }}>{{ $codeLabel }}</option>
                                                            @endforeach
                                                            @if(!array_key_exists($template->code, $systemCodes))
                                                                <option value="{{ $template->code }}" selected>{{ $template->code }} (Custom)</option>
                                                            @endif
                                                        </select>
                                                        <small class="text-muted">System event this template is used for</small>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label>Status</label>
                                                        <div class="custom-control custom-switch mt-2">
                                                            <input type="checkbox" class="custom-control-input" id="is_active{{ $template->id }}" name="is_active" {{ $template->is_active ? 'checked' : '' }}>
                                                            <label class="custom-control-label" for="is_active{{ $template->id }}">Active</label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label>Message Content <span class="text-danger">*</span></label>
                                                <textarea name="content" class="form-control" rows="5" required>{{ $template->content }}</textarea>
                                                <small class="text-muted">Character count: <span id="charCount{{ $template->id }}">{{ strlen($template->content) }}</span></small>
                                            </div>
                                            <div class="alert alert-info py-2">
                                                <small><strong>Variables:</strong> {pppoe_id}, {client_name}, {client_id}, {amount}, {bill_amount}, {due_amount}, {due_date}, {bill_month}, {bill_no}, {payment_no}, {advance_balance}, {advance_used}, {payment_link}, {package}, {monthly_fee}, {balance}, {company_name}</small>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Save Changes</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <div class="modal fade" id="deleteModal{{ $template->id }}" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form action="{{ route('admin.sms.templates.destroy', $template->id) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <div class="modal-header bg-danger text-white">
                                            <h5 class="modal-title"><i class="fas fa-trash mr-2"></i>Delete Template</h5>
                                            <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="text-center mb-3">
                                                <i class="fas fa-exclamation-triangle text-warning fa-3x"></i>
                                            </div>
                                            <p class="text-center">Are you sure you want to delete this template?</p>
                                            <div class="alert alert-secondary">
                                                <strong>Name:</strong> {{ $template->name }}<br>
                                                <strong>Type:</strong> {{ ucfirst($template->type) }}<br>
                                                <strong>Code:</strong> {{ $template->code }}
                                            </div>
                                            <p class="text-danger text-center"><strong>This action cannot be undone!</strong></p>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-danger"><i class="fas fa-trash mr-1"></i> Delete</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-4">
                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No templates found. Click "Add New Template" to create one.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($templates->hasPages())
        <div class="card-footer">
            <div class="d-flex flex-wrap justify-content-between align-items-center mt-2 mb-1">
    <div class="d-flex align-items-center">
        <label class="small text-muted mb-0 mr-2" style="white-space:nowrap;">SHOW</label>
        <select class="form-control form-control-sm bulk-show-entries" style="width:80px;">
            <option value="20" {{ request('per_page',20)==20?'selected':'' }}>20</option>
            <option value="50" {{ request('per_page',20)==50?'selected':'' }}>50</option>
            <option value="100" {{ request('per_page',20)==100?'selected':'' }}>100</option>
            <option value="200" {{ request('per_page',20)==200?'selected':'' }}>200</option>
        </select>
        <label class="small text-muted mb-0 ml-2" style="white-space:nowrap;">ENTRIES</label>
    </div>
    <div>
        {{ $templates->appends(request()->query())->links() }}
    </div>
</div>
<script>
(function(){
    var sel = document.querySelector('.bulk-show-entries');
    if(sel){ sel.addEventListener('change',function(){
        var p=new URLSearchParams(window.location.search);
        p.set('per_page',this.value); p.delete('page');
        window.location=window.location.pathname+'?'+p.toString();
    }); }
})();
</script>
        </div>
        @endif
    </div>
</div>

<div class="modal fade" id="addTemplateModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('admin.sms.templates.store') }}" method="POST">
                @csrf
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-plus mr-2"></i>Add New Template</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Template Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" placeholder="e.g., Payment Received" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Type <span class="text-danger">*</span></label>
                                <select name="type" class="form-control" required>
                                    @foreach($types as $key => $info)
                                        <option value="{{ $key }}">{{ $info['label'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Code <span class="text-danger">*</span></label>
                                <select name="code" class="form-control" id="new_template_code" required>
                                    <option value="">-- Select System Code --</option>
                                    <option value="monthly_bill">monthly_bill (Monthly Bill SMS)</option>
                                    <option value="payment_received">payment_received (Payment Confirmation)</option>
                                    <option value="advance_payment">advance_payment (Advance Payment)</option>
                                    <option value="due_reminder">due_reminder (Due Reminder)</option>
                                    <option value="disconnect_notice">disconnect_notice (Disconnect Notice)</option>
                                    <option value="reconnect_notice">reconnect_notice (Reconnect Notice)</option>
                                    <option value="welcome_message">welcome_message (Welcome Message)</option>
                                    <option value="new_client_welcome">new_client_welcome (New Client Welcome)</option>
                                    <option value="application_submitted">application_submitted (Application Submitted)</option>
                                    <option value="application_correction">application_correction (Application Correction)</option>
                                    <option value="application_approved">application_approved (Application Approved)</option>
                                    <option value="application_rejected">application_rejected (Application Rejected)</option>
                                    <option value="application_status">application_status (Application Status)</option>
                                    <option value="ticket_update">ticket_update (Ticket Update)</option>
                                    <option value="custom">custom (Custom Template)</option>
                                </select>
                                <small class="text-muted">Select the system event this template will be used for. The latest active template for each code is used.</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Status</label>
                                <div class="custom-control custom-switch mt-2">
                                    <input type="checkbox" class="custom-control-input" id="new_is_active" name="is_active" checked>
                                    <label class="custom-control-label" for="new_is_active">Active</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="alert alert-warning py-2">
                        <small><i class="fas fa-info-circle"></i> <strong>Important:</strong> The system uses template <strong>code</strong> to match SMS events. If you create a new template with the same code as an existing one, the most recently updated active template will be used. To replace a system template, create one with the same code and make sure it is <strong>Active</strong>.</small>
                    </div>
                    <div class="form-group">
                        <label>Message Content <span class="text-danger">*</span></label>
                        <textarea name="content" class="form-control" rows="5" placeholder="Enter SMS message content..." required></textarea>
                        <small class="text-muted">Character count: <span id="newCharCount">0</span></small>
                    </div>
                    <div class="alert alert-info py-2">
                        <small><strong>Available Variables:</strong> <code>{pppoe_id}</code>, <code>{client_name}</code>, <code>{client_id}</code>, <code>{package}</code>, <code>{amount}</code>, <code>{bill_amount}</code>, <code>{due_amount}</code>, <code>{due_date}</code>, <code>{bill_month}</code>, <code>{bill_no}</code>, <code>{payment_no}</code>, <code>{advance_balance}</code>, <code>{advance_used}</code>, <code>{monthly_fee}</code>, <code>{payment_link}</code>, <code>{company_name}</code>, <code>{username}</code>, <code>{balance}</code>, <code>{phone}</code></small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Save Template</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$('#addTemplateModal textarea[name="content"]').on('input', function() {
    $('#newCharCount').text($(this).val().length);
});

// Individual save for each automation template row
$(document).on('click', '.btn-save-auto-tpl', function() {
    var btn = $(this);
    var key = btn.data('key');
    var url = btn.data('url');
    var val = $('textarea[data-key="' + key + '"]').val();

    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

    $.ajax({
        url: url,
        method: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            [key]: val
        },
        success: function(res) {
            btn.html('<i class="fas fa-check"></i>').removeClass('btn-warning').addClass('btn-success');
            setTimeout(function() {
                btn.html('<i class="fas fa-save"></i>').removeClass('btn-success').addClass('btn-warning').prop('disabled', false);
            }, 2000);
        },
        error: function() {
            btn.html('<i class="fas fa-times"></i>').removeClass('btn-warning').addClass('btn-danger');
            setTimeout(function() {
                btn.html('<i class="fas fa-save"></i>').removeClass('btn-danger').addClass('btn-warning').prop('disabled', false);
            }, 2000);
        }
    });
});
</script>
@endpush
