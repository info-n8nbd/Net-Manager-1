@extends('layouts.admin')

@section('title', 'My Submitted Applications')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.applications.index') }}">Applications</a></li>
<li class="breadcrumb-item active">My Applications</li>
@endsection

@section('content')

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title"><i class="fas fa-file-signature mr-2"></i>My Submitted Applications</h3>
                @if($__canAction('dashboard', 'total_clients') || $__isSuperAdmin || ($__user->user_type ?? '') === 'admin')
                <a href="{{ route('admin.applications.create') }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus mr-1"></i> Submit New Application
                </a>
                @endif
            </div>

            <div class="card-body">
                {{-- Filters --}}
                <form method="GET" class="mb-3">
                    <div class="input-group">
                        <input type="text" name="search" class="form-control" placeholder="Search by application number, name or mobile..." value="{{ request('search') }}">
                        <select name="status" class="form-control ml-2" style="max-width:160px;">
                            <option value="">All Status</option>
                            <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="submitted" {{ request('status') == 'submitted' ? 'selected' : '' }}>Submitted</option>
                            <option value="under_review" {{ request('status') == 'under_review' ? 'selected' : '' }}>Under Review</option>
                            <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                            <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                        </select>
                        <div class="input-group-append ml-2">
                            <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i></button>
                            <a href="{{ route('admin.applications.my') }}" class="btn btn-secondary">Reset</a>
                        </div>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover table-sm">
                        <thead class="thead-light">
                            <tr>
                                <th>App. No.</th>
                                <th>Name</th>
                                <th>Mobile</th>
                                <th>Zone</th>
                                <th>Package</th>
                                <th>Status</th>
                                <th>Submitted</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($applications as $app)
                            <tr>
                                <td><code>{{ $app->application_number }}</code></td>
                                <td>{{ $app->full_name_english }}</td>
                                <td>{{ $app->mobile_number }}</td>
                                <td>{{ $app->zone->name ?? '—' }}</td>
                                <td>{{ $app->package->name ?? '—' }}</td>
                                <td>
                                    @php
                                        $statusColors = [
                                            'draft'          => 'secondary',
                                            'submitted'      => 'info',
                                            'under_review'   => 'warning',
                                            'approved'       => 'success',
                                            'rejected'       => 'danger',
                                            'correction_needed' => 'warning',
                                        ];
                                        $color = $statusColors[$app->status] ?? 'secondary';
                                    @endphp
                                    <span class="badge badge-{{ $color }}">{{ ucwords(str_replace('_', ' ', $app->status)) }}</span>
                                </td>
                                <td>{{ $app->created_at->format('d M Y') }}</td>
                                <td>
                                    <a href="{{ route('admin.applications.show', $app) }}" class="btn btn-xs btn-info">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-3">
                                    <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                                    No applications submitted yet.
                                    <a href="{{ route('admin.applications.create') }}" class="btn btn-sm btn-primary ml-2">
                                        <i class="fas fa-plus mr-1"></i> Submit First Application
                                    </a>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{ $applications->withQueryString()->links() }}
            </div>
        </div>

@endsection
