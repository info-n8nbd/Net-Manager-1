<?php

namespace App\Http\Controllers\Reseller;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Reseller;
use App\Models\ResellerSmsLog;
use App\Models\ResellerSmsSetting;
use App\Models\ResellerSmsTemplate;
use App\Services\ResellerSmsService;
use App\Services\SmsService;
use Illuminate\Http\Request;

class ResellerSmsController extends Controller
{
    protected function getReseller(): Reseller
    {
        $user = auth()->user();
        $reseller = Reseller::where('user_id', $user->id)->first();
        if (!$reseller) abort(403, 'Reseller not found');
        return $reseller;
    }

    // ─── Settings ─────────────────────────────────────────────────────────────

    public function settings()
    {
        $reseller  = $this->getReseller();
        $setting   = ResellerSmsSetting::forReseller($reseller->id);
        $providers = SmsService::getProviders();
        $service   = new ResellerSmsService($reseller);
        $events    = ResellerSmsService::EVENT_LABELS;
        $apiStatus = $service->getApiStatus();

        $stats = [
            'total_sent'    => ResellerSmsLog::where('reseller_id', $reseller->id)->where('status', 'sent')->count(),
            'total_failed'  => ResellerSmsLog::where('reseller_id', $reseller->id)->where('status', 'failed')->count(),
            'today_sent'    => ResellerSmsLog::where('reseller_id', $reseller->id)->where('status', 'sent')->whereDate('created_at', today())->count(),
            'total_skipped' => ResellerSmsLog::where('reseller_id', $reseller->id)->where('status', 'skipped')->count(),
        ];

        return view('reseller.sms.settings', compact('reseller', 'setting', 'providers', 'service', 'events', 'stats', 'apiStatus'));
    }

    public function saveSettings(Request $request)
    {
        $reseller = $this->getReseller();
        $setting  = ResellerSmsSetting::forReseller($reseller->id);

        $request->validate([
            'provider'  => 'required|string',
            'api_key'   => 'nullable|string|max:500',
            'sender_id' => 'nullable|string|max:100',
        ]);

        $eventSettings = [];
        foreach (array_keys(ResellerSmsService::EVENT_LABELS) as $event) {
            $eventSettings[$event] = $request->boolean("event_{$event}");
        }

        $customApiConfig = [
            'url'             => $request->input('custom_api_url', ''),
            'method'          => $request->input('custom_api_method', 'GET'),
            'phone_param'     => $request->input('custom_phone_param', 'number'),
            'message_param'   => $request->input('custom_message_param', 'message'),
            'api_key_param'   => $request->input('custom_api_key_param', 'apikey'),
            'sender_id_param' => $request->input('custom_sender_id_param', 'senderid'),
            'extra_params'    => $request->input('custom_extra_params', ''),
            'success_response'=> $request->input('custom_success_response', ''),
        ];

        $templates = [];
        foreach (array_keys(ResellerSmsService::EVENT_LABELS) as $event) {
            $templates[$event] = $request->input("template_{$event}", $setting->getTemplate($event));
        }

        $setting->update([
            'is_enabled'       => $request->boolean('is_enabled'),
            'provider'         => $request->input('provider'),
            'api_key'          => $request->input('api_key'),
            'sender_id'        => $request->input('sender_id'),
            'api_url'          => $request->input('api_url', ''),
            'event_settings'   => $eventSettings,
            'custom_api_config'=> $customApiConfig,
            'templates'        => $templates,
        ]);

        return back()->with('success', 'SMS settings saved successfully.');
    }

    public function testSms(Request $request)
    {
        $reseller = $this->getReseller();
        $request->validate([
            'phone'   => 'required|string',
            'message' => 'required|string',
        ]);

        $service = new ResellerSmsService($reseller);
        $result  = $service->testSend($request->phone, $request->message);

        if ($request->expectsJson()) {
            return response()->json([
                'success'  => $result['success'],
                'message'  => $result['success'] ? 'Test SMS sent successfully.' : ($result['message'] ?? $result['response'] ?? 'Unknown error'),
                'error'    => $result['success'] ? null : ($result['message'] ?? $result['response'] ?? 'Unknown error'),
                'response' => $result['response'] ?? null,
            ]);
        }

        return back()->with(
            $result['success'] ? 'success' : 'error',
            $result['success'] ? 'Test SMS sent. Response: ' . ($result['response'] ?? '') : 'SMS send failed: ' . ($result['message'] ?? $result['response'] ?? 'Unknown error')
        );
    }

