@extends('layouts.admin')

@section('title', 'Payment Receipt')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1"><i class="fas fa-receipt me-2"></i>Payment Receipt</h4>
            <p class="text-muted mb-0">Receipt #{{ $payment->receipt_no ?? 'RCP-' . str_pad($payment->id, 6, '0', STR_PAD_LEFT) }}</p>
        </div>
        <div>
            <a href="{{ route('admin.payments.receipt.print', $payment) }}" class="btn btn-primary">
                <i class="fas fa-print me-1"></i>Print A4
            </a>
            <a href="{{ route('admin.payments.receipt.thermal', $payment) }}" class="btn btn-secondary">
                <i class="fas fa-print me-1"></i>Print Thermal
            </a>
            <a href="{{ route('admin.payments.receipt.download', $payment) }}" class="btn btn-success">
                <i class="fas fa-download me-1"></i>Download PDF
            </a>
            <a href="{{ route('admin.payments.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i>Back
            </a>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header text-center bg-primary text-white">
                    <h5 class="mb-0">{{ $settings['name'] }}</h5>
                    <small>{{ $settings['address'] }} | {{ $settings['phone'] }}</small>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <h4 class="text-primary">PAYMENT RECEIPT</h4>
                        <h6 class="text-muted">{{ $payment->receipt_no ?? 'RCP-' . str_pad($payment->id, 6, '0', STR_PAD_LEFT) }}</h6>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="text-primary mb-3">Customer Information</h6>
                                    <p class="mb-1"><strong>Client ID:</strong> {{ $payment->client?->client_id }}</p>
                                    <p class="mb-1"><strong>Name:</strong> {{ $payment->client?->name }}</p>
                                    <p class="mb-1"><strong>Phone:</strong> {{ $payment->client?->phone }}</p>
                                    <p class="mb-1"><strong>Zone:</strong> {{ $payment->client?->zone?->name ?? '-' }}</p>
                                    <p class="mb-0"><strong>Package:</strong> {{ $payment->client?->package?->name ?? '-' }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="text-primary mb-3">Payment Details</h6>
                                    <p class="mb-1"><strong>Date:</strong> {{ $payment->payment_date->format('d-m-Y') }}</p>
                                    <p class="mb-1"><strong>Time:</strong> {{ $payment->created_at->format('h:i A') }}</p>
                                    <p class="mb-1"><strong>Method:</strong> {{ $payment->paymentMethod->name ?? 'Cash' }}</p>
                                    <p class="mb-1"><strong>Collected By:</strong> {{ $payment->collectedBy?->name ?? 'Office' }}</p>
                                    <p class="mb-0"><strong>For Month:</strong> {{ $payment->for_month ?? $payment->payment_date->format('F Y') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="text-center py-4 bg-primary text-white rounded mb-4">
                        <p class="mb-1">Amount Paid</p>
                        <h2 class="mb-1">৳ {{ number_format((float)$payment->amount, 0) }}</h2>
                        <small class="fst-italic">{{ ucwords(\App\Helpers\NumberToWords::convert((float)$payment->amount)) }} Taka Only</small>
                        <div class="mt-2">
                            <span class="badge bg-success px-3 py-2">{{ strtoupper($payment->status ?? 'APPROVED') }}</span>
                        </div>
                    </div>

                    <div class="row text-center">
                        <div class="col-6">
                            <div class="p-3 bg-warning bg-opacity-25 rounded">
                                <small class="text-muted">Remaining Due</small>
                                <h4 class="text-warning mb-0">৳ {{ number_format((float)($payment->client?->due_amount ?? 0), 0) }}</h4>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 bg-success bg-opacity-25 rounded">
                                <small class="text-muted">Advance Balance</small>
                                <h4 class="text-success mb-0">৳ {{ number_format((float)($payment->client?->advance_amount ?? 0), 0) }}</h4>
                            </div>
                        </div>
                    </div>

                    @if($payment->notes)
                    <div class="mt-3 p-3 bg-light rounded">
                        <strong>Notes:</strong> {{ $payment->notes }}
                    </div>
                    @endif

                    <div class="text-center mt-4 pt-3 border-top">
                        <p class="text-muted mb-1">{{ $settings['footer_text'] }}</p>
                        <small class="text-muted">Generated on {{ now()->format('d-m-y h:i:s A') }} (BDT)</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
