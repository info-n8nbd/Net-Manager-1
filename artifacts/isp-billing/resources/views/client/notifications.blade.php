@extends('layouts.client')
@section('title', 'Notifications')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-2">
    <span style="font-size:14px; font-weight:700; color:#1e3c72;"><i class="fas fa-bell mr-1"></i>Notifications</span>
</div>

<div style="background:#fff; border-radius:10px; box-shadow:0 1px 5px rgba(0,0,0,0.06); overflow:hidden;">
    @forelse($notifications as $notification)
    @php
        $ic = 'fa-bell'; $bg = '#3b82f6';
        if(str_contains($notification->type ?? '', 'payment')) { $ic='fa-money-bill-wave'; $bg='#10b981'; }
        elseif(str_contains($notification->type ?? '', 'ticket')) { $ic='fa-headset'; $bg='#06b6d4'; }
        elseif(str_contains($notification->type ?? '', 'due') || str_contains($notification->type ?? '', 'reminder')) { $ic='fa-exclamation-circle'; $bg='#f59e0b'; }
        elseif(str_contains($notification->type ?? '', 'outage')) { $ic='fa-times-circle'; $bg='#ef4444'; }
    @endphp
    <div class="d-flex" style="padding:10px 14px; border-bottom:1px solid #f8fafc; {{ $notification->status == 'pending' ? 'background:#fafbff;' : '' }}">
        <div style="width:32px; height:32px; border-radius:50%; background:{{ $bg }}; display:flex; align-items:center; justify-content:center; flex-shrink:0; margin-right:10px; margin-top:1px;">
            <i class="fas {{ $ic }} text-white" style="font-size:11px;"></i>
        </div>
        <div class="flex-grow-1">
            <div class="d-flex justify-content-between align-items-start">
                <div style="font-size:12px; font-weight:600; color:#1e293b;">{{ $notification->title }}</div>
                <small class="text-muted ml-2" style="font-size:9px; white-space:nowrap;">{{ \Carbon\Carbon::parse($notification->created_at)->diffForHumans() }}</small>
            </div>
            <div style="font-size:11px; color:#64748b; margin-top:2px;">{{ $notification->message }}</div>
        </div>
    </div>
    @empty
    <div class="text-center" style="padding:30px;">
        <i class="fas fa-bell-slash fa-2x text-muted mb-2"></i>
        <p class="text-muted mb-0" style="font-size:12px;">No notifications yet</p>
    </div>
    @endforelse
</div>
@if($notifications->hasPages())
<div class="d-flex justify-content-center mt-3">{{ $notifications->links() }}</div>
@endif
@endsection
