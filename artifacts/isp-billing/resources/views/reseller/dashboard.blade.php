@extends('layouts.reseller')

@section('title', 'Dashboard')

@section('content')

{{-- Current Month ISP Bill Alert --}}
@if($reseller->type === 'mac')
@php
    $cmMonth = now()->format('F Y');
    $cmBill   = $currentMonthBill ? (float)$currentMonthBill->total_recharge : 0;
    $cmPaid   = $currentMonthBill ? (float)$currentMonthBill->total_paid     : 0;
    $cmDue    = $currentMonthBill ? (float)$currentMonthBill->due_amount      : 0;
    $cmStatus = $currentMonthBill ? $currentMonthBill->status : null;
@endphp
@if($currentMonthBill)
<div class="alert mb-2 py-2 px-3 d-flex align-items-center justify-content-between flex-wrap"
     style="border-radius:8px; border:none;
            background: {{ $cmDue > 0 ? 'linear-gradient(135deg,#fff5f5,#ffe0e0)' : 'linear-gradient(135deg,#f0fff4,#d4f7e0)' }};
            border-left: 4px solid {{ $cmDue > 0 ? '#e53e3e' : '#38a169' }};">
    <div class="d-flex align-items-center flex-wrap" style="gap:18px;">
        <div>
            <span style="font-size:11px;text-transform:uppercase;letter-spacing:1px;color:#718096;font-weight:600;">
                <i class="fas fa-file-invoice-dollar mr-1"></i>{{ $cmMonth }} — ISP Bill
            </span>
            <div style="font-size:13px;margin-top:2px;">
                <span class="mr-3"><span style="color:#718096;">Bill:</span> <strong>৳{{ number_format($cmBill,2) }}</strong></span>
                <span class="mr-3 text-success"><span style="color:#718096;">Paid:</span> <strong>৳{{ number_format($cmPaid,2) }}</strong></span>
                <span class="{{ $cmDue > 0 ? 'text-danger' : 'text-success' }}">
                    <span style="color:#718096;">Due:</span>
                    <strong>৳{{ number_format($cmDue,2) }}</strong>
                </span>
                <span class="ml-2">
                    @if($cmStatus === 'paid')
                        <span class="badge badge-success" style="font-size:10px;">PAID</span>
                    @elseif($cmStatus === 'partial')
                        <span class="badge badge-warning" style="font-size:10px;">PARTIAL</span>
                    @else
                        <span class="badge badge-danger" style="font-size:10px;">UNPAID</span>
                    @endif
                </span>
            </div>
        </div>
    </div>
    <a href="{{ route('reseller.mac-billing') }}" class="btn btn-sm mt-1 mt-md-0"
       style="background:{{ $cmDue > 0 ? '#e53e3e' : '#38a169' }};color:#fff;border:none;font-size:12px;white-space:nowrap;">
        <i class="fas fa-eye mr-1"></i>View Invoices
    </a>
</div>
@else
<div class="alert mb-2 py-2 px-3 d-flex align-items-center justify-content-between flex-wrap"
     style="border-radius:8px; border:none; background:linear-gradient(135deg,#fffbf0,#fef3cd);
            border-left:4px solid #d69e2e;">
    <div>
        <span style="font-size:11px;text-transform:uppercase;letter-spacing:1px;color:#718096;font-weight:600;">
            <i class="fas fa-file-invoice-dollar mr-1"></i>{{ $cmMonth }} — ISP Bill
        </span>
        <div style="font-size:13px;margin-top:2px;">
            <span style="color:#744210;">No invoice generated yet for this month.</span>
        </div>
    </div>
    <a href="{{ route('reseller.mac-billing') }}" class="btn btn-sm mt-1 mt-md-0"
       style="background:#d69e2e;color:#fff;border:none;font-size:12px;white-space:nowrap;">
        <i class="fas fa-eye mr-1"></i>View Invoices
    </a>
</div>
@endif
@endif

<div class="mobile-quick-search d-block d-lg-none">
    <div class="search-input-wrapper">
        <i class="fas fa-search search-icon"></i>
        <input type="text" id="mobileClientSearch" placeholder="Search client by name, ID, phone...">
    </div>
    <div class="mobile-search-results" id="mobileSearchResults"></div>
