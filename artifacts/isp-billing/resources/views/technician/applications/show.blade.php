@extends('layouts.technician')

@section('title', 'Application Details')

@section('content')
<section class="content">
    <div class="container-fluid">
        @if($application->status == 'need_correction')
        <div class="alert alert-warning">
            <h5><i class="fas fa-exclamation-triangle"></i> Correction Required</h5>
            <p>{{ $application->correction_request }}</p>
        </div>
        @endif

        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Application Status</h3>
                        <span class="badge {{ $application->status_badge_class }} float-right">{{ $application->status_label }}</span>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <table class="table table-sm">
                                    <tr><th>Application #</th><td>{{ $application->application_number }}</td></tr>
                                    <tr><th>Full Name (EN)</th><td>{{ $application->full_name_english }}</td></tr>
                                    <tr><th>Full Name (BN)</th><td>{{ $application->full_name_bangla }}</td></tr>
                                    <tr><th>Mobile</th><td>{{ $application->mobile_number }}</td></tr>
                                    <tr><th>Email</th><td>{{ $application->email ?? '-' }}</td></tr>
                                    <tr><th>NID Number</th><td>{{ $application->nid_number }}</td></tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-sm">
                                    <tr><th>Connection Type</th><td>{{ ucfirst($application->connection_type) }}</td></tr>
                                    <tr><th>Package</th><td>{{ $application->package?->name ?? '-' }} @if($application->package?->price) <small class="text-muted">— ৳{{ number_format($application->package->price, 2) }}</small> @endif</td></tr>
                                    <tr><th>MikroTik Profile</th><td>{{ $application->mikrotik_profile_name ?: '-' }}</td></tr>
                                    <tr><th>Zone</th><td>{{ $application->zone?->name ?? '-' }}</td></tr>
                                    <tr><th>Sub-Zone</th><td>{{ $application->subZone?->name ?? '-' }}</td></tr>
                                    <tr><th>Connection Type</th><td>{{ ucfirst($application->ip_type) }}</td></tr>
                                    <tr><th>Installation Charge</th><td>৳{{ number_format($$application->installation_charge ?? 0) }}</td></tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-id-card"></i> NID Documents</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>NID Front</h6>
                                @if($application->nid_front_image)
                                <img src="{{ asset('storage/' . $application->nid_front_image) }}" class="img-fluid img-thumbnail" alt="NID Front">
                                @else
                                <p class="text-muted">Not uploaded</p>
                                @endif
                            </div>
                            <div class="col-md-6">
                                <h6>NID Back</h6>
                                @if($application->nid_back_image)
                                <img src="{{ asset('storage/' . $application->nid_back_image) }}" class="img-fluid img-thumbnail" alt="NID Back">
                                @else
                                <p class="text-muted">Not uploaded</p>
                                @endif
                            </div>
                        </div>
                        <div class="mt-3">
                            <strong>NID Verification Status:</strong>
                            @if($application->nid_verification_status == 'verified')
                            <span class="badge badge-success">Verified</span>
                            @elseif($application->nid_verification_status == 'rejected')
                            <span class="badge badge-danger">Rejected</span>
                            @else
                            <span class="badge badge-warning">Pending</span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-history"></i> Status History</h3>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Action</th>
                                    <th>By</th>
                                    <th>Note</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($application->statusLogs as $log)
                                <tr>
                                    <td>{{ $log->created_at->format('d-m-y H:i') }}</td>
                                    <td>{{ $log->action_label }}</td>
                                    <td>{{ $log->changedByUser?->name ?? 'System' }}</td>
                                    <td>{{ Str::limit($log->note, 50) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                @if($application->canBeEdited())
                <div class="card">
                    <div class="card-header bg-primary">
                        <h3 class="card-title">Actions</h3>
                    </div>
                    <div class="card-body">
                        <a href="{{ route('technician.applications.edit', $application) }}" class="btn btn-warning btn-block mb-2">
                            <i class="fas fa-edit"></i> Edit Application
                        </a>
                        @if($application->canBeSubmitted())
                        <form action="{{ route('technician.applications.submit', $application) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-success btn-block" onclick="return confirm('Submit this application for admin review?')">
                                <i class="fas fa-paper-plane"></i> Submit for Review
                            </button>
                        </form>
                        @endif
                    </div>
                </div>
                @endif

                @if($application->status == 'approved')
                <div class="card card-success">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-check-circle"></i> Approval Details</h3>
                    </div>
                    <div class="card-body">
                        <p><strong>Approved By:</strong> {{ $application->approver?->name }}</p>
                        <p><strong>Approved At:</strong> {{ $application->approved_at?->format('d-m-y H:i') }}</p>
                        @if($application->created_client_id)
                        <p><strong>Client ID:</strong> {{ $application->createdClient?->client_id }}</p>
                        @endif
                        @if($application->mikrotik_username)
                        <p><strong>MikroTik Username:</strong> {{ $application->mikrotik_username }}</p>
                        @endif
                    </div>
                </div>
                @endif

                @if($application->status == 'rejected')
                <div class="card card-danger">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-times-circle"></i> Rejection Details</h3>
                    </div>
                    <div class="card-body">
                        <p><strong>Rejected By:</strong> {{ $application->reviewer?->name }}</p>
                        <p><strong>Reason:</strong> {{ $application->rejection_reason }}</p>
                    </div>
                </div>
                @endif

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-clipboard-check"></i> Technician Info</h3>
                    </div>
                    <div class="card-body">
                        <p><strong>Feasibility:</strong> 
                            @if($application->installation_feasibility)
                            <span class="badge badge-success">Feasible</span>
                            @else
                            <span class="badge badge-danger">Not Feasible</span>
                            @endif
                        </p>
                        @if($application->technician_note)
                        <p><strong>Note:</strong> {{ $application->technician_note }}</p>
                        @endif
                        <p><strong>ONU Required:</strong> {{ $application->onu_required ? 'Yes' : 'No' }}</p>
                        <p><strong>Cable Type:</strong> {{ $application->connection_medium == 'fiber' ? 'Optical Fiber' : 'UTP Cable' }}</p>
                        @if($application->cable_length_meters)
                        <p><strong>Cable Length:</strong> {{ $application->cable_length_meters }} meters</p>
                        @endif
                        <p><strong>Fiber Brand:</strong> {{ $application->fiber_vendor ?: '—' }}</p>
                        <p><strong>Fiber Code:</strong> {{ $application->fiber_code ?: '—' }}</p>
                        <p><strong>Fiber Core Type:</strong> {{ $application->fiber_core_type ?: '—' }}</p>
                        <p><strong>Fiber Color:</strong> {{ $application->fiber_color ?: '—' }}</p>
                        <p><strong>Splitter Number:</strong> {{ $application->splitter_number ?: '—' }}</p>
                        <p><strong>Splitter Color:</strong> {{ $application->splitter_color ?: '—' }}</p>
                        <p><strong>ONU Brand:</strong> {{ $application->onu_brand ?: '—' }}</p>
                        <p><strong>ONU MAC/Serial:</strong> {{ $application->onu_mac ?: '—' }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
