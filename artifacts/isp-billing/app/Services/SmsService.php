<?php

namespace App\Services;

use App\Models\Bill;
use App\Models\Client;
use App\Models\SmsLog;
use App\Models\SmsTemplate;
use App\Models\SmsSchedule;
use App\Models\DueReminder;
use App\Models\SystemSetting;
use App\Events\SMSDispatched;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class SmsService
{
    protected ?string $apiKey = null;
    protected ?string $senderId = null;
    protected ?string $apiUrl = null;
    protected ?string $provider = null;
    protected array $smsSettings = [];
    protected array $customApiConfig = [];

    const PROVIDER_CUSTOM = 'custom';
    const PROVIDER_BULKSMS_DHAKA = 'bulksms_dhaka';
    const PROVIDER_BULKSMS_BD = 'bulksms_bd';
    const PROVIDER_SSL_SMS = 'ssl_sms';
    const PROVIDER_TWILIO = 'twilio';
    const PROVIDER_REVE_SMS = 'reve_sms';
    const PROVIDER_MUTHOFUN = 'muthofun';
    const PROVIDER_GREENWEB = 'greenweb';
    const PROVIDER_ALPHA_SMS = 'alpha_sms';
    const PROVIDER_ELITBUZZ = 'elitbuzz';
    const PROVIDER_ONNOROKOM = 'onnorokom';
    const PROVIDER_SMSNET24 = 'smsnet24';
    const PROVIDER_AJURATECH = 'ajuratech';
    const PROVIDER_MIMSMS = 'mimsms';
    const PROVIDER_ADNSMS = 'adnsms';
    const PROVIDER_BULKSMSPRO = 'bulksmspro';

    const BULK_THRESHOLD = 1;

    const EVENT_MONTHLY_BILL = 'monthly_bill';
    const EVENT_BILL_CONFIRMATION = 'bill_confirmation';
    const EVENT_PAYMENT_CONFIRMATION = 'payment_confirmation';
    const EVENT_SERVICE_SUSPENSION = 'service_suspension';
    const EVENT_SERVICE_RESTORATION = 'service_restoration';
    const EVENT_ADVANCE_PAYMENT = 'advance_payment';
    const EVENT_CUSTOM = 'custom';
    const EVENT_DUE_REMINDER_3DAY = 'due_reminder_3day';
    const EVENT_DUE_REMINDER_1DAY = 'due_reminder_1day';
    const EVENT_AUTO_INACTIVE     = 'auto_inactive';

    const RECIPIENT_CLIENT = 'client';
    const RECIPIENT_TECHNICIAN = 'technician';
    const RECIPIENT_ADMIN = 'admin';

    public static function getProviders(): array
    {
        return [
            self::PROVIDER_CUSTOM => 'Custom API (Any Provider)',
            self::PROVIDER_REVE_SMS => 'Reve SMS (ReveSystems)',
            self::PROVIDER_BULKSMS_DHAKA => 'BulkSMS Dhaka',
            self::PROVIDER_BULKSMS_BD => 'BulkSMS BD',
            self::PROVIDER_SSL_SMS => 'SSL Wireless',
            self::PROVIDER_GREENWEB => 'Greenweb BD',
            self::PROVIDER_ALPHA_SMS => 'Alpha SMS',
            self::PROVIDER_ELITBUZZ => 'Elitbuzz SMS',
            self::PROVIDER_MUTHOFUN => 'Muthofun SMS',
            self::PROVIDER_ONNOROKOM => 'Onnorokom SMS',
            self::PROVIDER_SMSNET24 => 'SMSNet24',
            self::PROVIDER_AJURATECH => 'Ajura Tech SMS',
            self::PROVIDER_MIMSMS => 'MIM SMS',
            self::PROVIDER_ADNSMS => 'ADN SMS',
            self::PROVIDER_BULKSMSPRO => 'BulkSMS Pro (bulksmspro.net)',
            self::PROVIDER_TWILIO => 'Twilio (International)',
        ];
    }

    public function __construct()
    {
        $this->provider = SystemSetting::get('sms_provider', self::PROVIDER_BULKSMS_DHAKA);
        $this->apiKey = SystemSetting::get('sms_api_key', env('BULKSMS_API_KEY'));
        $this->senderId = SystemSetting::get('sms_sender_id', env('BULKSMS_SENDER_ID', '1234'));
        $this->apiUrl = SystemSetting::get('sms_api_url', '');
        
        $this->customApiConfig = [
            'url' => SystemSetting::get('sms_custom_api_url', ''),
            'method' => SystemSetting::get('sms_custom_api_method', 'GET'),
            'phone_param' => SystemSetting::get('sms_custom_phone_param', 'number'),
            'message_param' => SystemSetting::get('sms_custom_message_param', 'message'),
            'api_key_param' => SystemSetting::get('sms_custom_api_key_param', 'apikey'),
            'sender_id_param' => SystemSetting::get('sms_custom_sender_id_param', 'senderid'),
            'extra_params' => SystemSetting::get('sms_custom_extra_params', ''),
            'success_response' => SystemSetting::get('sms_custom_success_response', ''),
            'balance_url' => SystemSetting::get('sms_custom_balance_url', ''),
            'balance_field' => SystemSetting::get('sms_custom_balance_field', 'balance'),
        ];
        
        $this->loadSmsSettings();
    }

    protected function loadSmsSettings(): void
    {
        $this->smsSettings = [
            'global_enabled' => SystemSetting::get('sms_global_enabled', '0') === '1',
            'events' => [
                self::EVENT_MONTHLY_BILL => SystemSetting::get('sms_event_monthly_bill', '1') === '1',
                self::EVENT_BILL_CONFIRMATION => SystemSetting::get('sms_event_bill_confirmation', '1') === '1',
                self::EVENT_PAYMENT_CONFIRMATION => SystemSetting::get('sms_event_payment_confirmation', '1') === '1',
                self::EVENT_SERVICE_SUSPENSION => SystemSetting::get('sms_event_service_suspension', '1') === '1',
                self::EVENT_SERVICE_RESTORATION => SystemSetting::get('sms_event_service_restoration', '1') === '1',
                self::EVENT_ADVANCE_PAYMENT => SystemSetting::get('sms_event_advance_payment', '1') === '1',
                self::EVENT_DUE_REMINDER_3DAY => SystemSetting::get('sms_event_due_reminder_3day', '1') === '1',
                self::EVENT_DUE_REMINDER_1DAY => SystemSetting::get('sms_event_due_reminder_1day', '1') === '1',
                self::EVENT_AUTO_INACTIVE => SystemSetting::get('sms_event_auto_inactive', '1') === '1',
            ],
            'roles' => [
                self::RECIPIENT_CLIENT => SystemSetting::get('sms_role_client', '1') === '1',
                self::RECIPIENT_TECHNICIAN => SystemSetting::get('sms_role_technician', '1') === '1',
                self::RECIPIENT_ADMIN => SystemSetting::get('sms_role_admin', '1') === '1',
            ],
        ];
    }

    public function getProvider(): string
    {
        return $this->provider ?? self::PROVIDER_BULKSMS_DHAKA;
    }

    public function isConfigured(): bool
    {
        if ($this->provider === self::PROVIDER_CUSTOM) {
            $url = $this->customApiConfig['url'] ?? '';
            return !empty($url) && filter_var($url, FILTER_VALIDATE_URL) && !empty($this->apiKey);
        }
        return !empty($this->apiKey) && !empty($this->senderId);
    }
    
    public function getConfigurationStatus(): array
    {
        $issues = [];
        
        if ($this->provider === self::PROVIDER_CUSTOM) {
            $url = $this->customApiConfig['url'] ?? '';
            if (empty($url)) {
                $issues[] = 'Custom API URL is not set';
            } elseif (!filter_var($url, FILTER_VALIDATE_URL)) {
                $issues[] = 'Custom API URL is invalid';
            }
            if (empty($this->apiKey)) {
                $issues[] = 'API Key is not set';
            }
        } else {
            if (empty($this->apiKey)) {
                $issues[] = 'API Key is not set';
            }
            if (empty($this->senderId)) {
                $issues[] = 'Sender ID is not set';
            }
        }
        
        return [
            'configured' => empty($issues),
            'issues' => $issues,
        ];
    }

    public function getApiStatus(): array
    {
        $balance = $this->getBalance();
        return [
            'configured' => $this->isConfigured(),
            'provider' => $this->provider,
            'provider_name' => self::getProviders()[$this->provider] ?? 'Unknown',
            'sender_id' => $this->senderId,
            'balance' => $balance['balance'] ?? 0,
            'api_url' => $this->provider === self::PROVIDER_CUSTOM ? $this->customApiConfig['url'] : $this->getProviderApiUrl(),
        ];
    }

    protected function getProviderApiUrl(): string
    {
        return match($this->provider) {
            self::PROVIDER_BULKSMS_DHAKA => 'https://bulksmsdhaka.com/api',
            self::PROVIDER_BULKSMS_BD => 'https://bulksmsbd.net/api',
            self::PROVIDER_SSL_SMS => 'https://smsplus.sslwireless.com/api/v3',
            self::PROVIDER_TWILIO => 'https://api.twilio.com/2010-04-01',
            self::PROVIDER_REVE_SMS => 'http://api.revesms.com/send',
            self::PROVIDER_GREENWEB => 'http://api.greenweb.com.bd/api.php',
            self::PROVIDER_ALPHA_SMS => 'https://api.sms.net.bd/sendsms',
            self::PROVIDER_ELITBUZZ => 'https://msg.elitbuzz-bd.com/smsapi',
            self::PROVIDER_MUTHOFUN => 'https://clients.muthobarta.com/send-sms',
            self::PROVIDER_ONNOROKOM => 'https://api2.onnorokomsms.com/HttpSendSms.ashx',
            self::PROVIDER_SMSNET24 => 'https://smsnet24.com/api/sms/send',
            self::PROVIDER_AJURATECH => 'https://smpp.ajuratech.com:7790/sendtext',
            self::PROVIDER_MIMSMS => 'https://api.mimsms.com/api/SmsSending/SMS',
            self::PROVIDER_ADNSMS => 'https://portal.adnsms.com/api/v1/secure/send-sms',
            self::PROVIDER_BULKSMSPRO => 'https://bulksmspro.net/api/sms-send',
            default => $this->customApiConfig['url'] ?? '',
        };
    }

    public function canSend(string $eventType, string $recipientType = self::RECIPIENT_CLIENT, bool $manualSendFlag = true, ?int $clientId = null): array
    {
        if (!$this->smsSettings['global_enabled']) {
            return ['can_send' => false, 'reason' => 'Global SMS disabled'];
        }

        if (!$manualSendFlag) {
            return ['can_send' => false, 'reason' => 'Manual SMS flag unchecked'];
        }

        if (isset($this->smsSettings['events'][$eventType]) && !$this->smsSettings['events'][$eventType]) {
            return ['can_send' => false, 'reason' => "Event SMS disabled: {$eventType}"];
        }

        if (isset($this->smsSettings['roles'][$recipientType]) && !$this->smsSettings['roles'][$recipientType]) {
            return ['can_send' => false, 'reason' => "Role SMS disabled: {$recipientType}"];
        }

        if ($clientId) {
            $client = Client::find($clientId);
            if ($client && $client->sms_opt_out) {
                return ['can_send' => false, 'reason' => 'Client opted out of SMS'];
            }
        }

        return ['can_send' => true, 'reason' => null];
    }

    public function sendWithPermissionCheck(
        string $phone,
        string $message,
        string $eventType,
        string $recipientType = self::RECIPIENT_CLIENT,
        ?int $clientId = null,
        ?int $sentBy = null,
        bool $manualSendFlag = true
    ): ?SmsLog {
        $checkResult = $this->canSend($eventType, $recipientType, $manualSendFlag, $clientId);

        if (!$checkResult['can_send']) {
            return $this->logSkipped($phone, $message, $eventType, $recipientType, $clientId, $sentBy, $checkResult['reason']);
        }

        return $this->sendSmsInternal($phone, $message, $eventType, $recipientType, $clientId, $sentBy);
    }

    protected function sendSmsInternal(
        string $phone,
        string $message,
        string $eventType,
        string $recipientType,
        ?int $clientId,
        ?int $sentBy
    ): SmsLog {
        // Verify client_id exists to avoid FK violation on sms_logs
        $client = $clientId ? Client::find($clientId) : null;
        $safeClientId = $client?->id;

        try {
            $response = $this->callApi($phone, $message);
            $success = $response !== null && $this->isSuccessResponse($response);

            $smsLog = SmsLog::create([
                'phone' => $phone,
                'message' => $message,
                'status' => $success ? 'sent' : 'failed',
                'event_type' => $eventType,
                'recipient_type' => $recipientType,
                'response' => $response,
                'skip_reason' => null,
                'client_id' => $safeClientId,
                'sent_by' => $sentBy ?? auth()->id(),
            ]);

            event(new SMSDispatched($client, $phone, $eventType, $success, $smsLog, 'system'));

            return $smsLog;
        } catch (\Exception $e) {
            Log::error("SMS send failed: " . $e->getMessage());

            try {
                $smsLog = SmsLog::create([
                    'phone' => $phone,
                    'message' => $message,
                    'status' => 'failed',
                    'event_type' => $eventType,
                    'recipient_type' => $recipientType,
                    'response' => $e->getMessage(),
                    'skip_reason' => null,
                    'client_id' => $safeClientId,
                    'sent_by' => $sentBy ?? auth()->id(),
                ]);
            } catch (\Exception $logEx) {
                Log::error("SMS log save failed: " . $logEx->getMessage());
                $smsLog = new SmsLog(['phone' => $phone, 'status' => 'failed']);
            }

            event(new SMSDispatched($client, $phone, $eventType, false, $smsLog, 'system'));

            return $smsLog;
        }
    }

    protected function isSuccessResponse(?string $response): bool
    {
        if (!$response) return false;
        
        $responseStr = strtolower($response);
        
        if ($this->provider === self::PROVIDER_CUSTOM && !empty($this->customApiConfig['success_response'])) {
            return stripos($response, $this->customApiConfig['success_response']) !== false;
        }
        
        if (stripos($responseStr, '"success":"false"') !== false || stripos($responseStr, '"success": "false"') !== false) {
            return false;
        }
        if (stripos($responseStr, '"success":false') !== false || stripos($responseStr, '"success": false') !== false) {
            return false;
        }
        if (stripos($responseStr, 'not active') !== false || stripos($responseStr, 'invalid') !== false) {
            return false;
        }
        if (stripos($responseStr, '"status":"404"') !== false || stripos($responseStr, '"status":"401"') !== false) {
            return false;
        }
        $data = json_decode($response, true);
        if ($data && is_array($data)) {
            if (isset($data['response_code'])) {
                $code = (string)$data['response_code'];
                // BulkSMS Pro uses "0000" for success
                if ($this->provider === self::PROVIDER_BULKSMSPRO) {
                    return $code === '0000' || $code === '0';
                }
                return in_array((int)$code, [0, 1000, 200, 202]);
            }
            if (isset($data['success']) && ($data['success'] === false || $data['success'] === 'false')) {
                return false;
            }
            if (isset($data['status']) && in_array((string)$data['status'], ['404', '401', '403', '400', '500'])) {
                return false;
            }
            if (isset($data['error']) || (isset($data['status']) && $data['status'] === 'error')) {
                return false;
            }
        }

        if (stripos($responseStr, '"error"') !== false && stripos($responseStr, '"response_code"') === false) {
            return false;
        }
        
        $successIndicators = ['sent', 'delivered', 'queued', 'accepted'];
        foreach ($successIndicators as $indicator) {
            if (stripos($responseStr, $indicator) !== false) {
                return true;
            }
        }
        
        if (stripos($responseStr, '"success":true') !== false || stripos($responseStr, '"success":"true"') !== false) {
            return true;
        }
        
        $data = json_decode($response, true);
        if ($data && is_array($data)) {
            if (isset($data['status'])) {
                $status = strtolower((string)$data['status']);
                if (in_array($status, $successIndicators) || in_array($status, ['100', '200', '202', '1', 'true'])) {
                    return true;
                }
            }
            if (isset($data['Status'])) {
                $status = strtolower((string)$data['Status']);
                if (in_array($status, $successIndicators) || in_array($status, ['100', '200', '202', '1', 'true'])) {
                    return true;
                }
            }
            if (isset($data['success']) && ($data['success'] === true || $data['success'] === 'true' || $data['success'] === 1)) {
                return true;
            }
            if (isset($data['Success']) && strtolower((string)$data['Success']) === 'true') {
                return true;
            }
            if (isset($data['sid']) && !isset($data['error_code'])) {
                return true;
            }
        }
        
        if (preg_match('/^\d{3}$/', trim($response))) {
            $code = (int)trim($response);
            return $code >= 200 && $code < 300;
        }
        
        return false;
    }

    protected function logSkipped(
        string $phone,
        string $message,
        string $eventType,
        string $recipientType,
        ?int $clientId,
        ?int $sentBy,
        string $reason
    ): SmsLog {
        $safeClientId = $clientId && Client::where('id', $clientId)->exists() ? $clientId : null;
        return SmsLog::create([
            'phone' => $phone,
            'message' => $message,
            'status' => 'skipped',
            'event_type' => $eventType,
            'recipient_type' => $recipientType,
            'response' => null,
            'skip_reason' => $reason,
            'client_id' => $safeClientId,
            'sent_by' => $sentBy ?? auth()->id(),
        ]);
    }

    public function sendSms(string $phone, string $message, ?string $type = null, ?int $referenceId = null): ?SmsLog
    {
        return $this->sendWithPermissionCheck(
            $phone,
            $message,
            $type ?? self::EVENT_CUSTOM,
            self::RECIPIENT_CLIENT,
            $referenceId,
            auth()->id(),
            true
        );
    }

    public function testApi(string $phone, string $message): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'error' => 'SMS API is not configured. Please add API credentials.',
                'response' => null,
            ];
        }

        try {
            $formattedPhone = $this->formatPhoneNumber($phone);
            $startTime = microtime(true);
            $response = $this->callApi($formattedPhone, $message);
            $endTime = microtime(true);
            $responseTime = round(($endTime - $startTime) * 1000);
            
            $success = $this->isSuccessResponse($response);
            
            SmsLog::create([
                'phone' => $formattedPhone,
                'message' => $message,
                'status' => $success ? 'sent' : 'failed',
                'event_type' => 'test',
                'recipient_type' => self::RECIPIENT_ADMIN,
                'response' => $response,
                'skip_reason' => null,
                'client_id' => null,
                'sent_by' => auth()->id(),
            ]);
            
            return [
                'success' => $success,
                'response' => $response,
                'response_time' => $responseTime . 'ms',
                'provider' => $this->provider,
                'formatted_phone' => $formattedPhone,
                'error' => $success ? null : 'API returned error response',
            ];
        } catch (\Exception $e) {
            Log::error("SMS test failed: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'response' => null,
                'provider' => $this->provider,
            ];
        }
    }

    protected function resolveTemplate(string $code): ?SmsTemplate
    {
        // Code alias map: SmsService internal name → DB code variants
        $aliases = [
            'monthly_bill'      => ['monthly_bill', 'BILL_GEN', 'bill_generated'],
            'due_reminder'      => ['due_reminder', 'DUE_REM', 'due_reminder_3day', 'due_reminder_1day'],
            'payment_received'  => ['payment_received', 'PAY_RECV', 'payment_confirmation'],
            'disconnect_notice' => ['disconnect_notice', 'ACC_DEACT', 'service_suspension'],
            'reconnect_notice'  => ['reconnect_notice', 'ACC_ACT', 'service_restoration'],
        ];

        $searchCodes = $aliases[$code] ?? [$code, strtoupper($code)];

        return SmsTemplate::whereIn('code', $searchCodes)
            ->where('is_active', true)
            ->orderByDesc('updated_at')
            ->first();
    }

    public function sendFromTemplate(string $templateCode, Client $client, array $variables = [], bool $sendSms = true): ?SmsLog
    {
        $template = $this->resolveTemplate($templateCode);
        
        if (!$template) {
            return null;
        }
        
        $message = $this->parseTemplate($template->content, $client, $variables);
        
        return $this->sendWithPermissionCheck(
            $client->phone,
            $message,
            $templateCode,
            self::RECIPIENT_CLIENT,
            $client->id,
            auth()->id(),
            $sendSms
        );
    }

    public function sendBillNotification(Client $client, Bill $bill, bool $sendSms = true): ?SmsLog
    {
        if (!$client->phone) {
            return null;
        }

        $client->refresh();
        $advanceUsed = (float) ($bill->advance_used ?? 0);
        $advanceBalance = max(
            (float) ($client->balance ?? 0),
            $client->advancePayments()->where('remaining_amount', '>', 0)->sum('remaining_amount')
        );

        $template = $this->resolveTemplate('monthly_bill');
        $billMonth = date('F Y', mktime(0, 0, 0, $bill->billing_month, 1, $bill->billing_year));
        $billAmount = number_format($bill->total_amount, 2);
        $payLink = rtrim(config('app.url'), '/') . '/payment';
        $variables = [
            'bill_amount' => $billAmount,
            'amount' => $billAmount,
            'due_amount' => number_format(max(0, $bill->due_amount), 2),
            'due_date' => $bill->due_date->format('d-m-Y'),
            'bill_month' => $billMonth,
            'month' => $billMonth,
            'bill_no' => $bill->bill_no,
            'advance_used' => number_format($advanceUsed, 2),
            'advance_balance' => number_format($advanceBalance, 2),
            'date' => now()->timezone('Asia/Dhaka')->format('d-m-Y'),
            'time' => now()->timezone('Asia/Dhaka')->format('h:i A'),
            'payment_link' => $payLink,
        ];

        if ($advanceUsed > 0 && $bill->due_amount <= 0) {
            $message = $template
                ? $this->parseTemplate($template->content, $client, $variables)
                : "ID: " . ($client->username ?? $client->client_id) . ", your {$variables['bill_month']} bill of BDT {$variables['bill_amount']} has been auto-paid from advance. Remaining advance: BDT {$variables['advance_balance']}. No dues! - SOFTYNEX";
        } elseif ($advanceUsed > 0) {
            $message = $template
                ? $this->parseTemplate($template->content, $client, $variables)
                : "ID: " . ($client->username ?? $client->client_id) . ", your {$variables['bill_month']} bill: BDT {$variables['bill_amount']}. Advance used: BDT {$variables['advance_used']}. Remaining due: BDT {$variables['due_amount']}. Pay by {$variables['due_date']}: {$payLink} - SOFTYNEX";
        } else {
            $message = $template 
                ? $this->parseTemplate($template->content, $client, $variables)
                : "ID: " . ($client->username ?? $client->client_id) . ", your bill of BDT {$variables['bill_amount']} for {$variables['bill_month']} is ready. Due: {$variables['due_date']}. Pay: {$payLink} - SOFTYNEX";
        }

        return $this->sendWithPermissionCheck(
            $client->phone,
            $message,
            self::EVENT_MONTHLY_BILL,
            self::RECIPIENT_CLIENT,
            $client->id,
            auth()->id(),
            $sendSms
        );
    }

    public function sendPaymentConfirmation(Client $client, float $amount, string $paymentNo, bool $sendSms = true): ?SmsLog
    {
        if (!$client->phone) {
            return null;
        }

        $client->refresh();
        $dueAmount = (float)($client->due_amount ?? 0);
        $advanceBalance = (float)($client->balance ?? 0);
        if ($advanceBalance <= 0) {
            $advanceBalance = max(
                $client->getAvailableAdvance(),
                $client->advancePayments()->where('remaining_amount', '>', 0)->sum('remaining_amount')
            );
        }

        $template = $this->resolveTemplate('payment_received');
        $variables = [
            'amount' => number_format($amount, 2),
            'payment_no' => $paymentNo,
            'due_amount' => number_format(max(0, $dueAmount), 2),
            'advance_balance' => number_format($advanceBalance, 2),
            'date' => now()->timezone('Asia/Dhaka')->format('d-m-Y'),
            'time' => now()->timezone('Asia/Dhaka')->format('h:i A'),
        ];

        $message = $template 
            ? $this->parseTemplate($template->content, $client, $variables)
            : "ID: " . ($client->username ?? $client->client_id) . ", we received your payment of BDT {$variables['amount']}. Due: BDT {$variables['due_amount']}. Advance: BDT {$variables['advance_balance']}. Receipt: {$paymentNo}. Thank you! - SOFTYNEX";

        return $this->sendWithPermissionCheck(
            $client->phone,
            $message,
            self::EVENT_PAYMENT_CONFIRMATION,
            self::RECIPIENT_CLIENT,
            $client->id,
            auth()->id(),
            $sendSms
        );
    }

    public function sendAdvancePaymentConfirmation(Client $client, float $amount, bool $sendSms = true): ?SmsLog
    {
        if (!$client->phone) {
            return null;
        }

        $client->refresh();
        $advanceBalance = max(
            $client->getAvailableAdvance(),
            $client->advancePayments()->where('remaining_amount', '>', 0)->sum('remaining_amount')
        );
        $dueAmount = (float)($client->due_amount ?? 0);

        $template = $this->resolveTemplate('advance_payment');
        $variables = [
            'amount' => number_format($amount, 2),
            'advance_balance' => number_format($advanceBalance, 2),
            'date' => now()->timezone('Asia/Dhaka')->format('d-m-Y'),
            'time' => now()->timezone('Asia/Dhaka')->format('h:i A'),
            'credit' => number_format($advanceBalance, 2),
            'due_amount' => number_format(max(0, $dueAmount), 2),
        ];

        $message = $template
            ? $this->parseTemplate($template->content, $client, $variables)
            : "ID: " . ($client->username ?? $client->client_id) . ", we received advance payment of BDT " . number_format($amount, 2) . ". Credit balance: BDT " . number_format($advanceBalance, 2) . ". Thank you! - SOFTYNEX";

        return $this->sendWithPermissionCheck(
            $client->phone,
            $message,
            self::EVENT_ADVANCE_PAYMENT,
            self::RECIPIENT_CLIENT,
            $client->id,
            auth()->id(),
            $sendSms
        );
    }

    public function sendDueReminder(Bill $bill, bool $sendSms = true): ?SmsLog
    {
        $client = $bill->client;
        
        $advanceBalance = max(
            $client->getAvailableAdvance(),
            $client->advancePayments()->where('remaining_amount', '>', 0)->sum('remaining_amount')
        );
        $effectiveDue = max(0, $bill->due_amount - $advanceBalance);
        
        if ($advanceBalance >= $bill->due_amount) {
            Log::info("Due reminder skipped for client {$client->id} - sufficient advance balance", [
                'advance_balance' => $advanceBalance,
                'bill_due' => $bill->due_amount
            ]);
            return $this->logSkipped(
                $client->phone ?? '',
                'Due reminder skipped - sufficient advance balance',
                'due_reminder',
                self::RECIPIENT_CLIENT,
                $client->id,
                null,
                "Advance balance (৳{$advanceBalance}) covers bill due (৳{$bill->due_amount})"
            );
        }
        
        $hasPartialAdvance = $advanceBalance > 0 && $effectiveDue > 0;
        
        $paymentLink = $this->generatePaymentLink($client, $effectiveDue);

        $variables = [
            'bill_amount' => number_format($bill->total_amount, 2),
            'amount' => number_format($effectiveDue, 2),
            'due_amount' => number_format($effectiveDue, 2),
            'original_due' => number_format($bill->due_amount, 2),
            'advance_adjusted' => number_format($advanceBalance, 2),
            'due_date' => $bill->due_date->format('d-m-Y'),
            'bill_month' => date('F', mktime(0, 0, 0, $bill->billing_month, 1)),
            'payment_link' => $paymentLink,
            'advance_note' => $hasPartialAdvance 
                ? "Your advance of ৳" . number_format($advanceBalance, 2) . " has been adjusted."
                : ''
        ];
        
        $template = $this->resolveTemplate('due_reminder');
        $templateContent = $template?->content ?? '';
        
        if ($hasPartialAdvance && !str_contains($templateContent, '{advance_note}') && !str_contains($templateContent, '{advance_adjusted}')) {
            $templateContent = "ID: {pppoe_id}, due BDT {original_due} by {due_date}. Advance adjusted: BDT {advance_adjusted}. Remaining: BDT {due_amount}. Pay: {payment_link} - SOFTYNEX";
        }
        
        DueReminder::create([
            'bill_id' => $bill->id,
            'client_id' => $client->id,
            'reminder_day' => (int) now()->diffInDays($bill->due_date),
            'sms_sent' => $sendSms,
            'sent_at' => now(),
            'message' => $this->parseTemplate($templateContent, $client, $variables)
        ]);
        
        if ($hasPartialAdvance && $template && !str_contains($template->content, '{advance_note}') && !str_contains($template->content, '{advance_adjusted}')) {
            return $this->sendWithPermissionCheck(
                $client->phone,
                $this->parseTemplate($templateContent, $client, $variables),
                'due_reminder',
                self::RECIPIENT_CLIENT,
                $client->id,
                auth()->id(),
                $sendSms
            );
        }
        
        return $this->sendFromTemplate('due_reminder', $client, $variables, $sendSms);
    }

    public function sendDisconnectNotice(Client $client, bool $sendSms = true): ?SmsLog
    {
        if (!$client->phone) {
            return null;
        }

        $advanceBalance = max(
            $client->getAvailableAdvance(),
            $client->advancePayments()->where('remaining_amount', '>', 0)->sum('remaining_amount')
        );
        $totalDue = (float)($client->due_amount ?? 0);
        
        if ($totalDue <= 0) {
            Log::info("Disconnect notice skipped for client {$client->id} - no outstanding due", [
                'total_due' => $totalDue
            ]);
            return $this->logSkipped(
                $client->phone,
                'Disconnect notice skipped - no outstanding due',
                self::EVENT_SERVICE_SUSPENSION,
                self::RECIPIENT_CLIENT,
                $client->id,
                null,
                "No outstanding due (৳{$totalDue})"
            );
        }
        
        if ($advanceBalance >= $totalDue) {
            Log::info("Disconnect notice skipped for client {$client->id} - sufficient advance balance", [
                'advance_balance' => $advanceBalance,
                'total_due' => $totalDue
            ]);
            return $this->logSkipped(
                $client->phone,
                'Disconnect notice skipped - sufficient advance balance',
                self::EVENT_SERVICE_SUSPENSION,
                self::RECIPIENT_CLIENT,
                $client->id,
                null,
                "Advance balance (৳{$advanceBalance}) covers total due (৳{$totalDue})"
            );
        }

        $paymentLink = $this->generatePaymentLink($client, $totalDue);

        $variables = [
            'due_amount' => number_format($totalDue, 2),
            'payment_link' => $paymentLink,
        ];

        $template = $this->resolveTemplate('disconnect_notice');
        $message = $template 
            ? $this->parseTemplate($template->content, $client, $variables)
            : "ID: " . ($client->username ?? $client->client_id) . ", your line is inactive for due BDT " . number_format($totalDue, 2) . ". Pay now: {$paymentLink} - SOFTYNEX";

        return $this->sendWithPermissionCheck(
            $client->phone,
            $message,
            self::EVENT_SERVICE_SUSPENSION,
            self::RECIPIENT_CLIENT,
            $client->id,
            auth()->id(),
            $sendSms
        );
    }

    public function sendReconnectNotice(Client $client, bool $sendSms = true): ?SmsLog
    {
        if (!$client->phone) {
            return null;
        }

        $template = $this->resolveTemplate('reconnect_notice');
        $message = $template 
            ? $this->parseTemplate($template->content, $client)
            : "Dear {$client->name}, your internet service has been restored. Thank you for your payment!";

        return $this->sendWithPermissionCheck(
            $client->phone,
            $message,
            self::EVENT_SERVICE_RESTORATION,
            self::RECIPIENT_CLIENT,
            $client->id,
            auth()->id(),
            $sendSms
        );
    }

    public function sendTicketUpdate(Client $client, string $ticketNo, string $status, bool $sendSms = true): ?SmsLog
    {
        return $this->sendFromTemplate('ticket_update', $client, [
            'ticket_no' => $ticketNo,
            'status' => $status
        ], $sendSms);
    }

    public function sendBulkSms(array $clientIds, string $message, ?string $type = null, bool $sendSms = true): array
    {
        $results = [];
        $clients = Client::whereIn('id', $clientIds)->whereNotNull('phone')->get();
        
        if ($clients->count() <= self::BULK_THRESHOLD) {
            foreach ($clients as $client) {
                $parsedMessage = $this->parseTemplate($message, $client);
                $results[] = $this->sendWithPermissionCheck(
                    $client->phone,
                    $parsedMessage,
                    $type ?? self::EVENT_CUSTOM,
                    self::RECIPIENT_CLIENT,
                    $client->id,
                    auth()->id(),
                    $sendSms
                );
            }
        } else {
            $checkResult = $this->canSend($type ?? self::EVENT_CUSTOM, self::RECIPIENT_CLIENT, $sendSms);
            
            if (!$checkResult['can_send']) {
                foreach ($clients as $client) {
                    $results[] = $this->logSkipped(
                        $client->phone,
                        $this->parseTemplate($message, $client),
                        $type ?? self::EVENT_CUSTOM,
                        self::RECIPIENT_CLIENT,
                        $client->id,
                        auth()->id(),
                        $checkResult['reason']
                    );
                }
                return $results;
            }
            
            foreach ($clients as $client) {
                $parsedMessage = $this->parseTemplate($message, $client);
                $response = $this->callApi($client->phone, $parsedMessage);
                $success = $this->isSuccessResponse($response);
                
                $results[] = SmsLog::create([
                    'phone' => $client->phone,
                    'message' => $parsedMessage,
                    'status' => $success ? 'sent' : 'failed',
                    'event_type' => $type ?? self::EVENT_CUSTOM,
                    'recipient_type' => self::RECIPIENT_CLIENT,
                    'response' => $response,
                    'skip_reason' => null,
                    'client_id' => $client->id,
                    'sent_by' => auth()->id(),
                ]);
            }
        }
        
        return $results;
    }

    public function sendBulkByFilter(array $filter, string $message, bool $sendSms = true): array
    {
        $query = Client::query();
        
        if (!empty($filter['zone_id'])) {
            $query->where('zone_id', $filter['zone_id']);
        }
        
        if (!empty($filter['package_id'])) {
            $query->where('package_id', $filter['package_id']);
        }
        
        if (!empty($filter['status'])) {
            $query->where('status', $filter['status']);
        }
        
        $clientIds = $query->pluck('id')->toArray();
        
        return $this->sendBulkSms($clientIds, $message, 'bulk', $sendSms);
    }

    protected function generatePaymentLink(Client $client, float $amount): string
    {
        return rtrim(config('app.url'), '/') . '/payment';
    }

    protected function parseTemplate(string $template, Client $client, array $variables = []): string
    {
        $paymentUrl = rtrim(config('app.url'), '/') . '/payment';
        $replacements = array_merge([
            '{client_name}' => $client->name,
            '{pppoe_id}' => $client->username ?? $client->client_id ?? '',
            '{username}' => $client->username ?? $client->client_id ?? '',
            '{pppoe_password}' => $client->pppoe_password ?? '',
            '{client_id}' => $client->client_id,
            '{payment_link}' => $paymentUrl,
            '{client_code}' => $client->client_id ?? '',
            '{phone}' => $client->phone,
            '{package}' => $client->package?->name ?? '',
            '{package_name}' => $client->package?->name ?? '',
            '{monthly_fee}' => number_format($client->monthly_fee, 2),
            '{due_amount}' => number_format((float)($client->due_amount ?? 0), 2),
            '{balance}' => number_format((float)($client->balance ?? 0), 2),
            '{company_name}' => SystemSetting::get('company_name', 'SOFTYNEX'),
        ], array_combine(
            array_map(fn($k) => "{{$k}}", array_keys($variables)),
            array_values($variables)
        ));
        
        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }

    protected function callApi(string $phone, string $message): ?string
    {
        if (!$this->apiKey) {
            Log::warning('SMS API not configured - missing API key');
            return json_encode(['status' => 'error', 'message' => 'SMS API not configured']);
        }

        $formattedPhone = $this->formatPhoneNumber($phone);

        try {
            $response = match($this->provider) {
                self::PROVIDER_CUSTOM => $this->callCustomApi($formattedPhone, $message),
                self::PROVIDER_BULKSMS_DHAKA => $this->callBulkSmsDhakaApi($formattedPhone, $message),
                self::PROVIDER_BULKSMS_BD => $this->callBulkSmsBdApi($formattedPhone, $message),
                self::PROVIDER_SSL_SMS => $this->callSslSmsApi($formattedPhone, $message),
                self::PROVIDER_TWILIO => $this->callTwilioApi($formattedPhone, $message),
                self::PROVIDER_REVE_SMS => $this->callReveSmsApi($formattedPhone, $message),
                self::PROVIDER_GREENWEB => $this->callGreenwebApi($formattedPhone, $message),
                self::PROVIDER_ALPHA_SMS => $this->callAlphaSmsApi($formattedPhone, $message),
                self::PROVIDER_ELITBUZZ => $this->callElitbuzzApi($formattedPhone, $message),
                self::PROVIDER_MUTHOFUN => $this->callMuthofunApi($formattedPhone, $message),
                self::PROVIDER_ONNOROKOM => $this->callOnnorokomApi($formattedPhone, $message),
                self::PROVIDER_SMSNET24 => $this->callSmsnet24Api($formattedPhone, $message),
                self::PROVIDER_AJURATECH => $this->callAjuratechApi($formattedPhone, $message),
                self::PROVIDER_MIMSMS => $this->callMimsmsApi($formattedPhone, $message),
                self::PROVIDER_ADNSMS => $this->callAdnsmsApi($formattedPhone, $message),
                self::PROVIDER_BULKSMSPRO => $this->callBulkSmsProApi($formattedPhone, $message),
                default => $this->callCustomApi($formattedPhone, $message),
            };

            $this->clearBalanceCache();
            
            return $response;
        } catch (\Exception $e) {
            Log::error("SMS API Error ({$this->provider}): " . $e->getMessage());
            return json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    protected function callCustomApi(string $phone, string $message): string
    {
        $url = $this->customApiConfig['url'] ?? '';
        
        if (empty($url)) {
            Log::error('Custom SMS API Error: API URL is not configured');
            return json_encode(['status' => 'error', 'message' => 'Custom API URL is not configured. Please set the API URL in SMS Settings.']);
        }
        
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            Log::error('Custom SMS API Error: Invalid URL format', ['url' => $url]);
            return json_encode(['status' => 'error', 'message' => 'Invalid API URL format. Please check the URL in SMS Settings.']);
        }
        
        $method = strtoupper($this->customApiConfig['method'] ?? 'GET');
        
        $apiKeyParam = $this->customApiConfig['api_key_param'] ?? 'apikey';
        $senderIdParam = $this->customApiConfig['sender_id_param'] ?? 'senderid';
        $phoneParam = $this->customApiConfig['phone_param'] ?? 'number';
        $messageParam = $this->customApiConfig['message_param'] ?? 'message';
        
        $params = [
            $apiKeyParam => $this->apiKey,
            $senderIdParam => $this->senderId,
            $phoneParam => $phone,
            $messageParam => $message,
        ];
        
        if (!empty($this->customApiConfig['extra_params'])) {
            $extraParams = [];
            $pairs = explode('&', $this->customApiConfig['extra_params']);
            foreach ($pairs as $pair) {
                $parts = explode('=', $pair, 2);
                if (count($parts) === 2) {
                    $extraParams[trim($parts[0])] = trim($parts[1]);
                }
            }
            $params = array_merge($params, $extraParams);
        }
        
        Log::info("Custom SMS API call", ['url' => $url, 'method' => $method, 'phone' => $phone]);
        
        $response = match($method) {
            'POST' => Http::timeout(30)->asForm()->post($url, $params),
            'POST_JSON' => Http::timeout(30)->post($url, $params),
            default => Http::timeout(30)->get($url, $params),
        };

        $body = $response->body();
        
        if (str_starts_with(trim($body), '<')) {
            Log::warning('Custom SMS API returned HTML instead of JSON', ['body' => substr($body, 0, 500)]);
        }
        
        $result = $response->json() ?? ['raw_response' => $body];
        
        Log::info('Custom SMS API Response', ['phone' => $phone, 'response' => $result]);
        
        return json_encode($result);
    }

    protected function callBulkSmsDhakaApi(string $phone, string $message): string
    {
        $response = Http::timeout(30)->get('https://bulksmsdhaka.com/api/sendtext', [
            'apikey' => $this->apiKey,
            'callerID' => $this->senderId,
            'number' => $phone,
            'message' => $message
        ]);

        $body = $response->body();
        
        if (str_starts_with(trim($body), '<')) {
            Log::error('BulkSMS Dhaka API returned HTML', ['body' => substr($body, 0, 500)]);
            return json_encode(['status' => 'error', 'message' => 'API returned invalid response']);
        }
        
        $result = $response->json() ?? ['status' => 'error', 'message' => 'Empty response'];
        
        Log::info('BulkSMS Dhaka API Response', ['phone' => $phone, 'response' => $result]);
        
        return json_encode($result);
    }

    protected function callBulkSmsBdApi(string $phone, string $message): string
    {
        $response = Http::timeout(30)->asForm()->post('https://bulksmsbd.net/api/smsapi', [
            'api_key' => $this->apiKey,
            'senderid' => $this->senderId,
            'number' => $phone,
            'message' => $message
        ]);

        $result = $response->json() ?? ['status' => 'error', 'message' => 'Empty response'];
        
        Log::info('BulkSMS BD API Response', ['phone' => $phone, 'response' => $result]);
        
        return json_encode($result);
    }

    protected function callSslSmsApi(string $phone, string $message): string
    {
        $response = Http::timeout(30)->post('https://smsplus.sslwireless.com/api/v3/send-sms', [
            'api_token' => $this->apiKey,
            'sid' => $this->senderId,
            'msisdn' => $phone,
            'sms' => $message,
            'csms_id' => uniqid('ssl_'),
        ]);

        $result = $response->json() ?? ['status' => 'error', 'message' => 'Empty response'];
        
        Log::info('SSL SMS API Response', ['phone' => $phone, 'response' => $result]);
        
        return json_encode($result);
    }

    protected function callTwilioApi(string $phone, string $message): string
    {
        $accountSid = $this->apiKey;
        $authToken = SystemSetting::get('sms_twilio_auth_token', '');
        
        if (!$authToken) {
            return json_encode(['status' => 'error', 'message' => 'Twilio Auth Token not configured']);
        }
        
        $response = Http::timeout(30)
            ->withBasicAuth($accountSid, $authToken)
            ->asForm()
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$accountSid}/Messages.json", [
                'From' => $this->senderId,
                'To' => $phone,
                'Body' => $message,
            ]);

        $result = $response->json() ?? ['status' => 'error', 'message' => 'Empty response'];
        
        Log::info('Twilio API Response', ['phone' => $phone, 'response' => $result]);
        
        return json_encode($result);
    }

    protected function callReveSmsApi(string $phone, string $message): string
    {
        $response = Http::timeout(30)->get('http://api.revesms.com/send', [
            'apikey' => $this->apiKey,
            'secretkey' => SystemSetting::get('sms_reve_secret_key', ''),
            'callerID' => $this->senderId,
            'toUser' => $phone,
            'messageContent' => $message
        ]);

        $result = $response->json() ?? ['raw_response' => $response->body()];
        Log::info('Reve SMS API Response', ['phone' => $phone, 'response' => $result]);
        return json_encode($result);
    }

    protected function callGreenwebApi(string $phone, string $message): string
    {
        $response = Http::timeout(30)->get('http://api.greenweb.com.bd/api.php', [
            'token' => $this->apiKey,
            'to' => $phone,
            'message' => $message
        ]);

        $result = ['raw_response' => $response->body()];
        Log::info('Greenweb API Response', ['phone' => $phone, 'response' => $result]);
        return json_encode($result);
    }

    protected function callAlphaSmsApi(string $phone, string $message): string
    {
        $response = Http::timeout(30)->post('https://api.sms.net.bd/sendsms', [
            'api_key' => $this->apiKey,
            'msg' => $message,
            'to' => $phone,
        ]);

        $result = $response->json() ?? ['raw_response' => $response->body()];
        Log::info('Alpha SMS API Response', ['phone' => $phone, 'response' => $result]);
        return json_encode($result);
    }

    protected function callElitbuzzApi(string $phone, string $message): string
    {
        $response = Http::timeout(30)->get('https://msg.elitbuzz-bd.com/smsapi', [
            'api_key' => $this->apiKey,
            'type' => 'text',
            'contacts' => $phone,
            'senderid' => $this->senderId,
            'msg' => $message
        ]);

        $result = $response->json() ?? ['raw_response' => $response->body()];
        Log::info('Elitbuzz API Response', ['phone' => $phone, 'response' => $result]);
        return json_encode($result);
    }

    protected function callMuthofunApi(string $phone, string $message): string
    {
        // Official Muthofun/Muthobarta API: https://clients.muthobarta.com
        // Auth: Authorization: Token {api_key}
        // Params: receiver (comma-separated numbers), message, remove_duplicate
        $response = Http::timeout(30)
            ->withHeaders(['Authorization' => 'Token ' . $this->apiKey])
            ->post('https://clients.muthobarta.com/send-sms', [
                'receiver'         => $phone,
                'message'          => $message,
                'remove_duplicate' => true,
            ]);

        $result = $response->json() ?? ['raw_response' => $response->body()];
        Log::info('Muthofun API Response', ['phone' => $phone, 'response' => $result]);
        return json_encode($result);
    }

    protected function callOnnorokomApi(string $phone, string $message): string
    {
        $response = Http::timeout(30)->get('https://api2.onnorokomsms.com/HttpSendSms.ashx', [
            'op' => 'OneToOne',
            'apiKey' => $this->apiKey,
            'type' => 'TEXT',
            'mobile' => $phone,
            'smsText' => $message,
            'maskName' => $this->senderId
        ]);

        $result = ['raw_response' => $response->body()];
        Log::info('Onnorokom API Response', ['phone' => $phone, 'response' => $result]);
        return json_encode($result);
    }

    protected function callSmsnet24Api(string $phone, string $message): string
    {
        $response = Http::timeout(30)->post('https://smsnet24.com/api/sms/send', [
            'api_key' => $this->apiKey,
            'sender_id' => $this->senderId,
            'phone' => $phone,
            'message' => $message
        ]);

        $result = $response->json() ?? ['raw_response' => $response->body()];
        Log::info('SMSNet24 API Response', ['phone' => $phone, 'response' => $result]);
        return json_encode($result);
    }

    protected function callAjuratechApi(string $phone, string $message): string
    {
        $response = Http::timeout(30)->get('https://smpp.ajuratech.com:7790/sendtext', [
            'apikey' => $this->apiKey,
            'secretkey' => SystemSetting::get('sms_ajura_secret_key', ''),
            'callerID' => $this->senderId,
            'toUser' => $phone,
            'messageContent' => $message
        ]);

        $result = $response->json() ?? ['raw_response' => $response->body()];
        Log::info('Ajuratech API Response', ['phone' => $phone, 'response' => $result]);
        return json_encode($result);
    }

    protected function callMimsmsApi(string $phone, string $message): string
    {
        // Official MimSMS API: https://api.mimsms.com
        // POST JSON: UserName (email), Apikey, MobileNumber, SenderName, TransactionType (T=transactional), Message
        $response = Http::timeout(30)
            ->withHeaders(['Content-Type' => 'application/json', 'Accept' => 'application/json'])
            ->post('https://api.mimsms.com/api/SmsSending/SMS', [
                'UserName'        => $this->apiKey,
                'Apikey'          => $this->senderId,
                'MobileNumber'    => $phone,
                'SenderName'      => $this->senderId,
                'TransactionType' => 'T',
                'Message'         => $message,
                'CampaignId'      => 'null',
            ]);

        $result = $response->json() ?? ['raw_response' => $response->body()];
        Log::info('MIM SMS API Response', ['phone' => $phone, 'response' => $result]);
        return json_encode($result);
    }

    protected function callAdnsmsApi(string $phone, string $message): string
    {
        // Official ADN SMS API: https://portal.adnsms.com/api/v1/secure/send-sms
        // Auth: api_key + api_secret as POST params (NO Bearer header)
        // request_type: SINGLE_SMS | OTP | GENERAL_CAMPAIGN | MULTIBODY_CAMPAIGN
        // message_type: TEXT | UNICODE
        $response = Http::timeout(30)
            ->withHeaders(['Accept' => 'application/json'])
            ->post('https://portal.adnsms.com/api/v1/secure/send-sms', [
                'api_key'      => $this->apiKey,
                'api_secret'   => SystemSetting::get('sms_adn_secret_key', ''),
                'request_type' => 'SINGLE_SMS',
                'message_type' => 'TEXT',
                'mobile'       => $phone,
                'message_body' => $message,
            ]);

        $result = $response->json() ?? ['raw_response' => $response->body()];
        Log::info('ADN SMS API Response', ['phone' => $phone, 'response' => $result]);
        return json_encode($result);
    }

   protected function callBulkSmsProApi(string $phone, string $message): string
{
    $response = Http::withOptions([
        'verify' => false,
    ])->timeout(30)->get('https://bulksmspro.net/api/sms-send', [
        'api_key' => $this->apiKey,
        'sender_id' => $this->senderId,
        'phone_numbers' => $phone,
        'message' => $message,
        'type' => 'text',
    ]);


        $body = $response->body();
        
        if (str_starts_with(trim($body), '<')) {
            Log::error('BulkSMS Pro API returned HTML', ['body' => substr($body, 0, 500)]);
            return json_encode(['status' => 'error', 'message' => 'API returned invalid response']);
        }
        
        $result = $response->json() ?? ['raw_response' => $body];
        
        Log::info('BulkSMS Pro API Response', ['phone' => $phone, 'response' => $result]);
        
        return json_encode($result);
    }

    public function getBalance(bool $forceRefresh = false): ?array
    {
        if (!$this->apiKey) {
            return ['balance' => 0, 'error' => 'API key not configured'];
        }

        $cacheKey = 'sms_balance_' . md5($this->apiKey . $this->provider);
        
        if (!$forceRefresh && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $balanceData = match($this->provider) {
                self::PROVIDER_CUSTOM       => $this->getCustomApiBalance(),
                self::PROVIDER_BULKSMS_DHAKA => $this->getBulkSmsDhakaBalance(),
                self::PROVIDER_BULKSMS_BD   => $this->getBulkSmsBdBalance(),
                self::PROVIDER_SSL_SMS      => $this->getSslSmsBalance(),
                self::PROVIDER_TWILIO       => $this->getTwilioBalance(),
                self::PROVIDER_BULKSMSPRO   => $this->getBulkSmsProBalance(),
                self::PROVIDER_GREENWEB     => $this->getGreenwebBalance(),
                self::PROVIDER_ALPHA_SMS    => $this->getAlphaSmsBalance(),
                self::PROVIDER_ELITBUZZ     => $this->getElitbuzzBalance(),
                self::PROVIDER_MIMSMS       => $this->getMimSmsBalance(),
                self::PROVIDER_ADNSMS       => $this->getAdnSmsBalance(),
                self::PROVIDER_MUTHOFUN     => $this->getMuthofunBalance(),
                default => ['balance' => 'N/A', 'message' => 'Balance check not supported for this provider'],
            };
            
            Cache::put($cacheKey, $balanceData, now()->addMinutes(5));
            
            return $balanceData;
        } catch (\Exception $e) {
            Log::error('SMS Balance Check Error: ' . $e->getMessage());
            return ['balance' => 0, 'error' => $e->getMessage()];
        }
    }

    protected function getBulkSmsDhakaBalance(): array
    {
        $response = Http::get('https://bulksmsdhaka.com/api/getBalance', [
            'apikey' => $this->apiKey
        ]);

        $result = $response->json();
        
        return [
            'balance' => $result['Balance'] ?? $result['balance'] ?? 0,
            'raw_response' => $result,
            'updated_at' => now()->toDateTimeString()
        ];
    }

    protected function getBulkSmsBdBalance(): array
    {
        $response = Http::asForm()->post('https://bulksmsbd.net/api/getBalanceApi', [
            'api_key' => $this->apiKey
        ]);

        $result = $response->json();
        
        return [
            'balance' => $result['balance'] ?? 0,
            'raw_response' => $result,
            'updated_at' => now()->toDateTimeString()
        ];
    }

    protected function getSslSmsBalance(): array
    {
        $response = Http::get('https://smsplus.sslwireless.com/api/v3/balance', [
            'api_token' => $this->apiKey
        ]);

        $result = $response->json();
        
        return [
            'balance' => $result['balance'] ?? 0,
            'raw_response' => $result,
            'updated_at' => now()->toDateTimeString()
        ];
    }

    protected function getTwilioBalance(): array
    {
        $accountSid = $this->apiKey;
        $authToken = SystemSetting::get('sms_twilio_auth_token', '');
        
        if (!$authToken) {
            return ['balance' => 'N/A', 'error' => 'Twilio Auth Token not configured'];
        }
        
        $response = Http::withBasicAuth($accountSid, $authToken)
            ->get("https://api.twilio.com/2010-04-01/Accounts/{$accountSid}/Balance.json");

        $result = $response->json();
        
        return [
            'balance' => $result['balance'] ?? 'N/A',
            'currency' => $result['currency'] ?? 'USD',
            'raw_response' => $result,
            'updated_at' => now()->toDateTimeString()
        ];
    }

    protected function getBulkSmsProBalance(): array
    {
        try {
            $url = "https://bulksmspro.net/api/{$this->apiKey}/getBalance";
            
            $response = Http::withOptions([
    'verify' => false,
])->timeout(15)->get($url);


            $body = $response->body();
            
            if (str_contains($body, '404') || str_starts_with(trim($body), '<')) {
                return [
                    'balance' => 'Check in Portal',
                    'message' => 'Please check balance at bulksmspro.net',
                    'currency' => 'BDT',
                    'updated_at' => now()->toDateTimeString()
                ];
            }
            
            $result = $response->json();
            
            if (!$result) {
                if (is_numeric(trim($body))) {
                    return [
                        'balance' => floatval(trim($body)),
                        'currency' => 'BDT',
                        'updated_at' => now()->toDateTimeString()
                    ];
                }
                return [
                    'balance' => 'Check in Portal',
                    'message' => 'Please check balance at bulksmspro.net',
                    'currency' => 'BDT',
                    'updated_at' => now()->toDateTimeString()
                ];
            }
            
            $balance = $result['data']['current_balance'] ?? $result['balance'] ?? $result['credit'] ?? $result['credits'] ?? 'N/A';
            
            return [
                'balance' => $balance,
                'currency' => 'BDT',
                'raw_response' => $result,
                'updated_at' => now()->toDateTimeString()
            ];
        } catch (\Exception $e) {
            Log::error('BulkSMS Pro Balance Check Error: ' . $e->getMessage());
            return [
                'balance' => 'Check in Portal',
                'message' => 'Please check balance at bulksmspro.net',
                'currency' => 'BDT',
                'updated_at' => now()->toDateTimeString()
            ];
        }
    }

    protected function getGreenwebBalance(): array
    {
        // Greenweb balance: GET https://api.greenweb.com.bd/api.php with token & type=balance
        $response = Http::timeout(15)->get('https://api.greenweb.com.bd/api.php', [
            'token' => $this->apiKey,
            'type'  => 'balance',
        ]);
        $body = trim($response->body());
        if (is_numeric($body)) {
            return ['balance' => (float)$body, 'currency' => 'BDT', 'unit' => 'SMS', 'updated_at' => now()->toDateTimeString()];
        }
        $data = $response->json();
        return ['balance' => $data['balance'] ?? $body, 'currency' => 'BDT', 'unit' => 'SMS', 'updated_at' => now()->toDateTimeString()];
    }

    protected function getAlphaSmsBalance(): array
    {
        // Alpha SMS balance: GET https://api.sms.net.bd/user/balance with Bearer token
        $response = Http::timeout(15)->withToken($this->apiKey)->get('https://api.sms.net.bd/user/balance');
        $data = $response->json();
        return ['balance' => $data['data']['balance'] ?? $data['balance'] ?? null, 'currency' => 'BDT', 'unit' => 'SMS', 'updated_at' => now()->toDateTimeString()];
    }

    protected function getElitbuzzBalance(): array
    {
        // Elitbuzz balance: GET https://msg.elitbuzz-bd.com/smsapi/balancecheck?api_key={}
        $response = Http::timeout(15)->get('https://msg.elitbuzz-bd.com/smsapi/balancecheck', [
            'api_key' => $this->apiKey,
        ]);
        $body = trim($response->body());
        if (is_numeric($body)) {
            return ['balance' => (float)$body, 'currency' => 'BDT', 'unit' => 'SMS', 'updated_at' => now()->toDateTimeString()];
        }
        $data = $response->json();
        return ['balance' => $data['balance'] ?? $body, 'currency' => 'BDT', 'unit' => 'SMS', 'updated_at' => now()->toDateTimeString()];
    }

    protected function getMimSmsBalance(): array
    {
        // MimSMS: No official public balance endpoint; check portal
        return ['balance' => 'N/A', 'message' => 'Balance check via portal: mimsms.com', 'currency' => 'BDT', 'updated_at' => now()->toDateTimeString()];
    }

    protected function getAdnSmsBalance(): array
    {
        // ADN SMS balance: POST https://portal.adnsms.com/api/v1/secure/check-balance
        // Params: api_key, api_secret
        $response = Http::timeout(15)
            ->withHeaders(['Accept' => 'application/json'])
            ->post('https://portal.adnsms.com/api/v1/secure/check-balance', [
                'api_key'    => $this->apiKey,
                'api_secret' => SystemSetting::get('sms_adn_secret_key', ''),
            ]);
        $data = $response->json();
        $balance = $data['balance']['sms'] ?? $data['balance'] ?? null;
        return ['balance' => $balance, 'currency' => 'SMS', 'unit' => 'SMS', 'raw_response' => $data, 'updated_at' => now()->toDateTimeString()];
    }

    protected function getMuthofunBalance(): array
    {
        // Muthofun/Muthobarta balance: GET https://clients.muthobarta.com/get-balance
        // Auth: Authorization: Token {api_key}
        $response = Http::timeout(15)
            ->withHeaders(['Authorization' => 'Token ' . $this->apiKey])
            ->get('https://clients.muthobarta.com/get-balance');
        $data = $response->json();
        $balance = $data['balance'] ?? null;
        return ['balance' => $balance, 'currency' => 'BDT', 'unit' => 'SMS', 'raw_response' => $data, 'updated_at' => now()->toDateTimeString()];
    }

    protected function getCustomApiBalance(): array
    {
        $balanceUrl = $this->customApiConfig['balance_url'] ?? '';
        $balanceField = $this->customApiConfig['balance_field'] ?? 'balance';
        
        if (empty($balanceUrl)) {
            return [
                'balance' => 'N/A',
                'message' => 'Balance URL not configured. Set it in Custom API settings.',
                'updated_at' => now()->toDateTimeString()
            ];
        }
        
        if (!filter_var($balanceUrl, FILTER_VALIDATE_URL)) {
            return [
                'balance' => 'N/A',
                'error' => 'Invalid Balance URL format',
                'updated_at' => now()->toDateTimeString()
            ];
        }
        
        try {
            $apiKeyParam = $this->customApiConfig['api_key_param'] ?? 'apikey';
            
            $finalUrl = str_replace(['{apikey}', '{api_key}', '{API_KEY}', '{apiKey}'], $this->apiKey, $balanceUrl);
            
            if ($finalUrl === $balanceUrl) {
                $response = Http::timeout(15)->get($balanceUrl, [
                    $apiKeyParam => $this->apiKey,
                ]);
            } else {
                $response = Http::timeout(15)->get($finalUrl);
            }
            
            $body = $response->body();
            
            if (str_starts_with(trim($body), '<') || str_contains($body, '<!DOCTYPE') || str_contains($body, '<html')) {
                return [
                    'balance' => 'Check in Portal',
                    'message' => 'Balance API returned HTML. Please check URL format.',
                    'updated_at' => now()->toDateTimeString()
                ];
            }
            
            if (is_numeric(trim($body))) {
                return [
                    'balance' => floatval(trim($body)),
                    'currency' => 'BDT',
                    'updated_at' => now()->toDateTimeString()
                ];
            }
            
            $result = $response->json();
            
            if (!$result) {
                return [
                    'balance' => 'Check in Portal',
                    'message' => 'Could not parse response. Check balance URL.',
                    'updated_at' => now()->toDateTimeString()
                ];
            }
            
            $balance = $this->extractBalanceFromResponse($result, $balanceField);
            
            return [
                'balance' => $balance,
                'currency' => 'BDT',
                'raw_response' => $result,
                'updated_at' => now()->toDateTimeString()
            ];
        } catch (\Exception $e) {
            Log::error('Custom API Balance Check Error: ' . $e->getMessage());
            return [
                'balance' => 'Check in Portal',
                'message' => 'Balance check failed: ' . $e->getMessage(),
                'updated_at' => now()->toDateTimeString()
            ];
        }
    }
    
    protected function extractBalanceFromResponse(array $data, string $field): mixed
    {
        if (str_contains($field, '.')) {
            $parts = explode('.', $field);
            $current = $data;
            foreach ($parts as $part) {
                if (is_array($current) && isset($current[$part])) {
                    $current = $current[$part];
                } else {
                    break;
                }
            }
            if (!is_array($current)) {
                return $current;
            }
        }
        
        if (isset($data[$field])) {
            return $data[$field];
        }
        
        $lowerField = strtolower($field);
        foreach ($data as $key => $value) {
            if (strtolower($key) === $lowerField) {
                return $value;
            }
        }
        
        $commonFields = [
            'balance', 'Balance', 'BALANCE',
            'current_balance', 'currentBalance', 'CurrentBalance',
            'credit', 'Credit', 'credits', 'Credits',
            'sms_balance', 'smsBalance', 'SmsBalance',
            'remaining', 'Remaining', 'amount', 'Amount',
            'available_balance', 'availableBalance'
        ];
        
        foreach ($commonFields as $commonField) {
            if (isset($data[$commonField])) {
                return $data[$commonField];
            }
        }
        
        if (isset($data['data']) && is_array($data['data'])) {
            foreach ($commonFields as $commonField) {
                if (isset($data['data'][$commonField])) {
                    return $data['data'][$commonField];
                }
            }
            return $this->extractBalanceFromResponse($data['data'], $field);
        }
        
        if (isset($data['result']) && is_array($data['result'])) {
            foreach ($commonFields as $commonField) {
                if (isset($data['result'][$commonField])) {
                    return $data['result'][$commonField];
                }
            }
        }
        
        if (isset($data['response']) && is_array($data['response'])) {
            foreach ($commonFields as $commonField) {
                if (isset($data['response'][$commonField])) {
                    return $data['response'][$commonField];
                }
            }
        }
        
        return 'N/A';
    }

    protected function clearBalanceCache(): void
    {
        $cacheKey = 'sms_balance_' . md5($this->apiKey . $this->provider);
        Cache::forget($cacheKey);
    }

    protected function formatPhoneNumber(string $phone): string
    {
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        
        if (strlen($phone) === 11 && str_starts_with($phone, '0')) {
            $phone = '88' . $phone;
        }
        
        if (strlen($phone) === 10 && !str_starts_with($phone, '0')) {
            $phone = '880' . $phone;
        }
        
        return $phone;
    }

    public function sendNewClientCredentials(Client $client, ?string $password = null, bool $sendSms = true): ?SmsLog
    {
        if (!$client->phone) {
            return null;
        }

        $companyName = SystemSetting::get('company_name', 'SOFTYNEX');
        $supportNumber = SystemSetting::get('support_phone', '');

        $variables = [
            'username' => $client->username ?? $client->client_id ?? 'N/A',
            'password' => $password ?? $client->pppoe_password ?? 'Contact support',
            'client_id' => $client->client_id ?? '',
            'package_name' => $client->package?->name ?? 'N/A',
            'company_name' => $companyName,
            'support_number' => $supportNumber,
            'monthly_fee' => number_format($client->monthly_fee, 2),
        ];

        $template = $this->resolveTemplate('new_client_welcome') ?? $this->resolveTemplate('welcome_message');
        
        $message = $template
            ? $this->parseTemplate($template->content, $client, $variables)
            : "Welcome to {$companyName}! Dear {$client->name}, your account is active. Username: {$variables['username']}, Password: {$variables['password']}. Package: {$variables['package_name']}. Monthly Fee: BDT {$variables['monthly_fee']}.";

        return $this->sendWithPermissionCheck(
            $client->phone,
            $message,
            'new_client_welcome',
            self::RECIPIENT_CLIENT,
            $client->id,
            auth()->id(),
            $sendSms
        );
    }

    public function sendApplicationSubmitted($application, bool $sendSms = true): ?SmsLog
    {
        $template = $this->resolveTemplate('application_submitted');

        $phone = $application->phone ?? $application->mobile ?? null;
        if (!$phone) {
            return null;
        }

        $name = $application->name ?? $application->applicant_name ?? 'Applicant';
        $companyName = config('app.name', 'ISP');

        if ($template) {
            $message = str_replace(
                ['{client_name}', '{company_name}', '{status}'],
                [$name, $companyName, 'Submitted'],
                $template->content
            );
        } else {
            $message = "Dear {$name}, your new connection application has been submitted successfully. We will review it shortly. - {$companyName}";
        }

        return $this->sendWithPermissionCheck(
            $phone,
            $message,
            'application_submitted',
            self::RECIPIENT_CLIENT,
            null,
            auth()->id(),
            $sendSms
        );
    }

    public function sendRejectionNotification($application, bool $sendSms = true): ?SmsLog
    {
        $template = $this->resolveTemplate('application_rejected');

        $phone = $application->phone ?? $application->mobile ?? null;
        if (!$phone) {
            return null;
        }

        $name = $application->name ?? $application->applicant_name ?? 'Applicant';
        $companyName = config('app.name', 'ISP');
        $reason = $application->rejection_reason ?? '';

        if ($template) {
            $message = str_replace(
                ['{client_name}', '{company_name}', '{reason}', '{status}'],
                [$name, $companyName, $reason, 'Rejected'],
                $template->content
            );
        } else {
            $message = "Dear {$name}, your new connection application has been rejected. Reason: {$reason}. For more info, please contact us. - {$companyName}";
        }

        return $this->sendWithPermissionCheck(
            $phone,
            $message,
            'application_rejected',
            self::RECIPIENT_CLIENT,
            null,
            auth()->id(),
            $sendSms
        );
    }

    public function sendApplicationStatusUpdate(Client $client, string $status, bool $sendSms = true): ?SmsLog
    {
        $template = $this->resolveTemplate('application_status');
        
        if (!$template) {
            return null;
        }
        
        $message = $this->parseTemplate($template->content, $client, [
            'status' => $status
        ]);
        
        return $this->sendWithPermissionCheck(
            $client->phone,
            $message,
            'application_status',
            self::RECIPIENT_CLIENT,
            $client->id,
            auth()->id(),
            $sendSms
        );
    }

    public function sendApplicationCorrectionRequest(Client $client, string $corrections, bool $sendSms = true): ?SmsLog
    {
        $template = $this->resolveTemplate('application_correction');
        
        if (!$template) {
            return null;
        }
        
        $message = $this->parseTemplate($template->content, $client, [
            'corrections' => $corrections
        ]);
        
        return $this->sendWithPermissionCheck(
            $client->phone,
            $message,
            'application_correction',
            self::RECIPIENT_CLIENT,
            $client->id,
            auth()->id(),
            $sendSms
        );
    }

    public function sendApprovalNotification($application, Client $client, array $mikrotikResult = [], bool $sendSms = true): ?SmsLog
    {
        $template = $this->resolveTemplate('application_approved');
        
        $username = $mikrotikResult['username'] ?? $client->username ?? 'N/A';
        $password = $mikrotikResult['password'] ?? 'Contact support';
        
        if ($template) {
            $message = $this->parseTemplate($template->content, $client, [
                'username' => $username,
                'password' => $password,
                'client_id' => $client->client_id,
                'package' => $client->package->name ?? 'N/A',
            ]);
        } else {
            $message = "Dear {$client->name}, your connection has been approved! Client ID: {$client->client_id}. Username: {$username}. Thank you for choosing us.";
        }
        
        return $this->sendWithPermissionCheck(
            $client->phone,
            $message,
            'application_approved',
            self::RECIPIENT_CLIENT,
            $client->id,
            auth()->id(),
            $sendSms
        );
    }

