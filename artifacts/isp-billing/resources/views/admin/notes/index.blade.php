@extends("layouts.admin")
@section("title", "My Notes")

@section("content_header")
<div class="d-flex justify-content-between align-items-center">
    <div>
        <h1 class="m-0 font-weight-bold" style="font-size:22px;">
            <i class="fas fa-sticky-note mr-2" style="color:#f6c23e;"></i>My Notes
        </h1>
        <small class="text-muted" style="font-size:12px;">{{ $notes->count() }} note{{ $notes->count() !== 1 ? 's' : '' }} total</small>
    </div>
    <button class="btn btn-warning btn-sm font-weight-bold px-3" onclick="openCreateNote()" style="border-radius:8px;box-shadow:0 2px 8px rgba(246,194,62,.4);">
        <i class="fas fa-plus mr-1"></i> New Note
    </button>
</div>
@endsection

@section("content")
<div class="container-fluid">

    {{-- Toolbar --}}
    <div class="card border-0 shadow-sm mb-3" style="border-radius:12px;">
        <div class="card-body py-2 px-3">
            <div class="d-flex align-items-center flex-wrap" style="gap:10px;">

                {{-- Search --}}
                <div class="input-group input-group-sm" style="max-width:260px;flex:1;min-width:180px;">
                    <div class="input-group-prepend">
                        <span class="input-group-text" style="background:#f8f9fa;border-right:0;border-radius:8px 0 0 8px;">
                            <i class="fas fa-search text-muted" style="font-size:11px;"></i>
                        </span>
                    </div>
                    <input type="text" id="noteSearch" class="form-control" placeholder="Search notes..."
                        oninput="filterNotes()"
                        style="border-left:0;border-radius:0 8px 8px 0;font-size:13px;background:#f8f9fa;">
                </div>

                {{-- Label filter --}}
                <select id="noteLabel" class="form-control form-control-sm" onchange="filterNotes()"
                    style="max-width:150px;border-radius:8px;font-size:13px;background:#f8f9fa;border-color:#dee2e6;">
                    <option value="">All Labels</option>
                    @foreach($labels as $label)
                    <option value="{{ $label }}">{{ $label }}</option>
                    @endforeach
                </select>

                {{-- Color filter pills --}}
                <div class="d-flex align-items-center" style="gap:5px;" id="colorFilter">
                    <span class="color-pill active" data-color="" onclick="setColorFilter(this,'')" title="All"
                        style="display:inline-flex;align-items:center;justify-content:center;width:26px;height:26px;border-radius:50%;background:#495057;cursor:pointer;box-shadow:0 0 0 2px #fff,0 0 0 4px #495057;font-size:10px;color:#fff;font-weight:700;transition:all .2s;">✓</span>
                    @foreach(["yellow"=>"#f6c23e","blue"=>"#36b9cc","green"=>"#1cc88a","red"=>"#e74a3b","purple"=>"#6f42c1","gray"=>"#858796"] as $cname=>$chex)
                    <span class="color-pill" data-color="{{ $cname }}" onclick="setColorFilter(this,'{{ $cname }}')" title="{{ ucfirst($cname) }}"
                        style="display:inline-block;width:26px;height:26px;border-radius:50%;background:{{ $chex }};cursor:pointer;box-shadow:0 0 0 2px transparent;transition:all .2s;"></span>
                    @endforeach
                </div>

                <div class="ml-auto d-flex align-items-center" style="gap:6px;">
                    {{-- View toggle --}}
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" id="viewGrid" class="btn btn-light active" onclick="setView('grid')" title="Grid View" style="border-radius:6px 0 0 6px;padding:4px 8px;">
                            <i class="fas fa-th-large" style="font-size:11px;"></i>
                        </button>
                        <button type="button" id="viewList" class="btn btn-light" onclick="setView('list')" title="List View" style="border-radius:0 6px 6px 0;padding:4px 8px;">
                            <i class="fas fa-list" style="font-size:11px;"></i>
                        </button>
                    </div>
                    {{-- Note count badge --}}
                    <span id="visibleCount" class="badge badge-secondary" style="font-size:11px;border-radius:6px;">{{ $notes->count() }} shown</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Notes Grid --}}
    <div id="notesGrid" class="row" style="transition:all .3s;">
        @forelse($notes as $note)
        @include("admin.notes._note_card", ["note" => $note])
        @empty
        <div class="col-12" id="emptyState">
            <div class="text-center py-5" style="background:#fff;border-radius:16px;border:2px dashed #dee2e6;">
                <div style="font-size:64px;margin-bottom:16px;opacity:.25;">📝</div>
                <h5 class="text-muted font-weight-bold mb-2">No notes yet</h5>
                <p class="text-muted mb-4" style="font-size:14px;">Create your first note to get started</p>
                <button class="btn btn-warning px-4" onclick="openCreateNote()" style="border-radius:10px;font-weight:600;box-shadow:0 2px 8px rgba(246,194,62,.4);">
                    <i class="fas fa-plus mr-2"></i>Create First Note
                </button>
            </div>
        </div>
        @endforelse
    </div>

    {{-- No results state (hidden by default) --}}
    <div id="noResultsState" class="col-12 d-none">
        <div class="text-center py-4" style="background:#fff;border-radius:16px;border:2px dashed #dee2e6;">
            <div style="font-size:48px;margin-bottom:12px;opacity:.3;">🔍</div>
            <h6 class="text-muted">No notes match your search</h6>
            <button class="btn btn-sm btn-outline-secondary mt-2" onclick="clearFilters()" style="border-radius:8px;">Clear Filters</button>
        </div>
    </div>
