<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AndroidSmsLog;
use App\Models\Client;
use App\Models\Payment;
use App\Models\SystemSetting;
use App\Services\AndroidSmsParserService;
use App\Services\BillingService;
use App\Services\PaymentGatewayService;
use App\Services\SmsService;
use App\Events\PaymentReceived;
use App\Jobs\ProcessAndroidSmsJob;
use App\Models\MikrotikDevice;
use App\Services\MikroTikService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AndroidSmsController extends Controller
{
    private function parser(): AndroidSmsParserService
    {
        return app(AndroidSmsParserService::class);
    }

    private function billingService(): BillingService
    {
        return app(BillingService::class);
    }

    private function gatewayService(): PaymentGatewayService
    {
        return app(PaymentGatewayService::class);
    }

    private function smsService(): SmsService
    {
        return app(SmsService::class);
    }

    /**
     * Admin: list all Android SMS logs
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        $query = AndroidSmsLog::with(['client', 'payment', 'locker'])
            ->where('company_id', $user->company_id)
            ->latest();

        if ($request->manual) {
            // Manual Applied filter
            $query->where('status', 'applied')
                  ->whereRaw("(parsed_data::json->>'manually_applied')::text = 'true'");
        } elseif ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->gateway) {
            $query->where('gateway', $request->gateway);
        }
        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('reference', 'like', "%$s%")
                  ->orWhere('sender_number', 'like', "%$s%")
                  ->orWhere('raw_sms', 'like', "%$s%");
            });
        }

        $logs = $query->paginate(in_array((int)request()->per_page,[20,50,100,200])?(int)request()->per_page:25)->withQueryString();

        $stats = AndroidSmsLog::where('company_id', $user->company_id)
            ->selectRaw("status, COUNT(*) as cnt")
            ->groupBy('status')
            ->pluck('cnt', 'status');

        $apiToken = SystemSetting::get('android_sms_api_token');
        $apiUrl   = url('/api/sms/receive');

        $manualCount = AndroidSmsLog::where('company_id', $user->company_id)
            ->where('status', 'applied')
            ->whereRaw("(parsed_data::json->>'manually_applied')::text = 'true'")
            ->count();

        return view('admin.billing.android-sms-logs', compact('logs', 'stats', 'apiToken', 'apiUrl', 'manualCount'));
    }

    /**
     * PUBLIC API: Android app posts raw SMS here
     * POST /api/sms/receive
     */
    public function receive(Request $request)
    {
        $token = $request->header('X-Api-Token') ?? $request->input('token');
        $deviceId = $request->header('X-Device-Id') ?? $request->input('device_id');

        if (!$token) {
            return response()->json(['success' => false, 'message' => 'API token required'], 401);
        }

        // Cache token → company lookup for 5 minutes to avoid DB hit on every SMS
        $companyId = Cache::remember('sms_token_' . md5($token), 300, function () use ($token) {
            $setting = \App\Models\SystemSetting::withoutGlobalScopes()
                ->where('key', 'android_sms_api_token')
                ->where('value', $token)
                ->first();
            return $setting ? $setting->company_id : null;
        });

        if (!$companyId) {
            Log::warning("Android SMS: Invalid token from IP " . $request->ip());
            return response()->json(['success' => false, 'message' => 'Invalid token'], 401);
        }

        $raw = trim($request->input('sms') ?? $request->input('message') ?? '');
        if (!$raw) {
            return response()->json(['success' => false, 'message' => 'SMS text required'], 422);
        }

        // Parse inline (fast, no DB)
        $parsed = $this->parser()->parse($raw);

        // Log immediately — nothing is ever lost
        // Handle duplicate trx_id gracefully (forwarder retries should not crash)
        try {
            $log = AndroidSmsLog::create([
                'raw_sms'        => $raw,
                'gateway'        => $parsed['gateway'],
                'amount'         => $parsed['amount'],
                'sender_number'  => $parsed['sender'],
                'reference'      => $parsed['reference'],
                'trx_id'         => $parsed['trx_id'],
                'parsed_data'    => $parsed,
                'status'         => 'pending',
                'device_id'      => $deviceId,
                'device_ip'      => $request->ip(),
                'api_token'      => substr($token, 0, 8) . '****',
                'sms_received_at'=> $request->input('received_at') ? \Carbon\Carbon::parse($request->input('received_at')) : now(),
                'company_id'     => $companyId,
            ]);
        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            // Duplicate trx_id — this SMS was already received (forwarder retry)
            $existing = AndroidSmsLog::where('trx_id', $parsed['trx_id'])->first();
            return response()->json([
                'success' => true,
                'message' => 'Duplicate — already received',
                'log_id'  => $existing?->id,
                'status'  => $existing?->status ?? 'unknown',
            ]);
        }

        // Not a payment SMS (no amount extracted) — mark unknown, no need to queue
        if (!$parsed['amount'] || $parsed['amount'] <= 0) {
            $log->update(['status' => 'unknown', 'failure_reason' => 'Could not extract amount — not a recognized payment SMS']);
            return response()->json(['success' => false, 'message' => 'Not a payment SMS', 'log_id' => $log->id]);
        }

        // Dispatch to queue — respond instantly, processing happens in background
        ProcessAndroidSmsJob::dispatch($log->id, $companyId);

        return response()->json([
            'success' => true,
            'message' => 'SMS received and queued for processing',
            'log_id'  => $log->id,
        ]);
    }

    /**
     * Admin: manually reprocess a failed/unknown SMS log
     */
    public function reprocess(Request $request, int $id)
    {
        $log = AndroidSmsLog::findOrFail($id);
        // Check if locked by another admin
        if ($log->locked_by && (int)$log->locked_by !== (int)auth()->id()) {
            return back()->with('error', 'This SMS is locked by another admin and cannot be applied.');
        }

        $user = auth()->user();

        if ($log->status === 'applied') {
            return back()->with('error', 'Already applied — cannot reprocess.');
        }

        // Allow manual reference override
        if ($request->reference) {
            $log->update(['reference' => $request->reference]);
        }

        if ($request->client_id) {
            $client = Client::withoutGlobalScopes()->find($request->client_id);
        } else {
            $client = $this->findClient($log->reference, $log->company_id);
        }

        if (!$client) {
            return back()->with('error', "Client not found for reference: {$log->reference}");
        }

        $parsed = $log->parsed_data ?? [];
        // Use request amount if log amount is null (unknown SMS manually assigned)
        $amount = $log->amount ?? (float)$request->input('amount', 0);
        if (!$amount || $amount <= 0) {
            return back()->with('error', 'Payment amount is required. Please enter the amount manually.');
        }
        $parsed['amount'] = $amount;
        $log->update(['amount' => $amount]);
        $parsed['reference'] = $log->reference;
        $parsed['trx_id'] = $log->trx_id ?? ('MANUAL-' . $log->id);
        $parsed['gateway'] = $log->gateway;

        $log->update(['client_id' => $client->id, 'status' => 'matched']);

        $sendSms = (bool)$request->input('send_sms', 0);

        $lockKey = 'android_sms_client_' . $client->id;
        $lock = Cache::lock($lockKey, 60);
        $lock->block(15);
        try {
            $result = $this->applyPayment($log, $client, $parsed, $sendSms);
        } finally {
            $lock->release();
        }

        if ($result['success']) {
            // Tag log as manually applied for the Manual Applied history box
            $log->refresh();
            $pd = $log->parsed_data ?? [];
            $pd['manually_applied']      = true;
            $pd['manually_applied_by']   = $user->name;
            $pd['manually_applied_at']   = now()->format('Y-m-d H:i:s');
            $log->update(['parsed_data' => $pd]);

            $result['client_name']    = $client->name ?? '';
            $result['amount']         = $amount;
            $result['gateway']        = $log->gateway;
            $result['sender_number']  = $log->sender_number;
            $result['reference']      = $log->reference;
            $result['trx_id']         = $log->trx_id;
            $result['payment_id']     = $log->payment_id;
            $result['applied_by']     = $user->name;
        }
        return response()->json($result);
    }

    /**
     * Admin: generate or regenerate API token for company
     */

    /**
     * Admin: toggle lock on a pending SMS log to prevent others from applying it
     */
    public function toggleLock(Request $request, int $id)
    {
        $log = AndroidSmsLog::where('company_id', auth()->user()->company_id)->findOrFail($id);

        if (!in_array($log->status, ['pending'])) {
            return response()->json(['success' => false, 'message' => 'Only pending SMS can be locked'], 422);
        }

        $userId = auth()->id();

        if ($log->locked_by && (int)$log->locked_by !== (int)$userId) {
            return response()->json(['success' => false, 'message' => 'Already locked by another admin'], 403);
        }

        if ((int)$log->locked_by === (int)$userId) {
            // Unlock
            $log->update(['locked_by' => null, 'locked_at' => null]);
            return response()->json(['success' => true, 'locked' => false, 'message' => 'Unlocked']);
        } else {
            // Lock
            $log->update(['locked_by' => $userId, 'locked_at' => now()]);
            return response()->json(['success' => true, 'locked' => true, 'message' => 'Locked', 'locked_by_name' => auth()->user()->name]);
        }
    }

    public function generateToken(Request $request)
    {
        $user = auth()->user();

        $token = bin2hex(random_bytes(24));
        SystemSetting::updateOrCreate(
            ['key' => 'android_sms_api_token', 'company_id' => $user->company_id],
            ['value' => $token, 'type' => 'text', 'is_public' => false]
        );

        return back()->with('success', "New API Token: {$token}");
    }

    private function findClient(string $reference, int $companyId): ?Client
    {
        $lookups = $this->parser()->parseReference($reference);

        foreach ($lookups as $lookup) {
            $client = Client::withoutGlobalScopes()
                ->where('company_id', $companyId)
                ->where(function ($q) use ($lookup) {
                    $q->where('username', $lookup)
                      ->orWhereRaw('LOWER(username) = ?', [strtolower($lookup)]);
                    if (is_numeric($lookup)) {
                        $q->orWhere('client_id', $lookup);
                    }
                })
                ->where('status', '!=', 'deleted')
                ->first();

            if ($client) return $client;
        }

        return null;
    }

    private function applyPayment(AndroidSmsLog $log, Client $client, array $parsed, bool $sendSms = true): array
    {
        try {
            $gateway = in_array($parsed['gateway'], ['bkash', 'nagad', 'rocket'])
                ? $parsed['gateway'] : 'bkash';

            $paymentMethodId = $this->gatewayService()->getPaymentMethodIdPublic($gateway);

            $trxId = $parsed['trx_id'] ?? ('SMS-' . $log->id . '-' . time());

            $payment = DB::transaction(function () use ($log, $client, $parsed, $gateway, $paymentMethodId, $trxId) {
                $payment = Payment::create([
                    'client_id'         => $client->id,
                    'payment_method_id' => $paymentMethodId,
                    'amount'            => $parsed['amount'],
                    'payment_date'      => now(),
                    'transaction_id'    => $trxId,
                    'status'            => 'approved',
                    'source'            => 'online',
                    'notes'             => 'Auto via Android SMS Forwarder. Sender: ' . ($parsed['sender'] ?? 'N/A') . '. Ref: ' . ($parsed['reference'] ?? 'N/A'),
                    'company_id'        => $client->company_id,
                ]);

                $log->update(['payment_id' => $payment->id, 'trx_id' => $trxId, 'status' => 'applied']);

                return $payment;
            });

            event(new PaymentReceived($payment, 'online', null));

            $this->billingService()->reapplyAllPaymentsToBills($client->fresh());
            $this->billingService()->recalculateClientBalance($client->fresh());

            $client->refresh();

            if ($client->status === 'inactive' && $client->due_amount <= 0) {
                try {
                    $client->update(['status' => 'active', 'last_active_date' => now()]);
                } catch (\Throwable $e) {
                    Log::warning("AndroidSMS: Could not reactivate client: " . $e->getMessage());
                }
            }

            // Re-enable MikroTik if profile is stuck on 'Inactive' after payment
            $client->refresh();
            if (strtolower($client->mikrotik_profile_name ?? '') === 'inactive') {
                try {
                    $restoreProfile = $client->previous_mikrotik_profile
                        ?: ($client->package
                            ? ($client->package->mikrotik_profile ?: $client->package->name)
                            : null);

                    if ($restoreProfile) {
                        $client->update([
                            'mikrotik_profile_name'    => $restoreProfile,
                            'previous_mikrotik_profile' => null,
                        ]);

                        // Push to MikroTik router
                        $deviceId = $client->mikrotik_device_id;
                        $device   = $deviceId
                            ? MikrotikDevice::find($deviceId)
                            : MikrotikDevice::where('is_active', true)->first();

                        if ($device && $client->username) {
                            $mk = MikroTikService::fromDevice($device);
                            if ($mk->connect() !== false) {
                                $mk->changePPPoEProfile($client->username, $restoreProfile);
                                $mk->enablePPPoEUser($client->username);
                                $mk->disconnect();
                                Log::info("AndroidSMS: MikroTik re-enabled", [
                                    'client_id' => $client->client_id,
                                    'profile'   => $restoreProfile,
                                ]);
                            }
                        }
                    }
                } catch (\Throwable $e) {
                    Log::warning("AndroidSMS: MikroTik re-enable failed (non-fatal): " . $e->getMessage());
                }
            }

            if ($sendSms) {
                try {
                    $remaining = max(0, $client->fresh()->due_amount ?? 0);
                    $msg = "Payment Received! Tk " . number_format($parsed['amount'], 2)
                         . " via " . strtoupper($gateway)
                         . ". Remaining Due: Tk " . number_format($remaining, 2)
                         . ". Ref: " . ($parsed['reference'] ?? '') . ". Thank you!";
                    $this->smsService()->sendSms($client->phone, $msg, 'payment_confirmation', $payment->id);
                } catch (\Throwable $e) {
                    Log::warning("AndroidSMS: Confirmation SMS failed: " . $e->getMessage());
                }
            }

            Log::info("AndroidSMS: Payment applied", [
                'log_id'    => $log->id,
                'client_id' => $client->client_id,
                'amount'    => $parsed['amount'],
                'gateway'   => $gateway,
            ]);

            return [
                'success'    => true,
                'message'    => 'Payment applied successfully',
                'client_id'  => $client->client_id,
                'client_name'=> $client->name,
                'amount'     => $parsed['amount'],
                'payment_id' => $payment->id,
                'log_id'     => $log->id,
            ];

        } catch (\Throwable $e) {
            // Update status OUTSIDE any transaction so it always persists
            try {
                $log->update(['status' => 'failed', 'failure_reason' => substr($e->getMessage(), 0, 500)]);
            } catch (\Throwable $ex) {
                Log::error("AndroidSMS: Could not update log status: " . $ex->getMessage());
            }
            Log::error("AndroidSMS: Payment failed: " . $e->getMessage(), ['log_id' => $log->id]);
            return ['success' => false, 'message' => 'Payment processing failed: ' . $e->getMessage()];
        }
    }
}
