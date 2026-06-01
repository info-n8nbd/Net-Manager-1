<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ClientController;
use App\Http\Controllers\Admin\BillingController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\NetworkController;
use App\Http\Controllers\Admin\AccountController;
use App\Http\Controllers\Admin\StoreController;
use App\Http\Controllers\Admin\SupportController;
use App\Http\Controllers\Admin\ResellerController;
use App\Http\Controllers\Admin\HrmController;
use App\Http\Controllers\Admin\SmsController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\ClientHistoryController;
use App\Http\Controllers\Admin\AdvancedBillingController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\ImpersonateController;
use App\Http\Controllers\Admin\MikrotikSyncController;
use App\Http\Controllers\Admin\OltController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\TechnicianController;
use App\Http\Controllers\Admin\StaffDuesController;
use App\Http\Controllers\Admin\AdminOverrideController;
use App\Http\Controllers\Admin\SuperAdminController;
use App\Http\Controllers\Admin\RadiusSettingsController;
use App\Http\Controllers\Admin\AgentController;
use App\Http\Controllers\Admin\FirewallController;
use App\Http\Controllers\Admin\IpPoolController;
use App\Http\Controllers\Admin\BandwidthReportController;
use App\Http\Controllers\Admin\DeviceLogController;
use App\Http\Controllers\Admin\ConnectivityTestController;
use App\Http\Controllers\Admin\Inventory\ProductController;
use App\Http\Controllers\Admin\Inventory\ProductCategoryController;
use App\Http\Controllers\Admin\Inventory\VendorController as InventoryVendorController;
use App\Http\Controllers\Admin\Inventory\PurchaseController as InventoryPurchaseController;
use App\Http\Controllers\Admin\Inventory\StockController;
use App\Http\Controllers\Admin\Inventory\DamageController;
use App\Http\Controllers\Admin\Inventory\FiberStockController;
use App\Http\Controllers\Admin\Inventory\InstrumentController;
use App\Http\Controllers\Admin\Inventory\SerialSearchController;
use App\Http\Controllers\Admin\Inventory\SerialAssignController;
use App\Http\Controllers\Admin\MacBindingController;
use App\Http\Controllers\Client\ClientDashboardController;
use App\Http\Controllers\Admin\MenuPermissionController;


Route::get('/health', function () { return response('OK', 200); });
Route::get('/', function () { 
    return response('<html><head><meta http-equiv="refresh" content="0;url=/login"></head><body>Redirecting...</body></html>', 200)
        ->header('Content-Type', 'text/html');
});

Route::get('/apply', [\App\Http\Controllers\Public\NewClientApplicationController::class, 'create'])->name('public.application.create');
Route::post('/apply', [\App\Http\Controllers\Public\NewClientApplicationController::class, 'store'])->name('public.application.store');
Route::get('/apply/success/{applicationNumber}', [\App\Http\Controllers\Public\NewClientApplicationController::class, 'success'])->name('public.application.success');

// Public API for location cascading (no auth required)
Route::get('/public-data/divisions', function() {
    return \App\Models\District::where('is_active', true)->whereNotNull('division')->where('division', '!=', '')->distinct()->orderBy('division')->pluck('division');
})->name('public.api.divisions');
Route::get('/public-data/districts-by-division/{division}', function($division) {
    return \App\Models\District::where('is_active', true)->where('division', $division)->orderBy('name')->get(['id', 'name']);
})->name('public.api.districts-by-division');
Route::get('/public-data/upazilas-by-district/{district}', function(\App\Models\District $district) {
    return $district->upazilas()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
})->name('public.api.upazilas-by-district');
Route::get('/public-data/zones-by-upazila/{upazila}', function(\App\Models\Upazila $upazila) {
    return \App\Models\Zone::where('upazila_id', $upazila->id)->where('is_active', true)->orderBy('name')->get(['id', 'name']);
})->name('public.api.zones-by-upazila');
Route::get('/public-data/areas-by-zone/{zone}', function(\App\Models\Zone $zone) {
    return \App\Services\ClientFilterService::getAreasForZone($zone->id);
})->name('public.api.areas-by-zone');
Route::get('/public-data/boxes-by-area/{area}', function(\App\Models\Area $area) {
    return \App\Services\ClientFilterService::getBoxesForArea($area->id);
})->name('public.api.boxes-by-area');

// Client Self-Service Portal (no login required)
Route::get('/payment', [\App\Http\Controllers\ClientSelfServiceController::class, 'index'])->name('self-service.index');
Route::post('/payment', [\App\Http\Controllers\ClientSelfServiceController::class, 'lookup'])->name('self-service.lookup');
Route::get('/payment/{code}', [\App\Http\Controllers\ClientSelfServiceController::class, 'status'])->name('self-service.status');
Route::post('/api/self-service/lookup', [\App\Http\Controllers\ClientSelfServiceController::class, 'apiLookup'])->name('api.self-service.lookup');

// Self-Service Payment Routes (no login required)
Route::post('/payment/pay', [\App\Http\Controllers\ClientSelfServiceController::class, 'initiatePayment'])->name('self-service.payment.initiate');
Route::get('/payment/callback', [\App\Http\Controllers\ClientSelfServiceController::class, 'paymentCallback'])->name('self-service.payment.callback');
Route::post('/payment/ssl/success', [\App\Http\Controllers\ClientSelfServiceController::class, 'sslSuccess'])->name('self-service.ssl.success');
Route::post('/payment/ssl/fail', [\App\Http\Controllers\ClientSelfServiceController::class, 'sslFail'])->name('self-service.ssl.fail');
Route::post('/payment/ssl/cancel', [\App\Http\Controllers\ClientSelfServiceController::class, 'sslCancel'])->name('self-service.ssl.cancel');
Route::get('/payment/demo/{transaction_id}', [\App\Http\Controllers\ClientSelfServiceController::class, 'demoPayment'])->name('self-service.payment.demo');
Route::post('/payment/demo/{transaction_id}', [\App\Http\Controllers\ClientSelfServiceController::class, 'confirmDemoPayment'])->name('self-service.payment.demo.confirm');

Route::post('/webhook/voice/status', [\App\Http\Controllers\Admin\VoiceCallController::class, 'statusCallback'])->name('webhook.voice.status');

Route::get('/voice-audio/{file}', function (string $file) {
    if (!preg_match('/^[a-zA-Z0-9_\-]+\.(mp3|wav)$/', $file)) abort(404);
    $path = storage_path('app/public/voice-audio/' . $file);
    if (!file_exists($path)) abort(404);
    $mime = str_ends_with($file, '.wav') ? 'audio/wav' : 'audio/mpeg';
    return response()->file($path, ['Content-Type' => $mime, 'Cache-Control' => 'public, max-age=86400']);
})->name('voice.audio.serve');

Route::get('/deploy-serve/{file}', function(string $file){if(request('t')!=='isp-deploy-2024')abort(403);$file=basename($file);$p=public_path('deploy/'.$file);if(!file_exists($p))abort(404);return response(file_get_contents($p))->header('Content-Type','text/plain');})->where('file','[A-Za-z0-9._-]+');

Route::post('/webhook/payment/bkash', [\App\Http\Controllers\WebhookPaymentController::class, 'handleBkash'])->name('webhook.payment.bkash');
Route::post('/webhook/payment/nagad', [\App\Http\Controllers\WebhookPaymentController::class, 'handleNagad'])->name('webhook.payment.nagad');
Route::post('/webhook/payment/rocket', [\App\Http\Controllers\WebhookPaymentController::class, 'handleRocket'])->name('webhook.payment.rocket');
Route::post('/webhook/payment/{gateway}', [\App\Http\Controllers\WebhookPaymentController::class, 'handleGeneric'])->name('webhook.payment.generic');
Route::get('/webhook/payment/{any}', function () {
    return response()->json(['status' => 'ok', 'message' => 'Webhook endpoint active. Use POST to send payment data.'], 200);
})->where('any', '.*');

Route::post('/payment/initiate', [\App\Http\Controllers\PaymentGatewayController::class, 'initiate'])->name('payment.initiate');
Route::get('/payment/bkash/callback', [\App\Http\Controllers\PaymentGatewayController::class, 'bkashCallback'])->name('payment.bkash.callback');
Route::get('/payment/nagad/callback', [\App\Http\Controllers\PaymentGatewayController::class, 'nagadCallback'])->name('payment.nagad.callback');
Route::post('/payment/ssl/success', [\App\Http\Controllers\PaymentGatewayController::class, 'sslSuccess'])->name('payment.ssl.success');
Route::post('/payment/ssl/fail', [\App\Http\Controllers\PaymentGatewayController::class, 'sslFail'])->name('payment.ssl.fail');
Route::post('/payment/ssl/cancel', [\App\Http\Controllers\PaymentGatewayController::class, 'sslCancel'])->name('payment.ssl.cancel');
Route::get('/payment/ipay/success', [\App\Http\Controllers\PaymentGatewayController::class, 'ipaySuccess'])->name('payment.ipay.success');
Route::get('/payment/ipay/cancel', [\App\Http\Controllers\PaymentGatewayController::class, 'ipayCancel'])->name('payment.ipay.cancel');
Route::get('/payment/demo/{transaction_id}', [\App\Http\Controllers\PaymentGatewayController::class, 'demoConfirm'])->name('payment.demo.confirm')->middleware(['auth', 'client.access']);
Route::post('/payment/demo/{transaction_id}', [\App\Http\Controllers\PaymentGatewayController::class, 'demoProcess'])->name('payment.demo.process')->middleware(['auth', 'client.access']);

// Download project route removed for security - exposed source code without authentication

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
Route::post('/session/keepalive', function() {
    if (auth()->check()) {
        session(['last_activity_time' => time()]);
        return response()->json(['status' => 'ok', 'expires_in' => 1800]);
    }
    return response()->json(['status' => 'expired'], 401);
})->middleware('auth')->name('session.keepalive');

// Real-time location update for already-logged-in sessions
Route::post('/update-my-location', [\App\Http\Controllers\Auth\LoginController::class, 'updateMyLocation'])
    ->middleware('auth')
    ->name('update.my.location');

Route::get('/client/login', [LoginController::class, 'showClientLoginForm'])->name('client.login');
Route::post('/client/login', [LoginController::class, 'clientLogin'])->name('client.login.submit');
Route::get('/client/forgot-password', [\App\Http\Controllers\Auth\ClientForgotPasswordController::class, 'showForgotForm'])->name('client.forgot');
Route::post('/client/forgot-password/verify', [\App\Http\Controllers\Auth\ClientForgotPasswordController::class, 'verifyIdentity'])->name('client.forgot.verify');
Route::post('/client/forgot-password/reset', [\App\Http\Controllers\Auth\ClientForgotPasswordController::class, 'resetPassword'])->name('client.forgot.reset');

Route::get('/forgot-password', [\App\Http\Controllers\Auth\AdminForgotPasswordController::class, 'showForgotForm'])->name('admin.forgot');
Route::post('/forgot-password/verify', [\App\Http\Controllers\Auth\AdminForgotPasswordController::class, 'verifyIdentity'])->name('admin.forgot.verify');
Route::post('/forgot-password/reset', [\App\Http\Controllers\Auth\AdminForgotPasswordController::class, 'resetPassword'])->name('admin.forgot.reset');


// Real-time Bill Sync API (accessible by all authenticated users)
Route::get('/api/client/{client}/bill-status', [\App\Http\Controllers\Api\BillSyncController::class, 'getStatus'])
    ->middleware('auth')
    ->name('api.bill-status');



Route::get('/impersonate/switch-back', [ImpersonateController::class, 'switchBack'])
    ->middleware('auth')
    ->name('impersonate.switch-back');


// Company switcher for super admin
Route::get("admin/switch-company", function (\Illuminate\Http\Request $request) {
    if (auth()->check() && (auth()->user()->user_type === "super_admin" || auth()->user()->hasRole("Super Admin"))) {
        $cid = $request->input("company_id");
        if ($cid) { session(["global_company_id" => (int)$cid]); }
        else { session()->forget("global_company_id"); }
    }
    return redirect()->back();
})->middleware(["auth", "admin"])->name("admin.switch-company");

