@extends('layouts.admin')

@section('title', 'Technician Details')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.hrm.technicians') }}">Technicians</a></li>
<li class="breadcrumb-item active">{{ $technician->name }}</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-4">
        <div class="card card-primary card-outline">
            <div class="card-body box-profile">
                <div class="text-center">
                    <div class="profile-user-img img-fluid img-circle bg-primary text-white d-flex align-items-center justify-content-center mx-auto" style="width: 80px; height: 80px; font-size: 2rem;">
                        {{ strtoupper(substr($technician->name, 0, 1)) }}
                    </div>
                </div>
                <h3 class="profile-username text-center">{{ $technician->name }}</h3>
                <p class="text-muted text-center">
                    <span class="badge badge-{{ $technician->is_active ? 'success' : 'danger' }}">
                        {{ $technician->is_active ? 'Active' : 'Inactive' }}
                    </span>
                    Technician
                </p>
                
                <ul class="list-group list-group-unbordered mb-3">
                    <li class="list-group-item">
                        <b><i class="fas fa-envelope mr-2"></i>Email</b>
                        <span class="float-right">{{ $technician->email }}</span>
                    </li>
                    <li class="list-group-item">
                        <b><i class="fas fa-phone mr-2"></i>Phone</b>
                        <span class="float-right">{{ $technician->phone }}</span>
                    </li>
                    <li class="list-group-item">
                        <b><i class="fas fa-id-badge mr-2"></i>Employee ID</b>
                        <span class="float-right">{{ $technician->employee?->employee_id ?? 'N/A' }}</span>
                    </li>
                    <li class="list-group-item">
                        <b><i class="fas fa-calendar mr-2"></i>Joined</b>
                        <span class="float-right">{{ $technician->employee?->joining_date?->format('d-m-Y') ?? 'N/A' }}</span>
                    </li>
                </ul>
                
                <a href="{{ route('admin.hrm.technicians.edit', $technician) }}" class="btn btn-primary btn-block">
                    <i class="fas fa-edit mr-2"></i>Edit Profile
                </a>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header bg-info">
                <h3 class="card-title"><i class="fas fa-map-marker-alt mr-2"></i>Assigned Zones</h3>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    @forelse($assignedZones as $zone)
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        {{ $zone->name }}
                        <span class="badge badge-info">Zone</span>
                    </li>
                    @empty
                    <li class="list-group-item text-muted">No zones assigned</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="row mb-3">
            <div class="col-6 col-md-3 mb-2">
                <div class="stat-box bg-success text-white text-center py-3 rounded h-100">
                    <div class="stat-number">৳{{ number_format($thisMonthCollection, 0) }}</div>
                    <div class="stat-label">Monthly</div>
                </div>
            </div>
            <div class="col-6 col-md-3 mb-2">
                <div class="stat-box bg-primary text-white text-center py-3 rounded h-100">
                    <div class="stat-number">৳{{ number_format($totalCollection, 0) }}</div>
                    <div class="stat-label">Total</div>
                </div>
            </div>
            <div class="col-6 col-md-3 mb-2">
                <div class="stat-box bg-info text-white text-center py-3 rounded h-100">
                    <div class="stat-number">{{ $completedTickets }}</div>
                    <div class="stat-label">Completed</div>
                </div>
            </div>
            <div class="col-6 col-md-3 mb-2">
                <div class="stat-box bg-warning text-dark text-center py-3 rounded h-100">
                    <div class="stat-number">{{ $pendingTickets }}</div>
                    <div class="stat-label">Pending</div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-money-check mr-2"></i>Recent Payments Collected</h3>
            </div>
            <div class="card-body table-responsive p-0" style="max-height: 300px;">
                <table class="table table-sm table-hover">
                    <thead class="sticky-top bg-light">
                        <tr>
                            <th>Date</th>
                            <th>Client</th>
                            <th class="text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentPayments as $payment)
                        <tr>
                            <td>{{ $payment->payment_date->format('d-m-Y') }}</td>
                            <td>{{ $payment->client->name ?? 'N/A' }}</td>
                            <td class="text-right"><strong>৳{{ number_format($payment->amount, 2) }}</strong></td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="text-center text-muted">No payments collected yet</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-ticket-alt mr-2"></i>Recent Assigned Tickets</h3>
            </div>
            <div class="card-body table-responsive p-0" style="max-height: 300px;">
                <table class="table table-sm table-hover">
                    <thead class="sticky-top bg-light">
                        <tr>
                            <th>Ticket #</th>
                            <th>Client</th>
                            <th>Category</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentTickets as $ticket)
                        <tr>
                            <td>
                                <a href="{{ route('admin.support.tickets.show', $ticket) }}">{{ $ticket->ticket_no }}</a>
                            </td>
                            <td>{{ $ticket->client->name ?? 'N/A' }}</td>
                            <td>{{ $ticket->category->name ?? 'General' }}</td>
                            <td>
                                <span class="badge badge-{{ $ticket->status == 'resolved' ? 'success' : ($ticket->status == 'closed' ? 'secondary' : ($ticket->status == 'in_progress' ? 'info' : 'warning')) }}">
                                    {{ ucfirst(str_replace('_', ' ', $ticket->status)) }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted">No tickets assigned yet</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
