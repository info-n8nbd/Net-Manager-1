@php
$colorMap = [
    "yellow" => ["hex"=>"#f6c23e","light"=>"#fffbea","border"=>"#f6c23e","text"=>"#856404","badge"=>"#fff3cd"],
    "blue"   => ["hex"=>"#36b9cc","light"=>"#eaf9fb","border"=>"#36b9cc","text"=>"#0e6673","badge"=>"#d1f0f4"],
    "green"  => ["hex"=>"#1cc88a","light"=>"#eafaf4","border"=>"#1cc88a","text"=>"#0a6644","badge"=>"#c8f5e3"],
    "red"    => ["hex"=>"#e74a3b","light"=>"#fef0ef","border"=>"#e74a3b","text"=>"#7b1c14","badge"=>"#fbd3d0"],
    "purple" => ["hex"=>"#6f42c1","light"=>"#f3effe","border"=>"#6f42c1","text"=>"#3d2070","badge"=>"#ddd1f7"],
    "gray"   => ["hex"=>"#858796","light"=>"#f5f5f6","border"=>"#858796","text"=>"#42434e","badge"=>"#dddde2"],
];
$c = $colorMap[$note->color] ?? $colorMap["yellow"];
$wordCount = str_word_count($note->content);
@endphp
<div class="col-md-4 col-sm-6 mb-3 note-card-wrapper" data-color="{{ $note->color }}">
    <div class="card h-100 border-0 shadow-sm"
         style="border-radius:14px;cursor:pointer;background:{{ $c['light'] }};border-left:4px solid {{ $c['hex'] }} !important;"
         onclick="openEditNote({{ $note->id }}, {{ json_encode($note->title) }}, {{ json_encode($note->content) }}, {{ json_encode($note->color) }}, {{ json_encode($note->label) }}, {{ $note->is_pinned ? 'true' : 'false' }})">

        <div class="card-body p-3">
            {{-- Top bar: pin + label + color dot --}}
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div class="d-flex flex-wrap" style="gap:4px;flex:1;">
                    @if($note->is_pinned)
                    <span style="display:inline-flex;align-items:center;gap:3px;font-size:10px;font-weight:600;color:{{ $c['text'] }};background:{{ $c['badge'] }};padding:2px 7px;border-radius:20px;">
                        <i class="fas fa-thumbtack" style="font-size:8px;transform:rotate(45deg);"></i> Pinned
                    </span>
                    @endif
                    @if($note->label)
                    <span class="note-card-label" style="display:inline-flex;align-items:center;gap:3px;font-size:10px;font-weight:600;color:{{ $c['text'] }};background:{{ $c['badge'] }};padding:2px 7px;border-radius:20px;">
                        <i class="fas fa-tag" style="font-size:8px;"></i> {{ $note->label }}
                    </span>
                    @endif
                </div>
                <div style="width:10px;height:10px;border-radius:50%;background:{{ $c['hex'] }};flex-shrink:0;margin-top:3px;box-shadow:0 0 0 3px {{ $c['badge'] }};"></div>
            </div>

            {{-- Title --}}
            @if($note->title)
            <h6 class="note-card-title font-weight-bold mb-1" style="font-size:14px;line-height:1.4;color:#2d2d2d;">
                {{ $note->title }}
            </h6>
            @endif

            {{-- Content preview --}}
            <p class="note-card-body mb-0" style="font-size:12.5px;line-height:1.6;color:#555;white-space:pre-wrap;display:-webkit-box;-webkit-line-clamp:4;-webkit-box-orient:vertical;overflow:hidden;">{{ Str::limit($note->content, 180) }}</p>
        </div>

        {{-- Footer --}}
        <div class="card-footer border-0 px-3 py-2" style="background:transparent;">
            <div class="d-flex justify-content-between align-items-center">
                <small style="font-size:10px;color:{{ $c['text'] }};opacity:.8;">
                    <i class="far fa-clock mr-1"></i>{{ $note->updated_at->diffForHumans() }}
                </small>
                <small style="font-size:10px;color:{{ $c['text'] }};opacity:.7;">
                    {{ $wordCount }} {{ $wordCount === 1 ? 'word' : 'words' }}
                </small>
            </div>
        </div>
    </div>
</div>