</div>

{{-- Note Modal --}}
<div class="modal fade" id="noteModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" id="noteModalContent" style="border:none;border-radius:16px;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,.2);">
            {{-- Colored top bar --}}
            <div id="noteModalBar" style="height:5px;background:#f6c23e;transition:background .2s;"></div>
            <div class="modal-header border-0 pb-0 pt-3 px-4">
                <div class="d-flex align-items-center w-100">
                    <div id="noteModalIcon" style="width:36px;height:36px;border-radius:10px;background:rgba(246,194,62,.15);display:flex;align-items:center;justify-content:center;margin-right:10px;transition:background .2s;">
                        <i class="fas fa-sticky-note" style="color:#f6c23e;font-size:16px;transition:color .2s;" id="noteModalIconI"></i>
                    </div>
                    <div>
                        <h6 class="m-0 font-weight-bold" id="noteModalTitleText" style="font-size:15px;">New Note</h6>
                        <small class="text-muted" id="noteModalSubtitle" style="font-size:11px;">Personal note</small>
                    </div>
                    <button type="button" class="close ml-auto" data-dismiss="modal" style="font-size:20px;opacity:.5;">&times;</button>
                </div>
            </div>

            <div class="modal-body px-4 pt-2 pb-3">
                {{-- Title --}}
                <input type="text" id="noteTitle" class="form-control border-0 font-weight-bold px-0 mb-1"
                    placeholder="Note title (optional)"
                    style="font-size:18px;background:transparent;box-shadow:none;border-bottom:1px solid #eee !important;border-radius:0;"
                    oninput="updateSubtitle()">

                {{-- Content --}}
                <div style="position:relative;">
                    <textarea id="noteContent" class="form-control border-0 px-0 mt-2"
                        rows="9" placeholder="Start writing your note..."
                        style="resize:none;font-size:14px;line-height:1.7;background:transparent;box-shadow:none;color:#444;"
                        oninput="updateCharCount()"></textarea>
                    <div style="position:absolute;bottom:4px;right:4px;">
                        <small id="charCount" class="text-muted" style="font-size:10px;background:rgba(255,255,255,.8);padding:1px 5px;border-radius:4px;">0 words</small>
                    </div>
                </div>

                <hr class="mt-2 mb-3" style="border-color:#f0f0f0;">

                <div class="row">
                    {{-- Label --}}
                    <div class="col-md-5 mb-2">
                        <label class="small text-muted mb-1" style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;font-weight:600;">
                            <i class="fas fa-tag mr-1"></i>Label
                        </label>
                        <input type="text" id="noteLabel2" class="form-control form-control-sm"
                            placeholder="e.g. Work, Personal, Urgent"
                            list="labelsDatalist"
                            style="border-radius:8px;font-size:13px;">
                        <datalist id="labelsDatalist">
                            @foreach($labels as $label)
                            <option value="{{ $label }}">
                            @endforeach
                        </datalist>
                    </div>

                    {{-- Color --}}
                    <div class="col-md-7 mb-2">
                        <label class="small text-muted mb-1" style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;font-weight:600;">
                            <i class="fas fa-palette mr-1"></i>Color
                        </label>
                        <div class="d-flex align-items-center flex-wrap" style="gap:8px;" id="colorPicker">
                            @foreach(["yellow"=>"#f6c23e","blue"=>"#36b9cc","green"=>"#1cc88a","red"=>"#e74a3b","purple"=>"#6f42c1","gray"=>"#858796"] as $cname=>$chex)
                            <div class="color-pick-wrap" onclick="pickColor(this,'{{ $cname }}')" title="{{ ucfirst($cname) }}"
                                style="display:flex;flex-direction:column;align-items:center;cursor:pointer;gap:3px;">
                                <span class="color-pick" data-color="{{ $cname }}"
                                    style="display:inline-block;width:30px;height:30px;border-radius:50%;background:{{ $chex }};border:3px solid transparent;transition:all .15s;box-shadow:0 2px 5px rgba(0,0,0,.15);"></span>
                                <span style="font-size:9px;color:#aaa;line-height:1;">{{ ucfirst($cname) }}</span>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer border-0 pt-0 px-4 pb-3" style="justify-content:space-between;">
                <div class="d-flex" style="gap:6px;">
                    <button type="button" class="btn btn-sm d-none" id="deleteNoteBtn" onclick="deleteNote()"
                        style="border-radius:8px;background:#fff1f0;color:#e74a3b;border:1px solid #ffd6d3;font-size:12px;">
                        <i class="fas fa-trash mr-1"></i>Delete
                    </button>
                    <button type="button" class="btn btn-sm d-none" id="pinNoteBtn" onclick="togglePin()"
                        style="border-radius:8px;background:#fff8e1;color:#f6a800;border:1px solid #fce4a0;font-size:12px;">
                        <i class="fas fa-thumbtack mr-1"></i><span id="pinBtnText">Pin</span>
                    </button>
                </div>
                <div class="d-flex" style="gap:8px;">
                    <button type="button" class="btn btn-sm btn-light" data-dismiss="modal" style="border-radius:8px;font-size:13px;border:1px solid #dee2e6;">
                        Cancel
                    </button>
                    <button type="button" class="btn btn-sm btn-warning font-weight-bold" id="saveNoteBtn" onclick="saveNote()"
                        style="border-radius:8px;font-size:13px;padding:6px 18px;box-shadow:0 2px 8px rgba(246,194,62,.4);">
                        <i class="fas fa-save mr-1"></i><span id="saveBtnText">Save Note</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push("scripts")
