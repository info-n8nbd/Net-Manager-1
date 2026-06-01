@extends('layouts.technician')

@section('title', 'Technician Dashboard')

@section('content')
<div class="mobile-quick-search d-block d-lg-none mb-3">
    <div class="input-group">
        <div class="input-group-prepend">
            <span class="input-group-text"><i class="fas fa-search"></i></span>
        </div>
        <input type="text" id="mobileClientSearch" class="form-control" placeholder="Search client by name, ID, phone...">
    </div>
    <div class="mobile-search-results" id="mobileSearchResults"></div>
</div>

<div class="row mb-3">
    <div class="col-6 col-lg-3 mb-2">
        <a href="{{ route('technician.clients') }}?status=active" class="text-decoration-none">
            <div class="small-box bg-teal" style="background-color: #20c997 !important;">
                <div class="inner">
                    <h3>{{ $activeClients }}</h3>
                    <p>Active Clients</p>
                </div>
                <div class="icon"><i class="fas fa-user-check"></i></div>
            </div>
        </a>
    </div>
    <div class="col-6 col-lg-3 mb-2">
        <a href="{{ route('technician.clients') }}?status=inactive" class="text-decoration-none">
            <div class="small-box bg-secondary" style="background-color: #6c757d !important;">
                <div class="inner">
                    <h3>{{ $inactiveClients }}</h3>
                    <p>Inactive Clients</p>
                </div>
                <div class="icon"><i class="fas fa-user-slash"></i></div>
            </div>
        </a>
    </div>
    <div class="col-6 col-lg-3 mb-2">
        <a href="{{ route('technician.collection-report') }}" class="text-decoration-none">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>৳{{ number_format($todayCollection ?? 0, 0) }}</h3>
                    <p>Today's Collection</p>
                </div>
                <div class="icon"><i class="fas fa-money-bill-wave"></i></div>
            </div>
        </a>
    </div>
    <div class="col-6 col-lg-3 mb-2">
        <a href="{{ route('technician.due-report') }}" class="text-decoration-none">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>৳{{ number_format($totalDue ?? 0, 0) }}</h3>
                    <p>Total Due</p>
                </div>
                <div class="icon"><i class="fas fa-exclamation-triangle"></i></div>
            </div>
        </a>
    </div>
</div>

@if($zoneStats->count() > 0)
<div class="row mb-3">
    <div class="col-12">
        <h6 class="text-muted text-uppercase mb-2" style="font-size:11px;letter-spacing:1px;">
            <i class="fas fa-map-marker-alt mr-1"></i> Zone-wise Summary
        </h6>
    </div>
    @foreach($zoneStats as $zs)
    <div class="col-12 col-sm-6 col-lg-{{ $zoneStats->count() == 1 ? '12' : ($zoneStats->count() == 2 ? '6' : '4') }} mb-2">
        <div class="card card-outline card-primary mb-0 h-100" style="border-radius:10px;">
            <div class="card-header py-2 px-3" style="background:linear-gradient(135deg,#1e3c72,#2a5298);border-radius:10px 10px 0 0;">
                <h3 class="card-title text-white" style="font-size:13px;font-weight:600;">
                    <i class="fas fa-map-marker-alt mr-1"></i>{{ $zs['name'] }}
                </h3>
            </div>
            <div class="card-body p-0">
                <div class="row no-gutters text-center">
                    <div class="col-3 border-right py-2">
                        <div style="font-size:20px;font-weight:700;color:#1e3c72;">{{ $zs['total'] }}</div>
                        <div style="font-size:10px;color:#666;text-transform:uppercase;">Total</div>
                    </div>
                    <div class="col-3 border-right py-2">
                        <div style="font-size:20px;font-weight:700;color:#28a745;">{{ $zs['active'] }}</div>
                        <div style="font-size:10px;color:#666;text-transform:uppercase;">Active</div>
                    </div>
                    <div class="col-3 border-right py-2">
                        <div style="font-size:20px;font-weight:700;color:#dc3545;">{{ $zs['inactive'] }}</div>
                        <div style="font-size:10px;color:#666;text-transform:uppercase;">Inactive</div>
                    </div>
                    <div class="col-3 py-2">
                        <div style="font-size:16px;font-weight:700;color:#e67e22;">৳{{ number_format($zs['monthly_bill'],0) }}</div>
                        <div style="font-size:10px;color:#666;text-transform:uppercase;">Monthly Bill</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>
