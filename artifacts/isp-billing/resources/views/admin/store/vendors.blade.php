@extends('layouts.admin')
@section('title', 'Vendors')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.store.purchases') }}">Store</a></li>
<li class="breadcrumb-item active">Vendors</li>
@endsection

@section('content')
<div class="row mb-3">
    <div class="col-12">
        <div class="input-group">
            <div class="input-group-prepend">
                <span class="input-group-text"><i class="fas fa-search"></i></span>
            </div>
            <input type="text" id="vendorSearch" class="form-control" placeholder="Search vendors by name, company, phone, email...">
            <div class="input-group-append">
                <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addVendorModal">
                    <i class="fas fa-plus"></i> Add Vendor
                </button>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h3 class="card-title"><i class="fas fa-truck mr-2"></i>Vendor Management</h3>
        <span class="badge badge-primary">{{ $vendors->total() }} Vendors</span>
    </div>
    <div class="card-body p-0">
        <div class="d-none d-md-block">
            <table class="table table-hover table-striped mb-0">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Company</th>
                        <th>Contact Person</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th class="text-right">Balance</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody id="vendorTableBody">
                    @forelse($vendors as $vendor)
                    <tr class="vendor-row" data-search="{{ strtolower($vendor->name . ' ' . $vendor->company . ' ' . $vendor->contact_person . ' ' . $vendor->phone . ' ' . $vendor->email) }}">
                        <td><strong>{{ $vendor->name }}</strong></td>
                        <td>{{ $vendor->company ?? '-' }}</td>
                        <td>{{ $vendor->contact_person ?? '-' }}</td>
                        <td>{{ $vendor->phone ?? '-' }}</td>
                        <td>{{ $vendor->email ?? '-' }}</td>
                        <td class="text-right">
                            <span class="{{ ($vendor->balance ?? 0) > 0 ? 'text-danger' : (($vendor->balance ?? 0) < 0 ? 'text-success' : '') }}">
                                ৳{{ number_format(abs($vendor->balance ?? 0), 2) }}
                                @if(($vendor->balance ?? 0) > 0) <small>(Due)</small> @elseif(($vendor->balance ?? 0) < 0) <small>(Advance)</small> @endif
                            </span>
                        </td>
                        <td class="text-center">
                            <span class="badge badge-{{ $vendor->is_active ? 'success' : 'danger' }}">{{ $vendor->is_active ? 'Active' : 'Inactive' }}</span>
                        </td>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-info" data-toggle="modal" data-target="#editVendorModal{{ $vendor->id }}" title="Edit"><i class="fas fa-edit"></i></button>
                                <a href="{{ route('admin.purchase.vendors.ledger', $vendor) }}" class="btn btn-primary" title="Ledger"><i class="fas fa-book"></i></a>
                                <form action="{{ route('admin.purchase.vendors.destroy', $vendor) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this vendor?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm" title="Delete"><i class="fas fa-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-4">
                            <i class="fas fa-truck fa-3x text-muted mb-3 d-block"></i>
                            <p class="text-muted">No vendors found. Add your first vendor to get started.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="d-md-none p-3">
            @forelse($vendors as $vendor)
            <div class="card mb-2 vendor-row" data-search="{{ strtolower($vendor->name . ' ' . $vendor->company . ' ' . $vendor->contact_person . ' ' . $vendor->phone . ' ' . $vendor->email) }}">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <h6 class="mb-0"><strong>{{ $vendor->name }}</strong></h6>
                            @if($vendor->company)<small class="text-muted">{{ $vendor->company }}</small>@endif
                        </div>
                        <span class="badge badge-{{ $vendor->is_active ? 'success' : 'danger' }}">{{ $vendor->is_active ? 'Active' : 'Inactive' }}</span>
                    </div>
                    @if($vendor->contact_person)<div class="small"><i class="fas fa-user-tie mr-1"></i> {{ $vendor->contact_person }}</div>@endif
                    @if($vendor->phone)<div class="small"><i class="fas fa-phone mr-1"></i> {{ $vendor->phone }}</div>@endif
                    @if($vendor->email)<div class="small"><i class="fas fa-envelope mr-1"></i> {{ $vendor->email }}</div>@endif
                    <div class="small mt-1">
                        <strong>Balance:</strong>
                        <span class="{{ ($vendor->balance ?? 0) > 0 ? 'text-danger' : (($vendor->balance ?? 0) < 0 ? 'text-success' : '') }}">
                            ৳{{ number_format(abs($vendor->balance ?? 0), 2) }}
                            @if(($vendor->balance ?? 0) > 0) (Due) @elseif(($vendor->balance ?? 0) < 0) (Advance) @endif
                        </span>
                    </div>
                    <div class="mt-2 btn-group btn-group-sm">
                        <button type="button" class="btn btn-info" data-toggle="modal" data-target="#editVendorModal{{ $vendor->id }}"><i class="fas fa-edit"></i> Edit</button>
                        <a href="{{ route('admin.purchase.vendors.ledger', $vendor) }}" class="btn btn-primary"><i class="fas fa-book"></i> Ledger</a>
                        <form action="{{ route('admin.purchase.vendors.destroy', $vendor) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this vendor?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
                        </form>
                    </div>
                </div>
            </div>
            @empty
            <div class="text-center py-4">
                <i class="fas fa-truck fa-3x text-muted mb-3 d-block"></i>
                <p class="text-muted">No vendors found.</p>
            </div>
            @endforelse
        </div>
    </div>
    <div class="card-footer">
        {{ $vendors->links() }}
    </div>
