@extends('layouts.admin')

@section('title', 'Phase 2: MikroTik Discovery')

@section('content')
        @include('partials.alerts')
        @include('admin.migration._phase-steps', ['currentPhase' => 2, 'run' => $run])

        <div class="callout callout-info">
            <h5><i class="fas fa-shield-alt"></i> Read-Only Operation</h5>
            <p>This phase only READS data from your routers. No changes will be made to any router configuration.</p>
        </div>

        @if(!$run->isPhaseComplete(2))
        <div class="card">
            <div class="card-header bg-primary">
                <h3 class="card-title"><i class="fas fa-server"></i> Select Routers to Scan</h3>
            </div>
            <div class="card-body">
                @if($devices->isEmpty())
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i> No active MikroTik devices found.
                    <a href="{{ route('admin.network.devices.index') }}">Add routers first</a>.
                </div>
                @else
                <form action="{{ route('admin.migration.phase2-scan', $run) }}" method="POST">
                    @csrf
                    <div class="row">
                        @foreach($devices as $device)
                        <div class="col-md-4 mb-3">
                            <div class="card">
                                <div class="card-body p-3">
                                    <div class="form-check">
                                        <input type="checkbox" name="device_ids[]" value="{{ $device->id }}" class="form-check-input" id="device{{ $device->id }}" checked>
                                        <label class="form-check-label" for="device{{ $device->id }}">
                                            <strong>{{ $device->name }}</strong><br>
                                            <small class="text-muted">{{ $device->host ?? $device->ip_address }}:{{ $device->port ?? 8728 }}</small>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>

                    <div class="row mt-3">
                        <div class="col-md-6">
                            <button type="submit" class="btn btn-primary btn-lg btn-block" onclick="this.disabled=true;this.innerHTML='<i class=\'fas fa-spinner fa-spin\'></i> Scanning Routers...';this.form.submit();">
                                <i class="fas fa-search"></i> Start Discovery Scan
                            </button>
                        </div>
                    </div>
                </form>
                <div class="row mt-2">
                    <div class="col-md-6 offset-md-6">
                        <form action="{{ route('admin.migration.phase2-skip', $run) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-outline-secondary btn-lg btn-block" onclick="return confirm('Skip router discovery? You can still do reconciliation later.')">
                                <i class="fas fa-forward"></i> Skip This Phase
                            </button>
                        </form>
                    </div>
                </div>
                @endif
            </div>
        </div>
        @endif

        @if($snapshots->count() > 0)
        <div class="card">
            <div class="card-header bg-success">
                <h3 class="card-title"><i class="fas fa-check"></i> Discovery Results: {{ $snapshots->count() }} PPPoE Users Found</h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Profile</th>
                                <th>Service</th>
                                <th>Status</th>
                                <th>Router</th>
                                <th>Comment</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($snapshots->take(50) as $snap)
                            <tr>
                                <td><code>{{ $snap->username }}</code></td>
                                <td>{{ $snap->profile }}</td>
                                <td>{{ $snap->service }}</td>
                                <td>
                                    @if($snap->disabled)
                                    <span class="badge badge-danger">Disabled</span>
                                    @else
                                    <span class="badge badge-success">Enabled</span>
                                    @endif
                                </td>
                                <td>{{ $snap->device_name }}</td>
                                <td>{{ Str::limit($snap->comment, 30) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($snapshots->count() > 50)
                <div class="card-footer text-center text-muted">
                    Showing 50 of {{ $snapshots->count() }} users
                </div>
                @endif
            </div>
        </div>

        @if($run->isPhaseComplete(2))
        <div class="text-center mt-3">
            <a href="{{ route('admin.migration.phase3', $run) }}" class="btn btn-primary btn-lg">
                <i class="fas fa-arrow-right"></i> Proceed to Phase 3: Reconciliation
            </a>
        </div>
        @endif
        @endif
@endsection
