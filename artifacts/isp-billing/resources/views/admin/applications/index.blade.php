@extends('layouts.admin')

@section('title', 'New Connection Applications')

@section('content')
<style>
.app-card { border-left: 4px solid #17a2b8; margin-bottom: 12px; background: #fff; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
.app-card.status-submitted { border-left-color: #007bff; }
.app-card.status-under_review { border-left-color: #ffc107; }
.app-card.status-need_correction { border-left-color: #fd7e14; }
.app-card.status-approved { border-left-color: #28a745; }
.app-card.status-rejected { border-left-color: #dc3545; }
.app-card .card-body { padding: 12px 15px; }
.app-card .app-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px; }
.app-card .app-name { font-weight: 600; font-size: 15px; color: #333; margin: 0; }
.app-card .app-info { font-size: 13px; color: #666; margin-bottom: 5px; }
.app-card .app-meta { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 10px; font-size: 12px; }
.app-card .btn-group-mobile { display: flex; flex-wrap: wrap; gap: 6px; }
.app-card .btn-group-mobile .btn { flex: 1; min-width: 70px; font-size: 13px; padding: 6px 10px; }
@media (min-width: 768px) { .mobile-cards { display: none; } }
@media (max-width: 767px) { .desktop-table { display: none; } .card-tools form { flex-wrap: wrap; gap: 5px; } .card-tools .form-control { margin-bottom: 5px; } }
</style>
        <div class="row mb-3">
            <div class="col-4 col-lg-2 mb-2">
                <div class="stat-box bg-info text-white text-center py-3 rounded h-100">
                    <div class="stat-number">{{ $stats['total'] }}</div>
                    <div class="stat-label">Total</div>
                </div>
            </div>
            <div class="col-4 col-lg-2 mb-2">
                <div class="stat-box bg-primary text-white text-center py-3 rounded h-100">
                    <div class="stat-number">{{ $stats['submitted'] }}</div>
                    <div class="stat-label">Pending</div>
                </div>
            </div>
            <div class="col-4 col-lg-2 mb-2">
                <div class="stat-box bg-warning text-dark text-center py-3 rounded h-100">
                    <div class="stat-number">{{ $stats['under_review'] }}</div>
                    <div class="stat-label">Review</div>
                </div>
            </div>
            <div class="col-4 col-lg-2 mb-2">
                <div class="stat-box bg-orange text-white text-center py-3 rounded h-100">
                    <div class="stat-number">{{ $stats['need_correction'] }}</div>
                    <div class="stat-label">Correct</div>
                </div>
            </div>
            <div class="col-4 col-lg-2 mb-2">
                <div class="stat-box bg-success text-white text-center py-3 rounded h-100">
                    <div class="stat-number">{{ $stats['approved'] }}</div>
                    <div class="stat-label">Approved</div>
                </div>
            </div>
            <div class="col-4 col-lg-2 mb-2">
                <div class="stat-box bg-danger text-white text-center py-3 rounded h-100">
                    <div class="stat-number">{{ $stats['rejected'] }}</div>
                    <div class="stat-label">Rejected</div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">All Applications</h3>
                <div class="card-tools">
                    <form action="" method="GET" class="form-inline">
                        <select name="status" class="form-control form-control-sm mr-2">
                            <option value="">All Status</option>
                            <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="submitted" {{ request('status') == 'submitted' ? 'selected' : '' }}>Pending</option>
                            <option value="under_review" {{ request('status') == 'under_review' ? 'selected' : '' }}>Under Review</option>
                            <option value="need_correction" {{ request('status') == 'need_correction' ? 'selected' : '' }}>Need Correction</option>
                            <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                            <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                        </select>
                        <select name="zone_id" class="form-control form-control-sm mr-2">
                            <option value="">All Zones</option>
                            @foreach($zones as $zone)
                            <option value="{{ $zone->id }}" {{ request('zone_id') == $zone->id ? 'selected' : '' }}>{{ $zone->name }}</option>
                            @endforeach
                        </select>
                        <input type="text" name="search" class="form-control form-control-sm mr-2" placeholder="Search..." value="{{ request('search') }}">
                        <button type="submit" class="btn btn-sm btn-primary">Filter</button>
                    </form>
                </div>
            </div>
            <div class="card-body">
                <div class="mobile-cards">
                    @forelse($applications as $app)
                    <div class="card app-card status-{{ $app->status }}">
                        <div class="card-body">
                            <div class="app-header">
                                <h6 class="app-name">{{ $app->full_name_english }}</h6>
                                <span class="badge {{ $app->status_badge_class }}">{{ $app->status_label }}</span>
                            </div>
                            <div class="app-info">
                                <strong>#{{ $app->application_number }}</strong> | {{ $app->mobile_number }}
                            </div>
                            <div class="app-meta">
                                <span><i class="fas fa-box mr-1"></i>{{ $app->package?->name ?? '-' }}</span>
                                <span><i class="fas fa-map-marker-alt mr-1"></i>{{ $app->zone?->name ?? '-' }}</span>
                                <span><i class="fas fa-calendar mr-1"></i>{{ $app->created_at->format('d-m-Y') }}</span>
                            </div>
                            @if($app->technician)
                            <div class="app-info"><i class="fas fa-user-cog mr-1"></i>{{ $app->technician->name }}</div>
                            @endif
                            <div class="btn-group-mobile">
                                <a href="{{ route('admin.applications.show', $app) }}" class="btn btn-info btn-sm">
                                    <i class="fas fa-eye mr-1"></i> View
                                </a>
                                <a href="tel:{{ $app->mobile_number }}" class="btn btn-success btn-sm">
                                    <i class="fas fa-phone mr-1"></i> Call
                                </a>
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="text-center text-muted py-4">No applications found</div>
                    @endforelse
                </div>

                <div class="desktop-table">
                    <div class="table-responsive">
                        <table class="table table-hover text-nowrap">
                            <thead>
                                <tr>
                                    <th>Application #</th>
                                    <th>Customer</th>
                                    <th>Mobile</th>
                                    <th>Package</th>
                                    <th>Zone</th>
                                    <th>Technician</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($applications as $app)
                                <tr>
                                    <td>{{ $app->application_number }}</td>
                                    <td>{{ $app->full_name_english }}</td>
                                    <td>{{ $app->mobile_number }}</td>
                                    <td>{{ $app->package?->name ?? '-' }}</td>
                                    <td>{{ $app->zone?->name ?? '-' }}</td>
                                    <td>{{ $app->technician?->name ?? '-' }}</td>
                                    <td><span class="badge {{ $app->status_badge_class }}">{{ $app->status_label }}</span></td>
                                    <td>{{ $app->created_at->format('d-m-Y') }}</td>
                                    <td class="d-flex" style="gap:4px">
                                        <a href="{{ route('admin.applications.show', $app) }}" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                        <form action="{{ route('admin.applications.destroy', $app) }}" method="POST"
                                              onsubmit="return confirm('Delete application {{ $app->application_no }}? This cannot be undone.')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="9" class="text-center">No applications found</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                {{ $applications->appends(request()->query())->links() }}
            </div>
        </div>
@endsection