</div>

<div class="modal fade" id="addVendorModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.purchase.vendors.store') }}" method="POST">
                @csrf
                <div class="modal-header bg-primary">
                    <h4 class="modal-title text-white"><i class="fas fa-plus mr-2"></i>Add New Vendor</h4>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required placeholder="Vendor name">
                    </div>
                    <div class="form-group">
                        <label>Company</label>
                        <input type="text" name="company" class="form-control" placeholder="Company name">
                    </div>
                    <div class="form-group">
                        <label>Contact Person</label>
                        <input type="text" name="contact_person" class="form-control" placeholder="Contact person name">
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" name="email" class="form-control" placeholder="email@example.com">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Phone</label>
                                <input type="text" name="phone" class="form-control" placeholder="Phone number">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Address</label>
                        <textarea name="address" class="form-control" rows="2" placeholder="Full address"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Opening Balance</label>
                        <input type="number" name="opening_balance" class="form-control" step="0.01" value="0" placeholder="Opening balance amount">
                        <small class="text-muted">Positive = amount owed to vendor, Negative = advance paid</small>
                    </div>
                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="is_active_new" name="is_active" checked>
                            <label class="custom-control-label" for="is_active_new">Active</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Save Vendor</button>
                </div>
            </form>
        </div>
    </div>
</div>

@foreach($vendors as $vendor)
<div class="modal fade" id="editVendorModal{{ $vendor->id }}">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.purchase.vendors.update', $vendor) }}" method="POST">
                @csrf @method('PUT')
                <div class="modal-header bg-info">
                    <h4 class="modal-title text-white"><i class="fas fa-edit mr-2"></i>Edit Vendor</h4>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="{{ $vendor->name }}" required>
                    </div>
                    <div class="form-group">
                        <label>Company</label>
                        <input type="text" name="company" class="form-control" value="{{ $vendor->company }}">
                    </div>
                    <div class="form-group">
                        <label>Contact Person</label>
                        <input type="text" name="contact_person" class="form-control" value="{{ $vendor->contact_person }}">
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" name="email" class="form-control" value="{{ $vendor->email }}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Phone</label>
                                <input type="text" name="phone" class="form-control" value="{{ $vendor->phone }}">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Address</label>
                        <textarea name="address" class="form-control" rows="2">{{ $vendor->address }}</textarea>
                    </div>
                    <div class="form-group">
                        <label>Opening Balance</label>
                        <input type="number" name="opening_balance" class="form-control" step="0.01" value="{{ $vendor->opening_balance ?? 0 }}">
                    </div>
                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="is_active_{{ $vendor->id }}" name="is_active" {{ $vendor->is_active ? 'checked' : '' }}>
                            <label class="custom-control-label" for="is_active_{{ $vendor->id }}">Active</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info"><i class="fas fa-save mr-1"></i> Update</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach

@endsection

@push('scripts')
<script>
document.getElementById('vendorSearch').addEventListener('input', function() {
    var search = this.value.toLowerCase();
    document.querySelectorAll('.vendor-row').forEach(function(row) {
        var data = row.getAttribute('data-search');
        row.style.display = data.indexOf(search) > -1 ? '' : 'none';
    });
});
</script>
@endpush