Route::prefix('admin')->middleware(['auth', 'admin', 'session.timeout', 'route.permission'])->group(function () {
    // Android SMS Auto-Payment
    Route::prefix('billing')->name('billing.')->group(function () {
        Route::get('android-sendmoney', [App\Http\Controllers\Admin\AndroidSmsController::class, 'index'])->name('android-sms.index');
        Route::post('android-sendmoney/reprocess/{id}', [App\Http\Controllers\Admin\AndroidSmsController::class, 'reprocess'])->name('android-sms.reprocess');
        Route::post('android-sendmoney/{id}/lock', [App\Http\Controllers\Admin\AndroidSmsController::class, 'toggleLock'])->name('android-sms.lock');
        Route::post('android-sendmoney/token', [App\Http\Controllers\Admin\AndroidSmsController::class, 'generateToken'])->name('android-sms.token');
    });

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');
    Route::get('/dashboard/online-client-history', [DashboardController::class, 'onlineClientHistory'])->name('admin.dashboard.online-client-history');

    Route::get('/help', [\App\Http\Controllers\Admin\HelpController::class, 'index'])->name('admin.help.index');
    Route::get('/help/contextual', [\App\Http\Controllers\Admin\HelpController::class, 'contextual'])->name('admin.help.contextual');
    
    Route::get('/api/company-stats', [\App\Http\Controllers\Admin\ClientController::class, 'companyStatsApi'])->name('admin.api.company-stats');
    Route::get('/api/quick-search', [\App\Http\Controllers\Admin\QuickSearchController::class, 'search'])->name('admin.api.quick-search');
    Route::get('/api/search-clients', [\App\Http\Controllers\Admin\ClientController::class, 'searchClientsApi'])->name('admin.api.search-clients');
    Route::get('/api/live-stats', [DashboardController::class, 'liveStats'])->name('admin.api.live-stats');
    Route::get('/api/live-network', [DashboardController::class, 'liveNetworkStatus'])->name('admin.api.live-network');
    Route::get('/api/live-alerts', [DashboardController::class, 'liveAlerts'])->name('admin.api.live-alerts');
    Route::get('/api/live-payments', [DashboardController::class, 'livePayments'])->name('admin.api.live-payments');
    Route::get('/api/live-health', [DashboardController::class, 'liveIspHealth'])->name('admin.api.live-health');
    Route::get('/api/live-activity', [DashboardController::class, 'liveClientActivity'])->name('admin.api.live-activity');
    Route::get('/api/chart-data', [DashboardController::class, 'chartData'])->name('admin.api.chart-data');
    Route::get('/api/live-mikrotik-count', [DashboardController::class, 'liveMikrotikCount'])->name('admin.api.live-mikrotik-count');
    Route::get('/api/app-update-news', [DashboardController::class, 'appUpdateNews'])->name('admin.api.app-update-news');
    Route::get('/api/server-health', [DashboardController::class, 'serverHealth'])->name('admin.api.server-health');
    
    Route::post('/api/sync-all-devices', [\App\Http\Controllers\Admin\RealtimeSyncController::class, 'syncAllDevices'])->name('admin.api.sync-all');
    Route::post('/api/instant-sync-status', [\App\Http\Controllers\Admin\RealtimeSyncController::class, 'instantSyncClientStatuses'])->name('admin.api.instant-sync-status');
    Route::get('/api/network-overview', [\App\Http\Controllers\Admin\RealtimeSyncController::class, 'getNetworkOverview'])->name('admin.api.network-overview');
    Route::get('/api/realtime-alerts', [\App\Http\Controllers\Admin\RealtimeSyncController::class, 'getRealtimeAlerts'])->name('admin.api.realtime-alerts');
    Route::get('/api/live-bandwidth', [\App\Http\Controllers\Admin\RealtimeSyncController::class, 'getLiveBandwidthStats'])->name('admin.api.live-bandwidth');
    Route::post('/api/sync-mikrotik/{device}', [\App\Http\Controllers\Admin\RealtimeSyncController::class, 'syncMikrotikDevice'])->name('admin.api.sync-mikrotik');
    Route::post('/api/sync-olt/{olt}', [\App\Http\Controllers\Admin\RealtimeSyncController::class, 'syncOltDevice'])->name('admin.api.sync-olt');
    Route::post('/api/olt/sync-all-onus-now', [\App\Http\Controllers\Admin\RealtimeSyncController::class, 'syncAllOnusNow'])->name('admin.api.sync-all-onus-now');
    Route::get('/api/olt/sync-status', [\App\Http\Controllers\Admin\RealtimeSyncController::class, 'getOltSyncStatus'])->name('admin.api.olt-sync-status');
    Route::get('/api/client-realtime/{client}', [\App\Http\Controllers\Admin\RealtimeSyncController::class, 'getClientRealtimeStatus'])->name('admin.api.client-realtime');
    
    // Location filtering API (Division/District/Upazila/Zone/Subzone/TJ cascading)
    Route::get('/api/divisions', function() {
        return \App\Models\District::where('is_active', true)->whereNotNull('division')->where('division', '!=', '')->distinct()->orderBy('division')->pluck('division');
    })->name('admin.api.divisions');
    Route::get('/api/districts-by-division/{division}', function($division) {
        return \App\Models\District::where('is_active', true)->where('division', $division)->orderBy('name')->get(['id', 'name']);
    })->name('admin.api.districts-by-division');
    Route::get('/api/districts-detail-by-division/{division}', function($division) {
        return \App\Models\District::withCount('upazilas')->where('division', $division)->orderBy('name')->get(['id', 'name', 'division', 'is_active']);
    })->name('admin.api.districts-detail-by-division');
    Route::get('/api/districts-available/{division}', function($division) {
        return \App\Models\District::where('division', $division)->where('is_configured', false)->orderBy('name')->get(['id', 'name']);
    })->name('admin.api.districts-available');
    Route::get('/api/upazilas-by-district/{district}', function(\App\Models\District $district) {
        return $district->upazilas()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    })->name('admin.api.upazilas-by-district');
    Route::get('/api/dashboard/zones-by-thana/{upazila?}', [DashboardController::class, 'zonesByThana'])->name('admin.api.dashboard.zones-by-thana');
    Route::get('/api/zones-by-upazila/{upazila}', function(\App\Models\Upazila $upazila) {
        return \App\Models\Zone::where('upazila_id', $upazila->id)->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    })->name('admin.api.zones-by-upazila');
    Route::get('/api/areas-by-zone/{zone}', function(\App\Models\Zone $zone) {
        return \App\Services\ClientFilterService::getAreasForZone($zone->id);
    })->name('admin.api.areas-by-zone');
    Route::get('/api/boxes-by-area/{area}', function(\App\Models\Area $area) {
        return \App\Services\ClientFilterService::getBoxesForArea($area->id);
    })->name('admin.api.boxes-by-area');
    Route::get('/api/boxes-by-zone/{zone}', function(\App\Models\Zone $zone) {
        return \App\Services\ClientFilterService::getBoxesForZone($zone->id);
    })->name('admin.api.boxes-by-zone');
    
    // Personal selfie attendance for logged-in admin user
    Route::get('/my-attendance', [\App\Http\Controllers\Admin\SelfieAttendanceController::class, 'myPersonalAttendance'])->name('admin.my-attendance');
    Route::post('/my-attendance', [\App\Http\Controllers\Admin\SelfieAttendanceController::class, 'storePersonalAttendance'])->name('admin.my-attendance.store');
    Route::get('/profile', [ProfileController::class, 'index'])->name('admin.profile');
    Route::post('/profile', [ProfileController::class, 'update'])->name('admin.profile.update');
    Route::post('/profile/password', [ProfileController::class, 'updatePassword'])->name('admin.profile.password');

    Route::get('/clients', [ClientController::class, 'index'])->name('admin.clients.index');
    Route::post('/clients/bulk-action', [ClientController::class, 'bulkAction'])->name('admin.clients.bulk-action');
    Route::post('/clients/export', [ClientController::class, 'exportClients'])->name('admin.clients.export');
    Route::get('/clients/export-filtered', [ClientController::class, 'exportFilteredClients'])->name('admin.clients.export-filtered');
    Route::get('/clients/autocomplete', [ClientController::class, 'autocomplete'])->name('admin.clients.autocomplete');
    Route::get('/clients/create', [ClientController::class, 'create'])->name('admin.clients.create');
    Route::get('/clients/suggest-credentials', [ClientController::class, 'suggestCredentials'])->name('admin.clients.suggest-credentials');
    Route::post('/clients', [ClientController::class, 'store'])->name('admin.clients.store');
    Route::get('/clients/map', [ClientController::class, 'clientMap'])->name('admin.clients.map');
    Route::get('/clients/pending', [ClientController::class, 'pending'])->name('admin.clients.pending');
    Route::get('/clients/inactive', [ClientController::class, 'inactive'])->name('admin.clients.inactive');
    Route::get('/clients/due', [ClientController::class, 'dueClients'])->name('admin.clients.due');
    Route::get('/clients/package-requests', [ClientHistoryController::class, 'packageRequests'])->name('admin.clients.package-requests');
    Route::post('/clients/package-requests/{changeRequest}', [ClientHistoryController::class, 'approvePackageRequest'])->name('admin.clients.package-requests.process');
    Route::get('/clients/{client}', [ClientController::class, 'show'])->name('admin.clients.show');
    Route::get('/clients/{client}/edit', [ClientController::class, 'edit'])->name('admin.clients.edit');
    Route::put('/clients/{client}', [ClientController::class, 'update'])->name('admin.clients.update');
    Route::post('/clients/detect-all-routers', [ClientController::class, 'detectAllRouterBrands'])->name('admin.clients.detect-all-routers');
    Route::post('/clients/{client}/detect-router', [ClientController::class, 'detectRouterBrand'])->name('admin.clients.detect-router');
    Route::post('/clients/{client}/toggle-mikrotik', [ClientController::class, 'toggleMikrotikProfile'])->name('admin.clients.toggle-mikrotik');
    Route::post('/clients/{client}/change-package', [ClientController::class, 'changePackage'])->name('admin.clients.change-package');
    Route::post('/clients/{client}/update-remarks', [ClientController::class, 'updateRemarks'])->name('admin.clients.update-remarks');
    Route::get('/clients/{client}/confirm-delete', [ClientController::class, 'confirmDelete'])->name('admin.clients.confirm-delete');
    Route::delete('/clients/{client}/bills/{bill}', [ClientController::class, 'deleteBill'])->name('admin.clients.delete-bill');
    Route::delete('/clients/{client}', [ClientController::class, 'destroy'])->name('admin.clients.destroy');
    
    Route::get('/clients/{client}/line-shifts', [ClientHistoryController::class, 'lineShifts'])->name('admin.clients.line-shifts');
    Route::post('/clients/{client}/line-shifts', [ClientHistoryController::class, 'storeLineShift'])->name('admin.clients.line-shifts.store');
    Route::get('/clients/{client}/reconnections', [ClientHistoryController::class, 'reconnections'])->name('admin.clients.reconnections');
    Route::post('/clients/{client}/reconnections', [ClientHistoryController::class, 'storeReconnection'])->name('admin.clients.reconnections.store');
    Route::get('/clients/{client}/documents', [ClientHistoryController::class, 'documents'])->name('admin.clients.documents');
    Route::post('/clients/{client}/documents', [ClientHistoryController::class, 'uploadDocument'])->name('admin.clients.documents.upload');
    Route::delete('/clients/documents/{document}', [ClientHistoryController::class, 'deleteDocument'])->name('admin.clients.documents.delete');
    Route::get('/clients/{client}/ledger', [ClientHistoryController::class, 'ledger'])->name('admin.clients.ledger');
    Route::get('/clients/{client}/status-history', [ClientHistoryController::class, 'statusHistory'])->name('admin.clients.status-history');
    Route::get('/clients/{client}/payment-history', [ClientHistoryController::class, 'paymentHistory'])->name('admin.clients.payment-history');
    
    // Live Monitoring (Real-time bandwidth & ping)
    Route::get('/clients/{client}/live-bandwidth', [\App\Http\Controllers\Admin\LiveMonitorController::class, 'getBandwidth'])->name('admin.clients.live-bandwidth');
    Route::get('/clients/{client}/live-ping', [\App\Http\Controllers\Admin\LiveMonitorController::class, 'ping'])->name('admin.clients.live-ping');
    Route::get('/clients/{client}/refresh-online-info', [ClientController::class, 'refreshOnlineInfo'])->name('admin.clients.refresh-online-info');
    Route::get('/clients/{client}/ping-stream', [\App\Http\Controllers\Admin\LiveMonitorController::class, 'pingStream'])->name('admin.clients.ping-stream');
    Route::get('/clients/{client}/bandwidth-history', [\App\Http\Controllers\Admin\LiveMonitorController::class, 'getBandwidthHistory'])->name('admin.clients.bandwidth-history');
    
    // Additional Payments (equipment, adapters, ONU, etc.)
    Route::get('/clients/{client}/additional-payments', [\App\Http\Controllers\Admin\ClientAdditionalPaymentController::class, 'index'])->name('admin.clients.additional-payments.index');
    Route::get('/clients/{client}/additional-payments/create', [\App\Http\Controllers\Admin\ClientAdditionalPaymentController::class, 'create'])->name('admin.clients.additional-payments.create');
    Route::post('/clients/{client}/additional-payments', [\App\Http\Controllers\Admin\ClientAdditionalPaymentController::class, 'store'])->name('admin.clients.additional-payments.store');
    Route::delete('/clients/{client}/additional-payments/{payment}', [\App\Http\Controllers\Admin\ClientAdditionalPaymentController::class, 'destroy'])->name('admin.clients.additional-payments.destroy');
    
    Route::post('/clients/{client}/sync', [\App\Http\Controllers\Admin\DeviceControlController::class, 'syncClient'])->name('admin.clients.sync');
    Route::post('/clients/{client}/disconnect', [\App\Http\Controllers\Admin\DeviceControlController::class, 'disconnectClient'])->name('admin.clients.disconnect');
    Route::post('/clients/{client}/enable', [\App\Http\Controllers\Admin\DeviceControlController::class, 'enableClient'])->name('admin.clients.enable');
    Route::post('/clients/{client}/disable', [\App\Http\Controllers\Admin\DeviceControlController::class, 'disableClient'])->name('admin.clients.disable');
    Route::post('/clients/{client}/quick-payment', [\App\Http\Controllers\Admin\PaymentController::class, 'quickPayment'])->name('admin.clients.quick-payment');
    Route::post('/clients/{client}/reboot-onu', [\App\Http\Controllers\Admin\DeviceControlController::class, 'rebootOnu'])->name('admin.clients.reboot-onu');
    Route::post('/clients/{client}/fix-onu-link', [ClientController::class, 'fixOnuLink'])->name('admin.clients.fix-onu-link');
    Route::get('/clients/{client}/onu-diagnostics', [ClientController::class, 'onuDiagnostics'])->name('admin.clients.onu-diagnostics');
    Route::post('/clients/{client}/update-speed', [\App\Http\Controllers\Admin\DeviceControlController::class, 'updateSpeed'])->name('admin.clients.update-speed');
    Route::post('/clients/{client}/pay-installation-charge', [ClientController::class, 'payInstallationCharge'])->name('admin.clients.pay-installation-charge');
    Route::post('/clients/{client}/unpay-installation-charge', [ClientController::class, 'unpayInstallationCharge'])->name('admin.clients.unpay-installation-charge');
    Route::post('/clients/{client}/set-installation-charge', [ClientController::class, 'setInstallationCharge'])->name('admin.clients.set-installation-charge');
    Route::post('/clients/{client}/change-connection-type', [ClientController::class, 'changeConnectionType'])->name('admin.clients.change-connection-type');
    Route::post('/clients/{client}/temp-deadline', [ClientController::class, 'applyTemporaryDeadline'])->name('admin.clients.temp-deadline');
    Route::post('/clients/{client}/clear-temp-deadline', [ClientController::class, 'clearTemporaryDeadline'])->name('admin.clients.clear-temp-deadline');
    Route::get('/clients/{client}/info', [ClientController::class, 'getClientInfo'])->name('admin.clients.info');

    Route::get('/network/devices', [NetworkController::class, 'devices'])->name('admin.network.devices');
    Route::get('/network/devices/index', [NetworkController::class, 'devices'])->name('admin.network.devices.index');
    Route::get('/network/devices/create', [NetworkController::class, 'createDevice'])->name('admin.network.devices.create');
    Route::post('/network/devices', [NetworkController::class, 'storeDevice'])->name('admin.network.devices.store');
    Route::post('/network/devices/bulk', [NetworkController::class, 'storeBulkDevices'])->name('admin.network.devices.bulk');
    Route::get('/network/devices/{device}/edit', [NetworkController::class, 'editDevice'])->name('admin.network.devices.edit');
    Route::put('/network/devices/{device}', [NetworkController::class, 'updateDevice'])->name('admin.network.devices.update');
    Route::delete('/network/devices/{device}', [NetworkController::class, 'destroyDevice'])->name('admin.network.devices.destroy');
    Route::post('/network/devices/{device}/test', [NetworkController::class, 'testDevice'])->name('admin.network.devices.test');
    Route::get('/network/connections', [NetworkController::class, 'connections'])->name('admin.network.connections');
    Route::get('/network/queues', [NetworkController::class, 'queues'])->name('admin.network.queues');
    Route::get('/network/diagram', [NetworkController::class, 'diagram'])->name('admin.network.diagram');
    Route::get('/network/client-diagram', [NetworkController::class, 'clientDiagram'])->name('admin.network.client-diagram');
    Route::get('/network/monitoring', [NetworkController::class, 'monitoring'])->name('admin.network.monitoring');
    Route::get('/network/dashboard', [NetworkController::class, 'networkDashboard'])->name('admin.network.dashboard');
    Route::get('/network/devices/{device}/detail', [NetworkController::class, 'routerDetail'])->name('admin.network.devices.detail');
    Route::get('/network/devices/{device}/detail-api', [NetworkController::class, 'routerDetailApi'])->name('admin.network.devices.detail-api');
    Route::get('/network/devices/{device}/ppp-users', [NetworkController::class, 'pppUsers'])->name('admin.network.devices.ppp-users');
    Route::post('/network/devices/{device}/sync-ppp', [NetworkController::class, 'syncPPPUsers'])->name('admin.network.devices.sync-ppp');
    Route::post('/network/devices/{device}/sync-packages', [NetworkController::class, 'syncPackagesToDevice'])->name('admin.network.devices.sync-packages');
    Route::post('/network/devices/{device}/import-ppp-user', [NetworkController::class, 'importPPPUser'])->name('admin.network.devices.import-ppp-user');

    Route::get('/network/mikrotik-sync', [MikrotikSyncController::class, 'index'])->name('admin.mikrotik-sync.index');
    Route::post('/network/mikrotik-sync/run', [MikrotikSyncController::class, 'runSync'])->name('admin.mikrotik-sync.run');
    Route::get('/network/mikrotik-sync/{run}', [MikrotikSyncController::class, 'show'])->name('admin.mikrotik-sync.show');
    Route::post('/network/mikrotik-sync/{run}/apply-all', [MikrotikSyncController::class, 'applyAll'])->name('admin.mikrotik-sync.apply-all');
    Route::post('/network/mikrotik-sync/item/{item}/apply', [MikrotikSyncController::class, 'applyAction'])->name('admin.mikrotik-sync.apply-action');
    Route::post('/network/mikrotik-sync/item/{item}/skip', [MikrotikSyncController::class, 'skipAction'])->name('admin.mikrotik-sync.skip-action');

    Route::get('/network/bulk-operations', [\App\Http\Controllers\Admin\BulkOperationsController::class, 'index'])->name('admin.network.bulk-operations');
    Route::post('/network/bulk-enable', [\App\Http\Controllers\Admin\BulkOperationsController::class, 'bulkEnable'])->name('admin.network.bulk-enable');
    Route::post('/network/bulk-disable', [\App\Http\Controllers\Admin\BulkOperationsController::class, 'bulkDisable'])->name('admin.network.bulk-disable');
    Route::post('/network/bulk-sync', [\App\Http\Controllers\Admin\BulkOperationsController::class, 'bulkSync'])->name('admin.network.bulk-sync');
    Route::post('/network/bulk-disconnect', [\App\Http\Controllers\Admin\BulkOperationsController::class, 'bulkDisconnect'])->name('admin.network.bulk-disconnect');
    Route::post('/network/bulk-provision', [\App\Http\Controllers\Admin\BulkOperationsController::class, 'bulkProvision'])->name('admin.network.bulk-provision');

    Route::get('/network/olt', [OltController::class, 'index'])->name('admin.olt.index');
    Route::get('/network/olt/create', [OltController::class, 'create'])->name('admin.olt.create');
    Route::post('/network/olt', [OltController::class, 'store'])->name('admin.olt.store');
    Route::get('/network/olt/{olt}/edit', [OltController::class, 'edit'])->name('admin.olt.edit');
    Route::put('/network/olt/{olt}', [OltController::class, 'update'])->name('admin.olt.update');
    Route::delete('/network/olt/{olt}', [OltController::class, 'destroy'])->name('admin.olt.destroy');
    Route::post('/network/olt/{olt}/test', [OltController::class, 'testConnection'])->name('admin.olt.test');
    Route::post('/network/olt/detect-pon-type', [OltController::class, 'detectPonType'])->name('admin.olt.detect-pon-type');
    Route::post('/network/olt/{olt}/onu-info', [OltController::class, 'getOnuInfo'])->name('admin.olt.onu-info');
    Route::post('/network/olt/{olt}/sync-onus', [OltController::class, 'syncOnus'])->name('admin.olt.sync-onus');
    Route::post('/network/olt/{olt}/poll-dashboard', [OltController::class, 'pollDashboard'])->name('admin.olt.poll-dashboard');
    Route::get('/network/olt/{olt}/dashboard', [OltController::class, 'dashboard'])->name('admin.olt.dashboard');
    Route::get('/network/olt/{olt}/onus', [OltController::class, 'onuManagement'])->name('admin.olt.onus');
    Route::post('/network/olt/{olt}/discover-onus', [OltController::class, 'discoverOnus'])->name('admin.olt.discover-onus');
    Route::post('/network/olt/{olt}/provision-onu', [OltController::class, 'provisionOnu'])->name('admin.olt.provision-onu');
    Route::post('/network/onu/{onu}/decommission', [OltController::class, 'decommissionOnu'])->name('admin.olt.decommission-onu');
    Route::post('/network/onu/{onu}/assign', [OltController::class, 'assignOnu'])->name('admin.olt.assign-onu');
    Route::get('/network/onu/{onu}/detail', [OltController::class, 'onuDetail'])->name('admin.olt.onu-detail');
    Route::get('/network/onu/{onu}/live-info', [OltController::class, 'getOnuLiveInfo'])->name('admin.olt.onu-live-info');
    Route::get('/network/onus/picker-list', [OltController::class, 'onuPickerList'])->name('admin.olt.onu-picker-list');
    Route::get('/network/olt/{olt}/port-utilization', [OltController::class, 'portUtilization'])->name('admin.olt.port-utilization');
    Route::get('/network/olt/{olt}/alarms', [OltController::class, 'alarms'])->name('admin.olt.alarms');
    Route::post('/network/olt/alarms/{alarm}/resolve', [OltController::class, 'resolveAlarm'])->name('admin.olt.resolve-alarm');
    Route::get('/network/olt/{olt}/provision-logs', [OltController::class, 'provisionLogs'])->name('admin.olt.provision-logs');
    Route::post('/network/olt/{olt}/check-all-power', [OltController::class, 'checkAllOnuPower'])->name('admin.olt.check-all-power');
    Route::post('/network/olt/{olt}/live-refresh', [OltController::class, 'liveRefresh'])->name('admin.olt.live-refresh');
    Route::post('/network/olt/{olt}/sync-pon-tx', [OltController::class, 'syncPonTx'])->name('admin.olt.sync-pon-tx');
    Route::post('/network/olt/{olt}/scan-match', [OltController::class, 'scanAndMatch'])->name('admin.olt.scan-match');
    Route::post('/network/olt/{olt}/set-port-description', [OltController::class, 'setPortDescription'])->name('admin.olt.set-port-description');

    Route::get('/network/radius-settings', [RadiusSettingsController::class, 'index'])->name('admin.network.radius-settings');
    Route::post('/network/radius-settings', [RadiusSettingsController::class, 'update'])->name('admin.network.radius-settings.update');
    Route::post('/network/radius-settings/test', [RadiusSettingsController::class, 'testConnection'])->name('admin.network.radius-settings.test');
    Route::post('/network/radius-settings/sync-clients', [RadiusSettingsController::class, 'syncAllClients'])->name('admin.network.radius-settings.sync-clients');
    Route::post('/network/radius-settings/sync-packages', [RadiusSettingsController::class, 'syncAllPackages'])->name('admin.network.radius-settings.sync-packages');

    Route::get('/network/firewall/{device}', [FirewallController::class, 'index'])->name('admin.network.firewall');
    Route::post('/network/firewall/{device}/sync', [FirewallController::class, 'syncFromRouter'])->name('admin.network.firewall.sync');
    Route::post('/network/firewall/{device}/store', [FirewallController::class, 'store'])->name('admin.network.firewall.store');
    Route::delete('/network/firewall/{device}/{rule}', [FirewallController::class, 'destroy'])->name('admin.network.firewall.destroy');
    Route::post('/network/firewall/{device}/{rule}/toggle', [FirewallController::class, 'toggleRule'])->name('admin.network.firewall.toggle');
    Route::get('/network/nat/{device}', [FirewallController::class, 'natIndex'])->name('admin.network.nat');
    Route::post('/network/nat/{device}/sync', [FirewallController::class, 'syncNatFromRouter'])->name('admin.network.nat.sync');
    Route::post('/network/nat/{device}/store', [FirewallController::class, 'storeNat'])->name('admin.network.nat.store');
    Route::delete('/network/nat/{device}/{rule}', [FirewallController::class, 'destroyNat'])->name('admin.network.nat.destroy');

    Route::get('/network/ip-pools', [IpPoolController::class, 'index'])->name('admin.network.ip-pools');
    Route::post('/network/ip-pools/sync-from-router', [IpPoolController::class, 'syncFromRouter'])->name('admin.network.ip-pool-import');
    Route::post('/network/ip-pools/assign', [IpPoolController::class, 'assignIp'])->name('admin.network.ip-assign');
    Route::post('/network/ip-pools/release/{address}', [IpPoolController::class, 'releaseIp'])->name('admin.network.ip-release');
    Route::get('/network/ip-pools/{pool}', [IpPoolController::class, 'show'])->name('admin.network.ip-pool-detail');
    Route::post('/network/ip-pools/{pool}/sync-to-router', [IpPoolController::class, 'syncToRouter'])->name('admin.network.ip-pool-sync');

    Route::get('/billing', [BillingController::class, 'index'])->name('admin.billing.index');
    Route::post('/billing/bulk-action', [BillingController::class, 'bulkAction'])->name('admin.billing.bulk-action');
    Route::get('/billing/generate', [BillingController::class, 'generate'])->name('admin.billing.generate');
    Route::post('/billing/generate', [BillingController::class, 'generateBills'])->name('admin.billing.generate.post');
    Route::get('/billing/due', [BillingController::class, 'due'])->name('admin.billing.due');
    Route::get('/billing/advance-collection', [AdvancedBillingController::class, 'advanceCollection'])->name('admin.billing.advance-collection');
    Route::post('/billing/advance-collection', [AdvancedBillingController::class, 'storeAdvanceCollection'])->name('admin.billing.advance-collection.store');
    Route::get('/billing/online-payments', [AdvancedBillingController::class, 'onlinePayments'])->name('admin.billing.online-payments');
    Route::get('/billing/collection-transfers', [AdvancedBillingController::class, 'collectionTransfers'])->name('admin.billing.collection-transfers');
    Route::post('/billing/collection-transfers', [AdvancedBillingController::class, 'storeCollectionTransfer'])->name('admin.billing.collection-transfers.store');
    Route::post('/billing/collection-transfers/{transfer}/verify', [AdvancedBillingController::class, 'verifyTransfer'])->name('admin.billing.collection-transfers.verify');
    Route::get('/billing/api/staff-balance/{user}', [AdvancedBillingController::class, 'getStaffBalance'])->name('admin.billing.staff-balance');

    Route::get('/billing/daily-collections', [\App\Http\Controllers\Admin\DailyCollectionController::class, 'index'])->name('admin.billing.daily-collections');
    Route::post('/billing/daily-collections', [\App\Http\Controllers\Admin\DailyCollectionController::class, 'store'])->name('admin.billing.daily-collections.store');
    Route::get('/billing/daily-collections/search-clients', [\App\Http\Controllers\Admin\DailyCollectionController::class, 'searchClients'])->name('admin.billing.daily-collections.search-clients');
    Route::post('/billing/daily-collections/{collection}/approve', [\App\Http\Controllers\Admin\DailyCollectionController::class, 'approve'])->name('admin.billing.daily-collections.approve');
    Route::post('/billing/daily-collections/{collection}/reject', [\App\Http\Controllers\Admin\DailyCollectionController::class, 'reject'])->name('admin.billing.daily-collections.reject');
    Route::get('/billing/collection-report', [\App\Http\Controllers\Admin\DailyCollectionController::class, 'report'])->name('admin.billing.collection-report');

    Route::get('/inventory/returned-equipments', [\App\Http\Controllers\Admin\ReturnedEquipmentController::class, 'index'])->name('admin.inventory.returned-equipments');
    Route::get('/inventory/returned-equipments/{returnedEquipment}', [\App\Http\Controllers\Admin\ReturnedEquipmentController::class, 'show'])->name('admin.inventory.returned-equipments.show');
    Route::post('/inventory/returned-equipments/{returnedEquipment}/restock', [\App\Http\Controllers\Admin\ReturnedEquipmentController::class, 'restockToInventory'])->name('admin.inventory.returned-equipments.restock');
    Route::post("/inventory/returned-equipments/{returnedEquipment}/assign-to-client", [\App\Http\Controllers\Admin\ReturnedEquipmentController::class, "assignToClient"])->name("admin.inventory.returned-equipments.assign");
    Route::get('/inventory/inactive-client-history/export', [InventoryController::class, 'exportInactiveClientHistory'])->name('admin.inventory.inactive-client-history.export');
    Route::get('/clients/{client}/equipment', [\App\Http\Controllers\Admin\ReturnedEquipmentController::class, 'getClientEquipment'])->name('admin.clients.equipment');
    Route::post('/clients/{client}/return-equipment', [\App\Http\Controllers\Admin\ReturnedEquipmentController::class, 'returnEquipment'])->name('admin.clients.return-equipment');

    Route::get('/billing/{bill}', [BillingController::class, 'show'])->name('admin.billing.show');
    Route::get('/billing/{bill}/edit', [BillingController::class, 'edit'])->name('admin.billing.edit');
    Route::put('/billing/{bill}', [BillingController::class, 'update'])->name('admin.billing.update');
    Route::delete('/billing/{bill}', [BillingController::class, 'destroy'])->name('admin.billing.destroy');
    Route::get('/billing/{bill}/invoice', [BillingController::class, 'invoice'])->name('admin.billing.invoice');
    Route::get('/billing/{bill}/pocket-invoice', [AdvancedBillingController::class, 'printPocketInvoice'])->name('admin.billing.pocket-invoice');
    Route::get('/billing/{bill}/due-reminder-preview', [AdvancedBillingController::class, 'dueReminderPreview'])->name('admin.billing.due-reminder-preview');
    Route::post('/billing/{bill}/send-reminder', [AdvancedBillingController::class, 'sendDueReminder'])->name('admin.billing.send-reminder');
    Route::post('/clients/{client}/partial-payment', [AdvancedBillingController::class, 'processPartialPayment'])->name('admin.billing.partial-payment');
    Route::post('/clients/{client}/generate-bill', [BillingController::class, 'generateClientBill'])->name('admin.clients.generate-bill');

    Route::get('/payments', [PaymentController::class, 'index'])->name('admin.payments.index');
    Route::get('/payments/pending', [PaymentController::class, 'pendingPayments'])->name('admin.payments.pending');
    Route::post('/payments/{payment}/approve', [PaymentController::class, 'approvePayment'])->name('admin.payments.approve');
    Route::post('/payments/{payment}/reject', [PaymentController::class, 'rejectPayment'])->name('admin.payments.reject');
    Route::get('/payments/create', [PaymentController::class, 'create'])->name('admin.payments.create');
    Route::post('/payments', [PaymentController::class, 'store'])->name('admin.payments.store');
    Route::get('/payments/{payment}/edit', [PaymentController::class, 'edit'])->name('admin.payments.edit');
    Route::put('/payments/{payment}', [PaymentController::class, 'update'])->name('admin.payments.update');
    Route::delete('/payments/{payment}', [PaymentController::class, 'destroy'])->name('admin.payments.destroy');
    Route::post('/payments/bulk-delete', [PaymentController::class, 'bulkDelete'])->name('admin.payments.bulk-delete');
    
    // Payment Receipt Routes (Instant Invoice)
    Route::get('/payments/{payment}/receipt', [\App\Http\Controllers\Admin\PaymentReceiptController::class, 'show'])->name('admin.payments.receipt');
    Route::get('/payments/{payment}/receipt/print', [\App\Http\Controllers\Admin\PaymentReceiptController::class, 'print'])->name('admin.payments.receipt.print');
    Route::get('/payments/{payment}/receipt/thermal', [\App\Http\Controllers\Admin\PaymentReceiptController::class, 'printThermal'])->name('admin.payments.receipt.thermal');
    Route::get('/payments/{payment}/receipt/download', [\App\Http\Controllers\Admin\PaymentReceiptController::class, 'download'])->name('admin.payments.receipt.download');
    Route::get('/payments/{payment}/receipt/data', [\App\Http\Controllers\Admin\PaymentReceiptController::class, 'receiptData'])->name('admin.payments.receipt.data');

    // Bill & Payment Correction System (Admin Only)
    Route::get('/corrections', [\App\Http\Controllers\Admin\CorrectionController::class, 'index'])->name('admin.corrections.index');
    Route::get('/corrections/bill/{bill}', [\App\Http\Controllers\Admin\CorrectionController::class, 'showBillCorrection'])->name('admin.corrections.bill');
    Route::post('/corrections/bill/{bill}/correct-amount', [\App\Http\Controllers\Admin\CorrectionController::class, 'correctBillAmount'])->name('admin.corrections.bill.correct-amount');
    Route::post('/corrections/bill/{bill}/cancel', [\App\Http\Controllers\Admin\CorrectionController::class, 'cancelBill'])->name('admin.corrections.bill.cancel');
    Route::get('/corrections/payment/{payment}', [\App\Http\Controllers\Admin\CorrectionController::class, 'showPaymentCorrection'])->name('admin.corrections.payment');
    Route::post('/corrections/payment/{payment}/reverse', [\App\Http\Controllers\Admin\CorrectionController::class, 'reversePayment'])->name('admin.corrections.payment.reverse');
    Route::post('/corrections/payment/{payment}/mark-duplicate', [\App\Http\Controllers\Admin\CorrectionController::class, 'markDuplicate'])->name('admin.corrections.payment.mark-duplicate');
    Route::get('/corrections/client/{client}/ledger', [\App\Http\Controllers\Admin\CorrectionController::class, 'clientLedger'])->name('admin.corrections.client-ledger');
    Route::post('/corrections/client/{client}/sync-ledger', [\App\Http\Controllers\Admin\CorrectionController::class, 'syncClientLedger'])->name('admin.corrections.sync-ledger');

    // Data Transfer - Import/Export
    Route::get('/data-transfer', [\App\Http\Controllers\Admin\DataTransferController::class, 'index'])->name('admin.data-transfer.index');
    Route::get('/data-transfer/import', [\App\Http\Controllers\Admin\DataTransferController::class, 'importIndex'])->name('admin.data-transfer.import.index');
    Route::get('/data-transfer/import/create', [\App\Http\Controllers\Admin\DataTransferController::class, 'importCreate'])->name('admin.data-transfer.import.create');
    Route::post('/data-transfer/import/upload', [\App\Http\Controllers\Admin\DataTransferController::class, 'importUpload'])->name('admin.data-transfer.import.upload');
    Route::get('/data-transfer/import/{job}/mapping', [\App\Http\Controllers\Admin\DataTransferController::class, 'importMapping'])->name('admin.data-transfer.import.mapping');
    Route::post('/data-transfer/import/{job}/save-mapping', [\App\Http\Controllers\Admin\DataTransferController::class, 'importSaveMapping'])->name('admin.data-transfer.import.save-mapping');
    Route::get('/data-transfer/import/{job}/preview', [\App\Http\Controllers\Admin\DataTransferController::class, 'importPreview'])->name('admin.data-transfer.import.preview');
    Route::post('/data-transfer/import/{job}/confirm', [\App\Http\Controllers\Admin\DataTransferController::class, 'importConfirm'])->name('admin.data-transfer.import.confirm');
    Route::get('/data-transfer/import/{job}/result', [\App\Http\Controllers\Admin\DataTransferController::class, 'importResult'])->name('admin.data-transfer.import.result');
    Route::get('/data-transfer/import/{job}/cancel', [\App\Http\Controllers\Admin\DataTransferController::class, 'importCancel'])->name('admin.data-transfer.import.cancel');
    Route::get('/data-transfer/import/{job}/report', [\App\Http\Controllers\Admin\DataTransferController::class, 'downloadImportReport'])->name('admin.data-transfer.import.report');
    Route::get('/data-transfer/export', [\App\Http\Controllers\Admin\DataTransferController::class, 'exportIndex'])->name('admin.data-transfer.export.index');
    Route::get('/data-transfer/export/create', [\App\Http\Controllers\Admin\DataTransferController::class, 'exportCreate'])->name('admin.data-transfer.export.create');
    Route::post('/data-transfer/export/process', [\App\Http\Controllers\Admin\DataTransferController::class, 'exportProcess'])->name('admin.data-transfer.export.process');
    Route::get('/data-transfer/export/{job}/download', [\App\Http\Controllers\Admin\DataTransferController::class, 'exportDownload'])->name('admin.data-transfer.export.download');
    Route::get('/data-transfer/templates', [\App\Http\Controllers\Admin\DataTransferController::class, 'templatesIndex'])->name('admin.data-transfer.templates.index');
    Route::delete('/data-transfer/templates/{template}', [\App\Http\Controllers\Admin\DataTransferController::class, 'templateDelete'])->name('admin.data-transfer.templates.delete');
    Route::get('/data-transfer/templates/{template}/mapping', [\App\Http\Controllers\Admin\DataTransferController::class, 'getTemplateMapping'])->name('admin.data-transfer.templates.mapping');
    Route::get('/data-transfer/quick', [\App\Http\Controllers\Admin\DataTransferController::class, 'quickIndex'])->name('admin.data-transfer.quick');
    Route::post('/data-transfer/quick-import', [\App\Http\Controllers\Admin\DataTransferController::class, 'quickImport'])->name('admin.data-transfer.quick-import');
    Route::get('/data-transfer/quick-export/{format}', [\App\Http\Controllers\Admin\DataTransferController::class, 'quickExport'])->name('admin.data-transfer.quick-export');
    Route::get('/data-transfer/sample-template/{dataType}', [\App\Http\Controllers\Admin\DataTransferController::class, 'downloadSampleTemplate'])->name('admin.data-transfer.sample-template');
    Route::get('/data-transfer/import-guide', [\App\Http\Controllers\Admin\DataTransferController::class, 'importGuide'])->name('admin.data-transfer.import-guide');

    Route::get('/migration', [\App\Http\Controllers\Admin\MigrationWizardController::class, 'index'])->name('admin.migration.index');
    Route::get('/migration/download-sample', [\App\Http\Controllers\Admin\MigrationWizardController::class, 'downloadSample'])->name('admin.migration.download-sample');
    Route::get('/migration/create', [\App\Http\Controllers\Admin\MigrationWizardController::class, 'create'])->name('admin.migration.create');
    Route::post('/migration', [\App\Http\Controllers\Admin\MigrationWizardController::class, 'store'])->name('admin.migration.store');
    Route::get('/migration/{run}', [\App\Http\Controllers\Admin\MigrationWizardController::class, 'show'])->name('admin.migration.show');
    Route::get('/migration/{run}/phase1', [\App\Http\Controllers\Admin\MigrationWizardController::class, 'phase1'])->name('admin.migration.phase1');
    Route::post('/migration/{run}/phase1/upload', [\App\Http\Controllers\Admin\MigrationWizardController::class, 'phase1Upload'])->name('admin.migration.phase1-upload');
    Route::get('/migration/{run}/phase1/mapping', [\App\Http\Controllers\Admin\MigrationWizardController::class, 'phase1Mapping'])->name('admin.migration.phase1-mapping');
    Route::post('/migration/{run}/phase1/save-mapping', [\App\Http\Controllers\Admin\MigrationWizardController::class, 'phase1SaveMapping'])->name('admin.migration.phase1-save-mapping');
    Route::get('/migration/{run}/phase1/preview', [\App\Http\Controllers\Admin\MigrationWizardController::class, 'phase1Preview'])->name('admin.migration.phase1-preview');
    Route::post('/migration/{run}/phase1/confirm', [\App\Http\Controllers\Admin\MigrationWizardController::class, 'phase1Confirm'])->name('admin.migration.phase1-confirm');
    Route::get('/migration/{run}/phase2', [\App\Http\Controllers\Admin\MigrationWizardController::class, 'phase2'])->name('admin.migration.phase2');
    Route::post('/migration/{run}/phase2/scan', [\App\Http\Controllers\Admin\MigrationWizardController::class, 'phase2Scan'])->name('admin.migration.phase2-scan');
    Route::post('/migration/{run}/phase2/skip', [\App\Http\Controllers\Admin\MigrationWizardController::class, 'phase2Skip'])->name('admin.migration.phase2-skip');
    Route::get('/migration/{run}/phase3', [\App\Http\Controllers\Admin\MigrationWizardController::class, 'phase3'])->name('admin.migration.phase3');
    Route::post('/migration/{run}/phase3/run', [\App\Http\Controllers\Admin\MigrationWizardController::class, 'phase3Run'])->name('admin.migration.phase3-run');
    Route::post('/migration/{run}/phase3/skip', [\App\Http\Controllers\Admin\MigrationWizardController::class, 'phase3Skip'])->name('admin.migration.phase3-skip');
    Route::get('/migration/{run}/phase4', [\App\Http\Controllers\Admin\MigrationWizardController::class, 'phase4'])->name('admin.migration.phase4');
    Route::post('/migration/{run}/phase4/apply/{snapshotId}', [\App\Http\Controllers\Admin\MigrationWizardController::class, 'phase4ApplyAction'])->name('admin.migration.phase4-apply');
    Route::post('/migration/{run}/phase4/apply-all', [\App\Http\Controllers\Admin\MigrationWizardController::class, 'phase4ApplyAll'])->name('admin.migration.phase4-apply-all');
    Route::post('/migration/{run}/phase4/skip', [\App\Http\Controllers\Admin\MigrationWizardController::class, 'phase4Skip'])->name('admin.migration.phase4-skip');
    Route::get('/migration/{run}/phase5', [\App\Http\Controllers\Admin\MigrationWizardController::class, 'phase5'])->name('admin.migration.phase5');
    Route::post('/migration/{run}/phase5/complete', [\App\Http\Controllers\Admin\MigrationWizardController::class, 'phase5Complete'])->name('admin.migration.phase5-complete');

    Route::get('/accounts/cash', [AccountController::class, 'cash'])->name('admin.accounts.cash');
    Route::get('/accounts/cash/account/{account}/ledger', [AccountController::class, 'accountLedger'])->name('admin.accounts.account-ledger');
    Route::get('/accounts/cash/staff/{user}/ledger', [AccountController::class, 'staffCashLedger'])->name('admin.accounts.staff-cash-ledger');
    Route::get('/accounts/income', [AccountController::class, 'income'])->name('admin.accounts.income');
    Route::post('/accounts/income', [AccountController::class, 'storeIncome'])->name('admin.accounts.income.store');
    Route::put('/accounts/income/{income}', [AccountController::class, 'updateIncome'])->name('admin.accounts.income.update');
    Route::delete('/accounts/income/{income}', [AccountController::class, 'destroyIncome'])->name('admin.accounts.income.destroy');
    Route::get('/accounts/expense', [AccountController::class, 'expense'])->name('admin.accounts.expense');
    Route::post('/accounts/expense', [AccountController::class, 'storeExpense'])->name('admin.accounts.expense.store');
    Route::put('/accounts/expense/{expense}', [AccountController::class, 'updateExpense'])->name('admin.accounts.expense.update');
    Route::delete('/accounts/expense/{expense}', [AccountController::class, 'destroyExpense'])->name('admin.accounts.expense.destroy');
    Route::get('/accounts/expense-approval', [AccountController::class, 'expenseApproval'])->name('admin.accounts.expense-approval');
    Route::get('/sales/installation-charges', [AccountController::class, 'installationCharges'])->name('admin.sales.installation-charges');
    Route::put('/sales/installation-charges/{charge}', [AccountController::class, 'updateInstallationCharge'])->name('admin.sales.installation-charges.update');
    Route::delete('/sales/installation-charges/{charge}', [AccountController::class, 'destroyInstallationCharge'])->name('admin.sales.installation-charges.destroy');
    Route::post('/accounts/expense/{expense}/approve', [AccountController::class, 'approveExpense'])->name('admin.accounts.expense.approve');
    Route::post('/accounts/expense/{expense}/reject', [AccountController::class, 'rejectExpense'])->name('admin.accounts.expense.reject');
    Route::get('/accounts/banks', [AccountController::class, 'banks'])->name('admin.accounts.banks');
    Route::get('/accounts/fund-transfer', [AccountController::class, 'fundTransfer'])->name('admin.accounts.fund-transfer');
    Route::post('/accounts/fund-transfer', [AccountController::class, 'storeFundTransfer'])->name('admin.accounts.fund-transfer.store');
    Route::get('/accounts/vendor-ledger', [AccountController::class, 'vendorLedger'])->name('admin.accounts.vendor-ledger');
    Route::get('/accounts/loan', [AccountController::class, 'loan'])->name('admin.accounts.loan');
    Route::post('/accounts/loan', [AccountController::class, 'storeLoan'])->name('admin.accounts.loan.store');
    Route::get('/accounts/loan/{loan}', [AccountController::class, 'showLoan'])->name('admin.accounts.loan.show');
    Route::get('/accounts/loan/{loan}/edit', [AccountController::class, 'editLoan'])->name('admin.accounts.loan.edit');
    Route::put('/accounts/loan/{loan}', [AccountController::class, 'updateLoan'])->name('admin.accounts.loan.update');
    Route::delete('/accounts/loan/{loan}', [AccountController::class, 'deleteLoan'])->name('admin.accounts.loan.destroy');
    Route::post('/accounts/loan/{loan}/payment', [AccountController::class, 'addLoanPayment'])->name('admin.accounts.loan.payment');
    Route::delete('/accounts/loan/{loan}/payment/{payment}', [AccountController::class, 'deleteLoanPayment'])->name('admin.accounts.loan.payment.destroy');
    Route::get('/accounts/loan/{loan}/statement', [AccountController::class, 'loanStatement'])->name('admin.accounts.loan.statement');

    Route::get('/store/categories', [StoreController::class, 'categories'])->name('admin.store.categories');
    Route::post('/store/categories', [StoreController::class, 'storeCategory'])->name('admin.store.categories.store');
    Route::put('/store/categories/{category}', [StoreController::class, 'updateCategory'])->name('admin.store.categories.update');
    Route::delete('/store/categories/{category}', [StoreController::class, 'destroyCategory'])->name('admin.store.categories.destroy');

    Route::get('/store/products', [StoreController::class, 'products'])->name('admin.store.products');
    Route::post('/store/products', [StoreController::class, 'storeProduct'])->name('admin.store.products.store');
    Route::put('/store/products/{product}', [StoreController::class, 'updateProduct'])->name('admin.store.products.update');
    Route::delete('/store/products/{product}', [StoreController::class, 'destroyProduct'])->name('admin.store.products.destroy');
    Route::get('/store/purchases', [StoreController::class, 'purchases'])->name('admin.store.purchases');
    Route::get('/store/stock', [StoreController::class, 'stock'])->name('admin.store.stock');
    Route::get('/store/serials', [InventoryController::class, 'serials'])->name('admin.store.serials');
    Route::post('/store/serials', [InventoryController::class, 'storeSerial'])->name('admin.store.serials.store');
    Route::post('/store/serials/{serial}/assign', [InventoryController::class, 'assignSerial'])->name('admin.store.serials.assign');
    Route::get('/store/stock-movements', [InventoryController::class, 'stockMovements'])->name('admin.store.stock-movements');
    Route::post('/store/stock-movements', [InventoryController::class, 'storeStockMovement'])->name('admin.store.stock-movements.store');
    Route::get('/store/damaged-items', [InventoryController::class, 'damagedItems'])->name('admin.store.damaged-items');
    Route::post('/store/damaged-items', [InventoryController::class, 'reportDamage'])->name('admin.store.damaged-items.store');
    Route::get('/store/warranty-report', [InventoryController::class, 'warrantyReport'])->name('admin.store.warranty-report');
    Route::get('/store/vendors/{vendor}/history', [InventoryController::class, 'vendorPurchaseHistory'])->name('admin.store.vendor-history');
    Route::get('/store/stock-report', [InventoryController::class, 'stockReport'])->name('admin.store.stock-report');
    Route::get('/inventory/inactive-client-history', [InventoryController::class, 'inactiveClientHistory'])->name('admin.inventory.inactive-client-history');
    Route::post('/inventory/inactive-client-history/recover-instrument/{instrument}', [InventoryController::class, 'recoverInstrument'])->name('admin.inventory.recover-instrument');
    Route::post('/inventory/inactive-client-history/recover-fiber-usage/{id}', [InventoryController::class, 'recoverFiberUsage'])->name('admin.inventory.recover-fiber-usage');
    Route::post('/inventory/inactive-client-history/recover-fiber-history/{id}', [InventoryController::class, 'recoverFiberHistory'])->name('admin.inventory.recover-fiber-history');

    Route::get('/support/tickets', [SupportController::class, 'tickets'])->name('admin.support.tickets');
    Route::post('/support/tickets', [SupportController::class, 'createTicket'])->name('admin.support.tickets.create');
    Route::get('/support/tickets/{ticket}', [SupportController::class, 'showTicket'])->name('admin.support.tickets.show')->whereNumber('ticket');
    Route::get('/support/tickets/{ticket}/edit', [SupportController::class, 'editTicket'])->name('admin.support.tickets.edit')->whereNumber('ticket');
    Route::put('/support/tickets/{ticket}', [SupportController::class, 'updateTicket'])->name('admin.support.tickets.update')->whereNumber('ticket');
    Route::delete('/support/tickets/{ticket}', [SupportController::class, 'destroyTicket'])->name('admin.support.tickets.destroy')->whereNumber('ticket');
    Route::post('/support/tickets/{ticket}/reply', [SupportController::class, 'replyTicket'])->name('admin.support.tickets.reply')->whereNumber('ticket');
    Route::get('/support/categories', [SupportController::class, 'categories'])->name('admin.support.categories');
    Route::post('/support/categories', [SupportController::class, 'storeCategory'])->name('admin.support.categories.store');
    Route::put('/support/categories/{category}', [SupportController::class, 'updateCategory'])->name('admin.support.categories.update');
    Route::delete('/support/categories/{category}', [SupportController::class, 'destroyCategory'])->name('admin.support.categories.destroy');
    Route::get('/api/category-subjects/{category}', [SupportController::class, 'getCategorySubjects'])->name('admin.api.category-subjects');
    Route::post('/support/tickets/{ticket}/assign-technician', [SupportController::class, 'assignTechnician'])->name('admin.support.tickets.assign-technician');
    Route::post('/support/tickets/{ticket}/start-work', [SupportController::class, 'startWork'])->name('admin.support.tickets.start-work');
    Route::post('/support/tickets/{ticket}/resolve', [SupportController::class, 'resolve'])->name('admin.support.tickets.resolve');
    Route::post('/support/tickets/{ticket}/reopen', [SupportController::class, 'reopen'])->name('admin.support.tickets.reopen');
    Route::post('/support/tickets/{ticket}/close', [SupportController::class, 'close'])->name('admin.support.tickets.close');
    Route::post('/support/tickets/{ticket}/feedback', [SupportController::class, 'submitFeedback'])->name('admin.support.tickets.feedback');
    Route::get('/support/sla-config', [SupportController::class, 'slaConfig'])->name('admin.support.sla-config');
    Route::post('/support/sla-config', [SupportController::class, 'saveSlaConfig'])->name('admin.support.sla-config.save');
    Route::get('/support/technician-efficiency', [SupportController::class, 'technicianEfficiency'])->name('admin.support.technician-efficiency');

    Route::get('/reseller', [ResellerController::class, 'index'])->name('admin.reseller.index');
    Route::get('/reseller/create', [ResellerController::class, 'create'])->name('admin.reseller.create');
    Route::post('/reseller', [ResellerController::class, 'store'])->name('admin.reseller.store');
    Route::get('/reseller/clients', [ResellerController::class, 'clients'])->name('admin.reseller.clients');
    Route::post('/reseller/{reseller}/clients/{client}/refund-recharge', [ResellerController::class, 'refundRecharge'])->name('admin.reseller.client.refund-recharge');
    Route::post('/reseller/{reseller}/clients/{client}/deactivate', [ResellerController::class, 'deactivateResellerClient'])->name('admin.reseller.client.deactivate');
    Route::post('/reseller/{reseller}/clients/{client}/toggle-recharge-block', [ResellerController::class, 'toggleRechargeBlock'])->name('admin.reseller.client.toggle-recharge-block');
    Route::post('/reseller/{reseller}/correct-recharge', [ResellerController::class, 'correctRecharge'])->name('admin.reseller.correct-recharge');
    Route::get('/reseller/funding', [ResellerController::class, 'funding'])->name('admin.reseller.funding');
    Route::post('/reseller/funding', [ResellerController::class, 'storeFunding'])->name('admin.reseller.funding.store');
    Route::put('/reseller/funding/payment/{payment}', [ResellerController::class, 'updateFundingPayment'])->name('admin.reseller.funding.payment.update');
    Route::delete('/reseller/funding/payment/{payment}', [ResellerController::class, 'deleteFundingPayment'])->name('admin.reseller.funding.payment.delete');
    Route::get('/reseller/bw-funding', [ResellerController::class, 'bwFunding'])->name('admin.reseller.bw-funding');
    Route::post('/reseller/bw-funding', [ResellerController::class, 'bwFundingStore'])->name('admin.reseller.bw-funding.store');
    Route::get('/reseller/collection-report', [ResellerController::class, 'collectionReport'])->name('admin.reseller.collection-report');
    Route::get('/reseller/bw-collection-report', [ResellerController::class, 'bwCollectionReport'])->name('admin.reseller.bw-collection-report');
    Route::get('/reseller/{reseller}', [ResellerController::class, 'show'])->name('admin.reseller.show');
    Route::get('/reseller/{reseller}/edit', [ResellerController::class, 'edit'])->name('admin.reseller.edit');
    Route::put('/reseller/{reseller}', [ResellerController::class, 'update'])->name('admin.reseller.update');
    Route::delete('/reseller/{reseller}', [ResellerController::class, 'destroy'])->name('admin.reseller.destroy');
    Route::get('/reseller/{reseller}/wallet', [ResellerController::class, 'wallet'])->name('admin.reseller.wallet');
    Route::post('/reseller/{reseller}/wallet/credit', [ResellerController::class, 'creditWallet'])->name('admin.reseller.wallet.credit');
    Route::post('/reseller/{reseller}/wallet/debit', [ResellerController::class, 'debitWallet'])->name('admin.reseller.wallet.debit');
    Route::get('/reseller/{reseller}/packages', [ResellerController::class, 'packages'])->name('admin.reseller.packages');
    Route::post('/reseller/{reseller}/packages', [ResellerController::class, 'storePackage'])->name('admin.reseller.packages.store');
    Route::put('/reseller/{reseller}/packages/{resellerPackage}', [ResellerController::class, 'updatePackage'])->name('admin.reseller.packages.update');
    Route::delete('/reseller/{reseller}/packages/{resellerPackage}', [ResellerController::class, 'deletePackage'])->name('admin.reseller.packages.delete');
    Route::post('/reseller/{reseller}/terminate', [ResellerController::class, 'terminate'])->name('admin.reseller.terminate');
    Route::post('/reseller/{reseller}/reactivate', [ResellerController::class, 'reactivate'])->name('admin.reseller.reactivate');
    Route::get('/bandwidth-categories', [\App\Http\Controllers\Admin\BandwidthCategoryController::class, 'index'])->name('admin.bandwidth-categories.index');
    Route::post('/bandwidth-categories', [\App\Http\Controllers\Admin\BandwidthCategoryController::class, 'store'])->name('admin.bandwidth-categories.store');
    Route::put('/bandwidth-categories/{bandwidthCategory}', [\App\Http\Controllers\Admin\BandwidthCategoryController::class, 'update'])->name('admin.bandwidth-categories.update');
    Route::delete('/bandwidth-categories/{bandwidthCategory}', [\App\Http\Controllers\Admin\BandwidthCategoryController::class, 'destroy'])->name('admin.bandwidth-categories.destroy');
    Route::get('/reseller/{reseller}/bandwidth', [ResellerController::class, 'bandwidthView'])->name('admin.reseller.bandwidth');
    Route::get('/reseller/{reseller}/bandwidth/adjustments', [ResellerController::class, 'bandwidthAdjustments'])->name('admin.reseller.bandwidth.adjustments');
    Route::post('/reseller/{reseller}/bandwidth/adjustments', [ResellerController::class, 'storeBandwidthAdjustment'])->name('admin.reseller.bandwidth.adjustments.store');
    Route::delete('/reseller/{reseller}/bandwidth/adjustments/{adjustment}', [ResellerController::class, 'destroyBandwidthAdjustment'])->name('admin.reseller.bandwidth.adjustments.destroy');
    Route::get('/reseller/{reseller}/bill-history', [ResellerController::class, 'billHistory'])->name('admin.reseller.bill-history');
    Route::get('/reseller/{reseller}/current-mac-invoice', [ResellerController::class, 'currentMonthMacInvoice'])->name('admin.reseller.current-mac-invoice');
    Route::get('/reseller/{reseller}/current-invoice', [ResellerController::class, 'currentMonthInvoice'])->name('admin.reseller.current-invoice');
    Route::get('/reseller-bills/{bill}/invoice', [ResellerController::class, 'invoiceBill'])->name('admin.reseller.invoice');
    Route::get('/reseller-bills/{bill}/download', [ResellerController::class, 'downloadInvoiceBill'])->name('admin.reseller.invoice.download');

    Route::get('/reseller/{reseller}/bill-payment', [ResellerController::class, 'billPayment'])->name('admin.reseller.bill-payment');
    Route::post('/reseller/{reseller}/bill-payment', [ResellerController::class, 'storeBillPayment'])->name('admin.reseller.bill-payment.store');
    Route::get('/reseller/{reseller}/generate-bill', [ResellerController::class, 'showGenerateBill'])->name('admin.reseller.generate-bill');
    Route::post('/reseller/{reseller}/generate-bill-now', [ResellerController::class, 'generateBillNow'])->name('admin.reseller.generate-bill-now');
    Route::post('/reseller/{reseller}/toggle-auto-bill', [ResellerController::class, 'toggleAutoBill'])->name('admin.reseller.toggle-auto-bill');
    Route::post('/reseller/{reseller}/toggle-recharge-enabled', [ResellerController::class, 'toggleRechargeEnabled'])->name('admin.reseller.toggle-recharge-enabled');
    Route::get('/reseller/{reseller}/recharge-history', [ResellerController::class, 'rechargeHistoryAjax'])->name('admin.reseller.recharge-history');
    Route::get('/reseller/{reseller}/client-transactions', [ResellerController::class, 'clientTransactions'])->name('admin.reseller.client-transactions');
    Route::post('/reseller/{reseller}/curtail-client', [ResellerController::class, 'curtailClient'])->name('admin.reseller.curtail-client');
    Route::post('/reseller/{reseller}/clients/{client}/reverse-recharge', [ResellerController::class, 'reverseClientRecharge'])->name('admin.reseller.client.reverse-recharge');
    Route::get('/reseller/{reseller}/reversed-transactions', [ResellerController::class, 'reversedTransactions'])->name('admin.reseller.reversed-transactions');
    Route::post('/reseller/{reseller}/sync-clients', [ResellerController::class, 'syncResellerClients'])->name('admin.reseller.sync-clients');
    Route::post('/reseller/sync-all-clients', [ResellerController::class, 'syncAllResellerClients'])->name('admin.reseller.sync-all-clients');
    Route::post('/reseller/{reseller}/login-as', [ResellerController::class, 'loginAsReseller'])->name('admin.reseller.login-as');
    Route::get('/reseller/{reseller}/features', [ResellerController::class, 'featureControl'])->name('admin.reseller.features');
    Route::post('/reseller/{reseller}/features', [ResellerController::class, 'saveFeatureControl'])->name('admin.reseller.features.save');

    Route::get('/agent/ledgers', [AgentController::class, 'allLedgers'])->name('admin.agent.ledgers');
    Route::get('/agent', [AgentController::class, 'index'])->name('admin.agent.index');
    Route::get('/agent/create', [AgentController::class, 'create'])->name('admin.agent.create');
    Route::post('/agent', [AgentController::class, 'store'])->name('admin.agent.store');
    Route::get('/agent/{id}/edit', [AgentController::class, 'edit'])->name('admin.agent.edit');
    Route::put('/agent/{id}', [AgentController::class, 'update'])->name('admin.agent.update');
    Route::delete('/agent/{id}', [AgentController::class, 'destroy'])->name('admin.agent.destroy');
    Route::post('/agent/{id}/toggle-status', [AgentController::class, 'toggleStatus'])->name('admin.agent.toggleStatus');
    Route::get('/agent/{id}/commission', [AgentController::class, 'commission'])->name('admin.agent.commission');
    Route::post('/agent/{id}/commission', [AgentController::class, 'storeCommission'])->name('admin.agent.commission.store');
    Route::delete('/agent/commission/{id}', [AgentController::class, 'destroyCommission'])->name('admin.agent.commission.destroy');
    Route::get('/agent/{id}/ledger', [AgentController::class, 'ledger'])->name('admin.agent.ledger');
    Route::post('/agent/{id}/pay', [AgentController::class, 'payAgent'])->name('admin.agent.pay');
    Route::get('/agent/{id}/resellers', [AgentController::class, 'resellers'])->name('admin.agent.resellers');
    Route::get('/agent/{id}/clients', [AgentController::class, 'clients'])->name('admin.agent.clients');

    Route::post('/impersonate/{user}', [ImpersonateController::class, 'loginAs'])->name('admin.impersonate');

    Route::get('/hrm/employees', [HrmController::class, 'employees'])->name('admin.hrm.employees');
    Route::post('/hrm/employees', [HrmController::class, 'storeEmployee'])->name('admin.hrm.employees.store');
    Route::get('/hrm/employees/{employee}', [HrmController::class, 'showEmployee'])->name('admin.hrm.employees.show');
    Route::get('/hrm/employees/{employee}/edit', [HrmController::class, 'editEmployee'])->name('admin.hrm.employees.edit');
    Route::put('/hrm/employees/{employee}', [HrmController::class, 'updateEmployee'])->name('admin.hrm.employees.update');
    Route::delete('/hrm/employees/{employee}', [HrmController::class, 'destroyEmployee'])->name('admin.hrm.employees.destroy');
    Route::get('/hrm/employees/{employee}/cv', [HrmController::class, 'employeeCv'])->name('admin.hrm.employees.cv');
    Route::put('/hrm/employees/{employee}/permissions', [HrmController::class, 'saveEmployeePermissions'])->name('admin.hrm.employees.permissions');
    Route::put('/hrm/employees/{employee}/role', [HrmController::class, 'updateEmployeeRole'])->name('admin.hrm.employees.update-role');
    Route::post('/hrm/employees/{employee}/credentials', [HrmController::class, 'updateEmployeeCredentials'])->name('admin.hrm.employees.update-credentials');
    Route::post('/hrm/employees/{employee}/create-account', [HrmController::class, 'createEmployeeAccount'])->name('admin.hrm.employees.create-account');
    Route::post('/hrm/employees/{employee}/payment-method-perms', [HrmController::class, 'saveEmployeePaymentMethodPerms'])->name('admin.hrm.employees.payment-method-perms');
    Route::post('/hrm/employees/{employee}/toggle-login-access', [HrmController::class, 'toggleLoginAccess'])->name('admin.hrm.employees.toggle-login-access');
    Route::get('/hrm/attendance', [HrmController::class, 'attendance'])->name('admin.hrm.attendance');
    Route::post('/hrm/attendance', [HrmController::class, 'storeAttendance'])->name('admin.hrm.attendance.store');
    Route::get('/hrm/attendance-report', [HrmController::class, 'attendanceReport'])->name('admin.hrm.attendance.report');

    Route::get('/hrm/selfie-attendance', [\App\Http\Controllers\Admin\SelfieAttendanceController::class, 'index'])->name('admin.hrm.selfie-attendance');
    Route::post('/hrm/selfie-attendance', [\App\Http\Controllers\Admin\SelfieAttendanceController::class, 'store'])->name('admin.hrm.selfie-attendance.store');
    Route::delete('/hrm/selfie-attendance/{id}', [\App\Http\Controllers\Admin\SelfieAttendanceController::class, 'destroy'])->name('admin.hrm.selfie-attendance.destroy');
    Route::get('/hrm/selfie-attendance-report', [\App\Http\Controllers\Admin\SelfieAttendanceController::class, 'report'])->name('admin.hrm.selfie-attendance.report');
    Route::get('/hrm/selfie-attendance-map', [\App\Http\Controllers\Admin\SelfieAttendanceController::class, 'map'])->name('admin.hrm.selfie-attendance.map');
    Route::get('/hrm/selfie-attendance-settings', [\App\Http\Controllers\Admin\SelfieAttendanceController::class, 'settings'])->name('admin.hrm.selfie-attendance.settings');
    Route::post('/hrm/selfie-attendance-settings', [\App\Http\Controllers\Admin\SelfieAttendanceController::class, 'saveSettings'])->name('admin.hrm.selfie-attendance.settings.save');
    Route::get('/hrm/salary', [HrmController::class, 'salary'])->name('admin.hrm.salary');
    Route::post('/hrm/salary/process', [HrmController::class, 'processSalary'])->name('admin.hrm.salary.process');
    Route::get('/hrm/salary/{salary}/edit', [HrmController::class, 'editSalary'])->name('admin.hrm.salary.edit');
    Route::put('/hrm/salary/{salary}', [HrmController::class, 'updateSalary'])->name('admin.hrm.salary.update');
    Route::delete('/hrm/salary/{salary}', [HrmController::class, 'destroySalary'])->name('admin.hrm.salary.destroy');
    Route::get('/hrm/departments', [HrmController::class, 'departments'])->name('admin.hrm.departments');
    Route::post('/hrm/departments', [HrmController::class, 'storeDepartment'])->name('admin.hrm.departments.store');
    Route::put('/hrm/departments/{department}', [HrmController::class, 'updateDepartment'])->name('admin.hrm.departments.update');
    Route::delete('/hrm/departments/{department}', [HrmController::class, 'destroyDepartment'])->name('admin.hrm.departments.destroy');
    Route::get('/hrm/designations', [HrmController::class, 'designations'])->name('admin.hrm.designations');
    Route::post('/hrm/designations', [HrmController::class, 'storeDesignation'])->name('admin.hrm.designations.store');
    Route::put('/hrm/designations/{designation}', [HrmController::class, 'updateDesignation'])->name('admin.hrm.designations.update');
    Route::delete('/hrm/designations/{designation}', [HrmController::class, 'destroyDesignation'])->name('admin.hrm.designations.destroy');
    Route::get('/hrm/api/designations-by-department/{department}', function(\App\Models\Department $department) {
        // Step 1: Department-specific designations (exact match)
        $designations = \App\Models\Designation::where('department_id', $department->id)
            ->where('is_active', true)->select('id', 'name')->orderBy('name')->get();

        if ($designations->isNotEmpty()) {
            return $designations;
        }

        // Step 2: Keyword-based fallback — match department name to generic departments
        $keywordMap = [
            'noc'            => 'Network Operations Department (NOC)',
            'network'        => 'Network Operations Department (NOC)',
            'technical'      => 'Technical Support Department',
            'support'        => 'Technical Support Department',
            'customer'       => 'Customer Support / Customer Service',
            'service'        => 'Customer Support / Customer Service',
            'sales'          => 'Sales & Marketing Department',
            'marketing'      => 'Sales & Marketing Department',
            'account'        => 'Billing & Accounts Department',
            'billing'        => 'Billing & Accounts Department',
            'finance'        => 'Billing & Accounts Department',
            'it'             => 'System / IT Department',
            'system'         => 'System / IT Department',
            'software'       => 'System / IT Department',
            'admin'          => 'Management / Administration',
            'management'     => 'Management / Administration',
            'hr'             => 'Management / Administration',
            'administration' => 'Management / Administration',
        ];

        $deptNameLower = strtolower($department->name);
        $matchedGenericDept = null;
        foreach ($keywordMap as $keyword => $genericDeptName) {
            if (str_contains($deptNameLower, $keyword)) {
                $matchedGenericDept = $genericDeptName;
                break;
            }
        }

        if ($matchedGenericDept) {
            $genericDept = \App\Models\Department::where('name', $matchedGenericDept)->whereNull('company_id')->first();
            if ($genericDept) {
                $designations = \App\Models\Designation::where('department_id', $genericDept->id)
                    ->where('is_active', true)->select('id', 'name')->orderBy('name')->get();
                if ($designations->isNotEmpty()) {
                    return $designations;
                }
            }
        }

        // Step 3: All active designations for the company as last resort
        $companyId = $department->company_id;
        return \App\Models\Designation::where(function ($q) use ($companyId) {
                $q->where('company_id', $companyId)->orWhereNull('company_id');
            })
            ->whereNull('department_id')
            ->where('is_active', true)->select('id', 'name')->orderBy('name')->get();
    })->name('admin.hrm.api.designations-by-department');
    Route::get('/hrm/leave', [HrmController::class, 'leave'])->name('admin.hrm.leave');
    Route::post('/hrm/leave', [HrmController::class, 'storeLeave'])->name('admin.hrm.leave.store');
    Route::post('/hrm/leave/{leave}/approve', [HrmController::class, 'approveLeave'])->name('admin.hrm.leave.approve');
    Route::post('/hrm/leave/{leave}/reject', [HrmController::class, 'rejectLeave'])->name('admin.hrm.leave.reject');
    Route::get('/hrm/staff-dues', [HrmController::class, 'staffDues'])->name('admin.hrm.staff-dues');
    Route::get('/hrm/staff-dues/{user}/receive', [HrmController::class, 'receiveDues'])->name('admin.hrm.receive-dues');
    Route::post('/hrm/staff-dues/{user}/receive', [HrmController::class, 'storeReceipt'])->name('admin.hrm.store-receipt');

    Route::get('/hrm/technicians', [TechnicianController::class, 'index'])->name('admin.hrm.technicians');
    Route::post('/hrm/technicians', [TechnicianController::class, 'store'])->name('admin.hrm.technicians.store');
    Route::get('/hrm/technicians/performance', [TechnicianController::class, 'performanceReport'])->name('admin.hrm.technicians.performance');
    Route::get('/hrm/technicians/{technician}', [TechnicianController::class, 'show'])->name('admin.hrm.technicians.show');
    Route::get('/hrm/technicians/{technician}/edit', [TechnicianController::class, 'edit'])->name('admin.hrm.technicians.edit');
    Route::put('/hrm/technicians/{technician}', [TechnicianController::class, 'update'])->name('admin.hrm.technicians.update');
    Route::delete('/hrm/technicians/{technician}', [TechnicianController::class, 'destroy'])->name('admin.hrm.technicians.destroy');

    Route::get('/staff-dues', [StaffDuesController::class, 'index'])->name('admin.staff-dues.index');
    Route::get('/staff-dues/{user}', [StaffDuesController::class, 'show'])->name('admin.staff-dues.show');
    Route::post('/staff-dues/{user}/receive', [StaffDuesController::class, 'receivePayment'])->name('admin.staff-dues.receive');
    Route::post('/staff-dues/{user}/clear-all', [StaffDuesController::class, 'clearAllDues'])->name('admin.staff-dues.clear-all');
    Route::post('/staff-dues/submissions/{submission}/approve', [StaffDuesController::class, 'approveSubmission'])->name('admin.staff-dues.approve-submission');
    Route::get('/my-collections', [StaffDuesController::class, 'myCollections'])->name('admin.my-collections.index');
    Route::post('/my-collections/submit', [StaffDuesController::class, 'mySubmitToAdmin'])->name('admin.my-collections.submit');

    Route::get('/sms/templates', [SmsController::class, 'templates'])->name('admin.sms.templates');
    Route::post('/sms/templates', [SmsController::class, 'storeTemplate'])->name('admin.sms.templates.store');
    Route::put('/sms/templates/{template}', [SmsController::class, 'updateTemplate'])->name('admin.sms.templates.update');
    Route::delete('/sms/templates/{template}', [SmsController::class, 'destroyTemplate'])->name('admin.sms.templates.destroy');
    Route::post('/sms/auto-templates', [SmsController::class, 'saveAutoTemplates'])->name('admin.sms.auto-templates.save');
    Route::get('/sms/bulk', [SmsController::class, 'bulk'])->name('admin.sms.bulk');
    Route::post('/sms/bulk', [SmsController::class, 'sendBulk'])->name('admin.sms.bulk.send');
    Route::get('/sms/logs', [SmsController::class, 'logs'])->name('admin.sms.logs');
    Route::get('/sms/auto-process', [SmsController::class, 'autoProcess'])->name('admin.sms.auto-process');
    Route::post('/sms/auto-process', [SmsController::class, 'saveAutoProcess'])->name('admin.sms.auto-process.save');
    
    Route::get('/settings/sms', [\App\Http\Controllers\Admin\SmsSettingsController::class, 'index'])->name('admin.settings.sms');
    Route::put('/settings/sms', [\App\Http\Controllers\Admin\SmsSettingsController::class, 'update'])->name('admin.settings.sms.update');
    Route::get('/settings/sms/balance', [\App\Http\Controllers\Admin\SmsSettingsController::class, 'checkBalance'])->name('admin.settings.sms.balance');
    Route::get('/settings/sms/status', [\App\Http\Controllers\Admin\SmsSettingsController::class, 'getApiStatus'])->name('admin.settings.sms.status');
    Route::post('/settings/sms/test', [\App\Http\Controllers\Admin\SmsSettingsController::class, 'testSms'])->name('admin.settings.sms.test');
    Route::get('/settings/sms/logs', [\App\Http\Controllers\Admin\SmsSettingsController::class, 'logs'])->name('admin.settings.sms.logs');

    Route::get('/mikrotik/logs', [\App\Http\Controllers\Admin\MikrotikLogController::class, 'index'])->name('admin.mikrotik.logs');
    Route::post('/mikrotik/retry-failed', [\App\Http\Controllers\Admin\MikrotikLogController::class, 'retryFailed'])->name('admin.mikrotik.retry-failed');
    Route::get('/mikrotik/logs/{log}', [\App\Http\Controllers\Admin\MikrotikLogController::class, 'show'])->name('admin.mikrotik.logs.show');
    Route::get('/mikrotik/stats', [\App\Http\Controllers\Admin\MikrotikLogController::class, 'stats'])->name('admin.mikrotik.stats');

    Route::get('/reports/daily', [ReportController::class, 'daily'])->name('admin.reports.daily');
    Route::get('/reports/monthly', [ReportController::class, 'monthly'])->name('admin.reports.monthly');
    Route::get('/reports/due', [ReportController::class, 'due'])->name('admin.reports.due');
    Route::get('/reports/client-ledger', [ReportController::class, 'clientLedger'])->name('admin.reports.client-ledger');
    Route::get('/reports/zone', [ReportController::class, 'zone'])->name('admin.reports.zone');
    Route::get('/reports/employee-collection', [ReportController::class, 'employeeCollection'])->name('admin.reports.employee-collection');
    Route::get('/reports/profit-loss', [ReportController::class, 'profitLoss'])->name('admin.reports.profit-loss');
    Route::get('/reports/income-statement', [ReportController::class, 'incomeStatement'])->name('admin.reports.income-statement');
    Route::get('/reports/btrc-format', [ReportController::class, 'btrcFormat'])->name('admin.reports.btrc-format');
    Route::get('/reports/stock', [ReportController::class, 'stock'])->name('admin.reports.stock');
    Route::get('/reports/reseller', [ReportController::class, 'resellerReport'])->name('admin.reports.reseller');
    Route::get('/reports/export/pdf', [ReportController::class, 'exportPdf'])->name('admin.reports.export.pdf');
    Route::get('/reports/export/excel', [ReportController::class, 'exportExcel'])->name('admin.reports.export.excel');
    Route::get('/reports/technician-leakage', [ReportController::class, 'technicianLeakage'])->name('admin.reports.technician-leakage');

    // BTRC Compliance Reports
    Route::prefix('btrc')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\BtrcReportController::class, 'index'])->name('admin.btrc.index');
        Route::get('/subscriber-list', [\App\Http\Controllers\Admin\BtrcReportController::class, 'subscriberList'])->name('admin.btrc.subscriber-list');
        Route::get('/new-connections', [\App\Http\Controllers\Admin\BtrcReportController::class, 'newConnections'])->name('admin.btrc.new-connections');
        Route::get('/disconnections', [\App\Http\Controllers\Admin\BtrcReportController::class, 'disconnections'])->name('admin.btrc.disconnections');
        Route::get('/ip-assignment', [\App\Http\Controllers\Admin\BtrcReportController::class, 'ipAssignment'])->name('admin.btrc.ip-assignment');
        Route::get('/monthly-revenue', [\App\Http\Controllers\Admin\BtrcReportController::class, 'monthlyRevenue'])->name('admin.btrc.monthly-revenue');
        Route::get('/official-format', [\App\Http\Controllers\Admin\BtrcReportController::class, 'btrcOfficialFormat'])->name('admin.btrc.official-format');
    });

    // Zone Analytics & Problem Graphs
    Route::prefix('zone-analytics')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\ZoneAnalyticsController::class, 'index'])->name('admin.zone-analytics.index');
        Route::get('/problems', [\App\Http\Controllers\Admin\ZoneAnalyticsController::class, 'getZoneProblems'])->name('admin.zone-analytics.problems');
        Route::get('/collection', [\App\Http\Controllers\Admin\ZoneAnalyticsController::class, 'getZoneCollection'])->name('admin.zone-analytics.collection');
        Route::get('/distribution', [\App\Http\Controllers\Admin\ZoneAnalyticsController::class, 'getClientDistribution'])->name('admin.zone-analytics.distribution');
        Route::get('/trend', [\App\Http\Controllers\Admin\ZoneAnalyticsController::class, 'getZoneTrend'])->name('admin.zone-analytics.trend');
    });

    Route::prefix('overrides')->group(function () {
        Route::get('/', [AdminOverrideController::class, 'index'])->name('admin.overrides.index');
        Route::get('/history', [AdminOverrideController::class, 'overrideHistory'])->name('admin.overrides.history');
        Route::get('/audit-log', [AdminOverrideController::class, 'auditLog'])->name('admin.overrides.audit-log');
        Route::get('/pending-syncs', [AdminOverrideController::class, 'pendingNetworkSyncs'])->name('admin.overrides.pending-syncs');
        Route::get('/{actionLog}', [AdminOverrideController::class, 'show'])->name('admin.overrides.show');
        Route::post('/{actionLog}/payment', [AdminOverrideController::class, 'overridePayment'])->name('admin.overrides.payment');
        Route::post('/{actionLog}/cash', [AdminOverrideController::class, 'overrideCashSettlement'])->name('admin.overrides.cash');
        Route::post('/{actionLog}/bill', [AdminOverrideController::class, 'overrideBill'])->name('admin.overrides.bill');
        Route::post('/{actionLog}/status', [AdminOverrideController::class, 'overrideClientStatus'])->name('admin.overrides.status');
        Route::post('/{actionLog}/data', [AdminOverrideController::class, 'overrideClientData'])->name('admin.overrides.data');
        Route::post('/{actionLog}/network', [AdminOverrideController::class, 'overrideNetworkAction'])->name('admin.overrides.network');
    });

    Route::prefix('super-admin')->group(function () {
        Route::get('/access-panel', [SuperAdminController::class, 'accessPanel'])->name('admin.super-admin.access-panel');
        Route::post('/sync-permissions', [SuperAdminController::class, 'syncPermissions'])->name('admin.super-admin.sync-permissions');
        Route::get('/roles', [SuperAdminController::class, 'listRoles'])->name('admin.super-admin.roles');
        Route::post('/roles', [SuperAdminController::class, 'createRole'])->name('admin.super-admin.roles.create');
        Route::put('/roles/{roleId}', [SuperAdminController::class, 'editRole'])->name('admin.super-admin.roles.edit');
        Route::delete('/roles/{roleId}', [SuperAdminController::class, 'deleteRole'])->name('admin.super-admin.roles.delete');
        Route::get('/roles/{roleId}/permissions', [SuperAdminController::class, 'getRolePermissions'])->name('admin.super-admin.roles.permissions');
        Route::put('/roles/{roleId}/permissions', [SuperAdminController::class, 'updateRolePermissions'])->name('admin.super-admin.roles.permissions.update');
        Route::post('/permissions/grant', [SuperAdminController::class, 'grantSinglePermission'])->name('admin.super-admin.permissions.grant');
        Route::post('/permissions/revoke', [SuperAdminController::class, 'revokeSinglePermission'])->name('admin.super-admin.permissions.revoke');
        Route::get('/audit-log', [SuperAdminController::class, 'auditLog'])->name('admin.super-admin.audit-log');
        Route::get('/users/{userId}/permissions', [SuperAdminController::class, 'getUserPermissions'])->name('admin.super-admin.users.permissions');
        Route::put('/users/{userId}/permissions', [SuperAdminController::class, 'updateUserPermissions'])->name('admin.super-admin.users.permissions.update');
        Route::post('/users/{userId}/assign-role', [SuperAdminController::class, 'assignRoleToUserById'])->name('admin.super-admin.users.assign-role-by-id');
        Route::post('/users/assign-role', [SuperAdminController::class, 'assignRoleToUser'])->name('admin.super-admin.users.assign-role');
        Route::post('/users/remove-role', [SuperAdminController::class, 'removeRoleFromUser'])->name('admin.super-admin.users.remove-role');
        Route::get('/footer-settings', [SuperAdminController::class, 'footerSettings'])->name('superadmin.footer-settings');
        Route::post('/footer-settings', [SuperAdminController::class, 'updateFooterSettings'])->name('superadmin.footer-settings.update');
        Route::post('/employees/{employeeId}/create-account', [SuperAdminController::class, 'createEmployeeAccount'])->name('admin.super-admin.employees.create-account');
        
        Route::get('/integrity', [SuperAdminController::class, 'integrityDashboard'])->name('admin.super-admin.integrity');
        Route::post('/integrity/run', [SuperAdminController::class, 'runIntegrityCheck'])->name('admin.super-admin.integrity.run');
        Route::get('/integrity/history', [SuperAdminController::class, 'integrityCheckHistory'])->name('admin.super-admin.integrity.history');
        Route::get('/integrity/{logId}', [SuperAdminController::class, 'integrityCheckDetails'])->name('admin.super-admin.integrity.details');
    });

    // System Logs (Super Admin Only)
    Route::prefix('system')->group(function () {
        Route::get('/logs', [\App\Http\Controllers\Admin\SystemLogController::class, 'index'])->name('admin.system.logs');
        Route::post('/logs/clear', [\App\Http\Controllers\Admin\SystemLogController::class, 'clear'])->name('admin.system.logs.clear');
        Route::get('/logs/download', [\App\Http\Controllers\Admin\SystemLogController::class, 'download'])->name('admin.system.logs.download');
        Route::post('/logs/test', [\App\Http\Controllers\Admin\SystemLogController::class, 'test'])->name('admin.system.logs.test');
    });

    Route::get('/reports/aging-analysis', [ReportController::class, 'agingAnalysis'])->name('admin.reports.aging-analysis');
    Route::get('/reports/advance-vs-due', [ReportController::class, 'advanceVsDue'])->name('admin.reports.advance-vs-due');
    Route::get('/reports/advance-liability', [ReportController::class, 'advanceLiability'])->name('admin.reports.advance-liability');
    Route::get('/api/dashboard-summary', [ReportController::class, 'dashboardSummary'])->name('admin.api.dashboard-summary');

    // ── Unified Permission Management ────────────────────────────────────────
    Route::get('/permissions', [\App\Http\Controllers\Admin\PermissionController::class, 'index'])->name('admin.permissions.index');
    Route::get('/permissions/roles/{roleId}', [\App\Http\Controllers\Admin\PermissionController::class, 'getRoleData'])->name('admin.permissions.role.data');
    Route::post('/permissions/roles/{roleId}/menus', [\App\Http\Controllers\Admin\PermissionController::class, 'saveRoleMenus'])->name('admin.permissions.role.menus.save');
    Route::post('/permissions/roles/{roleId}/actions', [\App\Http\Controllers\Admin\PermissionController::class, 'saveRoleActions'])->name('admin.permissions.role.actions.save');
    Route::delete('/permissions/roles/{roleId}/reset', [\App\Http\Controllers\Admin\PermissionController::class, 'resetRole'])->name('admin.permissions.role.reset');
    Route::get('/permissions/users/{userId}', [\App\Http\Controllers\Admin\PermissionController::class, 'getUserData'])->name('admin.permissions.user.data');
    Route::post('/permissions/users/{userId}/overrides', [\App\Http\Controllers\Admin\PermissionController::class, 'saveUserOverrides'])->name('admin.permissions.user.save');
    Route::delete('/permissions/users/{userId}/overrides', [\App\Http\Controllers\Admin\PermissionController::class, 'clearUserOverrides'])->name('admin.permissions.user.clear');

    // ── Legacy routes (redirect to new unified page) ─────────────────────────
    Route::get('/menu-permissions', fn() => redirect()->route('admin.permissions.index'))->name('admin.menu-permissions.index');
    Route::put('/menu-permissions', [MenuPermissionController::class, 'update'])->name('admin.menu-permissions.update');
    Route::put('/menu-permissions/actions', [MenuPermissionController::class, 'updateActions'])->name('admin.menu-permissions.update-actions');

    Route::get('/role-permissions', fn() => redirect()->route('admin.permissions.index'))->name('admin.settings.role-permissions');
    Route::put('/role-permissions', [\App\Http\Controllers\Admin\RolePermissionController::class, 'update'])->name('admin.settings.role-permissions.update');
    Route::post('/role-permissions/apply-to-all', [\App\Http\Controllers\Admin\RolePermissionController::class, 'applyToAll'])->name('admin.settings.role-permissions.apply-to-all');

    Route::get('/settings/company', [SettingsController::class, 'company'])->name('admin.settings.company');
    Route::post('/settings/company', [SettingsController::class, 'updateCompany'])->name('admin.settings.company.update');
    Route::get('/settings/packages', [SettingsController::class, 'packages'])->name('admin.settings.packages');
    Route::post('/settings/packages', [SettingsController::class, 'storePackage'])->name('admin.settings.packages.store');
    Route::post('/settings/packages/bulk-delete', [SettingsController::class, 'bulkDeletePackages'])->name('admin.settings.packages.bulk-delete');
    Route::post('/settings/packages/import-from-router', [SettingsController::class, 'importPackagesFromRouter'])->name('admin.settings.packages.import-from-router');
    Route::post('/settings/packages/sync-to-router', [SettingsController::class, 'syncPackagesToRouter'])->name('admin.settings.packages.sync-to-router');
    Route::put('/settings/packages/{package}', [SettingsController::class, 'updatePackage'])->name('admin.settings.packages.update');
    Route::delete('/settings/packages/{package}', [SettingsController::class, 'destroyPackage'])->name('admin.settings.packages.destroy');
    Route::get('/settings/mikrotik-profiles', [\App\Http\Controllers\Admin\MikrotikProfileController::class, 'index'])->name('admin.settings.mikrotik-profiles');
    Route::post('/settings/mikrotik-profiles', [\App\Http\Controllers\Admin\MikrotikProfileController::class, 'store'])->name('admin.settings.mikrotik-profiles.store');
    Route::post('/settings/mikrotik-profiles/sync-from-router', [\App\Http\Controllers\Admin\MikrotikProfileController::class, 'syncFromRouter'])->name('admin.settings.mikrotik-profiles.sync-from-router');
    Route::post('/settings/mikrotik-profiles/push-to-router', [\App\Http\Controllers\Admin\MikrotikProfileController::class, 'pushToRouter'])->name('admin.settings.mikrotik-profiles.push-to-router');
    Route::post('/settings/mikrotik-profiles/bulk-delete', [\App\Http\Controllers\Admin\MikrotikProfileController::class, 'bulkDelete'])->name('admin.settings.mikrotik-profiles.bulk-delete');
    Route::put('/settings/mikrotik-profiles/{mikrotikProfile}', [\App\Http\Controllers\Admin\MikrotikProfileController::class, 'update'])->name('admin.settings.mikrotik-profiles.update');
    Route::delete('/settings/mikrotik-profiles/{mikrotikProfile}', [\App\Http\Controllers\Admin\MikrotikProfileController::class, 'destroy'])->name('admin.settings.mikrotik-profiles.destroy');

    Route::get('/settings/zones', [SettingsController::class, 'zones'])->name('admin.settings.zones');
    Route::post('/settings/zones', [SettingsController::class, 'storeZone'])->name('admin.settings.zones.store');
    Route::post('/settings/zones/bulk-delete', [SettingsController::class, 'bulkDeleteZones'])->name('admin.settings.zones.bulk-delete');
    Route::put('/settings/zones/{zone}', [SettingsController::class, 'updateZone'])->name('admin.settings.zones.update');
    Route::delete('/settings/zones/{zone}', [SettingsController::class, 'destroyZone'])->name('admin.settings.zones.destroy');
    Route::get('/settings/payment-methods', [SettingsController::class, 'paymentMethods'])->name('admin.settings.payment-methods');
    Route::post('/settings/payment-methods', [SettingsController::class, 'storePaymentMethod'])->name('admin.settings.payment-methods.store');
    Route::post('/settings/payment-methods/bulk-delete', [SettingsController::class, 'bulkDeletePaymentMethods'])->name('admin.settings.payment-methods.bulk-delete');
    Route::post('/settings/payment-methods/role-perms', [SettingsController::class, 'savePaymentMethodRolePerms'])->name('admin.settings.payment-methods.role-perms');
    Route::put('/settings/payment-methods/{paymentMethod}', [SettingsController::class, 'updatePaymentMethod'])->name('admin.settings.payment-methods.update');
    Route::delete('/settings/payment-methods/{paymentMethod}', [SettingsController::class, 'destroyPaymentMethod'])->name('admin.settings.payment-methods.destroy');
    Route::get('/settings/system', [SettingsController::class, 'system'])->name('admin.settings.system');
    Route::post('/settings/system', [SettingsController::class, 'updateSystem'])->name('admin.settings.system.update');

    Route::get('/settings/dropdown-options', [\App\Http\Controllers\Admin\DropdownOptionController::class, 'index'])->name('admin.settings.dropdown-options');
    Route::post('/settings/dropdown-options', [\App\Http\Controllers\Admin\DropdownOptionController::class, 'store'])->name('admin.settings.dropdown-options.store');
    Route::put('/settings/dropdown-options/{dropdownOption}', [\App\Http\Controllers\Admin\DropdownOptionController::class, 'update'])->name('admin.settings.dropdown-options.update');
    Route::post('/settings/dropdown-options/{dropdownOption}/toggle', [\App\Http\Controllers\Admin\DropdownOptionController::class, 'toggleActive'])->name('admin.settings.dropdown-options.toggle');
    Route::delete('/settings/dropdown-options/{dropdownOption}', [\App\Http\Controllers\Admin\DropdownOptionController::class, 'destroy'])->name('admin.settings.dropdown-options.destroy');
    Route::post('/settings/dropdown-options/reorder', [\App\Http\Controllers\Admin\DropdownOptionController::class, 'reorder'])->name('admin.settings.dropdown-options.reorder');
    Route::post('/settings/test-telegram', [SettingsController::class, 'testTelegram'])->name('admin.settings.test-telegram');
    Route::get('/settings/areas', [SettingsController::class, 'areas'])->name('admin.settings.areas');
    Route::post('/settings/areas', [SettingsController::class, 'storeArea'])->name('admin.settings.areas.store');
    Route::post('/settings/areas/bulk-delete', [SettingsController::class, 'bulkDeleteAreas'])->name('admin.settings.areas.bulk-delete');
    Route::put('/settings/areas/{area}', [SettingsController::class, 'updateArea'])->name('admin.settings.areas.update');
    Route::delete('/settings/areas/{area}', [SettingsController::class, 'destroyArea'])->name('admin.settings.areas.destroy');
    Route::get('/settings/logo', [SettingsController::class, 'logo'])->name('admin.settings.logo');
    Route::post('/settings/logo', [SettingsController::class, 'updateLogo'])->name('admin.settings.logo.update');
    Route::delete('/settings/logo/{type}', [SettingsController::class, 'removeLogo'])->name('admin.settings.logo.remove');
    Route::delete('/settings/company/logo/{type}', [SettingsController::class, 'removeLogo'])->name('admin.settings.company.logo.remove');
    Route::get('/settings/payment', [SettingsController::class, 'paymentSettings'])->name('admin.settings.payment');
    Route::post('/settings/payment', [SettingsController::class, 'updatePaymentSettings'])->name('admin.settings.payment.update');
    Route::post('/settings/payment/api', [SettingsController::class, 'updatePaymentApiSettings'])->name('admin.settings.payment.api.update');
    Route::post('/settings/payment/sslcommerz', [SettingsController::class, 'updateSslCommerzSettings'])->name('admin.settings.payment.sslcommerz.update');
    Route::post('/settings/payment/sslcommerz/test', [SettingsController::class, 'testSslConnection'])->name('admin.settings.payment.sslcommerz.test');
    Route::post('/settings/payment/nagad', [SettingsController::class, 'updateNagadSettings'])->name('admin.settings.payment.nagad.update');
    Route::post('/settings/payment/nagad/test', [SettingsController::class, 'testNagadConnection'])->name('admin.settings.payment.nagad.test');
    Route::post('/settings/payment/bkash/test', [SettingsController::class, 'testBkashConnection'])->name('admin.settings.payment.bkash.test');
    Route::post('/settings/payment/webhook', [SettingsController::class, 'updateWebhookSettings'])->name('admin.settings.payment.webhook.update');
    Route::get('/webhook-payment-logs', [SettingsController::class, 'webhookPaymentLogs'])->name('admin.webhook.payment.logs');

    // Configuration Routes
    Route::get('/config/box', [SettingsController::class, 'box'])->name('admin.config.box');
    Route::post('/config/box', [SettingsController::class, 'storeBox'])->name('admin.config.box.store');
    Route::post('/config/box/bulk-delete', [SettingsController::class, 'bulkDeleteBoxes'])->name('admin.config.box.bulk-delete');
    Route::put('/config/box/{box}', [SettingsController::class, 'updateBox'])->name('admin.config.box.update');
    Route::delete('/config/box/{box}', [SettingsController::class, 'destroyBox'])->name('admin.config.box.destroy');
    Route::get('/config/connection-type', [SettingsController::class, 'connectionType'])->name('admin.config.connection-type');
    Route::post('/config/connection-type', [SettingsController::class, 'storeConnectionType'])->name('admin.config.connection-type.store');
    Route::get('/config/client-type', [SettingsController::class, 'clientType'])->name('admin.config.client-type');
    Route::post('/config/client-type', [SettingsController::class, 'storeClientType'])->name('admin.config.client-type.store');
    Route::get('/config/protocol-type', [SettingsController::class, 'protocolType'])->name('admin.config.protocol-type');
    Route::post('/config/protocol-type', [SettingsController::class, 'storeProtocolType'])->name('admin.config.protocol-type.store');
    Route::get('/config/area', [SettingsController::class, 'areaManagement'])->name('admin.config.area');
    Route::get('/config/district', [SettingsController::class, 'district'])->name('admin.config.district');
    Route::post('/config/district', [SettingsController::class, 'storeDistrict'])->name('admin.config.district.store');
    Route::post('/config/district/bulk-delete', [SettingsController::class, 'bulkDeleteDistricts'])->name('admin.config.district.bulk-delete');
    Route::post('/config/upazila/bulk-delete', [SettingsController::class, 'bulkDeleteUpazilas'])->name('admin.config.upazila.bulk-delete');
    Route::get('/config/upazila', [SettingsController::class, 'upazila'])->name('admin.config.upazila');
    Route::post('/config/upazila', [SettingsController::class, 'storeUpazila'])->name('admin.config.upazila.store');
    Route::put('/config/district/{district}', [SettingsController::class, 'updateDistrict'])->name('admin.config.district.update');
    Route::delete('/config/district/{district}', [SettingsController::class, 'destroyDistrict'])->name('admin.config.district.destroy');
    Route::put('/config/upazila/{upazila}', [SettingsController::class, 'updateUpazila'])->name('admin.config.upazila.update');
    Route::delete('/config/upazila/{upazila}', [SettingsController::class, 'destroyUpazila'])->name('admin.config.upazila.destroy');

    // Client Module Routes
    Route::get('/client/scheduler', [ClientController::class, 'scheduler'])->name('admin.client.scheduler');
    Route::post('/client/scheduler', [ClientController::class, 'storeSchedule'])->name('admin.client.scheduler.store');
    Route::put('/client/scheduler/{schedule}', [ClientController::class, 'updateSchedule'])->name('admin.client.scheduler.update');
    Route::delete('/client/scheduler/{schedule}', [ClientController::class, 'deleteSchedule'])->name('admin.client.scheduler.delete');
    Route::get('/client/portal', [ClientController::class, 'portalManage'])->name('admin.client.portal');
    Route::post('/client/portal', [ClientController::class, 'updatePortalSettings'])->name('admin.client.portal.update');
    Route::post('/client/portal/notices', [ClientController::class, 'storeNotice'])->name('admin.client.portal.notices.store');
    Route::delete('/client/portal/notices/{id}', [ClientController::class, 'deleteNotice'])->name('admin.client.portal.notices.delete');
    Route::patch('/client/portal/notices/{id}/toggle', [ClientController::class, 'toggleNotice'])->name('admin.client.portal.notices.toggle');

    // OLT Management Routes
    Route::get('/olt/users', [OltController::class, 'users'])->name('admin.olt.users');
    Route::get('/olt/onu-status', [OltController::class, 'onuStatusRedirect'])->name('admin.olt.onu-status');
    Route::get('/olt/onu-list', [OltController::class, 'onuList'])->name('admin.olt.onu-list');
    Route::get('/olt/onu-mismatches', [OltController::class, 'onuMismatches'])->name('admin.olt.onu-mismatches');
    Route::get('/olt/optical-loss-calculator', [OltController::class, 'opticalLossCalculator'])->name('admin.olt.optical-loss-calculator');
    Route::post('/olt/import-mismatch-user', [OltController::class, 'importMismatchUser'])->name('admin.olt.import-mismatch-user');

    // Network Diagram Routes
    Route::get('/network/distributed-items', [NetworkController::class, 'distributedItems'])->name('admin.network.distributed-items');
    Route::get('/network/map', [NetworkController::class, 'networkMap'])->name('admin.network.map');
    Route::get('/network/map/live-status', [NetworkController::class, 'mapLiveStatus'])->name('admin.network.map.live');
    Route::post('/network/map/update-location', [NetworkController::class, 'updateMapLocation'])->name('admin.network.map.update-location');
    
    // Network Topology Routes
    Route::get('/network/topology', [\App\Http\Controllers\Admin\NetworkTopologyController::class, 'index'])->name('admin.network.topology');
    Route::get('/network/topology/data', [\App\Http\Controllers\Admin\NetworkTopologyController::class, 'getHierarchy'])->name('admin.network.topology.data');
    Route::get('/network/splitters', [\App\Http\Controllers\Admin\NetworkTopologyController::class, 'splitters'])->name('admin.network.splitters');
    Route::post('/network/splitters', [\App\Http\Controllers\Admin\NetworkTopologyController::class, 'storeSplitter'])->name('admin.network.splitters.store');
    Route::put('/network/splitters/{splitter}', [\App\Http\Controllers\Admin\NetworkTopologyController::class, 'updateSplitter'])->name('admin.network.splitters.update');
    Route::delete('/network/splitters/{splitter}', [\App\Http\Controllers\Admin\NetworkTopologyController::class, 'destroySplitter'])->name('admin.network.splitters.destroy');

    // Events & Holidays Routes
    Route::get('/events', [SettingsController::class, 'events'])->name('admin.events.index');
    Route::post('/events', [SettingsController::class, 'storeEvent'])->name('admin.events.store');
    Route::put('/events/{event}', [SettingsController::class, 'updateEvent'])->name('admin.events.update');
    Route::delete('/events/{event}', [SettingsController::class, 'deleteEvent'])->name('admin.events.delete');
    Route::get('/events/holidays', [SettingsController::class, 'holidays'])->name('admin.events.holidays');
    Route::post('/events/holidays', [SettingsController::class, 'storeHoliday'])->name('admin.events.holidays.store');

    // Task Management Routes
    Route::get('/tasks', [SettingsController::class, 'tasks'])->name('admin.tasks.index');
    Route::get('/tasks/create', [SettingsController::class, 'createTask'])->name('admin.tasks.create');
    Route::get('/tasks/calendar', [SettingsController::class, 'taskCalendar'])->name('admin.tasks.calendar');
    Route::get('/tasks/calendar/json', [SettingsController::class, 'taskCalendarJson'])->name('admin.tasks.calendar.json');
    Route::post('/tasks', [SettingsController::class, 'storeTask'])->name('admin.tasks.store');
    Route::get('/tasks/{task}', [SettingsController::class, 'showTask'])->name('admin.tasks.show');
    Route::put('/tasks/{task}', [SettingsController::class, 'updateTask'])->name('admin.tasks.update');
    Route::post('/tasks/{task}/toggle', [SettingsController::class, 'toggleTaskStatus'])->name('admin.tasks.toggle');
    Route::delete('/tasks/{task}', [SettingsController::class, 'deleteTask'])->name('admin.tasks.delete');

    // Purchase Routes
    Route::get('/purchase/create', [StoreController::class, 'createPurchase'])->name('admin.purchase.create');
    Route::post('/purchase/create', [StoreController::class, 'storePurchase']);
    Route::post('/purchase/store', [StoreController::class, 'storePurchase'])->name('admin.purchase.store');
    Route::get('/store/purchases/{purchase}', [StoreController::class, 'showPurchase'])->name('admin.store.purchases.show');
    Route::delete('/store/purchases/{purchase}', [StoreController::class, 'destroyPurchase'])->name('admin.store.purchases.destroy');
    Route::post('/store/purchases/{purchase}/payment', [StoreController::class, 'recordPurchasePayment'])->name('admin.store.purchases.payment');
    Route::get('/purchase/vendors', [StoreController::class, 'vendors'])->name('admin.purchase.vendors');
    Route::post('/purchase/vendors', [StoreController::class, 'storeVendor'])->name('admin.purchase.vendors.store');
    Route::put('/purchase/vendors/{vendor}', [StoreController::class, 'updateVendor'])->name('admin.purchase.vendors.update');
    Route::delete('/purchase/vendors/{vendor}', [StoreController::class, 'destroyVendor'])->name('admin.purchase.vendors.destroy');
    Route::get('/purchase/vendors/{vendor}/ledger', [StoreController::class, 'vendorLedger'])->name('admin.purchase.vendors.ledger');
    Route::post('/purchase/vendors/{vendor}/payment', [StoreController::class, 'vendorPayment'])->name('admin.purchase.vendors.payment');

    // Sales & Service Routes
    Route::get('/sales', [StoreController::class, 'sales'])->name('admin.sales.index');
    Route::get('/sales/create', [StoreController::class, 'createSale'])->name('admin.sales.create');
    Route::get('/sales/services', [StoreController::class, 'services'])->name('admin.sales.services');
    Route::post('/sales/services', [StoreController::class, 'storeService'])->name('admin.sales.services.store');
    Route::put('/sales/services/{charge}', [StoreController::class, 'updateService'])->name('admin.sales.services.update');
    Route::delete('/sales/services/{charge}', [StoreController::class, 'deleteService'])->name('admin.sales.services.delete');
    Route::post('/sales/services/{charge}/pay', [StoreController::class, 'payService'])->name('admin.sales.services.pay');
    Route::post('/sales', [StoreController::class, 'storeSale'])->name('admin.sales.store');
    Route::get('/sales/{sale}', [StoreController::class, 'showSale'])->name('admin.sales.show');
    Route::delete('/sales/{sale}', [StoreController::class, 'destroySale'])->name('admin.sales.destroy');
    Route::post('/sales/{sale}/payment', [StoreController::class, 'recordSalePayment'])->name('admin.sales.payment');

    // Inventory Routes
    Route::get('/inventory/categories', [InventoryController::class, 'categories'])->name('admin.inventory.categories');
    Route::post('/inventory/categories', [InventoryController::class, 'storeCategory'])->name('admin.inventory.categories.store');
    Route::put('/inventory/categories/{id}', [InventoryController::class, 'updateCategory'])->name('admin.inventory.categories.update');
    Route::delete('/inventory/categories/{id}', [InventoryController::class, 'destroyCategory'])->name('admin.inventory.categories.destroy');

    // Inventory - Product Categories
    Route::get('/inventory/product-categories', [ProductCategoryController::class, 'index'])->name('admin.inventory.product-categories.index');
    Route::get('/inventory/product-categories/create', [ProductCategoryController::class, 'create'])->name('admin.inventory.product-categories.create');
    Route::post('/inventory/product-categories', [ProductCategoryController::class, 'store'])->name('admin.inventory.product-categories.store');
    Route::get('/inventory/product-categories/{id}/edit', [ProductCategoryController::class, 'edit'])->name('admin.inventory.product-categories.edit');
    Route::put('/inventory/product-categories/{id}', [ProductCategoryController::class, 'update'])->name('admin.inventory.product-categories.update');
    Route::delete('/inventory/product-categories/{id}', [ProductCategoryController::class, 'destroy'])->name('admin.inventory.product-categories.destroy');

    // Inventory - Products (must come before /{id} wildcard routes)
    Route::get('/inventory/products/history', [ProductController::class, 'combinedHistory'])->name('admin.inventory.products.history');
    Route::get('/inventory/products/in-history', [ProductController::class, 'inHistory'])->name('admin.inventory.products.in_history');
    Route::get('/inventory/products/out-history/{id}/edit', [ProductController::class, 'editOutHistory'])->name('admin.inventory.products.out_history.edit');
    Route::put('/inventory/products/out-history/{id}', [ProductController::class, 'updateOutHistory'])->name('admin.inventory.products.out_history.update');
    Route::delete('/inventory/products/out-history/{id}', [ProductController::class, 'deleteOutHistory'])->name('admin.inventory.products.out_history.delete');
    Route::get('/inventory/products/out-history', [ProductController::class, 'outHistory'])->name('admin.inventory.products.out_history');
    Route::get('/inventory/products/out', [ProductController::class, 'productOut'])->name('admin.inventory.products.out');
    Route::post('/inventory/products/out', [ProductController::class, 'storeProductOut'])->name('admin.inventory.products.out.store');
    Route::get('/inventory/products/out/search-clients', [ProductController::class, 'searchClients'])->name('admin.inventory.products.out.search-clients');
    Route::get('/inventory/products/get-available-serials', [ProductController::class, 'getAvailableSerials'])->name('admin.inventory.products.available-serials');
    Route::post('/inventory/products/check-serial-duplicate', [ProductController::class, 'checkSerialDuplicate'])->name('admin.inventory.check-serial-duplicate');
    Route::get('/inventory/products', [ProductController::class, 'index'])->name('admin.inventory.products.index');
    Route::get('/inventory/products/create', [ProductController::class, 'create'])->name('admin.inventory.products.create');
    Route::post('/inventory/products', [ProductController::class, 'store'])->name('admin.inventory.products.store');
    Route::get('/inventory/products/{id}/edit', [ProductController::class, 'edit'])->name('admin.inventory.products.edit');
    Route::put('/inventory/products/{id}', [ProductController::class, 'update'])->name('admin.inventory.products.update');
    Route::delete('/inventory/products/{id}', [ProductController::class, 'destroy'])->name('admin.inventory.products.destroy');

    // Inventory - Stock
    Route::get('/inventory/stock/summary', [StockController::class, 'summary'])->name('admin.inventory.stock.summary');
    Route::get('/inventory/stock/availability', [StockController::class, 'availability'])->name('admin.inventory.stock.availability');
    Route::get('/inventory/stock/serial-history/{productId}', [StockController::class, 'serialHistory'])->name('admin.inventory.stock.serial-history');

    // Inventory - Vendors
    Route::get('/inventory/vendors', [InventoryVendorController::class, 'index'])->name('admin.inventory.vendors.index');
    Route::get('/inventory/vendors/create', [InventoryVendorController::class, 'create'])->name('admin.inventory.vendors.create');
    Route::post('/inventory/vendors', [InventoryVendorController::class, 'store'])->name('admin.inventory.vendors.store');
    Route::get('/inventory/vendors/{vendor}/edit', [InventoryVendorController::class, 'edit'])->name('admin.inventory.vendors.edit');
    Route::put('/inventory/vendors/{vendor}', [InventoryVendorController::class, 'update'])->name('admin.inventory.vendors.update');
    Route::delete('/inventory/vendors/{vendor}', [InventoryVendorController::class, 'destroy'])->name('admin.inventory.vendors.destroy');

    // Inventory - Purchases
    Route::get('/inventory/purchases/create', [InventoryPurchaseController::class, 'create'])->name('admin.inventory.purchases.create');
    Route::get('/inventory/purchases', [InventoryPurchaseController::class, 'index'])->name('admin.inventory.purchases.index');
    Route::post('/inventory/purchases', [InventoryPurchaseController::class, 'store'])->name('admin.inventory.purchases.store');
    Route::get('/inventory/purchases/{id}/edit', [InventoryPurchaseController::class, 'edit'])->name('admin.inventory.purchases.edit');
    Route::put('/inventory/purchases/{id}', [InventoryPurchaseController::class, 'update'])->name('admin.inventory.purchases.update');
    Route::delete('/inventory/purchases/{id}', [InventoryPurchaseController::class, 'destroy'])->name('admin.inventory.purchases.destroy');
    Route::get('/inventory/purchases/{id}', [InventoryPurchaseController::class, 'show'])->name('admin.inventory.purchases.show');

    // Inventory - Damage
    Route::get('/inventory/damage/create', [DamageController::class, 'create'])->name('admin.inventory.damage.create');
    Route::get('/inventory/damage', [DamageController::class, 'index'])->name('admin.inventory.damage.index');
    Route::post('/inventory/damage', [DamageController::class, 'store'])->name('admin.inventory.damage.store');
    Route::get('/inventory/damage/{id}/edit', [DamageController::class, 'edit'])->name('admin.inventory.damage.edit');
    Route::put('/inventory/damage/{id}', [DamageController::class, 'update'])->name('admin.inventory.damage.update');
    Route::delete('/inventory/damage/{id}', [DamageController::class, 'destroy'])->name('admin.inventory.damage.destroy');
    Route::get('/inventory/damage/{id}', [DamageController::class, 'show'])->name('admin.inventory.damage.show');

    // Inventory - Fiber Stock
    Route::get('/inventory/fiber/history', [FiberStockController::class, 'combinedHistory'])->name('admin.inventory.fiber.history');
    Route::get('/inventory/fiber/in-history', [FiberStockController::class, 'fiberInHistory'])->name('admin.inventory.fiber.in_history');
    Route::get('/inventory/fiber/out-history/{id}/edit', [FiberStockController::class, 'editFiberOut'])->name('admin.inventory.fiber.out.edit');
    Route::put('/inventory/fiber/out-history/{id}', [FiberStockController::class, 'updateFiberOut'])->name('admin.inventory.fiber.out.update');
    Route::delete('/inventory/fiber/out-history/{id}', [FiberStockController::class, 'destroyFiberOut'])->name('admin.inventory.fiber.out.destroy');
    Route::get('/inventory/fiber/out-history', [FiberStockController::class, 'fiberOutHistory'])->name('admin.inventory.fiber.out_history');
    Route::get('/inventory/fiber/out/search-clients', [FiberStockController::class, 'searchClients'])->name('admin.inventory.fiber.search-clients');
    Route::get('/inventory/fiber/out', [FiberStockController::class, 'fiberOut'])->name('admin.inventory.fiber.out');
    Route::post('/inventory/fiber/out', [FiberStockController::class, 'storeFiberOut'])->name('admin.inventory.fiber.out.store');
    Route::get('/inventory/fiber/usage', [FiberStockController::class, 'usage'])->name('admin.inventory.fiber.usage');
    Route::post('/inventory/fiber/usage', [FiberStockController::class, 'storeUsage'])->name('admin.inventory.fiber.usage.store');
    Route::get('/inventory/fiber/analytics', [FiberStockController::class, 'analytics'])->name('admin.inventory.fiber.analytics');
    Route::get('/inventory/fiber/{id}/edit', [FiberStockController::class, 'edit'])->name('admin.inventory.fiber.edit');
    Route::put('/inventory/fiber/{id}', [FiberStockController::class, 'update'])->name('admin.inventory.fiber.update');
    Route::delete('/inventory/fiber/{id}', [FiberStockController::class, 'destroy'])->name('admin.inventory.fiber.destroy');
    Route::get('/inventory/fiber/{id}', [FiberStockController::class, 'show'])->name('admin.inventory.fiber.show');
    Route::get('/inventory/fiber', [FiberStockController::class, 'index'])->name('admin.inventory.fiber.index');
    Route::post('/inventory/fiber', [FiberStockController::class, 'store'])->name('admin.inventory.fiber.store');

    // Inventory - Instruments
    Route::get('/inventory/instrument-categories', [InstrumentController::class, 'categoriesPage'])->name('admin.inventory.instrument-categories.page');
    Route::get('/inventory/instrument-categories/list', [InstrumentController::class, 'categoryList'])->name('admin.inventory.instrument-categories.list');
    Route::post('/inventory/instrument-categories', [InstrumentController::class, 'categoryStore'])->name('admin.inventory.instrument-categories.store');
    Route::put('/inventory/instrument-categories/{id}', [InstrumentController::class, 'categoryUpdate'])->name('admin.inventory.instrument-categories.update');
    Route::delete('/inventory/instrument-categories/{id}', [InstrumentController::class, 'categoryDestroy'])->name('admin.inventory.instrument-categories.destroy');
    Route::get('/inventory/instruments/subcategories/{parentId}', [InstrumentController::class, 'getSubCategories'])->name('admin.inventory.instruments.subcategories');
    Route::post('/inventory/instruments/check-serial-duplicate', [InstrumentController::class, 'checkSerialDuplicate'])->name('admin.inventory.instruments.check-serial-duplicate');
    Route::post('/inventory/instruments/destroy-group', [InstrumentController::class, 'destroyGroup'])->name('admin.inventory.instruments.destroy-group');
    Route::get('/inventory/instruments/{id}/edit', [InstrumentController::class, 'edit'])->name('admin.inventory.instruments.edit');
    Route::put('/inventory/instruments/{id}', [InstrumentController::class, 'update'])->name('admin.inventory.instruments.update');
    Route::delete('/inventory/instruments/{id}', [InstrumentController::class, 'destroy'])->name('admin.inventory.instruments.destroy');
    Route::get('/inventory/instruments', [InstrumentController::class, 'index'])->name('admin.inventory.instruments.index');
    Route::post('/inventory/instruments', [InstrumentController::class, 'store'])->name('admin.inventory.instruments.store');

    // Inventory - Serial Management
    Route::get('/inventory/product-analyse', [\App\Http\Controllers\Admin\Inventory\StockController::class, 'productAnalyse'])->name('admin.inventory.product.analyse');
    Route::get('/inventory/serials/search', [SerialSearchController::class, 'index'])->name('admin.inventory.serial.search');
    Route::get('/inventory/serials/search/results', [SerialSearchController::class, 'search'])->name('admin.inventory.serial.search.results');
    Route::post('/inventory/serials/assign', [SerialAssignController::class, 'assign'])->name('admin.inventory.serial.assign');
    Route::post('/inventory/serials/return', [SerialAssignController::class, 'returnDevice'])->name('admin.inventory.serial.return');
    Route::post('/inventory/serials/reassign', [SerialAssignController::class, 'reassign'])->name('admin.inventory.serial.reassign');
    Route::post('/inventory/serials/damage', [SerialAssignController::class, 'markDamaged'])->name('admin.inventory.serial.damage');
    Route::get('/inventory/serials/{id}', [SerialSearchController::class, 'show'])->name('admin.inventory.serial.show');

    // SMS Service Routes
    Route::get('/sms/individual', [SmsController::class, 'individual'])->name('admin.sms.individual');
    Route::post('/sms/individual', [SmsController::class, 'sendIndividual'])->name('admin.sms.individual.send');
    Route::get('/sms/gateway', [SmsController::class, 'gateway'])->name('admin.sms.gateway');
    Route::post('/sms/gateway', [SmsController::class, 'updateGateway'])->name('admin.sms.gateway.update');
    
    Route::get('/applications', [\App\Http\Controllers\Admin\NewConnectionController::class, 'index'])->name('admin.applications.index');
    Route::get('/applications/create', [\App\Http\Controllers\Admin\NewConnectionController::class, 'create'])->name('admin.applications.create');
    Route::post('/applications', [\App\Http\Controllers\Admin\NewConnectionController::class, 'store'])->name('admin.applications.store');
    Route::get('/applications/my', [\App\Http\Controllers\Admin\NewConnectionController::class, 'myApplications'])->name('admin.applications.my');
    Route::get('/applications/pending', [\App\Http\Controllers\Admin\NewConnectionController::class, 'pending'])->name('admin.applications.pending');
    Route::get('/applications/export', [\App\Http\Controllers\Admin\NewConnectionController::class, 'export'])->name('admin.applications.export');
    Route::get('/applications/{application}', [\App\Http\Controllers\Admin\NewConnectionController::class, 'show'])->name('admin.applications.show');
    Route::get('/applications/{application}/edit', [\App\Http\Controllers\Admin\NewConnectionController::class, 'edit'])->name('admin.applications.edit');
    Route::put('/applications/{application}', [\App\Http\Controllers\Admin\NewConnectionController::class, 'update'])->name('admin.applications.update');
    Route::post('/applications/{application}/under-review', [\App\Http\Controllers\Admin\NewConnectionController::class, 'takeUnderReview'])->name('admin.applications.under-review');
    Route::post('/applications/{application}/request-correction', [\App\Http\Controllers\Admin\NewConnectionController::class, 'requestCorrection'])->name('admin.applications.request-correction');
    Route::post('/applications/{application}/approve', [\App\Http\Controllers\Admin\NewConnectionController::class, 'approve'])->name('admin.applications.approve');
    Route::post('/applications/{application}/reject', [\App\Http\Controllers\Admin\NewConnectionController::class, 'reject'])->name('admin.applications.reject');
    Route::post('/applications/{application}/verify-nid', [\App\Http\Controllers\Admin\NewConnectionController::class, 'verifyNid'])->name('admin.applications.verify-nid');
    Route::get('/applications/{application}/logs', [\App\Http\Controllers\Admin\NewConnectionController::class, 'statusLogs'])->name('admin.applications.logs');
    Route::delete('/applications/{application}', [\App\Http\Controllers\Admin\NewConnectionController::class, 'destroy'])->name('admin.applications.destroy');

    // System Health & Diagnostics
    Route::get('/system/health', [\App\Http\Controllers\Admin\SystemHealthController::class, 'index'])->name('admin.system.health');
    Route::post('/system/health/check', [\App\Http\Controllers\Admin\SystemHealthController::class, 'runCheck'])->name('admin.system.health.check');
    Route::post('/system/health/check-component', [\App\Http\Controllers\Admin\SystemHealthController::class, 'checkComponent'])->name('admin.system.health.check-component');
    Route::get('/system/failed-jobs', [\App\Http\Controllers\Admin\SystemHealthController::class, 'failedJobs'])->name('admin.system.failed-jobs');
    Route::post('/system/jobs/{id}/retry', [\App\Http\Controllers\Admin\SystemHealthController::class, 'retryJob'])->name('admin.system.jobs.retry');
    Route::post('/system/jobs/{id}/resolve', [\App\Http\Controllers\Admin\SystemHealthController::class, 'resolveJob'])->name('admin.system.jobs.resolve');
    Route::get('/system/diagnostics', [\App\Http\Controllers\Admin\SystemHealthController::class, 'diagnostics'])->name('admin.system.diagnostics');
    Route::get('/system/app-info', [\App\Http\Controllers\Admin\AppInfoController::class, 'index'])->name('admin.system.app-info');
    Route::get('/system/vps-monitor', [\App\Http\Controllers\Admin\VpsMonitorController::class, 'index'])->name('admin.system.vps-monitor');
    Route::get('/system/vps-stats', [\App\Http\Controllers\Admin\VpsMonitorController::class, 'stats'])->name('admin.system.vps-stats');

    // Month Close & Accounting
    Route::get('/accounting/month-close', [\App\Http\Controllers\Admin\AccountingController::class, 'monthClose'])->name('admin.accounting.month-close');
    Route::post('/accounting/month-close', [\App\Http\Controllers\Admin\AccountingController::class, 'closeMonth'])->name('admin.accounting.close-month');
    Route::post('/accounting/month-lock', [\App\Http\Controllers\Admin\AccountingController::class, 'lockMonth'])->name('admin.accounting.lock-month');
    Route::post('/accounting/month-reopen', [\App\Http\Controllers\Admin\AccountingController::class, 'reopenMonth'])->name('admin.accounting.reopen-month');

    // Credit/Debit Notes
    Route::get('/accounting/credit-debit-notes', [\App\Http\Controllers\Admin\AccountingController::class, 'creditDebitNotes'])->name('admin.accounting.credit-debit-notes');
    Route::post('/accounting/credit-debit-notes', [\App\Http\Controllers\Admin\AccountingController::class, 'createNote'])->name('admin.accounting.create-note');
    Route::post('/accounting/credit-debit-notes/{id}/approve', [\App\Http\Controllers\Admin\AccountingController::class, 'approveNote'])->name('admin.accounting.approve-note');
    Route::post('/accounting/credit-debit-notes/{id}/reject', [\App\Http\Controllers\Admin\AccountingController::class, 'rejectNote'])->name('admin.accounting.reject-note');

    // Daily Cash Close & Staff Outstanding
    Route::get('/staff/daily-close', [\App\Http\Controllers\Admin\StaffManagementController::class, 'dailyClose'])->name('admin.staff.daily-close');
    Route::get('/staff/daily-close/{staffId}', [\App\Http\Controllers\Admin\StaffManagementController::class, 'staffDailySummary'])->name('admin.staff.daily-summary');
    Route::post('/staff/daily-close/{staffId}/verify', [\App\Http\Controllers\Admin\StaffManagementController::class, 'verifyDailyClose'])->name('admin.staff.verify-close');
    Route::get('/staff/outstanding', [\App\Http\Controllers\Admin\StaffManagementController::class, 'outstandingReport'])->name('admin.staff.outstanding');
    Route::get('/staff/area-collection', [\App\Http\Controllers\Admin\StaffManagementController::class, 'areaCollection'])->name('admin.staff.area-collection');
    Route::get('/staff/kpi', [\App\Http\Controllers\Admin\StaffManagementController::class, 'kpiDashboard'])->name('admin.staff.kpi');

    // Network Monitoring & MikroTik
    Route::get('/network/pop-stats', [\App\Http\Controllers\Admin\NetworkMonitoringController::class, 'popStats'])->name('admin.network.pop-stats');
    Route::post('/network/clients/{client}/sync-speed', [\App\Http\Controllers\Admin\NetworkMonitoringController::class, 'syncClientSpeed'])->name('admin.network.sync-speed');
    Route::post('/network/clients/bulk-speed-sync', [\App\Http\Controllers\Admin\NetworkMonitoringController::class, 'bulkSpeedSync'])->name('admin.network.bulk-speed-sync');
    Route::post('/network/auto-downgrade', [\App\Http\Controllers\Admin\NetworkMonitoringController::class, 'runAutoDowngrade'])->name('admin.network.auto-downgrade');
    Route::post('/network/clients/{client}/restore-speed', [\App\Http\Controllers\Admin\NetworkMonitoringController::class, 'restoreClientSpeed'])->name('admin.network.restore-speed');
    Route::get('/network/bandwidth-stats', [\App\Http\Controllers\Admin\NetworkMonitoringController::class, 'bandwidthStats'])->name('admin.network.bandwidth-stats');
    Route::get('/network/retry-stats', [\App\Http\Controllers\Admin\NetworkMonitoringController::class, 'retryStats'])->name('admin.network.retry-stats');
    Route::get('/network/failed-actions', [\App\Http\Controllers\Admin\NetworkMonitoringController::class, 'failedActions'])->name('admin.network.failed-actions');
    Route::post('/network/failed-actions/{id}/retry', [\App\Http\Controllers\Admin\NetworkMonitoringController::class, 'retryFailedAction'])->name('admin.network.retry-action');
    Route::get('/network/device-overview', [\App\Http\Controllers\Admin\NetworkMonitoringController::class, 'deviceOverview'])->name('admin.network.device-overview');
    Route::get('/network/limited-clients', [\App\Http\Controllers\Admin\NetworkMonitoringController::class, 'limitedClients'])->name('admin.network.limited-clients');

    // Multi-Router Live Panel
    Route::get('/multi-router', [\App\Http\Controllers\Admin\MultiRouterController::class, 'index'])->name('admin.multi-router.index');
    Route::get('/multi-router/status', [\App\Http\Controllers\Admin\MultiRouterController::class, 'liveStatus'])->name('admin.multi-router.status');
    Route::post('/multi-router/sync/{device}', [\App\Http\Controllers\Admin\MultiRouterController::class, 'syncDevice'])->name('admin.multi-router.sync');
    Route::post('/multi-router/sync-all', [\App\Http\Controllers\Admin\MultiRouterController::class, 'syncAll'])->name('admin.multi-router.sync-all');
    Route::get('/multi-router/{device}/users', [\App\Http\Controllers\Admin\MultiRouterController::class, 'getActiveUsers'])->name('admin.multi-router.users');
    Route::get('/multi-router/bandwidth', [\App\Http\Controllers\Admin\MultiRouterController::class, 'getBandwidthStats'])->name('admin.multi-router.bandwidth');

    // Exports (Background Jobs)
    Route::get('/exports', [\App\Http\Controllers\Admin\ExportController::class, 'index'])->name('admin.exports.index');
    Route::post('/exports', [\App\Http\Controllers\Admin\ExportController::class, 'create'])->name('admin.exports.create');
    Route::get('/exports/{export}/status', [\App\Http\Controllers\Admin\ExportController::class, 'status'])->name('admin.exports.status');
    Route::get('/exports/{export}/download', [\App\Http\Controllers\Admin\ExportController::class, 'download'])->name('admin.exports.download');
    Route::get('/exports/quick/{type}/{format}', [\App\Http\Controllers\Admin\ExportController::class, 'quickExport'])->name('admin.exports.quick');

    // Email Templates
    Route::get('/settings/email-templates', [\App\Http\Controllers\Admin\EmailTemplateController::class, 'index'])->name('admin.settings.email-templates');
    Route::get('/settings/email-templates/{template}/edit', [\App\Http\Controllers\Admin\EmailTemplateController::class, 'edit'])->name('admin.settings.email-templates.edit');
    Route::put('/settings/email-templates/{template}', [\App\Http\Controllers\Admin\EmailTemplateController::class, 'update'])->name('admin.settings.email-templates.update');
    Route::post('/settings/email-templates/{template}/preview', [\App\Http\Controllers\Admin\EmailTemplateController::class, 'preview'])->name('admin.settings.email-templates.preview');
    Route::post('/settings/email-templates/{template}/test', [\App\Http\Controllers\Admin\EmailTemplateController::class, 'sendTest'])->name('admin.settings.email-templates.test');

    // ==================== ENTERPRISE UPGRADE ROUTES ====================

    // Two-Factor Authentication
    Route::prefix('2fa')->group(function () {
        Route::get('/setup', [\App\Http\Controllers\Admin\TwoFactorController::class, 'setup'])->name('admin.2fa.setup');
        Route::post('/enable', [\App\Http\Controllers\Admin\TwoFactorController::class, 'enable'])->name('admin.2fa.enable');
        Route::post('/disable', [\App\Http\Controllers\Admin\TwoFactorController::class, 'disable'])->name('admin.2fa.disable');
        Route::get('/recovery-codes', [\App\Http\Controllers\Admin\TwoFactorController::class, 'showRecoveryCodes'])->name('admin.2fa.recovery-codes');
        Route::post('/regenerate-codes', [\App\Http\Controllers\Admin\TwoFactorController::class, 'regenerateRecoveryCodes'])->name('admin.2fa.regenerate-codes');
        Route::get('/verify', [\App\Http\Controllers\Admin\TwoFactorController::class, 'showVerify'])->name('admin.2fa.verify');
        Route::post('/verify-code', [\App\Http\Controllers\Admin\TwoFactorController::class, 'verifyCode'])->name('admin.2fa.verify-code');
        Route::post('/verify-recovery', [\App\Http\Controllers\Admin\TwoFactorController::class, 'verifyCode'])->name('admin.2fa.verify.recovery');
    });

    // Card-Based Prepaid Billing
    Route::prefix('card-billing')->group(function () {
        Route::get('/batches', [\App\Http\Controllers\Admin\CardBillingController::class, 'batches'])->name('admin.card-billing.batches');
        Route::get('/batches/create', [\App\Http\Controllers\Admin\CardBillingController::class, 'createBatch'])->name('admin.card-billing.batches.create');
        Route::post('/batches', [\App\Http\Controllers\Admin\CardBillingController::class, 'storeBatch'])->name('admin.card-billing.batches.store');
        Route::get('/batches/{batch}', [\App\Http\Controllers\Admin\CardBillingController::class, 'showBatch'])->name('admin.card-billing.batches.show');
        Route::get('/cards', [\App\Http\Controllers\Admin\CardBillingController::class, 'cards'])->name('admin.card-billing.cards');
        Route::get('/redeem', [\App\Http\Controllers\Admin\CardBillingController::class, 'redeemForm'])->name('admin.card-billing.redeem');
        Route::post('/redeem', [\App\Http\Controllers\Admin\CardBillingController::class, 'redeem'])->name('admin.card-billing.redeem.process');
        Route::post('/cards/{card}/disable', [\App\Http\Controllers\Admin\CardBillingController::class, 'disableCard'])->name('admin.card-billing.cards.disable');
        Route::get('/distributors', [\App\Http\Controllers\Admin\CardBillingController::class, 'distributors'])->name('admin.card-billing.distributors');
        Route::post('/distributors', [\App\Http\Controllers\Admin\CardBillingController::class, 'storeDistributor'])->name('admin.card-billing.distributors.store');
        Route::put('/distributors/{distributor}', [\App\Http\Controllers\Admin\CardBillingController::class, 'updateDistributor'])->name('admin.card-billing.distributors.update');
        Route::delete('/distributors/{distributor}', [\App\Http\Controllers\Admin\CardBillingController::class, 'destroyDistributor'])->name('admin.card-billing.distributors.destroy');
        Route::post('/distribute-cards', [\App\Http\Controllers\Admin\CardBillingController::class, 'distributeCards'])->name('admin.card-billing.distribute');
        Route::get('/create-batch', [\App\Http\Controllers\Admin\CardBillingController::class, 'createBatch'])->name('admin.card-billing.create-batch');
        Route::post('/store-batch', [\App\Http\Controllers\Admin\CardBillingController::class, 'storeBatch'])->name('admin.card-billing.store-batch');
        Route::post('/process-redeem', [\App\Http\Controllers\Admin\CardBillingController::class, 'redeem'])->name('admin.card-billing.process-redeem');
        Route::delete('/batches/{batch}', [\App\Http\Controllers\Admin\CardBillingController::class, 'destroyBatch'])->name('admin.card-billing.destroy-batch');
    });

    // WhatsApp Messaging
    Route::prefix('whatsapp')->group(function () {
        Route::get('/settings', [\App\Http\Controllers\Admin\WhatsAppController::class, 'settings'])->name('admin.whatsapp.settings');
        Route::post('/settings', [\App\Http\Controllers\Admin\WhatsAppController::class, 'updateSettings'])->name('admin.whatsapp.settings.update');
        Route::get('/templates', [\App\Http\Controllers\Admin\WhatsAppController::class, 'templates'])->name('admin.whatsapp.templates');
        Route::post('/templates', [\App\Http\Controllers\Admin\WhatsAppController::class, 'storeTemplate'])->name('admin.whatsapp.templates.store');
        Route::put('/templates/{template}', [\App\Http\Controllers\Admin\WhatsAppController::class, 'updateTemplate'])->name('admin.whatsapp.templates.update');
        Route::delete('/templates/{template}', [\App\Http\Controllers\Admin\WhatsAppController::class, 'deleteTemplate'])->name('admin.whatsapp.templates.delete');
        Route::get('/logs', [\App\Http\Controllers\Admin\WhatsAppController::class, 'logs'])->name('admin.whatsapp.logs');
        Route::post('/send', [\App\Http\Controllers\Admin\WhatsAppController::class, 'send'])->name('admin.whatsapp.send');
        Route::post('/send-process', [\App\Http\Controllers\Admin\WhatsAppController::class, 'send'])->name('admin.whatsapp.send.process');
        Route::post('/test', [\App\Http\Controllers\Admin\WhatsAppController::class, 'testMessage'])->name('admin.whatsapp.test');
    });

    // Hotspot HTML Editor
    Route::prefix('hotspot')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\HotspotEditorController::class, 'index'])->name('admin.hotspot.index');
        Route::get('/create', [\App\Http\Controllers\Admin\HotspotEditorController::class, 'create'])->name('admin.hotspot.create');
        Route::post('/', [\App\Http\Controllers\Admin\HotspotEditorController::class, 'store'])->name('admin.hotspot.store');
        Route::get('/{template}/edit', [\App\Http\Controllers\Admin\HotspotEditorController::class, 'edit'])->name('admin.hotspot.edit');
        Route::put('/{template}', [\App\Http\Controllers\Admin\HotspotEditorController::class, 'update'])->name('admin.hotspot.update');
        Route::delete('/{template}', [\App\Http\Controllers\Admin\HotspotEditorController::class, 'destroy'])->name('admin.hotspot.destroy');
        Route::get('/{template}/preview', [\App\Http\Controllers\Admin\HotspotEditorController::class, 'preview'])->name('admin.hotspot.preview');
        Route::post('/{template}/default', [\App\Http\Controllers\Admin\HotspotEditorController::class, 'setDefault'])->name('admin.hotspot.default');
        Route::post('/{template}/deploy', [\App\Http\Controllers\Admin\HotspotEditorController::class, 'deploy'])->name('admin.hotspot.deploy');
        Route::post('/{template}/duplicate', [\App\Http\Controllers\Admin\HotspotEditorController::class, 'duplicate'])->name('admin.hotspot.duplicate');
    });

    // VPN Account Management
    Route::prefix('vpn')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\VpnController::class, 'index'])->name('admin.vpn.index');
        Route::get('/settings', [\App\Http\Controllers\Admin\VpnController::class, 'settings'])->name('admin.vpn.settings');
        Route::post('/settings', [\App\Http\Controllers\Admin\VpnController::class, 'saveSettings'])->name('admin.vpn.settings.save');
        Route::get('/create', [\App\Http\Controllers\Admin\VpnController::class, 'create'])->name('admin.vpn.create');
        Route::post('/', [\App\Http\Controllers\Admin\VpnController::class, 'store'])->name('admin.vpn.store');
        Route::get('/{vpnAccount}', [\App\Http\Controllers\Admin\VpnController::class, 'show'])->name('admin.vpn.show');
        Route::get('/{vpnAccount}/edit', [\App\Http\Controllers\Admin\VpnController::class, 'edit'])->name('admin.vpn.edit');
        Route::put('/{vpnAccount}', [\App\Http\Controllers\Admin\VpnController::class, 'update'])->name('admin.vpn.update');
        Route::delete('/{vpnAccount}', [\App\Http\Controllers\Admin\VpnController::class, 'destroy'])->name('admin.vpn.destroy');
        Route::post('/{vpnAccount}/toggle', [\App\Http\Controllers\Admin\VpnController::class, 'toggle'])->name('admin.vpn.toggle');
    });

    // Port Forwarding Management
    Route::prefix('port-forward')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\PortForwardController::class, 'index'])->name('admin.port-forward.index');
        Route::get('/create', [\App\Http\Controllers\Admin\PortForwardController::class, 'create'])->name('admin.port-forward.create');
        Route::post('/', [\App\Http\Controllers\Admin\PortForwardController::class, 'store'])->name('admin.port-forward.store');
        Route::get('/{portForward}/edit', [\App\Http\Controllers\Admin\PortForwardController::class, 'edit'])->name('admin.port-forward.edit');
        Route::put('/{portForward}', [\App\Http\Controllers\Admin\PortForwardController::class, 'update'])->name('admin.port-forward.update');
        Route::delete('/{portForward}', [\App\Http\Controllers\Admin\PortForwardController::class, 'destroy'])->name('admin.port-forward.destroy');
        Route::post('/{portForward}/toggle', [\App\Http\Controllers\Admin\PortForwardController::class, 'toggle'])->name('admin.port-forward.toggle');
    });

    // Backup & Restore
    Route::prefix('backups')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\BackupController::class, 'index'])->name('admin.backups.index');
        Route::post('/database', [\App\Http\Controllers\Admin\BackupController::class, 'createDatabase'])->name('admin.backups.database');
        Route::post('/config', [\App\Http\Controllers\Admin\BackupController::class, 'createConfig'])->name('admin.backups.config');
        Route::get('/{backup}/download', [\App\Http\Controllers\Admin\BackupController::class, 'download'])->name('admin.backups.download');
        Route::post('/{backup}/restore', [\App\Http\Controllers\Admin\BackupController::class, 'restore'])->name('admin.backups.restore');
        Route::delete('/{backup}', [\App\Http\Controllers\Admin\BackupController::class, 'destroy'])->name('admin.backups.destroy');
        Route::post('/cleanup', [\App\Http\Controllers\Admin\BackupController::class, 'cleanup'])->name('admin.backups.cleanup');
        Route::post('/create', [\App\Http\Controllers\Admin\BackupController::class, 'createDatabase'])->name('admin.backups.create');
    });

    // Deleted Customer Recovery
    Route::prefix('client-recovery')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\ClientRecoveryController::class, 'index'])->name('admin.client-recovery.index');
        Route::post('/{client}/recover', [\App\Http\Controllers\Admin\ClientRecoveryController::class, 'recover'])->name('admin.client-recovery.recover');
        Route::get('/{client}/history', [\App\Http\Controllers\Admin\ClientRecoveryController::class, 'history'])->name('admin.client-recovery.history');
        Route::delete('/{client}/permanent-delete', [\App\Http\Controllers\Admin\ClientRecoveryController::class, 'permanentDelete'])->name('admin.client-recovery.permanent-delete');
    });

    // Security Settings
    Route::prefix('security')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\SecurityController::class, 'index'])->name('admin.security.index');
        Route::get('/password-policy', [\App\Http\Controllers\Admin\SecurityController::class, 'passwordPolicy'])->name('admin.security.password-policy');
        Route::post('/password-policy', [\App\Http\Controllers\Admin\SecurityController::class, 'updatePasswordPolicy'])->name('admin.security.password-policy.update');
        Route::get('/login-logs', [\App\Http\Controllers\Admin\SecurityController::class, 'loginLogs'])->name('admin.security.login-logs');
        Route::post('/login-logs/clear', [\App\Http\Controllers\Admin\SecurityController::class, 'clearLoginLogs'])->name('admin.security.login-logs.clear');
        Route::post('/toggle-location-capture', [\App\Http\Controllers\Admin\SecurityController::class, 'toggleLocationCapture'])->name('admin.security.toggle-location-capture');
        Route::get('/active-sessions', [\App\Http\Controllers\Admin\SecurityController::class, 'activeSessions'])->name('admin.security.active-sessions');
        Route::delete('/active-sessions/{session}', [\App\Http\Controllers\Admin\SecurityController::class, 'forceLogoutSession'])->name('admin.security.active-sessions.force-logout');
        Route::get('/ip-restrictions', [\App\Http\Controllers\Admin\SecurityController::class, 'ipRestrictions'])->name('admin.security.ip-restrictions');
        Route::post('/ip-restrictions', [\App\Http\Controllers\Admin\SecurityController::class, 'storeIpRestriction'])->name('admin.security.ip-restrictions.store');
        Route::delete('/ip-restrictions/{ipRestriction}', [\App\Http\Controllers\Admin\SecurityController::class, 'destroyIpRestriction'])->name('admin.security.ip-restrictions.destroy');
        Route::get('/api-tokens', [\App\Http\Controllers\Admin\SecurityController::class, 'apiTokens'])->name('admin.security.api-tokens');
        Route::post('/api-tokens', [\App\Http\Controllers\Admin\SecurityController::class, 'createApiToken'])->name('admin.security.api-tokens.store');
        Route::patch('/api-tokens/{token}/disable', [\App\Http\Controllers\Admin\SecurityController::class, 'disableApiToken'])->name('admin.security.api-tokens.disable');
        Route::delete('/api-tokens/{token}', [\App\Http\Controllers\Admin\SecurityController::class, 'revokeApiToken'])->name('admin.security.api-tokens.destroy');
    });

    // Knowledge Base / Help
    Route::prefix('knowledge-base')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\KnowledgeBaseController::class, 'index'])->name('admin.knowledge-base.index');
        Route::post('/categories', [\App\Http\Controllers\Admin\KnowledgeBaseController::class, 'storeCategory'])->name('admin.knowledge-base.categories.store');
        Route::put('/categories/{category}', [\App\Http\Controllers\Admin\KnowledgeBaseController::class, 'updateCategory'])->name('admin.knowledge-base.categories.update');
        Route::delete('/categories/{category}', [\App\Http\Controllers\Admin\KnowledgeBaseController::class, 'deleteCategory'])->name('admin.knowledge-base.categories.destroy');
        Route::get('/categories/{category}/articles', [\App\Http\Controllers\Admin\KnowledgeBaseController::class, 'articles'])->name('admin.knowledge-base.articles');
        Route::get('/articles/create', [\App\Http\Controllers\Admin\KnowledgeBaseController::class, 'createArticle'])->name('admin.knowledge-base.articles.create');
        Route::post('/articles', [\App\Http\Controllers\Admin\KnowledgeBaseController::class, 'storeArticle'])->name('admin.knowledge-base.articles.store');
        Route::get('/articles/{article}/edit', [\App\Http\Controllers\Admin\KnowledgeBaseController::class, 'editArticle'])->name('admin.knowledge-base.articles.edit');
        Route::put('/articles/{article}', [\App\Http\Controllers\Admin\KnowledgeBaseController::class, 'updateArticle'])->name('admin.knowledge-base.articles.update');
        Route::delete('/articles/{article}', [\App\Http\Controllers\Admin\KnowledgeBaseController::class, 'deleteArticle'])->name('admin.knowledge-base.articles.destroy');
    });

    Route::get('/reports/bandwidth', [BandwidthReportController::class, 'index'])->name('admin.reports.bandwidth');
    Route::get('/reports/bandwidth/client/{client}', [BandwidthReportController::class, 'clientDetail'])->name('admin.reports.bandwidth-client');
    Route::get('/reports/bandwidth/export', [BandwidthReportController::class, 'export'])->name('admin.reports.bandwidth-export');

    Route::get('/network/device-logs', [DeviceLogController::class, 'index'])->name('admin.network.device-logs');

    Route::get('/network/connectivity-test', [ConnectivityTestController::class, 'index'])->name('admin.network.connectivity-test');
    Route::post('/network/connectivity-test/ping', [ConnectivityTestController::class, 'pingFromRouter'])->name('admin.network.connectivity-ping');
    Route::post('/network/connectivity-test/traceroute', [ConnectivityTestController::class, 'traceroute'])->name('admin.network.connectivity-traceroute');
    Route::post('/network/connectivity-test/session', [ConnectivityTestController::class, 'checkSession'])->name('admin.network.connectivity-session');
    Route::post('/network/connectivity-test/dns', [ConnectivityTestController::class, 'dnsLookup'])->name('admin.network.connectivity-dns');

    Route::get('/network/mac-bindings', [MacBindingController::class, 'index'])->name('admin.network.mac-bindings');
    Route::post('/network/mac-bindings/{client}/toggle', [MacBindingController::class, 'toggleBinding'])->name('admin.network.mac-toggle');
    Route::post('/network/mac-bindings/{client}/update-mac', [MacBindingController::class, 'updateMac'])->name('admin.network.mac-update');
    Route::post('/network/mac-bindings/bulk-bind', [MacBindingController::class, 'bulkBind'])->name('admin.network.mac-bulk-bind');
});

