<?php

namespace App\Services;

use App\Models\WhatsappLog;
use App\Models\WhatsappSetting;
use App\Models\WhatsappTemplate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected ?WhatsappSetting $settings;

    public function __construct()
    {
        $this->settings = WhatsappSetting::getActive();
    }

    public function isConfigured(): bool
    {
        return $this->settings !== null && $this->settings->is_active;
    }

    public function send(string $phone, string $message, ?int $clientId = null, ?string $templateCode = null): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'WhatsApp not configured'];
        }

        if (!$this->settings->canSend()) {
            return ['success' => false, 'error' => 'Daily sending limit reached'];
        }

        $phone = $this->formatPhone($phone);

        $log = WhatsappLog::create([
            'client_id' => $clientId,
            'phone' => $phone,
            'template_code' => $templateCode,
            'message' => $message,
            'status' => 'pending',
        ]);

        try {
            $result = $this->sendViaProvider($phone, $message, $templateCode);

            $log->update([
                'status' => $result['success'] ? 'sent' : 'failed',
                'message_id' => $result['message_id'] ?? null,
                'error_message' => $result['error'] ?? null,
                'cost' => $result['cost'] ?? 0,
            ]);

            if ($result['success']) {
                $this->settings->incrementSentCount();
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('WhatsApp send error: ' . $e->getMessage());
            $log->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function sendTemplate(string $phone, string $templateCode, array $variables = [], ?int $clientId = null): array
    {
        $template = WhatsappTemplate::where('code', $templateCode)->where('is_active', true)->first();

        if (!$template) {
            return ['success' => false, 'error' => 'Template not found: ' . $templateCode];
        }

        $message = $template->renderContent($variables);
        return $this->send($phone, $message, $clientId, $templateCode);
    }

    public function sendBulk(array $recipients, string $message, ?string $templateCode = null): array
    {
        $results = ['sent' => 0, 'failed' => 0, 'errors' => []];

        foreach ($recipients as $recipient) {
            $phone = $recipient['phone'] ?? $recipient;
            $clientId = $recipient['client_id'] ?? null;

            $result = $this->send($phone, $message, $clientId, $templateCode);

            if ($result['success']) {
                $results['sent']++;
            } else {
                $results['failed']++;
                $results['errors'][] = ['phone' => $phone, 'error' => $result['error']];
            }

            usleep(100000); // 100ms delay between messages
        }

        return $results;
    }

    protected function sendViaProvider(string $phone, string $message, ?string $templateCode = null): array
    {
        return match ($this->settings->provider) {
            'whatsapp_business' => $this->sendViaWhatsAppBusiness($phone, $message),
            'twilio' => $this->sendViaTwilio($phone, $message),
            '360dialog' => $this->sendVia360Dialog($phone, $message),
            'wati' => $this->sendViaWati($phone, $message),
            default => ['success' => false, 'error' => 'Unknown provider: ' . $this->settings->provider],
        };
    }

    protected function sendViaWhatsAppBusiness(string $phone, string $message): array
    {
        $url = $this->settings->api_url ?: "https://graph.facebook.com/v18.0/{$this->settings->phone_number_id}/messages";

        $response = Http::withToken($this->settings->access_token)
            ->post($url, [
                'messaging_product' => 'whatsapp',
                'to' => $phone,
                'type' => 'text',
                'text' => ['body' => $message],
            ]);

        if ($response->successful()) {
            $data = $response->json();
            return [
                'success' => true,
                'message_id' => $data['messages'][0]['id'] ?? null,
                'cost' => 0.05,
            ];
        }

        return [
            'success' => false,
            'error' => $response->json('error.message', 'API error: ' . $response->status()),
        ];
    }

    protected function sendViaTwilio(string $phone, string $message): array
    {
        $config = $this->settings->config ?? [];
        $sid = $config['account_sid'] ?? '';
        $token = $config['auth_token'] ?? '';
        $fromNumber = $config['from_number'] ?? $this->settings->phone_number_id;

        $response = Http::withBasicAuth($sid, $token)
            ->asForm()
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json", [
                'From' => "whatsapp:{$fromNumber}",
                'To' => "whatsapp:{$phone}",
                'Body' => $message,
            ]);

        if ($response->successful()) {
            return [
                'success' => true,
                'message_id' => $response->json('sid'),
                'cost' => 0.05,
            ];
        }

        return [
            'success' => false,
            'error' => $response->json('message', 'Twilio error'),
        ];
    }

    protected function sendVia360Dialog(string $phone, string $message): array
    {
        $response = Http::withHeaders([
            'D360-API-KEY' => $this->settings->api_key,
        ])->post($this->settings->api_url ?: 'https://waba.360dialog.io/v1/messages', [
            'to' => $phone,
            'type' => 'text',
            'text' => ['body' => $message],
        ]);

        if ($response->successful()) {
            return [
                'success' => true,
                'message_id' => $response->json('messages.0.id'),
                'cost' => 0.04,
            ];
        }

        return ['success' => false, 'error' => $response->json('meta.developer_message', 'API error')];
    }

    protected function sendViaWati(string $phone, string $message): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->settings->access_token,
        ])->post(($this->settings->api_url ?: 'https://live-server.wati.io') . '/api/v1/sendSessionMessage/' . $phone, [
            'messageText' => $message,
        ]);

        if ($response->successful() && $response->json('result')) {
            return [
                'success' => true,
                'message_id' => $response->json('info'),
                'cost' => 0.05,
            ];
        }

        return ['success' => false, 'error' => $response->json('info', 'WATI API error')];
    }

    protected function formatPhone(string $phone): string
    {
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        if (str_starts_with($phone, '01') && strlen($phone) === 11) {
            $phone = '880' . $phone;
        } elseif (str_starts_with($phone, '+880')) {
            $phone = substr($phone, 1);
        }
        return $phone;
    }

    public function getBalance(): array
    {
        if (!$this->isConfigured()) {
            return ['remaining' => 0, 'used' => 0, 'limit' => 0];
        }

        return [
            'remaining' => $this->settings->daily_limit - $this->settings->sent_today,
            'used' => $this->settings->sent_today,
            'limit' => $this->settings->daily_limit,
        ];
    }
}
