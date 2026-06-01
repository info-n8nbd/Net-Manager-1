@extends('layouts.admin')

@section('title', 'Company Details - ' . $company->name)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('superadmin.dashboard') }}">Super Admin</a></li>
    <li class="breadcrumb-item"><a href="{{ route('superadmin.companies.index') }}">Companies</a></li>
    <li class="breadcrumb-item active">{{ $company->name }}</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-4">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-building mr-2"></i>Company Info</h3>
                <div class="card-tools">
                    <a href="{{ route('superadmin.companies.edit', $company) }}" class="btn btn-tool"><i class="fas fa-edit"></i></a>
                </div>
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless">
                    <tr>
                        <td class="text-muted" style="width: 40%;">Name</td>
                        <td><strong>{{ $company->name }}</strong></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Email</td>
                        <td>{{ $company->email }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Phone</td>
                        <td>{{ $company->phone ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Address</td>
                        <td>{{ $company->address ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Status</td>
                        <td>
                            @if($company->is_active)
                                <span class="badge badge-success"><i class="fas fa-check mr-1"></i>Active</span>
                            @else
                                <span class="badge badge-danger"><i class="fas fa-times mr-1"></i>Inactive</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted">Max Clients</td>
                        <td>{{ $company->max_clients > 0 ? $company->max_clients : 'Unlimited' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Subscription Start</td>
                        <td>{{ $company->subscription_start ? $company->subscription_start->format('d M Y') : 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Subscription End</td>
                        <td>
                            @if($company->subscription_end)
                                <span class="{{ $company->subscription_end->isPast() ? 'text-danger' : 'text-success' }}">
                                    {{ $company->subscription_end->format('d M Y') }}
                                    @if($company->subscription_end->isPast())
                                        <small>(Expired)</small>
                                    @elseif($company->subscription_end->diffInDays(now()) <= 7)
                                        <small>(Expiring soon)</small>
                                    @endif
                                </span>
                            @else
                                <span class="text-muted">Unlimited</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted">Created</td>
                        <td>{{ $company->created_at->format('d M Y H:i') }}</td>
                    </tr>
                </table>
            </div>
            <div class="card-footer">
                <form action="{{ route('superadmin.companies.toggle-status', $company) }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-sm {{ $company->is_active ? 'btn-danger' : 'btn-success' }}">
                        <i class="fas {{ $company->is_active ? 'fa-ban' : 'fa-check' }} mr-1"></i>
                        {{ $company->is_active ? 'Deactivate' : 'Activate' }}
                    </button>
                </form>
                <form action="{{ route('superadmin.companies.login-as', $company) }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-dark">
                        <i class="fas fa-sign-in-alt mr-1"></i>Login as Admin
                    </button>
                </form>
            </div>
        </div>

        <div class="card card-info card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-chart-bar mr-2"></i>Stats</h3>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-3">
                    <span><i class="fas fa-users text-primary mr-2"></i>Total Clients</span>
                    <strong>{{ $company->clients_count }}</strong>
                </div>
                <div class="d-flex justify-content-between mb-3">
                    <span><i class="fas fa-user-tie text-info mr-2"></i>Total Users</span>
                    <strong>{{ $company->users_count }}</strong>
                </div>
                @if($company->max_clients > 0)
                <div class="d-flex justify-content-between mb-2">
                    <span>Client Capacity</span>
                    <strong>{{ $company->clients_count }} / {{ $company->max_clients }}</strong>
                </div>
                <div class="progress" style="height: 8px;">
                    <div class="progress-bar {{ ($company->clients_count / max($company->max_clients, 1)) > 0.9 ? 'bg-danger' : 'bg-success' }}" style="width: {{ min(($company->clients_count / max($company->max_clients, 1)) * 100, 100) }}%"></div>
                </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card card-success card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-user-shield mr-2"></i>Admin Users</h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-hover">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($admins as $admin)
                        <tr>
                            <td>{{ $admin->name }}</td>
                            <td>{{ $admin->email }}</td>
                            <td>
                                @if($admin->is_active)
                                    <span class="badge badge-success">Active</span>
                                @else
                                    <span class="badge badge-danger">Inactive</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="text-center text-muted py-3">No admin users found</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card card-warning card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-sitemap mr-2"></i>Menu Permissions</h3>
            </div>
            <div class="card-body p-0" style="max-height: 500px; overflow-y: auto;">
                @php
                    $companyMenus = $company->allowed_menus ?? [];
                    $hasNoRestriction = empty($companyMenus);
                @endphp
                @foreach($menuStructure as $parentKey => $group)
                <div class="border-bottom">
                    <div class="d-flex align-items-center px-3 py-2 bg-light">
                        <i class="{{ $group['icon'] }} mr-2 text-muted"></i>
                        <strong class="small">{{ $group['label'] }}</strong>
                        @if(count($group['items']) > 0)
                            @php
                                $itemKeys = array_keys($group['items']);
                                $allowedCount = $hasNoRestriction ? count($itemKeys) : count(array_intersect($itemKeys, $companyMenus));
                            @endphp
                            <span class="badge {{ $allowedCount === count($itemKeys) ? 'badge-success' : ($allowedCount > 0 ? 'badge-warning' : 'badge-danger') }} ml-auto">{{ $allowedCount }}/{{ count($itemKeys) }}</span>
                        @else
                            @if($hasNoRestriction || in_array($parentKey, $companyMenus))
                                <span class="badge badge-success ml-auto"><i class="fas fa-check"></i></span>
                            @else
                                <span class="badge badge-danger ml-auto"><i class="fas fa-times"></i></span>
                            @endif
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

{{-- ─── Enabled Features Row ─────────────────────────────────────────────── --}}
<div class="row mt-3">
    <div class="col-md-12">
        <div class="card card-secondary card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-puzzle-piece mr-2"></i>Enabled Features / Modules</h3>
                <div class="card-tools">
                    @php
                        $allFeatureLabels = \App\Models\Company::$allFeatures;
                        $totalFeatureCount = count($allFeatureLabels);
                        $companyEnabledFeatures = $company->enabled_features ?? [];
                        $hasAllEnabled = empty($companyEnabledFeatures);
                        $enabledCount = $hasAllEnabled ? $totalFeatureCount : count($companyEnabledFeatures);
                    @endphp
                    <span class="badge {{ $enabledCount === $totalFeatureCount ? 'badge-success' : 'badge-info' }} py-1 px-2">
                        {{ $enabledCount }} / {{ $totalFeatureCount }} Enabled
                    </span>
                    <a href="{{ route('superadmin.companies.edit', $company) }}" class="btn btn-sm btn-tool" title="Edit Features">
                        <i class="fas fa-edit"></i>
                    </a>
                </div>
            </div>
            <div class="card-body">
                @php
                    $featureGroups = \App\Models\Company::$featureGroups;
                @endphp
                <div class="row">
                    @foreach($featureGroups as $groupName => $groupKeys)
                    <div class="col-md-4 col-sm-6 mb-3">
                        <div class="card card-outline card-light mb-0 h-100">
                            <div class="card-header py-1 px-2 bg-light">
                                @php
                                    $groupEnabled = 0;
                                    foreach ($groupKeys as $gk) {
                                        if ($hasAllEnabled || in_array($gk, $companyEnabledFeatures)) $groupEnabled++;
                                    }
                                @endphp
                                <h6 class="card-title mb-0 d-flex justify-content-between align-items-center">
                                    <span class="text-uppercase text-muted" style="font-size:0.72rem;letter-spacing:0.05em;">
                                        <i class="fas fa-layer-group mr-1"></i>{{ $groupName }}
                                    </span>
                                    <span class="badge {{ $groupEnabled === count($groupKeys) ? 'badge-success' : ($groupEnabled > 0 ? 'badge-warning' : 'badge-danger') }}">
                                        {{ $groupEnabled }}/{{ count($groupKeys) }}
                                    </span>
                                </h6>
                            </div>
                            <div class="card-body py-2 px-2">
                                @foreach($groupKeys as $key)
                                @if(isset($allFeatureLabels[$key]))
                                @php $isEnabled = $hasAllEnabled || in_array($key, $companyEnabledFeatures); @endphp
                                <div class="d-flex align-items-start mb-1">
                                    <i class="fas fa-{{ $isEnabled ? 'check-circle text-success' : 'times-circle text-muted' }} mr-2 mt-1" style="font-size:0.8rem;flex-shrink:0;"></i>
                                    <span class="small {{ $isEnabled ? '' : 'text-muted' }}" style="font-size:0.82rem;line-height:1.3;">
                                        {{ $allFeatureLabels[$key] }}
                                    </span>
                                </div>
                                @endif
                                @endforeach
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