// Speed Test API Routes (public for download/upload, auth for save)
Route::get('/api/speed-test/download', [\App\Http\Controllers\Api\SpeedTestController::class, 'download'])->name('api.speed-test.download');
Route::post('/api/speed-test/upload', [\App\Http\Controllers\Api\SpeedTestController::class, 'upload'])->name('api.speed-test.upload');
Route::get('/api/ping', [\App\Http\Controllers\Api\SpeedTestController::class, 'ping'])->name('api.ping');
Route::post('/api/speed-test/save', [\App\Http\Controllers\Api\SpeedTestController::class, 'save'])->name('api.speed-test.save');
Route::get('/api/speed-test/history', [\App\Http\Controllers\Api\SpeedTestController::class, 'history'])->name('api.speed-test.history');

Route::prefix('client')->middleware(['auth', 'client.access'])->group(function () {
    Route::get('/dashboard', [ClientDashboardController::class, 'index'])->name('client.dashboard');
    Route::get('/bills', [ClientDashboardController::class, 'bills'])->name('client.bills');
    Route::get('/speed-test', [ClientDashboardController::class, 'speedTest'])->name('client.speed-test');
    Route::get('/bills/{bill}/invoice', [ClientDashboardController::class, 'downloadInvoice'])->name('client.invoice');
    Route::get('/bills/{bill}/download', [ClientDashboardController::class, 'downloadInvoice'])->name('client.invoice.download');
    Route::get('/payments', [ClientDashboardController::class, 'payments'])->name('client.payments');
    Route::get('/tickets', [ClientDashboardController::class, 'tickets'])->name('client.tickets');
    Route::get('/tickets/create', [ClientDashboardController::class, 'tickets'])->name('client.tickets.create');
    Route::post('/tickets', [ClientDashboardController::class, 'createTicket'])->name('client.tickets.store');
    Route::get('/tickets/{ticket}', [ClientDashboardController::class, 'showTicket'])->name('client.tickets.show');
    Route::post('/tickets/{ticket}/reply', [ClientDashboardController::class, 'replyTicket'])->name('client.tickets.reply');
    Route::post('/tickets/{ticket}/rate', [ClientDashboardController::class, 'rateTicket'])->name('client.tickets.rate');
    Route::get('/profile', [ClientDashboardController::class, 'profile'])->name('client.profile');
    Route::post('/profile', [ClientDashboardController::class, 'updateProfile'])->name('client.profile.update');
    Route::post('/password', [ClientDashboardController::class, 'updatePassword'])->name('client.password.update');
    Route::get('/live-status', [ClientDashboardController::class, 'liveStatus'])->name('client.live-status');
    Route::get('/api/live-stats', [ClientDashboardController::class, 'liveStats'])->name('client.api.live-stats');
    Route::get('/api/usage-live', [ClientDashboardController::class, 'usageLive'])->name('client.api.usage-live');
    Route::get('/api/chart-data', [ClientDashboardController::class, 'chartData'])->name('client.api.chart-data');
    Route::get('/connection', [ClientDashboardController::class, 'connection'])->name('client.connection');
    Route::get('/usage', [ClientDashboardController::class, 'usage'])->name('client.usage');
    Route::get('/pay-now', [ClientDashboardController::class, 'payNow'])->name('client.pay-now');
    Route::get('/self-help', [ClientDashboardController::class, 'selfHelp'])->name('client.self-help');
    Route::post('/pay-now/bkash', [ClientDashboardController::class, 'initiateBkashPayment'])->name('client.pay-now.bkash');
    Route::post('/pay-now/sslcommerz', [ClientDashboardController::class, 'initiateSslPayment'])->name('client.pay-now.sslcommerz');
    Route::post('/pay-now/nagad', [ClientDashboardController::class, 'initiateNagadPayment'])->name('client.pay-now.nagad');
    Route::post('/pay-now/submit', [ClientDashboardController::class, 'submitPayment'])->name('client.pay-now.submit');
    Route::get('/notifications', [ClientDashboardController::class, 'notifications'])->name('client.notifications');
    Route::get('/settings', [ClientDashboardController::class, 'settings'])->name('client.settings');
    Route::post('/settings', [ClientDashboardController::class, 'updateSettings'])->name('client.settings.update');
    Route::get('/help', [ClientDashboardController::class, 'help'])->name('client.help');
    Route::get('/package-change', [ClientDashboardController::class, 'packageChange'])->name('client.package-change');
    Route::post('/package-change', [ClientDashboardController::class, 'submitPackageChange'])->name('client.package-change.submit');
    Route::get('/service-history', [ClientDashboardController::class, 'serviceHistory'])->name('client.service-history');
    Route::post('/self-reconnect', [ClientDashboardController::class, 'selfServiceReconnect'])->name('client.self-reconnect');
    Route::get('/api/timeline', [ClientDashboardController::class, 'getActivityTimeline'])->name('client.api.timeline');
    Route::get('/api/connection-details', [ClientDashboardController::class, 'connectionDetails'])->name('client.api.connection-details');
    Route::get('/connectivity-test', [ClientDashboardController::class, 'connectivityTest'])->name('client.connectivity-test');
    Route::post('/connectivity-test/run', [ClientDashboardController::class, 'runConnectivityTest'])->name('client.connectivity-test.run');
    Route::get('/media-servers', [ClientDashboardController::class, 'mediaServers'])->name('client.media-servers');
});