<script>
const NOTE_COLORS = {
    yellow: { hex:"#f6c23e", light:"rgba(246,194,62,.12)", icon:"#f6a800" },
    blue:   { hex:"#36b9cc", light:"rgba(54,185,204,.12)", icon:"#2aa8bb" },
    green:  { hex:"#1cc88a", light:"rgba(28,200,138,.12)", icon:"#16a870" },
    red:    { hex:"#e74a3b", light:"rgba(231,74,59,.12)",  icon:"#c73a2c" },
    purple: { hex:"#6f42c1", light:"rgba(111,66,193,.12)", icon:"#5a32a3" },
    gray:   { hex:"#858796", light:"rgba(133,135,150,.12)",icon:"#6b6d7e" },
};
let currentNoteId = null;
let currentColor = "yellow";
let currentPinned = false;
let activeColorFilter = "";
let currentView = "grid";

function pickColor(el, colorName) {
    document.querySelectorAll(".color-pick").forEach(e => {
        e.style.border = "3px solid transparent";
        e.style.transform = "scale(1)";
    });
    el.querySelector(".color-pick").style.border = "3px solid #333";
    el.querySelector(".color-pick").style.transform = "scale(1.1)";
    currentColor = colorName;
    const c = NOTE_COLORS[colorName];
    document.getElementById("noteModalBar").style.background = c.hex;
    document.getElementById("noteModalIcon").style.background = c.light;
    document.getElementById("noteModalIconI").style.color = c.icon;
}

function updateCharCount() {
    const content = document.getElementById("noteContent").value.trim();
    const words = content ? content.split(/\s+/).filter(w => w).length : 0;
    document.getElementById("charCount").textContent = words + (words === 1 ? " word" : " words");
}

function updateSubtitle() {
    const title = document.getElementById("noteTitle").value.trim();
    const label = document.getElementById("noteLabel2").value.trim();
    document.getElementById("noteModalSubtitle").textContent = label || (title ? "Personal note" : "Untitled note");
}

