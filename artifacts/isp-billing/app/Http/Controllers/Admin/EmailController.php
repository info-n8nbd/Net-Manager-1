<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\EmailLog;
use App\Models\EmailSetting;
use App\Services\EmailReminderService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;

class EmailController extends Controller
{
    public function index(Request $request)
    {
        $stats = [
            'total'   => EmailLog::count(),
            'sent'    => EmailLog::where('status', 'sent')->count(),
            'failed'  => EmailLog::where('status', 'failed')->count(),
            'today'   => EmailLog::whereDate('created_at', today())->count(),
            'week'    => EmailLog::where('created_at', '>=', Carbon::now()->subDays(7))->count(),
        ];

        $chartData = [];
        for ($i = 13; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->toDateString();
            $chartData[] = [
                'date'   => Carbon::now()->subDays($i)->format('M d'),
                'sent'   => EmailLog::whereDate('created_at', $date)->where('status', 'sent')->count(),
                'failed' => EmailLog::whereDate('created_at', $date)->where('status', 'failed')->count(),
            ];
        }

        $query = EmailLog::with('client')->latest();
        if ($request->filled('status'))   $query->where('status', $request->status);
        if ($request->filled('template')) $query->where('template', $request->template);
        if ($request->filled('date'))     $query->whereDate('created_at', $request->date);
        if ($request->filled('search'))   $query->where(function ($q) use ($request) {
            $q->where('to_email', 'like', '%' . $request->search . '%')
              ->orWhere('to_name',  'like', '%' . $request->search . '%')
              ->orWhere('subject',  'like', '%' . $request->search . '%');
        });

        $logs     = $query->paginate(in_array((int)request()->per_page,[20,50,100,200])?(int)request()->per_page:20)->withQueryString();
        $settings = EmailSetting::first();
        $clients  = Client::whereNotNull('email')->where('email', '!=', '')->orderBy('name')->get(['id', 'name', 'email', 'phone']);

        return view('admin.email.index', compact('stats', 'chartData', 'logs', 'settings', 'clients'));
    }

    public function settings()
    {
        $settings = EmailSetting::first() ?? new EmailSetting();
        return view('admin.email.settings', compact('settings'));
    }

    public function saveSettings(Request $request)
    {
        $request->validate([
            'host'         => 'required|string|max:255',
            'port'         => 'required|integer',
            'username'     => 'required|string|max:255',
            'password'     => 'nullable|string|max:255',
            'encryption'   => 'required|in:tls,ssl,none',
            'from_address' => 'required|email|max:255',
            'from_name'    => 'required|string|max:255',
        ]);

        $data = $request->only([
            'host', 'port', 'username', 'encryption', 'from_address', 'from_name',
            'bill_reminder_days_before', 'expiry_warning_days_before',
        ]);
        $data['mailer']                    = 'smtp';
        $data['is_active']                 = $request->boolean('is_active');
        $data['auto_bill_reminder']        = $request->boolean('auto_bill_reminder');
        $data['auto_expiry_warning']       = $request->boolean('auto_expiry_warning');
        $data['auto_payment_confirmation'] = $request->boolean('auto_payment_confirmation');

        if ($request->filled('password')) {
            $data['password'] = $request->password;
        }

        EmailSetting::updateOrCreate(['id' => 1], $data);

        return back()->with('success', 'Email settings saved successfully.');
    }

