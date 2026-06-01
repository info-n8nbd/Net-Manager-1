@extends('layouts.admin')
@section('title', 'Client Types')
@section('breadcrumb')<li class="breadcrumb-item active">Client Types</li>@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Client Type Management</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addModal">
                <i class="fas fa-plus"></i> Add Client Type
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
                    <td>Residential</td>
                    <td>Home/Personal Use</td>
                    <td><span class="badge badge-success">Active</span></td>
                    <td><button class="btn btn-sm btn-info"><i class="fas fa-edit"></i></button></td>
                </tr>
                <tr>
                    <td>2</td>
                    <td>Commercial</td>
                    <td>Business Use</td>
                    <td><span class="badge badge-success">Active</span></td>
                    <td><button class="btn btn-sm btn-info"><i class="fas fa-edit"></i></button></td>
                </tr>
                <tr>
                    <td>3</td>
                    <td>Corporate</td>
                    <td>Enterprise/Large Business</td>
                    <td><span class="badge badge-success">Active</span></td>
                    <td><button class="btn btn-sm btn-info"><i class="fas fa-edit"></i></button></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
@endsection
