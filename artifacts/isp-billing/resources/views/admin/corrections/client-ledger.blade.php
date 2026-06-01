@extends('layouts.admin')

@section('title', 'Client Ledger - ' . $client->name)

@section('content')
        <div class="row">
            <div class="col-md-4">
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-user mr-2"></i>{{ $client->name }}</h3>
                    </div>
                    <div class="card-body">
                        <p class="mb-1"><strong>Client ID:</strong> {{ $client->client_id }}</p>
                        <p class="mb-1"><strong>Phone:</strong> {{ $client->phone }}</p>
                        <p class="mb-1"><strong>Package:</strong> {{ $client->package->name ?? 'N/A' }}</p>
                        <hr>
                        <div class="text-center">
                            <h4 class="{{ $currentBalance > 0 ? 'text-danger' : ($currentBalance < 0 ? 'text-success' : 'text-muted') }}">
                                Current Balance: ৳{{ number_format($currentBalance, 2) }}
                            </h4>
                            <small class="text-muted">
                                {{ $currentBalance > 0 ? 'Due to ISP' : ($currentBalance < 0 ? 'Advance Payment' : 'No Balance') }}
                            </small>
                        </div>
                    </div>
                    <div class="card-footer">
                        <form action="{{ route('admin.corrections.sync-ledger', $client) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-info btn-block" onclick="return confirm('Sync all bills and payments to ledger?')">
                                <i class="fas fa-sync mr-2"></i>Sync to Ledger
                            </button>
                        </form>
                    </div>
                </div>

                <div class="card card-info card-outline">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-info-circle mr-2"></i>Ledger Info</h3>
                    </div>
                    <div class="card-body">
                        <p class="small text-muted mb-2">
                            <i class="fas fa-plus-circle text-danger mr-1"></i>
                            <strong>Debit (Red):</strong> Increases client due (bills, corrections adding due)
                        </p>
                        <p class="small text-muted mb-2">
                            <i class="fas fa-minus-circle text-success mr-1"></i>
                            <strong>Credit (Green):</strong> Decreases client due (payments, bill cancellations)
                        </p>
                        <p class="small text-muted mb-0">
                            <i class="fas fa-undo text-warning mr-1"></i>
                            <strong>Reversals:</strong> Undo previous entries without deleting
                        </p>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-list mr-2"></i>Ledger Entries</h3>
                        <span class="badge badge-primary float-right">{{ $ledgerEntries->count() }} entries</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm table-striped mb-0">
                                <thead class="bg-dark text-white">
                                    <tr>
                                        <th>Date</th>
                                        <th>Entry #</th>
                                        <th>Type</th>
                                        <th>Description</th>
                                        <th class="text-right">Debit</th>
                                        <th class="text-right">Credit</th>
                                        <th class="text-right">Balance</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($ledgerEntries as $entry)
                                    <tr class="{{ $entry->is_reversal ? 'table-warning' : '' }}">
                                        <td>{{ $entry->entry_date->format('d-m-Y') }}</td>
                                        <td><small class="text-muted">{{ $entry->entry_no }}</small></td>
                                        <td>
                                            @if($entry->is_reversal)
                                            <span class="badge badge-warning">Reversal</span>
                                            @elseif($entry->entry_type == 'bill')
                                            <span class="badge badge-primary">Bill</span>
                                            @elseif($entry->entry_type == 'payment')
                                            <span class="badge badge-success">Payment</span>
                                            @elseif($entry->entry_type == 'correction')
                                            <span class="badge badge-info">Correction</span>
                                            @elseif($entry->entry_type == 'adjustment')
                                            <span class="badge badge-secondary">Adjustment</span>
                                            @else
                                            <span class="badge badge-dark">{{ ucfirst($entry->entry_type) }}</span>
                                            @endif
                                        </td>
                                        <td><small>{{ \Illuminate\Support\Str::limit($entry->description, 50) }}</small></td>
                                        <td class="text-right text-danger">
                                            {{ $entry->debit_credit == 'debit' ? '৳' . number_format($entry->amount, 2) : '-' }}
                                        </td>
                                        <td class="text-right text-success">
                                            {{ $entry->debit_credit == 'credit' ? '৳' . number_format($entry->amount, 2) : '-' }}
                                        </td>
                                        <td class="text-right {{ $entry->running_balance > 0 ? 'text-danger' : 'text-success' }}">
                                            <strong>৳{{ number_format($entry->running_balance, 2) }}</strong>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-4">
                                            <i class="fas fa-book text-muted fa-2x mb-2"></i>
                                            <p class="text-muted mb-0">No ledger entries yet. Click "Sync to Ledger" to import existing bills and payments.</p>
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                                @if($ledgerEntries->count() > 0)
                                <tfoot class="bg-light">
                                    <tr>
                                        <th colspan="4" class="text-right">Totals:</th>
                                        <th class="text-right text-danger">
                                            ৳{{ number_format($ledgerEntries->where('debit_credit', 'debit')->sum('amount'), 2) }}
                                        </th>
                                        <th class="text-right text-success">
                                            ৳{{ number_format($ledgerEntries->where('debit_credit', 'credit')->sum('amount'), 2) }}
                                        </th>
                                        <th class="text-right {{ $currentBalance > 0 ? 'text-danger' : 'text-success' }}">
                                            <strong>৳{{ number_format($currentBalance, 2) }}</strong>
                                        </th>
                                    </tr>
                                </tfoot>
                                @endif
                            </table>
                        </div>
                    </div>
                </div>

                @if($billCorrections->count() > 0 || $paymentCorrections->count() > 0)
                <div class="row">
                    @if($billCorrections->count() > 0)
                    <div class="col-md-6">
                        <div class="card card-warning card-outline">
                            <div class="card-header">
                                <h3 class="card-title"><i class="fas fa-file-invoice mr-2"></i>Bill Corrections</h3>
                            </div>
                            <div class="card-body p-0">
                                <table class="table table-sm mb-0">
                                    <thead>
                                        <tr>
                                            <th>Bill</th>
                                            <th>Type</th>
                                            <th>Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($billCorrections->take(5) as $correction)
                                        <tr>
                                            <td>
                                                <a href="{{ route('admin.corrections.bill', $correction->bill_id) }}">
                                                    {{ $correction->bill->bill_no ?? 'N/A' }}
                                                </a>
                                            </td>
                                            <td>{{ ucfirst(str_replace('_', ' ', $correction->correction_type)) }}</td>
                                            <td class="{{ $correction->adjustment_amount >= 0 ? 'text-danger' : 'text-success' }}">
                                                {{ $correction->adjustment_amount >= 0 ? '+' : '' }}৳{{ number_format($correction->adjustment_amount, 2) }}
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    @endif

                    @if($paymentCorrections->count() > 0)
                    <div class="col-md-6">
                        <div class="card card-danger card-outline">
                            <div class="card-header">
                                <h3 class="card-title"><i class="fas fa-money-bill mr-2"></i>Payment Corrections</h3>
                            </div>
                            <div class="card-body p-0">
                                <table class="table table-sm mb-0">
                                    <thead>
                                        <tr>
                                            <th>Payment</th>
                                            <th>Type</th>
                                            <th>Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($paymentCorrections->take(5) as $correction)
                                        <tr>
                                            <td>
                                                <a href="{{ route('admin.corrections.payment', $correction->payment_id) }}">
                                                    {{ $correction->payment->payment_no ?? 'N/A' }}
                                                </a>
                                            </td>
                                            <td>{{ ucfirst($correction->correction_type) }}</td>
                                            <td class="text-danger">-৳{{ number_format($correction->original_amount, 2) }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
                @endif
            </div>
        </div>
@endsection
