@extends('layouts.reseller')
@section('title', 'Package Price Management')
@section('breadcrumb')<li class="breadcrumb-item active">Package Prices</li>@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h3 class="card-title mb-0"><i class="fas fa-tags mr-2"></i>My Package Prices</h3>
                <a href="{{ route('reseller.packages') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-eye mr-1"></i> View Summary
                </a>
            </div>
            <div class="card-body p-0">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible m-3 mb-0">
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                        <i class="fas fa-check-circle mr-1"></i> {{ session('success') }}
                    </div>
                @endif
                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible m-3 mb-0">
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                        <i class="fas fa-exclamation-circle mr-1"></i> {{ session('error') }}
                    </div>
                @endif

                @if($packages->count() > 0)
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover mb-0">
                        <thead class="thead-dark">
                            <tr>
                                <th>#</th>
                                <th>Package Name</th>
                                <th>Speed</th>
                                <th class="text-right">Your Cost (ISP)</th>
                                <th class="text-right">Client Price</th>
                                <th class="text-right">Profit</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($packages as $index => $rp)
                            @php
                                $clientPrice = $rp->selling_price ?? $rp->package->price ?? 0;
                                $resellerCost = $rp->reseller_price ?? 0;
                                $profit = $clientPrice - $resellerCost;
                            @endphp
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>
                                    <strong>{{ $rp->package->name }}</strong>
                                    @if($rp->package->description)
                                        <br><small class="text-muted">{{ Str::limit($rp->package->description, 60) }}</small>
                                    @endif
                                </td>
                                <td>{{ $rp->package->download_speed ?? $rp->package->speed ?? 'N/A' }} Mbps</td>
                                <td class="text-right text-primary font-weight-bold">৳{{ number_format($resellerCost) }}</td>
                                <td class="text-right font-weight-bold">৳{{ number_format($clientPrice) }}</td>
                                <td class="text-right font-weight-bold {{ $profit >= 0 ? 'text-success' : 'text-danger' }}">
                                    ৳{{ number_format($profit) }}
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-{{ $rp->is_active ? 'success' : 'secondary' }}">
                                        {{ $rp->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-xs btn-warning" data-toggle="modal" data-target="#editPriceModal{{ $rp->id }}">
                                        <i class="fas fa-edit"></i> Edit Price
                                    </button>
                                </td>
                            </tr>

                            {{-- Edit Selling Price Modal --}}
                            <div class="modal fade" id="editPriceModal{{ $rp->id }}" tabindex="-1">
                                <div class="modal-dialog modal-sm">
                                    <form action="{{ route('reseller.packages.update', $rp->id) }}" method="POST">
                                        @csrf @method('PUT')
                                        <div class="modal-content">
                                            <div class="modal-header bg-warning">
                                                <h5 class="modal-title">
                                                    <i class="fas fa-tag mr-1"></i> {{ $rp->package->name }}
                                                </h5>
                                                <button type="button" class="close" data-dismiss="modal">&times;</button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="form-group mb-2">
                                                    <label class="small text-muted">Your Cost (ISP Price)</label>
                                                    <div class="input-group input-group-sm">
                                                        <div class="input-group-prepend"><span class="input-group-text">৳</span></div>
                                                        <input type="text" class="form-control" value="{{ number_format($resellerCost) }}" readonly disabled>
                                                    </div>
                                                </div>
                                                <div class="form-group mb-0">
                                                    <label>Client Price (৳) <span class="text-danger">*</span></label>
                                                    <div class="input-group">
                                                        <div class="input-group-prepend"><span class="input-group-text">৳</span></div>
                                                        <input type="number" name="selling_price" class="form-control" value="{{ $clientPrice }}" min="{{ $resellerCost }}" step="1" required>
                                                    </div>
                                                    <small class="text-muted">Min: ৳{{ number_format($resellerCost) }}</small>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-warning btn-sm">
                                                    <i class="fas fa-save mr-1"></i> Save Price
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="p-3">
                    <div class="alert alert-info mb-0">
                        <i class="fas fa-info-circle mr-1"></i>
                        <strong>Note:</strong> Package assignment is managed by your ISP admin. You can only update the <strong>client selling price</strong> for each package. Contact admin to add or remove packages.
                    </div>
                </div>
                @else
                <div class="text-center py-5">
                    <i class="fas fa-box-open fa-4x text-muted mb-3 d-block"></i>
                    <h5 class="text-muted">No Packages Assigned</h5>
                    <p class="text-muted">Contact your administrator to get packages assigned to your account.</p>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
