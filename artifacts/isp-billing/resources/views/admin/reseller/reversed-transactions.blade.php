@extends('layouts.admin')

@section('title', 'Reversed Transactions — ' . $reseller->name)
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.reseller.index') }}">Resellers</a></li>
<li class="breadcrumb-item"><a href="{{ route('admin.reseller.show', $reseller) }}">{{ $reseller->name }}</a></li>
<li class="breadcrumb-item"><a href="{{ route('admin.reseller.client-transactions', $reseller) }}">Client Transactions</a></li>
<li class="breadcrumb-item active">Reversed Transactions</li>
@endsection

@section('content')
<div class="card card-outline card-primary">
    <div class="card-header py-2">
        <h3 class="card-title">
            <i class="fas fa-user-slash mr-2"></i>Reversed Transactions — {{ $reseller->name }}
        </h3>
        <div class="card-tools">
            <a href="{{ route('admin.reseller.client-transactions', $reseller) }}" class="btn btn-secondary btn-xs">
                <i class="fas fa-arrow-left mr-1"></i>Back to Transactions
            </a>
        </div>
    </div>

    <div class="card-body py-2">
        <form method="GET" class="d-flex align-items-end flex-wrap" style="gap:8px;">
            <div>
                <label class="small font-weight-bold text-muted mb-0" style="font-size:10px;">TYPE</label>
                <select name="type" class="form-control form-control-sm" style="width:180px;">
                    <option value="">All Reversal Types</option>
                    <option value="client_left_reversal" {{ request('type') == 'client_left_reversal' ? 'selected' : '' }}>Client Left Reversal</option>
                    <option value="recharge_reversal" {{ request('type') == 'recharge_reversal' ? 'selected' : '' }}>Correction Reversal</option>
                    <option value="recharge_refund" {{ request('type') == 'recharge_refund' ? 'selected' : '' }}>Refund</option>
                </select>
            </div>
            <div>
                <label class="small font-weight-bold text-muted mb-0" style="font-size:10px;">SEARCH</label>
                <input type="text" name="search" class="form-control form-control-sm" style="width:180px;" value="{{ request('search') }}" placeholder="Username/Name...">
            </div>
            <button type="submit" class="btn btn-info btn-sm"><i class="fas fa-filter mr-1"></i>Filter</button>
            <a href="{{ route('admin.reseller.reversed-transactions', $reseller) }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-undo mr-1"></i>Reset</a>
        </form>
    </div>

    @php
        $totalClientLeft = $reversals->where('transaction_type', 'client_left_reversal')->count();
        $totalRefund     = $reversals->where('transaction_type', 'recharge_refund')->count();
        $totalCorrection = $reversals->where('transaction_type', 'recharge_reversal')->count();
        $totalAmount     = $reversals->sum(fn($r) => abs($r->recharge_amount));
    @endphp
    <div class="card-body py-2">
        <div class="row">
            <div class="col-sm-3">
                <div class="info-box mb-2" style="min-height:60px;">
                    <span class="info-box-icon bg-purple" style="font-size:22px;line-height:60px;width:60px;"><i class="fas fa-user-slash"></i></span>
                    <div class="info-box-content" style="padding:8px 10px;">
                        <span class="info-box-text" style="font-size:11px;">Client Left</span>
                        <span class="info-box-number" style="font-size:18px;">{{ $totalClientLeft }}</span>
                    </div>
                </div>
            </div>
            <div class="col-sm-3">
                <div class="info-box mb-2" style="min-height:60px;">
                    <span class="info-box-icon bg-warning" style="font-size:22px;line-height:60px;width:60px;"><i class="fas fa-edit"></i></span>
                    <div class="info-box-content" style="padding:8px 10px;">
                        <span class="info-box-text" style="font-size:11px;">Correction</span>
                        <span class="info-box-number" style="font-size:18px;">{{ $totalCorrection }}</span>
                    </div>
                </div>
            </div>
            <div class="col-sm-3">
                <div class="info-box mb-2" style="min-height:60px;">
                    <span class="info-box-icon bg-secondary" style="font-size:22px;line-height:60px;width:60px;"><i class="fas fa-undo-alt"></i></span>
                    <div class="info-box-content" style="padding:8px 10px;">
                        <span class="info-box-text" style="font-size:11px;">Refunds</span>
                        <span class="info-box-number" style="font-size:18px;">{{ $totalRefund }}</span>
                    </div>
                </div>
            </div>
            <div class="col-sm-3">
                <div class="info-box mb-2" style="min-height:60px;">
                    <span class="info-box-icon bg-success" style="font-size:22px;line-height:60px;width:60px;"><i class="fas fa-taka-sign"></i></span>
                    <div class="info-box-content" style="padding:8px 10px;">
                        <span class="info-box-text" style="font-size:11px;">Total Reversed</span>
                        <span class="info-box-number" style="font-size:15px;">৳{{ number_format($totalAmount, 2) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card-body table-responsive p-0">
        <table class="table table-bordered table-hover table-sm mb-0">
            <thead>
                <tr style="background:#2c3e50;color:#fff;">
                    <th>#</th>
                    <th>Client</th>
                    <th>Package</th>
                    <th class="text-right">Daily Rate</th>
                    <th>From</th>
                    <th>To</th>
                    <th class="text-center">Days</th>
                    <th class="text-right">Refunded Amount</th>
                    <th class="text-center">Type</th>
                    <th>Reversed On</th>
                    <th>By</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                @forelse($reversals as $rev)
                @php
                    $isClientLeft = $rev->transaction_type === 'client_left_reversal';
                    $isCorrection = $rev->transaction_type === 'recharge_reversal';
                    $isRefund     = $rev->transaction_type === 'recharge_refund';
                @endphp
                <tr>
                    <td style="font-size:12px;">{{ $rev->id }}</td>
                    <td>
                        <strong>{{ $rev->client->username ?? 'N/A' }}</strong>
                        @if($rev->client)
                        <br><small class="text-muted">{{ $rev->client->name }}</small>
                        @endif
                    </td>
                    <td style="font-size:12px;">{{ $rev->package_name }}</td>
                    <td class="text-right" style="font-size:12px;">{{ number_format($rev->daily_rate ?? 0, 2) }}</td>
                    <td style="font-size:12px;">{{ $rev->start_date ? $rev->start_date->format('d/m/Y') : '-' }}</td>
                    <td style="font-size:12px;">{{ $rev->end_date ? $rev->end_date->format('d/m/Y') : '-' }}</td>
                    <td class="text-center">{{ $rev->days }}</td>
                    <td class="text-right font-weight-bold text-success">
                        ৳{{ number_format(abs($rev->recharge_amount), 2) }}
                    </td>
                    <td class="text-center">
                        @if($isClientLeft)
                            <span class="badge px-2 py-1" style="background:#8e44ad;color:#fff;font-size:10px;"><i class="fas fa-user-slash mr-1"></i>CLIENT LEFT</span>
                        @elseif($isCorrection)
                            <span class="badge px-2 py-1" style="background:#e67e22;color:#fff;font-size:10px;"><i class="fas fa-edit mr-1"></i>CORRECTION</span>
                        @elseif($isRefund)
                            <span class="badge px-2 py-1" style="background:#6c757d;color:#fff;font-size:10px;"><i class="fas fa-undo-alt mr-1"></i>REFUND</span>
                        @endif
                    </td>
                    <td style="font-size:11px;white-space:nowrap;">{{ $rev->created_at->format('d M Y h:i A') }}</td>
                    <td style="font-size:12px;">{{ $rev->rechargedByUser->name ?? 'System' }}</td>
                    <td><small class="text-muted">{{ $rev->remarks ?? '' }}</small></td>
                </tr>
                @empty
                <tr><td colspan="12" class="text-center text-muted py-4">No reversed transactions found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($reversals->hasPages())
    <div class="card-footer clearfix">
        {{ $reversals->links() }}
    </div>
    @endif
</div>
@endsection
