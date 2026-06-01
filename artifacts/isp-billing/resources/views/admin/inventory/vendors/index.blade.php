@extends('layouts.admin')

@section('title', 'Vendors - Inventory')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="#">Inventory</a></li>
<li class="breadcrumb-item active">Vendors</li>
@endsection

@section('content')
<div class="card card-primary card-outline">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-list mr-2"></i>Vendor List</h3>
        <div class="card-tools">
            @if($__canAction('inventory', 'add_vendor'))
            <a href="{{ route('admin.inventory.vendors.create') }}" class="btn btn-primary btn-sm">
                <i class="fas fa-plus mr-1"></i> Add Vendor
            </a>
            @endif
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="vendorsTable" class="table table-bordered table-hover table-striped" style="width:100%">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Company</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Balance</th>
                        <th>Status</th>
                        <th width="160">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($vendors as $vendor)
                    <tr>
                        <td><strong>{{ $vendor->name }}</strong></td>
                        <td>{{ $vendor->company_name ?? '-' }}</td>
                        <td>
                            @if($vendor->phone)
                                <i class="fas fa-phone-alt text-muted mr-1"></i>{{ $vendor->phone }}
                            @else
                                -
                            @endif
                        </td>
                        <td>
                            @if($vendor->email)
                                <i class="fas fa-envelope text-muted mr-1"></i>{{ $vendor->email }}
                            @else
                                -
                            @endif
                        </td>
                        <td class="text-right">
                            <span class="{{ ($vendor->balance ?? 0) > 0 ? 'text-danger font-weight-bold' : 'text-success' }}">
                                {{ number_format($vendor->balance ?? 0, 2) }}
                            </span>
                        </td>
                        <td class="text-center">
                            @if($vendor->is_active ?? true)
                                <span class="badge badge-success"><i class="fas fa-check-circle mr-1"></i>Active</span>
                            @else
                                <span class="badge badge-danger"><i class="fas fa-times-circle mr-1"></i>Inactive</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <a href="{{ route('admin.store.vendor-history', $vendor->id) }}" class="btn btn-info btn-sm" title="View History">
                                <i class="fas fa-history"></i>
                            </a>
                            @if($__canAction('inventory', 'edit_vendor'))
                            <a href="{{ route('admin.inventory.vendors.edit', $vendor->id) }}" class="btn btn-warning btn-sm" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            @endif
                            @if($__canAction('inventory', 'delete_vendor'))
                            <form method="POST" action="{{ route('admin.inventory.vendors.destroy', $vendor->id) }}" style="display:inline" onsubmit="return confirm('Are you sure you want to delete this vendor?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    if ($('#vendorsTable tbody tr td[colspan]').length === 0) { $('#vendorsTable').DataTable({
        pageLength: 25,
        order: [[0, 'asc']],
        language: {
            search: "<i class='fas fa-search'></i> Search:",
            lengthMenu: "Show _MENU_ entries",
            info: "Showing _START_ to _END_ of _TOTAL_ vendors",
            emptyTable: "No vendors found"
        }
    }); }
});
</script>
@endpush
