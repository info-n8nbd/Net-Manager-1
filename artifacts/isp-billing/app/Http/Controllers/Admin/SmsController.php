<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SmsTemplate;
use App\Models\SmsLog;
use App\Models\Client;
use App\Services\SmsService;
use Illuminate\Http\Request;

class SmsController extends Controller
{
    protected SmsService $smsService;

    public function __construct(SmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    public function templates(Request $request)
    {
        $query = SmsTemplate::query();
        
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        
        $templates = $query->orderBy('type')->orderBy('name')->paginate(in_array((int)request()->per_page,[20,50,100,200])?(int)request()->per_page:50);
        return view('admin.sms.templates', compact('templates'));
    }

    public function storeTemplate(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string',
            'content' => 'required|string',
        ]);

        $code = $request->code ?: \Str::slug($request->name, '_');
        
        // Ensure unique code for this company
        $existing = SmsTemplate::where('code', $code)->count();
        if ($existing > 0) {
            $code = $code . '_' . time();
        }

        try {
            SmsTemplate::create([
                'name' => $request->name,
                'type' => $request->type,
                'code' => $code,
                'content' => $request->content,
                'is_active' => $request->has('is_active'),
            ]);
            return back()->with('success', 'SMS template created successfully.');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Failed to save template: ' . $e->getMessage());
        }
    }

    public function bulk()
    {
        $clients = Client::where('status', 'active')->get();
        $templates = SmsTemplate::where('is_active', true)->get();
        return view('admin.sms.bulk', compact('clients', 'templates'));
    }

    public function sendBulk(Request $request)
    {
        $request->validate([
            'message' => 'required|string',
            'recipients' => 'required|array|min:1',
        ]);

        $configStatus = $this->smsService->getConfigurationStatus();
        if (!$configStatus['configured']) {
            return back()->with('error', 'SMS is not configured. Please set up your SMS gateway first in Settings > SMS Settings.')->withInput();
        }

        $results = $this->smsService->sendBulkSms($request->recipients, $request->message);

        $sent = collect($results)->where('status', 'sent')->count();
        $failed = collect($results)->where('status', 'failed')->count();
        $skipped = collect($results)->where('status', 'skipped')->count();

        $message = "{$sent} SMS sent successfully.";
        if ($failed > 0) $message .= " {$failed} failed.";
        if ($skipped > 0) $message .= " {$skipped} skipped.";

        return back()->with($sent > 0 ? 'success' : 'warning', $message);
    }

    public function logs(Request $request)
    {
        $query = SmsLog::with(['client', 'sentBy'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('event_type')) {
            $query->where('event_type', $request->event_type);
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
                $q->where('phone', 'like', "%{$search}%")
                  ->orWhere('message', 'like', "%{$search}%");
            });
        }

        $logs = $query->paginate(in_array((int)request()->per_page,[20,50,100,200])?(int)request()->per_page:50);

        $eventTypes = [
            'monthly_bill' => 'Monthly Bill',
            'bill_confirmation' => 'Bill Confirmation',
            'payment_confirmation' => 'Payment Confirmation',
            'service_suspension' => 'Service Suspension',
            'service_restoration' => 'Service Restoration',
            'custom' => 'Custom',
        ];

        return view('admin.sms.logs', compact('logs', 'eventTypes'));
    }
    
    public function autoProcess(Request $request)
    {
        $settingKeys = [
            'sms_auto_bill_generation',
            'sms_auto_reminder_3day',
            'sms_auto_reminder_1day',
            'sms_auto_disconnection',
            'sms_auto_online_payment',
            'sms_auto_device_down',
            'sms_auto_admin_phone',
            'sms_auto_welcome',
            'sms_auto_reminder_time',
            'sms_auto_device_check_interval',
        ];
        
        $settings = [];
        foreach ($settingKeys as $key) {
            $settings[$key] = \App\Models\SystemSetting::get($key, '');
        }
        
        foreach (['sms_auto_bill_generation', 'sms_auto_reminder_3day', 'sms_auto_reminder_1day', 'sms_auto_disconnection', 'sms_auto_online_payment', 'sms_auto_device_down', 'sms_auto_welcome'] as $boolKey) {
            $settings[$boolKey] = $settings[$boolKey] === '1';
        }
        
        if ($request->has('run_now')) {
            try {
                \Artisan::call('billing:send-reminders');
                $output = \Artisan::output();
                return redirect()->route('admin.sms.auto-process')->with('success', 'Auto process executed. ' . trim($output));
            } catch (\Exception $e) {
                return redirect()->route('admin.sms.auto-process')->with('warning', 'Auto process completed with note: ' . $e->getMessage());
            }
        }
        
        return view('admin.sms.auto-process', compact('settings'));
    }
    
    public function saveAutoProcess(Request $request)
    {
        $checkboxKeys = [
            'sms_auto_bill_generation',
            'sms_auto_reminder_3day',
            'sms_auto_reminder_1day',
            'sms_auto_disconnection',
            'sms_auto_online_payment',
            'sms_auto_device_down',
            'sms_auto_welcome',
        ];
        
        foreach ($checkboxKeys as $key) {
            \App\Models\SystemSetting::set($key, $request->has($key) ? '1' : '0');
        }
        
        $textKeys = [
            'sms_auto_admin_phone',
            'sms_auto_reminder_time',
            'sms_auto_device_check_interval',
        ];
        
        foreach ($textKeys as $key) {
            if ($request->has($key)) {
                \App\Models\SystemSetting::set($key, $request->input($key, ''));
            }
        }
        
        return back()->with('success', 'SMS auto process settings saved successfully.');
    }
    
    public function updateTemplate(Request $request, SmsTemplate $template)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string',
            'content' => 'required|string',
        ]);
        
        try {
            $template->update([
                'name' => $request->name,
                'type' => $request->type,
                'code' => $request->code ?: $template->code,
                'content' => $request->content,
                'is_active' => $request->has('is_active'),
            ]);
            return back()->with('success', 'SMS template updated.');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Failed to update template: ' . $e->getMessage());
        }
    }
    
    public function destroyTemplate(SmsTemplate $template)
    {
        $template->delete();
        return back()->with('success', 'SMS template deleted successfully.');
    }

    public function individual()
    {
        $clients = Client::where('status', 'active')->get();
        $templates = SmsTemplate::where('is_active', true)->get();
        $apiStatus = $this->smsService->getApiStatus();
        return view('admin.sms.individual', compact('clients', 'templates', 'apiStatus'));
    }

    public function sendIndividual(Request $request)
    {
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'message' => 'required|string',
        ]);

        $configStatus = $this->smsService->getConfigurationStatus();
        if (!$configStatus['configured']) {
            return back()->with('error', 'SMS is not configured. Please set up your SMS gateway first in Settings > SMS Settings.')->withInput();
        }

        $client = Client::findOrFail($request->client_id);

        if (!$client->phone) {
            return back()->with('error', 'Selected client has no phone number.')->withInput();
        }

        $result = $this->smsService->sendSms($client->phone, $request->message, SmsService::EVENT_CUSTOM, $client->id);

        $redirectUrl = $request->input('redirect_to');
        $safeRedirect = $redirectUrl && str_starts_with($redirectUrl, url('/'));

        if ($result && $result->status === 'sent') {
            $msg = 'SMS sent successfully to ' . $client->name . ' (' . $client->phone . ').';
            return $safeRedirect ? redirect($redirectUrl)->with('success', $msg) : back()->with('success', $msg);
        } elseif ($result && $result->status === 'skipped') {
            $msg = 'SMS was skipped: ' . ($result->skip_reason ?? 'Unknown reason');
            return $safeRedirect ? redirect($redirectUrl)->with('warning', $msg) : back()->with('warning', $msg);
        } else {
            $errorMsg = $result ? ('SMS sending failed. Response: ' . ($result->response ?? 'No response')) : 'SMS sending failed.';
            return $safeRedirect ? redirect($redirectUrl)->with('error', $errorMsg) : back()->with('error', $errorMsg)->withInput();
        }
    }

    public function gateway()
    {
        return redirect()->route('admin.settings.sms');
    }

    public function updateGateway(Request $request)
    {
        return redirect()->route('admin.settings.sms');
    }
    public function saveAutoTemplates(\Illuminate\Http\Request $request)
    {
        $keys = [
            'sms_template_due_reminder_3day',
            'sms_template_due_reminder_1day',
            'sms_template_auto_inactive',
        ];

        $saved = 0;
        foreach ($keys as $key) {
            if ($request->has($key)) {
                \App\Models\SystemSetting::set($key, $request->input($key, ''));
                $saved++;
            }
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'saved' => $saved]);
        }

        return redirect()->route('admin.sms.templates')
            ->with('success', 'Automation SMS template saved.');
    }

}