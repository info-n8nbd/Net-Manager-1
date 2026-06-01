@extends('layouts.admin')

@section('title', 'Edit Fiber Out Record')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="#">Inventory</a></li>
<li class="breadcrumb-item"><a href="{{ route('admin.inventory.fiber.index') }}">Fiber Stock</a></li>
<li class="breadcrumb-item active">Edit Fiber Out</li>
@endsection

@section('content')
<div class="card card-warning card-outline">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-edit mr-2"></i>Edit Fiber Out Record</h3>
    </div>
    <form method="POST" action="{{ route('admin.inventory.fiber.out.update', $history->id) }}">
        @csrf
        @method('PUT')

        @if($errors->any())
            <div class="alert alert-danger m-3">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Fiber Stock <span class="text-danger">*</span></label>
                        <select name="fiber_stock_id" class="form-control" required>
                            @foreach($stocks as $stock)
                                <option value="{{ $stock->id }}" {{ old('fiber_stock_id', $history->fiber_stock_id) == $stock->id ? 'selected' : '' }}>
                                    {{ $stock->fiber_code }}{{ $stock->brand_name ? ' - '.$stock->brand_name : '' }}{{ $stock->core ? ' ('.$stock->core.' Core)' : '' }} - {{ number_format($stock->remaining_meter) }}m remaining
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Out Meter <span class="text-danger">*</span></label>
                        <input type="number" name="out_meter" class="form-control" value="{{ old('out_meter', $history->meter) }}" step="0.01" min="0.01" required>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Out Date <span class="text-danger">*</span></label>
                        <input type="date" name="out_date" class="form-control" value="{{ old('out_date', $history->created_at ? $history->created_at->format('Y-m-d') : '') }}" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Reason <span class="text-danger">*</span></label>
                        <input type="text" name="reason" class="form-control" value="{{ old('reason', $reason) }}" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group" style="position: relative;">
                        <label><i class="fas fa-user mr-1"></i> Client Name</label>
                        <input type="text" id="fiberEditClientSearch" class="form-control" placeholder="Search by Client Name or PPPoE ID..." autocomplete="off" value="{{ $selectedClient ? $selectedClient->name . ' (' . ($selectedClient->username ?? '') . ')' : '' }}">
                        <input type="hidden" name="client_id" id="fiberEditClientId" value="{{ old('client_id', $client_id) }}">
                        <div id="fiberEditClientResults" class="list-group position-absolute w-100" style="z-index:1050; max-height:200px; overflow-y:auto; display:none;"></div>
                        <small class="text-muted" id="fiberEditClientInfo">
                            @if($selectedClient)
                                <i class="fas fa-check-circle text-success mr-1"></i> {{ $selectedClient->name }}
                                @if($selectedClient->username) | PPPoE: <code>{{ $selectedClient->username }}</code>@endif
                            @endif
                        </small>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Technician</label>
                        <select name="technician_id" class="form-control">
                            <option value="">-- Select Technician --</option>
                            @foreach($technicians as $tech)
                                <option value="{{ $tech->id }}" {{ old('technician_id', $technician_id) == $tech->id ? 'selected' : '' }}>{{ $tech->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Note</label>
                        <textarea name="note" class="form-control" rows="2">{{ old('note', $history->note ?? '') }}</textarea>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-warning"><i class="fas fa-save mr-1"></i> Update Fiber Out</button>
            <a href="{{ route('admin.inventory.fiber.out_history') }}" class="btn btn-secondary ml-2">Cancel</a>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
var editSearchTimer = null;
$('#fiberEditClientSearch').on('input', function() {
    var q = $(this).val().trim();
    var $results = $('#fiberEditClientResults');
    $('#fiberEditClientId').val('');
    $('#fiberEditClientInfo').html('');

    clearTimeout(editSearchTimer);
    if (q.length < 2) {
        $results.hide().html('');
        return;
    }

    editSearchTimer = setTimeout(function() {
        $.get('{{ route("admin.inventory.fiber.search-clients") }}', { q: q }, function(data) {
            if (data.length === 0) {
                $results.html('<div class="list-group-item text-muted text-sm">No clients found</div>').show();
                return;
            }
            var html = '';
            data.forEach(function(c) {
                html += '<a href="#" class="list-group-item list-group-item-action py-1 px-2 fiber-edit-client-result" '
                    + 'data-id="' + c.id + '" '
                    + 'data-name="' + (c.name || '') + '" '
                    + 'data-username="' + (c.username || '') + '" '
                    + 'data-client-id="' + (c.client_id || '') + '" '
                    + 'data-phone="' + (c.phone || '') + '">'
                    + '<strong>' + (c.name || '-') + '</strong>'
                    + (c.username ? ' <code class="ml-1">' + c.username + '</code>' : '')
                    + (c.client_id ? ' <small class="text-muted ml-1">[' + c.client_id + ']</small>' : '')
                    + (c.phone ? ' <small class="text-muted ml-1"><i class="fas fa-phone"></i> ' + c.phone + '</small>' : '')
                    + '</a>';
            });
            $results.html(html).show();
        });
    }, 300);
});

$(document).on('click', '.fiber-edit-client-result', function(e) {
    e.preventDefault();
    var id = $(this).data('id');
    var name = $(this).data('name');
    var username = $(this).data('username');
    var clientId = $(this).data('client-id');
    var phone = $(this).data('phone');

    var displayVal = name + (username ? ' (' + username + ')' : '');
    $('#fiberEditClientSearch').val(displayVal);
    $('#fiberEditClientId').val(id);
    var info = '<i class="fas fa-check-circle text-success mr-1"></i> ' + name;
    if (username) info += ' | PPPoE: <code>' + username + '</code>';
    if (clientId) info += ' | ID: ' + clientId;
    if (phone) info += ' | Phone: ' + phone;
    $('#fiberEditClientInfo').html(info);
    $('#fiberEditClientResults').hide().html('');
});

$(document).on('click', function(e) {
    if (!$(e.target).closest('#fiberEditClientSearch, #fiberEditClientResults').length) {
        $('#fiberEditClientResults').hide();
    }
});
</script>
@endpush