function openCreateNote() {
    currentNoteId = null; currentColor = "yellow"; currentPinned = false;
    document.getElementById("noteTitle").value = "";
    document.getElementById("noteContent").value = "";
    document.getElementById("noteLabel2").value = "";
    document.getElementById("noteModalTitleText").textContent = "New Note";
    document.getElementById("noteModalSubtitle").textContent = "Untitled note";
    document.getElementById("saveBtnText").textContent = "Save Note";
    document.getElementById("deleteNoteBtn").classList.add("d-none");
    document.getElementById("pinNoteBtn").classList.add("d-none");
    document.getElementById("noteModalBar").style.background = NOTE_COLORS.yellow.hex;
    document.getElementById("noteModalIcon").style.background = NOTE_COLORS.yellow.light;
    document.getElementById("noteModalIconI").style.color = NOTE_COLORS.yellow.icon;
    document.querySelectorAll(".color-pick").forEach(e => { e.style.border = "3px solid transparent"; e.style.transform = "scale(1)"; });
    const yel = document.querySelector(".color-pick[data-color=yellow]");
    if (yel) { yel.style.border = "3px solid #333"; yel.style.transform = "scale(1.1)"; }
    updateCharCount();
    $("#noteModal").modal("show");
    setTimeout(() => document.getElementById("noteTitle").focus(), 300);
}

function openEditNote(id, title, content, color, label, isPinned) {
    currentNoteId = id; currentColor = color || "yellow"; currentPinned = isPinned;
    document.getElementById("noteTitle").value = title || "";
    document.getElementById("noteContent").value = content;
    document.getElementById("noteLabel2").value = label || "";
    document.getElementById("noteModalTitleText").textContent = "Edit Note";
    document.getElementById("noteModalSubtitle").textContent = label || "Personal note";
    document.getElementById("saveBtnText").textContent = "Update Note";
    document.getElementById("deleteNoteBtn").classList.remove("d-none");
    document.getElementById("pinNoteBtn").classList.remove("d-none");
    document.getElementById("pinBtnText").textContent = isPinned ? "Unpin" : "Pin";
    const c = NOTE_COLORS[currentColor] || NOTE_COLORS.yellow;
    document.getElementById("noteModalBar").style.background = c.hex;
    document.getElementById("noteModalIcon").style.background = c.light;
    document.getElementById("noteModalIconI").style.color = c.icon;
    document.querySelectorAll(".color-pick").forEach(e => { e.style.border = "3px solid transparent"; e.style.transform = "scale(1)"; });
    const cp = document.querySelector(".color-pick[data-color=" + currentColor + "]");
    if (cp) { cp.style.border = "3px solid #333"; cp.style.transform = "scale(1.1)"; }
    updateCharCount();
    $("#noteModal").modal("show");
}

function saveNote() {
    const title = document.getElementById("noteTitle").value;
    const content = document.getElementById("noteContent").value.trim();
    const label = document.getElementById("noteLabel2").value.trim();
    if (!content) {
        document.getElementById("noteContent").style.borderBottom = "2px solid #e74a3b";
        document.getElementById("noteContent").placeholder = "⚠ Note content cannot be empty!";
        document.getElementById("noteContent").focus();
        setTimeout(() => { document.getElementById("noteContent").style.borderBottom = ""; document.getElementById("noteContent").placeholder = "Start writing your note..."; }, 2000);
        return;
    }
    const btn = document.getElementById("saveNoteBtn");
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Saving...';

    const url = currentNoteId ? `/admin/notes/${currentNoteId}` : "/admin/notes";
    const method = currentNoteId ? "PUT" : "POST";
    $.ajax({
        url, method, data: { title, content, color: currentColor, label, _token: "{{ csrf_token() }}" },
        success(res) {
            if (res.success) { $("#noteModal").modal("hide"); location.reload(); }
        },
        error() {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-save mr-1"></i><span id="saveBtnText">Save Note</span>';
            alert("Failed to save note. Please try again.");
        }
    });
}

function deleteNote() {
    if (!currentNoteId) return;
    if (!confirm("Delete this note permanently? This cannot be undone.")) return;
    $.ajax({
        url: `/admin/notes/${currentNoteId}`, method: "DELETE",
        data: { _token: "{{ csrf_token() }}" },
        success(res) { if (res.success) { $("#noteModal").modal("hide"); location.reload(); } },
        error() { alert("Failed to delete note."); }
    });
}