public function sendServiceRestoration($client)
{
    try {
        $message = "Your service has been restored successfully.";
        return $this->sendSms($client->phone, $message, 'service_restoration', $client->id);
    } catch (\Exception $e) {
        \Log::error('Service Restoration SMS Failed: ' . $e->getMessage());
        return false;
    }
}

public function sendExpiryNotification(Client $client): ?SmsLog
{
    if (!$client->phone) {
        return null;
    }

    $companyName = SystemSetting::get('company_name', 'SOFTYNEX');
    $expiryDate = $client->expire_date ? $client->expire_date->format('d-m-Y') : 'N/A';

    $message = "Dear {$client->name}, your internet service has expired on {$expiryDate}. Please renew to restore service. - {$companyName}";

    return $this->sendWithPermissionCheck(
        $client->phone,
        $message,
        self::EVENT_SERVICE_SUSPENSION,
        self::RECIPIENT_CLIENT,
        $client->id
    );
}

public function sendReactivationNotification(Client $client): ?SmsLog
{
    if (!$client->phone) {
        return null;
    }

    $companyName = SystemSetting::get('company_name', 'SOFTYNEX');

    $message = "Dear {$client->name}, your service has been reactivated successfully. Thank you for your payment. - {$companyName}";

    return $this->sendWithPermissionCheck(
        $client->phone,
        $message,
        self::EVENT_CUSTOM,
        self::RECIPIENT_CLIENT,
        $client->id
    );
}

    /**
     * Send due reminder notice (3-day or 1-day before auto-inactive date).
     */
    public function sendDueReminderNotice(Client $client, int $daysLeft): ?SmsLog
    {
        if (!$client->phone) {
            return null;
        }

        $dueAmount = (float)($client->due_amount ?? 0);
        if ($dueAmount <= 0) {
            return null;
        }

        $eventType   = $daysLeft <= 1 ? self::EVENT_DUE_REMINDER_1DAY : self::EVENT_DUE_REMINDER_3DAY;
        $settingKey  = $daysLeft <= 1 ? 'sms_template_due_reminder_1day' : 'sms_template_due_reminder_3day';
        $companyName = SystemSetting::get('company_name', 'SOFTYNEX');

        // Calculate auto-inactive date for this client
        $today = \Carbon\Carbon::today('Asia/Dhaka');
        $aiDay = (int)min($client->payment_deadline ?? 15, 28);
        if ($client->temp_deadline_date) {
            $aiDate = \Carbon\Carbon::parse($client->temp_deadline_date, 'Asia/Dhaka');
        } else {
            $aiDate = $today->day <= $aiDay
                ? \Carbon\Carbon::create($today->year, $today->month, $aiDay, 0, 0, 0, 'Asia/Dhaka')
                : \Carbon\Carbon::create($today->year, $today->month, $aiDay, 0, 0, 0, 'Asia/Dhaka')->addMonthNoOverflow();
        }

        $defaultTemplate = $daysLeft <= 1
            ? 'Dear {name}, your internet bill Tk {due_amount} is still unpaid. Your line will be auto-inactive TOMORROW ({auto_inactive_date}). Please pay now. - {company}'
            : 'Dear {name}, your internet bill Tk {due_amount} is due. Your line will be auto-inactive on {auto_inactive_date} ({days_left} days). Pay to avoid disconnection. - {company}';

        $rawTemplate = SystemSetting::get($settingKey, $defaultTemplate);

        $message = str_replace(
            ['{name}', '{due_amount}', '{days_left}', '{auto_inactive_date}', '{company}', '{client_id}'],
            [$client->name, number_format($dueAmount, 0), $daysLeft, $aiDate->format('d-m-Y'), $companyName, $client->client_id],
            $rawTemplate
        );

        return $this->sendWithPermissionCheck(
            $client->phone,
            $message,
            $eventType,
            self::RECIPIENT_CLIENT,
            $client->id,
            null,
            true
        );
    }

    /**
     * Send auto-inactive notice when a client's line is disabled due to non-payment.
     */
    public function sendAutoInactiveNotice(Client $client): ?SmsLog
    {
        if (!$client->phone) {
            return null;
        }

        $companyName = SystemSetting::get('company_name', 'SOFTYNEX');
        $dueAmount   = (float)($client->due_amount ?? 0);

        $defaultTemplate = 'Dear {name}, your internet line has been auto-inactivated due to unpaid bill of Tk {due_amount}. Please pay to restore service. - {company}';
        $rawTemplate = SystemSetting::get('sms_template_auto_inactive', $defaultTemplate);

        $message = str_replace(
            ['{name}', '{due_amount}', '{company}', '{client_id}'],
            [$client->name, number_format($dueAmount, 0), $companyName, $client->client_id],
            $rawTemplate
        );

        return $this->sendWithPermissionCheck(
            $client->phone,
            $message,
            self::EVENT_AUTO_INACTIVE,
            self::RECIPIENT_CLIENT,
            $client->id,
            null,
            true
        );
    }


}