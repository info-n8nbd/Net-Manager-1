@extends('layouts.admin')
@section('title', 'Distributed Inventory Items')
@section('breadcrumb')<li class="breadcrumb-item active">Distributed Items</li>@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Distributed Inventory Items</h3>
    </div>
    <div class="card-body">
        <div class="alert alert-info">
            <i class="fas fa-boxes"></i> Track inventory items distributed to network locations.
        </div>
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Serial Number</th>
                    <th>Location</th>
                    <th>Assigned To</th>
                    <th>Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td colspan="6" class="text-center">No distributed items found</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
@endsection