@endif


{{-- Pending Submissions - one-line alert --}}
@if(isset($pendingSubmissions) && $pendingSubmissions->count() > 0)
<div class="alert alert-warning py-2 px-3 mb-2 d-flex justify-content-between align-items-center" style="border-radius:6px;">
    <span><i class="fas fa-hourglass-half mr-1"></i> <strong>৳{{ number_format($pendingAmount ?? 0, 0) }}</strong> submitted — <em>Waiting for Admin Approval</em></span>
    <span class="badge badge-warning text-dark">{{ $pendingSubmissions->count() }} pending</span>
</div>
@endif



<div class="row">
    <div class="col-lg-6 mb-3">
        <div class="card card-success card-outline h-100">
            <div class="card-header py-2">
                <h3 class="card-title"><i class="fas fa-money-check-alt mr-2"></i>Today's Collection ({{ $todayPayments->count() }})</h3>
            </div>
            <div class="card-body p-0" style="max-height: 400px; overflow-y: auto;">
                @forelse($todayPayments->take(10) as $payment)
                <div class="d-flex justify-content-between align-items-center border-bottom px-3 py-2">
                    <div>
                        <strong>{{ $payment->client->name ?? 'N/A' }}</strong>
                        <small class="text-muted d-block">{{ $payment->client->client_id ?? '' }}</small>
                    </div>
                    <div class="text-right">
                        <span class="badge badge-success">৳{{ number_format($payment->amount ?? 0, 0) }}</span>
                        <small class="text-muted d-block">{{ $payment->created_at->format('h:i A') }}</small>
                    </div>
                </div>
                @empty
                <div class="text-center text-muted py-4">
                    <i class="fas fa-inbox fa-2x mb-2"></i>
                    <p class="mb-0">No collections today</p>
                </div>
                @endforelse
                @if($todayPayments->count() > 0)
                <div class="bg-success text-white text-center py-2">
                    <strong>Total: ৳{{ number_format($todayCollection ?? 0, 0) }}</strong>
                </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-6 mb-3">
        <div class="card card-danger card-outline h-100">
            <div class="card-header py-2">
                <h3 class="card-title"><i class="fas fa-user-clock mr-2"></i>Due Clients</h3>
                <div class="card-tools">
                    <a href="{{ route('technician.due-report') }}" class="btn btn-tool"><i class="fas fa-list"></i></a>
                </div>
            </div>
            <div class="card-body p-0" style="max-height: 400px; overflow-y: auto;">
                @php $dueClients = \App\Models\Client::whereIn('zone_id', $zones->pluck('id'))->where('due_amount', '>', 0)->orderBy('due_amount', 'desc')->take(10)->get(); @endphp
                @forelse($dueClients as $client)
                <a href="{{ route('technician.clients.show', $client) }}" class="d-flex justify-content-between align-items-center border-bottom px-3 py-2 text-dark text-decoration-none">
                    <div>
                        <strong>{{ $client->name }}</strong>
                        <small class="text-muted d-block">{{ $client->phone }}</small>
                    </div>
                    <div class="text-right">
                        <span class="badge badge-danger">৳{{ number_format($client->due_amount ?? 0, 0) }}</span>
                        <small class="text-muted d-block">{{ $client->zone->name ?? '' }}</small>
                    </div>
                </a>
                @empty
                <div class="text-center text-muted py-4">
                    <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                    <p class="mb-0">No due clients!</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<div class="row mb-3">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header py-2">
                <h3 class="card-title"><i class="fas fa-chart-line mr-2"></i>My Collection (Last 30 Days)</h3>
            </div>
            <div class="card-body">
                <canvas id="dailyCollectionChart" height="120"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header py-2">
                <h3 class="card-title"><i class="fas fa-users mr-2"></i>Client Status</h3>
            </div>
            <div class="card-body">
                <canvas id="clientStatusChart" height="180"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row mb-3">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header py-2">
                <h3 class="card-title"><i class="fas fa-chart-bar mr-2"></i>Monthly Collection</h3>
            </div>
            <div class="card-body">
                <canvas id="monthlyCollectionChart" height="140"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header py-2">
                <h3 class="card-title"><i class="fas fa-ticket-alt mr-2"></i>Ticket Stats</h3>
            </div>
            <div class="card-body">
                <canvas id="ticketStatsChart" height="140"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-6 mb-3">
        <div class="card card-warning card-outline h-100">
            <div class="card-header py-2">
                <h3 class="card-title"><i class="fas fa-ticket-alt mr-2"></i>Pending Tickets</h3>
                <div class="card-tools">
                    <a href="{{ route('technician.tickets') }}" class="btn btn-tool"><i class="fas fa-list"></i></a>
                </div>
            </div>
            <div class="card-body p-0" style="max-height: 300px; overflow-y: auto;">
                @php $pendingTickets = \App\Models\Ticket::where('assigned_to', auth()->id())->whereIn('status', ['open', 'in_progress'])->orderBy('created_at', 'desc')->take(5)->get(); @endphp
                @forelse($pendingTickets as $ticket)
                <a href="{{ route('technician.tickets.view', $ticket) }}" class="d-flex justify-content-between align-items-center border-bottom px-3 py-2 text-dark text-decoration-none">
                    <div>
                        <strong>#{{ $ticket->ticket_number }}</strong>
                        <small class="d-block">{{ Str::limit($ticket->subject, 30) }}</small>
                    </div>
                    <div class="text-right">
                        <span class="badge badge-{{ $ticket->priority == 'high' ? 'danger' : ($ticket->priority == 'medium' ? 'warning' : 'info') }}">{{ ucfirst($ticket->priority) }}</span>
                        <small class="text-muted d-block">{{ $ticket->created_at->diffForHumans() }}</small>
                    </div>
                </a>
                @empty
                <div class="text-center text-muted py-4">
                    <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                    <p class="mb-0">No pending tickets</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-lg-6 mb-3">
        <div class="card card-info card-outline h-100">
            <div class="card-header py-2">
                <h3 class="card-title"><i class="fas fa-map-marker-alt mr-2"></i>My Zones</h3>
            </div>
            <div class="card-body p-0">
                @forelse($zones as $zone)
                <div class="d-flex justify-content-between align-items-center border-bottom px-3 py-2">
                    <strong>{{ $zone->name }}</strong>
                    <span class="badge badge-primary">{{ \App\Models\Client::where('zone_id', $zone->id)->count() }} clients</span>
                </div>
                @empty
                <div class="text-center text-muted py-4">
                    <i class="fas fa-map fa-2x mb-2"></i>
                    <p class="mb-0">No zones assigned</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.small-box { border-radius: 8px; padding: 15px; position: relative; overflow: hidden; }
