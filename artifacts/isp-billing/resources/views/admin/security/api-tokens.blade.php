@extends('layouts.admin')

@section('title', 'API Tokens')

@section('content')
<div class="container-fluid">

    @if(session('token'))
    <div class="alert alert-success alert-dismissible border-left border-success" style="border-left-width:5px!important; background:#d4edda;">
        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span>&times;</span></button>
        <h5 class="mb-2"><i class="fas fa-key mr-2 text-success"></i><strong>API Token Created Successfully!</strong></h5>
        <p class="mb-2 text-danger"><i class="fas fa-exclamation-triangle mr-1"></i><strong>This token can only be viewed now — it will not be available once you close the page!</strong></p>
        <div class="input-group mb-2">
            <input type="text" class="form-control font-weight-bold" value="{{ session('token') }}" id="newApiToken" readonly style="font-family:monospace; font-size:14px; letter-spacing:1px; background:#fff3cd;">
            <div class="input-group-append">
                <button class="btn btn-success" type="button" id="copyTokenBtn" onclick="copyApiToken()">
                    <i class="fas fa-copy mr-1"></i><span id="copyBtnText">Copy Token</span>
                </button>
            </div>
        </div>
        <small class="text-muted"><i class="fas fa-info-circle mr-1"></i>Save the token somewhere — only the last 8 characters are visible in the database.</small>
    </div>
    <script>
    function copyApiToken() {
        var tokenInput = document.getElementById('newApiToken');
        var btn = document.getElementById('copyTokenBtn');
        var btnText = document.getElementById('copyBtnText');
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(tokenInput.value).then(function() {
                btnText.textContent = 'Copied!';
                btn.classList.remove('btn-success');
                btn.classList.add('btn-primary');
                setTimeout(function() {
                    btnText.textContent = 'Copy Token';
                    btn.classList.remove('btn-primary');
                    btn.classList.add('btn-success');
                }, 3000);
            });
        } else {
            tokenInput.select();
            tokenInput.setSelectionRange(0, 99999);
            document.execCommand('copy');
            btnText.textContent = 'Copied!';
            setTimeout(function() { btnText.textContent = 'Copy Token'; }, 3000);
        }
    }
    // Auto-select token on click
    document.getElementById('newApiToken').addEventListener('click', function() { this.select(); });
    </script>
    @endif

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-code mr-2"></i>API Tokens</h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.security.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left mr-1"></i>Back to Security
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Token (Last 8)</th>
                                <th>Permissions</th>
                                <th>Last Used</th>
                                <th>Expires At</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($tokens as $token)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $token->name }}</td>
                                <td><code>...{{ substr($token->token ?? '', -8) }}</code></td>
                                <td>
                                    @if(!empty($token->abilities) && $token->abilities !== ['*'] && !in_array('*', $token->abilities ?? []))
                                        @foreach($token->abilities as $perm)
                                        <span class="badge badge-info text-uppercase">{{ $perm }}</span>
                                        @endforeach
                                    @else
                                        <span class="badge badge-warning">Full Access</span>
                                    @endif
                                </td>
                                <td>{{ $token->last_used_at ? $token->last_used_at->diffForHumans() : 'Never' }}</td>
                                <td>{{ $token->expires_at ? $token->expires_at->format('d-m-Y') : 'Never' }}</td>
                                <td>
                                    @if($token->is_active)
                                        <span class="badge badge-success">Active</span>
                                    @else
                                        <span class="badge badge-danger">Revoked</span>
                                    @endif
                                </td>
                                <td>
                                    @if($token->is_active)
                                    <form action="{{ route('admin.security.api-tokens.disable', $token) }}" method="POST" class="d-inline" onsubmit="return confirm('Disable this token?')">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="btn btn-sm btn-warning" title="Disable"><i class="fas fa-ban"></i></button>
                                    </form>
                                    @else
                                    <form action="{{ route('admin.security.api-tokens.disable', $token) }}" method="POST" class="d-inline" onsubmit="return confirm('Re-enable this token?')">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="btn btn-sm btn-success" title="Enable"><i class="fas fa-check"></i></button>
                                    </form>
                                    @endif
                                    <form action="{{ route('admin.security.api-tokens.destroy', $token) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this token permanently?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" title="Delete"><i class="fas fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="8" class="text-center">No API tokens found</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-plus mr-1"></i>Create API Token</h3>
                </div>
                <form action="{{ route('admin.security.api-tokens.store') }}" method="POST">
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

                        <div class="form-group">
                            <label>Token Name</label>
                            <input type="text" name="name" class="form-control" value="{{ old('name') }}" placeholder="e.g., Mobile App, External System" required>
                        </div>
                        <div class="form-group">
                            <label>Permissions</label>
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="perm_read" name="permissions[]" value="read" checked>
                                <label class="custom-control-label" for="perm_read">Read</label>
                            </div>
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="perm_write" name="permissions[]" value="write">
                                <label class="custom-control-label" for="perm_write">Write</label>
                            </div>
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="perm_delete" name="permissions[]" value="delete">
                                <label class="custom-control-label" for="perm_delete">Delete</label>
                            </div>
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="perm_billing" name="permissions[]" value="billing">
                                <label class="custom-control-label" for="perm_billing">Billing</label>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Expires At</label>
                            <input type="date" name="expires_at" class="form-control" value="{{ old('expires_at') }}">
                            <small class="text-muted">Leave empty for no expiry</small>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-key mr-1"></i>Generate Token
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- ======= API QUICK REFERENCE ======= --}}
<div class="row mt-3">
    <div class="col-12">
        <div class="card card-outline card-primary collapsed-card">
            <div class="card-header py-2">
                <h3 class="card-title mb-0"><i class="fas fa-plug mr-2"></i>API Quick Reference</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-plus"></i></button>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="px-3 pt-3 pb-2 bg-light border-bottom">
                    <div class="row align-items-center">
                        <div class="col-auto text-muted" style="font-size:13px;"><strong>Base URL:</strong></div>
                        <div class="col">
                            <div class="input-group input-group-sm" style="max-width:380px;">
                                <input type="text" class="form-control" id="baseUrl" value="{{ config('app.url') }}/api/v1" readonly style="font-family:monospace; background:#fff;">
                                <div class="input-group-append">
                                    <button class="btn btn-outline-secondary" onclick="cpTxt('baseUrl',this)" type="button"><i class="fas fa-copy"></i></button>
                                </div>
                            </div>
                        </div>
                        <div class="col-auto text-muted" style="font-size:13px;"><strong>Auth Header:</strong></div>
                        <div class="col">
                            <code style="font-size:12px; background:#fff3cd; padding:4px 8px; border-radius:4px;">Authorization: Bearer YOUR_TOKEN</code>
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0" style="font-size:13px;">
                        <thead class="thead-dark">
                            <tr>
                                <th width="70">Method</th>
                                <th>Endpoint</th>
                                <th>Description</th>
                                <th width="80" class="text-center">Permission</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="table-secondary"><td colspan="4" class="py-1"><small><strong>Stats</strong></small></td></tr>
                            <tr>
                                <td><span class="badge badge-success">GET</span></td>
                                <td><code>/api/v1/stats</code></td>
                                <td>System overview (clients, revenue, payments)</td>
                                <td class="text-center"><span class="badge badge-info">read</span></td>
                            </tr>
                            <tr class="table-secondary"><td colspan="4" class="py-1"><small><strong>Clients</strong></small></td></tr>
                            <tr>
                                <td><span class="badge badge-success">GET</span></td>
                                <td><code>/api/v1/clients</code></td>
                                <td>List clients &nbsp;<small class="text-muted">?search= &nbsp;?status= &nbsp;?zone_id= &nbsp;?per_page=</small></td>
                                <td class="text-center"><span class="badge badge-info">read</span></td>
                            </tr>
                            <tr>
                                <td><span class="badge badge-success">GET</span></td>
                                <td><code>/api/v1/clients/{id}</code></td>
                                <td>Single client by ID or username</td>
                                <td class="text-center"><span class="badge badge-info">read</span></td>
                            </tr>
                            <tr>
                                <td><span class="badge badge-warning text-dark">POST</span></td>
                                <td><code>/api/v1/clients/{id}/activate</code></td>
                                <td>Activate a client</td>
                                <td class="text-center"><span class="badge badge-warning text-dark">write</span></td>
                            </tr>
                            <tr>
                                <td><span class="badge badge-warning text-dark">POST</span></td>
                                <td><code>/api/v1/clients/{id}/suspend</code></td>
                                <td>Suspend a client</td>
                                <td class="text-center"><span class="badge badge-warning text-dark">write</span></td>
                            </tr>
                            <tr class="table-secondary"><td colspan="4" class="py-1"><small><strong>Payments & Bills</strong></small></td></tr>
                            <tr>
                                <td><span class="badge badge-success">GET</span></td>
                                <td><code>/api/v1/payments</code></td>
                                <td>List payments &nbsp;<small class="text-muted">?client_id= &nbsp;?from= &nbsp;?to=</small></td>
                                <td class="text-center"><span class="badge badge-info">read</span></td>
                            </tr>
                            <tr>
                                <td><span class="badge badge-warning text-dark">POST</span></td>
                                <td><code>/api/v1/payments</code></td>
                                <td>Create payment &nbsp;<small class="text-muted">body: client_id, amount, method, note</small></td>
                                <td class="text-center"><span class="badge badge-warning text-dark">write</span></td>
                            </tr>
                            <tr>
                                <td><span class="badge badge-success">GET</span></td>
                                <td><code>/api/v1/bills</code></td>
                                <td>List bills &nbsp;<small class="text-muted">?client_id= &nbsp;?status=unpaid/paid</small></td>
                                <td class="text-center"><span class="badge badge-info">read</span></td>
                            </tr>
                            <tr class="table-secondary"><td colspan="4" class="py-1"><small><strong>Packages, Zones, Areas</strong></small></td></tr>
                            <tr>
                                <td><span class="badge badge-success">GET</span></td>
                                <td><code>/api/v1/packages</code></td>
                                <td>List all packages</td>
                                <td class="text-center"><span class="badge badge-info">read</span></td>
                            </tr>
                            <tr>
                                <td><span class="badge badge-success">GET</span></td>
                                <td><code>/api/v1/zones</code></td>
                                <td>List all zones</td>
                                <td class="text-center"><span class="badge badge-info">read</span></td>
                            </tr>
                            <tr>
                                <td><span class="badge badge-success">GET</span></td>
                                <td><code>/api/v1/areas</code></td>
                                <td>List all areas</td>
                                <td class="text-center"><span class="badge badge-info">read</span></td>
                            </tr>
                            <tr class="table-secondary"><td colspan="4" class="py-1"><small><strong>Tickets</strong></small></td></tr>
                            <tr>
                                <td><span class="badge badge-success">GET</span></td>
                                <td><code>/api/v1/tickets</code></td>
                                <td>List tickets &nbsp;<small class="text-muted">?status=open/closed &nbsp;?client_id=</small></td>
                                <td class="text-center"><span class="badge badge-info">read</span></td>
                            </tr>
                            <tr>
                                <td><span class="badge badge-warning text-dark">POST</span></td>
                                <td><code>/api/v1/tickets</code></td>
                                <td>Create ticket &nbsp;<small class="text-muted">body: client_id, subject, message, priority</small></td>
                                <td class="text-center"><span class="badge badge-warning text-dark">write</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="px-3 py-2 border-top" style="font-size:12px; background:#f0f4ff;">
    <span class="mr-2"><i class="fas fa-check-circle text-success mr-1"></i><strong>Compatible with:</strong></span>
    <span class="badge badge-light border mr-1 py-1 px-2" style="font-size:11px;"><i class="fas fa-project-diagram mr-1 text-danger"></i>N8N</span>
    <span class="badge badge-light border mr-1 py-1 px-2" style="font-size:11px;"><i class="fas fa-sync mr-1 text-primary"></i>Make (Integromat)</span>
    <span class="badge badge-light border mr-1 py-1 px-2" style="font-size:11px;"><i class="fas fa-bolt mr-1 text-warning"></i>Zapier</span>
    <span class="badge badge-light border mr-1 py-1 px-2" style="font-size:11px;"><i class="fas fa-paper-plane mr-1 text-orange"></i>Postman</span>
    <span class="badge badge-light border mr-1 py-1 px-2" style="font-size:11px;"><i class="fas fa-moon mr-1 text-info"></i>Insomnia</span>
    <span class="badge badge-light border mr-1 py-1 px-2" style="font-size:11px;"><i class="fab fa-python mr-1 text-primary"></i>Python</span>
    <span class="badge badge-light border mr-1 py-1 px-2" style="font-size:11px;"><i class="fab fa-node-js mr-1 text-success"></i>JavaScript / Node.js</span>
    <span class="badge badge-light border mr-1 py-1 px-2" style="font-size:11px;"><i class="fab fa-php mr-1 text-info"></i>PHP</span>
    <span class="badge badge-light border mr-1 py-1 px-2" style="font-size:11px;"><i class="fab fa-telegram mr-1 text-info"></i>Telegram Bot</span>
    <span class="badge badge-light border mr-1 py-1 px-2" style="font-size:11px;"><i class="fas fa-table mr-1 text-success"></i>Google Sheets</span>
    <span class="badge badge-light border mr-1 py-1 px-2" style="font-size:11px;"><i class="fas fa-link mr-1"></i>Any REST Client</span>
</div>
<div class="px-3 py-2 bg-light border-top" style="font-size:12px;">
    <i class="fas fa-info-circle text-info mr-1"></i>
    <strong>Permission Note:</strong>
    For GET endpoints, <span class="badge badge-info badge-sm">read</span> permission is required.
    For POST/activate/suspend/payment, <span class="badge badge-warning text-dark badge-sm">write</span> permission is required.
    To grant all permissions, select all checkboxes when creating the token.
</div>
</div>
</div>
</div>
<script>
function cpTxt(id, btn) {
    var el = document.getElementById(id);
    navigator.clipboard && navigator.clipboard.writeText(el.value).then(function() {
        var o = btn.innerHTML; btn.innerHTML = '<i class="fas fa-check text-success"></i>';
        setTimeout(function(){ btn.innerHTML = o; }, 2000);
    });
}
</script>

@endsection