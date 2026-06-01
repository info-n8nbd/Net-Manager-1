@extends('layouts.client')
@section('title', 'Ticket #' . $ticket->id)

@push('styles')
<style>
.ticket-header-card {
    background: linear-gradient(135deg, #1e3c72, #2a5298);
    border-radius: 12px;
    padding: 16px;
    color: white;
    margin-bottom: 12px;
}
.chat-bubble {
    margin-bottom: 12px;
    display: flex;
    flex-direction: column;
}
.chat-bubble.client { align-items: flex-end; }
.chat-bubble.support { align-items: flex-start; }
.bubble-content {
    max-width: 85%;
    padding: 10px 14px;
    border-radius: 16px;
    font-size: 13px;
    line-height: 1.5;
}
.chat-bubble.client .bubble-content {
    background: linear-gradient(135deg, #1e3c72, #2a5298);
    color: white;
    border-bottom-right-radius: 4px;
}
.chat-bubble.support .bubble-content {
    background: #f1f5f9;
    color: #1e293b;
    border-bottom-left-radius: 4px;
}
.bubble-meta {
    font-size: 10px;
    color: #94a3b8;
    margin-top: 4px;
    padding: 0 4px;
}
.reply-input-area {
    background: #fff;
    border-radius: 12px;
    padding: 12px;
    box-shadow: 0 1px 6px rgba(0,0,0,0.06);
    display: flex;
    gap: 8px;
    align-items: center;
    margin-top: 12px;
}
.reply-input-area input {
    flex: 1;
    border: 1px solid #e2e8f0;
    border-radius: 20px;
    padding: 9px 16px;
    font-size: 13px;
    outline: none;
}
.reply-input-area button {
    background: linear-gradient(135deg, #1e3c72, #2a5298);
    color: white;
    border: none;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
</style>
@endpush

@section('content')
{{-- Ticket Info Card --}}
<div class="ticket-header-card">
    <div class="d-flex justify-content-between align-items-start">
        <div style="flex:1; margin-right:10px;">
            <div style="font-size: 13px; opacity: 0.8; margin-bottom: 4px;">#{{ $ticket->id }} &middot; {{ $ticket->category->name ?? 'General' }}</div>
            <div style="font-size: 15px; font-weight: 700; line-height: 1.3;">{{ $ticket->subject }}</div>
        </div>
        <span class="badge badge-{{ $ticket->status === 'open' ? 'warning' : ($ticket->status === 'resolved' ? 'success' : 'secondary') }}"
              style="font-size: 11px; padding: 5px 10px; border-radius: 20px; flex-shrink:0;">
            {{ ucfirst($ticket->status) }}
        </span>
    </div>
    <div class="d-flex mt-3" style="gap: 16px; font-size: 11px; opacity: 0.85;">
        <span><i class="fas fa-calendar mr-1"></i>{{ $ticket->created_at->format('d M Y') }}</span>
        <span>
            <i class="fas fa-exclamation-circle mr-1"></i>
            <span class="badge badge-{{ $ticket->priority === 'high' ? 'danger' : ($ticket->priority === 'medium' ? 'warning' : 'info') }}"
                  style="font-size: 9px;">{{ ucfirst($ticket->priority ?? 'medium') }}</span>
        </span>
        @if($ticket->assigned_to)
        <span><i class="fas fa-user mr-1"></i>{{ $ticket->assignee->name ?? 'Support Team' }}</span>
        @endif
    </div>
</div>

{{-- Chat Area --}}
<div id="chatArea" style="padding-bottom: 4px;">
    {{-- Original message --}}
    <div class="chat-bubble client">
        <div class="bubble-content">{{ $ticket->description }}</div>
        <div class="bubble-meta text-right">
            <i class="fas fa-user mr-1"></i>You &middot; {{ $ticket->created_at->format('d M, h:i A') }}
        </div>
    </div>

    {{-- Replies --}}
    @foreach($ticket->replies ?? [] as $reply)
    @php $isClient = $reply->user_id === auth()->id(); @endphp
    <div class="chat-bubble {{ $isClient ? 'client' : 'support' }}">
        <div class="bubble-content">{{ $reply->message }}</div>
        <div class="bubble-meta {{ $isClient ? 'text-right' : '' }}">
            @if(!$isClient)<i class="fas fa-headset mr-1"></i>@endif
            {{ $reply->user->name ?? ($isClient ? 'You' : 'Support') }}
            &middot; {{ $reply->created_at->format('d M, h:i A') }}
            @if($isClient)<i class="fas fa-user ml-1"></i>@endif
        </div>
    </div>
    @endforeach

    @if($ticket->status === 'closed')
    <div class="text-center py-3">
        <span style="font-size: 12px; color: #94a3b8; background: #f1f5f9; padding: 6px 14px; border-radius: 20px;">
            <i class="fas fa-lock mr-1"></i>Ticket Closed
        </span>
    </div>
    @endif
</div>

{{-- Rate Ticket --}}
@if($ticket->status === 'resolved' && !$ticket->rating)
<div style="background: #fff; border-radius: 12px; padding: 14px 16px; margin-top: 12px; box-shadow: 0 1px 6px rgba(0,0,0,0.06); text-align: center;">
    <div style="font-size: 13px; font-weight: 600; margin-bottom: 10px;">Rate your experience</div>
    <form action="{{ route('client.tickets.rate', $ticket) }}" method="POST" class="d-flex justify-content-center" style="gap: 8px;">
        @csrf
        @foreach([1,2,3,4,5] as $star)
        <button type="submit" name="rating" value="{{ $star }}"
                style="background: none; border: none; font-size: 24px; cursor: pointer; color: #fbbf24; padding: 0;">★</button>
        @endforeach
    </form>
</div>
@endif

{{-- Reply Input --}}
@if($ticket->status !== 'closed')
<form action="{{ route('client.tickets.reply', $ticket) }}" method="POST">
    @csrf
    <div class="reply-input-area">
        <input type="text" name="message" placeholder="Type your message..." required autocomplete="off">
        <button type="submit"><i class="fas fa-paper-plane" style="font-size: 14px;"></i></button>
    </div>
</form>
@endif
@endsection

@push('scripts')
<script>
document.getElementById('chatArea').scrollIntoView({ behavior: 'smooth', block: 'end' });
</script>
@endpush
