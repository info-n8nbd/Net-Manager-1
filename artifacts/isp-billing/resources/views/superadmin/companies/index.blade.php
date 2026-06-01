@extends('layouts.admin')

@section('title', 'Company Management')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('superadmin.dashboard') }}">Super Admin</a></li>
    <li class="breadcrumb-item active">Companies</li>
@endsection

@section('content')

{{-- Summary Stats --}}
@php
    $totalCompanies  = $companies->count();
    $activeCompanies = $companies->where('is_active', true)->count();
    $inactiveComp    = $companies->where('is_active', false)->count();
    $expiringSoon    = $companies->filter(function($c) {
        return $c->subscription_end && !$c->subscription_end->isPast()
            && $c->subscription_end->diffInDays(now()) <= 30;
    })->count();
    $expired         = $companies->filter(fn($c) => $c->subscription_end && $c->subscription_end->isPast())->count();
@endphp
<div class="row mb-3">
    <div class="col-6 col-md-3">
        <div class="info-box mb-0">
            <span class="info-box-icon bg-primary"><i class="fas fa-building"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Total Companies</span>
                <span class="info-box-number">{{ $totalCompanies }}</span>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="info-box mb-0">
            <span class="info-box-icon bg-success"><i class="fas fa-check-circle"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Active</span>
                <span class="info-box-number">{{ $activeCompanies }}</span>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="info-box mb-0">
            <span class="info-box-icon bg-warning"><i class="fas fa-exclamation-triangle"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Expiring (30d)</span>
                <span class="info-box-number">{{ $expiringSoon }}</span>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="info-box mb-0">
            <span class="info-box-icon bg-danger"><i class="fas fa-times-circle"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Inactive / Expired</span>
                <span class="info-box-number">{{ $inactiveComp + $expired }}</span>
            </div>
        </div>
    </div>
</div>