Route::prefix('technician')->middleware(['auth', 'technician'])->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\Technician\TechnicianDashboardController::class, 'index'])->name('technician.dashboard')->middleware('check.permission:technician.dashboard');
    Route::get('/clients', [\App\Http\Controllers\Technician\TechnicianDashboardController::class, 'clients'])->name('technician.clients')->middleware('check.permission:technician.clients');
    Route::get('/clients/due', [\App\Http\Controllers\Technician\TechnicianDashboardController::class, 'dueClients'])->name('technician.clients.due')->middleware('check.permission:technician.clients');
    Route::get('/clients/create', [\App\Http\Controllers\Technician\TechnicianDashboardController::class, 'createClient'])->name('technician.clients.create')->middleware('check.permission:technician.client.create');
    Route::post('/clients', [\App\Http\Controllers\Technician\TechnicianDashboardController::class, 'storeClient'])->name('technician.clients.store')->middleware('check.permission:technician.client.create');
    Route::get('/clients/{client}', [\App\Http\Controllers\Technician\TechnicianDashboardController::class, 'showClient'])->name('technician.clients.show')->middleware('check.permission:technician.clients');
    Route::post('/clients/{client}/collect-payment', [\App\Http\Controllers\Technician\TechnicianDashboardController::class, 'collectPayment'])->name('technician.collect-payment')->middleware(['check.permission:technician.collection']);
    Route::get('/payments/{payment}/receipt', [\App\Http\Controllers\Admin\PaymentReceiptController::class, 'show'])->name('technician.payments.receipt');
    Route::get('/payments/{payment}/receipt/print', [\App\Http\Controllers\Admin\PaymentReceiptController::class, 'print'])->name('technician.payments.receipt.print');
    Route::get('/payments/{payment}/receipt/thermal', [\App\Http\Controllers\Admin\PaymentReceiptController::class, 'printThermal'])->name('technician.payments.receipt.thermal');
    Route::get('/payments/{payment}/receipt/download', [\App\Http\Controllers\Admin\PaymentReceiptController::class, 'download'])->name('technician.payments.receipt.download');
    Route::get('/clients/{client}/edit', [\App\Http\Controllers\Technician\TechnicianDashboardController::class, 'editClient'])->name('technician.clients.edit')->middleware('check.permission:technician.client.edit');
    Route::put('/clients/{client}', [\App\Http\Controllers\Technician\TechnicianDashboardController::class, 'updateClient'])->name('technician.clients.update')->middleware('check.permission:technician.client.edit');
    Route::post('/clients/{client}/sync', [\App\Http\Controllers\Technician\TechnicianDashboardController::class, 'syncClient'])->name('technician.clients.sync')->middleware('check.permission:technician.device_control');
    Route::post('/clients/{client}/enable', [\App\Http\Controllers\Technician\TechnicianDashboardController::class, 'enableClient'])->name('technician.clients.enable')->middleware('check.permission:technician.activate_client');
    Route::post('/clients/{client}/disable', [\App\Http\Controllers\Technician\TechnicianDashboardController::class, 'disableClient'])->name('technician.clients.disable')->middleware('check.permission:technician.activate_client');
    Route::post('/clients/{client}/disconnect', [\App\Http\Controllers\Technician\TechnicianDashboardController::class, 'disconnectClient'])->name('technician.clients.disconnect')->middleware('check.permission:technician.device_control');
    Route::post('/clients/{client}/generate-bill', [\App\Http\Controllers\Technician\TechnicianDashboardController::class, 'generateClientBill'])->name('technician.clients.generate-bill')->middleware('check.permission:technician.generate_bill');
    Route::post('/clients/{client}/pay-installation-charge', [\App\Http\Controllers\Technician\TechnicianDashboardController::class, 'payInstallationCharge'])->name('technician.clients.pay-installation-charge')->middleware(['check.permission:technician.pay_installation']);
    Route::post('/clients/{client}/temp-deadline', [\App\Http\Controllers\Technician\TechnicianDashboardController::class, 'applyTempDeadline'])->name('technician.clients.temp-deadline')->middleware('check.permission:technician.extend_deadline');
    Route::post('/clients/{client}/clear-temp-deadline', [\App\Http\Controllers\Technician\TechnicianDashboardController::class, 'clearTempDeadline'])->name('technician.clients.clear-temp-deadline')->middleware('check.permission:technician.extend_deadline');
    Route::post('/clients/{client}/send-sms', [\App\Http\Controllers\Technician\TechnicianDashboardController::class, 'sendSms'])->name('technician.clients.send-sms')->middleware('check.permission:technician.send_sms');
    Route::get('/clients/{client}/payment-history', [\App\Http\Controllers\Technician\TechnicianDashboardController::class, 'paymentHistory'])->name('technician.clients.payment-history');
    Route::get('/clients/{client}/additional-payments', [\App\Http\Controllers\Technician\TechnicianDashboardController::class, 'additionalPayments'])->name('technician.clients.additional-payments');
    Route::get('/clients/{client}/live-bandwidth', [\App\Http\Controllers\Admin\LiveMonitorController::class, 'getBandwidth'])->name('technician.clients.live-bandwidth');
    Route::get('/clients/{client}/live-ping', [\App\Http\Controllers\Admin\LiveMonitorController::class, 'ping'])->name('technician.clients.live-ping');
    Route::get('/clients/{client}/ping-stream', [\App\Http\Controllers\Admin\LiveMonitorController::class, 'pingStream'])->name('technician.clients.ping-stream');
    Route::get('/network/onu/{onu}/live-info', [\App\Http\Controllers\Admin\OltController::class, 'getOnuLiveInfo'])->name('technician.olt.onu-live-info');

    // Technician Selfie Attendance
    Route::get('/attendance', [\App\Http\Controllers\Technician\TechnicianDashboardController::class, 'myAttendance'])->name('technician.attendance');
    Route::post('/attendance', [\App\Http\Controllers\Technician\TechnicianDashboardController::class, 'storeAttendance'])->name('technician.attendance.store');

    Route::get('/due-report', [\App\Http\Controllers\Technician\TechnicianDashboardController::class, 'dueReport'])->name('technician.due-report')->middleware('check.permission:technician.due_report');
    Route::get('/collection-report', [\App\Http\Controllers\Technician\TechnicianDashboardController::class, 'collectionReport'])->name('technician.collection-report')->middleware('check.permission:technician.collection');
    Route::get('/tickets', [\App\Http\Controllers\Technician\TechnicianDashboardController::class, 'tickets'])->name('technician.tickets')->middleware('check.permission:technician.tickets');
    Route::post('/tickets', [\App\Http\Controllers\Technician\TechnicianDashboardController::class, 'createTicket'])->name('technician.tickets.create')->middleware('check.permission:technician.tickets');
    Route::get('/tickets/{ticket}', [\App\Http\Controllers\Technician\TechnicianDashboardController::class, 'viewTicket'])->name('technician.tickets.view')->middleware('check.permission:technician.tickets');
    Route::post('/tickets/{ticket}/update-status', [\App\Http\Controllers\Technician\TechnicianDashboardController::class, 'updateTicketStatus'])->name('technician.tickets.update-status')->middleware('check.permission:technician.tickets');
    Route::post('/tickets/{ticket}/add-note', [\App\Http\Controllers\Technician\TechnicianDashboardController::class, 'addTicketNote'])->name('technician.tickets.add-note')->middleware('check.permission:technician.tickets');
    Route::get('/profile', [\App\Http\Controllers\Technician\TechnicianDashboardController::class, 'profile'])->name('technician.profile');
    Route::put('/profile', [\App\Http\Controllers\Technician\TechnicianDashboardController::class, 'updateProfile'])->name('technician.profile.update');
    Route::put('/profile/password', [\App\Http\Controllers\Technician\TechnicianDashboardController::class, 'updatePassword'])->name('technician.profile.password');
    Route::post('/profile/avatar', [\App\Http\Controllers\Technician\TechnicianDashboardController::class, 'updateAvatar'])->name('technician.profile.avatar');
    Route::get('/api/live-stats', [\App\Http\Controllers\Technician\TechnicianDashboardController::class, 'liveStats'])->name('technician.api.live-stats');
    Route::get('/api/chart-data', [\App\Http\Controllers\Technician\TechnicianDashboardController::class, 'chartData'])->name('technician.api.chart-data');
    Route::post('/api/reboot-onu', [\App\Http\Controllers\Technician\TechnicianDashboardController::class, 'rebootOnu'])->name('technician.api.reboot-onu');
    Route::post('/api/resolve-alarm', [\App\Http\Controllers\Technician\TechnicianDashboardController::class, 'resolveAlarm'])->name('technician.api.resolve-alarm');
    Route::post('/api/jobs/{ticket}/status', [\App\Http\Controllers\Technician\TechnicianDashboardController::class, 'updateJobStatus'])->name('technician.api.job-status');
    
    Route::get('/applications', [\App\Http\Controllers\Technician\NewConnectionController::class, 'index'])->name('technician.applications.index')->middleware('check.permission:technician.new_connection');
    Route::get('/applications/create', [\App\Http\Controllers\Technician\NewConnectionController::class, 'create'])->name('technician.applications.create')->middleware('check.permission:technician.new_connection');
    Route::post('/applications', [\App\Http\Controllers\Technician\NewConnectionController::class, 'store'])->name('technician.applications.store')->middleware('check.permission:technician.new_connection');
    Route::get('/applications/sub-zones', [\App\Http\Controllers\Technician\NewConnectionController::class, 'getSubZones'])->name('technician.applications.sub-zones')->middleware('check.permission:technician.new_connection');
    Route::get('/applications/{application}', [\App\Http\Controllers\Technician\NewConnectionController::class, 'show'])->name('technician.applications.show')->middleware('check.permission:technician.new_connection');
    Route::get('/applications/{application}/edit', [\App\Http\Controllers\Technician\NewConnectionController::class, 'edit'])->name('technician.applications.edit')->middleware('check.permission:technician.new_connection');
    Route::put('/applications/{application}', [\App\Http\Controllers\Technician\NewConnectionController::class, 'update'])->name('technician.applications.update')->middleware('check.permission:technician.new_connection');
    Route::post('/applications/{application}/submit', [\App\Http\Controllers\Technician\NewConnectionController::class, 'submit'])->name('technician.applications.submit')->middleware('check.permission:technician.new_connection');
    Route::post('/applications/{application}/documents', [\App\Http\Controllers\Technician\NewConnectionController::class, 'uploadDocument'])->name('technician.applications.documents')->middleware('check.permission:technician.new_connection');
    Route::get('/due-clients', [\App\Http\Controllers\Technician\TechnicianDashboardController::class, 'dueClients'])->name('technician.due-clients')->middleware('check.permission:technician.clients');
    Route::get('/new-connection-applications', [\App\Http\Controllers\Technician\NewConnectionController::class, 'index'])->name('technician.new-connection-applications')->middleware('check.permission:technician.new_connection');
    Route::get('/package-requests', [\App\Http\Controllers\Technician\TechnicianDashboardController::class, 'clients'])->name('technician.package-requests')->middleware('check.permission:technician.clients');
    Route::get('/api/quick-search', [\App\Http\Controllers\Technician\QuickSearchController::class, 'search'])->name('technician.api.quick-search');
    Route::get('/api/search-clients', [\App\Http\Controllers\Admin\ClientController::class, 'searchClientsApi'])->name('technician.api.search-clients');
    Route::post('/submit-to-admin', [\App\Http\Controllers\Technician\TechnicianDashboardController::class, 'submitToAdmin'])->name('technician.submit-to-admin')->middleware('check.permission:technician.collection');
    Route::get('/submission-history', [\App\Http\Controllers\Technician\TechnicianDashboardController::class, 'submissionHistory'])->name('technician.submission-history')->middleware('check.permission:technician.collection');
});

