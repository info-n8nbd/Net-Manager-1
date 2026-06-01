@extends("layouts.admin")
@section("title", "Media Servers")

@section("breadcrumb")
    <li class="breadcrumb-item active">Media Servers</li>
@endsection

@section("content")
<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1 class="m-0" style="font-size:18px;font-weight:700;">
          <i class="fas fa-server mr-2" style="color:#e74c3c;"></i>Media Servers
        </h1>
      </div>
    </div>
  </div>
</div>

<section class="content">
<div class="container-fluid">

@if(session("success"))
<div class="alert alert-success alert-dismissible fade show">
  <i class="fas fa-check-circle mr-2"></i>{{ session("success") }}
  <button type="button" class="close" data-dismiss="alert">&times;</button>
</div>
@endif
@if(session("error"))
<div class="alert alert-danger alert-dismissible fade show">
  <i class="fas fa-times-circle mr-2"></i>{{ session("error") }}
  <button type="button" class="close" data-dismiss="alert">&times;</button>
</div>
@endif

{{-- TAB NAVIGATION --}}
<ul class="nav nav-tabs" id="mediaServerTabs" role="tablist" style="border-bottom:2px solid #dee2e6;">
  <li class="nav-item">
    <a class="nav-link active" id="livetv-tab" data-toggle="tab" href="#livetv" role="tab"
       style="font-weight:600;font-size:13px;">
      <i class="fas fa-tv mr-2" style="color:#8e44ad;"></i>Live TV Server
      <span class="badge ml-1" style="background:#8e44ad;color:white;font-size:10px;">{{ $servers->where('type','live_tv')->count() }}</span>
    </a>
  </li>
  <li class="nav-item">
    <a class="nav-link" id="ftp-tab" data-toggle="tab" href="#ftp" role="tab"
       style="font-weight:600;font-size:13px;">
      <i class="fas fa-film mr-2" style="color:#e74c3c;"></i>FTP Server
      <span class="badge ml-1" style="background:#e74c3c;color:white;font-size:10px;">{{ $servers->where('type','ftp')->count() }}</span>
    </a>
  </li>
  <li class="nav-item">
    <a class="nav-link" id="other-tab" data-toggle="tab" href="#other" role="tab"
       style="font-weight:600;font-size:13px;">
      <i class="fas fa-server mr-2" style="color:#2980b9;"></i>Others
      <span class="badge ml-1" style="background:#2980b9;color:white;font-size:10px;">{{ $servers->where('type','other')->count() }}</span>
    </a>
  </li>
</ul>

<div class="tab-content pt-3" id="mediaServerTabsContent">

  {{-- ══════════════════════════════════════════
       TAB 1 : LIVE TV SERVER
  ══════════════════════════════════════════ --}}
  <div class="tab-pane fade show active" id="livetv" role="tabpanel">
    @php $liveTvServers = $servers->where('type','live_tv'); @endphp
    @include('admin.media-servers._tab_content', [
        'tabServers'  => $liveTvServers,
        'tabType'     => 'live_tv',
        'tabLabel'    => 'Live TV Server',
        'tabIcon'     => 'fas fa-tv',
        'tabColor'    => '#8e44ad',
        'tabBadgeLabel' => 'Live TV Server',
        'addFormId'   => 'addFormLiveTv',
    ])
  </div>

  {{-- ══════════════════════════════════════════
       TAB 2 : FTP SERVER
  ══════════════════════════════════════════ --}}
  <div class="tab-pane fade" id="ftp" role="tabpanel">
    @php $ftpServers = $servers->where('type','ftp'); @endphp
    @include('admin.media-servers._tab_content', [
        'tabServers'  => $ftpServers,
        'tabType'     => 'ftp',
        'tabLabel'    => 'FTP Server',
        'tabIcon'     => 'fas fa-film',
        'tabColor'    => '#e74c3c',
        'tabBadgeLabel' => 'FTP Movie Server',
        'addFormId'   => 'addFormFtp',
    ])
  </div>

  {{-- ══════════════════════════════════════════
       TAB 3 : OTHERS
  ══════════════════════════════════════════ --}}
  <div class="tab-pane fade" id="other" role="tabpanel">
    @php $otherServers = $servers->where('type','other'); @endphp
    @include('admin.media-servers._tab_content', [
        'tabServers'  => $otherServers,
        'tabType'     => 'other',
        'tabLabel'    => 'Other Media Server',
        'tabIcon'     => 'fas fa-server',
        'tabColor'    => '#2980b9',
        'tabBadgeLabel' => 'Server',
        'addFormId'   => 'addFormOther',
    ])
  </div>

</div>{{-- end tab-content --}}
</div>
</section>

@push("styles")
<style>
.nav-tabs .nav-link { color:#495057; border-radius:6px 6px 0 0; padding:9px 18px; }
.nav-tabs .nav-link.active { background:#fff; border-color:#dee2e6 #dee2e6 #fff; color:#212529; }
.nav-tabs .nav-link:hover:not(.active) { background:#f8f9fa; }
.media-server-card { border:2px solid #e9ecef; border-radius:12px; transition:all 0.2s; cursor:pointer; }
.media-server-card:hover { border-color:#2a5298 !important; box-shadow:0 4px 15px rgba(42,82,152,0.18) !important; }
.ms-type-badge { font-size:10px; padding:3px 9px; border-radius:10px; color:#fff; font-weight:600; }
</style>
@endpush

@push("scripts")
<script>
function openEdit(id){
  document.querySelectorAll("[id^=edit-row-]").forEach(r=>r.style.display="none");
  var r=document.getElementById("edit-row-"+id);
  if(r){ r.style.display=""; r.scrollIntoView({behavior:"smooth",block:"center"}); }
}
function closeEdit(id){ var r=document.getElementById("edit-row-"+id); if(r) r.style.display="none"; }
document.querySelectorAll("form[data-confirm]").forEach(function(f){
  f.addEventListener("submit",function(e){
    if(!confirm(f.dataset.confirm)) e.preventDefault();
  });
});

// Restore active tab after redirect
(function(){
  var hash = localStorage.getItem('mediaServerTab') || window.location.hash;
  if(hash && document.querySelector('[href="'+hash+'"]')){
    $('#mediaServerTabs a[href="'+hash+'"]').tab('show');
  }
  $('#mediaServerTabs a').on('shown.bs.tab', function(e){
    localStorage.setItem('mediaServerTab', e.target.getAttribute('href'));
  });
})();
</script>
@endpush

@endsection
