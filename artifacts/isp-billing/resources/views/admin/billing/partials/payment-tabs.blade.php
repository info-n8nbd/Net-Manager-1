@php $currentRoute = Route::currentRouteName(); @endphp
<div style="margin-bottom:0;">
    <ul class="nav payment-page-tabs" style="background:linear-gradient(135deg,#1e3c72,#2a5298);border-radius:6px 6px 0 0;padding:0 10px;margin:0;border:none;">
        <li class="nav-item">
            <a class="nav-link {{ $currentRoute=='admin.payments.index' ? 'pptab-active' : 'pptab-link' }}"
               href="{{ route('admin.payments.index') }}">
                <i class="fas fa-money-bill-wave mr-1"></i>Payments
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $currentRoute=='admin.payments.pending' ? 'pptab-active' : 'pptab-link' }}"
               href="{{ route('admin.payments.pending') }}">
                <i class="fas fa-clock mr-1"></i>Pending Payments
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $currentRoute=='admin.billing.online-payments' ? 'pptab-active' : 'pptab-link' }}"
               href="{{ route('admin.billing.online-payments') }}">
                <i class="fas fa-credit-card mr-1"></i>Online Payments
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $currentRoute=='billing.android-sms.index' ? 'pptab-active' : 'pptab-link' }}"
               href="{{ route('billing.android-sms.index') }}">
                <i class="fas fa-mobile-alt mr-1"></i>Send Money Auto
            </a>
        </li>
    </ul>
</div>
<style>
.pptab-link { color:rgba(255,255,255,0.65)!important; font-weight:600; font-size:13px; padding:10px 16px; border:none; border-bottom:3px solid transparent; background:transparent; transition:all 0.2s; text-decoration:none; display:block; }
.pptab-link:hover { color:#fff!important; border-bottom-color:rgba(255,255,255,0.4); background:rgba(255,255,255,0.07); text-decoration:none; }
.pptab-active { color:#fff!important; font-weight:700; font-size:13px; padding:10px 16px; border:none; border-bottom:3px solid #fff; background:rgba(255,255,255,0.12); display:block; text-decoration:none; }
</style>
