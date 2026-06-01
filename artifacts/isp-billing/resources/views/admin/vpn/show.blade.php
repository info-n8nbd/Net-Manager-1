@extends('layouts.admin')

@section('title', 'VPN Account - ' . ($vpnAccount->name ?? $vpnAccount->username))
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
<li class="breadcrumb-item"><a href="{{ route('admin.vpn.index') }}">VPN Accounts</a></li>
<li class="breadcrumb-item active">{{ $vpnAccount->name ?? $vpnAccount->username }}</li>
@endsection

@section('content')
<div class="container-fluid">

    <div class="row">
        <div class="col-lg-8">
            <div class="card card-success">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-terminal mr-2"></i>MikroTik Terminal Commands</h3>
                    <div class="card-tools">
                        <button class="btn btn-sm btn-light" onclick="copyAllCommands()">
                            <i class="fas fa-copy mr-1"></i>Copy All Commands
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-1"></i>
                        <strong>Instructions:</strong> Run the following code into your MikroTik (<strong>{{ $vpnAccount->account_type === 'main_router' ? 'Main' : 'Branch' }} Router</strong>) Terminal.
                        Open Winbox, click "New Terminal", and paste the commands below.
                    </div>

                    <div class="position-relative">
                        <pre id="mikrotik-commands" class="bg-dark text-white p-3 rounded" style="font-size: 13px; line-height: 1.6; white-space: pre-wrap; word-wrap: break-word;">{{ $commands['full'] }}</pre>
                        <button class="btn btn-sm btn-outline-light position-absolute" style="top: 8px; right: 8px;" onclick="copyAllCommands()" title="Copy to clipboard">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>

                    <hr>

                    <div class="alert alert-warning mb-0">
                        <i class="fas fa-exclamation-triangle mr-1"></i>
                        <strong>After configuring VPN Account, use</strong>
                        <div class="text-center my-2">
                            <code class="bg-dark text-warning px-3 py-2 rounded d-inline-block" style="font-size: 18px; font-weight: bold;">{{ $vpnAccount->remote_address }}</code>
                        </div>
                        <strong>as your router IP address while adding router into the billing software.</strong>
                    </div>
                </div>
            </div>

            <div class="card card-outline card-secondary collapsed-card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-code mr-2"></i>Commands Breakdown</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <h6 class="text-bold"><i class="fas fa-user-circle mr-1 text-primary"></i> Step 1: PPP Profile</h6>
                    <p class="text-sm text-muted mb-1">Creates a VPN tunnel profile with IP addressing</p>
                    <div class="position-relative mb-3">
                        <pre class="bg-dark text-white p-2 rounded" style="font-size: 12px;">{{ $commands['profile'] }}</pre>
                        <button class="btn btn-xs btn-outline-light position-absolute" style="top: 4px; right: 4px;" onclick="copyCommand('profile')"><i class="fas fa-copy"></i></button>
                    </div>

                    <h6 class="text-bold"><i class="fas fa-plug mr-1 text-success"></i> Step 2: SSTP Client Interface</h6>
                    <p class="text-sm text-muted mb-1">Creates the VPN tunnel connection to the billing server</p>
                    <div class="position-relative mb-3">
                        <pre class="bg-dark text-white p-2 rounded" style="font-size: 12px;">{{ $commands['interface'] }}</pre>
                        <button class="btn btn-xs btn-outline-light position-absolute" style="top: 4px; right: 4px;" onclick="copyCommand('interface')"><i class="fas fa-copy"></i></button>
                    </div>

                    <h6 class="text-bold"><i class="fas fa-fire mr-1 text-warning"></i> Step 3: Firewall NAT Rule</h6>
                    <p class="text-sm text-muted mb-1">Adds masquerade NAT for VPN network traffic</p>
                    <div class="position-relative mb-3">
                        <pre class="bg-dark text-white p-2 rounded" style="font-size: 12px;">{{ $commands['nat'] }}</pre>
                        <button class="btn btn-xs btn-outline-light position-absolute" style="top: 4px; right: 4px;" onclick="copyCommand('nat')"><i class="fas fa-copy"></i></button>
                    </div>

                    <h6 class="text-bold"><i class="fas fa-route mr-1 text-info"></i> Step 4: Static Route</h6>
                    <p class="text-sm text-muted mb-1">Adds routing for VPN network through the tunnel</p>
                    <div class="position-relative mb-3">
                        <pre class="bg-dark text-white p-2 rounded" style="font-size: 12px;">{{ $commands['route'] }}</pre>
                        <button class="btn btn-xs btn-outline-light position-absolute" style="top: 4px; right: 4px;" onclick="copyCommand('route')"><i class="fas fa-copy"></i></button>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-shield-alt mr-2"></i>Account Details</h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.vpn.edit', $vpnAccount) }}" class="btn btn-sm btn-info" title="Edit">
                            <i class="fas fa-edit mr-1"></i>Edit
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <tr>
                            <td class="text-bold" width="40%">Name</td>
                            <td>{{ $vpnAccount->name ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-bold">Account Type</td>
                            <td>
                                @if($vpnAccount->account_type === 'main_router')
                                    <span class="badge badge-success"><i class="fas fa-server mr-1"></i>Main Router</span>
                                @elseif($vpnAccount->account_type === 'branch_router')
                                    <span class="badge badge-warning"><i class="fas fa-code-branch mr-1"></i>Branch Router</span>
                                @else
                                    <span class="badge badge-secondary">Client VPN</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="text-bold">VPN Type</td>
                            <td><span class="badge badge-success">{{ strtoupper($vpnAccount->vpn_type) }}</span></td>
                        </tr>
                        <tr>
                            <td class="text-bold">Status</td>
                            <td>
                                @if($vpnAccount->is_active)
                                    <span class="badge badge-success"><i class="fas fa-check mr-1"></i>Active</span>
                                @else
                                    <span class="badge badge-danger"><i class="fas fa-times mr-1"></i>Inactive</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="text-bold">Username</td>
                            <td><code>{{ $vpnAccount->username }}</code></td>
                        </tr>
                        <tr>
                            <td class="text-bold">Password</td>
                            <td>
                                <code id="vpn-password" style="filter: blur(4px); cursor: pointer;" onclick="this.style.filter = this.style.filter ? '' : 'blur(4px)'">{{ $vpnAccount->password }}</code>
                                <small class="text-muted d-block">(click to show/hide)</small>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-bold">VPN Server</td>
                            <td><code>{{ $vpnAccount->server_address ?: '-' }}</code></td>
                        </tr>
                        <tr>
                            <td class="text-bold">VPN IP (Remote)</td>
                            <td><code class="text-danger font-weight-bold">{{ $vpnAccount->remote_address ?: '-' }}</code></td>
                        </tr>
                        <tr>
                            <td class="text-bold">Gateway (Local)</td>
                            <td><code>{{ $vpnAccount->local_address ?: '-' }}</code></td>
                        </tr>
                        @if($vpnAccount->monthly_fee > 0)
                        <tr>
                            <td class="text-bold">Monthly Bill</td>
                            <td>&#2547; {{ number_format($vpnAccount->monthly_fee, 2) }}</td>
                        </tr>
                        @endif
                        @if($vpnAccount->client)
                        <tr>
                            <td class="text-bold">Client</td>
                            <td>{{ $vpnAccount->client->name }}</td>
                        </tr>
                        @endif
                        <tr>
                            <td class="text-bold">Created</td>
                            <td>{{ $vpnAccount->created_at->format('d-m-y h:i A') }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            @if($vpnAccount->linkedRouters->count() > 0)
            <div class="card card-info card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-server mr-2"></i>Linked Routers ({{ $vpnAccount->linkedRouters->count() }})</h3>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        @foreach($vpnAccount->linkedRouters as $router)
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <strong>{{ $router->name }}</strong><br>
                                <small class="text-muted"><code>{{ $router->host }}:{{ $router->port }}</code></small>
                            </div>
                            <span class="badge badge-{{ $router->status == 'online' ? 'success' : 'secondary' }}">
                                {{ ucfirst($router->status ?? 'unknown') }}
                            </span>
                        </li>
                        @endforeach
                    </ul>
                </div>
            </div>
            @endif

            <div class="card card-outline card-warning">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-question-circle mr-2"></i>How to Use</h3>
                </div>
                <div class="card-body text-sm">
                    <ol class="pl-3 mb-0">
                        <li class="mb-2">Open <strong>Winbox</strong> and connect to your MikroTik router</li>
                        <li class="mb-2">Click <strong>"New Terminal"</strong> in the top menu</li>
                        <li class="mb-2">Click <strong>"Copy All Commands"</strong> button above</li>
                        <li class="mb-2"><strong>Right-click</strong> in the Winbox terminal and paste</li>
                        <li class="mb-2">Press <strong>Enter</strong> and wait for it to finish</li>
                        <li class="mb-2">Go to <strong>Interfaces</strong> and check the SSTP client is running (R status)</li>
                        <li class="mb-2">Add your router in billing using VPN IP: <code>{{ $vpnAccount->remote_address }}</code></li>
                    </ol>
                </div>
            </div>

            <a href="{{ route('admin.vpn.index') }}" class="btn btn-secondary btn-block mb-3">
                <i class="fas fa-arrow-left mr-1"></i>Back to VPN Accounts
            </a>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function copyAllCommands() {
    const text = document.getElementById('mikrotik-commands').innerText;
    navigator.clipboard.writeText(text).then(function() {
        toastr.success('All MikroTik commands copied to clipboard!');
    }).catch(function() {
        const textarea = document.createElement('textarea');
        textarea.value = text;
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);
        toastr.success('All MikroTik commands copied to clipboard!');
    });
}

function copyCommand(section) {
    const commands = {
        'profile': @json($commands['profile']),
        'interface': @json($commands['interface']),
        'nat': @json($commands['nat']),
        'route': @json($commands['route'])
    };
    const text = commands[section];
    navigator.clipboard.writeText(text).then(function() {
        toastr.success('Command copied!');
    }).catch(function() {
        const textarea = document.createElement('textarea');
        textarea.value = text;
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);
        toastr.success('Command copied!');
    });
}
</script>
@endpush