Route::prefix('billman')->middleware(['auth', 'billman'])->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\Billman\BillmanController::class, 'dashboard'])->name('billman.dashboard');
    Route::get('/api/quick-search', [\App\Http\Controllers\Billman\BillmanController::class, 'quickSearch'])->name('billman.api.quick-search');
    Route::get('/clients/{client}', [\App\Http\Controllers\Billman\BillmanController::class, 'showClient'])->name('billman.client.show');
    Route::post('/clients/{client}/collect-payment', [\App\Http\Controllers\Billman\BillmanController::class, 'collectPayment'])->name('billman.collect-payment');
    Route::get('/network/onu/{onu}/live-info', [\App\Http\Controllers\Admin\OltController::class, 'getOnuLiveInfo'])->name('billman.olt.onu-live-info');
    Route::get('/collection-report', [\App\Http\Controllers\Billman\BillmanController::class, 'collectionReport'])->name('billman.collection-report');
    Route::post('/submit-to-admin', [\App\Http\Controllers\Billman\BillmanController::class, 'submitToAdmin'])->name('billman.submit-to-admin');
    Route::get('/submission-history', [\App\Http\Controllers\Billman\BillmanController::class, 'submissionHistory'])->name('billman.submission-history');
    Route::get('/clients', [\App\Http\Controllers\Billman\BillmanController::class, 'dashboard'])->name('billman.clients');
    Route::get('/profile', [\App\Http\Controllers\Billman\BillmanController::class, 'profile'])->name('billman.profile');
    Route::get('/payments/{payment}/receipt', [\App\Http\Controllers\Admin\PaymentReceiptController::class, 'show'])->name('billman.payments.receipt');
    Route::get('/payments/{payment}/receipt/print', [\App\Http\Controllers\Admin\PaymentReceiptController::class, 'print'])->name('billman.payments.receipt.print');
    Route::get('/payments/{payment}/receipt/thermal', [\App\Http\Controllers\Admin\PaymentReceiptController::class, 'printThermal'])->name('billman.payments.receipt.thermal');
    Route::get('/payments/{payment}/receipt/download', [\App\Http\Controllers\Admin\PaymentReceiptController::class, 'download'])->name('billman.payments.receipt.download');

    // Selfie Attendance
    Route::get('/attendance', [\App\Http\Controllers\Billman\BillmanController::class, 'myAttendance'])->name('billman.attendance');
    Route::post('/attendance', [\App\Http\Controllers\Billman\BillmanController::class, 'storeAttendance'])->name('billman.attendance.store');
});

