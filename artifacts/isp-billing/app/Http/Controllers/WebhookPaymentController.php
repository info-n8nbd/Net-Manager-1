<?php

namespace App\Http\Controllers;

use App\Services\WebhookPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookPaymentController extends Controller
{
    protected WebhookPaymentService $webhookService;

    public function __construct(WebhookPaymentService $webhookService)
    {
        $this->webhookService = $webhookService;
    }

    public function handleBkash(Request $request)
    {
        return $this->processWebhook('bkash', $request);
    }

    public function handleNagad(Request $request)
    {
        return $this->processWebhook('nagad', $request);
    }

    public function handleRocket(Request $request)
    {
        return $this->processWebhook('rocket', $request);
    }

    public function handleGeneric(Request $request, string $gateway)
    {
        $allowed = ['bkash', 'nagad', 'rocket'];
        if (!in_array($gateway, $allowed)) {
            return response()->json(['success' => false, 'message' => 'Unknown gateway'], 400);
        }
        return $this->processWebhook($gateway, $request);
    }

    protected function processWebhook(string $gateway, Request $request)
    {
        Log::info("Webhook received from {$gateway}", [
            'ip' => $request->ip(),
            'payload_keys' => array_keys($request->all()),
        ]);

        if (!$this->webhookService->isWebhookEnabled($gateway)) {
            Log::warning("Webhook disabled for {$gateway}");
            return response()->json(['success' => false, 'message' => 'Webhook not enabled'], 403);
        }

        $secret = $request->header('X-Webhook-Secret')
            ?? $request->header('Authorization')
            ?? $request->input('secret');

        if (!$this->webhookService->verifyWebhookSecret($gateway, $secret)) {
            Log::warning("Invalid webhook secret for {$gateway}", ['ip' => $request->ip()]);
            return response()->json(['success' => false, 'message' => 'Invalid secret'], 401);
        }

        $result = $this->webhookService->processWebhookPayment(
            $gateway,
            $request->all(),
            $request->ip()
        );

        $httpCode = $result['success'] ? 200 : 422;
        return response()->json($result, $httpCode);
    }
}
