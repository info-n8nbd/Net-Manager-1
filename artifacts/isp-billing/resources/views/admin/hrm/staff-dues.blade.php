@extends('layouts.admin')

@section('title', 'Staff Collection Dues')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
<li class="breadcrumb-item">HRM</li>
<li class="breadcrumb-item active">Staff Collection Dues</li>
@endsection

@section('content')
<style>
@media (min-width: 768px) { .mobile-staff-cards { display: none !important; } }
@media (max-width: 767px) { .desktop-staff-table { display: none !important; } }
</style>

<div class="row">
    <div class="col-lg-8">
        <div class="card card-danger card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-hand-holding-usd mr-2"></i>Staff Collection Dues</h3>
                @php $totalDue = collect($staffDues)->sum('current_due'); @endphp
                @if($totalDue > 0)
                <span class="badge badge-danger ml-2">Total Due: ৳{{ number_format($totalDue, 0) }}</span>
                @endif
            </div>
            <div class="card-body p-0">
                <!-- Mobile Cards -->
                <div class="mobile-staff-cards p-3">
                    <div class="device-card-grid">
                        @forelse($staffDues as $staff)
                        <div class="device-card">
                            <div class="device-card-header {{ $staff['current_due'] > 0 ? 'offline' : 'online' }}">
                                <div class="device-name">{{ $staff['name'] }}</div>
                                <span class="device-status">{{ ucfirst($staff['user_type']) }}</span>
                            </div>
                            <div class="device-card-body">
                                <div class="device-info-row">
                                    <span class="device-info-label"><i class="fas fa-phone mr-1"></i>Phone</span>
                                    <span class="device-info-value">{{ $staff['phone'] }}</span>
                                </div>
                                <div class="device-info-row">
                                    <span class="device-info-label"><i class="fas fa-coins mr-1"></i>Collected</span>
                                    <span class="device-info-value">৳{{ number_format($staff['total_collected'], 0) }}</span>
                                </div>
                                <div class="device-info-row">
                                    <span class="device-info-label"><i class="fas fa-check-circle mr-1"></i>Receipted</span>
                                    <span class="device-info-value text-success">৳{{ number_format($staff['receipted'], 0) }}</span>
                                </div>
                                <div class="device-info-row">
                                    <span class="device-info-label"><i class="fas fa-exclamation-circle mr-1"></i>Due</span>
                                    <span class="device-info-value {{ $staff['current_due'] > 0 ? 'text-danger font-weight-bold' : 'text-success' }}">
                                        ৳{{ number_format($staff['current_due'], 0) }}
                                    </span>
                                </div>
                            </div>
                            <div class="device-card-footer">
                                @if($staff['current_due'] > 0)
                                <a href="{{ route('admin.hrm.receive-dues', $staff['id']) }}" class="btn btn-success btn-sm btn-block">
                                    <i class="fas fa-plus mr-1"></i> Receive Payment
                                </a>
                                @else
                                <span class="badge badge-success d-block py-2">All Clear</span>
                                @endif
                            </div>
                        </div>
                        @empty
                        <div class="text-center py-4 text-muted">
                            <i class="fas fa-check-circle fa-3x mb-3"></i>
                            <p>No staff collection records found.</p>
                        </div>
                        @endforelse
                    </div>
                </div>

                <!-- Desktop Table -->
                <div class="desktop-staff-table">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0">
                            <thead class="bg-dark text-white">
                                <tr>
                                    <th>Staff Name</th>
                                    <th>Type</th>
                                    <th>Phone</th>
                                    <th class="text-right">Total Collected</th>
                                    <th class="text-right">Receipted</th>
                                    <th class="text-right">Current Due</th>
                                    <th width="100">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($staffDues as $staff)
                                <tr>
                                    <td><strong>{{ $staff['name'] }}</strong></td>
                                    <td><span class="badge badge-{{ $staff['user_type'] == 'technician' ? 'info' : ($staff['user_type'] == 'reseller' ? 'warning' : 'secondary') }}">{{ ucfirst($staff['user_type']) }}</span></td>
                                    <td>{{ $staff['phone'] }}</td>
                                    <td class="text-right">৳{{ number_format($staff['total_collected'], 0) }}</td>
                                    <td class="text-right text-success">৳{{ number_format($staff['receipted'], 0) }}</td>
                                    <td class="text-right">
                                        @if($staff['current_due'] > 0)
                                        <span class="text-danger font-weight-bold">৳{{ number_format($staff['current_due'], 0) }}</span>
                                        @else
                                        <span class="text-success">৳0</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($staff['current_due'] > 0)
                                        <a href="{{ route('admin.hrm.receive-dues', $staff['id']) }}" class="btn btn-success btn-sm">
                                            <i class="fas fa-plus"></i> Receive
                                        </a>
                                        @else
                                        <span class="badge badge-success">Clear</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">
                                        <i class="fas fa-check-circle fa-3x mb-2"></i>
                                        <p>No staff collection records found.</p>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                            @if(count($staffDues) > 0)
                            <tfoot class="bg-danger text-white">
                                <tr>
                                    <td colspan="5" class="text-right"><strong>Total Outstanding Dues:</strong></td>
                                    <td class="text-right"><strong>৳{{ number_format($totalDue, 0) }}</strong></td>
                                    <td></td>
                                </tr>
                            </tfoot>
                            @endif
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card card-success card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-receipt mr-2"></i>Recent Receipts</h3>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    @forelse($receipts as $receipt)
                    <li class="list-group-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong>{{ $receipt->staff_name }}</strong>
                                <br><small class="text-muted">{{ $receipt->receipt_no }}</small>
                            </div>
                            <div class="text-right">
                                <span class="text-success font-weight-bold">৳{{ number_format($receipt->amount, 0) }}</span>
                                <br><small class="text-muted">{{ \Carbon\Carbon::parse($receipt->receipt_date)->format('d-m-Y') }}</small>
                            </div>
                        </div>
                    </li>
                    @empty
                    <li class="list-group-item text-center text-muted py-3">No receipts yet</li>
                    @endforelse
                </ul>
            </div>
        </div>
        
        <div class="card card-info card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-info-circle mr-2"></i>How It Works</h3>
            </div>
            <div class="card-body text-sm">
                <p><strong>1. Collection:</strong> Staff collects payments from clients in the field.</p>
                <p><strong>2. Due Tracking:</strong> All collections become dues to admin.</p>
                <p><strong>3. Receipt:</strong> When staff hands over money, create a receipt here.</p>
                <p class="mb-0"><strong>4. Clear:</strong> When receipted amount equals collected, dues clear.</p>
            </div>
        </div>
    </div>
</div>
@endsection
