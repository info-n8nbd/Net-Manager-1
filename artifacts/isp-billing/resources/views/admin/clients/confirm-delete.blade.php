@extends('layouts.admin')

@section('title', 'Delete Client')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.clients.index') }}">Clients</a></li>
<li class="breadcrumb-item"><a href="{{ route('admin.clients.show', $client) }}">{{ $client->client_id }}</a></li>
<li class="breadcrumb-item active">Delete</li>
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-6">
            <div class="card card-danger card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-exclamation-triangle text-danger mr-2"></i>Delete Client</h3>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <strong>Warning!</strong> You are about to delete this client. The client and their data will be preserved and can be recovered from <strong>Client Recovery</strong>.
                    </div>
                    
                    <table class="table table-sm table-bordered">
                        <tr><th width="35%">Client ID</th><td><strong>{{ $client->client_id }}</strong></td></tr>
                        <tr><th>Name</th><td>{{ $client->name }}</td></tr>
                        <tr><th>Phone</th><td>{{ $client->phone }}</td></tr>
                        <tr><th>Username</th><td>{{ $client->username }}</td></tr>
                        <tr><th>Zone</th><td>{{ $client->zone->name ?? 'N/A' }}</td></tr>
                        <tr><th>Subzone</th><td>{{ $client->area->name ?? 'N/A' }}</td></tr>
                        <tr><th>TJ</th><td>{{ $client->box->name ?? 'N/A' }}</td></tr>
                        <tr><th>Package</th><td>{{ $client->package->name ?? 'N/A' }}</td></tr>
                        <tr><th>Status</th><td>
                            <span class="badge badge-{{ $client->status == 'active' ? 'success' : ($client->status == 'inactive' ? 'danger' : 'secondary') }}">
                                {{ ucfirst($client->status) }}
                            </span>
                        </td></tr>
                        <tr><th>Total Bills</th><td><span class="badge badge-info">{{ $bills->count() }}</span></td></tr>
                        <tr><th>Total Payments</th><td><span class="badge badge-success">{{ $payments->count() }}</span></td></tr>
                    </table>

                    @if(isset($clientEquipment) && count($clientEquipment) > 0)
                    <div class="card card-warning card-outline mt-3 mb-3">
                        <div class="card-header bg-warning">
                            <h3 class="card-title"><i class="fas fa-exclamation-triangle mr-2"></i>Equipment Assigned to This Client</h3>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-sm table-bordered mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th><input type="checkbox" id="selectAllEquip" checked></th>
                                        <th>Type</th>
                                        <th>Equipment</th>
                                        <th>Serial/MAC</th>
                                        <th>Condition</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($clientEquipment as $idx => $eq)
                                    <tr>
                                        <td><input type="checkbox" class="equip-check" data-index="{{ $idx }}" checked></td>
                                        <td><span class="badge badge-{{ $eq['type'] === 'ONU' ? 'primary' : ($eq['type'] === 'Fiber/Cable' ? 'success' : 'secondary') }}">{{ $eq['type'] }}</span></td>
                                        <td>{{ $eq['name'] }}</td>
                                        <td><small>{{ $eq['serial'] !== 'N/A' ? $eq['serial'] : ($eq['mac'] !== 'N/A' ? $eq['mac'] : '—') }}</small></td>
                                        <td>
                                            <select class="form-control form-control-sm equip-condition" data-index="{{ $idx }}" style="width: 100px;">
                                                <option value="good">Good</option>
                                                <option value="damaged">Damaged</option>
                                                <option value="faulty">Faulty</option>
                                                <option value="unknown" selected>Unknown</option>
                                            </select>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="card-footer">
                            <button type="button" class="btn btn-warning btn-block" id="btnReturnEquipment" onclick="returnEquipmentToInventory()">
                                <i class="fas fa-undo-alt mr-2"></i>Return Selected Equipment to Inventory
                            </button>
                            <div id="returnResult" class="mt-2"></div>
                        </div>
                    </div>
                    @else
                    <div class="alert alert-info mt-3">
                        <i class="fas fa-info-circle mr-2"></i>No equipment found assigned to this client.
                    </div>
                    @endif

                    <div class="mt-4">
                        <form action="{{ route('admin.clients.destroy', $client) }}" method="POST" id="deleteForm" onsubmit="return confirmDeleteWithCheck();">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="equipment_returned" id="equipmentReturned" value="0">
                            <div class="form-group mb-3">
                                <label for="delete_reason"><strong>Reason for deletion</strong></label>
                                <select class="form-control mb-2" id="deleteReasonPreset" onchange="if(this.value){document.getElementById('delete_reason').value=this.value;}">
                                    <option value="">-- Select reason --</option>
                                    @foreach($deleteReasons as $r)
                                    <option value="{{ $r }}">{{ $r }}</option>
                                    @endforeach
                                </select>
                                <input type="text" name="delete_reason" id="delete_reason" class="form-control" placeholder="Or type a reason..." value="Deleted by admin">
                            </div>
                            <button type="submit" class="btn btn-danger btn-lg btn-block">
                                <i class="fas fa-trash mr-2"></i>Delete Client
                            </button>
                        </form>
                        <a href="{{ route('admin.clients.show', $client) }}" class="btn btn-secondary btn-block mt-2">
                            <i class="fas fa-arrow-left mr-2"></i>Cancel
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-info">
                    <h3 class="card-title text-white"><i class="fas fa-file-invoice mr-2"></i>Client Bills ({{ $bills->count() }})</h3>
                </div>
                <div class="card-body p-0" style="max-height: 400px; overflow-y: auto;">
                    @if($bills->count() > 0)
                    <table class="table table-sm table-striped mb-0">
                        <thead class="bg-light sticky-top">
                            <tr>
                                <th>Bill No</th>
                                <th>Month</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($bills as $bill)
                            <tr>
                                <td>{{ $bill->bill_no }}</td>
                                <td>{{ $bill->billing_month }}/{{ $bill->billing_year }}</td>
                                <td>৳{{ number_format($bill->total_amount ?? 0) }}</td>
                                <td>
                                    <span class="badge badge-{{ $bill->status == 'paid' ? 'success' : ($bill->status == 'partial' ? 'warning' : 'danger') }}">
                                        {{ ucfirst($bill->status) }}
                                    </span>
                                </td>
                                <td>
                                    <form action="{{ route('admin.clients.delete-bill', [$client, $bill]) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this bill?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-xs" title="Delete Bill">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @else
                    <div class="text-center py-4 text-muted">
                        <i class="fas fa-file-invoice fa-2x mb-2"></i>
                        <p>No bills found for this client</p>
                    </div>
                    @endif
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header bg-success">
                    <h3 class="card-title text-white"><i class="fas fa-money-bill-wave mr-2"></i>Client Payments ({{ $payments->count() }})</h3>
                </div>
                <div class="card-body p-0" style="max-height: 300px; overflow-y: auto;">
                    @if($payments->count() > 0)
                    <table class="table table-sm table-striped mb-0">
                        <thead class="bg-light sticky-top">
                            <tr>
                                <th>Payment No</th>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Method</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($payments as $payment)
                            <tr>
                                <td>{{ $payment->payment_no }}</td>
                                <td>{{ $payment->payment_date->format('d-m-Y') }}</td>
                                <td class="text-success">৳{{ number_format($payment->amount ?? 0) }}</td>
                                <td>{{ $payment->paymentMethod->name ?? 'Cash' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @else
                    <div class="text-center py-4 text-muted">
                        <i class="fas fa-money-bill-wave fa-2x mb-2"></i>
                        <p>No payments found for this client</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
var clientEquipmentData = @json($clientEquipment ?? []);
var equipmentReturned = false;

document.getElementById('selectAllEquip')?.addEventListener('change', function() {
    document.querySelectorAll('.equip-check').forEach(function(cb) { cb.checked = this.checked; }.bind(this));
});

function returnEquipmentToInventory() {
    var selectedEquip = [];
    document.querySelectorAll('.equip-check:checked').forEach(function(cb) {
        var idx = cb.getAttribute('data-index');
        var condition = document.querySelector('.equip-condition[data-index="' + idx + '"]').value;
        var item = Object.assign({}, clientEquipmentData[idx]);
        item.condition = condition;
        selectedEquip.push(item);
    });

    if (selectedEquip.length === 0) {
        smartAlert('Please select at least one equipment to return.', 'warning'); return;
    }

    var btn = document.getElementById('btnReturnEquipment');
    smartConfirm({message:'Return '+selectedEquip.length+' equipment(s) to inventory?',confirmText:'Return'}, function() {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Processing...';

        fetch('{{ route("admin.clients.return-equipment", $client) }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ equipment: selectedEquip })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                equipmentReturned = true;
                document.getElementById('equipmentReturned').value = '1';
                document.getElementById('returnResult').innerHTML = '<div class="alert alert-success"><i class="fas fa-check-circle mr-2"></i>' + data.message + '</div>';
                btn.innerHTML = '<i class="fas fa-check mr-2"></i>Equipment Returned';
                btn.classList.remove('btn-warning');
                btn.classList.add('btn-success');
                document.querySelectorAll('.equip-check').forEach(function(cb) { cb.disabled = true; });
                document.querySelectorAll('.equip-condition').forEach(function(sel) { sel.disabled = true; });
            } else {
                document.getElementById('returnResult').innerHTML = '<div class="alert alert-danger"><i class="fas fa-times-circle mr-2"></i>' + (data.error || 'Failed') + '</div>';
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-undo-alt mr-2"></i>Retry Return Equipment';
            }
        })
        .catch(function(err) {
            document.getElementById('returnResult').innerHTML = '<div class="alert alert-danger">Error: ' + err.message + '</div>';
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-undo-alt mr-2"></i>Retry Return Equipment';
        });
    });

function confirmDeleteWithCheck() {
    @if(isset($clientEquipment) && count($clientEquipment) > 0)
    if (!equipmentReturned) {
        smartConfirm({title:'Equipment Not Returned',message:'Equipment NOT returned to inventory. Delete client anyway?',isDelete:true,confirmText:'Delete Anyway'},function(){var fm=document.getElementById('deleteClientForm');if(fm){fm.removeAttribute('onsubmit');fm.submit();}}); return false;
    }
    @endif
    smartConfirm({title:'Delete Client',message:'Delete this client? You can recover them later from Client Recovery.',isDelete:true},function(){var fm=document.getElementById('deleteClientForm');if(fm){fm.removeAttribute('onsubmit');fm.submit();}}); return false;
}
</script>
@endpush
