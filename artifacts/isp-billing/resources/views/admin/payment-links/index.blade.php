@extends('layouts.admin')

@section('title', 'Payment Links')

@section('content')
<div class="row">
    <div class="col-md-3 col-sm-6">
        <div class="info-box">
            <span class="info-box-icon bg-info"><i class="fas fa-link"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Total Links</span>
                <span class="info-box-number">{{ $stats['total'] ?? 0 }}</span>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="info-box">
            <span class="info-box-icon bg-success"><i class="fas fa-check-circle"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Active</span>
                <span class="info-box-number">{{ $stats['active'] ?? 0 }}</span>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="info-box">
            <span class="info-box-icon bg-primary"><i class="fas fa-money-bill-wave"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Paid</span>
                <span class="info-box-number">{{ $stats['paid'] ?? 0 }}</span>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="info-box">
            <span class="info-box-icon bg-warning"><i class="fas fa-clock"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Expired</span>
                <span class="info-box-number">{{ $stats['expired'] ?? 0 }}</span>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-cog mr-2"></i>Payment Link Settings</h3>
            </div>
            <form action="{{ route('admin.payment-links.settings') }}" method="POST">
                @csrf
                <div class="card-body">
                    <div class="form-group">
                        <label>Payment Link Base URL</label>
                        <input type="url" name="payment_link_base_url" class="form-control" value="{{ $settings['payment_link_base_url'] ?? '' }}" placeholder="https://yourdomain.com">
                        <small class="text-muted">Set your domain URL. Leave empty to use system default. Example: https://billing.yourisp.com</small>
                    </div>
                    <div class="form-group">
                        <label>Link Expiry (Hours)</label>
                        <input type="number" name="payment_link_expiry_hours" class="form-control" value="{{ $settings['payment_link_expiry_hours'] ?? 72 }}" min="1" max="720">
                        <small class="text-muted">How long payment links stay active (default: 72 hours / 3 days)</small>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save mr-1"></i> Save Settings</button>
                </div>
            </form>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card card-outline card-success">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-magic mr-2"></i>Auto Generate Payment Link</h3>
            </div>
            <form action="{{ route('admin.payment-links.auto-generate') }}" method="POST">
                @csrf
                <div class="card-body">
                    @include('admin.partials.client-search', [
                        'inputId' => 'autoGenClientSearch',
                        'hiddenId' => 'autoGenClientSearchId',
                        'label' => 'Select Client',
                    ])
                    <div id="clientDueInfo" class="alert alert-info" style="display:none;">
                        <strong>Due Amount:</strong> <span id="dueAmountDisplay">৳0</span>
                        <br><strong>Phone:</strong> <span id="phoneDisplay">-</span>
                    </div>
                    <div class="custom-control custom-checkbox mt-2">
                        <input type="checkbox" name="send_sms" class="custom-control-input" id="autoSendSms" value="1">
                        <label class="custom-control-label" for="autoSendSms">Send SMS with payment link</label>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-success btn-sm"><i class="fas fa-magic mr-1"></i> Generate Link (Auto Due Amount)</button>
                </div>
            </form>
        </div>

        <div class="card card-outline card-warning">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-bolt mr-2"></i>Bulk Auto Generate</h3>
            </div>
            <form action="{{ route('admin.payment-links.bulk-auto-generate') }}" method="POST" onsubmit="return confirm('This will generate payment links for ALL clients with dues. Continue?')">
                @csrf
                <div class="card-body">
                    <div class="form-group">
                        <label>Minimum Due Amount (৳)</label>
                        <input type="number" name="min_due" class="form-control" value="1" min="0" step="1">
                        <small class="text-muted">Generate links for clients with due >= this amount</small>
                    </div>
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" name="send_sms" class="custom-control-input" id="bulkSendSms" value="1">
                        <label class="custom-control-label" for="bulkSendSms">Send SMS to all clients</label>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-warning btn-sm"><i class="fas fa-bolt mr-1"></i> Bulk Generate for All Due Clients</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title"><i class="fas fa-link mr-2"></i>Payment Links</h3>
        <a href="{{ route('admin.payment-links.create') }}" class="btn btn-primary btn-sm">
            <i class="fas fa-plus mr-1"></i> Create Manual Link
        </a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover table-sm">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Client</th>
                        <th>Amount</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th>Link</th>
                        <th>Created</th>
                        <th>Expires</th>
                        <th>Views</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($links as $link)
                    <tr>
                        <td>{{ $loop->iteration + (($links->currentPage() - 1) * $links->perPage()) }}</td>
                        <td>{{ $link->client->name ?? '-' }} <br><small class="text-muted">{{ $link->client->username ?? '' }}</small></td>
                        <td>৳{{ number_format($link->amount, 2) }}</td>
                        <td>{{ Str::limit($link->description, 30) }}</td>
                        <td>
                            @if($link->status == 'active')
                                <span class="badge badge-success">Active</span>
                            @elseif($link->status == 'paid')
                                <span class="badge badge-info">Paid</span>
                            @elseif($link->status == 'expired')
                                <span class="badge badge-warning">Expired</span>
                            @elseif($link->status == 'cancelled')
                                <span class="badge badge-danger">Cancelled</span>
                            @else
                                <span class="badge badge-secondary">{{ ucfirst($link->status) }}</span>
                            @endif
                        </td>
                        <td>
                            @if($link->status == 'active')
                                @php
                                    $payUrl = app(\App\Services\PaymentLinkService::class)->getPaymentUrl($link);
                                @endphp
                                <button class="btn btn-xs btn-outline-primary copy-link-btn" data-url="{{ $payUrl }}" title="Copy Link">
                                    <i class="fas fa-copy"></i> Copy
                                </button>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>{{ $link->created_at->format('d-m-y H:i') }}</td>
                        <td>{{ $link->expires_at ? $link->expires_at->format('d-m-y H:i') : '-' }}</td>
                        <td>{{ $link->view_count ?? 0 }}</td>
                        <td>
                            @if($link->status == 'active')
                                <form action="{{ route('admin.payment-links.resend', $link) }}" method="POST" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="via" value="sms">
                                    <button type="submit" class="btn btn-info btn-xs" title="Send via SMS"><i class="fas fa-sms"></i></button>
                                </form>
                                <form action="{{ route('admin.payment-links.cancel', $link) }}" method="POST" class="d-inline" onsubmit="return confirm('Cancel this link?')">
                                    @csrf
                                    <button type="submit" class="btn btn-danger btn-xs" title="Cancel"><i class="fas fa-times"></i></button>
                                </form>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="10" class="text-center text-muted">No payment links found</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">
            <div class="d-flex flex-wrap justify-content-between align-items-center mt-2 mb-1">
    <div class="d-flex align-items-center">
        <label class="small text-muted mb-0 mr-2" style="white-space:nowrap;">SHOW</label>
        <select class="form-control form-control-sm bulk-show-entries" style="width:80px;">
            <option value="20" {{ request('per_page',20)==20?'selected':'' }}>20</option>
            <option value="50" {{ request('per_page',20)==50?'selected':'' }}>50</option>
            <option value="100" {{ request('per_page',20)==100?'selected':'' }}>100</option>
            <option value="200" {{ request('per_page',20)==200?'selected':'' }}>200</option>
        </select>
        <label class="small text-muted mb-0 ml-2" style="white-space:nowrap;">ENTRIES</label>
    </div>
    <div>
        {{ $links->appends(request()->query())->links() }}
    </div>
</div>
<script>
(function(){
    var sel = document.querySelector('.bulk-show-entries');
    if(sel){ sel.addEventListener('change',function(){
        var p=new URLSearchParams(window.location.search);
        p.set('per_page',this.value); p.delete('page');
        window.location=window.location.pathname+'?'+p.toString();
    }); }
})();
</script>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.copy-link-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var url = this.getAttribute('data-url');
            navigator.clipboard.writeText(url).then(function() {
                btn.innerHTML = '<i class="fas fa-check"></i> Copied!';
                setTimeout(function() {
                    btn.innerHTML = '<i class="fas fa-copy"></i> Copy';
                }, 2000);
            });
        });
    });
});
</script>
@endpush
