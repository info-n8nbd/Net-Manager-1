<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Reseller;
use App\Models\ResellerSmsSetting;
use App\Models\ResellerSmsLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ResellerSmsService
{
    protected ResellerSmsSetting $setting;
    protected Reseller $reseller;

    const EVENT_LINE_ACTIVATED   = 'line_activated';
    const EVENT_BILL_GENERATED   = 'bill_generated';
    const EVENT_PAYMENT_RECEIVED = 'payment_received';
    const EVENT_DUE_REMINDER     = 'due_reminder';
    const EVENT_LINE_DEACTIVATED = 'line_deactivated';

    const EVENT_LABELS = [
        self::EVENT_LINE_ACTIVATED   => 'Line Activated',
        self::EVENT_BILL_GENERATED   => 'Bill Generated',
        self::EVENT_PAYMENT_RECEIVED => 'Payment Received',
        self::EVENT_DUE_REMINDER     => 'Due Reminder',
        self::EVENT_LINE_DEACTIVATED => 'Line Deactivated',
    ];

    public function __construct(Reseller $reseller)
    {
        $this->reseller = $reseller;
        $this->setting  = ResellerSmsSetting::forReseller($reseller->id);
    }

    public function isEnabled(): bool
    {
        return $this->setting->is_enabled
            && !empty($this->setting->api_key);
    }

    public function isConfigured(): bool
    {
        if ($this->setting->provider === 'custom') {
            $cfg = $this->setting->getCustomApiConfig();
            return !empty($cfg['url']) && !empty($this->setting->api_key);
        }
        return !empty($this->setting->api_key) && !empty($this->setting->sender_id);
    }

    public function getSetting(): ResellerSmsSetting
    {
        return $this->setting;
    }

    public function sendToClient(Client $client, string $eventType, array $extraVars = []): ?ResellerSmsLog
    {
        if (!$this->isEnabled()) {
            return $this->log($client, $eventType, '', 'skipped', 'SMS disabled or not configured');
        }

        if (!$this->setting->isEventEnabled($eventType)) {
            return $this->log($client, $eventType, '', 'skipped', "Event {$eventType} disabled");
        }

        $phone = $this->normalizePhone($client->phone ?? '');
        if (empty($phone)) {
            return $this->log($client, $eventType, '', 'skipped', 'No phone number');
        }

        $message = $this->buildMessage($eventType, $client, $extraVars);
        if (empty($message)) {
            return $this->log($client, $eventType, $phone, 'skipped', 'No template configured');
        }

        return $this->sendRaw($client, $phone, $message, $eventType);
    }

    public function sendRaw(Client $client, string $phone, string $message, string $eventType): ResellerSmsLog
    {
        $phone = $this->normalizePhone($phone);
        try {
            $response = $this->callApi($phone, $message);
            $success  = $response !== null && $this->isSuccess($response);

            return $this->log($client, $eventType, $phone, $success ? 'sent' : 'failed', $response, $message);
        } catch (\Exception $e) {
            Log::warning("ResellerSmsService send failed for reseller#{$this->reseller->id}: " . $e->getMessage());
            return $this->log($client, $eventType, $phone, 'failed', $e->getMessage(), $message);
        }
    }

    public function sendBulk(array $clientIds, string $message, string $eventType = 'bulk'): array
    {
        $results = [];
        $clients = Client::whereIn('id', $clientIds)->where('reseller_id', $this->reseller->id)->get();

        foreach ($clients as $client) {
            $phone = $this->normalizePhone($client->phone ?? '');
            if (empty($phone)) {
                $results[] = ['client_id' => $client->id, 'status' => 'skipped', 'reason' => 'No phone'];
                continue;
            }
            $log = $this->sendRaw($client, $phone, $message, $eventType);
            $results[] = ['client_id' => $client->id, 'status' => $log->status, 'phone' => $phone];
        }
        return $results;
    }

    public function testSend(string $phone, string $message): array
    {
        $phone = $this->normalizePhone($phone);
        if (empty($phone)) {
            return ['success' => false, 'message' => 'Invalid phone number'];
        }
        try {
            $response = $this->callApi($phone, $message);
            $success  = $response !== null && $this->isSuccess($response);
            return ['success' => $success, 'response' => $response];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    protected function callApi(string $phone, string $message): ?string
    {
        $provider = $this->setting->provider;
        $apiKey   = $this->setting->api_key;
        $senderId = $this->setting->sender_id ?? '';
        $cfg      = $this->setting->getCustomApiConfig();

        return match($provider) {
            'bulksms_dhaka' => $this->callBulkSmsDhaka($phone, $message, $apiKey, $senderId),
            'bulksms_bd'    => $this->callBulkSmsBd($phone, $message, $apiKey, $senderId),
            'ssl_sms'       => $this->callSslSms($phone, $message, $apiKey, $senderId),
            'greenweb'      => $this->callGreenweb($phone, $message, $apiKey),
            'alpha_sms'     => $this->callAlphaSms($phone, $message, $apiKey),
            'elitbuzz'      => $this->callElitbuzz($phone, $message, $apiKey, $senderId),
            'muthofun'      => $this->callMuthofun($phone, $message, $apiKey, $senderId),
            'onnorokom'     => $this->callOnnorokom($phone, $message, $apiKey, $senderId),
            'smsnet24'      => $this->callSmsnet24($phone, $message, $apiKey, $senderId),
            'mimsms'        => $this->callMimSms($phone, $message, $apiKey, $senderId),
            'adnsms'        => $this->callAdnSms($phone, $message, $apiKey, $senderId),
            'bulksmspro'    => $this->callBulkSmsPro($phone, $message, $apiKey, $senderId),
            'custom'        => $this->callCustomApi($phone, $message, $apiKey, $senderId, $cfg),
            default         => null,
        };
    }

    protected function callBulkSmsDhaka(string $phone, string $message, string $apiKey, string $senderId): string
    {
        $response = Http::timeout(15)->get('https://bulksmsdhaka.com/api/sendtext', [
            'apikey'    => $apiKey,
            'callerID'  => $senderId,
            'number'    => $phone,
            'message'   => $message,
        ]);
        return $response->body();
    }

    protected function callBulkSmsBd(string $phone, string $message, string $apiKey, string $senderId): string
    {
        $response = Http::timeout(15)->asForm()->post('https://bulksmsbd.net/api/smsapi', [
            'api_key'  => $apiKey,
            'senderid' => $senderId,
            'number'   => $phone,
            'message'  => $message,
        ]);
        return $response->body();
    }

    protected function callSslSms(string $phone, string $message, string $apiKey, string $senderId): string
    {
        $response = Http::timeout(15)->post('https://smsplus.sslwireless.com/api/v3/send-sms', [
            'api_token' => $apiKey,
            'sid'       => $senderId,
            'msisdn'    => $phone,
            'sms'       => $message,
            'csms_id'   => uniqid('r_'),
        ]);
        return $response->body();
    }

    protected function callGreenweb(string $phone, string $message, string $apiKey): string
    {
        $response = Http::timeout(15)->get('http://api.greenweb.com.bd/api.php', [
            'token'   => $apiKey,
            'to'      => $phone,
            'message' => $message,
        ]);
        return $response->body();
    }

    protected function callAlphaSms(string $phone, string $message, string $apiKey): string
    {
        $response = Http::timeout(15)->post('https://api.sms.net.bd/sendsms', [
            'api_key' => $apiKey,
            'msg'     => $message,
            'to'      => $phone,
        ]);
        return $response->body();
    }

    protected function callElitbuzz(string $phone, string $message, string $apiKey, string $senderId): string
    {
        $response = Http::timeout(15)->get('https://msg.elitbuzz-bd.com/smsapi', [
            'api_key'  => $apiKey,
            'type'     => 'text',
            'contacts' => $phone,
            'senderid' => $senderId,
            'msg'      => $message,
        ]);
        return $response->body();
    }

    protected function callMuthofun(string $phone, string $message, string $apiKey, string $senderId): string
    {
        // Official Muthofun/Muthobarta API: https://clients.muthobarta.com
        // Auth: Authorization: Token {api_key}
        $response = Http::timeout(15)
            ->withHeaders(['Authorization' => 'Token ' . $apiKey])
            ->post('https://clients.muthobarta.com/send-sms', [
                'receiver'         => $phone,
                'message'          => $message,
                'remove_duplicate' => true,
            ]);
        return $response->body();
    }

    protected function callOnnorokom(string $phone, string $message, string $apiKey, string $senderId): string
    {
        $response = Http::timeout(15)->get('https://api2.onnorokomsms.com/HttpSendSms.ashx', [
            'op'       => 'OneToOne',
            'apiKey'   => $apiKey,
            'type'     => 'TEXT',
            'mobile'   => $phone,
            'smsText'  => $message,
            'maskName' => $senderId,
        ]);
        return $response->body();
    }

    protected function callSmsnet24(string $phone, string $message, string $apiKey, string $senderId): string
    {
        $response = Http::timeout(15)->post('https://smsnet24.com/api/sms/send', [
            'api_key'   => $apiKey,
            'sender_id' => $senderId,
            'phone'     => $phone,
            'message'   => $message,
        ]);
        return $response->body();
    }

    protected function callMimSms(string $phone, string $message, string $apiKey, string $senderId): string
    {
        // Official MimSMS API: https://api.mimsms.com/api/SmsSending/SMS
        // UserName = account email (stored as api_key), Apikey = api key (stored as sender_id for MimSMS)
        $response = Http::timeout(15)
            ->withHeaders(['Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->post('https://api.mimsms.com/api/SmsSending/SMS', [
                'UserName'        => $apiKey,
                'Apikey'          => $senderId,
                'MobileNumber'    => $phone,
                'SenderName'      => $senderId,
                'TransactionType' => 'T',
                'Message'         => $message,
                'CampaignId'      => 'null',
            ]);
        return $response->body();
    }

    protected function callAdnSms(string $phone, string $message, string $apiKey, string $senderId): string
    {
        // Official ADN SMS API: https://portal.adnsms.com/api/v1/secure/send-sms
        // Auth: api_key + api_secret as POST params (NO Bearer header)
        // api_secret stored in api_url field for reseller settings
        $cfg = $this->setting->getCustomApiConfig();
        $apiSecret = $this->setting->api_url ?? $cfg['api_secret'] ?? '';

        $response = Http::timeout(15)
            ->withHeaders(['Accept' => 'application/json'])
            ->post('https://portal.adnsms.com/api/v1/secure/send-sms', [
                'api_key'      => $apiKey,
                'api_secret'   => $apiSecret,
                'request_type' => 'SINGLE_SMS',
                'message_type' => 'TEXT',
                'mobile'       => $phone,
                'message_body' => $message,
            ]);
        return $response->body();
    }

    protected function callBulkSmsPro(string $phone, string $message, string $apiKey, string $senderId): string
    {
        $response = Http::withOptions(['verify' => false])->timeout(15)->get('https://bulksmspro.net/api/sms-send', [
            'api_key'      => $apiKey,
            'sender_id'    => $senderId,
            'phone_numbers' => $phone,
            'message'      => $message,
            'type'         => 'text',
        ]);
        return $response->body();
    }

    protected function callCustomApi(string $phone, string $message, string $apiKey, ?string $senderId, array $cfg): ?string
    {
        $url = $cfg['url'] ?? '';
        if (empty($url)) return null;

        $phoneParam  = $cfg['phone_param']    ?? 'number';
        $msgParam    = $cfg['message_param']  ?? 'message';
        $keyParam    = $cfg['api_key_param']  ?? 'apikey';
        $senderParam = $cfg['sender_id_param'] ?? 'senderid';
        $method      = strtoupper($cfg['method'] ?? 'GET');

        $params = [
            $phoneParam => $phone,
            $msgParam   => $message,
            $keyParam   => $apiKey,
        ];
        if ($senderId) $params[$senderParam] = $senderId;

        if (!empty($cfg['extra_params'])) {
            parse_str($cfg['extra_params'], $extra);
            $params = array_merge($params, $extra);
        }

        $response = $method === 'POST'
            ? Http::timeout(15)->post($url, $params)
            : Http::timeout(15)->get($url, $params);

        return $response->body();
    }

    protected function isSuccess(?string $response): bool
    {
        if ($response === null) return false;

        $cfg = $this->setting->getCustomApiConfig();
        if ($this->setting->provider === 'custom' && !empty($cfg['success_response'])) {
            return stripos($response, $cfg['success_response']) !== false;
        }

        // BulkSmsPro: response_code "0000" = SUCCESS (JSON response)
        if ($this->setting->provider === 'bulksmspro') {
            $data = json_decode($response, true);
            if (is_array($data) && isset($data['response_code'])) {
                return (string) $data['response_code'] === '0000';
            }
        }

        $lower = strtolower($response);
        foreach (['error', 'fail', 'invalid', 'reject'] as $bad) {
            if (str_contains($lower, $bad)) return false;
        }
        return true;
    }

    protected function buildMessage(string $eventType, Client $client, array $extraVars = []): string
    {
        $template = $this->setting->getTemplate($eventType);
        if (empty($template)) return '';

        $companyName = $this->reseller->name ?? 'ISP';
        $package     = $client->package->name ?? '-';

        $vars = array_merge([
            '{name}'     => $client->name ?? 'Customer',
            '{phone}'    => $client->phone ?? '',
            '{package}'  => $package,
            '{company}'  => $companyName,
            '{month}'    => now()->translatedFormat('F Y'),
            '{amount}'   => number_format($extraVars['amount'] ?? 0, 2),
            '{due}'      => number_format($extraVars['due'] ?? 0, 2),
            '{due_date}' => $extraVars['due_date'] ?? '',
            '{bill_no}'  => $extraVars['bill_no'] ?? '',
        ], $extraVars);

        return str_replace(array_keys($vars), array_values($vars), $template);
    }

    protected function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/\D/', '', $phone);
        if (strlen($phone) === 11 && str_starts_with($phone, '0')) {
            $phone = '88' . $phone;
        }
        if (strlen($phone) === 10) {
            $phone = '880' . $phone;
        }
        return $phone;
    }

    protected function log(Client $client, string $eventType, string $phone, string $status, ?string $response = null, ?string $message = null): ResellerSmsLog
    {
        return ResellerSmsLog::create([
            'reseller_id' => $this->reseller->id,
            'client_id'   => $client->id ?? null,
            'phone'       => $phone,
            'message'     => $message ?? '',
            'event_type'  => $eventType,
            'status'      => $status,
            'response'    => $response,
            'skip_reason' => in_array($status, ['skipped']) ? $response : null,
        ]);
    }

    public function getApiStatus(): array
    {
        $configured = $this->isConfigured();
        return [
            'configured'    => $configured,
            'enabled'       => $this->setting->is_enabled,
            'provider'      => $this->setting->provider,
            'provider_name' => SmsService::getProviders()[$this->setting->provider] ?? $this->setting->provider,
            'sender_id'     => $this->setting->sender_id ?? '',
            'has_api_key'   => !empty($this->setting->api_key),
        ];
    }

    public function getBalance(): ?array
    {
        if (!$this->isConfigured()) {
            return ['balance' => null, 'currency' => 'BDT', 'error' => 'SMS API is not configured', 'updated_at' => now()->toDateTimeString()];
        }

        $provider = $this->setting->provider;
        $apiKey   = $this->setting->api_key;

        try {
            return match($provider) {
                'bulksms_dhaka' => $this->getBalanceBulkSmsDhaka($apiKey),
                'bulksms_bd'    => $this->getBalanceBulkSmsBd($apiKey),
                'ssl_sms'       => $this->getBalanceSslSms($apiKey),
                'bulksmspro'    => $this->getBalanceBulkSmsPro($apiKey),
                'greenweb'      => $this->getBalanceGreenweb($apiKey),
                'elitbuzz'      => $this->getBalanceElitbuzz($apiKey),
                'mimsms'        => $this->getBalanceMimSms($apiKey),
                'alpha_sms'     => $this->getBalanceAlphaSms($apiKey),
                'adnsms'        => $this->getBalanceAdnSms($apiKey),
                'muthofun'      => $this->getBalanceMuthofun($apiKey),
                default         => ['balance' => null, 'currency' => 'BDT', 'error' => 'Balance check is not supported for this provider', 'updated_at' => now()->toDateTimeString()],
            };
        } catch (\Exception $e) {
            return ['balance' => null, 'currency' => 'BDT', 'error' => 'Balance check failed: ' . $e->getMessage(), 'updated_at' => now()->toDateTimeString()];
        }
    }

    protected function getBalanceBulkSmsDhaka(string $apiKey): array
    {
        $response = Http::timeout(10)->get('https://bulksmsdhaka.com/api/getBalance', ['apikey' => $apiKey]);
        $data = $response->json();
        return ['balance' => $data['Balance'] ?? $data['balance'] ?? null, 'currency' => 'BDT', 'unit' => 'SMS', 'updated_at' => now()->toDateTimeString()];
    }

    protected function getBalanceBulkSmsBd(string $apiKey): array
    {
        $response = Http::timeout(10)->asForm()->post('https://bulksmsbd.net/api/getBalanceApi', ['api_key' => $apiKey]);
        $data = $response->json();
        return ['balance' => $data['balance'] ?? null, 'currency' => 'BDT', 'unit' => 'SMS', 'updated_at' => now()->toDateTimeString()];
    }

    protected function getBalanceSslSms(string $apiKey): array
    {
        $response = Http::timeout(10)->get('https://smsplus.sslwireless.com/api/v3/balance', ['api_token' => $apiKey]);
        $data = $response->json();
        return ['balance' => $data['balance'] ?? null, 'currency' => 'BDT', 'unit' => 'SMS', 'updated_at' => now()->toDateTimeString()];
    }

    protected function getBalanceBulkSmsPro(string $apiKey): array
    {
        $url = "https://bulksmspro.net/api/{$apiKey}/getBalance";
        $response = Http::withOptions(['verify' => false])->timeout(10)->get($url);
        $body = $response->body();
        if (is_numeric(trim($body))) {
            return ['balance' => floatval(trim($body)), 'currency' => 'BDT', 'unit' => 'SMS', 'updated_at' => now()->toDateTimeString()];
        }
        $data = $response->json();
        // BulkSmsPro returns: {"response_code":"0000","data":{"current_balance":1061.7,...}}
        $bal = $data['data']['current_balance'] ?? $data['balance'] ?? $data['credit'] ?? null;
        if ($bal !== null && is_numeric($bal)) {
            return ['balance' => floatval($bal), 'currency' => 'BDT', 'unit' => 'BDT', 'updated_at' => now()->toDateTimeString()];
        }
        return ['balance' => null, 'currency' => 'BDT', 'portal_url' => 'https://bulksmspro.net', 'portal_name' => 'BulkSMSPro', 'error' => 'Could not retrieve balance from API. Please check the portal.', 'updated_at' => now()->toDateTimeString()];
    }

    protected function getBalanceGreenweb(string $apiKey): array
    {
        $response = Http::timeout(10)->get('http://api.greenweb.com.bd/api.php', ['token' => $apiKey, 'type' => 'balance']);
        return ['balance' => trim($response->body()), 'currency' => 'BDT', 'unit' => 'SMS', 'updated_at' => now()->toDateTimeString()];
    }

    protected function getBalanceElitbuzz(string $apiKey): array
    {
        $response = Http::timeout(10)->get('https://msg.elitbuzz-bd.com/smsapi/balancecheck', ['api_key' => $apiKey]);
        return ['balance' => trim($response->body()), 'currency' => 'BDT', 'unit' => 'SMS', 'updated_at' => now()->toDateTimeString()];
    }

    protected function getBalanceMimSms(string $apiKey): array
    {
        return ['balance' => null, 'currency' => 'BDT', 'portal_url' => 'https://api.mimsms.com', 'portal_name' => 'MimSMS', 'error' => 'MimSMS does not support API balance check. Please check via the portal.', 'updated_at' => now()->toDateTimeString()];
    }

    protected function getBalanceAlphaSms(string $apiKey): array
    {
        $response = Http::timeout(10)->withToken($apiKey)->get('https://api.sms.net.bd/user/balance');
        $data = $response->json();
        return ['balance' => $data['data']['balance'] ?? null, 'currency' => 'BDT', 'unit' => 'SMS', 'updated_at' => now()->toDateTimeString()];
    }

    protected function getBalanceAdnSms(string $apiKey): array
    {
        // ADN SMS balance: POST https://portal.adnsms.com/api/v1/secure/check-balance
        $cfg = $this->setting->getCustomApiConfig();
        $apiSecret = $this->setting->api_url ?? $cfg['api_secret'] ?? '';
        $response = Http::timeout(10)
            ->withHeaders(['Accept' => 'application/json'])
            ->post('https://portal.adnsms.com/api/v1/secure/check-balance', [
                'api_key'    => $apiKey,
                'api_secret' => $apiSecret,
            ]);
        $data = $response->json();
        $balance = $data['balance']['sms'] ?? $data['balance'] ?? null;
        return ['balance' => $balance, 'currency' => 'SMS', 'unit' => 'SMS', 'updated_at' => now()->toDateTimeString()];
    }

    protected function getBalanceMuthofun(string $apiKey): array
    {
        // Muthofun/Muthobarta balance: GET https://clients.muthobarta.com/get-balance
        $response = Http::timeout(10)
            ->withHeaders(['Authorization' => 'Token ' . $apiKey])
            ->get('https://clients.muthobarta.com/get-balance');
        $data = $response->json();
        return ['balance' => $data['balance'] ?? null, 'currency' => 'BDT', 'unit' => 'SMS', 'updated_at' => now()->toDateTimeString()];
    }

    public static function forReseller(Reseller $reseller): self
    {
        return new self($reseller);
    }
}