    public function checkBalance()
    {
        $reseller = $this->getReseller();
        $service  = new ResellerSmsService($reseller);
        $balance  = $service->getBalance();
        return response()->json($balance);
    }

    // ─── Templates ────────────────────────────────────────────────────────────

    public function templates(Request $request)
    {
        $reseller  = $this->getReseller();
        $query     = ResellerSmsTemplate::where('reseller_id', $reseller->id)->orderBy('created_at', 'desc');

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $templates = $query->paginate(20);

        return view('reseller.sms.templates', compact('reseller', 'templates'));
    }

    public function storeTemplate(Request $request)
    {
        $reseller = $this->getReseller();
        $request->validate([
            'name'    => 'required|string|max:100',
            'type'    => 'required|string|max:50',
            'content' => 'required|string|max:2000',
        ]);

        ResellerSmsTemplate::create([
            'reseller_id' => $reseller->id,
            'name'        => $request->name,
            'type'        => $request->type,
            'content'     => $request->content,
        ]);

        return back()->with('success', 'SMS template added successfully.');
    }

    public function updateTemplate(Request $request, ResellerSmsTemplate $template)
    {
        $reseller = $this->getReseller();
        if ($template->reseller_id !== $reseller->id) abort(403);

        $request->validate([
            'name'    => 'required|string|max:100',
            'type'    => 'required|string|max:50',
            'content' => 'required|string|max:2000',
        ]);

        $template->update($request->only('name', 'type', 'content'));

        return back()->with('success', 'Template updated successfully.');
    }

    public function destroyTemplate(ResellerSmsTemplate $template)
    {
        $reseller = $this->getReseller();
        if ($template->reseller_id !== $reseller->id) abort(403);
        $template->delete();
        return back()->with('success', 'Template deleted successfully.');
    }

    // ─── Bulk SMS ─────────────────────────────────────────────────────────────

    public function bulk()
    {
        $reseller  = $this->getReseller();
        $clients   = Client::where('reseller_id', $reseller->id)
            ->where('status', 'active')
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->orderBy('name')
            ->get();
        $events    = ResellerSmsService::EVENT_LABELS;
        $templates = ResellerSmsTemplate::where('reseller_id', $reseller->id)->orderBy('name')->get();

        return view('reseller.sms.bulk', compact('reseller', 'clients', 'events', 'templates'));
    }

    public function sendBulk(Request $request)
    {
        $reseller = $this->getReseller();
        $request->validate([
            'message'    => 'required|string|max:600',
            'client_ids' => 'required|array|min:1',
        ]);

        $service = new ResellerSmsService($reseller);

        if (!$service->isEnabled()) {
            return back()->with('error', 'SMS system is not enabled or API is not configured.');
        }

        $results = $service->sendBulk($request->client_ids, $request->message, 'bulk');

        $sent    = collect($results)->where('status', 'sent')->count();
        $failed  = collect($results)->where('status', 'failed')->count();
        $skipped = collect($results)->where('status', 'skipped')->count();

        $msg = "{$sent} SMS sent successfully.";
        if ($failed)  $msg .= " {$failed} failed.";
        if ($skipped) $msg .= " {$skipped} skipped.";

        return back()->with($failed > $sent ? 'error' : 'success', $msg);
    }

    // ─── Individual SMS ───────────────────────────────────────────────────────

    public function individual()
    {
        $reseller  = $this->getReseller();
        $service   = new ResellerSmsService($reseller);
        $apiStatus = $service->getApiStatus();
        $clients   = Client::where('reseller_id', $reseller->id)->orderBy('name')->get(['id', 'name', 'phone', 'username']);
        $templates = ResellerSmsTemplate::where('reseller_id', $reseller->id)->orderBy('name')->get();

        return view('reseller.sms.individual', compact('reseller', 'apiStatus', 'clients', 'templates'));
    }

