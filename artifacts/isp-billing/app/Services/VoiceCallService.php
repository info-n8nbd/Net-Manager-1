<?php

namespace App\Services;

use App\Models\VoiceCallLog;
use App\Models\VoiceCallSetting;
use Twilio\Rest\Client as TwilioClient;

class VoiceCallService
{
    public function getSettings(): ?VoiceCallSetting
    {
        return VoiceCallSetting::first();
    }

    public function makeCall(
        string  $phone,
        string  $message  = '',
        string  $callType = 'custom',
        ?int    $clientId = null,
        string  $language = 'en-US',
        ?string $audioUrl = null
    ): VoiceCallLog {
        $settings = $this->getSettings();

        $log = VoiceCallLog::create([
            'phone'        => $phone,
            'client_id'    => $clientId,
            'call_type'    => $callType,
            'message_text' => $audioUrl ? '[audio:' . $audioUrl . ']' : $message,
            'provider'     => $settings->provider ?? 'twilio',
            'status'       => 'queued',
        ]);

        if ($settings && $settings->is_active) {
            try {
                $response = $this->initiateProviderCall($settings, $phone, $message, $language, $audioUrl, $log->id);
                $log->update([
                    'status'            => 'initiated',
                    'provider_call_id'  => $response['call_id'] ?? null,
                    'provider_response' => $response,
                ]);
            } catch (\Exception $e) {
                $log->update([
                    'status'            => 'failed',
                    'provider_response' => ['error' => $e->getMessage()],
                ]);
            }
        } else {
            $log->update([
                'status'            => 'skipped',
                'provider_response' => ['reason' => 'Voice call not active or not configured'],
            ]);
        }

        return $log;
    }

    protected function initiateProviderCall(
        VoiceCallSetting $settings,
        string           $phone,
        string           $message,
        string           $language = 'en-US',
        ?string          $audioUrl = null,
        ?int             $logId    = null
    ): array {
        if ($settings->provider === 'twilio') {
            $creds = $settings->credentials ?? [];
            $sid   = $creds['account_sid'] ?? env('TWILIO_ACCOUNT_SID');
            $token = $creds['auth_token']   ?? env('TWILIO_AUTH_TOKEN');
            $from  = $settings->from_number ?? env('TWILIO_PHONE_NUMBER');

            return $this->twilioCall($sid, $token, $from, $phone, $message, $language, $audioUrl, $logId);
        }
        return ['status' => 'unsupported_provider'];
    }

    protected function twilioCall(
        string  $sid,
        string  $token,
        string  $from,
        string  $to,
        string  $message,
        string  $language = 'en-US',
        ?string $audioUrl = null,
        ?int    $logId    = null
    ): array {
        if (empty($sid) || empty($token) || empty($from)) {
            throw new \Exception('Twilio credentials not configured. Set TWILIO_ACCOUNT_SID, TWILIO_AUTH_TOKEN, TWILIO_PHONE_NUMBER.');
        }

        $to = $this->normalizePhone($to);

        $client = new TwilioClient($sid, $token);

        if ($audioUrl) {
            $twiml = '<Response><Play>' . htmlspecialchars($audioUrl, ENT_XML1) . '</Play></Response>';
        } elseif (in_array($language, ['bn-BD', 'bn-IN', 'bn'])) {
            $audioUrls = $this->generateBanglaTTSChunks($message);
            $plays = '';
            foreach ($audioUrls as $url) {
                $plays .= '<Play loop="2">' . htmlspecialchars($url, ENT_XML1) . '</Play><Pause length="1"/>';
            }
            $twiml = '<Response>' . $plays . '</Response>';
        } else {
            $safeMessage = htmlspecialchars($message, ENT_XML1);
            $twiml = '<Response><Say language="en-US" voice="alice">' . $safeMessage . '</Say></Response>';
        }

        $statusCallback = rtrim(config('app.url'), '/') . '/webhook/voice/status';

        $call = $client->calls->create($to, $from, [
            'twiml'                => $twiml,
            'statusCallback'       => $statusCallback,
            'statusCallbackMethod' => 'POST',
            'statusCallbackEvent'  => ['completed', 'failed', 'busy', 'no-answer'],
        ]);

        return [
            'status'   => 'initiated',
            'call_id'  => $call->sid,
            'provider' => 'twilio',
            'from'     => $from,
            'to'       => $to,
            'language' => $language,
        ];
    }

    protected function generateBanglaTTSChunks(string $text): array
    {
        $dir = storage_path('app/public/voice-audio');
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $sentences = $this->splitBySentence($text);
        $urls      = [];

        foreach ($sentences as $sentence) {
            $sentence = trim($sentence);
            if (empty($sentence)) continue;

            $hash     = md5($sentence);
            $mp3File  = 'bn_' . $hash . '.mp3';
            $wavFile  = 'bn_' . $hash . '.wav';
            $mp3Path  = $dir . '/' . $mp3File;
            $wavPath  = $dir . '/' . $wavFile;

            if (!file_exists($mp3Path) || filesize($mp3Path) < 500) {
                $ttsUrl = 'https://translate.google.com/translate_tts?ie=UTF-8&q='
                    . urlencode($sentence)
                    . '&tl=bn&client=tw-ob&ttsspeed=0.8';

                $ctx = stream_context_create(['http' => [
                    'header'  => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36\r\n",
                    'timeout' => 20,
                ]]);

                $data = @file_get_contents($ttsUrl, false, $ctx);

                if (!$data || strlen($data) < 500) {
                    throw new \Exception('Bangla TTS generation failed. Please try uploading a pre-recorded audio file.');
                }

                file_put_contents($mp3Path, $data);
            }

            if (!file_exists($wavPath) || filesize($wavPath) < 1000) {
                $ffmpeg = trim(shell_exec('which ffmpeg 2>/dev/null') ?: '');
                if ($ffmpeg) {
                    shell_exec(escapeshellarg($ffmpeg)
                        . ' -y -i ' . escapeshellarg($mp3Path)
                        . ' -ar 8000 -ac 1 -acodec pcm_mulaw '
                        . escapeshellarg($wavPath)
                        . ' 2>/dev/null');
                }
            }

            $serveFile = (file_exists($wavPath) && filesize($wavPath) > 1000) ? $wavFile : $mp3File;
            $urls[] = rtrim(config('app.url'), '/') . '/voice-audio/' . $serveFile;
        }

        if (empty($urls)) {
            throw new \Exception('No audio generated for the given text.');
        }

        return $urls;
    }

