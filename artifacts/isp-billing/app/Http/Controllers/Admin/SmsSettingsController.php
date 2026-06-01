<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SmsLog;
use App\Models\SystemSetting;
use App\Services\SmsService;
use Illuminate\Http\Request;

class SmsSettingsController extends Controller
{
    public function index()
    {
        $smsService = app(SmsService::class);
        
        $settings = [
            'sms_global_enabled' => SystemSetting::get('sms_global_enabled', '0'),
            'sms_event_monthly_bill' => SystemSetting::get('sms_event_monthly_bill', '1'),
            'sms_event_bill_confirmation' => SystemSetting::get('sms_event_bill_confirmation', '1'),
            'sms_event_payment_confirmation' => SystemSetting::get('sms_event_payment_confirmation', '1'),
            'sms_event_service_suspension' => SystemSetting::get('sms_event_service_suspension', '1'),
            'sms_event_service_restoration' => SystemSetting::get('sms_event_service_restoration', '1'),
            'sms_event_advance_payment' => SystemSetting::get('sms_event_advance_payment', '1'),
            'sms_event_due_reminder_3day' => SystemSetting::get('sms_event_due_reminder_3day', '1'),
            'sms_event_due_reminder_1day' => SystemSetting::get('sms_event_due_reminder_1day', '1'),
            'sms_event_auto_inactive'     => SystemSetting::get('sms_event_auto_inactive', '1'),
            'sms_auto_reminder_time'      => SystemSetting::get('sms_auto_reminder_time', '09:30'),
            'sms_template_due_reminder_3day' => SystemSetting::get('sms_template_due_reminder_3day', ''),
            'sms_template_due_reminder_1day' => SystemSetting::get('sms_template_due_reminder_1day', ''),
            'sms_template_auto_inactive'     => SystemSetting::get('sms_template_auto_inactive', ''),
            'sms_role_client' => SystemSetting::get('sms_role_client', '1'),
            'sms_role_technician' => SystemSetting::get('sms_role_technician', '1'),
            'sms_role_admin' => SystemSetting::get('sms_role_admin', '1'),
            'sms_provider' => SystemSetting::get('sms_provider', SmsService::PROVIDER_BULKSMS_DHAKA),
            'sms_api_key' => SystemSetting::get('sms_api_key', ''),
            'sms_sender_id' => SystemSetting::get('sms_sender_id', ''),
            'sms_twilio_auth_token' => SystemSetting::get('sms_twilio_auth_token', ''),
            'sms_reve_secret_key' => SystemSetting::get('sms_reve_secret_key', ''),
            'sms_ajura_secret_key' => SystemSetting::get('sms_ajura_secret_key', ''),
            'sms_adn_secret_key' => SystemSetting::get('sms_adn_secret_key', ''),
            'sms_custom_api_url' => SystemSetting::get('sms_custom_api_url', ''),
            'sms_custom_api_method' => SystemSetting::get('sms_custom_api_method', 'GET'),
            'sms_custom_phone_param' => SystemSetting::get('sms_custom_phone_param', 'number'),
            'sms_custom_message_param' => SystemSetting::get('sms_custom_message_param', 'message'),
            'sms_custom_api_key_param' => SystemSetting::get('sms_custom_api_key_param', 'apikey'),
            'sms_custom_sender_id_param' => SystemSetting::get('sms_custom_sender_id_param', 'senderid'),
            'sms_custom_extra_params' => SystemSetting::get('sms_custom_extra_params', ''),
            'sms_custom_success_response' => SystemSetting::get('sms_custom_success_response', ''),
            'sms_custom_balance_url' => SystemSetting::get('sms_custom_balance_url', ''),
            'sms_custom_balance_field' => SystemSetting::get('sms_custom_balance_field', 'balance'),
        ];

        $stats = [
            'total_sent' => SmsLog::where('status', 'sent')->count(),
            'total_failed' => SmsLog::where('status', 'failed')->count(),
            'total_skipped' => SmsLog::where('status', 'skipped')->count(),
            'today_sent' => SmsLog::where('status', 'sent')->whereDate('created_at', today())->count(),
        ];

        $apiStatus = $smsService->getApiStatus();
        $providers = SmsService::getProviders();

        return view('admin.settings.sms', compact('settings', 'stats', 'apiStatus', 'providers'));
    }

    public function update(Request $request)
    {
        $toggleKeys = [
            'sms_global_enabled',
            'sms_event_monthly_bill',
            'sms_event_bill_confirmation',
            'sms_event_payment_confirmation',
            'sms_event_service_suspension',
            'sms_event_service_restoration',
            'sms_event_advance_payment',
            'sms_event_due_reminder_3day',
            'sms_event_due_reminder_1day',
            'sms_event_auto_inactive',
            'sms_role_client',
            'sms_role_technician',
            'sms_role_admin',
        ];

        $textKeys = [
            'sms_provider',
            'sms_api_key',
            'sms_sender_id',
            'sms_twilio_auth_token',
            'sms_reve_secret_key',
            'sms_ajura_secret_key',
            'sms_adn_secret_key',
            'sms_custom_api_url',
            'sms_custom_api_method',
            'sms_custom_phone_param',
            'sms_custom_message_param',
            'sms_custom_api_key_param',
            'sms_custom_sender_id_param',
            'sms_custom_extra_params',
            'sms_custom_success_response',
            'sms_custom_balance_url',
            'sms_custom_balance_field',
            'sms_template_due_reminder_3day',
            'sms_template_due_reminder_1day',
            'sms_template_auto_inactive',
            'sms_auto_reminder_time',
        ];

        foreach ($toggleKeys as $key) {
            $value = $request->has($key) ? '1' : '0';
            SystemSetting::set($key, $value);
        }

        foreach ($textKeys as $key) {
            $value = $request->input($key, '');
            SystemSetting::set($key, $value);
        }

        return redirect()->route('admin.settings.sms')->with('success', 'SMS settings updated successfully.');
    }

    public function checkBalance()
    {
        try {
            $smsService = app(SmsService::class);
            $balance = $smsService->getBalance(true);
            
            if (!$balance) {
                return response()->json([
                    'balance' => 0,
                    'currency' => 'BDT',
                    'error' => 'Could not retrieve balance. Check SMS API configuration.',
                    'updated_at' => now()->toDateTimeString()
                ]);
            }
            
            return response()->json($balance);
        } catch (\Exception $e) {
            return response()->json([
                'balance' => 0,
                'currency' => 'BDT',
                'error' => 'Balance check failed: ' . $e->getMessage(),
                'updated_at' => now()->toDateTimeString()
            ]);
        }
    }

    public function getApiStatus()
    {
        $smsService = app(SmsService::class);
        
        return response()->json($smsService->getApiStatus());
    }

    public function testSms(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'message' => 'required|string|max:500'
        ]);

        $smsService = app(SmsService::class);
        
        $configStatus = $smsService->getConfigurationStatus();
        
        if (!$configStatus['configured']) {
            $issueText = implode(', ', $configStatus['issues']);
            return response()->json([
                'success' => false,
                'error' => 'SMS API is not configured properly: ' . $issueText,
                'issues' => $configStatus['issues']
            ], 400);
        }

        $result = $smsService->testApi(
            $request->phone,
            $request->message
        );

        return response()->json($result);
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
            'test' => 'Test SMS',
            'custom' => 'Custom',
        ];

        return view('admin.sms.logs', compact('logs', 'eventTypes'));
    }
}
