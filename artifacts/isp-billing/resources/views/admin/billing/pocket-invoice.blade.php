@extends('layouts.admin')

@section('title', 'Pocket Invoice')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="#">Billing</a></li>
<li class="breadcrumb-item active">Pocket Invoice</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card" id="pocket-invoice">
            <div class="card-body text-center" style="font-size: 12px; max-width: 300px; margin: 0 auto;">
                <h5 class="mb-1">{{ config('app.name', 'ISP Billing') }}</h5>
                <hr class="my-1">
                <p class="mb-0"><strong>Invoice #:</strong> {{ $bill->bill_no ?? 'N/A' }}</p>
                <p class="mb-0"><strong>Date:</strong> {{ $bill->bill_date ? $bill->bill_date->format('d-m-Y') : 'N/A' }}</p>
                <p class="mb-0"><strong>Due Date:</strong> {{ $bill->due_date ? $bill->due_date->format('d-m-Y') : 'N/A' }}</p>
                <hr class="my-1">
                <p class="mb-0"><strong>Client:</strong> {{ $bill->client->name ?? 'N/A' }}</p>
                <p class="mb-0"><strong>ID:</strong> {{ $bill->client->client_id ?? 'N/A' }}</p>
                <p class="mb-0"><strong>Package:</strong> {{ $bill->client->package->name ?? 'N/A' }}</p>
                <hr class="my-1">
                <table class="table table-sm table-borderless mb-0" style="font-size: 12px;">
                    <tr>
                        <td class="text-left">Bill Amount</td>
                        <td class="text-right">৳{{ number_format($bill->amount ?? 0, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="text-left">Paid</td>
                        <td class="text-right">৳{{ number_format($bill->paid_amount ?? 0, 2) }}</td>
                    </tr>
                    <tr class="font-weight-bold">
                        <td class="text-left">Due</td>
                        <td class="text-right">৳{{ number_format(max(0, $bill->amount - ($bill->paid_amount ?? 0)), 2) }}</td>
                    </tr>
                </table>
                <hr class="my-1">
                <p class="mb-0"><strong>Status:</strong>
                    @if($bill->status === 'paid')
                        <span class="badge badge-success">Paid</span>
                    @elseif($bill->status === 'partial')
                        <span class="badge badge-warning">Partial</span>
                    @else
                        <span class="badge badge-danger">Unpaid</span>
                    @endif
                </p>
                <p class="mt-2 mb-0" style="font-size: 10px;">Thank you for your payment</p>
            </div>
        </div>
        <div class="text-center mt-2">
            <button onclick="window.print()" class="btn btn-primary btn-sm"><i class="fas fa-print"></i> Print</button>
        </div>
    </div>
</div>
@endsection