    protected function splitBySentence(string $text): array
    {
        $sentences = preg_split('/(?<=[।.!?])\s*/u', $text, -1, PREG_SPLIT_NO_EMPTY);

        $result = [];
        foreach ($sentences as $s) {
            $s = trim($s);
            if ($s === '') continue;
            if (mb_strlen($s) <= 150) {
                $result[] = $s;
            } else {
                $parts = preg_split('/([,،;])\s*/u', $s, -1, PREG_SPLIT_NO_EMPTY);
                $current = '';
                foreach ($parts as $part) {
                    if (mb_strlen($current . $part) > 150 && $current !== '') {
                        $result[] = trim($current);
                        $current = $part;
                    } else {
                        $current .= $part . ', ';
                    }
                }
                if (trim($current) !== '') {
                    $result[] = trim(rtrim($current, ', '));
                }
            }
        }

        return $result ?: [$text];
    }

    protected function normalizePhone(string $phone): string
    {
        $phone  = trim($phone);
        $digits = preg_replace('/\D/', '', $phone);

        if (strlen($digits) === 11 && str_starts_with($digits, '0')) {
            $digits = '880' . substr($digits, 1);
        }

        if (str_starts_with($phone, '+')) {
            return '+' . $digits;
        }

        return '+' . $digits;
    }

    public function sendBillReminder(int $clientId, string $language = 'en-US'): ?VoiceCallLog
    {
        $client = \App\Models\Client::find($clientId);
        if (!$client || empty($client->phone)) return null;

        $messages = [
            'bn-BD' => 'Dear customer, this is a reminder from SOFTYNEX. Your internet bill payment is due. Please pay your outstanding balance to avoid service interruption. Thank you.',
            'en-US' => 'Dear customer, this is a reminder from SOFTYNEX. Your internet bill payment is due. Please pay your outstanding balance to avoid service interruption. Thank you.',
        ];

        $message = $messages[$language] ?? $messages['en-US'];

        return $this->makeCall($client->phone, $message, 'bill_reminder', $clientId, $language);
    }

    public function sendExpiryWarning(int $clientId, string $language = 'en-US'): ?VoiceCallLog
    {
        $client = \App\Models\Client::find($clientId);
        if (!$client || empty($client->phone)) return null;

        $messages = [
            'bn-BD' => 'Dear customer, this is SOFTYNEX. Your internet package is about to expire. Please renew your package to continue enjoying uninterrupted service. Thank you.',
            'en-US' => 'Dear customer, this is SOFTYNEX. Your internet package is about to expire. Please renew your package to continue enjoying uninterrupted service. Thank you.',
        ];

        $message = $messages[$language] ?? $messages['en-US'];

        return $this->makeCall($client->phone, $message, 'expiry_warning', $clientId, $language);
    }

    public function sendLineDisconnected(int $clientId, string $language = 'en-US'): ?VoiceCallLog
    {
        $client = \App\Models\Client::find($clientId);
        if (!$client || empty($client->phone)) return null;

        $messages = [
            'bn-BD' => 'Dear customer, your internet connection has been disconnected due to an outstanding bill. Please pay your bill to restore your service. Thank you.',
            'en-US' => 'Dear customer, your internet connection has been disconnected due to an outstanding bill. Please pay your bill to restore your service. Thank you.',
        ];

        $message = $messages[$language] ?? $messages['en-US'];

        return $this->makeCall($client->phone, $message, 'announcement', $clientId, $language);
    }

    public function sendPaymentConfirmation(int $clientId, float $amount, string $language = 'en-US'): ?VoiceCallLog
    {
        $client = \App\Models\Client::find($clientId);
        if (!$client || empty($client->phone)) return null;

        $formatted = number_format($amount);

        $messages = [
            'bn-BD' => "Dear customer, thank you for your payment of {$formatted} taka to SOFTYNEX. Your service has been activated. Thank you.",
            'en-US' => "Dear customer, thank you for your payment of {$formatted} taka to SOFTYNEX. Your service has been activated. Thank you.",
        ];

        $message = $messages[$language] ?? $messages['en-US'];

        return $this->makeCall($client->phone, $message, 'payment_confirmation', $clientId, $language);
    }

    public function getCallHistory(array $filters = [])
    {
        $query = VoiceCallLog::with('client');
        if (!empty($filters['client_id'])) $query->where('client_id', $filters['client_id']);
        if (!empty($filters['call_type'])) $query->where('call_type', $filters['call_type']);
        if (!empty($filters['status']))    $query->where('status', $filters['status']);
        if (!empty($filters['date_from'])) $query->whereDate('created_at', '>=', $filters['date_from']);
        if (!empty($filters['date_to']))   $query->whereDate('created_at', '<=', $filters['date_to']);
        return $query->orderBy('created_at', 'desc')->paginate(25);
    }
}
