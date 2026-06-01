@extends('layouts.admin')

@section('title', 'Payment Correction - ' . $payment->payment_no)

@section('content')
        <div class="row">
            <div class="col-md-6">
                <div class="card card-success card-outline">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-money-bill-wave mr-2"></i>Payment Details</h3>
                        @if($payment->is_reversed)
                        <span class="badge badge-danger float-right">REVERSED</span>
                        @elseif($payment->is_duplicate)
                        <span class="badge badge-warning float-right">DUPLICATE</span>
                        @endif
                    </div>
                    <div class="card-body">
                        <table class="table table-sm table-bordered">
                            <tr>
                                <th width="40%">Payment No</th>
                                <td><strong>{{ $payment->payment_no }}</strong></td>
                            </tr>
                            <tr>
                                <th>Client</th>
                                <td>
                                    <a href="{{ ($payment->client_id ? route('admin.clients.show', $payment->client_id) : '#') }}">
                                        {{ $payment->client->name ?? 'N/A' }}
                                    </a>
                                </td>
                            </tr>
                            <tr>
                                <th>Amount</th>
                                <td class="text-success"><strong>৳{{ number_format($payment->amount, 2) }}</strong></td>
                            </tr>
                            <tr>
                                <th>Bill</th>
                                <td>
                                    @if($payment->bill)
                                    <a href="{{ route('admin.corrections.bill', $payment->bill_id) }}">
                                        {{ $payment->bill->bill_no }}
                                    </a>
                                    @else
                                    <span class="text-muted">No bill linked</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Transaction ID</th>
                                <td>{{ $payment->transaction_id ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Payment Date</th>
                                <td>{{ $payment->payment_date ? $payment->payment_date->format('d-m-Y') : 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Collected By</th>
                                <td>{{ $payment->collectedBy->name ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Remarks</th>
                                <td>{{ $payment->remarks ?? '-' }}</td>
                            </tr>
                        </table>
                    </div>
                </div>

                @if(count($duplicates) > 0)
                <div class="card card-warning">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-exclamation-triangle mr-2"></i>Potential Duplicates Detected</h3>
                    </div>
                    <div class="card-body">
                        @if(isset($duplicates['transaction_id']))
                        <div class="alert alert-warning">
                            <strong>Same Transaction ID:</strong> Payment #{{ $duplicates['transaction_id']->payment_no }} 
                            (৳{{ number_format($duplicates['transaction_id']->amount, 2) }}) 
                            on {{ $duplicates['transaction_id']->payment_date->format('d-m-Y') }}
                        </div>
                        @endif
                        @if(isset($duplicates['recent_same_amount']))
                        <div class="alert alert-info">
                            <strong>Recent Same Amount:</strong> Payment #{{ $duplicates['recent_same_amount']->payment_no }} 
                            (৳{{ number_format($duplicates['recent_same_amount']->amount, 2) }}) 
                            within the last 5 minutes
                        </div>
                        @endif
                    </div>
                </div>
                @endif

                @if($corrections->count() > 0)
                <div class="card card-warning card-outline">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-history mr-2"></i>Correction History</h3>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>Correction #</th>
                                    <th>Type</th>
                                    <th>Amount</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($corrections as $correction)
                                <tr>
                                    <td><span class="badge badge-info">{{ $correction->correction_no }}</span></td>
                                    <td>{{ ucfirst($correction->correction_type) }}</td>
                                    <td class="text-danger">-৳{{ number_format($correction->original_amount, 2) }}</td>
                                    <td>{{ $correction->created_at->format('d-m-y H:i') }}</td>
                                </tr>
                                <tr>
                                    <td colspan="4" class="small text-muted bg-light">
                                        <strong>Reason:</strong> {{ $correction->reason }}
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif
            </div>

            <div class="col-md-6">
                @if(!$payment->is_reversed && !$payment->is_duplicate)
                <div class="card card-danger">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-undo mr-2"></i>Reverse Payment</h3>
                    </div>
                    <form action="{{ route('admin.corrections.payment.reverse', $payment) }}" method="POST">
                        @csrf
                        <div class="card-body">
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle mr-2"></i>
                                <strong>Warning:</strong> This will reverse the payment and add the amount back to the client's due. The bill status will be recalculated.
                            </div>
                            
                            <div class="form-group">
                                <label>Reason for Reversal <span class="text-danger">*</span></label>
                                <textarea name="reason" class="form-control" rows="3" required minlength="10"
                                          placeholder="Provide a detailed reason for reversal (min 10 characters)..."></textarea>
                                <small class="text-muted">This reason will be recorded in the audit log.</small>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-danger" onclick="return confirm('Are you absolutely sure you want to REVERSE this payment? This will add ৳{{ number_format($payment->amount, 2) }} back to client due.')">
                                <i class="fas fa-undo mr-2"></i>Reverse Payment
                            </button>
                        </div>
                    </form>
                </div>

                <div class="card card-warning">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-copy mr-2"></i>Mark as Duplicate</h3>
                    </div>
                    <form action="{{ route('admin.corrections.payment.mark-duplicate', $payment) }}" method="POST">
                        @csrf
                        <div class="card-body">
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle mr-2"></i>
                                <strong>Note:</strong> Mark this payment as a duplicate of another payment. This will reverse its effect on the client's balance.
                            </div>
                            
                            <div class="form-group">
                                <label>Original Payment ID <span class="text-danger">*</span></label>
                                <select name="original_payment_id" class="form-control" required>
                                    <option value="">-- Select Original Payment --</option>
                                    @foreach(\App\Models\Payment::where('client_id', $payment->client_id)->where('id', '!=', $payment->id)->where('is_reversed', false)->where('is_duplicate', false)->orderBy('created_at', 'desc')->take(20)->get() as $otherPayment)
                                    <option value="{{ $otherPayment->id }}">
                                        {{ $otherPayment->payment_no }} - ৳{{ number_format($otherPayment->amount, 2) }} 
                                        ({{ $otherPayment->payment_date ? $otherPayment->payment_date->format('d-m-Y') : 'N/A' }})
                                    </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Reason <span class="text-danger">*</span></label>
                                <textarea name="reason" class="form-control" rows="3" required minlength="10"
                                          placeholder="Explain why this is a duplicate payment (min 10 characters)..."></textarea>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-warning" onclick="return confirm('Mark this payment as duplicate? This will reverse its effect on the balance.')">
                                <i class="fas fa-copy mr-2"></i>Mark as Duplicate
                            </button>
                        </div>
                    </form>
                </div>
                @else
                <div class="alert alert-danger">
                    <i class="fas fa-ban mr-2"></i>
                    <strong>Payment {{ $payment->is_reversed ? 'Reversed' : 'Marked as Duplicate' }}:</strong> 
                    This payment has already been corrected and no further actions can be taken.
                </div>
                @endif

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-book mr-2"></i>Ledger Entries</h3>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>Entry #</th>
                                    <th>Type</th>
                                    <th>Amount</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($ledgerEntries as $entry)
                                <tr>
                                    <td><small>{{ $entry->entry_no }}</small></td>
                                    <td>
                                        @if($entry->is_reversal)
                                        <span class="badge badge-danger">Reversal</span>
                                        @elseif($entry->entry_type == 'correction')
                                        <span class="badge badge-warning">Correction</span>
                                        @else
                                        <span class="badge badge-success">{{ ucfirst($entry->entry_type) }}</span>
                                        @endif
                                    </td>
                                    <td class="{{ $entry->debit_credit == 'debit' ? 'text-danger' : 'text-success' }}">
                                        {{ $entry->debit_credit == 'debit' ? '+' : '-' }}৳{{ number_format($entry->amount, 2) }}
                                    </td>
                                    <td>{{ $entry->created_at->format('d-m-Y') }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-3">No ledger entries yet</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
@endsection
