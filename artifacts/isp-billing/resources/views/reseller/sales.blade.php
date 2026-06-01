@extends('layouts.reseller')
@section('title', 'Sales & Service')
@section('breadcrumb')<li class="breadcrumb-item active">Sales & Service</li>@endsection

@section('content')
<div class="row">
    <div class="col-md-6">
        <div class="card card-info">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-chart-line mr-2"></i>Sales Overview</h3>
            </div>
            <div class="card-body">
                <div class="text-center py-4">
                    <i class="fas fa-chart-pie fa-4x text-muted mb-3"></i>
                    <p class="text-muted">Sales tracking coming soon</p>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card card-success">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-tools mr-2"></i>Service Charges</h3>
            </div>
            <div class="card-body">
                <div class="text-center py-4">
                    <i class="fas fa-wrench fa-4x text-muted mb-3"></i>
                    <p class="text-muted">Service tracking coming soon</p>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-info-circle mr-2"></i>Quick Links</h3>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-4 mb-2">
                <a href="{{ route('reseller.products') }}" class="btn btn-outline-primary btn-block">
                    <i class="fas fa-box mr-2"></i>View Products
                </a>
            </div>
            <div class="col-md-4 mb-2">
                <a href="{{ route('reseller.purchases') }}" class="btn btn-outline-info btn-block">
                    <i class="fas fa-shopping-cart mr-2"></i>Purchase History
                </a>
            </div>
            <div class="col-md-4 mb-2">
                <a href="{{ route('reseller.clients') }}" class="btn btn-outline-success btn-block">
                    <i class="fas fa-users mr-2"></i>My Clients
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
