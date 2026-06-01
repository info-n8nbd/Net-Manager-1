<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Mobile\MobileApiController;

Route::prefix('mobile')->group(function () {
    Route::post('/login', [MobileApiController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout',                         [MobileApiController::class, 'logout']);
        Route::get('/profile',                         [MobileApiController::class, 'profile']);
        Route::get('/dashboard',                       [MobileApiController::class, 'dashboard']);
        Route::get('/clients',                         [MobileApiController::class, 'clients']);
        Route::get('/clients/{id}',                    [MobileApiController::class, 'clientDetail']);
        Route::post('/clients/{id}/collect-payment',   [MobileApiController::class, 'collectPayment']);
        Route::get('/payments',                        [MobileApiController::class, 'payments']);
        Route::get('/billing',                         [MobileApiController::class, 'billing']);
        Route::get('/resellers',                       [MobileApiController::class, 'resellers']);
        Route::get('/tickets',                         [MobileApiController::class, 'tickets']);
        Route::get('/tickets/{id}',                    [MobileApiController::class, 'ticketDetail']);
        Route::get('/applications',                    [MobileApiController::class, 'applications']);
        Route::get('/network',                         [MobileApiController::class, 'network']);
        Route::get('/reports',                         [MobileApiController::class, 'reports']);
        Route::get('/payment-methods',                 [MobileApiController::class, 'paymentMethods']);
    });
});

// ─── V1 Automation API (n8n, Make.com, Zapier compatible) ───────────────────
// Auth: Authorization: Bearer {api_token}
use App\Http\Controllers\Api\V1\AutomationApiController;

Route::prefix('v1')->middleware('api.token')->group(function () {

    // Stats / Dashboard
    Route::get('/stats',                            [AutomationApiController::class, 'stats']);

    // Clients
    Route::get('/clients',                          [AutomationApiController::class, 'listClients']);
    Route::get('/clients/{id}',                     [AutomationApiController::class, 'getClient']);
    Route::post('/clients/{id}/activate',           [AutomationApiController::class, 'activateClient']);
    Route::post('/clients/{id}/suspend',            [AutomationApiController::class, 'suspendClient']);

    // Payments
    Route::get('/payments',                         [AutomationApiController::class, 'listPayments']);
    Route::post('/payments',                        [AutomationApiController::class, 'createPayment']);

    // Bills
    Route::get('/bills',                            [AutomationApiController::class, 'listBills']);

    // Packages
    Route::get('/packages',                         [AutomationApiController::class, 'listPackages']);

    // Tickets
    Route::get('/tickets',                          [AutomationApiController::class, 'listTickets']);
    Route::post('/tickets',                         [AutomationApiController::class, 'createTicket']);

    // Zones & Areas (for reference)
    Route::get('/zones',                            [AutomationApiController::class, 'listZones']);
    Route::get('/areas',                            [AutomationApiController::class, 'listAreas']);
});

