@extends('layouts.admin')

@section('title', 'System Logs')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
<li class="breadcrumb-item active">System Logs</li>
@endsection

@section('content')
<div class="row">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-danger">
            <div class="inner">
                <h3>{{ $stats['errors'] }}</h3>
                <p>Errors</p>
            </div>
            <div class="icon"><i class="fas fa-exclamation-circle"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3>{{ $stats['warnings'] }}</h3>
                <p>Warnings</p>
            </div>
            <div class="icon"><i class="fas fa-exclamation-triangle"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>{{ $stats['info'] }}</h3>
                <p>Info</p>
            </div>
            <div class="icon"><i class="fas fa-info-circle"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-secondary">
            <div class="inner">
                <h3>{{ $stats['file_size'] }}</h3>
                <p>Log Size</p>
            </div>
            <div class="icon"><i class="fas fa-file-alt"></i></div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header bg-dark text-white">
        <h3 class="card-title"><i class="fas fa-terminal mr-2"></i>System Logs <span class="badge badge-success ml-2" id="liveIndicator"><i class="fas fa-circle mr-1" style="font-size: 8px;"></i>LIVE</span></h3>
        <div class="card-tools">
            <button type="button" class="btn btn-outline-light btn-sm mr-1" id="refreshBtn" title="Refresh Now">
                <i class="fas fa-sync-alt mr-1" id="refreshIcon"></i>Refresh
            </button>
            <button type="button" class="btn btn-outline-light btn-sm mr-1" id="autoRefreshBtn" title="Toggle Auto-Refresh">
                <i class="fas fa-play mr-1" id="autoRefreshIcon"></i><span id="autoRefreshText">Auto</span>
            </button>
            <form action="{{ route('admin.system.logs.test') }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-info btn-sm mr-1">
                    <i class="fas fa-vial mr-1"></i>Test
                </button>
            </form>
            <a href="{{ route('admin.system.logs.download') }}" class="btn btn-success btn-sm mr-1">
                <i class="fas fa-download mr-1"></i>Download
            </a>
            <form action="{{ route('admin.system.logs.clear') }}" method="POST" class="d-inline" onsubmit="return confirm('Clear all logs?')">
                @csrf
                <button type="submit" class="btn btn-danger btn-sm">
                    <i class="fas fa-trash mr-1"></i>Clear
                </button>
            </form>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="p-2">
            <div class="btn-group btn-group-sm mb-2">
                <button class="btn btn-outline-secondary filter-btn active" data-filter="all">All</button>
                <button class="btn btn-outline-danger filter-btn" data-filter="error">Errors</button>
                <button class="btn btn-outline-warning filter-btn" data-filter="warning">Warnings</button>
                <button class="btn btn-outline-info filter-btn" data-filter="info">Info</button>
            </div>
            <input type="text" id="logSearch" class="form-control form-control-sm mb-2" placeholder="Search logs...">
        </div>
        
        <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
            <table class="table table-sm table-striped mb-0" id="logTable">
                <thead class="bg-dark text-white sticky-top">
                    <tr>
                        <th width="60">Type</th>
                        <th width="150">Date</th>
                        <th>Message</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                    <tr class="log-row" data-type="{{ $log['type'] }}">
                        <td>
                            @if($log['type'] == 'error')
                            <span class="badge badge-danger">ERROR</span>
                            @elseif($log['type'] == 'warning')
                            <span class="badge badge-warning">WARN</span>
                            @else
                            <span class="badge badge-info">INFO</span>
                            @endif
                        </td>
                        <td class="text-muted small">{{ $log['date'] }}</td>
                        <td>
                            <code class="d-block text-wrap" style="font-size: 11px; max-width: 800px; word-break: break-all;">
                                {{ Str::limit($log['message'], 500) }}
                            </code>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" class="text-center py-4">
                            <i class="fas fa-check-circle text-success fa-3x mb-2"></i>
                            <p class="mb-0">No log entries found. System is running smoothly!</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header bg-info text-white">
        <h3 class="card-title"><i class="fas fa-lightbulb mr-2"></i>Quick Debug Tips</h3>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <h6>Common Issues:</h6>
                <ul class="mb-0">
                    <li><strong>MikroTik Connection Failed:</strong> Check router IP, port 8728, and API credentials</li>
                    <li><strong>Ping Not Working:</strong> Client IP not set or host unreachable</li>
                    <li><strong>SMS Failed:</strong> Check SMS gateway settings and API key</li>
                    <li><strong>Payment Failed:</strong> Check bKash API credentials</li>
                </ul>
            </div>
            <div class="col-md-6">
                <h6>Useful Commands:</h6>
                <ul class="mb-0">
                    <li><code>php artisan cache:clear</code> - Clear cache</li>
                    <li><code>php artisan config:clear</code> - Clear config</li>
                    <li><code>php artisan queue:work</code> - Process queued jobs</li>
                    <li><code>tail -f storage/logs/laravel.log</code> - Live log view</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    let autoRefreshEnabled = false;
    let refreshInterval = null;
    
    // Filter buttons
    $('.filter-btn').on('click', function() {
        $('.filter-btn').removeClass('active');
        $(this).addClass('active');
        
        var filter = $(this).data('filter');
        if (filter === 'all') {
            $('.log-row').show();
        } else {
            $('.log-row').hide();
            $('.log-row[data-type="' + filter + '"]').show();
        }
    });
    
    // Search
    $('#logSearch').on('keyup', function() {
        var value = $(this).val().toLowerCase();
        $('.log-row').filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
        });
    });
    
    // Manual refresh
    $('#refreshBtn').on('click', function() {
        refreshLogs();
    });
    
    // Toggle auto-refresh
    $('#autoRefreshBtn').on('click', function() {
        autoRefreshEnabled = !autoRefreshEnabled;
        if (autoRefreshEnabled) {
            $('#autoRefreshIcon').removeClass('fa-play').addClass('fa-pause');
            $('#autoRefreshText').text('Stop');
            $('#liveIndicator').show();
            refreshInterval = setInterval(refreshLogs, 5000);
            refreshLogs();
        } else {
            $('#autoRefreshIcon').removeClass('fa-pause').addClass('fa-play');
            $('#autoRefreshText').text('Auto');
            $('#liveIndicator').hide();
            if (refreshInterval) clearInterval(refreshInterval);
        }
    });
    
    function refreshLogs() {
        $('#refreshIcon').addClass('fa-spin');
        $.get('{{ route("admin.system.logs") }}', function(html) {
            var newContent = $(html).find('#logTable tbody').html();
            var newStats = $(html).find('.small-box .inner h3');
            
            $('#logTable tbody').html(newContent);
            
            // Update stats
            $('.small-box.bg-danger .inner h3').text($(newStats[0]).text());
            $('.small-box.bg-warning .inner h3').text($(newStats[1]).text());
            $('.small-box.bg-info .inner h3').text($(newStats[2]).text());
            $('.small-box.bg-secondary .inner h3').text($(newStats[3]).text());
            
            // Re-apply filter
            var activeFilter = $('.filter-btn.active').data('filter');
            if (activeFilter && activeFilter !== 'all') {
                $('.log-row').hide();
                $('.log-row[data-type="' + activeFilter + '"]').show();
            }
            
            // Re-apply search
            var searchValue = $('#logSearch').val().toLowerCase();
            if (searchValue) {
                $('.log-row:visible').each(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(searchValue) > -1);
                });
            }
        }).always(function() {
            $('#refreshIcon').removeClass('fa-spin');
        });
    }
    
    // Initially hide live indicator
    $('#liveIndicator').hide();
});
</script>
@endpush
