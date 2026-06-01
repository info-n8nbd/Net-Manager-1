<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\WhatsappLog;
use App\Models\WhatsappSetting;
use App\Models\WhatsappTemplate;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;

class WhatsAppController extends Controller
{
    protected WhatsAppService $whatsAppService;

    public function __construct(WhatsAppService $whatsAppService)
    {
        $this->whatsAppService = $whatsAppService;
    }

    public function settings()
    {
        $settings = WhatsappSetting::first() ?? new WhatsappSetting();
        $balance = $this->whatsAppService->getBalance();

        return view('admin.whatsapp.settings', compact('settings', 'balance'));
    }

    public function updateSettings(Request $request)
    {
        $request->validate([
            'provider' => 'required|string|in:whatsapp_business,twilio,360dialog,wati',
            'api_url' => 'nullable|url|max:500',
            'api_key' => 'nullable|string|max:500',
            'phone_number_id' => 'nullable|string|max:255',
            'business_account_id' => 'nullable|string|max:255',
            'access_token' => 'nullable|string|max:1000',
            'daily_limit' => 'required|integer|min:1|max:100000',
        ]);

        $settings = WhatsappSetting::first() ?? new WhatsappSetting();
        $settings->fill($request->only([
            'provider', 'api_url', 'api_key', 'phone_number_id',
            'business_account_id', 'access_token', 'daily_limit',
        ]));
        $settings->is_active = $request->has('is_active');
        $settings->save();

        return back()->with('success', 'WhatsApp settings updated successfully.');
    }

    public function templates()
    {
        $templates = WhatsappTemplate::orderBy('name')->paginate(in_array((int)request()->per_page,[20,50,100,200])?(int)request()->per_page:50);
        return view('admin.whatsapp.templates', compact('templates'));
    }

    public function storeTemplate(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255|unique:whatsapp_templates,code',
            'content' => 'required|string',
            'language' => 'nullable|string|max:10',
            'category' => 'nullable|string|max:50',
        ]);

        WhatsappTemplate::create([
            'name' => $request->name,
            'code' => $request->code,
            'content' => $request->content,
            'language' => $request->language ?? 'en',
            'category' => $request->category ?? 'utility',
            'variables' => $request->variables ?? [],
            'is_active' => $request->has('is_active'),
            'status' => 'approved',
        ]);

        return back()->with('success', 'WhatsApp template created successfully.');
    }

    public function updateTemplate(Request $request, WhatsappTemplate $template)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255|unique:whatsapp_templates,code,' . $template->id,
            'content' => 'required|string',
            'language' => 'nullable|string|max:10',
            'category' => 'nullable|string|max:50',
        ]);

        $template->update([
            'name' => $request->name,
            'code' => $request->code,
            'content' => $request->content,
            'language' => $request->language ?? $template->language,
            'category' => $request->category ?? $template->category,
            'variables' => $request->variables ?? $template->variables,
            'is_active' => $request->has('is_active'),
        ]);

        return back()->with('success', 'WhatsApp template updated successfully.');
    }

    public function deleteTemplate(WhatsappTemplate $template)
    {
        $template->delete();
        return back()->with('success', 'WhatsApp template deleted successfully.');
    }

    public function logs(Request $request)
    {
        $query = WhatsappLog::with('client')->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('phone', 'ILIKE', "%{$search}%")
                  ->orWhere('message', 'ILIKE', "%{$search}%");
            });
        }

        $logs = $query->paginate(in_array((int)request()->per_page,[20,50,100,200])?(int)request()->per_page:50);

        return view('admin.whatsapp.logs', compact('logs'));
    }

    public function send(Request $request)
    {
        $request->validate([
            'message' => 'required|string',
            'recipients' => 'required|array|min:1',
            'recipients.*' => 'required|string',
        ]);

        if (count($request->recipients) === 1) {
            $result = $this->whatsAppService->send($request->recipients[0], $request->message);

            if ($result['success']) {
                return back()->with('success', 'WhatsApp message sent successfully.');
            }

            return back()->with('error', 'Failed to send message: ' . ($result['error'] ?? 'Unknown error'))->withInput();
        }

        $recipients = array_map(fn($phone) => ['phone' => $phone], $request->recipients);
        $results = $this->whatsAppService->sendBulk($recipients, $request->message);

        return back()->with('success', "Bulk send completed: {$results['sent']} sent, {$results['failed']} failed.");
    }

    public function testMessage(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'message' => 'nullable|string',
        ]);

        $message = $request->message ?: 'This is a test message from ' . config('app.name', 'SmartBilling') . '.';
        $result = $this->whatsAppService->send($request->phone, $message);

        if ($result['success']) {
            return back()->with('success', 'Test message sent successfully.');
        }

        return back()->with('error', 'Test message failed: ' . ($result['error'] ?? 'Unknown error'));
    }
}
