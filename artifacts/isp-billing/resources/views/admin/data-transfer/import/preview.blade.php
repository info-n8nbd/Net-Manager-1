@extends('layouts.admin')

@section('title', 'Import Preview')

@section('content')
        <div class="row">
            <div class="col-md-3 col-sm-6 col-12">
                <div class="info-box bg-info">
                    <span class="info-box-icon"><i class="fas fa-list"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Total Rows</span>
                        <span class="info-box-number">{{ number_format($job->total_rows) }}</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 col-12">
                <div class="info-box bg-success">
                    <span class="info-box-icon"><i class="fas fa-check"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Valid Rows</span>
                        <span class="info-box-number">{{ number_format($job->valid_rows) }}</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 col-12">
                <div class="info-box bg-danger">
                    <span class="info-box-icon"><i class="fas fa-times"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Invalid Rows</span>
                        <span class="info-box-number">{{ number_format($job->invalid_rows) }}</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 col-12">
                <div class="info-box bg-warning">
                    <span class="info-box-icon"><i class="fas fa-clone"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Duplicates</span>
                        <span class="info-box-number">{{ number_format($job->duplicate_rows) }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Import: {{ $job->name }}</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <dl class="row">
                            <dt class="col-sm-4">File:</dt>
                            <dd class="col-sm-8">{{ $job->file_name }}</dd>
                            <dt class="col-sm-4">Data Type:</dt>
                            <dd class="col-sm-8">{{ ucfirst($job->data_type) }}</dd>
                            <dt class="col-sm-4">Source:</dt>
                            <dd class="col-sm-8">{{ $job->source_software ?? 'Not specified' }}</dd>
                        </dl>
                    </div>
                    <div class="col-md-6">
                        <dl class="row">
                            <dt class="col-sm-4">Status:</dt>
                            <dd class="col-sm-8"><span class="badge {{ $job->getStatusBadgeClass() }}">{{ ucfirst($job->status) }}</span></dd>
                            <dt class="col-sm-4">Uploaded:</dt>
                            <dd class="col-sm-8">{{ $job->created_at->format('d-m-y H:i') }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <ul class="nav nav-tabs" id="previewTabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" id="valid-tab" data-toggle="tab" href="#valid" role="tab">
                    <i class="fas fa-check text-success mr-1"></i> Valid ({{ $validLogs->count() }})
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="invalid-tab" data-toggle="tab" href="#invalid" role="tab">
                    <i class="fas fa-times text-danger mr-1"></i> Invalid ({{ $invalidLogs->count() }})
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="duplicate-tab" data-toggle="tab" href="#duplicate" role="tab">
                    <i class="fas fa-clone text-warning mr-1"></i> Duplicates ({{ $duplicateLogs->count() }})
                </a>
            </li>
        </ul>

        <div class="tab-content" id="previewTabContent">
            <div class="tab-pane fade show active" id="valid" role="tabpanel">
                <div class="card card-success card-outline card-outline-tabs">
                    <div class="card-body table-responsive">
                        @if($validLogs->count() > 0)
                        <table class="table table-sm table-striped">
                            <thead>
                                <tr>
                                    <th>Row</th>
                                    @foreach(array_keys($validLogs->first()->mapped_data ?? []) as $key)
                                    <th>{{ ucfirst(str_replace('_', ' ', $key)) }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($validLogs as $log)
                                <tr>
                                    <td>{{ $log->row_number }}</td>
                                    @foreach($log->mapped_data ?? [] as $value)
                                    <td>{{ is_array($value) ? json_encode($value) : $value }}</td>
                                    @endforeach
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                        @else
                        <p class="text-muted text-center mb-0">No valid rows found.</p>
                        @endif
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="invalid" role="tabpanel">
                <div class="card card-danger card-outline card-outline-tabs">
                    <div class="card-body table-responsive">
                        @if($invalidLogs->count() > 0)
                        <table class="table table-sm table-striped">
                            <thead>
                                <tr>
                                    <th>Row</th>
                                    <th>Errors</th>
                                    <th>Data Preview</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($invalidLogs as $log)
                                <tr>
                                    <td>{{ $log->row_number }}</td>
                                    <td>
                                        <ul class="mb-0 pl-3">
                                            @foreach($log->validation_errors ?? [] as $error)
                                            <li class="text-danger">{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    </td>
                                    <td><small>{{ json_encode(array_slice($log->original_data ?? [], 0, 3)) }}</small></td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                        @else
                        <p class="text-muted text-center mb-0">No invalid rows found.</p>
                        @endif
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="duplicate" role="tabpanel">
                <div class="card card-warning card-outline card-outline-tabs">
                    <div class="card-body table-responsive">
                        @if($duplicateLogs->count() > 0)
                        <table class="table table-sm table-striped">
                            <thead>
                                <tr>
                                    <th>Row</th>
                                    <th>Data Preview</th>
                                    <th>Reason</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($duplicateLogs as $log)
                                <tr>
                                    <td>{{ $log->row_number }}</td>
                                    <td><small>{{ json_encode(array_slice($log->mapped_data ?? [], 0, 4)) }}</small></td>
                                    <td><span class="badge badge-warning">Duplicate record exists in system</span></td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                        @else
                        <p class="text-muted text-center mb-0">No duplicate rows found.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-body">
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    <strong>Ready to Import:</strong> 
                    {{ number_format($job->valid_rows) }} records will be imported.
                    {{ $job->invalid_rows + $job->duplicate_rows }} records will be skipped.
                </div>
                
                <form action="{{ route('admin.data-transfer.import.confirm', $job->id) }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-success btn-lg" 
                            onclick="return confirm('Are you sure you want to import {{ $job->valid_rows }} records? This action cannot be undone.')">
                        <i class="fas fa-play mr-1"></i> Confirm & Start Import
                    </button>
                </form>
                
                <a href="{{ route('admin.data-transfer.import.mapping', $job->id) }}" class="btn btn-warning btn-lg">
                    <i class="fas fa-arrow-left mr-1"></i> Back to Mapping
                </a>
                
                <a href="{{ route('admin.data-transfer.import.cancel', $job->id) }}" class="btn btn-danger btn-lg"
                   onclick="return confirm('Are you sure you want to cancel this import?')">
                    <i class="fas fa-times mr-1"></i> Cancel Import
                </a>
            </div>
        </div>
@endsection