.small-box .inner h3 { font-size: 1.8rem; font-weight: 700; margin: 0; color: #fff; }
.small-box .inner p { font-size: 0.9rem; margin: 0; color: rgba(255,255,255,0.8); }
.small-box .icon { position: absolute; right: 15px; top: 50%; transform: translateY(-50%); font-size: 3rem; opacity: 0.3; color: #fff; }
.small-box:hover { opacity: 0.9; }

.mobile-quick-search {
    position: relative;
}
.mobile-search-results {
    position: absolute;
    width: 100%;
    max-height: 400px;
    overflow-y: auto;
    background: white;
    z-index: 9999;
    box-shadow: 0 8px 16px rgba(0,0,0,0.15);
    border-radius: 8px;
    margin-top: 5px;
    border: 1px solid #ddd;
}
.search-result-item {
    display: block;
    padding: 12px 15px;
    border-bottom: 1px solid #eee;
    color: #333;
    text-decoration: none !important;
}
.search-result-item:hover {
    background-color: #f0f7ff;
    color: #007bff;
}
.result-avatar {
    width: 40px;
    height: 40px;
    background: #1e3c72;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 16px;
    margin-right: 12px;
}
.result-info {
    flex: 1;
}
.result-name {
    font-weight: 600;
    font-size: 14px;
    margin-bottom: 2px;
}
.result-meta {
    font-size: 11px;
    color: #666;
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}
.result-meta i {
    width: 12px;
    text-align: center;
}
.status-badges {
    display: flex;
    gap: 4px;
    margin-top: 4px;
}
.badge-xs {
    padding: 2px 5px;
    font-size: 10px;
}
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
let dailyChart, statusChart, monthlyChart, ticketChart;

function loadCharts() {
    fetch('/technician/api/chart-data')
        .then(r => r.json())
        .then(data => {
            // Daily Collection Line Chart
            const dailyCtx = document.getElementById('dailyCollectionChart');
            if(dailyCtx) {
                if(dailyChart) dailyChart.destroy();
                const gradient1 = dailyCtx.getContext('2d').createLinearGradient(0, 0, 0, 200);
                gradient1.addColorStop(0, 'rgba(0, 184, 148, 0.4)');
                gradient1.addColorStop(1, 'rgba(0, 184, 148, 0.02)');
                dailyChart = new Chart(dailyCtx, {
                    type: 'line',
                    data: {
                        labels: data.daily.labels,
                        datasets: [{
                            label: 'Collection (BDT)',
                            data: data.daily.data,
                            borderColor: '#00b894',
                            backgroundColor: gradient1,
                            fill: true,
                            tension: 0.4,
                            borderWidth: 3,
                            pointRadius: 4,
                            pointBackgroundColor: '#00b894',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2
                        }]
                    },
                    options: { 
                        responsive: true, 
                        plugins: { legend: { display: false } },
                        scales: { y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' } }, x: { grid: { display: false } } }
                    }
                });
            }
            
            // Client Status Pie Chart
            const statusCtx = document.getElementById('clientStatusChart');
            if(statusCtx) {
                if(statusChart) statusChart.destroy();
                statusChart = new Chart(statusCtx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Active', 'Inactive'],
                        datasets: [{
                            data: [data.status.active, data.status.inactive],
                            backgroundColor: ['#00b894', '#636e72', '#d63031'],
                            borderWidth: 0,
                            hoverOffset: 8
                        }]
                    },
                    options: { 
                        responsive: true, 
                        cutout: '60%',
                        plugins: { legend: { position: 'bottom', labels: { padding: 15, usePointStyle: true, pointStyle: 'circle' } } }
                    }
                });
            }
            
            // Monthly Collection Bar Chart
            const monthlyCtx = document.getElementById('monthlyCollectionChart');
            if(monthlyCtx) {
                if(monthlyChart) monthlyChart.destroy();
                const gradient2 = monthlyCtx.getContext('2d').createLinearGradient(0, 0, 0, 300);
                gradient2.addColorStop(0, 'rgba(9, 132, 227, 0.8)');
                gradient2.addColorStop(1, 'rgba(116, 185, 255, 0.4)');
                monthlyChart = new Chart(monthlyCtx, {
                    type: 'bar',
                    data: {
                        labels: data.monthly.labels,
                        datasets: [{
                            label: 'Collection (BDT)',
                            data: data.monthly.data,
                            backgroundColor: gradient2,
                            borderColor: '#0984e3',
                            borderWidth: 1,
                            borderRadius: 6,
                            borderSkipped: false
                        }]
                    },
                    options: { 
                        responsive: true, 
                        plugins: { legend: { display: false } },
                        scales: { y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' } }, x: { grid: { display: false } } }
                    }
                });
            }
            
            // Ticket Stats Bar Chart
            const ticketCtx = document.getElementById('ticketStatsChart');
            if(ticketCtx) {
                if(ticketChart) ticketChart.destroy();
                ticketChart = new Chart(ticketCtx, {
                    type: 'bar',
                    data: {
                        labels: ['Open', 'In Progress', 'Closed'],
                        datasets: [{
                            label: 'Tickets',
                            data: [data.tickets.open, data.tickets.in_progress, data.tickets.closed],
                            backgroundColor: ['#d63031', '#fdcb6e', '#00b894'],
                            borderRadius: 6,
                            borderSkipped: false
                        }]
                    },
                    options: { 
                        responsive: true, 
                        plugins: { legend: { display: false } },
                        scales: { y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' } }, x: { grid: { display: false } } }
                    }
                });
            }
        })
        .catch(e => console.log('Chart load failed'));
}

loadCharts();

$(document).ready(function() {
    let searchTimeout = null;
    const searchInput = $('#mobileClientSearch');
    const resultsDiv = $('#mobileSearchResults');

    searchInput.on('input', function() {
        const query = $(this).val().trim();
        
        if (searchTimeout) clearTimeout(searchTimeout);
        
        if (query.length < 2) {
            resultsDiv.empty().hide();
            return;
        }

        searchTimeout = setTimeout(() => {
            $.ajax({
                url: "{{ route('admin.api.quick-search') }}",
                data: { q: query },
                success: function(data) {
                    resultsDiv.empty();
                    if (data.length > 0) {
                        data.forEach(client => {
                            const initial = client.name.charAt(0).toUpperCase();
                            const statusClass = client.status === 'active' ? 'success' : (client.status === 'inactive' ? 'danger' : 'warning');
                            const connStatusClass = client.connection_status === 'online' ? 'success' : 'danger';
                            
                            resultsDiv.append(`
                                <a href="/technician/clients/${client.id}" class="search-result-item">
                                    <div class="d-flex align-items-center">
                                        <div class="result-avatar">${initial}</div>
                                        <div class="result-info">
                                            <div class="d-flex justify-content-between">
                                                <div class="result-name">${client.name}</div>
                                                <div class="status-badges">
                                                    <span class="badge badge-xs badge-${statusClass}">${client.status}</span>
                                                    <span class="badge badge-xs badge-${connStatusClass}"><i class="fas fa-wifi mr-1"></i>${client.connection_status}</span>
                                                </div>
                                            </div>
                                            <div class="result-meta">
                                                <span><i class="fas fa-id-card"></i> ${client.username || client.client_id}</span>
                                                <span class="text-success"><i class="fas fa-phone"></i> ${client.phone}</span>
                                                <span class="text-info"><i class="fas fa-map-marker-alt"></i> ${client.zone}</span>
                                            </div>
                                            <div class="result-meta mt-1">
                                                <small class="text-muted"><i class="fas fa-box"></i> ${client.package}</small>
                                            </div>
                                        </div>
                                        <i class="fas fa-chevron-right text-muted ml-2"></i>
                                    </div>
                                </a>
                            `);
                        });
                        resultsDiv.show();
                    } else {
                        resultsDiv.append('<div class="p-3 text-center text-muted">No clients found</div>').show();
                    }
                },
                error: function() {
                    resultsDiv.empty().hide();
                }
            });
        }, 300);
    });

    $(document).on('click', function(e) {
        if (!$(e.target).closest('.mobile-quick-search').length) {
            resultsDiv.empty().hide();
        }
    });

    // Handle focus to show results again if query exists
    searchInput.on('focus', function() {
        if ($(this).val().trim().length >= 2 && resultsDiv.children().length > 0) {
            resultsDiv.show();
        }
    });
});
</script>
@endpush
