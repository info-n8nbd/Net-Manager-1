@extends('layouts.admin')

@section('title', 'Vendor Details')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="#">Inventory</a></li>
<li class="breadcrumb-item"><a href="{{ route('admin.inventory.vendors.index') }}">Vendors</a></li>
<li class="breadcrumb-item active">{{ $vendor->name }}</li>
@endsection

@section('content')
<div class="card card-primary card-outline">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-truck mr-2"></i>Vendor Details</h3>
    </div>
    <div class="card-body">
        <table class="table table-bordered">
            <tr>
                <th style="width:30%">Name</th>
                <td>{{ $vendor->name }}</td>
            </tr>
            <tr>
                <th>Company</th>
                <td>{{ $vendor->company_name ?? '-' }}</td>
            </tr>
            <tr>
                <th>Phone</th>
                <td>{{ $vendor->phone ?? '-' }}</td>
            </tr>
            <tr>
                <th>Email</th>
                <td>{{ $vendor->email ?? '-' }}</td>
            </tr>
            <tr>
                <th>Address</th>
                <td>{{ $vendor->address ?? '-' }}</td>
            </tr>
        </table>
    </div>
    <div class="card-footer">
        <a href="{{ route('admin.inventory.vendors.edit', $vendor->id) }}" class="btn btn-warning btn-sm">
            <i class="fas fa-edit mr-1"></i> Edit
        </a>
        <form action="{{ route('admin.inventory.vendors.destroy', $vendor->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this vendor?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger btn-sm ml-1"><i class="fas fa-trash mr-1"></i> Delete</button>
        </form>
        <a href="{{ route('admin.inventory.vendors.index') }}" class="btn btn-secondary btn-sm ml-1">
            <i class="fas fa-arrow-left mr-1"></i> Back
        </a>
    </div>
</div>
@endsection