Route::prefix('reseller')->middleware(['auth', 'reseller'])->group(function () {
    // Shared routes (both MAC and Bandwidth resellers)
    Route::get('/dashboard', [\App\Http\Controllers\Reseller\ResellerDashboardController::class, 'index'])->name('reseller.dashboard')->middleware('check.permission:reseller.dashboard');
    Route::get('/bandwidth-dashboard', [\App\Http\Controllers\Reseller\ResellerDashboardController::class, 'bandwidthDashboard'])->name('reseller.bandwidth-dashboard')->middleware('check.permission:reseller.dashboard');
    Route::get('/bandwidth-billing', [\App\Http\Controllers\Reseller\ResellerDashboardController::class, 'bandwidthBilling'])->name('reseller.bandwidth-billing')->middleware('reseller.feature:view_my_invoices');
    Route::get('/bandwidth-bills/{bill}/invoice', [\App\Http\Controllers\Reseller\ResellerDashboardController::class, 'bandwidthInvoice'])->name('reseller.bandwidth-invoice');
    Route::get('/bandwidth-bills/{bill}/download', [\App\Http\Controllers\Reseller\ResellerDashboardController::class, 'bandwidthInvoiceDownload'])->name('reseller.bandwidth-invoice.download');
    Route::get('/wallet', [\App\Http\Controllers\Reseller\ResellerDashboardController::class, 'wallet'])->name('reseller.wallet')->middleware(['check.permission:reseller.wallet', 'reseller.feature:wallet']);
    Route::get('/profile', [\App\Http\Controllers\Reseller\ResellerDashboardController::class, 'profile'])->name('reseller.profile');
    // Reseller Selfie Attendance
    Route::get('/attendance', [\App\Http\Controllers\Reseller\ResellerDashboardController::class, 'myAttendance'])->name('reseller.attendance');
    Route::post('/attendance', [\App\Http\Controllers\Reseller\ResellerDashboardController::class, 'storeAttendance'])->name('reseller.attendance.store');
    Route::get('/api/live-stats', [\App\Http\Controllers\Reseller\ResellerDashboardController::class, 'liveStats'])->name('reseller.api.live-stats');
    Route::middleware('reseller.feature:wallet')->group(function () {
        Route::post('/wallet/recharge', [\App\Http\Controllers\Reseller\ResellerDashboardController::class, 'initiateRecharge'])->name('reseller.wallet.recharge');
        Route::get('/wallet/recharge/callback', [\App\Http\Controllers\Reseller\ResellerDashboardController::class, 'rechargeCallback'])->name('reseller.wallet.recharge.callback');
        Route::get('/wallet/recharge/{transaction_id}/demo', [\App\Http\Controllers\Reseller\ResellerDashboardController::class, 'rechargeDemo'])->name('reseller.wallet.recharge.demo');
        Route::post('/wallet/recharge/{transaction_id}/demo-confirm', [\App\Http\Controllers\Reseller\ResellerDashboardController::class, 'rechargeDemoConfirm'])->name('reseller.wallet.recharge.demo-confirm');
    });

    // Tickets & Collection Report (shared: MAC + Bandwidth resellers)
    Route::get('/tickets', [\App\Http\Controllers\Reseller\ResellerDashboardController::class, 'tickets'])->name('reseller.tickets')->middleware('check.permission:reseller.tickets');
    Route::post('/tickets', [\App\Http\Controllers\Reseller\ResellerDashboardController::class, 'createTicket'])->name('reseller.tickets.create')->middleware('check.permission:reseller.tickets');
    Route::get('/tickets-manage', [\App\Http\Controllers\Reseller\ResellerModulesController::class, 'ticketsManage'])->name('reseller.tickets-manage')->middleware('check.permission:reseller.tickets');
    Route::post('/tickets-manage', [\App\Http\Controllers\Reseller\ResellerModulesController::class, 'storeTicket'])->name('reseller.tickets-manage.store')->middleware('check.permission:reseller.tickets');
    Route::put('/tickets-manage/{ticket}', [\App\Http\Controllers\Reseller\ResellerModulesController::class, 'updateTicket'])->name('reseller.tickets-manage.update')->middleware('check.permission:reseller.tickets');
    Route::delete('/tickets-manage/{ticket}', [\App\Http\Controllers\Reseller\ResellerModulesController::class, 'deleteTicket'])->name('reseller.tickets-manage.delete')->middleware('check.permission:reseller.tickets');
    Route::get('/collection-report', [\App\Http\Controllers\Reseller\ResellerDashboardController::class, 'collectionReport'])->name('reseller.collection-report')->middleware('check.permission:reseller.collection');

    // MAC Binding reseller only routes
    Route::middleware('mac.reseller')->group(function () {
        // Static client routes MUST come before {client} wildcard
        Route::get('/clients/create', [\App\Http\Controllers\Reseller\ResellerModulesController::class, 'createClient'])->name('reseller.clients.create')->middleware('reseller.feature:client_create');
        Route::post('/clients', [\App\Http\Controllers\Reseller\ResellerModulesController::class, 'storeClient'])->name('reseller.clients.store')->middleware('reseller.feature:client_create');

        // Clients (feature: clients)
        Route::middleware('reseller.feature:clients')->group(function () {
            Route::get('/clients', [\App\Http\Controllers\Reseller\ResellerDashboardController::class, 'clients'])->name('reseller.clients')->middleware('check.permission:reseller.clients');
            Route::get('/clients/due', [\App\Http\Controllers\Reseller\ResellerDashboardController::class, 'dueClients'])->name('reseller.clients.due')->middleware('check.permission:reseller.clients');
            Route::get('/clients/{client}', [\App\Http\Controllers\Reseller\ResellerDashboardController::class, 'showClient'])->name('reseller.clients.show')->middleware('check.permission:reseller.clients');
            Route::get('/clients/{client}/payment-history', [\App\Http\Controllers\Reseller\ResellerDashboardController::class, 'paymentHistory'])->name('reseller.clients.payment-history')->middleware('check.permission:reseller.clients');
            Route::get('/clients/{client}/live-bandwidth', [\App\Http\Controllers\Admin\LiveMonitorController::class, 'getBandwidth'])->name('reseller.clients.live-bandwidth');
            Route::get('/clients/{client}/live-ping', [\App\Http\Controllers\Admin\LiveMonitorController::class, 'ping'])->name('reseller.clients.live-ping');
            Route::get('/clients/{client}/ping-stream', [\App\Http\Controllers\Admin\LiveMonitorController::class, 'pingStream'])->name('reseller.clients.ping-stream');
            Route::get('/network/onu/{onu}/live-info', [\App\Http\Controllers\Admin\OltController::class, 'getOnuLiveInfo'])->name('reseller.olt.onu-live-info');
        });
        // Edit clients (feature: client_edit)
        Route::middleware('reseller.feature:client_edit')->group(function () {
            Route::get('/clients/{client}/edit', [\App\Http\Controllers\Reseller\ResellerModulesController::class, 'editClient'])->name('reseller.clients.edit')->middleware('check.permission:reseller.client_edit');
            Route::put('/clients/{client}', [\App\Http\Controllers\Reseller\ResellerModulesController::class, 'updateClient'])->name('reseller.clients.update')->middleware('check.permission:reseller.client_edit');
            Route::post('/clients/{client}/change-package', [\App\Http\Controllers\Reseller\ResellerDashboardController::class, 'changePackage'])->name('reseller.clients.change-package');
            Route::post('/clients/{client}/change-pppoe-password', [\App\Http\Controllers\Reseller\ResellerDashboardController::class, 'changePppoePassword'])->name('reseller.clients.change-pppoe-password');
        });
        Route::delete('/clients/{client}', [\App\Http\Controllers\Reseller\ResellerModulesController::class, 'deleteClient'])->name('reseller.clients.delete');
        Route::post('/clients/{client}/restore', [\App\Http\Controllers\Reseller\ResellerModulesController::class, 'restoreClient'])->name('reseller.clients.restore')->middleware('reseller.feature:client_delete_restore');
        Route::delete('/clients/{client}/force-delete', [\App\Http\Controllers\Reseller\ResellerModulesController::class, 'deleteClient'])->name('reseller.clients.force-delete')->middleware('reseller.feature:client_delete_restore');
        // Device control (feature: device_control)
        Route::middleware('reseller.feature:device_control')->group(function () {
            Route::post('/clients/{client}/sync', [\App\Http\Controllers\Reseller\ResellerDashboardController::class, 'syncClient'])->name('reseller.clients.sync')->middleware('check.permission:reseller.device_control');
            Route::post('/clients/{client}/enable', [\App\Http\Controllers\Reseller\ResellerDashboardController::class, 'enableClient'])->name('reseller.clients.enable')->middleware('check.permission:reseller.device_control');
            Route::post('/clients/{client}/disable', [\App\Http\Controllers\Reseller\ResellerDashboardController::class, 'disableClient'])->name('reseller.clients.disable')->middleware('check.permission:reseller.device_control');
            Route::post('/clients/{client}/disconnect', [\App\Http\Controllers\Reseller\ResellerDashboardController::class, 'disconnectClient'])->name('reseller.clients.disconnect')->middleware('check.permission:reseller.device_control');
        });
        // Collection (feature: collection)
        Route::middleware('reseller.feature:collection')->group(function () {
            Route::post('/clients/{client}/generate-bill', [\App\Http\Controllers\Reseller\ResellerDashboardController::class, 'generateClientBill'])->name('reseller.clients.generate-bill')->middleware('check.permission:reseller.collection');
            Route::get('/clients/{client}/collect-payment', [\App\Http\Controllers\Reseller\ResellerModulesController::class, 'collectPaymentForm'])->name('reseller.clients.collect-payment');
            Route::post('/clients/{client}/collect-payment', [\App\Http\Controllers\Reseller\ResellerDashboardController::class, 'collectPayment'])->name('reseller.collect-payment')->middleware(['check.permission:reseller.collection']);
        });

        // Reports & Packages
        Route::get('/packages', [\App\Http\Controllers\Reseller\ResellerDashboardController::class, 'packages'])->name('reseller.packages')->middleware(['check.permission:reseller.packages', 'reseller.feature:packages']);

    Route::get('/due-report', [\App\Http\Controllers\Reseller\ResellerDashboardController::class, 'dueReport'])->name('reseller.due-report')->middleware(['check.permission:reseller.due_report', 'reseller.feature:due_report']);
        Route::get('/api/chart-data', [\App\Http\Controllers\Reseller\ResellerDashboardController::class, 'chartData'])->name('reseller.api.chart-data');
        Route::get('/api/quick-search', [\App\Http\Controllers\Reseller\QuickSearchController::class, 'search'])->name('reseller.api.quick-search');
        Route::get('/api/search-clients', [\App\Http\Controllers\Admin\ClientController::class, 'searchClientsApi'])->name('reseller.api.search-clients');
        // Online clients (feature: online_clients)
        Route::get('/online-clients', [\App\Http\Controllers\Reseller\ResellerDashboardController::class, 'onlineClients'])->name('reseller.online-clients')->middleware('reseller.feature:online_clients');
        Route::get('/api/online-clients', [\App\Http\Controllers\Reseller\ResellerDashboardController::class, 'onlineClientsApi'])->name('reseller.api.online-clients');

        // Billing / Recharge Module (feature: billing)
        Route::middleware('reseller.feature:billing')->group(function () {
            Route::post('/recharge-client', [\App\Http\Controllers\Reseller\ResellerModulesController::class, 'rechargeClient'])->name('reseller.recharge-client')->middleware('check.permission:reseller.clients');
            Route::post('/bulk-recharge-clients', [\App\Http\Controllers\Reseller\ResellerModulesController::class, 'bulkRechargeClients'])->name('reseller.bulk-recharge-clients')->middleware('check.permission:reseller.clients');
            Route::get('/bills', [\App\Http\Controllers\Reseller\ResellerModulesController::class, 'bills'])->name('reseller.bills');
            Route::post('/bills/generate', [\App\Http\Controllers\Reseller\ResellerModulesController::class, 'generateBills'])->name('reseller.bills.generate');
        });

        // Credited & Recharge Transactions (feature: recharge_correction — admin-controlled)
        Route::middleware('reseller.feature:recharge_correction')->group(function () {
            Route::get('/client-transactions', [\App\Http\Controllers\Reseller\ResellerModulesController::class, 'clientTransactions'])->name('reseller.client-transactions');
            Route::post('/curtail-client', [\App\Http\Controllers\Reseller\ResellerModulesController::class, 'curtailClient'])->name('reseller.curtail-client');
            Route::post('/correct-recharge', [\App\Http\Controllers\Reseller\ResellerModulesController::class, 'correctRecharge'])->name('reseller.correct-recharge');
        });

        // Recharge Reversal (separate feature: recharge_reversal — independently admin-controlled)
        Route::middleware('reseller.feature:recharge_reversal')->group(function () {
            Route::post('/reverse-recharge', [\App\Http\Controllers\Reseller\ResellerModulesController::class, 'reverseRecharge'])->name('reseller.reverse-recharge');
            Route::get('/reversed-transactions', [\App\Http\Controllers\Reseller\ResellerModulesController::class, 'reversedTransactions'])->name('reseller.reversed-transactions');
        });

        // Zone/Subzone/TJ Management (feature: zones)
        Route::middleware('reseller.feature:zones')->group(function () {
            Route::get('/zones', [\App\Http\Controllers\Reseller\ResellerModulesController::class, 'zones'])->name('reseller.zones');
            Route::post('/zones', [\App\Http\Controllers\Reseller\ResellerModulesController::class, 'storeZone'])->name('reseller.zones.store');
            Route::put('/zones/{zone}', [\App\Http\Controllers\Reseller\ResellerModulesController::class, 'updateZone'])->name('reseller.zones.update');
            Route::delete('/zones/{zone}', [\App\Http\Controllers\Reseller\ResellerModulesController::class, 'deleteZone'])->name('reseller.zones.delete');
            Route::post('/subzones', [\App\Http\Controllers\Reseller\ResellerModulesController::class, 'storeSubzone'])->name('reseller.subzones.store');
            Route::put('/subzones/{area}', [\App\Http\Controllers\Reseller\ResellerModulesController::class, 'updateSubzone'])->name('reseller.subzones.update');
            Route::delete('/subzones/{area}', [\App\Http\Controllers\Reseller\ResellerModulesController::class, 'deleteSubzone'])->name('reseller.subzones.delete');
            Route::post('/tjs', [\App\Http\Controllers\Reseller\ResellerModulesController::class, 'storeTj'])->name('reseller.tjs.store');
            Route::put('/tjs/{box}', [\App\Http\Controllers\Reseller\ResellerModulesController::class, 'updateTj'])->name('reseller.tjs.update');
            Route::delete('/tjs/{box}', [\App\Http\Controllers\Reseller\ResellerModulesController::class, 'deleteTj'])->name('reseller.tjs.delete');
        });

        // Package Price Management (feature: packages_manage)
        Route::middleware('reseller.feature:packages_manage')->group(function () {
            Route::get('/packages-manage', [\App\Http\Controllers\Reseller\ResellerModulesController::class, 'packagesManage'])->name('reseller.packages-manage');
            Route::post('/packages', [\App\Http\Controllers\Reseller\ResellerModulesController::class, 'storePackage'])->name('reseller.packages.store');
            Route::put('/packages/{resellerPackage}', [\App\Http\Controllers\Reseller\ResellerModulesController::class, 'updatePackage'])->name('reseller.packages.update');
            Route::delete('/packages/{resellerPackage}', [\App\Http\Controllers\Reseller\ResellerModulesController::class, 'deletePackage'])->name('reseller.packages.delete');
        });

        // MAC Reseller own billing & invoices
        Route::get('/mac-billing', [\App\Http\Controllers\Reseller\ResellerDashboardController::class, 'macBilling'])->name('reseller.mac-billing');
        Route::get('/mac-bills/{bill}/invoice', [\App\Http\Controllers\Reseller\ResellerDashboardController::class, 'macInvoice'])->name('reseller.mac-invoice');
        Route::get('/mac-bills/{bill}/download', [\App\Http\Controllers\Reseller\ResellerDashboardController::class, 'macInvoiceDownload'])->name('reseller.mac-invoice.download');

        // Installation Charges
        Route::get("/installation-charges", [\App\Http\Controllers\Reseller\ResellerModulesController::class, "installationCharges"])->name("reseller.installation-charges");
        Route::post("/clients/{client}/pay-installation-charge", [\App\Http\Controllers\Reseller\ResellerModulesController::class, "payInstallationCharge"])->name("reseller.clients.pay-installation-charge");
        Route::put("/installation-charges/{charge}", [\App\Http\Controllers\Reseller\ResellerModulesController::class, "updateInstallationCharge"])->name("reseller.installation-charges.update");

    }); // end mac.reseller middleware

    // Network (feature: network)
    Route::get('/network-diagram', [\App\Http\Controllers\Reseller\ResellerModulesController::class, 'networkDiagram'])->name('reseller.network-diagram')->middleware('reseller.feature:network');
    Route::get('/olt-management', [\App\Http\Controllers\Reseller\ResellerModulesController::class, 'oltManagement'])->name('reseller.olt-management')->middleware('reseller.feature:network');

    // SMS Notifications (feature: sms)
    Route::middleware('reseller.feature:sms')->group(function () {
        // Settings & Gateway
        Route::get('/sms/settings', [\App\Http\Controllers\Reseller\ResellerSmsController::class, 'settings'])->name('reseller.sms.settings');
        Route::post('/sms/settings', [\App\Http\Controllers\Reseller\ResellerSmsController::class, 'saveSettings'])->name('reseller.sms.settings.save');
        Route::post('/sms/test', [\App\Http\Controllers\Reseller\ResellerSmsController::class, 'testSms'])->name('reseller.sms.test');
        Route::get('/sms/balance', [\App\Http\Controllers\Reseller\ResellerSmsController::class, 'checkBalance'])->name('reseller.sms.balance');
        // Templates
        Route::get('/sms/templates', [\App\Http\Controllers\Reseller\ResellerSmsController::class, 'templates'])->name('reseller.sms.templates');
        Route::post('/sms/templates', [\App\Http\Controllers\Reseller\ResellerSmsController::class, 'storeTemplate'])->name('reseller.sms.templates.store');
        Route::put('/sms/templates/{template}', [\App\Http\Controllers\Reseller\ResellerSmsController::class, 'updateTemplate'])->name('reseller.sms.templates.update');
        Route::delete('/sms/templates/{template}', [\App\Http\Controllers\Reseller\ResellerSmsController::class, 'destroyTemplate'])->name('reseller.sms.templates.destroy');
        // Bulk SMS
        Route::get('/sms/bulk', [\App\Http\Controllers\Reseller\ResellerSmsController::class, 'bulk'])->name('reseller.sms.bulk');
        Route::post('/sms/bulk', [\App\Http\Controllers\Reseller\ResellerSmsController::class, 'sendBulk'])->name('reseller.sms.bulk.send');
        // Individual SMS
        Route::get('/sms/individual', [\App\Http\Controllers\Reseller\ResellerSmsController::class, 'individual'])->name('reseller.sms.individual');
        Route::post('/sms/individual', [\App\Http\Controllers\Reseller\ResellerSmsController::class, 'sendIndividual'])->name('reseller.sms.individual.send');
        // Auto Process
        Route::get('/sms/auto-process', [\App\Http\Controllers\Reseller\ResellerSmsController::class, 'autoProcess'])->name('reseller.sms.auto-process');
        Route::post('/sms/auto-process', [\App\Http\Controllers\Reseller\ResellerSmsController::class, 'saveAutoProcess'])->name('reseller.sms.auto-process.save');
        // Logs
        Route::get('/sms/logs', [\App\Http\Controllers\Reseller\ResellerSmsController::class, 'logs'])->name('reseller.sms.logs');
        // Send to individual client
        Route::post('/sms/client/{client}', [\App\Http\Controllers\Reseller\ResellerSmsController::class, 'sendToClient'])->name('reseller.sms.client.send');
    });

    // Events & Holidays
    Route::get('/events', [\App\Http\Controllers\Reseller\ResellerModulesController::class, 'events'])->name('reseller.events');
    Route::post('/events', [\App\Http\Controllers\Reseller\ResellerModulesController::class, 'storeEvent'])->name('reseller.events.store');
    Route::delete('/events/{event}', [\App\Http\Controllers\Reseller\ResellerModulesController::class, 'deleteEvent'])->name('reseller.events.delete');
    
    // Task Management
    Route::get('/tasks', [\App\Http\Controllers\Reseller\ResellerModulesController::class, 'tasks'])->name('reseller.tasks');
    Route::post('/tasks', [\App\Http\Controllers\Reseller\ResellerModulesController::class, 'storeTask'])->name('reseller.tasks.store');
    Route::put('/tasks/{task}/status', [\App\Http\Controllers\Reseller\ResellerModulesController::class, 'updateTaskStatus'])->name('reseller.tasks.status');
    Route::delete('/tasks/{task}', [\App\Http\Controllers\Reseller\ResellerModulesController::class, 'deleteTask'])->name('reseller.tasks.delete');
    
    // Purchase & Sales
    Route::get('/purchases', [\App\Http\Controllers\Reseller\ResellerModulesController::class, 'purchases'])->name('reseller.purchases');
    Route::get('/products', [\App\Http\Controllers\Reseller\ResellerModulesController::class, 'products'])->name('reseller.products');
    Route::get('/sales', [\App\Http\Controllers\Reseller\ResellerModulesController::class, 'sales'])->name('reseller.sales');
});