</div>


<div class="row mb-2">
    <div class="col-12">
        <div class="card card-dark card-outline mb-0">
            <div class="card-header bg-gradient-dark py-2">
                <h3 class="card-title"><i class="fas fa-satellite-dish mr-2 live-pulse"></i> Real-Time</h3>
                <div class="card-tools">
                    <small class="text-light" id="liveTimestamp">--:--:--</small>
                </div>
            </div>
            <div class="card-body p-2">
                <div class="row no-gutters">
                    <div class="col-4 p-1">
                        <div class="stat-box bg-success text-white text-center py-3 rounded">
                            <div class="stat-number" id="liveTodayCollection">৳0</div>
                            <div class="stat-label">Today</div>
                        </div>
                    </div>
                    <div class="col-4 p-1">
                        <div class="stat-box bg-info text-white text-center py-3 rounded">
                            <div class="stat-number" id="liveTodayPayments">0</div>
                            <div class="stat-label">Payments</div>
                        </div>
                    </div>
                    <div class="col-4 p-1">
                        <div class="stat-box bg-primary text-white text-center py-3 rounded">
                            <div class="stat-number" id="liveActiveClients">{{ $activeClients }}</div>
                            <div class="stat-label">Active</div>
                        </div>
                    </div>
                    <div class="col-4 p-1">
                        <div class="stat-box bg-warning text-dark text-center py-3 rounded">
                            <div class="stat-number" id="liveWallet">৳{{ number_format($reseller->wallet_balance ?? 0, 0) }}</div>
                            <div class="stat-label">Wallet</div>
                        </div>
                    </div>
                    <div class="col-4 p-1">
                        <div class="stat-box bg-danger text-white text-center py-3 rounded">
                            <div class="stat-number" id="liveTotalDue">৳{{ number_format($dueAmount ?? 0, 0) }}</div>
                            <div class="stat-label">Client Due</div>
                        </div>
                    </div>
                    <div class="col-4 p-1">
                        <div class="stat-box bg-secondary text-white text-center py-3 rounded">
                            <div class="stat-number" id="liveInactiveClients">{{ $inactiveClients }}</div>
                            <div class="stat-label">Inactive</div>
                        </div>
                    </div>
                    <div class="col-4 p-1">
                        <div class="stat-box bg-maroon text-white text-center py-3 rounded">
                            <div class="stat-number" id="liveResellerDue">৳{{ number_format($resellerDue ?? 0, 0) }}</div>
                            <div class="stat-label">My Due (ISP)</div>
                        </div>
                    </div>
                    @if($reseller->billing_type == 'postpaid')
                    <div class="col-4 p-1">
                        <div class="stat-box bg-navy text-white text-center py-3 rounded">
                            <div class="stat-number">৳{{ number_format($reseller->credit_limit ?? 0, 0) }}</div>
                            <div class="stat-label">Credit Limit</div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mb-2">
    <div class="col-6 col-lg-3 mb-2">
        <a href="{{ route('reseller.clients') }}" class="text-decoration-none">
            <div class="stat-box bg-info text-white text-center py-3 rounded h-100">
                <div class="stat-number">{{ $totalClients }}</div>
                <div class="stat-label">Total Clients</div>
            </div>
        </a>
    </div>
    <div class="col-6 col-lg-3 mb-2">
        <a href="{{ route('reseller.collection-report') }}" class="text-decoration-none">
            <div class="stat-box bg-teal text-white text-center py-3 rounded h-100">
                <div class="stat-number">৳{{ number_format($thisMonthCollection ?? 0, 0) }}</div>
                <div class="stat-label">Monthly Collection</div>
            </div>
        </a>
    </div>
    <div class="col-6 col-lg-3 mb-2">
        <div class="stat-box bg-olive text-white text-center py-3 rounded h-100">
            <div class="stat-number">৳{{ number_format($totalCollection ?? 0, 0) }}</div>
            <div class="stat-label">All-Time Collection</div>
        </div>
    </div>
    <div class="col-6 col-lg-3 mb-2">
        <div class="stat-box bg-purple text-white text-center py-3 rounded h-100">
            <div class="stat-number">৳{{ number_format($dueAmount ?? 0, 0) }}</div>
            <div class="stat-label">Total Due</div>
        </div>
    </div>
