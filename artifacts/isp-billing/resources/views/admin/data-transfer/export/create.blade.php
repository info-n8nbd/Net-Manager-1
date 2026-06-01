@extends('layouts.admin')

@section('title', 'New Export')

@section('content')
        <div class="row">
            <div class="col-lg-8">
                <div class="card card-success">
                    <div class="card-header">
                        <h3 class="card-title">Configure Export</h3>
                    </div>
                    <form action="{{ route('admin.data-transfer.export.process') }}" method="POST">
                        @csrf
                        <div class="card-body">
                            <div class="form-group">
                                <label for="name">Export Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                       id="name" name="name" value="{{ old('name', 'Full Export - ' . date('Y-m-d')) }}" required>
                                @error('name')
                                <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label>Select Data to Export <span class="text-danger">*</span></label>
                                <div class="row">
                                    @foreach($dataTypes as $type)
                                    <div class="col-md-4 col-sm-6">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" class="custom-control-input" 
                                                   id="type_{{ $type }}" name="data_types[]" value="{{ $type }}"
                                                   {{ in_array($type, old('data_types', [])) ? 'checked' : '' }}>
                                            <label class="custom-control-label" for="type_{{ $type }}">
                                                {{ ucfirst($type) }}
                                            </label>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                                @error('data_types')
                                <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="file_format">Export Format <span class="text-danger">*</span></label>
                                <div class="row">
                                    <div class="col-md-3 col-sm-6">
                                        <div class="custom-control custom-radio">
                                            <input type="radio" id="format_xlsx" name="file_format" value="xlsx" 
                                                   class="custom-control-input" {{ old('file_format', 'xlsx') === 'xlsx' ? 'checked' : '' }}>
                                            <label class="custom-control-label" for="format_xlsx">
                                                <i class="fas fa-file-excel text-success mr-1"></i> Excel (.xlsx)
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-sm-6">
                                        <div class="custom-control custom-radio">
                                            <input type="radio" id="format_csv" name="file_format" value="csv" 
                                                   class="custom-control-input" {{ old('file_format') === 'csv' ? 'checked' : '' }}>
                                            <label class="custom-control-label" for="format_csv">
                                                <i class="fas fa-file-csv text-info mr-1"></i> CSV
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-sm-6">
                                        <div class="custom-control custom-radio">
                                            <input type="radio" id="format_json" name="file_format" value="json" 
                                                   class="custom-control-input" {{ old('file_format') === 'json' ? 'checked' : '' }}>
                                            <label class="custom-control-label" for="format_json">
                                                <i class="fas fa-file-code text-warning mr-1"></i> JSON
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-sm-6">
                                        <div class="custom-control custom-radio">
                                            <input type="radio" id="format_zip" name="file_format" value="zip" 
                                                   class="custom-control-input" {{ old('file_format') === 'zip' ? 'checked' : '' }}>
                                            <label class="custom-control-label" for="format_zip">
                                                <i class="fas fa-file-archive text-secondary mr-1"></i> ZIP (All formats)
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                @error('file_format')
                                <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="callout callout-info">
                                <h5><i class="fas fa-info-circle mr-1"></i> Export Information</h5>
                                <ul class="mb-0">
                                    <li><strong>Excel:</strong> Multi-sheet workbook, ideal for viewing and analysis</li>
                                    <li><strong>CSV:</strong> Single data type, compatible with most systems</li>
                                    <li><strong>JSON:</strong> Machine-readable, includes metadata for re-import</li>
                                    <li><strong>ZIP:</strong> Complete package with JSON + CSV files</li>
                                </ul>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="fas fa-download mr-1"></i> Generate Export
                            </button>
                            <a href="{{ route('admin.data-transfer.export.index') }}" class="btn btn-default">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card card-info">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-shield-alt mr-1"></i> Data Security</h3>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2">
                                <i class="fas fa-check text-success mr-2"></i>
                                All relationships preserved
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-check text-success mr-2"></i>
                                No data truncation
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-check text-success mr-2"></i>
                                Includes metadata for re-import
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-check text-success mr-2"></i>
                                Export files expire in 7 days
                            </li>
                            <li>
                                <i class="fas fa-lock text-info mr-2"></i>
                                Only authorized admins can export
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="card bg-light">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-database mr-1"></i> Quick Export</h3>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Need a full backup? Use the button below for a complete export.</p>
                        <button type="button" class="btn btn-primary btn-block" id="selectAll">
                            <i class="fas fa-check-double mr-1"></i> Select All Data Types
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

@push('scripts')
<script>
document.getElementById('selectAll').addEventListener('click', function() {
    document.querySelectorAll('input[name="data_types[]"]').forEach(function(checkbox) {
        checkbox.checked = true;
    });
});
</script>
@endpush
@endsection