Route::get('/signup', [\App\Http\Controllers\Public\OtpSignupController::class, 'showSignupForm'])->name('public.signup');
Route::post('/signup/send-otp', [\App\Http\Controllers\Public\OtpSignupController::class, 'sendOtp'])->name('public.signup.send-otp');
Route::post('/signup/verify-otp', [\App\Http\Controllers\Public\OtpSignupController::class, 'verifyOtp'])->name('public.signup.verify-otp');
Route::post('/signup/register', [\App\Http\Controllers\Public\OtpSignupController::class, 'register'])->name('public.signup.register');

Route::get('/pay/{token}', [\App\Http\Controllers\Public\PaymentLinkPublicController::class, 'show'])->name('public.payment-link');
Route::post('/pay/{token}', [\App\Http\Controllers\Public\PaymentLinkPublicController::class, 'processPayment'])->name('public.payment-link.pay');

Route::prefix('sub-reseller')->middleware(['auth', 'verified', 'sub_reseller'])->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\SubReseller\SubResellerDashboardController::class, 'index'])->name('sub-reseller.dashboard');
    Route::get('/clients', [\App\Http\Controllers\SubReseller\SubResellerDashboardController::class, 'clients'])->name('sub-reseller.clients');
    Route::get('/clients/create', [\App\Http\Controllers\SubReseller\SubResellerDashboardController::class, 'createClient'])->name('sub-reseller.clients.create');
    Route::post('/clients', [\App\Http\Controllers\SubReseller\SubResellerDashboardController::class, 'storeClient'])->name('sub-reseller.clients.store');
    Route::get('/billing', [\App\Http\Controllers\SubReseller\SubResellerDashboardController::class, 'billing'])->name('sub-reseller.billing');
    Route::get('/wallet', [\App\Http\Controllers\SubReseller\SubResellerDashboardController::class, 'wallet'])->name('sub-reseller.wallet');
    Route::get('/commissions', [\App\Http\Controllers\SubReseller\SubResellerDashboardController::class, 'commissions'])->name('sub-reseller.commissions');
    Route::post('/collect-payment', [\App\Http\Controllers\SubReseller\SubResellerDashboardController::class, 'collectPayment'])->name('sub-reseller.collect-payment');
    Route::post('/billing/collect', [\App\Http\Controllers\SubReseller\SubResellerDashboardController::class, 'collectPayment'])->name('sub-reseller.billing.collect');
    Route::get('/clients/{client}', [\App\Http\Controllers\SubReseller\SubResellerDashboardController::class, 'clients'])->name('sub-reseller.clients.show');
    Route::get('/clients/{client}/edit', [\App\Http\Controllers\SubReseller\SubResellerDashboardController::class, 'clients'])->name('sub-reseller.clients.edit');
    Route::get('/api/search-clients', [\App\Http\Controllers\Admin\ClientController::class, 'searchClientsApi'])->name('sub-reseller.api.search-clients');

    // Selfie Attendance
    Route::get('/attendance', [\App\Http\Controllers\SubReseller\SubResellerDashboardController::class, 'myAttendance'])->name('sub-reseller.attendance');
    Route::post('/attendance', [\App\Http\Controllers\SubReseller\SubResellerDashboardController::class, 'storeAttendance'])->name('sub-reseller.attendance.store');
});

