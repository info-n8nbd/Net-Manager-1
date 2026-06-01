@extends('layouts.admin')
@section('title', 'Returned Products & Fiber — Detail')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.inventory.returned-equipments') }}">Returned Products &amp; Fiber</a></li>
<li class="breadcrumb-item active">Detail</li>
@endsection
@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-6">
            <div class="card card-primary card-outline">
                <div class="card-header"><h3 class="card-title"><i class="fas fa-info-circle mr-2"></i>Equipment Information</h3></div>
                <div class="card-body">
                    <table class="table table-sm table-bordered">
                        <tr><th width="35%">Type</th><td><span class="badge badge-primary">{{ $returnedEquipment->equipment_type }}</span></td></tr>
                        <tr><th>Equipment Name</th><td>{{ $returnedEquipment->equipment_name }}</td></tr>
                        <tr><th>Serial Number</th><td>{{ $returnedEquipment->serial_number ?? 'N/A' }}</td></tr>
                        <tr><th>MAC Address</th><td>{{ $returnedEquipment->mac_address ?? 'N/A' }}</td></tr>
                        <tr><th>Model</th><td>{{ $returnedEquipment->model ?? 'N/A' }}</td></tr>
                        <tr><th>Brand</th><td>{{ $returnedEquipment->brand ?? 'N/A' }}</td></tr>
                        <tr><th>PON Port</th><td>{{ $returnedEquipment->pon_port ?? 'N/A' }}</td></tr>
                        <tr><th>ONU Index</th><td>{{ $returnedEquipment->onu_index ?? 'N/A' }}</td></tr>
                        <tr><th>Condition</th><td><span class="badge badge-{{ $returnedEquipment->condition==='good'?'success':($returnedEquipment->condition==='damaged'?'danger':'warning') }} badge-lg">{{ ucfirst($returnedEquipment->condition) }}</span></td></tr>
                        <tr><th>Remarks</th><td>{{ $returnedEquipment->remarks ?? 'N/A' }}</td></tr>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card card-info card-outline">
                <div class="card-header"><h3 class="card-title"><i class="fas fa-user mr-2"></i>Return Details</h3></div>
                <div class="card-body">
                    <table class="table table-sm table-bordered">
                        <tr><th width="35%">Client ID</th><td>{{ $returnedEquipment->client_client_id }}</td></tr>
                        <tr><th>Client Name</th><td>{{ $returnedEquipment->client_name }}</td></tr>
                        <tr><th>Return Date</th><td>{{ $returnedEquipment->return_date->format('d M Y') }}</td></tr>
                        <tr><th>Received By</th><td>{{ $returnedEquipment->receivedByUser->name ?? 'N/A' }}</td></tr>
                        <tr><th>Returned By</th><td>{{ $returnedEquipment->returnedByUser->name ?? 'N/A' }}</td></tr>
                        <tr><th>Record Created</th><td>{{ $returnedEquipment->created_at->format('d M Y h:i A') }}</td></tr>
                    </table>
                </div>
            </div>
            <a href="{{ route('admin.inventory.returned-equipments') }}" class="btn btn-secondary"><i class="fas fa-arrow-left mr-2"></i>Back to List</a>
        </div>
    </div>
</div>
@endsection
