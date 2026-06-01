@extends('layouts.admin')

@section('title', 'Phase 3: Reconciliation')

@section('content')
        @include('partials.alerts')
        @include('admin.migration._phase-steps', ['currentPhase' => 3, 'run' => $run])

        @if(!$run->isPhaseComplete(3))
        <div class="card">
            <div class="card-body text-center">
                <p>Compare your imported billing data with MikroTik router users to find discrepancies.</p>
                <div class="row justify-content-center">
                    <div class="col-md-4">
                        <form action="{{ route('admin.migration.phase3-run', $run) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-primary btn-lg btn-block" onclick="this.disabled=true;this.innerHTML='<i class=\'fas fa-spinner fa-spin\'></i> Running Reconciliation...';this.form.submit();">
                                <i class="fas fa-balance-scale"></i> Run Reconciliation
                            </button>
                        </form>
                    </div>
                    <div class="col-md-4">
                        <form action="{{ route('admin.migration.phase3-skip', $run) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-outline-secondary btn-lg btn-block">
                                <i class="fas fa-forward"></i> Skip
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        @endif

        @if($summary['total'] > 0)
        <div class="row">
            <div class="col-md-2">
                <div class="small-box bg-success">
                    <div class="inner"><h3>{{ $summary['matched'] }}</h3><p>Matched</p></div>
                    <div class="icon"><i class="fas fa-check"></i></div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="small-box bg-danger">
                    <div class="inner"><h3>{{ $summary['ghost_users'] }}</h3><p>Ghost Users</p></div>
                    <div class="icon"><i class="fas fa-ghost"></i></div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="small-box bg-warning">
                    <div class="inner"><h3>{{ $summary['profile_mismatch'] }}</h3><p>Profile Mismatch</p></div>
                    <div class="icon"><i class="fas fa-not-equal"></i></div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="small-box bg-info">
                    <div class="inner"><h3>{{ $summary['status_mismatch'] }}</h3><p>Status Mismatch</p></div>
                    <div class="icon"><i class="fas fa-exchange-alt"></i></div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="small-box bg-primary">
                    <div class="inner"><h3>{{ $summary['synced'] }}</h3><p>Already Synced</p></div>
                    <div class="icon"><i class="fas fa-check-double"></i></div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="small-box bg-secondary">
                    <div class="inner"><h3>{{ $summary['skipped'] }}</h3><p>Skipped</p></div>
                    <div class="icon"><i class="fas fa-forward"></i></div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-list"></i> Reconciliation Details</h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Router</th>
                                <th>Router Profile</th>
                                <th>Router Status</th>
                                <th>Billing Client</th>
                                <th>Match Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($snapshots as $snap)
                            <tr>
                                <td><code>{{ $snap->username }}</code></td>
                                <td>{{ $snap->device_name }}</td>
                                <td>{{ $snap->profile }}</td>
                                <td>
                                    @if($snap->disabled)
                                    <span class="badge badge-danger">Disabled</span>
                                    @else
                                    <span class="badge badge-success">Enabled</span>
                                    @endif
                                </td>
                                <td>
                                    @if($snap->billing_client_id)
                                    <a href="{{ route('admin.clients.show', $snap->billing_client_id) }}">
                                        {{ $snap->billingClient->name ?? 'Client #' . $snap->billing_client_id }}
                                    </a>
                                    @else
                                    <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @php
                                    $badgeClass = match($snap->match_status) {
                                        'matched', 'synced' => 'badge-success',
                                        'ghost_user' => 'badge-danger',
                                        'profile_mismatch' => 'badge-warning',
                                        'status_mismatch' => 'badge-info',
                                        'skipped' => 'badge-secondary',
                                        default => 'badge-light',
                                    };
                                    @endphp
                                    <span class="badge {{ $badgeClass }}">{{ ucwords(str_replace('_', ' ', $snap->match_status)) }}</span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @if($snapshots->hasPages())
            <div class="card-footer"><div class="d-flex flex-wrap justify-content-between align-items-center mt-2 mb-1">
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
        {{ $snapshots->appends(request()->query())->links() }}
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
</script></div>
            @endif
        </div>

        @if(count($billingOnlyClients) > 0)
        <div class="card">
            <div class="card-header bg-info">
                <h3 class="card-title"><i class="fas fa-user-slash"></i> Billing-Only Clients ({{ count($billingOnlyClients) }} not on any router)</h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Client ID</th><th>Name</th><th>Username</th><th>Package</th><th>Status</th></tr></thead>
                        <tbody>
                            @foreach($billingOnlyClients->take(30) as $client)
                            <tr>
                                <td>{{ $client->client_id }}</td>
                                <td>{{ $client->name }}</td>
                                <td><code>{{ $client->username }}</code></td>
                                <td>{{ $client->package->name ?? '-' }}</td>
                                <td><span class="badge badge-{{ $client->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($client->status) }}</span></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif

        @if($run->isPhaseComplete(3))
        <div class="text-center mt-3">
            <a href="{{ route('admin.migration.phase4', $run) }}" class="btn btn-primary btn-lg">
                <i class="fas fa-arrow-right"></i> Proceed to Phase 4: Safe Sync
            </a>
        </div>
        @endif
        @endif
@endsection
