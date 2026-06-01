@extends('layouts.admin')

@section('title', 'Technician Efficiency')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.support.tickets') }}">Support</a></li>
<li class="breadcrumb-item active">Technician Efficiency</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-chart-line mr-2"></i>Technician Efficiency Report</h3>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead class="thead-dark">
                    <tr>
                        <th>Technician</th>
                        <th>Total Assigned</th>
                        <th>Completed</th>
                        <th>Efficiency Rate</th>
                        <th>Avg Resolution Time</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($technicians as $tech)
                    <tr>
                        <td>
                            <strong>{{ $tech->name }}</strong>
                            <br><small class="text-muted">{{ $tech->phone ?? '' }}</small>
                        </td>
                        <td>{{ $tech->total_assigned ?? 0 }}</td>
                        <td>{{ $tech->completed ?? 0 }}</td>
                        <td>
                            @php
                                $rate = $tech->efficiency_rate ?? 0;
                                $color = $rate >= 80 ? 'success' : ($rate >= 50 ? 'warning' : 'danger');
                            @endphp
                            <span class="badge badge-{{ $color }}">{{ $rate }}%</span>
                        </td>
                        <td>
                            @if($tech->avg_resolution_minutes > 0)
                                @if($tech->avg_resolution_minutes >= 60)
                                    {{ floor($tech->avg_resolution_minutes / 60) }}h {{ $tech->avg_resolution_minutes % 60 }}m
                                @else
                                    {{ $tech->avg_resolution_minutes }} min
                                @endif
                            @else
                                <span class="text-muted">N/A</span>
                            @endif
                        </td>
                        <td>
                            @if($rate >= 80)
                            <span class="badge badge-success"><i class="fas fa-star"></i> Excellent</span>
                            @elseif($rate >= 50)
                            <span class="badge badge-warning">Good</span>
                            @else
                            <span class="badge badge-danger">Needs Improvement</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">No technicians found</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
