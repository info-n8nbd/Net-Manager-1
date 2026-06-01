@extends('layouts.admin')
@section('title', 'Protocol Types')
@section('breadcrumb')<li class="breadcrumb-item active">Protocol Types</li>@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Protocol Type Management</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addModal">
                <i class="fas fa-plus"></i> Add Protocol Type
            </button>
        </div>
    </div>
    <div class="card-body">
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Description</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>1</td>
                    <td>IPv4</td>
                    <td>Internet Protocol version 4</td>
                    <td><span class="badge badge-success">Active</span></td>
                    <td><button class="btn btn-sm btn-info"><i class="fas fa-edit"></i></button></td>
                </tr>
                <tr>
                    <td>2</td>
                    <td>IPv6</td>
                    <td>Internet Protocol version 6</td>
                    <td><span class="badge badge-success">Active</span></td>
                    <td><button class="btn btn-sm btn-info"><i class="fas fa-edit"></i></button></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
@endsection