</div>

<div class="row mb-3">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-chart-line mr-2"></i>Collection Trend (Last 30 Days)</h3>
            </div>
            <div class="card-body">
                <canvas id="dailyCollectionChart" height="120"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
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
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-chart-bar mr-2"></i>Monthly Collection</h3>
            </div>
            <div class="card-body">
                <canvas id="monthlyCollectionChart" height="140"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-cubes mr-2"></i>Package Distribution</h3>
            </div>
            <div class="card-body" style="height: 280px;">
                <canvas id="packageDistributionChart"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-history mr-2"></i>Recent Payments</h3>
            </div>
            <div class="card-body table-responsive p-0" style="max-height: 350px;">
                <table class="table table-hover table-head-fixed">
                    <thead>
                        <tr>
                            <th>Client</th>
                            <th>Amount</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentPayments as $payment)
                        <tr>
                            <td>{{ $payment->client->name ?? 'N/A' }}</td>
                            <td class="text-success">৳{{ number_format($payment->amount ?? 0) }}</td>
                            <td>{{ $payment->payment_date->format('d M') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="text-center text-muted">No payments yet</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-user-plus mr-2"></i>Recent Clients</h3>
            </div>
            <div class="card-body table-responsive p-0" style="max-height: 350px;">
                <table class="table table-hover table-head-fixed">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Package</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentClients as $client)
                        <tr>
                            <td>
                                <a href="{{ route('reseller.clients.show', $client) }}">{{ $client->name }}</a>
                            </td>
                            <td>{{ $client->package->name ?? 'N/A' }}</td>
                            <td>
                                <span class="badge badge-{{ $client->status == 'active' ? 'success' : 'secondary' }}">
                                    {{ ucfirst($client->status) }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="text-center text-muted">No clients yet</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
let dailyChart, statusChart, monthlyChart, packageChart;

function loadCharts() {
    fetch('/reseller/api/chart-data')
        .then(r => r.json())
        .then(data => {
            // Daily Collection Line Chart
            const dailyCtx = document.getElementById('dailyCollectionChart');
            if(dailyCtx) {
                if(dailyChart) dailyChart.destroy();
                const gradient = dailyCtx.getContext('2d').createLinearGradient(0, 0, 0, 200);
                gradient.addColorStop(0, 'rgba(0, 184, 148, 0.4)');
                gradient.addColorStop(1, 'rgba(0, 184, 148, 0.02)');
                dailyChart = new Chart(dailyCtx, {
                    type: 'line',
                    data: {
                        labels: data.daily.labels,
                        datasets: [{
                            label: 'Collection (BDT)',
                            data: data.daily.data,
                            borderColor: '#00b894',
                            backgroundColor: gradient,
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
                const gradient = monthlyCtx.getContext('2d').createLinearGradient(0, 0, 0, 300);
                gradient.addColorStop(0, 'rgba(0, 184, 148, 0.8)');
                gradient.addColorStop(1, 'rgba(0, 206, 201, 0.4)');
                monthlyChart = new Chart(monthlyCtx, {
                    type: 'bar',
                    data: {
                        labels: data.monthly.labels,
                        datasets: [{
                            label: 'Collection (BDT)',
                            data: data.monthly.data,
                            backgroundColor: gradient,
                            borderColor: '#00b894',
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
            
            // Package Distribution Pie Chart
            const packageCtx = document.getElementById('packageDistributionChart');
            if(packageCtx) {
                if(packageChart) packageChart.destroy();
                const pkgLabels = Object.keys(data.packages);
                const pkgData = Object.values(data.packages);
                const colors = ['#0984e3', '#00b894', '#fdcb6e', '#d63031', '#00cec9', '#6c5ce7', '#e17055', '#74b9ff'];
                packageChart = new Chart(packageCtx, {
                    type: 'doughnut',
                    data: {
                        labels: pkgLabels.length > 0 ? pkgLabels : ['No Clients'],
                        datasets: [{
                            data: pkgData.length > 0 ? pkgData : [1],
                            backgroundColor: colors.slice(0, pkgLabels.length || 1),
                            borderWidth: 0,
                            hoverOffset: 8
                        }]
                    },
                    options: { 
                        responsive: true, 
                        maintainAspectRatio: false, 
                        cutout: '55%',
                        plugins: { legend: { position: 'bottom', labels: { padding: 12, usePointStyle: true, pointStyle: 'circle' } } }
                    }
                });
            }
        })
        .catch(e => console.log('Chart load failed'));
}

loadCharts();

function updateLiveStats() {
    fetch('/reseller/api/live-stats')
        .then(r => r.json())
        .then(data => {
            document.getElementById('liveTodayCollection').textContent = data.today_collection_formatted;
            document.getElementById('liveTodayPayments').textContent = data.today_payments_count;
            document.getElementById('liveActiveClients').textContent = data.active_clients;
            document.getElementById('liveInactiveClients').textContent = data.inactive_clients;
            document.getElementById('liveTotalDue').textContent = data.total_due_formatted;
            document.getElementById('liveWallet').textContent = data.wallet_formatted;
            if(data.reseller_due_formatted) {
                document.getElementById('liveResellerDue').textContent = data.reseller_due_formatted;
            }
            document.getElementById('liveTimestamp').textContent = 'Updated: ' + data.timestamp;
            
            if(data.recent_payments && data.recent_payments.length > 0) {
                var html = '';
                data.recent_payments.slice(0, 10).forEach(function(payment) {
                    html += '<tr>' +
                        '<td>' + payment.client_name + '</td>' +
                        '<td class="text-success">৳' + payment.amount.toLocaleString() + '</td>' +
                        '<td>' + payment.date + ' ' + payment.time + '</td>' +
                        '</tr>';
                });
                var container = document.getElementById('livePaymentsTable');
                if(container) container.innerHTML = html;
            }
        })
        .catch(e => console.log('Live stats update failed'));
}

updateLiveStats();
setInterval(updateLiveStats, 5000);

// Mobile Client Search
var mobileSearchInput = document.getElementById('mobileClientSearch');
var mobileSearchResults = document.getElementById('mobileSearchResults');
var searchTimeout;

if(mobileSearchInput) {
    mobileSearchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        var query = this.value.trim();
        
        if(query.length < 2) {
            mobileSearchResults.classList.remove('active');
            return;
        }
        
        searchTimeout = setTimeout(function() {
            fetch('/reseller/api/quick-search?q=' + encodeURIComponent(query))
                .then(r => r.json())
                .then(data => {
                    if(data.results && data.results.length > 0) {
                        var html = '';
                        data.results.forEach(function(client) {
                            html += '<a href="/reseller/clients/' + client.id + '" class="result-item">' +
                                '<div>' +
                                    '<div class="result-name">' + client.name + '</div>' +
                                    '<div class="result-info">ID: ' + client.client_id + ' | ' + client.phone + '</div>' +
                                '</div>' +
                                '<span class="badge badge-' + (client.status === 'active' ? 'success' : 'danger') + '">' + client.status + '</span>' +
                            '</a>';
                        });
                        mobileSearchResults.innerHTML = html;
                        mobileSearchResults.classList.add('active');
                    } else {
                        mobileSearchResults.innerHTML = '<div class="result-item"><div class="result-info">No clients found</div></div>';
                        mobileSearchResults.classList.add('active');
                    }
                })
                .catch(function() {
                    mobileSearchResults.classList.remove('active');
                });
        }, 300);
    });
    
    document.addEventListener('click', function(e) {
        if(!mobileSearchInput.contains(e.target) && !mobileSearchResults.contains(e.target)) {
            mobileSearchResults.classList.remove('active');
        }
    });
}
</script>

<style>
.live-pulse { animation: pulse 2s infinite; }
@keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.6; } }
</style>
@endpush