Route::prefix('manager')->middleware(['auth', 'verified', 'manager'])->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\Manager\ManagerDashboardController::class, 'index'])->name('manager.dashboard');
    Route::get('/clients', [\App\Http\Controllers\Manager\ManagerDashboardController::class, 'clients'])->name('manager.clients');
    Route::get('/billing', [\App\Http\Controllers\Manager\ManagerDashboardController::class, 'billing'])->name('manager.billing');
    Route::get('/reports', [\App\Http\Controllers\Manager\ManagerDashboardController::class, 'reports'])->name('manager.reports');
    Route::get('/pops', [\App\Http\Controllers\Manager\ManagerDashboardController::class, 'pops'])->name('manager.pops');
    Route::get('/resellers', [\App\Http\Controllers\Manager\ManagerDashboardController::class, 'resellers'])->name('manager.resellers');
    Route::post('/collect-payment', [\App\Http\Controllers\Manager\ManagerDashboardController::class, 'collectPayment'])->name('manager.collect-payment');
    Route::get('/clients/{client}', [\App\Http\Controllers\Manager\ManagerDashboardController::class, 'clients'])->name('manager.clients.show');
    Route::get('/billing/{bill}', [\App\Http\Controllers\Manager\ManagerDashboardController::class, 'billing'])->name('manager.billing.show');
    Route::get('/billing/{bill}/print', [\App\Http\Controllers\Manager\ManagerDashboardController::class, 'billing'])->name('manager.billing.print');

    // Selfie Attendance
    Route::get('/attendance', [\App\Http\Controllers\Manager\ManagerDashboardController::class, 'myAttendance'])->name('manager.attendance');
    Route::post('/attendance', [\App\Http\Controllers\Manager\ManagerDashboardController::class, 'storeAttendance'])->name('manager.attendance.store');
});

Route::prefix('card-distributor')->middleware(['auth', 'verified', 'card_distributor'])->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\CardDistributor\CardDistributorDashboardController::class, 'index'])->name('card-distributor.dashboard');
    Route::get('/cards', [\App\Http\Controllers\CardDistributor\CardDistributorDashboardController::class, 'cards'])->name('card-distributor.cards');
    Route::get('/cards/{card}', [\App\Http\Controllers\CardDistributor\CardDistributorDashboardController::class, 'cards'])->name('card-distributor.cards.show');
    Route::get('/sales', [\App\Http\Controllers\CardDistributor\CardDistributorDashboardController::class, 'sales'])->name('card-distributor.sales');
    Route::post('/sell-card', [\App\Http\Controllers\CardDistributor\CardDistributorDashboardController::class, 'sellCard'])->name('card-distributor.sell-card');
    Route::get('/commissions', [\App\Http\Controllers\CardDistributor\CardDistributorDashboardController::class, 'commissions'])->name('card-distributor.commissions');
    Route::get('/wallet', [\App\Http\Controllers\CardDistributor\CardDistributorDashboardController::class, 'wallet'])->name('card-distributor.wallet');

    // Selfie Attendance
    Route::get('/attendance', [\App\Http\Controllers\CardDistributor\CardDistributorDashboardController::class, 'myAttendance'])->name('card-distributor.attendance');
    Route::post('/attendance', [\App\Http\Controllers\CardDistributor\CardDistributorDashboardController::class, 'storeAttendance'])->name('card-distributor.attendance.store');
});



Route::prefix('admin')->middleware(['auth', 'verified', 'admin'])->group(function () {
    Route::resource('branches', \App\Http\Controllers\Admin\BranchController::class)->names('admin.branches');
    Route::resource('pops', \App\Http\Controllers\Admin\PopController::class)->names('admin.pops');
    Route::get('/pops-reseller-info/{reseller}', [\App\Http\Controllers\Admin\PopController::class, 'resellerInfo'])->name('admin.pops.reseller-info');

    Route::get('/ipam', [\App\Http\Controllers\Admin\IpamController::class, 'index'])->name('admin.ipam.index');
    Route::get('/ipam/create', [\App\Http\Controllers\Admin\IpamController::class, 'create'])->name('admin.ipam.create');
    Route::post('/ipam', [\App\Http\Controllers\Admin\IpamController::class, 'store'])->name('admin.ipam.store');
    Route::get('/ipam/{pool}', [\App\Http\Controllers\Admin\IpamController::class, 'show'])->name('admin.ipam.show');
    Route::get('/ipam/{pool}/edit', [\App\Http\Controllers\Admin\IpamController::class, 'edit'])->name('admin.ipam.edit');
    Route::put('/ipam/{pool}', [\App\Http\Controllers\Admin\IpamController::class, 'update'])->name('admin.ipam.update');
    Route::delete('/ipam/{pool}', [\App\Http\Controllers\Admin\IpamController::class, 'destroy'])->name('admin.ipam.destroy');
    Route::post('/ipam/{pool}/allocate', [\App\Http\Controllers\Admin\IpamController::class, 'allocate'])->name('admin.ipam.allocate');
    Route::post('/ipam/release/{allocation}', [\App\Http\Controllers\Admin\IpamController::class, 'release'])->name('admin.ipam.release');

    Route::get('/minute-sales/packages', [\App\Http\Controllers\Admin\MinuteSalesController::class, 'packages'])->name('admin.minute-sales.packages');
    Route::get('/minute-sales/packages/create', [\App\Http\Controllers\Admin\MinuteSalesController::class, 'createPackage'])->name('admin.minute-sales.packages.create');
    Route::post('/minute-sales/packages', [\App\Http\Controllers\Admin\MinuteSalesController::class, 'storePackage'])->name('admin.minute-sales.packages.store');
    Route::get('/minute-sales/packages/{package}/edit', [\App\Http\Controllers\Admin\MinuteSalesController::class, 'editPackage'])->name('admin.minute-sales.packages.edit');
    Route::put('/minute-sales/packages/{package}', [\App\Http\Controllers\Admin\MinuteSalesController::class, 'updatePackage'])->name('admin.minute-sales.packages.update');
    Route::get('/minute-sales', [\App\Http\Controllers\Admin\MinuteSalesController::class, 'sales'])->name('admin.minute-sales.sales');
    Route::get('/minute-sales/sell', [\App\Http\Controllers\Admin\MinuteSalesController::class, 'sellMinutes'])->name('admin.minute-sales.sell');
    Route::post('/minute-sales/sell', [\App\Http\Controllers\Admin\MinuteSalesController::class, 'processSale'])->name('admin.minute-sales.process-sale');
    Route::get('/minute-sales/create-package', [\App\Http\Controllers\Admin\MinuteSalesController::class, 'createPackage'])->name('admin.minute-sales.create-package');
    Route::post('/minute-sales/store-package', [\App\Http\Controllers\Admin\MinuteSalesController::class, 'storePackage'])->name('admin.minute-sales.store-package');
    Route::post('/minute-sales/process-sell', [\App\Http\Controllers\Admin\MinuteSalesController::class, 'processSale'])->name('admin.minute-sales.process-sell');

    Route::get('/payment-links', [\App\Http\Controllers\Admin\PaymentLinkController::class, 'index'])->name('admin.payment-links.index');
    Route::get('/payment-links/create', [\App\Http\Controllers\Admin\PaymentLinkController::class, 'create'])->name('admin.payment-links.create');
    Route::post('/payment-links', [\App\Http\Controllers\Admin\PaymentLinkController::class, 'store'])->name('admin.payment-links.store');
    Route::post('/payment-links/{paymentLink}/cancel', [\App\Http\Controllers\Admin\PaymentLinkController::class, 'cancel'])->name('admin.payment-links.cancel');
    Route::post('/payment-links/{paymentLink}/resend', [\App\Http\Controllers\Admin\PaymentLinkController::class, 'resend'])->name('admin.payment-links.resend');
    Route::post('/payment-links/settings', [\App\Http\Controllers\Admin\PaymentLinkController::class, 'saveSettings'])->name('admin.payment-links.settings');
    Route::post('/payment-links/auto-generate', [\App\Http\Controllers\Admin\PaymentLinkController::class, 'autoGenerate'])->name('admin.payment-links.auto-generate');
    Route::post('/payment-links/bulk-auto-generate', [\App\Http\Controllers\Admin\PaymentLinkController::class, 'bulkAutoGenerate'])->name('admin.payment-links.bulk-auto-generate');
    Route::get('/payment-links/client-due', [\App\Http\Controllers\Admin\PaymentLinkController::class, 'getClientDue'])->name('admin.payment-links.client-due');

    Route::get('/voice-calls', [\App\Http\Controllers\Admin\VoiceCallController::class, 'index'])->name('admin.voice-calls.index');
    Route::get('/voice-calls/settings', [\App\Http\Controllers\Admin\VoiceCallController::class, 'settings'])->name('admin.voice-calls.settings');
    Route::post('/voice-calls/settings', [\App\Http\Controllers\Admin\VoiceCallController::class, 'updateSettings'])->name('admin.voice-calls.settings.update');
    Route::post('/voice-calls/make-call', [\App\Http\Controllers\Admin\VoiceCallController::class, 'makeCall'])->name('admin.voice-calls.make-call');
    Route::put('/voice-calls/pbx-settings', [\App\Http\Controllers\Admin\VoiceCallController::class, 'updatePbxSettings'])->name('admin.voice-calls.pbx.update');
    Route::get('/voice-calls/pbx-credentials', [\App\Http\Controllers\Admin\PbxController::class, 'credentials'])->name('admin.pbx.credentials');
    Route::post('/voice-calls/pbx-log', [\App\Http\Controllers\Admin\PbxController::class, 'logCall'])->name('admin.pbx.log');

    // Email System
    Route::get('/email',                    [\App\Http\Controllers\Admin\EmailController::class, 'index'])->name('admin.email.index');
    Route::get('/email/settings',           [\App\Http\Controllers\Admin\EmailController::class, 'settings'])->name('admin.email.settings');
    Route::post('/email/settings',          [\App\Http\Controllers\Admin\EmailController::class, 'saveSettings'])->name('admin.email.settings.save');
    Route::post('/email/test',              [\App\Http\Controllers\Admin\EmailController::class, 'testEmail'])->name('admin.email.test');
    Route::post('/email/send-to-client',    [\App\Http\Controllers\Admin\EmailController::class, 'sendToClient'])->name('admin.email.send-to-client');
    Route::post('/email/batch',             [\App\Http\Controllers\Admin\EmailController::class, 'sendBatch'])->name('admin.email.batch');
    Route::post('/email/clear-logs',        [\App\Http\Controllers\Admin\EmailController::class, 'clearLogs'])->name('admin.email.clear-logs');
    Route::delete('/email/logs/{log}',      [\App\Http\Controllers\Admin\EmailController::class, 'deleteLog'])->name('admin.email.delete-log');

    Route::get('/daily-billing', [\App\Http\Controllers\Admin\DailyBillingController::class, 'index'])->name('admin.daily-billing.index');
    Route::post('/daily-billing/configure', [\App\Http\Controllers\Admin\DailyBillingController::class, 'configure'])->name('admin.daily-billing.configure');
    Route::get('/daily-billing/{reseller}/history', [\App\Http\Controllers\Admin\DailyBillingController::class, 'history'])->name('admin.daily-billing.history');
    Route::post('/daily-billing/process', [\App\Http\Controllers\Admin\DailyBillingController::class, 'processBilling'])->name('admin.daily-billing.process');
    Route::post('/daily-billing/store', [\App\Http\Controllers\Admin\DailyBillingController::class, 'configure'])->name('admin.daily-billing.store');
    Route::get('/daily-billing/{config}/edit', [\App\Http\Controllers\Admin\DailyBillingController::class, 'configure'])->name('admin.daily-billing.edit');
    Route::delete('/daily-billing/{config}', [\App\Http\Controllers\Admin\DailyBillingController::class, 'configure'])->name('admin.daily-billing.destroy');
    Route::delete('/minute-sales/packages/{package}', [\App\Http\Controllers\Admin\MinuteSalesController::class, 'destroyPackage'])->name('admin.minute-sales.destroy-package');
});

Route::prefix('api/v1')->group(function () {
    Route::post('/auth/login', [\App\Http\Controllers\Api\V1\AuthController::class, 'login']);
    Route::post('/auth/client-login', [\App\Http\Controllers\Api\V1\AuthController::class, 'clientLogin']);
    Route::post('/auth/refresh', [\App\Http\Controllers\Api\V1\AuthController::class, 'refresh']);
    Route::post('/auth/logout', [\App\Http\Controllers\Api\V1\AuthController::class, 'logout']);

    Route::middleware('api.token')->group(function () {
        Route::get('/client/profile', [\App\Http\Controllers\Api\V1\ClientApiController::class, 'profile']);
        Route::get('/client/bills', [\App\Http\Controllers\Api\V1\ClientApiController::class, 'bills']);
        Route::get('/client/payments', [\App\Http\Controllers\Api\V1\ClientApiController::class, 'payments']);
        Route::get('/client/packages', [\App\Http\Controllers\Api\V1\ClientApiController::class, 'packages']);
        Route::get('/client/minute-balance', [\App\Http\Controllers\Api\V1\ClientApiController::class, 'minuteBalance']);
        Route::get('/client/status', [\App\Http\Controllers\Api\V1\ClientApiController::class, 'status']);
    });
});
use App\Http\Controllers\AutomationSettingController;

Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/billing/automation-settings', [AutomationSettingController::class, 'index'])
        ->name('billing.automation.settings');

    Route::post('/billing/automation-settings', [AutomationSettingController::class, 'store'])
        ->name('billing.automation.settings.store');

    Route::post('/billing/skip-dates', [AutomationSettingController::class, 'addSkipDate'])
        ->name('billing.skip-dates.store');

    Route::put('/billing/skip-dates/{id}', [AutomationSettingController::class, 'updateSkipDate'])
        ->name('billing.skip-dates.update');

    Route::delete('/billing/skip-dates/{id}', [AutomationSettingController::class, 'removeSkipDate'])
        ->name('billing.skip-dates.destroy');
});




Route::get('/admin/companies/switch-back', [\App\Http\Controllers\SuperAdmin\CompanyManagementController::class, 'switchBack'])
    ->middleware('auth')
    ->name('superadmin.switch-back');

Route::middleware(['auth', 'super_admin'])->prefix('admin/companies')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\SuperAdmin\DashboardController::class, 'index'])->name('superadmin.dashboard');
    Route::get('/', [\App\Http\Controllers\SuperAdmin\CompanyManagementController::class, 'index'])->name('superadmin.companies.index');
    Route::get('/create', [\App\Http\Controllers\SuperAdmin\CompanyManagementController::class, 'create'])->name('superadmin.companies.create');
    Route::post('/', [\App\Http\Controllers\SuperAdmin\CompanyManagementController::class, 'store'])->name('superadmin.companies.store');
    Route::get('/{company}', [\App\Http\Controllers\SuperAdmin\CompanyManagementController::class, 'show'])->name('superadmin.companies.show');
    Route::get('/{company}/edit', [\App\Http\Controllers\SuperAdmin\CompanyManagementController::class, 'edit'])->name('superadmin.companies.edit');
    Route::put('/{company}', [\App\Http\Controllers\SuperAdmin\CompanyManagementController::class, 'update'])->name('superadmin.companies.update');
    Route::post('/{company}/toggle-status', [\App\Http\Controllers\SuperAdmin\CompanyManagementController::class, 'toggleStatus'])->name('superadmin.companies.toggle-status');
    Route::post('/{company}/login-as', [\App\Http\Controllers\SuperAdmin\CompanyManagementController::class, 'loginAs'])->name('superadmin.companies.login-as');
    Route::delete('/{company}', [\App\Http\Controllers\SuperAdmin\CompanyManagementController::class, 'destroy'])->name('superadmin.companies.destroy');
});


// Android SMS Receive API (no auth required, token-based)
Route::post('/api/sms/receive', [App\Http\Controllers\Admin\AndroidSmsController::class, 'receive'])->middleware('throttle:60,1');

// Admin Notes
Route::prefix("admin")->middleware(["auth", "admin"])->group(function () {
    Route::get("/notes", [\App\Http\Controllers\Admin\AdminNoteController::class, "index"])->name("admin.notes.index");
    Route::post("/notes", [\App\Http\Controllers\Admin\AdminNoteController::class, "store"])->name("admin.notes.store");
    Route::get("/notes/{adminNote}", [\App\Http\Controllers\Admin\AdminNoteController::class, "show"])->name("admin.notes.show");
    Route::put("/notes/{adminNote}", [\App\Http\Controllers\Admin\AdminNoteController::class, "update"])->name("admin.notes.update");
    Route::delete("/notes/{adminNote}", [\App\Http\Controllers\Admin\AdminNoteController::class, "destroy"])->name("admin.notes.destroy");
    Route::post("/notes/{adminNote}/toggle-pin", [\App\Http\Controllers\Admin\AdminNoteController::class, "togglePin"])->name("admin.notes.toggle-pin");
});

// ZTP (Zero-Touch Provisioning) routes
Route::prefix('admin')->middleware(['auth', 'admin'])->group(function () {
    Route::post('/clients/{client}/ztp-provision', [\App\Http\Controllers\Admin\ZtpController::class, 'provision'])->name('admin.clients.ztp-provision');
    Route::get('/settings/ztp', [\App\Http\Controllers\Admin\ZtpController::class, 'settings'])->name('admin.settings.ztp');
    Route::post('/settings/ztp', [\App\Http\Controllers\Admin\ZtpController::class, 'saveSettings'])->name('admin.settings.ztp.save');
    Route::get('/settings/ztp-logs', [\App\Http\Controllers\Admin\ZtpController::class, 'logs'])->name('admin.settings.ztp.logs');
});

// Browser Notification - New Ticket Polling
Route::prefix('admin')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/notifications/new-tickets', [\App\Http\Controllers\Admin\SupportController::class, 'newTicketsPoll'])->name('admin.notifications.new-tickets');
});

// Media Servers
Route::prefix("admin")->middleware(["auth", "admin"])->group(function () {
    Route::get("/media-servers", [\App\Http\Controllers\Admin\MediaServerController::class, "index"])->name("admin.media-servers.index");
    Route::post("/media-servers", [\App\Http\Controllers\Admin\MediaServerController::class, "store"])->name("admin.media-servers.store");
    Route::put("/media-servers/{mediaServer}", [\App\Http\Controllers\Admin\MediaServerController::class, "update"])->name("admin.media-servers.update");
    Route::delete("/media-servers/{mediaServer}", [\App\Http\Controllers\Admin\MediaServerController::class, "destroy"])->name("admin.media-servers.destroy");
});
