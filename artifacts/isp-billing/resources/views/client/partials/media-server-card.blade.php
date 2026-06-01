<a href="{{ $server->url }}" target="_blank" class="text-decoration-none">
  <div class="card h-100 text-center media-server-card">
    <div class="card-body py-3 px-2">
      @if($server->logo_url)
        <img src="{{ $server->logo_url }}" style="height:42px;width:auto;max-width:80px;object-fit:contain;margin-bottom:8px;border-radius:6px;" onerror="this.style.display='none';this.nextElementSibling.style.display='block'">
        <div style="display:none;"><i class="{{ $server->getTypeIcon() }} fa-2x mb-2" style="color:#2a5298;"></i></div>
      @else
        <i class="{{ $server->getTypeIcon() }} fa-2x mb-2" style="color:#2a5298;"></i>
      @endif
      <div style="font-size:12px;font-weight:700;color:#212529;line-height:1.3;margin-bottom:4px;">{{ $server->name }}</div>
      @if($server->description)
        <div style="font-size:10px;color:#6c757d;margin-top:3px;line-height:1.3;">{{ $server->description }}</div>
      @endif
    </div>
    <div class="card-footer py-1" style="background:#f8f9fa;border-top:1px solid #f0f0f0;border-radius:0 0 10px 10px;">
      <small style="font-size:10px;color:#2a5298;font-weight:600;"><i class="fas fa-external-link-alt mr-1"></i>Open</small>
    </div>
  </div>
</a>
