@extends('layouts.billman')
@section('title', 'My Profile')
@section('breadcrumb')
<li class="breadcrumb-item active">Profile</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-body text-center">
                <div class="d-inline-flex align-items-center justify-content-center rounded-circle text-white mb-3" style="width: 80px; height: 80px; background: #1e3c72; font-size: 32px; font-weight: 600;">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>
                <h4 class="mb-1">{{ $user->name }}</h4>
                <p class="text-muted mb-3">Bill Collector</p>
                <table class="table table-borderless text-left">
                    <tr>
                        <td class="text-muted" width="40%"><i class="fas fa-envelope mr-2"></i>Email</td>
                        <td>{{ $user->email }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted"><i class="fas fa-phone mr-2"></i>Phone</td>
                        <td>{{ $user->phone ?? 'N/A' }}</td>
                    </tr>
                    @if($user->employee)
                    <tr>
                        <td class="text-muted"><i class="fas fa-id-card mr-2"></i>Employee ID</td>
                        <td>{{ $user->employee->employee_id ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted"><i class="fas fa-map-marker-alt mr-2"></i>Address</td>
                        <td>{{ $user->employee->address ?? 'N/A' }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td class="text-muted"><i class="fas fa-calendar mr-2"></i>Joined</td>
                        <td>{{ $user->created_at->format('d-m-Y') }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