    public function testEmail(Request $request)
    {
        $request->validate(['test_email' => 'required|email']);

        $s = EmailSetting::first();
        if (!$s || !$s->host || !$s->username || !$s->password) {
            return back()->with('error', 'Email not configured. Please save settings first.');
        }

        Config::set('mail.default', 'smtp');
        Config::set('mail.mailers.smtp.host', $s->host);
        Config::set('mail.mailers.smtp.port', $s->port);
        Config::set('mail.mailers.smtp.username', $s->username);
        Config::set('mail.mailers.smtp.password', $s->password);
        Config::set('mail.mailers.smtp.encryption', $s->encryption);
        Config::set('mail.from.address', $s->from_address);
        Config::set('mail.from.name', $s->from_name);

        $prevTimeout = ini_get('default_socket_timeout');
        ini_set('default_socket_timeout', 15);

        try {
            $html = "<h2>Test Email</h2><p>Your email configuration is working correctly!</p><p>Sent at: " . now() . "</p>";
            Mail::html($html, function ($msg) use ($request, $s) {
                $msg->to($request->test_email)->subject($s->from_name . ' - Test Email');
            });

            ini_set('default_socket_timeout', $prevTimeout);

            EmailLog::create([
                'to_email' => $request->test_email,
                'subject'  => 'Test Email',
                'template' => 'test',
                'status'   => 'sent',
                'sent_at'  => now(),
            ]);

            return back()->with('success', 'Test email sent to ' . $request->test_email);
        } catch (\Symfony\Component\Mailer\Exception\TransportException $e) {
            ini_set('default_socket_timeout', $prevTimeout);
            $msg = $e->getMessage();
            if (str_contains($msg, 'Connection timed out') || str_contains($msg, 'timed out') || str_contains($msg, 'Connection refused')) {
                return back()->with('error', 'SMTP connection failed: Could not connect to ' . $s->host . ':' . $s->port . '. Check host, port and firewall settings.');
            }
            return back()->with('error', 'SMTP Error: ' . $msg);
        } catch (\Exception $e) {
            ini_set('default_socket_timeout', $prevTimeout);
            return back()->with('error', 'Failed: ' . $e->getMessage());
        }
    }

    public function sendToClient(Request $request, EmailReminderService $service)
    {
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'type'      => 'required|in:bill_reminder,expiry_warning,payment_confirmation,suspension_notice,welcome',
            'amount'    => 'nullable|numeric',
        ]);

        $client = Client::findOrFail($request->client_id);

        if (empty($client->email)) {
            return back()->with('error', 'This client does not have an email address.');
        }

        $ok = match ($request->type) {
            'bill_reminder'        => $service->sendBillReminder($client),
            'expiry_warning'       => $service->sendExpiryWarning($client),
            'payment_confirmation' => $service->sendPaymentConfirmation($client, (float)($request->amount ?? 0)),
            'suspension_notice'    => $service->sendSuspensionNotice($client, (float)($request->amount ?? 0)),
            'welcome'              => $service->sendWelcome($client),
            default                => false,
        };

        return back()->with(
            $ok ? 'success' : 'error',
            $ok ? "Email sent successfully to {->email}." : 'Failed to send email. Please check your settings.'
        );
    }

    public function sendBatch(Request $request, EmailReminderService $service)
    {
        $type = $request->input('type', 'all');

        $billResult   = ['sent' => 0, 'failed' => 0];
        $expiryResult = ['sent' => 0, 'failed' => 0];

        if (in_array($type, ['all', 'bill_reminder'])) {
            $billResult = $service->runBillReminderBatch();
        }
        if (in_array($type, ['all', 'expiry_warning'])) {
            $expiryResult = $service->runExpiryWarningBatch();
        }

        $totalSent   = $billResult['sent'] + $expiryResult['sent'];
        $totalFailed = $billResult['failed'] + $expiryResult['failed'];

        return back()->with('success', "Batch complete → Sent: {$totalSent}, Failed: {$totalFailed}");
    }

    public function deleteLog(EmailLog $log)
    {
        $log->delete();
        return back()->with('success', 'Log deleted.');
    }

    public function clearLogs(Request $request)
    {
        $days = (int)$request->input('days', 30);
        $deleted = EmailLog::where('created_at', '<', Carbon::now()->subDays($days))->delete();
        return back()->with('success', "{$deleted} old log(s) deleted successfully.");
    }
}
