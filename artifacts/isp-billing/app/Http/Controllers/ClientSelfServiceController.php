<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Bill;
use App\Models\Payment;
use App\Models\PaymentGatewayTransaction;
use App\Services\BillingCalculatorService;
use App\Services\PaymentGatewayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ClientSelfServiceController extends Controller
{
    protected $maxAttempts = 60;
    protected $decayMinutes = 1;
    
    public function index()
    {
        $siteLogo = \App\Models\SystemSetting::get('site_logo', '/images/logo.png');
        $companyName = \App\Models\SystemSetting::get('company_name', 'Quick Pay');
        return view('self-service.index', compact('siteLogo', 'companyName'));
    }

    public function lookup(Request $request)
    {
        if ($this->isRateLimited($request)) {
            return back()->with('error', 'Too many attempts. Please wait a few minutes and try again.');
        }
        
        $request->validate([
            'identifier' => 'required|string|min:3|max:100',
        ]);

        $identifier = trim($request->identifier);
        
        $this->incrementAttempts($request);

        $client = Client::where('client_id', $identifier)
            ->orWhere('username', $identifier)
            ->first();

        if (!$client) {
            $this->logLookupAttempt($request, $identifier, false);
            return back()->with('error', 'No account found with this ID or username. Please check and try again.');
        }
        
        $this->logLookupAttempt($request, $identifier, true, $client->id);
        
        $token = $this->generateAccessToken($client->client_id, $request->ip());

        return redirect()->route('self-service.status', ['code' => $client->client_id, 'token' => $token]);
    }

    public function status($code, Request $request)
    {
        if ($this->isRateLimited($request)) {
            return redirect()->route('self-service.index')->with('error', 'Too many requests. Please wait and try again.');
        }
        
        $this->incrementAttempts($request);
        
        $token = $request->query('token');
        
        if (!$this->validateAccessToken($code, $token, $request->ip())) {
            $this->logLookupAttempt($request, $code, false, null);
            return redirect()->route('self-service.index')
                ->with('error', 'Invalid or expired access. Please search again using your Client ID.');
        }
        
        $client = Client::where('client_id', $code)
            ->with(['zone', 'area', 'package'])
            ->first();

        if (!$client) {
            return redirect()->route('self-service.index')->with('error', 'Account not found.');
        }
        
        $this->logLookupAttempt($request, $code, true, $client->id);

        $billingData = BillingCalculatorService::calculate($client);

        $recentBills = Bill::where('client_id', $client->id)
            ->orderBy('created_at', 'desc')
            ->take(6)
            ->get();

        $recentPayments = Payment::where('client_id', $client->id)
            ->where('status', 'approved')
            ->orderBy('payment_date', 'desc')
            ->take(6)
            ->get();

        $lastPayment = $recentPayments->first();
        
        $maskedClient = $this->maskClientData($client);

        $gatewayService = app(\App\Services\PaymentGatewayService::class);
        $enabledGateways = [];
        foreach (['bkash','sslcommerz','nagad'] as $gw) {
            if (\App\Models\SystemSetting::get($gw . '_enabled', '0') == '1') {
                $enabledGateways[] = $gw;
            }
        }
        // fallback: if none enabled, allow demo (bkash in demo mode)
        if (empty($enabledGateways) && $gatewayService->isDemoMode()) {
            $enabledGateways[] = 'bkash';
        }
        return view('self-service.status', compact(
            'client', 'maskedClient', 'billingData', 'recentBills', 'recentPayments', 'lastPayment', 'enabledGateways'
        ));
    }
    
    protected function generateAccessToken(string $clientId, string $ip): string
    {
        $expiry = now()->addMinutes(30)->timestamp;
        $data = $clientId . '|' . $ip . '|' . $expiry;
        $signature = hash_hmac('sha256', $data, config('app.key'));
        return base64_encode($expiry . '|' . $signature);
    }
    
    protected function validateAccessToken(string $clientId, ?string $token, string $ip): bool
    {
        if (!$token) {
            return false;
        }
        
        try {
            $decoded = base64_decode($token);
            $parts = explode('|', $decoded);
            if (count($parts) !== 2) {
                return false;
            }
            
            [$expiry, $providedSignature] = $parts;
            
            if ((int) $expiry < now()->timestamp) {
                return false;
            }
            
            $data = $clientId . '|' . $ip . '|' . $expiry;
            $expectedSignature = hash_hmac('sha256', $data, config('app.key'));
            
            return hash_equals($expectedSignature, $providedSignature);
        } catch (\Exception $e) {
            return false;
        }
    }
    
    protected function maskClientData(Client $client): array
    {
        return [
            'name' => $this->maskName($client->name),
            'phone' => $this->maskPhone($client->phone),
            'address' => $this->maskAddress($client->address),
            'client_id' => $client->client_id,
            'status' => $client->status,
            'connection_status' => $client->connection_status ?? 'unknown',
            'package_name' => $client->package->name ?? 'N/A',
            'zone_name' => $client->zone->name ?? 'N/A',
            'monthly_fee' => $client->monthly_fee,
            'connection_type' => ucfirst($client->connection_type ?? 'PPPoE'),
        ];
    }
    
    protected function isRateLimited(Request $request): bool
    {
        $key = 'self_service_lookup:' . $request->ip();
        $attempts = Cache::get($key, 0);
        return $attempts >= $this->maxAttempts;
    }
    
    protected function incrementAttempts(Request $request): void
    {
        $key = 'self_service_lookup:' . $request->ip();
        $attempts = Cache::get($key, 0);
        Cache::put($key, $attempts + 1, now()->addMinutes($this->decayMinutes));
    }
    
    protected function logLookupAttempt(Request $request, string $identifier, bool $success, ?int $clientId = null): void
    {
        try {
            DB::table('self_service_lookup_logs')->insert([
                'ip_address' => $request->ip(),
                'user_agent' => substr($request->userAgent() ?? '', 0, 255),
                'identifier_searched' => substr($identifier, 0, 100),
                'found' => $success,
                'client_id' => $clientId,
                'created_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to log self-service lookup: ' . $e->getMessage());
        }
    }

    public function apiLookup(Request $request)
    {
        if ($this->isRateLimited($request)) {
            return response()->json([
                'success' => false,
                'message' => 'Too many requests. Please wait a few minutes.'
            ], 429);
        }
        
        $request->validate([
            'identifier' => 'required|string|min:3',
            'token' => 'required|string',
        ]);

        $identifier = trim($request->identifier);
        $this->incrementAttempts($request);

        if (!$this->validateAccessToken($identifier, $request->input('token'), $request->ip())) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired access token. Please search again.'
            ], 403);
        }

        $client = Client::where('client_id', $identifier)
            ->with(['zone', 'package'])
            ->first();

        if (!$client) {
            $this->logLookupAttempt($request, $identifier, false);
            return response()->json([
                'success' => false,
                'message' => 'Account not found'
            ], 404);
        }
        
        $this->logLookupAttempt($request, $identifier, true, $client->id);

        $billingData = BillingCalculatorService::calculate($client);

        return response()->json([
            'success' => true,
            'client' => [
                'id' => $client->client_id,
                'name' => $this->maskName($client->name),
                'zone' => $client->zone->name ?? 'N/A',
                'package' => $client->package->name ?? 'N/A',
                'status' => $client->status,
            ],
            'billing' => [
                'due_amount' => $billingData['due'],
                'due_formatted' => '৳' . number_format($billingData['due'], 0),
                'advance' => $billingData['advance'],
                'advance_formatted' => '৳' . number_format($billingData['advance'], 0),
                'has_due' => $billingData['has_due'],
            ],
        ]);
    }

    protected function maskName(string $name): string
    {
        $parts = explode(' ', $name);
        $masked = [];
        foreach ($parts as $part) {
            if (strlen($part) > 2) {
                $masked[] = substr($part, 0, 2) . str_repeat('*', strlen($part) - 2);
            } else {
                $masked[] = $part;
            }
        }
        return implode(' ', $masked);
    }

    protected function maskPhone(?string $phone): string
    {
        if (!$phone) return 'N/A';
        if (strlen($phone) > 6) {
            return substr($phone, 0, 4) . str_repeat('*', strlen($phone) - 6) . substr($phone, -2);
        }
        return $phone;
    }
    
    protected function maskAddress(?string $address): string
    {
        if (!$address) return 'N/A';
        $words = explode(' ', $address);
        if (count($words) <= 2) {
            return $address;
        }
        return $words[0] . ' ' . $words[1] . ' ***';
    }
    
    public function initiatePayment(Request $request)
    {
        $request->validate([
            'client_code' => 'required|string',
            'token' => 'required|string',
            'amount' => 'required|numeric|min:1',
        ]);
        
        $code = $request->client_code;
        $token = $request->token;
        $amount = floatval($request->amount);
        
        if (!$this->validateAccessToken($code, $token, $request->ip())) {
            return redirect()->route('self-service.index')
                ->with('error', 'Invalid or expired session. Please search again.');
        }
        
        $client = Client::where('client_id', $code)->first();
            
        if (!$client) {
            return redirect()->route('self-service.index')
                ->with('error', 'Client not found.');
        }
        
        $billingData = BillingCalculatorService::calculate($client);
        $dueAmount = $billingData['due'];
        
        if ($dueAmount <= 0) {
            return redirect()->route('self-service.status', ['code' => $code, 'token' => $token])
                ->with('error', 'No outstanding due. Payment not required.');
        }
        
        if ($amount > $dueAmount) {
            $amount = $dueAmount;
        }
        if ($amount < 1) {
            return redirect()->route('self-service.status', ['code' => $code, 'token' => $token])
                ->with('error', 'Invalid payment amount.');
        }
        
        $paymentNonce = hash_hmac('sha256', $code . $request->ip() . time(), config('app.key'));
        
        $returnUrl = route('self-service.status', ['code' => $code]) . '?token=' . $token;
        
        $gateway = in_array($request->gateway, ['bkash','sslcommerz','nagad']) ? $request->gateway : 'bkash';
        $gatewayService = app(PaymentGatewayService::class);
        $result = $gatewayService->initiateDirectPayment($client, $amount, $gateway, $returnUrl, $paymentNonce, $request->ip());
        
        if ($result['success']) {
            if (isset($result['demo_mode']) && $result['demo_mode']) {
                return redirect($result['redirect_url']);
            }
            if (isset($result['redirect_url'])) {
                return redirect()->away($result['redirect_url']);
            }
            if (isset($result['bkashURL'])) {
                return redirect()->away($result['bkashURL']);
            }
        }
        
        return redirect()->route('self-service.status', ['code' => $code, 'token' => $token])
            ->with('error', $result['message'] ?? 'Payment initiation failed. Please try again.');
    }
    

    public function sslSuccess(\Illuminate\Http\Request $request)
    {
        return $this->handleSslCallback($request, 'success');
    }
    public function sslFail(\Illuminate\Http\Request $request)
    {
        return redirect()->route('self-service.index')->with('error', 'Payment failed. Please try again.');
    }
    public function sslCancel(\Illuminate\Http\Request $request)
    {
        return redirect()->route('self-service.index')->with('error', 'Payment was cancelled.');
    }
    protected function handleSslCallback(\Illuminate\Http\Request $request, string $type)
    {
        $tranId = $request->tran_id ?? $request->val_id ?? null;
        if (!$tranId) return redirect()->route('self-service.index')->with('error', 'Invalid callback.');
        $transaction = \App\Models\PaymentGatewayTransaction::where('transaction_id', $tranId)->where('status','initiated')->first();
        if (!$transaction) return redirect()->route('self-service.index')->with('error', 'Transaction not found.');
        if ($type !== 'success') {
            $transaction->update(['status'=>'failed']);
            return redirect()->route('self-service.index')->with('error','Payment failed.');
        }
        app(\App\Services\PaymentGatewayService::class)->verifyPayment('sslcommerz', $tranId, $request->all());
        $client = \App\Models\Client::find($transaction->client_id);
        if ($client) {
            $code = $client->client_id;
            $token = $this->generateAccessToken($code, $request->ip());
            return redirect()->route('self-service.status', ['code'=>$code,'token'=>$token])->with('success','Payment successful! Your connection will be activated shortly.');
        }
        return redirect()->route('self-service.index')->with('success','Payment successful!');
    }

    public function paymentCallback(Request $request)
    {
        $paymentId = $request->paymentID;
        $status = $request->status;
        
        return DB::transaction(function () use ($paymentId, $status, $request) {
            $transaction = PaymentGatewayTransaction::where('gateway_response->paymentID', $paymentId)
                ->where('status', 'initiated')
                ->lockForUpdate()
                ->first();
            
            if (!$transaction) {
                Log::warning('Self-service payment callback: Transaction not found or already processed', ['paymentID' => $paymentId]);
                return redirect()->route('self-service.index')
                    ->with('error', 'Transaction not found or already processed.');
            }
            
            $storedIp = $transaction->gateway_response['initiator_ip'] ?? null;
            if ($storedIp && $storedIp !== $request->ip()) {
                Log::error('Self-service payment callback: IP mismatch - rejecting', [
                    'transaction_id' => $transaction->transaction_id,
                    'stored_ip' => $storedIp,
                    'request_ip' => $request->ip()
                ]);
                return redirect()->route('self-service.index')
                    ->with('error', 'Security check failed. Please try again.');
            }
            
            $initiatedAt = $transaction->gateway_response['initiated_at'] ?? null;
            if ($initiatedAt) {
                $initiatedTime = \Carbon\Carbon::parse($initiatedAt);
                if ($initiatedTime->diffInMinutes(now()) > 30) {
                    $transaction->update(['status' => 'expired']);
                    Log::warning('Self-service payment callback: Transaction expired', [
                        'transaction_id' => $transaction->transaction_id,
                        'initiated_at' => $initiatedAt
                    ]);
                    return redirect()->route('self-service.index')
                        ->with('error', 'Payment session expired. Please try again.');
                }
            }
            
            $returnUrl = $this->validateReturnUrl($transaction->gateway_response['return_url'] ?? null);
            
            if ($status !== 'success') {
                $transaction->update(['status' => 'cancelled']);
                return redirect($returnUrl)
                    ->with('error', 'Payment was cancelled.');
            }
            
            $transaction->update(['status' => 'processing']);
            
            $gatewayService = app(PaymentGatewayService::class);
            $result = $gatewayService->executeBkashPayment($paymentId);
            
            if (!$result['success']) {
                $transaction->update(['status' => 'failed']);
                return redirect($returnUrl)
                    ->with('error', 'Payment verification failed.');
            }
            
            $gatewayData = $result['data'] ?? [];
            
            $gatewayAmount = floatval($gatewayData['amount'] ?? 0);
            if ($gatewayAmount > 0 && abs($gatewayAmount - $transaction->amount) > 0.01) {
                Log::error('Self-service payment callback: Amount mismatch', [
                    'transaction_id' => $transaction->transaction_id,
                    'stored_amount' => $transaction->amount,
                    'gateway_amount' => $gatewayAmount
                ]);
                $transaction->update(['status' => 'failed', 'gateway_response' => array_merge($transaction->gateway_response ?? [], $gatewayData, ['error' => 'amount_mismatch'])]);
                return redirect($returnUrl)
                    ->with('error', 'Payment verification failed. Amount mismatch.');
            }
            
            $gatewayPayerRef = $gatewayData['payerReference'] ?? null;
            $client = Client::find($transaction->client_id);
            if ($gatewayPayerRef && $client && $gatewayPayerRef !== $client->client_id) {
                Log::error('Self-service payment callback: Client mismatch', [
                    'transaction_id' => $transaction->transaction_id,
                    'stored_client_id' => $client->client_id,
                    'gateway_payer_ref' => $gatewayPayerRef
                ]);
                $transaction->update(['status' => 'failed', 'gateway_response' => array_merge($transaction->gateway_response ?? [], $gatewayData, ['error' => 'client_mismatch'])]);
                return redirect($returnUrl)
                    ->with('error', 'Payment verification failed. Client mismatch.');
            }
            
            $gatewayService->verifyPayment('bkash', $transaction->transaction_id, $gatewayData);
            
            return redirect($returnUrl)
                ->with('success', 'Payment successful! Your connection will be activated shortly. TrxID: ' . ($gatewayData['trxID'] ?? $transaction->transaction_id));
        });
    }
    
    protected function validateReturnUrl(?string $url): string
    {
        $defaultUrl = route('self-service.index');
        
        if (!$url) {
            return $defaultUrl;
        }
        
        $parsed = parse_url($url);
        $appHost = parse_url(config('app.url'), PHP_URL_HOST);
        
        if (!isset($parsed['host']) || $parsed['host'] !== $appHost) {
            if (strpos($url, '/check') === 0 || strpos($url, route('self-service.index')) === 0) {
                return url($url);
            }
            return $defaultUrl;
        }
        
        return $url;
    }
    
    public function demoPayment(Request $request, string $transaction_id)
    {
        $transaction = PaymentGatewayTransaction::where('transaction_id', $transaction_id)
            ->where('status', 'initiated')
            ->first();
        
        if (!$transaction) {
            return redirect()->route('self-service.index')
                ->with('error', 'Transaction not found or already processed.');
        }
        
        $storedIp = $transaction->gateway_response['initiator_ip'] ?? null;
        if ($storedIp && $storedIp !== $request->ip()) {
            return redirect()->route('self-service.index')
                ->with('error', 'Security check failed. Please try again.');
        }
        
        $client = Client::find($transaction->client_id);
        
        return view('self-service.demo-payment', [
            'transaction' => $transaction,
            'client' => $client,
        ]);
    }
    
    public function confirmDemoPayment(Request $request, string $transaction_id)
    {
        $transaction = PaymentGatewayTransaction::where('transaction_id', $transaction_id)
            ->where('status', 'initiated')
            ->first();
        
        if (!$transaction) {
            return redirect()->route('self-service.index')
                ->with('error', 'Transaction not found or already processed.');
        }
        
        $storedIp = $transaction->gateway_response['initiator_ip'] ?? null;
        if ($storedIp && $storedIp !== $request->ip()) {
            return redirect()->route('self-service.index')
                ->with('error', 'Security check failed. Please try again.');
        }
        
        $gatewayService = app(PaymentGatewayService::class);
        $gatewayService->verifyPayment('bkash', $transaction->transaction_id, ['demo' => true, 'is_demo' => true, 'trxID' => 'DEMO-' . time()]);
        
        $returnUrl = $this->validateReturnUrl($transaction->gateway_response['return_url'] ?? null);
        
        return redirect($returnUrl)
            ->with('success', 'Demo payment successful! Your connection has been activated. TrxID: DEMO-' . time());
    }
}
