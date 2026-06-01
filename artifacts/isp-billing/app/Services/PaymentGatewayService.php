<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Client;
use App\Models\Bill;
use App\Models\PaymentGatewayTransaction;
use App\Events\PaymentReceived;
use App\Events\ClientStatusChanged;
use App\Events\ClientBalanceChanged;
use App\Services\BillingService;
use App\Services\MikroTikService;
use App\Services\SyncService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaymentGatewayService
{
    protected array $config = [];
    protected bool $demoMode = false;

    public function __construct()
    {
        $bkashAppKey = \App\Models\SystemSetting::get('bkash_app_key') ?: config('services.bkash.app_key');
        $bkashAppSecret = \App\Models\SystemSetting::get('bkash_app_secret') ?: config('services.bkash.app_secret');
        $bkashUsername = \App\Models\SystemSetting::get('bkash_username') ?: config('services.bkash.username');
        $bkashPassword = \App\Models\SystemSetting::get('bkash_password') ?: config('services.bkash.password');
        $bkashSandbox = \App\Models\SystemSetting::get('bkash_sandbox', '1') == '1';
        
        $nagadSandbox = \App\Models\SystemSetting::get('nagad_sandbox', '1') == '1';

        $this->config = [
            'bkash' => [
                'app_key' => $bkashAppKey,
                'app_secret' => $bkashAppSecret,
                'username' => $bkashUsername,
                'password' => $bkashPassword,
                'base_url' => $bkashSandbox 
                    ? 'https://tokenized.sandbox.bka.sh/v1.2.0-beta'
                    : 'https://tokenized.pay.bka.sh/v1.2.0-beta',
            ],
            'nagad' => [
                'merchant_id' => \App\Models\SystemSetting::get('nagad_merchant_id', ''),
                'merchant_phone' => \App\Models\SystemSetting::get('nagad_merchant_phone', ''),
                'pg_public_key' => \App\Models\SystemSetting::get('nagad_pg_public_key', ''),
                'merchant_private_key' => \App\Models\SystemSetting::get('nagad_merchant_private_key', ''),
                'sandbox' => $nagadSandbox,
                'base_url' => $nagadSandbox
                    ? 'https://sandbox.mynagad.com:10061'
                    : 'https://api.mynagad.com',
            ],
            'rocket' => [
                'merchant_id' => \App\Models\SystemSetting::get('rocket_merchant_number', '') ?: config('services.rocket.merchant_id'),
                'api_key' => config('services.rocket.api_key'),
            ],
            'ipay' => [
                'merchant_id' => config('services.ipay.merchant_id'),
                'api_key' => config('services.ipay.api_key'),
            ],
            'ssl' => [
                'store_id' => \App\Models\SystemSetting::get('sslcommerz_store_id') ?: config('services.sslcommerz.store_id'),
                'store_password' => \App\Models\SystemSetting::get('sslcommerz_store_password') ?: config('services.sslcommerz.store_password'),
                'sandbox' => \App\Models\SystemSetting::get('sslcommerz_sandbox', '1') == '1',
            ],
        ];

        $this->demoMode = config('services.payment.demo_mode', false);
    }

    protected function isDemoMode(): bool
    {
        return $this->demoMode || empty($this->config['bkash']['app_key']);
    }

    public function initiateBkashPayment(Bill $bill, Client $client): array
    {
        return $this->initiateBkashPaymentWithAmount($client, $bill->total_amount, $bill->id);
    }

    public function isGatewayEnabled(string $gateway): bool
    {
        $enabled = \App\Models\SystemSetting::get($gateway . '_enabled', '0') == '1';
        if (!$enabled) return false;

        $activationDate = \App\Models\SystemSetting::get($gateway . '_activation_date', '');
        $expiryDate = \App\Models\SystemSetting::get($gateway . '_expiry_date', '');

        if ($activationDate && now()->lt($activationDate)) return false;
        if ($expiryDate && now()->gt($expiryDate . ' 23:59:59')) return false;

        return true;
    }

    public function getEnabledGateways(): array
    {
        $gateways = [];
        if ($this->isGatewayEnabled('sslcommerz')) $gateways[] = 'sslcommerz';
        if ($this->isGatewayEnabled('bkash')) $gateways[] = 'bkash';
        if ($this->isGatewayEnabled('nagad')) $gateways[] = 'nagad';
        return $gateways;
    }

    public function initiateBkashPaymentWithAmount(Client $client, float $amount, ?int $billId = null): array
    {
        if (!$this->isGatewayEnabled('bkash')) {
            return ['success' => false, 'message' => 'bKash payment gateway is currently disabled.'];
        }

        $transactionId = 'BKASH-' . Str::upper(Str::random(10));

        if ($this->isDemoMode()) {
            $demoPaymentId = 'DEMO-' . Str::upper(Str::random(8));
            
            PaymentGatewayTransaction::create([
                'transaction_id' => $transactionId,
                'gateway' => 'bkash',
                'client_id' => $client->id,
                'bill_id' => $billId,
                'amount' => $amount,
                'status' => 'initiated',
                'gateway_response' => ['paymentID' => $demoPaymentId, 'demo' => true],
            ]);

            return [
                'success' => true,
                'demo_mode' => true,
                'redirect_url' => route('payment.demo.confirm', ['transaction_id' => $transactionId]),
                'transaction_id' => $transactionId,
            ];
        }
        
        try {
            $token = $this->getBkashToken();
            
            $response = Http::withHeaders([
                'Authorization' => $token,
                'X-APP-Key' => $this->config['bkash']['app_key'],
            ])->post($this->config['bkash']['base_url'] . '/tokenized/checkout/create', [
                'mode' => '0011',
                'payerReference' => $client->client_id,
                'callbackURL' => route('payment.bkash.callback'),
                'amount' => $amount,
                'currency' => 'BDT',
                'intent' => 'sale',
                'merchantInvoiceNumber' => 'PAY-' . time(),
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                PaymentGatewayTransaction::create([
                    'transaction_id' => $transactionId,
                    'gateway' => 'bkash',
                    'client_id' => $client->id,
                    'bill_id' => $billId,
                    'amount' => $amount,
                    'status' => 'initiated',
                    'gateway_response' => $data,
                ]);

                return [
                    'success' => true,
                    'bkashURL' => $data['bkashURL'] ?? null,
                    'paymentID' => $data['paymentID'] ?? null,
                    'transaction_id' => $transactionId,
                ];
            }
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
            Log::error('bKash Payment Error: ' . $e->getMessage());
        }

        return ['success' => false, 'message' => 'Failed to initiate bKash payment'];
    }

    protected function getBkashToken(): string
    {
        $response = Http::withHeaders([
            'username' => $this->config['bkash']['username'],
            'password' => $this->config['bkash']['password'],
        ])->post($this->config['bkash']['base_url'] . '/tokenized/checkout/token/grant', [
            'app_key' => $this->config['bkash']['app_key'],
            'app_secret' => $this->config['bkash']['app_secret'],
        ]);

        $data  = $response->json();
        $token = $data['id_token'] ?? '';
        if (empty($token)) {
            $code = $data['statusCode'] ?? $response->status();
            $msg  = $data['statusMessage'] ?? 'Token grant failed';
            Log::error('bKash token grant failed', ['code' => $code, 'msg' => $msg]);
            throw new \RuntimeException('bKash API: ' . $msg . ' (Code: ' . $code . ')');
        }
        return $token;
    }

    public function executeBkashPayment(string $paymentId): array
    {
        try {
            $token = $this->getBkashToken();
            
            $response = Http::withHeaders([
                'Authorization' => $token,
                'X-APP-Key' => $this->config['bkash']['app_key'],
            ])->post($this->config['bkash']['base_url'] . '/tokenized/checkout/execute', [
                'paymentID' => $paymentId,
            ]);

            if ($response->successful()) {
                return ['success' => true, 'data' => $response->json()];
            }
        } catch (\Exception $e) {
            Log::error('bKash Execute Error: ' . $e->getMessage());
        }

        return ['success' => false];
    }

    public function initiateRocketPayment(Bill $bill, Client $client): array
    {
        $transactionId = 'ROCKET-' . Str::upper(Str::random(10));
        
        PaymentGatewayTransaction::create([
            'transaction_id' => $transactionId,
            'gateway' => 'rocket',
            'client_id' => $client->id,
            'bill_id' => $bill->id,
            'amount' => $bill->total_amount,
            'status' => 'initiated',
        ]);

        return [
            'success' => true,
            'transaction_id' => $transactionId,
            'message' => 'Please dial *322# and send payment to merchant: ' . $this->config['rocket']['merchant_id'],
            'amount' => $bill->total_amount,
        ];
    }

    public function initiateNagadPayment(Client $client, float $amount, ?int $billId = null): array
    {
        if (!$this->isGatewayEnabled('nagad')) {
            return ['success' => false, 'message' => 'Nagad payment gateway is currently disabled.'];
        }

        $merchantId = $this->config['nagad']['merchant_id'];
        $merchantPhone = $this->config['nagad']['merchant_phone'];
        $baseUrl = $this->config['nagad']['base_url'];
        $orderId = 'NAGAD-' . Str::upper(Str::random(10));
        $dateTime = now()->format('YmdHis');

        if (empty($merchantId) || empty($this->config['nagad']['pg_public_key']) || empty($this->config['nagad']['merchant_private_key'])) {
            return ['success' => false, 'message' => 'Nagad gateway credentials are not configured.'];
        }

        $serverIp = $this->getNagadServerIp();

        try {
            $sensitiveData = json_encode([
                'merchantId' => $merchantId,
                'datetime' => $dateTime,
                'orderId' => $orderId,
                'challenge' => Str::random(40),
            ]);

            $encryptedSensitiveData = $this->nagadEncrypt($sensitiveData);
            $signature = $this->nagadSign($sensitiveData);

            if (!$encryptedSensitiveData || !$signature) {
                return ['success' => false, 'message' => 'Failed to process Nagad encryption. Check your keys.'];
            }

            $initResponse = Http::withHeaders([
                'Content-Type' => 'application/json',
                'X-KM-Api-Version' => 'v-0.2.0',
                'X-KM-IP-V4' => $serverIp,
                'X-KM-Client-Type' => 'PC_WEB',
            ])->post($baseUrl . '/remote-payment-gateway-1.0/api/dfs/check-out/initialize/' . $merchantId . '/' . $orderId, [
                'accountNumber' => $merchantPhone,
                'dateTime' => $dateTime,
                'sensitiveData' => $encryptedSensitiveData,
                'signature' => $signature,
            ]);

            if (!$initResponse->successful()) {
                Log::error('Nagad Init HTTP Error', ['status' => $initResponse->status(), 'body' => $initResponse->body()]);
                return ['success' => false, 'message' => 'Nagad initialization failed (HTTP ' . $initResponse->status() . ')'];
            }

            $initData = $initResponse->json();

            if (!isset($initData['sensitiveData']) || !isset($initData['signature'])) {
                Log::error('Nagad Init: Missing sensitiveData/signature', ['response' => $initData]);
                return ['success' => false, 'message' => $initData['message'] ?? 'Nagad initialization failed.'];
            }

            $decryptedInitData = $this->nagadDecrypt($initData['sensitiveData']);
            if (!$decryptedInitData) {
                return ['success' => false, 'message' => 'Failed to decrypt Nagad response.'];
            }

            $initResult = json_decode($decryptedInitData, true);
            $paymentReferenceId = $initResult['paymentReferenceId'] ?? null;
            $challenge = $initResult['challenge'] ?? null;

            if (!$paymentReferenceId || !$challenge) {
                Log::error('Nagad Init: Missing paymentReferenceId/challenge', ['decrypted' => $initResult]);
                return ['success' => false, 'message' => 'Invalid Nagad initialization response.'];
            }

            $orderData = json_encode([
                'merchantId' => $merchantId,
                'orderId' => $orderId,
                'currencyCode' => '050',
                'amount' => number_format($amount, 2, '.', ''),
                'challenge' => $challenge,
            ]);

            $encryptedOrderData = $this->nagadEncrypt($orderData);
            $orderSignature = $this->nagadSign($orderData);

            $completeResponse = Http::withHeaders([
                'Content-Type' => 'application/json',
                'X-KM-Api-Version' => 'v-0.2.0',
                'X-KM-IP-V4' => $serverIp,
                'X-KM-Client-Type' => 'PC_WEB',
            ])->post($baseUrl . '/remote-payment-gateway-1.0/api/dfs/check-out/complete/' . $paymentReferenceId, [
                'sensitiveData' => $encryptedOrderData,
                'signature' => $orderSignature,
                'merchantCallbackURL' => route('payment.nagad.callback'),
                'additionalMerchantInfo' => json_encode([
                    'client_id' => $client->client_id,
                    'bill_id' => $billId,
                ]),
            ]);

            if (!$completeResponse->successful()) {
                Log::error('Nagad Complete HTTP Error', ['status' => $completeResponse->status(), 'body' => $completeResponse->body()]);
                return ['success' => false, 'message' => 'Nagad checkout completion failed.'];
            }

            $completeData = $completeResponse->json();

            if (($completeData['status'] ?? '') !== 'Success' || empty($completeData['callBackUrl'])) {
                Log::error('Nagad Complete: Failed', ['response' => $completeData]);
                return ['success' => false, 'message' => $completeData['message'] ?? 'Nagad checkout failed.'];
            }

            PaymentGatewayTransaction::create([
                'transaction_id' => $orderId,
                'gateway' => 'nagad',
                'client_id' => $client->id,
                'bill_id' => $billId,
                'amount' => $amount,
                'status' => 'initiated',
                'gateway_response' => [
                    'paymentReferenceId' => $paymentReferenceId,
                    'callBackUrl' => $completeData['callBackUrl'],
                    'initiated_at' => now()->toIso8601String(),
                ],
            ]);

            return [
                'success' => true,
                'redirect_url' => $completeData['callBackUrl'],
                'transaction_id' => $orderId,
                'paymentReferenceId' => $paymentReferenceId,
            ];

        } catch (\Exception $e) {
            Log::error('Nagad Payment Error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return ['success' => false, 'message' => 'Nagad payment initiation failed: ' . $e->getMessage()];
        }
    }

    public function verifyNagadPayment(string $paymentRefId): array
    {
        $baseUrl = $this->config['nagad']['base_url'];
        $merchantId = $this->config['nagad']['merchant_id'];
        $serverIp = $this->getNagadServerIp();

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'X-KM-Api-Version' => 'v-0.2.0',
                'X-KM-IP-V4' => $serverIp,
                'X-KM-Client-Type' => 'PC_WEB',
            ])->get($baseUrl . '/remote-payment-gateway-1.0/api/dfs/verify/payment/' . $paymentRefId);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => ($data['status'] ?? '') === 'Success',
                    'data' => $data,
                    'status' => $data['status'] ?? 'Unknown',
                    'statusCode' => $data['statusCode'] ?? null,
                    'orderId' => $data['orderId'] ?? null,
                    'amount' => $data['amount'] ?? null,
                ];
            }

            return ['success' => false, 'message' => 'Nagad verify API returned HTTP ' . $response->status()];
        } catch (\Exception $e) {
            Log::error('Nagad Verify Error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Nagad verification failed: ' . $e->getMessage()];
        }
    }



    public function testBkashConnection(): array
    {
        $appKey   = $this->config['bkash']['app_key'];
        $username = $this->config['bkash']['username'];
        $password = $this->config['bkash']['password'];
        $appSecret = $this->config['bkash']['app_secret'];
        $baseUrl  = $this->config['bkash']['base_url'];
        $mode = str_contains($baseUrl, 'sandbox') ? 'Sandbox' : 'Live';

        if (empty($appKey) || empty($username) || empty($password) || empty($appSecret)) {
            return ['success' => false, 'message' => 'bKash credentials incomplete.', 'mode' => $mode];
        }

        try {
            $response = Http::timeout(15)
                ->withHeaders(['username' => $username, 'password' => $password])
                ->post($baseUrl . '/tokenized/checkout/token/grant', [
                    'app_key'    => $appKey,
                    'app_secret' => $appSecret,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                if (!empty($data['id_token'])) {
                    return ['success' => true, 'message' => 'bKash connection successful! Token granted — credentials valid.', 'mode' => $mode, 'merchant' => $username];
                }
                $errMsg = $data['errorCode'] ?? ($data['message'] ?? json_encode($data));
                return ['success' => false, 'message' => 'Token grant failed: ' . $errMsg, 'mode' => $mode];
            }

            return ['success' => false, 'message' => 'bKash API HTTP ' . $response->status(), 'mode' => $mode];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Connection failed: ' . $e->getMessage(), 'mode' => $mode];
        }
    }


    public function testNagadConnection(): array


    {
        $merchantId = $this->config['nagad']['merchant_id'];
        $merchantPhone = $this->config['nagad']['merchant_phone'];
        $baseUrl = $this->config['nagad']['base_url'];

        if (empty($merchantId) || empty($this->config['nagad']['pg_public_key']) || empty($this->config['nagad']['merchant_private_key'])) {
            return [
                'success' => false,
                'message' => 'Nagad credentials are incomplete. Please fill in Merchant ID, PG Public Key, and Merchant Private Key.',
                'mode' => $this->config['nagad']['sandbox'] ? 'Sandbox' : 'Live',
            ];
        }

        $testOrderId = 'TEST-' . Str::upper(Str::random(10));
        $dateTime = now()->format('YmdHis');

        try {
            $sensitiveData = json_encode([
                'merchantId' => $merchantId,
                'datetime' => $dateTime,
                'orderId' => $testOrderId,
                'challenge' => Str::random(40),
            ]);

            $encrypted = $this->nagadEncrypt($sensitiveData);
            $signed = $this->nagadSign($sensitiveData);

            if (!$encrypted || !$signed) {
                return [
                    'success' => false,
                    'message' => 'RSA encryption/signing failed. Check your PG Public Key and Merchant Private Key format.',
                    'mode' => $this->config['nagad']['sandbox'] ? 'Sandbox' : 'Live',
                ];
            }

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'X-KM-Api-Version' => 'v-0.2.0',
                'X-KM-IP-V4' => $this->getNagadServerIp(),
                'X-KM-Client-Type' => 'PC_WEB',
            ])->timeout(15)->post($baseUrl . '/remote-payment-gateway-1.0/api/dfs/check-out/initialize/' . $merchantId . '/' . $testOrderId, [
                'accountNumber' => $merchantPhone,
                'dateTime' => $dateTime,
                'sensitiveData' => $encrypted,
                'signature' => $signed,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['sensitiveData'])) {
                    return [
                        'success' => true,
                        'message' => 'Nagad connection successful! Credentials are valid.',
                        'mode' => $this->config['nagad']['sandbox'] ? 'Sandbox' : 'Live',
                        'merchant_id' => $merchantId,
                    ];
                }
                return [
                    'success' => false,
                    'message' => 'Nagad API responded but initialization failed: ' . ($data['message'] ?? json_encode($data)),
                    'mode' => $this->config['nagad']['sandbox'] ? 'Sandbox' : 'Live',
                ];
            }

            return [
                'success' => false,
                'message' => 'Nagad API returned HTTP ' . $response->status(),
                'mode' => $this->config['nagad']['sandbox'] ? 'Sandbox' : 'Live',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Connection failed: ' . $e->getMessage(),
                'mode' => $this->config['nagad']['sandbox'] ? 'Sandbox' : 'Live',
            ];
        }
    }

    protected function getNagadServerIp(): string
    {
        $configuredIp = \App\Models\SystemSetting::get('nagad_server_ip', '');
        if (!empty($configuredIp)) {
            return $configuredIp;
        }

        try {
            $response = Http::timeout(5)->get('https://api.ipify.org?format=json');
            if ($response->successful()) {
                return $response->json('ip', '127.0.0.1');
            }
        } catch (\Exception $e) {
        }

        return request()->server('SERVER_ADDR', '127.0.0.1');
    }

    protected function nagadEncrypt(string $data): ?string
    {
        $pgPublicKey = $this->config['nagad']['pg_public_key'];
        $pgPublicKey = trim($pgPublicKey);

        if (strpos($pgPublicKey, '-----BEGIN PUBLIC KEY-----') === false) {
            $pgPublicKey = "-----BEGIN PUBLIC KEY-----\n" . wordwrap($pgPublicKey, 64, "\n", true) . "\n-----END PUBLIC KEY-----";
        }

        $publicKeyResource = openssl_pkey_get_public($pgPublicKey);
        if (!$publicKeyResource) {
            Log::error('Nagad: Invalid PG Public Key');
            return null;
        }

        $encrypted = '';
        if (openssl_public_encrypt($data, $encrypted, $publicKeyResource, OPENSSL_PKCS1_PADDING)) {
            return base64_encode($encrypted);
        }

        Log::error('Nagad: Public key encryption failed');
        return null;
    }

    protected function nagadDecrypt(string $encryptedData): ?string
    {
        $merchantPrivateKey = $this->config['nagad']['merchant_private_key'];
        $merchantPrivateKey = trim($merchantPrivateKey);

        if (strpos($merchantPrivateKey, '-----BEGIN') === false) {
            $merchantPrivateKey = "-----BEGIN RSA PRIVATE KEY-----\n" . wordwrap($merchantPrivateKey, 64, "\n", true) . "\n-----END RSA PRIVATE KEY-----";
        }

        $privateKeyResource = openssl_pkey_get_private($merchantPrivateKey);
        if (!$privateKeyResource) {
            Log::error('Nagad: Invalid Merchant Private Key');
            return null;
        }

        $decryptedData = '';
        $encData = base64_decode($encryptedData);
        if (openssl_private_decrypt($encData, $decryptedData, $privateKeyResource, OPENSSL_PKCS1_PADDING)) {
            return $decryptedData;
        }

        Log::error('Nagad: Private key decryption failed');
        return null;
    }

    protected function nagadSign(string $data): ?string
    {
        $merchantPrivateKey = $this->config['nagad']['merchant_private_key'];
        $merchantPrivateKey = trim($merchantPrivateKey);

        if (strpos($merchantPrivateKey, '-----BEGIN') === false) {
            $merchantPrivateKey = "-----BEGIN RSA PRIVATE KEY-----\n" . wordwrap($merchantPrivateKey, 64, "\n", true) . "\n-----END RSA PRIVATE KEY-----";
        }

        $privateKeyResource = openssl_pkey_get_private($merchantPrivateKey);
        if (!$privateKeyResource) {
            Log::error('Nagad: Invalid Merchant Private Key for signing');
            return null;
        }

        $signature = '';
        if (openssl_sign($data, $signature, $privateKeyResource, OPENSSL_ALGO_SHA256)) {
            return base64_encode($signature);
        }

        Log::error('Nagad: Signing failed');
        return null;
    }

    public function initiateIpayPayment(Bill $bill, Client $client): array
    {
        $transactionId = 'IPAY-' . Str::upper(Str::random(10));
        
        $params = [
            'ipay_store_id' => $this->config['ipay']['merchant_id'],
            'amount' => $bill->total_amount,
            'order_id' => $bill->bill_number,
            'email' => $client->email ?? 'customer@isp.com',
            'name' => $client->name,
            'phone' => $client->phone,
            'desc' => 'Bill Payment - ' . $bill->bill_number,
            'success_url' => route('payment.ipay.success'),
            'cancel_url' => route('payment.ipay.cancel'),
        ];

        PaymentGatewayTransaction::create([
            'transaction_id' => $transactionId,
            'gateway' => 'ipay',
            'client_id' => $client->id,
            'bill_id' => $bill->id,
            'amount' => $bill->total_amount,
            'status' => 'initiated',
            'gateway_response' => $params,
        ]);

        return [
            'success' => true,
            'transaction_id' => $transactionId,
            'redirect_url' => 'https://www.ipaybd.com/checkout?' . http_build_query($params),
        ];
    }

    public function initiateSslPayment(Bill $bill, Client $client): array
    {
        if (!$this->isGatewayEnabled('sslcommerz')) {
            return ['success' => false, 'message' => 'SSLCommerz payment gateway is currently disabled.'];
        }

        $transactionId = 'SSL-' . Str::upper(Str::random(10));
        
        $baseUrl = $this->config['ssl']['sandbox'] 
            ? 'https://sandbox.sslcommerz.com' 
            : 'https://securepay.sslcommerz.com';

        $postData = [
            'store_id' => $this->config['ssl']['store_id'],
            'store_passwd' => $this->config['ssl']['store_password'],
            'total_amount' => $bill->total_amount,
            'currency' => 'BDT',
            'tran_id' => $transactionId,
            'success_url' => route('payment.ssl.success'),
            'fail_url' => route('payment.ssl.fail'),
            'cancel_url' => route('payment.ssl.cancel'),
            'cus_name' => $client->name,
            'cus_email' => $client->email ?? 'customer@isp.com',
            'cus_phone' => $client->phone,
            'cus_add1' => $client->address ?? 'Bangladesh',
            'cus_city' => 'Dhaka',
            'cus_country' => 'Bangladesh',
            'shipping_method' => 'NO',
            'product_name' => 'Internet Bill',
            'product_category' => 'Service',
            'product_profile' => 'general',
        ];

        try {
            $response = Http::asForm()->post($baseUrl . '/gwprocess/v4/api.php', $postData);

            if ($response->successful()) {
                $data = $response->json();
                
                PaymentGatewayTransaction::create([
                    'transaction_id' => $transactionId,
                    'gateway' => 'sslcommerz',
                    'client_id' => $client->id,
                    'bill_id' => $bill->id,
                    'amount' => $bill->total_amount,
                    'status' => 'initiated',
                    'gateway_response' => $data,
                ]);

                if ($data['status'] === 'SUCCESS') {
                    return [
                        'success' => true,
                        'redirect_url' => $data['GatewayPageURL'],
                        'transaction_id' => $transactionId,
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::error('SSL Payment Error: ' . $e->getMessage());
        }

        return ['success' => false, 'message' => 'Failed to initiate SSL payment'];
    }

    public function testSslConnection(): array
    {
        $baseUrl = $this->config['ssl']['sandbox']
            ? 'https://sandbox.sslcommerz.com'
            : 'https://securepay.sslcommerz.com';

        $testTranId = 'TEST-' . Str::upper(Str::random(10));

        $postData = [
            'store_id' => $this->config['ssl']['store_id'],
            'store_passwd' => $this->config['ssl']['store_password'],
            'total_amount' => 10,
            'currency' => 'BDT',
            'tran_id' => $testTranId,
            'success_url' => route('payment.ssl.success'),
            'fail_url' => route('payment.ssl.fail'),
            'cancel_url' => route('payment.ssl.cancel'),
            'cus_name' => 'Test Customer',
            'cus_email' => 'test@example.com',
            'cus_phone' => '01700000000',
            'cus_add1' => 'Dhaka, Bangladesh',
            'cus_city' => 'Dhaka',
            'cus_country' => 'Bangladesh',
            'shipping_method' => 'NO',
            'product_name' => 'Connection Test',
            'product_category' => 'Service',
            'product_profile' => 'general',
        ];

        try {
            $response = Http::asForm()->timeout(15)->post($baseUrl . '/gwprocess/v4/api.php', $postData);

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['status']) && $data['status'] === 'SUCCESS') {
                    return [
                        'success' => true,
                        'message' => 'SSLCommerz connection successful! Sandbox credentials are valid.',
                        'gateway_url' => $data['GatewayPageURL'] ?? null,
                        'session_key' => $data['sessionkey'] ?? null,
                        'store_id' => $this->config['ssl']['store_id'],
                        'mode' => $this->config['ssl']['sandbox'] ? 'Sandbox' : 'Live',
                        'test_tran_id' => $testTranId,
                    ];
                }

                return [
                    'success' => false,
                    'message' => 'SSLCommerz API responded but returned error: ' . ($data['failedreason'] ?? json_encode($data)),
                    'store_id' => $this->config['ssl']['store_id'],
                    'mode' => $this->config['ssl']['sandbox'] ? 'Sandbox' : 'Live',
                    'api_response' => $data,
                ];
            }

            return [
                'success' => false,
                'message' => 'SSLCommerz API returned HTTP ' . $response->status(),
                'mode' => $this->config['ssl']['sandbox'] ? 'Sandbox' : 'Live',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Connection failed: ' . $e->getMessage(),
                'mode' => $this->config['ssl']['sandbox'] ? 'Sandbox' : 'Live',
            ];
        }
    }

    public function verifyPayment(string $gateway, string $transactionId, array $data = []): bool
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($gateway, $transactionId, $data) {
            $transaction = PaymentGatewayTransaction::where('transaction_id', $transactionId)
                ->lockForUpdate()
                ->first();
            
            if (!$transaction) {
                Log::warning("verifyPayment: Transaction not found", ['transaction_id' => $transactionId]);
                return false;
            }
            
            if ($transaction->status === 'completed') {
                Log::info("verifyPayment: Transaction already completed (idempotent check)", ['transaction_id' => $transactionId]);
                return true;
            }
            
            if (!in_array($transaction->status, ['initiated', 'processing'])) {
                Log::warning("verifyPayment: Invalid transaction status", [
                    'transaction_id' => $transactionId,
                    'status' => $transaction->status
                ]);
                return false;
            }
            
            // Extract real gateway TrxID from callback data
            $realTrxId = match($gateway) {
                'bkash'                  => $data['trxID'] ?? null,
                'sslcommerz', 'ssl'      => $data['bank_tran_id'] ?? $data['val_id'] ?? null,
                'nagad'                  => $data['nagad_trx_id'] ?? $data['issuerPaymentRefNo'] ?? $data['payment_ref_id'] ?? null,
                default                  => null,
            };
            $paymentTrxId = $realTrxId ?? $transactionId;
            $senderMsisdn = match($gateway) {
                'bkash' => $data['customerMsisdn'] ?? $data['payerAccount'] ?? null,
                'nagad' => $data['customerMobileNo'] ?? null,
                default => null,
            };

            $existingPayment = Payment::where('transaction_id', $paymentTrxId)
                ->orWhere('transaction_id', $transactionId)
                ->first();
            if ($existingPayment) {
                Log::info("verifyPayment: Payment already recorded (idempotent check)", ['transaction_id' => $paymentTrxId]);
                $transaction->update(['status' => 'completed']);
                return true;
            }
            
            $transaction->update([
                'status' => 'completed',
                'gateway_response' => array_merge($transaction->gateway_response ?? [], $data),
            ]);

            // Skip real payment record creation for demo/test payments
            if (!empty($data['is_demo']) || (!empty($data['demo_mode']) && str_starts_with($paymentTrxId, 'DEMO'))) {
                Log::info("verifyPayment: Demo payment skipped from payment history", ['transaction_id' => $transactionId]);
                return true;
            }

            $client = Client::find($transaction->client_id);
            
            $paymentAmount = $transaction->amount;
            if ($client && !$transaction->bill_id) {
                $currentDue = \App\Services\BillingCalculatorService::calculate($client)['due'];
                if ($paymentAmount > $currentDue && $currentDue > 0) {
                    $paymentAmount = $currentDue;
                    Log::info("verifyPayment: Capped payment to current due", [
                        'transaction_id' => $transactionId,
                        'original_amount' => $transaction->amount,
                        'capped_amount' => $paymentAmount
                    ]);
                }
            }

            $payment = null;

            if ($transaction->bill_id) {
                $bill = Bill::lockForUpdate()->find($transaction->bill_id);
                if ($bill) {
                    $payment = Payment::create([
                        'client_id' => $transaction->client_id,
                        'bill_id' => $transaction->bill_id,
                        'payment_method_id' => $this->getPaymentMethodId($gateway),
                        'amount' => $paymentAmount,
                        'payment_date' => now(),
                        'transaction_id' => $paymentTrxId,
                        'status' => 'approved',
                        'notes' => 'Online payment via ' . strtoupper($gateway) . ($realTrxId ? ' | Gateway TrxID: ' . $realTrxId : ''),
                        'remarks' => $senderMsisdn ? 'Sender: ' . $senderMsisdn : null,
                    ]);

                    $newPaid = $bill->paid_amount + $paymentAmount;
                    $newDue = max(0, $bill->total_amount - $newPaid);
                    $bill->update([
                        'status' => $newDue <= 0 ? 'paid' : ($newPaid > 0 ? 'partial' : 'unpaid'),
                        'paid_amount' => $newPaid,
                        'due_amount' => $newDue,
                    ]);
                }
            } elseif ($client) {
                $payment = Payment::create([
                    'client_id' => $transaction->client_id,
                    'payment_method_id' => $this->getPaymentMethodId($gateway),
                    'amount' => $paymentAmount,
                    'payment_date' => now(),
                    'transaction_id' => $paymentTrxId,
                    'status' => 'approved',
                    'notes' => 'Self-service online payment via ' . strtoupper($gateway) . ($realTrxId ? ' | Gateway TrxID: ' . $realTrxId : ''),
                    'remarks' => $senderMsisdn ? 'Sender: ' . $senderMsisdn : null,
                ]);
            }

            if ($payment) {
                event(new PaymentReceived($payment, 'online', null));
            }

            if ($client) {
                $billingService = app(BillingService::class);
                $billingService->reapplyAllPaymentsToBills($client->fresh());
                $billingService->recalculateClientBalance($client->fresh());
                
                $client->refresh();
                
                if ($client->status === 'inactive' && $client->due_amount <= 0) {
                    $this->autoReconnectClient($client, $gateway, $transactionId);
                }
            }

            return true;
        });
    }
    
    protected function autoReconnectClient(Client $client, string $gateway, string $transactionId): void
    {
        $oldStatus = $client->status;
        try {
            $mikrotikService = app(MikroTikService::class);
            $result = $mikrotikService->restoreClient($client, 'online_payment', null, true);
            
            $originalProfile = $client->package ? ($client->package->mikrotik_profile ?: $client->package->name) : null;
            $restoreProfile = $client->previous_mikrotik_profile ?: $originalProfile;

            if ($result['success']) {
                Log::info("Auto-reconnected client {$client->client_id} after {$gateway} payment {$transactionId}");
            } else {
                $updateData = [
                    'status' => 'active',
                    'last_active_date' => now(),
                ];
                if ($restoreProfile) {
                    $updateData['mikrotik_profile_name'] = $restoreProfile;
                    $updateData['previous_mikrotik_profile'] = null;
                }
                $client->update($updateData);
                Log::warning("MikroTik reconnect failed for {$client->client_id}, database updated only");
            }
        } catch (\Exception $e) {
            $originalProfile = $client->package ? ($client->package->mikrotik_profile ?: $client->package->name) : null;
            $restoreProfile = $client->previous_mikrotik_profile ?: $originalProfile;
            $updateData = [
                'status' => 'active',
                'last_active_date' => now(),
            ];
            if ($restoreProfile) {
                $updateData['mikrotik_profile_name'] = $restoreProfile;
                $updateData['previous_mikrotik_profile'] = null;
            }
            $client->update($updateData);
            Log::error("Auto-reconnect error for {$client->client_id}: " . $e->getMessage());
        }

        $client->refresh();
        if ($client->status !== $oldStatus) {
            event(new ClientStatusChanged($client, $oldStatus, $client->status, 'online_payment'));
        }
    }
    
    public function initiateDirectPayment(Client $client, float $amount, string $gateway = 'bkash', ?string $returnUrl = null, ?string $nonce = null, ?string $initiatorIp = null): array
    {
        $transactionId = strtoupper($gateway) . '-' . Str::upper(Str::random(10));
        
        $securityData = [
            'return_url' => $returnUrl,
            'nonce' => $nonce,
            'initiator_ip' => $initiatorIp,
            'initiated_at' => now()->toIso8601String(),
        ];
        
        if ($this->isDemoMode()) {
            $demoPaymentId = 'DEMO-' . Str::upper(Str::random(8));
            
            PaymentGatewayTransaction::create([
                'transaction_id' => $transactionId,
                'gateway' => $gateway,
                'client_id' => $client->id,
                'bill_id' => null,
                'amount' => $amount,
                'status' => 'initiated',
                'gateway_response' => array_merge(['paymentID' => $demoPaymentId, 'demo' => true], $securityData),
            ]);

            return [
                'success' => true,
                'demo_mode' => true,
                'redirect_url' => route('self-service.payment.demo', ['transaction_id' => $transactionId]),
                'transaction_id' => $transactionId,
            ];
        }

        // SSL Commerz direct payment
        if ($gateway === 'sslcommerz') {
            if (!$this->isGatewayEnabled('sslcommerz')) {
                return ['success' => false, 'message' => 'SSLCommerz is not enabled.'];
            }
            $baseUrl = $this->config['ssl']['sandbox']
                ? 'https://sandbox.sslcommerz.com'
                : 'https://securepay.sslcommerz.com';
            $postData = [
                'store_id'        => $this->config['ssl']['store_id'],
                'store_passwd'    => $this->config['ssl']['store_password'],
                'total_amount'    => $amount,
                'currency'        => 'BDT',
                'tran_id'         => $transactionId,
                'success_url'     => route('self-service.ssl.success'),
                'fail_url'        => route('self-service.ssl.fail'),
                'cancel_url'      => route('self-service.ssl.cancel'),
                'cus_name'        => $client->name,
                'cus_email'       => $client->email ?? 'customer@isp.com',
                'cus_phone'       => $client->phone,
                'cus_add1'        => $client->address ?? 'Bangladesh',
                'cus_city'        => 'Dhaka',
                'cus_country'     => 'Bangladesh',
                'shipping_method' => 'NO',
                'product_name'    => 'Internet Bill',
                'product_category'=> 'Service',
                'product_profile' => 'general',
            ];
            try {
                $response = \Illuminate\Support\Facades\Http::asForm()->post($baseUrl . '/gwprocess/v4/api.php', $postData);
                if ($response->successful()) {
                    $data = $response->json();
                    \App\Models\PaymentGatewayTransaction::create([
                        'transaction_id'   => $transactionId,
                        'gateway'          => 'sslcommerz',
                        'client_id'        => $client->id,
                        'bill_id'          => null,
                        'amount'           => $amount,
                        'status'           => 'initiated',
                        'gateway_response' => array_merge($data, $securityData),
                    ]);
                    if (isset($data['status']) && $data['status'] === 'SUCCESS') {
                        return ['success' => true, 'redirect_url' => $data['GatewayPageURL'], 'transaction_id' => $transactionId];
                    }
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Self-service SSL Error: ' . $e->getMessage());
            }
            return ['success' => false, 'message' => 'Failed to initiate SSLCommerz payment'];
        }
        
        try {
            $token = $this->getBkashToken();
            
            $response = Http::withHeaders([
                'Authorization' => $token,
                'X-APP-Key' => $this->config['bkash']['app_key'],
            ])->post($this->config['bkash']['base_url'] . '/tokenized/checkout/create', [
                'mode' => '0011',
                'payerReference' => $client->client_id,
                'callbackURL' => route('self-service.payment.callback'),
                'amount' => $amount,
                'currency' => 'BDT',
                'intent' => 'sale',
                'merchantInvoiceNumber' => 'PAY-' . $client->client_id . '-' . time(),
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                PaymentGatewayTransaction::create([
                    'transaction_id' => $transactionId,
                    'gateway' => $gateway,
                    'client_id' => $client->id,
                    'bill_id' => null,
                    'amount' => $amount,
                    'status' => 'initiated',
                    'gateway_response' => array_merge($data, $securityData),
                ]);

                return [
                    'success' => true,
                    'bkashURL' => $data['bkashURL'] ?? null,
                    'paymentID' => $data['paymentID'] ?? null,
                    'transaction_id' => $transactionId,
                ];
            }
        } catch (\Exception $e) {
            Log::error('Direct bKash Payment Error: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }

        return ['success' => false, 'message' => 'Failed to initiate payment'];
    }

    public function getPaymentMethodIdPublic(string $gateway): ?int
    {
        return $this->getPaymentMethodId($gateway);
    }

    protected function getPaymentMethodId(string $gateway): ?int
    {
        $methods = [
            'bkash' => 'BKASH',
            'rocket' => 'ROCKET',
            'nagad' => 'NAGAD',
            'ipay' => 'IPAY',
            'sslcommerz' => 'SSL',
            'cash' => 'CASH',
            'bank' => 'BANK',
        ];

        $code = $methods[strtolower($gateway)] ?? strtoupper($gateway);
        $method = \App\Models\PaymentMethod::whereRaw('UPPER(code) = ?', [strtoupper($code)])->first();
        return $method?->id;
    }
}