<div class="card card-primary card-outline">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-building mr-2"></i>All Companies</h3>
        <div class="card-tools d-flex align-items-center" style="gap:8px;">
            <div class="input-group input-group-sm" style="width:220px;">
                <input type="text" id="companySearch" class="form-control" placeholder="Search company, email…">
                <div class="input-group-append">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                </div>
            </div>
            <div class="btn-group btn-group-sm" id="statusFilter">
                <button type="button" class="btn btn-outline-secondary active" data-filter="all">All</button>
                <button type="button" class="btn btn-outline-success" data-filter="active">Active</button>
                <button type="button" class="btn btn-outline-danger" data-filter="inactive">Inactive</button>
            </div>
            <a href="{{ route('superadmin.companies.create') }}" class="btn btn-primary btn-sm">
                <i class="fas fa-plus mr-1"></i>Add Company
            </a>
        </div>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover table-striped mb-0" id="companiesTable">
            <thead class="thead-dark">
                <tr>
                    <th style="width:40px;">#</th>
                    <th>Company</th>
                    <th>Admin Email</th>
                    <th class="text-center" style="width:70px;">Clients</th>
                    <th class="text-center" style="width:60px;">Users</th>
                    <th class="text-center" style="width:80px;">Status</th>
                    <th style="width:130px;">Subscription End</th>
                    <th class="text-center" style="width:170px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($companies as $idx => $company)
                @php
                    $isExpired     = $company->subscription_end && $company->subscription_end->isPast();
                    $isExpiringSoon= $company->subscription_end && !$isExpired
                                    && $company->subscription_end->diffInDays(now()) <= 30;
                    $rowClass      = '';
                    if (!$company->is_active || $isExpired) $rowClass = 'table-danger';
                    elseif ($isExpiringSoon)                  $rowClass = 'table-warning';
                @endphp
                <tr class="{{ $rowClass }}"
                    data-name="{{ strtolower($company->name) }}"
                    data-email="{{ strtolower(($adminUsersByCompany[$company->id] ?? null)?->email ?? '') }}"
                    data-status="{{ $company->is_active ? 'active' : 'inactive' }}">
                    <td>{{ $idx + 1 }}</td>
                    <td>
                        <a href="{{ route('superadmin.companies.show', $company) }}" class="font-weight-bold">
                            {{ $company->name }}
                        </a>
                        <br>
                        <small class="text-muted">{{ $company->email }}</small>
                        @if($company->slug)
                        <br><small class="text-muted" style="font-size:10px;">/{{ $company->slug }}</small>
                        @endif
                    </td>
                    <td>
                        <span class="small">{{ ($adminUsersByCompany[$company->id] ?? null)?->email ?? '<span class="text-muted">N/A</span>' }}</span>
                    </td>
                    <td class="text-center">
                        <span class="badge badge-primary">{{ $company->clients_count }}</span>
                        @if($company->max_clients > 0)
                        <br><small class="text-muted" style="font-size:10px;">/ {{ $company->max_clients }}</small>
                        @endif
                    </td>
                    <td class="text-center">
                        <span class="badge badge-info">{{ $company->users_count }}</span>
                    </td>
                    <td class="text-center">
                        @if($company->is_active)
                            <span class="badge badge-success"><i class="fas fa-check mr-1"></i>Active</span>
                        @else
                            <span class="badge badge-danger"><i class="fas fa-times mr-1"></i>Inactive</span>
                        @endif
                    </td>
                    <td>
                        @if($company->subscription_end)
                            @if($isExpired)
                                <span class="text-danger font-weight-bold">
                                    <i class="fas fa-exclamation-circle mr-1"></i>
                                    {{ $company->subscription_end->format('d M Y') }}
                                    <br><small>Expired</small>
                                </span>
                            @elseif($isExpiringSoon)
                                <span class="text-warning font-weight-bold">
                                    <i class="fas fa-clock mr-1"></i>
                                    {{ $company->subscription_end->format('d M Y') }}
                                    <br><small>{{ $company->subscription_end->diffInDays(now()) }}d left</small>
                                </span>
                            @else
                                <span class="text-success">
                                    {{ $company->subscription_end->format('d M Y') }}
                                </span>
                            @endif
                        @else
                            <span class="text-muted"><i class="fas fa-infinity mr-1"></i>Unlimited</span>
                        @endif
                    </td>
                    <td class="text-center">
                        <div class="btn-group btn-group-sm">
                            <a href="{{ route('superadmin.companies.show', $company) }}"
                               class="btn btn-info" title="View">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('superadmin.companies.edit', $company) }}"
                               class="btn btn-warning" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form action="{{ route('superadmin.companies.toggle-status', $company) }}"
                                  method="POST" style="display:inline;">
                                @csrf
                                <button type="submit"
                                        class="btn {{ $company->is_active ? 'btn-secondary' : 'btn-success' }}"
                                        title="{{ $company->is_active ? 'Deactivate' : 'Activate' }}">
                                    <i class="fas {{ $company->is_active ? 'fa-ban' : 'fa-check' }}"></i>
                                </button>
                            </form>
                            <form action="{{ route('superadmin.companies.login-as', $company) }}"
                                  method="POST" style="display:inline;"
                                  onsubmit="return confirm('Login as admin of {{ addslashes($company->name) }}?');">
                                @csrf
                                <button type="submit" class="btn btn-dark" title="Login as Admin">
                                    <i class="fas fa-sign-in-alt"></i>
                                </button>
                            </form>
                            <form action="{{ route('superadmin.companies.destroy', $company) }}"
                                  method="POST" style="display:inline;"
                                  onsubmit="return confirm('DELETE COMPANY: {{ addslashes($company->name) }}\n\nAll clients, users, bills, payments and data will be permanently deleted!\n\nType DELETE to confirm:') && prompt('Type DELETE to confirm:') === 'DELETE';">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger" title="Delete Company">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center text-muted py-5">
                        <i class="fas fa-building fa-3x mb-3 d-block"></i>
                        No companies found.
                        <a href="{{ route('superadmin.companies.create') }}">Create one now</a>.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($companies->count() > 0)
    <div class="card-footer py-2">
        <small class="text-muted">
            Showing {{ $companies->count() }} companies &nbsp;|&nbsp;
            <span class="text-success">{{ $activeCompanies }} active</span>
            @if($expiringSoon > 0)
            &nbsp;|&nbsp; <span class="text-warning"><i class="fas fa-exclamation-triangle mr-1"></i>{{ $expiringSoon }} expiring soon</span>
            @endif
            @if($expired > 0)
            &nbsp;|&nbsp; <span class="text-danger"><i class="fas fa-times-circle mr-1"></i>{{ $expired }} expired</span>
            @endif
        </small>
    </div>
    @endif
</div>

@endsection

@push('scripts')
<script>
$(function () {
    var currentFilter = 'all';

    function applyFilters() {
        var search = $('#companySearch').val().toLowerCase().trim();
        $('#companiesTable tbody tr').each(function () {
            var name   = $(this).data('name') || '';
            var email  = $(this).data('email') || '';
            var status = $(this).data('status') || '';
            var matchSearch = !search || name.indexOf(search) > -1 || email.indexOf(search) > -1;
            var matchFilter = currentFilter === 'all' || status === currentFilter;
            $(this).toggle(matchSearch && matchFilter);
        });
    }

    $('#companySearch').on('keyup', applyFilters);

    $('#statusFilter button').on('click', function () {
        $('#statusFilter button').removeClass('active btn-secondary btn-success btn-danger')
            .addClass('btn-outline-secondary btn-outline-success btn-outline-danger');
        $(this).removeClass('btn-outline-secondary btn-outline-success btn-outline-danger').addClass('active');
        currentFilter = $(this).data('filter');
        applyFilters();
    });
});
</script>
@endpush
