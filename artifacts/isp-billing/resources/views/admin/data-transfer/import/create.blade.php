@extends('layouts.admin')

@section('title', 'New Import')

@section('content')
        <div class="row">
            <div class="col-lg-8">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Upload Import File</h3>
                    </div>
                    <form action="{{ route('admin.data-transfer.import.upload') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="card-body">
                            <div class="form-group">
                                <label for="name">Import Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                       id="name" name="name" value="{{ old('name') }}" 
                                       placeholder="e.g., Client Migration from XYZ Software" required>
                                @error('name')
                                <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="data_type">Data Type <span class="text-danger">*</span></label>
                                <select class="form-control @error('data_type') is-invalid @enderror" 
                                        id="data_type" name="data_type" required>
                                    <option value="">-- Select Data Type --</option>
                                    @foreach($dataTypes as $type)
                                    <option value="{{ $type }}" {{ old('data_type') === $type ? 'selected' : '' }}>
                                        {{ ucfirst($type) }}
                                    </option>
                                    @endforeach
                                </select>
                                @error('data_type')
                                <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="source_software">Source Software (Optional)</label>
                                <input type="text" class="form-control @error('source_software') is-invalid @enderror" 
                                       id="source_software" name="source_software" value="{{ old('source_software') }}"
                                       placeholder="e.g., UCRM, Splynx, MikroBill">
                                <small class="form-text text-muted">Enter the name of the software you're migrating from.</small>
                                @error('source_software')
                                <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="file">Import File <span class="text-danger">*</span></label>
                                <div class="custom-file">
                                    <input type="file" class="custom-file-input @error('file') is-invalid @enderror" 
                                           id="file" name="file" accept=".xlsx,.xls,.csv,.json,.zip" required>
                                    <label class="custom-file-label" for="file">Choose file</label>
                                    @error('file')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                                <small class="form-text text-muted">Supported formats: Excel (.xlsx, .xls), CSV, JSON, ZIP (max 50MB)</small>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-upload mr-1"></i> Upload & Continue
                            </button>
                            <a href="{{ route('admin.data-transfer.import.index') }}" class="btn btn-default">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card card-info">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-info-circle mr-1"></i> Import Guide</h3>
                    </div>
                    <div class="card-body">
                        <h6>Supported Data Types:</h6>
                        <ul class="list-unstyled mb-3">
                            <li><i class="fas fa-users text-primary mr-2"></i> <strong>Clients</strong> - Customer records</li>
                            <li><i class="fas fa-file-invoice text-warning mr-2"></i> <strong>Bills</strong> - Monthly invoices</li>
                            <li><i class="fas fa-money-bill text-success mr-2"></i> <strong>Payments</strong> - Payment records</li>
                            <li><i class="fas fa-map-marker-alt text-danger mr-2"></i> <strong>Zones</strong> - Service areas</li>
                            <li><i class="fas fa-map-pin text-info mr-2"></i> <strong>Areas</strong> - Subzones</li>
                            <li><i class="fas fa-cube text-indigo mr-2"></i> <strong>Packages</strong> - Internet plans</li>
                        </ul>

                        <h6>Import Process:</h6>
                        <ol class="pl-3">
                            <li>Download sample template below</li>
                            <li>Fill in your data in Excel</li>
                            <li>Upload the file</li>
                            <li>Map fields to system fields</li>
                            <li>Preview & validate data</li>
                            <li>Confirm import</li>
                        </ol>

                        <div class="alert alert-warning mb-2">
                            <i class="fas fa-exclamation-triangle mr-1"></i>
                            <strong>Important:</strong> Duplicates are auto-detected and skipped. Usernames are used exactly as typed.
                        </div>

                        <a href="{{ route('admin.data-transfer.import-guide') }}" class="btn btn-info btn-block">
                            <i class="fas fa-book mr-1"></i> View Full Import Guide
                        </a>
                    </div>
                </div>

                <div class="card card-success">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-file-excel mr-1"></i> Download Sample Templates</h3>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            <a href="{{ route('admin.data-transfer.sample-template', 'clients') }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <span><i class="fas fa-users text-primary mr-2"></i>Clients Template</span>
                                <span class="badge badge-primary badge-pill"><i class="fas fa-download"></i></span>
                            </a>
                            <a href="{{ route('admin.data-transfer.sample-template', 'bills') }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <span><i class="fas fa-file-invoice text-warning mr-2"></i>Bills Template</span>
                                <span class="badge badge-warning badge-pill"><i class="fas fa-download"></i></span>
                            </a>
                            <a href="{{ route('admin.data-transfer.sample-template', 'payments') }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <span><i class="fas fa-money-bill text-success mr-2"></i>Payments Template</span>
                                <span class="badge badge-success badge-pill"><i class="fas fa-download"></i></span>
                            </a>
                            <a href="{{ route('admin.data-transfer.sample-template', 'zones') }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <span><i class="fas fa-map-marker-alt text-danger mr-2"></i>Zones Template</span>
                                <span class="badge badge-danger badge-pill"><i class="fas fa-download"></i></span>
                            </a>
                            <a href="{{ route('admin.data-transfer.sample-template', 'areas') }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <span><i class="fas fa-map-pin text-info mr-2"></i>Areas Template</span>
                                <span class="badge badge-info badge-pill"><i class="fas fa-download"></i></span>
                            </a>
                            <a href="{{ route('admin.data-transfer.sample-template', 'packages') }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <span><i class="fas fa-cube text-indigo mr-2"></i>Packages Template</span>
                                <span class="badge badge-indigo badge-pill"><i class="fas fa-download"></i></span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

@push('scripts')
<script>
document.querySelector('.custom-file-input').addEventListener('change', function(e) {
    var fileName = e.target.files[0]?.name || 'Choose file';
    e.target.nextElementSibling.textContent = fileName;
});
</script>
@endpush
@endsection
