@php
$phases = [
    1 => ['icon' => 'fa-file-import', 'title' => 'Data Ingestion'],
    2 => ['icon' => 'fa-search', 'title' => 'Discovery'],
    3 => ['icon' => 'fa-balance-scale', 'title' => 'Reconciliation'],
    4 => ['icon' => 'fa-sync', 'title' => 'Safe Sync'],
    5 => ['icon' => 'fa-rocket', 'title' => 'Go Live'],
];
@endphp

<div class="card mb-3">
    <div class="card-body p-2">
        <div class="d-flex justify-content-between align-items-center">
            @foreach($phases as $num => $phase)
            @php
            $status = $run->getPhaseStatus($num);
            $isActive = $num === $currentPhase;
            $btnClass = match(true) {
                $status === 'completed' => 'btn-success',
                $isActive => 'btn-primary',
                $run->canAccessPhase($num) => 'btn-outline-primary',
                default => 'btn-outline-secondary',
            };
            @endphp
            <a href="{{ $run->canAccessPhase($num) ? route("admin.migration.phase{$num}", $run) : '#' }}"
               class="btn {{ $btnClass }} btn-sm flex-fill mx-1 {{ !$run->canAccessPhase($num) ? 'disabled' : '' }}">
                @if($status === 'completed')<i class="fas fa-check mr-1"></i>@endif
                <i class="fas {{ $phase['icon'] }} mr-1"></i>
                {{ $num }}. {{ $phase['title'] }}
            </a>
            @endforeach
        </div>
    </div>
</div>
