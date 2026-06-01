@extends('layouts.admin')

@section('title', 'Export Applications')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
<li class="breadcrumb-item"><a href="{{ route('admin.applications.index') }}">Applications</a></li>
<li class="breadcrumb-item active">Export</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Export Applications</h3>
    </div>
    <div class="card-body">
        <p>Export application data in your preferred format.</p>
        <div class="row">
            <div class="col-md-4">
                <a href="{{ route('admin.applications.export', ['format' => 'csv']) }}" class="btn btn-block btn-outline-primary">
                    <i class="fas fa-file-csv mr-2"></i> Export as CSV
                </a>
            </div>
            <div class="col-md-4">
                <a href="{{ route('admin.applications.export', ['format' => 'pdf']) }}" class="btn btn-block btn-outline-danger">
                    <i class="fas fa-file-pdf mr-2"></i> Export as PDF
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