    public function sendIndividual(Request $request)
    {
        $reseller = $this->getReseller();
        $request->validate([
            'message' => 'required|string|max:600',
        ]);

        $service = new ResellerSmsService($reseller);
        if (!$service->isEnabled()) {
            return back()->with('error', 'SMS system is not enabled or API is not configured.');
        }

        // Either client or custom number
        if ($request->filled('client_id')) {
            $client = Client::where('id', $request->client_id)->where('reseller_id', $reseller->id)->first();
            if (!$client) return back()->with('error', 'Client not found.');
            if (empty($client->phone)) return back()->with('error', 'This client has no phone number.');

            $log = $service->sendRaw($client, $client->phone, $request->message, 'individual');
            return back()->with($log->status === 'sent' ? 'success' : 'error',
                $log->status === 'sent' ? 'SMS sent successfully.' : 'SMS send failed: ' . $log->response
            );
        }

        if ($request->filled('custom_phone')) {
            // Send to custom number (no client record) — use unsaved model so client_id is null in log
            $fakeClient = new Client();
            $fakeClient->name  = 'Custom';
            $fakeClient->phone = $request->custom_phone;
            $log = $service->sendRaw($fakeClient, $request->custom_phone, $request->message, 'individual');
            return back()->with($log->status === 'sent' ? 'success' : 'error',
                $log->status === 'sent' ? 'SMS sent to ' . $request->custom_phone . '.' : 'SMS failed: ' . $log->response
            );
        }

        return back()->with('error', 'Please provide a client or phone number.');
    }

    // ─── Auto Process ─────────────────────────────────────────────────────────

    public function autoProcess()
    {
        $reseller = $this->getReseller();
        $setting  = ResellerSmsSetting::forReseller($reseller->id);
        $autoSettings = $setting->auto_process_settings ?? [];

        return view('reseller.sms.auto-process', compact('reseller', 'setting', 'autoSettings'));
    }

    public function saveAutoProcess(Request $request)
    {
        $reseller = $this->getReseller();
        $setting  = ResellerSmsSetting::forReseller($reseller->id);

        $autoSettings = [
            'bill_generation'   => $request->boolean('auto_bill_generation'),
            'payment_received'  => $request->boolean('auto_payment_received'),
            'line_activated'    => $request->boolean('auto_line_activated'),
            'line_deactivated'  => $request->boolean('auto_line_deactivated'),
            'reminder_3day'     => $request->boolean('auto_reminder_3day'),
            'reminder_1day'     => $request->boolean('auto_reminder_1day'),
            'reminder_deadline' => $request->boolean('auto_reminder_deadline'),
        ];

        $setting->update(['auto_process_settings' => $autoSettings]);

        return back()->with('success', 'Auto Process settings saved successfully.');
    }

    // ─── Logs ─────────────────────────────────────────────────────────────────

    public function logs(Request $request)
    {
        $reseller = $this->getReseller();
        $query    = ResellerSmsLog::where('reseller_id', $reseller->id)
            ->with('client')
            ->orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('event_type')) {
            $query->where('event_type', $request->event_type);
        }
        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }

        $logs   = $query->paginate(50);
        $events = ResellerSmsService::EVENT_LABELS;

        return view('reseller.sms.logs', compact('logs', 'events', 'reseller'));
    }

    // ─── Send to single client ────────────────────────────────────────────────

    public function sendToClient(Request $request, Client $client)
    {
        $reseller = $this->getReseller();

        if ($client->reseller_id !== $reseller->id) {
            abort(403);
        }

        $request->validate([
            'message' => 'required|string|max:600',
        ]);

        $service = new ResellerSmsService($reseller);
        if (!$service->isEnabled()) {
            return back()->with('error', 'SMS system is not enabled.');
        }

        $phone = $client->phone ?? '';
        if (empty($phone)) {
            return back()->with('error', 'This client has no phone number.');
        }

        $log = $service->sendRaw($client, $phone, $request->message, 'manual');
        return back()->with($log->status === 'sent' ? 'success' : 'error',
            $log->status === 'sent' ? 'SMS sent successfully.' : 'SMS send failed: ' . $log->response
        );
    }
}
