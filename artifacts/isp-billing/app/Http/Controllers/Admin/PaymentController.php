<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Bill;
use App\Models\Client;
use App\Models\PaymentMethod;
use App\Models\ActivityLog;
use App\Models\StaffDue;
use App\Services\LedgerService;
use App\Services\SmsService;
use App\Services\BillingService;
use App\Events\PaymentReceived;
use App\Events\ClientStatusChanged;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    protected LedgerService $ledgerService;
    protected SmsService $smsService;

    public function __construct(LedgerService $ledgerService, SmsService $smsService)
    {
        $this->ledgerService = $ledgerService;
        $this->smsService = $smsService;
    }

    public function index(Request $request)
    {
        $query = Payment::with(['client', 'paymentMethod', 'collectedBy']);
        
        if ($request->date) {
            $query->whereDate('payment_date', $request->date);
        }
        if ($request->client_id) {
            $query->where('client_id', $request->client_id);
        }
        if ($request->payment_method_id) {
            $query->where('payment_method_id', $request->payment_method_id);
        }
        if ($request->bkash_number) {
            $query->where('remarks', 'ILIKE', '%' . $request->bkash_number . '%');
        }
        if ($request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('transaction_id', 'ILIKE', "%{$search}%")
                  ->orWhere('payment_no', 'ILIKE', "%{$search}%")
                  ->orWhere('remarks', 'ILIKE', "%{$search}%")
                  ->orWhereHas('client', function($cq) use ($search) {
                      $cq->where('name', 'ILIKE', "%{$search}%")
                         ->orWhere('client_id', 'ILIKE', "%{$search}%");
                  });
            });
        }
        
        $payments = $query->orderBy('created_at', 'desc')->paginate(in_array((int)request()->per_page,[20,50,100,200])?(int)request()->per_page:20)->appends($request->query());
        $paymentMethods = auth()->user()->allowedPaymentMethods();
        return view('admin.billing.payments', compact('payments', 'paymentMethods'));
    }

    public function create(Request $request)
    {
        $clients = Client::orderBy('name')->get();
        $paymentMethods = auth()->user()->allowedPaymentMethods();
        
        $selectedClient = null;
        $unpaidBills = collect();
        if ($request->has('client_id')) {
            $selectedClient = Client::find($request->client_id);
            if ($selectedClient) {
                $unpaidBills = Bill::where('client_id', $selectedClient->id)
                    ->where('is_cancelled', false)
                    ->whereIn('status', ['unpaid', 'partial'])
                    ->orderBy('billing_month', 'asc')
                    ->get();
            }
        }
        
        return view('admin.billing.payment-create', compact('clients', 'paymentMethods', 'selectedClient', 'unpaidBills'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'amount' => 'required|numeric|min:1',
            'payment_method_id' => 'required|exists:payment_methods,id',
            'payment_date' => 'required|date',
        ]);

        try {
            $client = Client::findOrFail($request->client_id);
            $user = auth()->user();
            $isAdmin = $user->hasRole('Administrator') || $user->hasRole('Super Admin');
            $isFieldStaff = $user->hasRole('Field Technician') || $user->hasRole('Billing Officer') || $user->employee;
            
            $remarks = $request->remarks;
            if ($request->filled('mobile_payment_number')) {
                $methodName = \App\Models\PaymentMethod::find($request->payment_method_id)?->name ?? 'Mobile';
                $mobileInfo = "{$methodName} Number: {$request->mobile_payment_number}";
                $remarks = $remarks ? "{$mobileInfo} | {$remarks}" : $mobileInfo;
            }

            $payment = DB::transaction(function () use ($client, $request, $remarks, $isAdmin, $isFieldStaff, $user) {
                $billingService = app(BillingService::class);
                
                $payment = $billingService->recordPayment($client, [
                    'amount' => $request->amount,
                    'payment_method_id' => $request->payment_method_id,
                    'payment_date' => $request->payment_date,
                    'transaction_id' => $request->transaction_id,
                    'money_receipt_no' => $request->money_receipt_no,
                    'remarks' => $remarks,
                    'bill_id' => $request->bill_id,
                    'sender_number' => $request->mobile_payment_number ?? $request->sender_number ?? '',
                    'reason' => $request->filled('notes_reason') ? $request->notes_reason : null,
                ], auth()->id(), $isAdmin ? 'admin' : 'technician');

                $this->ledgerService->createPaymentEntry($payment, auth()->id(), $user->role ?? 'admin');

                if (!$isAdmin && $isFieldStaff) {
                    StaffDue::lockForUpdate()->firstOrCreate(
                        ['user_id' => auth()->id()],
                        ['total_collected' => 0, 'total_submitted' => 0, 'current_due' => 0]
                    );
                    StaffDue::where('user_id', auth()->id())
                        ->increment('total_collected', $request->amount);
                    StaffDue::where('user_id', auth()->id())
                        ->increment('current_due', $request->amount);
                    StaffDue::where('user_id', auth()->id())
                        ->update(['last_collection_date' => now()]);
                }
                
                return $payment;
            });

            ActivityLog::log('create', 'payments', "Received payment of ৳{$request->amount} from {$client->name}");

            // Auto-send payment confirmation email if client has email and setting is enabled
            try {
                $emailService = app(\App\Services\EmailReminderService::class);
                $emailSettings = $emailService->getSettings();
                if ($emailSettings && $emailSettings->is_active && $emailSettings->auto_payment_confirmation && !empty($client->email)) {
                    $txnId = $request->transaction_id ?? $request->money_receipt_no ?? '';
                    $emailService->sendPaymentConfirmation($client, (float)$request->amount, $txnId);
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Payment email failed: ' . $e->getMessage());
            }

            // Auto-send payment confirmation SMS
            try {
                $paymentNo = $payment->payment_no ?? $payment->money_receipt_no ?? $payment->transaction_id ?? '';
                app(\App\Services\SmsService::class)->sendPaymentConfirmation($client, (float)$request->amount, $paymentNo);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Payment SMS failed: ' . $e->getMessage());
            }

            $client->refresh();
            if ($client->due_amount <= 0) {
                $todayDhaka = \Carbon\Carbon::today('Asia/Dhaka');
                $needsExpireDateUpdate = $client->expire_date && $client->expire_date->lte($todayDhaka);
                $newExpireDate = $needsExpireDateUpdate
                    ? \Carbon\Carbon::now('Asia/Dhaka')->addMonthNoOverflow()->toDateString()
                    : ($client->expire_date ? $client->expire_date->toDateString() : null);

                if ($client->status === 'inactive') {
                    $oldClientStatus = $client->status;
                    $client->update([
                        'status' => 'active',

                        'last_active_date' => now(),
                        'last_status_change' => now(),
                        'expire_date' => $newExpireDate,
                        'auto_inactive_note' => null,
                    ]);

                    try {
                        $device = $client->mikrotikDevice ?? \App\Models\MikrotikDevice::where('is_active', true)->first();
                        if ($device && $client->username) {
                            $mikrotik = new \App\Services\MikroTikService($device->getRawOriginal('host') ?? $device->host, $device->username, $device->password, (int)($device->getRawOriginal('port') ?? $device->port ?? 8728));
                            $originalProfile = $client->package ? ($client->package->mikrotik_profile ?: $client->package->name) : null;
                            if ($originalProfile) {
                                $mikrotik->changePPPoEProfile($client->username, $originalProfile);
                            } else {
                                $mikrotik->enablePPPoEUser($client->username);
                            }
                            $mikrotik->disconnect();
                            \Log::info("Payment: MikroTik profile restored for client {$client->id} ({$client->username})");
                        }
                    } catch (\Throwable $e) {
                        \Log::warning("Payment: MikroTik restore failed for client {$client->id}: " . $e->getMessage());
                    }

                    event(new ClientStatusChanged($client, $oldClientStatus, 'active', 'payment'));
                } elseif ($needsExpireDateUpdate) {
                    $client->update([
                        'expire_date' => $newExpireDate,
                        'auto_inactive_note' => null,
                    ]);
                }
            }

            // Return JSON for AJAX requests (instant receipt)
            if ($request->expectsJson() || $request->ajax() || $request->header('Accept') === 'application/json') {
                return response()->json([
                    'success' => true,
                    'message' => 'Payment of ৳' . number_format($request->amount, 0) . ' recorded successfully.',
                    'payment_id' => $payment->id,
                    'payment_no' => $payment->payment_no,
                    'receipt_url' => route('admin.payments.receipt', $payment),
                    'print_url' => route('admin.payments.receipt.print', $payment),
                    'thermal_url' => route('admin.payments.receipt.thermal', $payment),
                    'download_url' => route('admin.payments.receipt.download', $payment),
                ]);
            }
            
            $successMsg = 'Payment of ৳' . number_format($request->amount, 2) . ' recorded successfully.';
            
            $redirectTo = $request->input('redirect_to');
            if ($redirectTo && !empty($redirectTo) && $this->isSafeRedirect($redirectTo)) {
                return redirect()->to($redirectTo)->with('success', $successMsg);
            }
            
            return redirect()->route('admin.payments.index')->with('success', $successMsg);
        } catch (\Exception $e) {
            \Log::error('Payment recording failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            if ($request->expectsJson() || $request->ajax() || $request->header('Accept') === 'application/json') {
                return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
            }
            return back()->with('error', 'Error recording payment: ' . $e->getMessage())->withInput();
        }
    }

    public function edit(Payment $payment)
    {
        $payment->load(['client', 'paymentMethod']);
        $paymentMethods = auth()->user()->allowedPaymentMethods();
        return view('admin.billing.payment-edit', compact('payment', 'paymentMethods'));
    }

    public function update(Request $request, Payment $payment)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'payment_method_id' => 'required|exists:payment_methods,id',
            'payment_date' => 'required|date',
        ]);

        DB::beginTransaction();
        try {
            $client = Client::lockForUpdate()->find($payment->client_id);
            $billingService = app(BillingService::class);

            $payment->update([
                'amount' => $request->amount,
                'payment_method_id' => $request->payment_method_id,
                'payment_date' => $request->payment_date,
                'transaction_id' => $request->transaction_id,
                'money_receipt_no' => $request->money_receipt_no,
                'remarks' => $request->remarks,
            ]);

            $billingService->reapplyAllPaymentsToBills($client);
            $billingService->recalculateClientBalance($client);

            ActivityLog::log('update', 'payments', "Updated payment #{$payment->id} for {$client->name}");
            
            DB::commit();
            return redirect()->route('admin.payments.index')->with('success', 'Payment updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error updating payment: ' . $e->getMessage());
        }
    }

    public function destroy(Payment $payment)
    {
        $user = auth()->user();
        if ($user && !$user->hasRole('Super Admin') && !\App\Services\MenuPermissionService::canAction($user, 'billing', 'delete_payment')) {
            abort(403, 'You do not have permission to delete payments.');
        }

        DB::beginTransaction();
        try {
            $client = $payment->client;
            $amount = $payment->amount;
            $clientName = $client->name;
            $billingService = app(BillingService::class);

            BillingService::permanentlyDeletePayments([$payment->id]);

            $billingService->reapplyAllPaymentsToBills($client);
            $billingService->recalculateClientBalance($client);

            ActivityLog::log('delete', 'payments', "Permanently deleted payment of ৳{$amount} for {$clientName}");

            DB::commit();
            return redirect()->route('admin.payments.index')->with('success', 'Payment permanently deleted from database.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error deleting payment: ' . $e->getMessage());
        }
    }


    public function bulkDelete(Request $request)
    {
        $user = auth()->user();
        if ($user && !$user->hasRole('Super Admin') && !\App\Services\MenuPermissionService::canAction($user, 'billing', 'delete_payment')) {
            abort(403, 'You do not have permission to delete payments.');
        }

        $ids = $request->input('ids', []);
        if (empty($ids) || !is_array($ids)) {
            return back()->with('error', 'No payments selected.');
        }

        $ids = array_map('intval', $ids);

        DB::beginTransaction();
        try {
            // Group by client to reapply bills per client after deletion
            $payments = \App\Models\Payment::whereIn('id', $ids)->with('client')->get();
            $clientIds = $payments->pluck('client_id')->unique()->filter();
            $totalAmount = $payments->sum('amount');
            $count = $payments->count();

            BillingService::permanentlyDeletePayments($ids);

            $billingService = app(BillingService::class);
            foreach ($clientIds as $clientId) {
                $client = \App\Models\Client::find($clientId);
                if ($client) {
                    $billingService->reapplyAllPaymentsToBills($client);
                    $billingService->recalculateClientBalance($client);
                }
            }

            ActivityLog::log('delete', 'payments', "Bulk deleted {$count} payments totaling ৳{$totalAmount}");

            DB::commit();
            return redirect()->route('admin.payments.index', request()->only(['search','payment_method_id','date','bkash_number','page','per_page']))
                ->with('success', "Successfully deleted {$count} payment(s).");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error deleting payments: ' . $e->getMessage());
        }
    }

    public function quickPayment(Request $request, Client $client)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'payment_method_id' => 'required|exists:payment_methods,id',
            'transaction_id' => 'nullable|string|max:100',
            'sender_number' => 'nullable|string|max:20',
        ]);

        try {
            $notes = $request->notes_reason ? $request->notes_reason : '';
            if ($request->notes) {
                $notes .= ($notes ? ' - ' : '') . $request->notes;
            }
            if ($request->sender_number) {
                $notes = 'Sender: ' . $request->sender_number . ($notes ? ' | ' . $notes : '');
            }

            $billingService = app(BillingService::class);
            
            $payment = $billingService->recordPayment($client, [
                'amount' => $request->amount,
                'payment_method_id' => $request->payment_method_id,
                'payment_date' => now(),
                'remarks' => $notes ?: null,
                'reason' => $request->filled('notes_reason') ? $request->notes_reason : null,
                'transaction_id' => $request->transaction_id ?: null,
                'money_receipt_no' => $request->money_receipt_no ?: null,
                'sender_number' => $request->sender_number ?? '',
            ], auth()->id(), 'admin');

            // Create ledger entry
            $this->ledgerService->createPaymentEntry($payment, auth()->id(), 'admin');

            ActivityLog::log('create', 'payments', "Quick payment of ৳{$request->amount} collected from {$client->name}");


            // Dispatch SMS to background queue — do NOT block the HTTP response
            $smsClientId  = $client->id;
            $smsAmount    = (float) $request->amount;
            $smsPaymentNo = $payment->payment_no ?? '';
            $smsSend      = $request->boolean('send_sms', true);
            dispatch(function () use ($smsClientId, $smsAmount, $smsPaymentNo, $smsSend) {
                try {
                    $cl = \App\Models\Client::find($smsClientId);
                    if ($cl) {
                        app(\App\Services\SmsService::class)->sendPaymentConfirmation($cl, $smsAmount, $smsPaymentNo, $smsSend);
                    }
                } catch (\Exception $ex) {
                    \Illuminate\Support\Facades\Log::warning("Async quick payment SMS failed for client {$smsClientId}: " . $ex->getMessage());
                }
            })->onQueue('default');
            $dateTime = now()->format('d-m-y h:i:s A') . ' (BDT GMT+6)';
            $successMsg = 'Payment of ৳' . number_format($request->amount) . ' collected successfully on ' . $dateTime;

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $successMsg,
                    'payment_no' => $payment->payment_no ?? '',
                    'receipt_url' => route('admin.payments.receipt', $payment),
                    'print_url' => route('admin.payments.receipt.print', $payment),
                    'thermal_url' => route('admin.payments.receipt.thermal', $payment),
                ]);
            }
            
            $redirectTo = $request->input('redirect_to');
            if ($redirectTo && !empty($redirectTo) && $this->isSafeRedirect($redirectTo)) {
                return redirect()->to($redirectTo)->with('success', $successMsg);
            }
            
            return redirect()->to(route('admin.clients.index'))->with('success', $successMsg);
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 422);
            }
            return back()->with('error', 'Error collecting payment: ' . $e->getMessage());
        }
    }

    public function pendingPayments()
    {
        $payments = Payment::with(['client', 'paymentMethod', 'collectedBy'])
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->paginate(in_array((int)request()->per_page,[20,50,100,200])?(int)request()->per_page:20);
        
        return view('admin.billing.pending-payments', compact('payments'));
    }

    public function approvePayment(Payment $payment)
    {
        if ($payment->status !== 'pending') {
            return back()->with('error', 'This payment has already been processed.');
        }

        DB::transaction(function () use ($payment) {
            $client = Client::lockForUpdate()->find($payment->client_id);
            $oldStatus = $client->status;
            
            $payment->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'remarks' => $payment->remarks . ' | Approved by admin on ' . now()->format('d-m-y h:i A'),
            ]);

            $billingService = app(BillingService::class);
            $billingService->reapplyAllPaymentsToBills($client);
            $billingService->recalculateClientBalance($client);
            
            event(new PaymentReceived($payment, 'admin', auth()->id()));

            $client->refresh();
            if ($client->due_amount <= 0) {
                $todayDhaka = \Carbon\Carbon::today('Asia/Dhaka');
                $needsExpireDateUpdate = $client->expire_date && $client->expire_date->lte($todayDhaka);
                $newExpireDate = $needsExpireDateUpdate
                    ? \Carbon\Carbon::now('Asia/Dhaka')->addMonthNoOverflow()->toDateString()
                    : ($client->expire_date ? $client->expire_date->toDateString() : null);

                if ($client->status === 'inactive') {
                    $originalProfile = $client->package ? ($client->package->mikrotik_profile ?: $client->package->name) : null;
                    $restoreProfile = $client->previous_mikrotik_profile ?: $originalProfile;

                    $updateData = [
                        'status' => 'active',
                        'last_active_date' => now(),
                        'last_status_change' => now(),
                        'expire_date' => $newExpireDate,
                        'auto_inactive_note' => null,
                    ];
                    if ($restoreProfile) {
                        $updateData['mikrotik_profile_name'] = $restoreProfile;
                        $updateData['previous_mikrotik_profile'] = null;
                    }
                    $client->update($updateData);
                    
                    try {
                        $device = $client->mikrotikDevice ?? \App\Models\MikrotikDevice::where('is_active', true)->first();
                        if ($device && $client->username) {
                            $mikrotik = new \App\Services\MikroTikService($device->getRawOriginal('host') ?? $device->host, $device->username, $device->password, (int)($device->getRawOriginal('port') ?? $device->port ?? 8728));
                            if ($restoreProfile) {
                                $mikrotik->changePPPoEProfile($client->username, $restoreProfile);
                            } else {
                                $mikrotik->enablePPPoEUser($client->username);
                            }
                            $mikrotik->disconnect();
                            \Log::info("PaymentApproval: MikroTik profile restored to '{$restoreProfile}' for client {$client->id} ({$client->username})");
                        }
                    } catch (\Throwable $e) {
                        \Log::warning("PaymentApproval: MikroTik restore failed for client {$client->id}: " . $e->getMessage());
                    }
                    
                    event(new ClientStatusChanged($client, $oldStatus, 'active', 'payment_approval'));
                } elseif ($needsExpireDateUpdate) {
                    $client->update([
                        'expire_date' => $newExpireDate,
                        'auto_inactive_note' => null,
                    ]);
                }
            }
        });

        ActivityLog::log('update', 'payments', "Approved pending payment #{$payment->payment_no} of ৳{$payment->amount} for {$payment->client->name}");

        return back()->with('success', 'Payment approved successfully! Client balance has been updated.');
    }

    public function rejectPayment(Request $request, Payment $payment)
    {
        if ($payment->status !== 'pending') {
            return back()->with('error', 'This payment has already been processed.');
        }

        $request->validate([
            'rejection_reason' => 'required|string|max:255'
        ]);

        $payment->update([
            'status' => 'rejected',
            'remarks' => $payment->remarks . ' | Rejected: ' . $request->rejection_reason,
        ]);

        ActivityLog::log('update', 'payments', "Rejected pending payment #{$payment->payment_no} - Reason: {$request->rejection_reason}");

        return back()->with('success', 'Payment rejected. Client has been notified.');
    }
}
