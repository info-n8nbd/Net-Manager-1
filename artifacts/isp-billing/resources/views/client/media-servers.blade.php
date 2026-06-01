@extends("layouts.client")
@section("title", "Media Servers")
@section("content")

<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-12">
        <h1 class="m-0" style="font-size:18px;font-weight:700;">
          <i class="fas fa-play-circle mr-2" style="color:#e74c3c;"></i>Media Servers
        </h1>
        <p class="text-muted" style="font-size:12px;margin-top:4px;">Click any server to access it directly</p>
      </div>
    </div>
  </div>
</div>

<section class="content">
  <div class="container-fluid">

    @if($servers->isEmpty())
      <div class="card">
        <div class="card-body text-center py-5">
          <i class="fas fa-film fa-4x mb-3 d-block" style="color:#dee2e6;"></i>
          <p class="text-muted">No media servers are available at this time.</p>
        </div>
      </div>
    @else

    @php
      $ftpServers = $servers->where('type','ftp');
      $tvServers  = $servers->where('type','live_tv');
      $otherServers = $servers->where('type','other');
    @endphp

    @if($ftpServers->count())
    <div class="mb-4">
      <h6 style="font-weight:700;font-size:13px;color:#495057;margin-bottom:12px;border-bottom:2px solid #e9ecef;padding-bottom:6px;">
        <i class="fas fa-film mr-2" style="color:#8e44ad;"></i>FTP Movie Servers
        <span class="badge badge-secondary ml-1">{{ $ftpServers->count() }}</span>
      </h6>
      <div class="row">
        @foreach($ftpServers as $server)
        <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 col-6 mb-3">
          @include('client.partials.media-server-card', ['server' => $server])
        </div>
        @endforeach
      </div>
    </div>
    @endif

    @if($tvServers->count())
    <div class="mb-4">
      <h6 style="font-weight:700;font-size:13px;color:#495057;margin-bottom:12px;border-bottom:2px solid #e9ecef;padding-bottom:6px;">
        <i class="fas fa-tv mr-2" style="color:#e74c3c;"></i>Live TV Servers
        <span class="badge badge-secondary ml-1">{{ $tvServers->count() }}</span>
      </h6>
      <div class="row">
        @foreach($tvServers as $server)
        <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 col-6 mb-3">
          @include('client.partials.media-server-card', ['server' => $server])
        </div>
        @endforeach
      </div>
    </div>
    @endif

    @if($otherServers->count())
    <div class="mb-4">
      <h6 style="font-weight:700;font-size:13px;color:#495057;margin-bottom:12px;border-bottom:2px solid #e9ecef;padding-bottom:6px;">
        <i class="fas fa-server mr-2" style="color:#27ae60;"></i>Other Servers
        <span class="badge badge-secondary ml-1">{{ $otherServers->count() }}</span>
      </h6>
      <div class="row">
        @foreach($otherServers as $server)
        <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 col-6 mb-3">
          @include('client.partials.media-server-card', ['server' => $server])
        </div>
        @endforeach
      </div>
    </div>
    @endif

    @endif

  </div>
</section>

@push("styles")
<style>
.media-server-card { border:2px solid #e9ecef; border-radius:12px; transition:all 0.2s; }
.media-server-card:hover { border-color:#2a5298; box-shadow:0 4px 15px rgba(42,82,152,0.18); }
</style>
@endpush
@endsection
