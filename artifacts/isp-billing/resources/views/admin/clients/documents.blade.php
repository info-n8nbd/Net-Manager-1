@extends('layouts.admin')

@section('title', 'Client Documents')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.clients.index') }}">Clients</a></li>
<li class="breadcrumb-item"><a href="{{ route('admin.clients.show', $client) }}">{{ $client->name }}</a></li>
<li class="breadcrumb-item active">Documents</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-folder-open mr-2"></i>Client Documents</h3>
            </div>
            <div class="card-body">
                @if($documents->count() > 0)
                <div class="row">
                    @foreach($documents as $doc)
                    <div class="col-md-4 mb-3">
                        <div class="card">
                            <div class="card-body text-center">
                                @if(in_array(pathinfo($doc->document_name, PATHINFO_EXTENSION), ['jpg', 'jpeg', 'png']))
                                <img src="{{ asset('storage/' . $doc->file_path) }}" class="img-fluid mb-2" style="max-height: 120px;">
                                @else
                                <i class="fas fa-file-pdf fa-4x text-danger mb-2"></i>
                                @endif
                                <h6 class="mb-1">{{ $doc->document_type }}</h6>
                                <small class="text-muted">{{ $doc->document_name }}</small>
                            </div>
                            <div class="card-footer p-2">
                                <a href="{{ asset('storage/' . $doc->file_path) }}" class="btn btn-sm btn-info">
                                    <i class="fas fa-eye"></i> View
                                </a>
                                <a href="{{ asset('storage/' . $doc->file_path) }}" class="btn btn-sm btn-success" download>
                                    <i class="fas fa-download"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <div class="text-center py-5">
                    <i class="fas fa-folder-open fa-4x text-muted mb-3"></i>
                    <h5 class="text-muted">No Documents</h5>
                    <p class="text-muted">Upload documents using the form on the right.</p>
                </div>
                @endif
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-upload mr-2"></i>Upload Document</h3>
            </div>
            <form action="{{ route('admin.clients.documents.upload', $client) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="card-body">
                    <div class="form-group">
                        <label>Document Type <span class="text-danger">*</span></label>
                        <select name="document_type" class="form-control" required>
                            <option value="">Select Type</option>
                            <option value="NID">National ID (NID)</option>
                            <option value="Agreement">Service Agreement</option>
                            <option value="Photo">Client Photo</option>
                            <option value="Address Proof">Address Proof</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Document File <span class="text-danger">*</span></label>
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" id="document" name="document" accept=".pdf,.jpg,.jpeg,.png" required>
                            <label class="custom-file-label" for="document">Choose file</label>
                        </div>
                        <small class="text-muted">Allowed: PDF, JPG, PNG (Max 5MB)</small>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-upload mr-2"></i>Upload Document
                    </button>
                </div>
            </form>
        </div>
        
        <div class="card">
            <div class="card-header bg-info">
                <h3 class="card-title"><i class="fas fa-info-circle mr-2"></i>Client Info</h3>
            </div>
            <div class="card-body">
                <p><strong>Name:</strong> {{ $client->name }}</p>
                <p><strong>ID:</strong> {{ $client->client_id }}</p>
                <p><strong>NID:</strong> {{ $client->nid ?? 'Not provided' }}</p>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.querySelector('.custom-file-input').addEventListener('change', function(e) {
    var fileName = e.target.files[0].name;
    var nextSibling = e.target.nextElementSibling;
    nextSibling.innerText = fileName;
});
</script>
@endpush
