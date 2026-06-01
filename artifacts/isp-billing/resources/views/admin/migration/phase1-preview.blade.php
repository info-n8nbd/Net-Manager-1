@extends('layouts.admin')

@section('title', 'Phase 1: Preview & Confirm')

@section('content')
        @include('partials.alerts')
        @include('admin.migration._phase-steps', ['currentPhase' => 1, 'run' => $run])

        <div class="row">
            <div class="col-md-3">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3>{{ $validation['valid'] }}</h3>
                        <p>Valid Records</p>
                    </div>
                    <div class="icon"><i class="fas fa-check"></i></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3>{{ $validation['invalid'] }}</h3>
                        <p>Invalid Records</p>
                    </div>
                    <div class="icon"><i class="fas fa-times"></i></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3>{{ $validation['duplicate'] }}</h3>
                        <p>Duplicates</p>
                    </div>
                    <div class="icon"><i class="fas fa-copy"></i></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3>{{ $validation['total'] }}</h3>
                        <p>Total Records</p>
                    </div>
                    <div class="icon"><i class="fas fa-list"></i></div>
                </div>
            </div>
        </div>

        @if(!empty($validation['warnings']))
        <div class="card">
            <div class="card-header bg-warning">
                <h3 class="card-title"><i class="fas fa-exclamation-triangle"></i> Warnings</h3>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    @foreach($validation['warnings'] as $warning)
                    <li class="list-group-item list-group-item-warning">{{ $warning }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
        @endif

        @if(count($validation['valid_rows']) > 0)
        <div class="card">
            <div class="card-header bg-success">
                <h3 class="card-title"><i class="fas fa-check"></i> Valid Records (showing first 20)</h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-striped mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Phone</th>
                                <th>Username</th>
                                <th>Package</th>
                                <th>Fee</th>
                                <th>Due</th>
                                <th>Advance</th>
                                <th>Status</th>
                                <th>Router</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach(array_slice($validation['valid_rows'], 0, 20) as $item)
                            <tr>
                                <td>{{ $item['row'] }}</td>
                                <td>{{ $item['data']['name'] ?? '' }}</td>
                                <td>{{ $item['data']['phone'] ?? '' }}</td>
                                <td>{{ $item['data']['username'] ?? '-' }}</td>
                                <td>{{ $item['data']['package_name'] ?? '-' }}</td>
                                <td>{{ $item['data']['monthly_fee'] ?? '-' }}</td>
                                <td>{{ $item['data']['due_amount'] ?? '0' }}</td>
                                <td>{{ $item['data']['advance_balance'] ?? '0' }}</td>
                                <td>{{ $item['data']['status'] ?? 'active' }}</td>
                                <td>{{ $item['data']['router_name'] ?? '-' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif

        @if(count($validation['invalid_rows']) > 0)
        <div class="card">
            <div class="card-header bg-danger">
                <h3 class="card-title"><i class="fas fa-times"></i> Invalid Records (will be skipped)</h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Row</th><th>Name</th><th>Phone</th><th>Errors</th></tr></thead>
                        <tbody>
                            @foreach(array_slice($validation['invalid_rows'], 0, 20) as $item)
                            <tr class="table-danger">
                                <td>{{ $item['row'] }}</td>
                                <td>{{ $item['data']['name'] ?? '' }}</td>
                                <td>{{ $item['data']['phone'] ?? '' }}</td>
                                <td>{{ implode(', ', $item['errors']) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif

        @if(count($validation['duplicate_rows']) > 0)
        <div class="card">
            <div class="card-header bg-warning">
                <h3 class="card-title"><i class="fas fa-copy"></i> Duplicate Records</h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Row</th><th>Name</th><th>Phone</th><th>Username</th></tr></thead>
                        <tbody>
                            @foreach(array_slice($validation['duplicate_rows'], 0, 20) as $item)
                            <tr class="table-warning">
                                <td>{{ $item['row'] }}</td>
                                <td>{{ $item['data']['name'] ?? '' }}</td>
                                <td>{{ $item['data']['phone'] ?? '' }}</td>
                                <td>{{ $item['data']['username'] ?? '' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif

        @if($validation['valid'] > 0)
        <div class="card">
            <div class="card-body text-center">
                <form action="{{ route('admin.migration.phase1-confirm', $run) }}" method="POST">
                    @csrf
                    @if($validation['duplicate'] > 0)
                    <div class="form-check mb-3">
                        <input type="checkbox" name="include_duplicates" value="1" class="form-check-input" id="includeDuplicates">
                        <label class="form-check-label" for="includeDuplicates">
                            Also import {{ $validation['duplicate'] }} duplicate records (use with caution)
                        </label>
                    </div>
                    @endif
                    <button type="submit" class="btn btn-success btn-lg" onclick="return confirm('Import {{ $validation['valid'] }} clients? This cannot be undone easily.')">
                        <i class="fas fa-check"></i> Confirm Import ({{ $validation['valid'] }} clients)
                    </button>
                </form>
            </div>
        </div>
        @else
        <div class="alert alert-danger text-center">
            No valid records to import. Please fix the issues and try again.
            <br><a href="{{ route('admin.migration.phase1', $run) }}" class="btn btn-primary mt-2">Re-upload File</a>
        </div>
        @endif
@endsection
