@extends('layouts.client')
@section('title', 'Service History')

@section('content')

{{-- Activity Log --}}
<div style="font-size: 15px; font-weight: 700; color: #1e3c72; margin-bottom: 12px;">
    <i class="fas fa-history mr-2"></i>Activity Log
</div>
@forelse($activityLogs as $log)
<div style="background:#fff; border-radius:12px; padding:12px 16px; margin-bottom:8px; box-shadow:0 1px 6px rgba(0,0,0,0.06); border-left:4px solid #3b82f6;">
    <div class="d-flex justify-content-between align-items-start">
        <div>
            <span class="badge badge-info" style="font-size: 10px;">{{ ucfirst($log->category ?? 'General') }}</span>
            <div style="font-size: 13px; font-weight: 600; margin-top: 4px; color: #1e293b;">{{ $log->action ?? '-' }}</div>
            @if($log->description)
            <div style="font-size: 11px; color: #64748b; margin-top: 2px;">{{ $log->description }}</div>
            @endif
        </div>
        <small class="text-muted" style="font-size: 10px; white-space: nowrap; margin-left: 10px;">{{ $log->created_at->format('d M Y') }}<br>{{ $log->created_at->format('h:i A') }}</small>
    </div>
</div>
@empty
<div class="text-center py-4">
    <i class="fas fa-history fa-2x text-muted mb-2"></i>
    <p class="text-muted" style="font-size: 13px;">No activity logs</p>
</div>
@endforelse
@if($activityLogs->hasPages())
<div class="d-flex justify-content-center mt-2 mb-4">{{ $activityLogs->links() }}</div>
@endif

{{-- Reconnection History --}}
<div style="font-size: 15px; font-weight: 700; color: #1e3c72; margin-bottom: 12px; margin-top: 8px;">
    <i class="fas fa-plug mr-2"></i>Reconnection History
</div>
@forelse($reconnections as $r)
<div style="background:#fff; border-radius:12px; padding:12px 16px; margin-bottom:8px; box-shadow:0 1px 6px rgba(0,0,0,0.06); border-left:4px solid #10b981;">
    <div class="row" style="font-size: 12px;">
        <div class="col-6 mb-1">
            <small class="text-muted">Disconnected</small>
            <div style="font-weight: 600;">{{ $r->disconnect_date ? \Carbon\Carbon::parse($r->disconnect_date)->format('d M Y') : '-' }}</div>
        </div>
        <div class="col-6 mb-1">
            <small class="text-muted">Reconnected</small>
            <div style="font-weight: 600; color: #10b981;">{{ $r->reconnect_date ? \Carbon\Carbon::parse($r->reconnect_date)->format('d M Y') : '-' }}</div>
        </div>
        @if($r->disconnect_reason)
        <div class="col-6 mb-1">
            <small class="text-muted">Reason</small>
            <div>{{ $r->disconnect_reason }}</div>
        </div>
        @endif
        @if(($r->reconnection_fee ?? 0) > 0)
        <div class="col-6 mb-1">
            <small class="text-muted">Reconnection Fee</small>
            <div style="font-weight: 600; color: #ef4444;">৳{{ number_format($r->reconnection_fee, 0) }}</div>
        </div>
        @endif
    </div>
</div>
@empty
<div class="text-center py-4">
    <i class="fas fa-plug fa-2x text-muted mb-2"></i>
    <p class="text-muted" style="font-size: 13px;">No reconnection history</p>
</div>
@endforelse

{{-- Line Shift History --}}
@if($lineShifts->count() > 0)
<div style="font-size: 15px; font-weight: 700; color: #1e3c72; margin-bottom: 12px; margin-top: 8px;">
    <i class="fas fa-exchange-alt mr-2"></i>Line Shift History
</div>
@foreach($lineShifts as $shift)
<div style="background:#fff; border-radius:12px; padding:12px 16px; margin-bottom:8px; box-shadow:0 1px 6px rgba(0,0,0,0.06); border-left:4px solid #f59e0b;">
    <div class="d-flex justify-content-between align-items-start">
        <div style="font-size: 12px;">
            <div style="font-weight: 600; color: #1e293b;">
                {{ $shift->from_zone ?? '-' }} <i class="fas fa-arrow-right mx-1" style="color: #f59e0b;"></i> {{ $shift->to_zone ?? '-' }}
            </div>
            @if($shift->reason)
            <div class="text-muted mt-1">{{ $shift->reason }}</div>
            @endif
        </div>
        <small class="text-muted" style="font-size: 10px;">
            {{ $shift->shift_date ? \Carbon\Carbon::parse($shift->shift_date)->format('d M Y') : '-' }}
        </small>
    </div>
</div>
@endforeach
@endif
@endsection
