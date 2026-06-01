@extends('layouts.technician')

@section('title', 'My Applications')

@section('content')
<section class="content">
    <div class="container-fluid">
        <div class="row mb-3">
            <div class="col-4 col-lg-2 mb-2">
                <div class="stat-box bg-info text-white text-center py-3 rounded h-100">
                    <div class="stat-number">{{ $stats['total'] }}</div>
                    <div class="stat-label">Total</div>
                </div>
            </div>
            <div class="col-4 col-lg-2 mb-2">
                <div class="stat-box bg-secondary text-white text-center py-3 rounded h-100">
                    <div class="stat-number">{{ $stats['draft'] }}</div>
                    <div class="stat-label">Draft</div>
                </div>
            </div>
            <div class="col-4 col-lg-2 mb-2">
                <div class="stat-box bg-primary text-white text-center py-3 rounded h-100">
                    <div class="stat-number">{{ $stats['submitted'] }}</div>
                    <div class="stat-label">Submitted</div>
                </div>
            </div>
            <div class="col-4 col-lg-2 mb-2">
                <div class="stat-box bg-warning text-dark text-center py-3 rounded h-100">
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
                <h3 class="card-title">Application List</h3>
                <div class="card-tools">
                    <form action="" method="GET" class="form-inline">
                        <select name="status" class="form-control form-control-sm mr-2">
                            <option value="">All Status</option>
                            <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="submitted" {{ request('status') == 'submitted' ? 'selected' : '' }}>Submitted</option>
                            <option value="under_review" {{ request('status') == 'under_review' ? 'selected' : '' }}>Under Review</option>
                            <option value="need_correction" {{ request('status') == 'need_correction' ? 'selected' : '' }}>Need Correction</option>
                            <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                            <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                        </select>
                        <input type="text" name="search" class="form-control form-control-sm mr-2" placeholder="Search..." value="{{ request('search') }}">
                        <button type="submit" class="btn btn-sm btn-primary">Filter</button>
                    </form>
                </div>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover text-nowrap">
                    <thead>
                        <tr>
                            <th>Application #</th>
                            <th>Customer Name</th>
                            <th>Mobile</th>
                            <th>Package</th>
                            <th>Zone</th>
                            <th>Status</th>
                            <th>Submitted</th>
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
                            <td><span class="badge {{ $app->status_badge_class }}">{{ $app->status_label }}</span></td>
                            <td>{{ $app->created_at->format('d-m-Y') }}</td>
                            <td>
                                <a href="{{ route('technician.applications.show', $app) }}" class="btn btn-sm btn-info">
                                    <i class="fas fa-eye"></i>
                                </a>
                                @if($app->canBeEdited())
                                <a href="{{ route('technician.applications.edit', $app) }}" class="btn btn-sm btn-warning">
                                    <i class="fas fa-edit"></i>
                                </a>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center">No applications found</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer">
                {{ $applications->appends(request()->query())->links() }}
            </div>
        </div>
    </div>
</section>
@endsection