function togglePin() {
    if (!currentNoteId) return;
    $.ajax({
        url: `/admin/notes/${currentNoteId}/toggle-pin`, method: "POST",
        data: { _token: "{{ csrf_token() }}" },
        success(res) { if (res.success) { $("#noteModal").modal("hide"); location.reload(); } },
        error() { alert("Failed to toggle pin."); }
    });
}

function filterNotes() {
    const search = document.getElementById("noteSearch").value.toLowerCase();
    const label = document.getElementById("noteLabel").value.toLowerCase();
    let visible = 0;
    document.querySelectorAll(".note-card-wrapper").forEach(card => {
        const titleEl = card.querySelector(".note-card-title");
        const bodyEl  = card.querySelector(".note-card-body");
        const labelEl = card.querySelector(".note-card-label");
        const colorVal = card.dataset.color;
        const title = (titleEl ? titleEl.textContent : "").toLowerCase();
        const body  = (bodyEl  ? bodyEl.textContent  : "").toLowerCase();
        const lbl   = (labelEl ? labelEl.textContent : "").toLowerCase();
        const matchSearch = !search || title.includes(search) || body.includes(search) || lbl.includes(search);
        const matchLabel  = !label  || lbl.includes(label);
        const matchColor  = !activeColorFilter || colorVal === activeColorFilter;
        const show = matchSearch && matchLabel && matchColor;
        card.style.display = show ? "" : "none";
        if (show) visible++;
    });
    document.getElementById("visibleCount").textContent = visible + " shown";
    const noRes = document.getElementById("noResultsState");
    if (noRes) noRes.classList.toggle("d-none", visible > 0 || document.querySelectorAll(".note-card-wrapper").length === 0);
}

function setColorFilter(el, color) {
    activeColorFilter = color;
    document.querySelectorAll(".color-pill").forEach(p => {
        p.style.boxShadow = "0 0 0 2px transparent";
        p.innerHTML = "";
        p.classList.remove("active");
        p.style.transform = "scale(1)";
    });
    el.style.boxShadow = "0 0 0 2px #fff,0 0 0 4px " + (color ? NOTE_COLORS[color].hex : "#495057");
    el.innerHTML = '<span style="color:#fff;font-size:10px;font-weight:700;">&#10003;</span>';
    el.style.display = "inline-flex";
    el.style.alignItems = "center";
    el.style.justifyContent = "center";
    el.style.transform = "scale(1.15)";
    el.classList.add("active");
    filterNotes();
}

function setView(view) {
    currentView = view;
    const grid = document.getElementById("notesGrid");
    document.getElementById("viewGrid").classList.toggle("active", view === "grid");
    document.getElementById("viewList").classList.toggle("active", view === "list");
    document.querySelectorAll(".note-card-wrapper").forEach(card => {
        if (view === "list") {
            card.className = card.className.replace(/col-md-\d+|col-sm-\d+/g, "").trim();
            card.classList.add("col-12");
        } else {
            card.className = card.className.replace(/col-12/g, "").trim();
            card.classList.add("col-md-4", "col-sm-6");
        }
    });
}

function clearFilters() {
    document.getElementById("noteSearch").value = "";
    document.getElementById("noteLabel").value = "";
    setColorFilter(document.querySelector(".color-pill[data-color='']"), "");
    filterNotes();
}

// Keyboard shortcut: Ctrl+N = new note
document.addEventListener("keydown", function(e) {
    if ((e.ctrlKey || e.metaKey) && e.key === "n" && !$("#noteModal").hasClass("show")) {
        e.preventDefault();
        openCreateNote();
    }
});
</script>

<style>
.note-card-wrapper .card { transition: transform .2s ease, box-shadow .2s ease; }
.note-card-wrapper .card:hover { transform: translateY(-4px); box-shadow: 0 8px 25px rgba(0,0,0,.12) !important; }
.color-pill { transition: all .2s ease; }
.color-pick-wrap:hover .color-pick { transform: scale(1.15); box-shadow: 0 4px 10px rgba(0,0,0,.2); }
#noteContent:focus { outline: none; box-shadow: none; }
#noteTitle:focus { outline: none; box-shadow: none; }
.modal-content { border-radius: 16px !important; }
</style>
@endpush
