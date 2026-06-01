<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailTemplate;
use App\Services\EmailTemplateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class EmailTemplateController extends Controller
{
    protected EmailTemplateService $templateService;

    public function __construct(EmailTemplateService $templateService)
    {
        $this->templateService = $templateService;
    }

    public function index()
    {
        $templates = EmailTemplate::orderBy('name')->get();
        return view('admin.settings.email-templates.index', compact('templates'));
    }

    public function edit(EmailTemplate $template)
    {
        return view('admin.settings.email-templates.edit', compact('template'));
    }

    public function update(Request $request, EmailTemplate $template)
    {
        $request->validate([
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
            'is_active' => 'boolean',
        ]);

        $template->update([
            'subject' => $request->subject,
            'body' => $request->body,
            'is_active' => $request->boolean('is_active', true),
        ]);

        $this->templateService->clearCache($template->name);

        return redirect()->route('admin.settings.email-templates')
            ->with('success', 'Email template updated successfully.');
    }

    public function preview(Request $request, EmailTemplate $template)
    {
        $request->validate([
            'subject' => 'nullable|string',
            'body' => 'nullable|string',
        ]);

        $variables = $this->getSampleVariables($template->name);
        
        $subject = $request->subject ?? $template->subject;
        $body = $request->body ?? $template->body;

        foreach ($variables as $key => $value) {
            $subject = str_replace(["{{" . $key . "}}", "{{ " . $key . " }}"], $value, $subject);
            $body = str_replace(["{{" . $key . "}}", "{{ " . $key . " }}"], $value, $body);
        }

        return response()->json([
            'subject' => $subject,
            'body' => $body,
        ]);
    }

    public function sendTest(Request $request, EmailTemplate $template)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $variables = $this->getSampleVariables($template->name);
        
        $success = $this->templateService->send($template->name, $request->email, $variables);

        if ($success) {
            return response()->json([
                'success' => true,
                'message' => 'Test email sent to ' . $request->email,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to send test email. Please check your mail configuration.',
        ], 500);
    }

    protected function getSampleVariables(string $templateName): array
    {
        $common = [
            'company_name' => config('app.name', 'ISP Company'),
            'company_address' => '123 Network Street, Tech City',
            'company_phone' => '+880 1700 000000',
            'currency' => '৳',
        ];

        return match ($templateName) {
            'invoice' => array_merge($common, [
                'bill_no' => 'INV-2026-001234',
                'bill_month' => 'January 2026',
                'client_name' => 'John Doe',
                'amount' => '1,000',
                'due_date' => 'February 10, 2026',
                'package_name' => '10 Mbps Unlimited',
            ]),
            'payment_confirmation' => array_merge($common, [
                'client_name' => 'John Doe',
                'amount' => '1,000',
                'transaction_id' => 'TRX-2026-56789',
                'payment_date' => 'January 28, 2026',
                'payment_method' => 'bKash',
            ]),
            'due_reminder' => array_merge($common, [
                'client_name' => 'John Doe',
                'due_amount' => '2,000',
                'due_date' => 'January 30, 2026',
                'payment_link' => url('/check'),
            ]),
            'welcome' => array_merge($common, [
                'client_name' => 'John Doe',
                'client_id' => 'CLT-00123',
                'package_name' => '10 Mbps Unlimited',
                'monthly_fee' => '1,000',
                'portal_link' => url('/check'),
            ]),
            'suspension_notice' => array_merge($common, [
                'client_name' => 'John Doe',
                'due_amount' => '3,000',
                'payment_link' => url('/check'),
            ]),
            default => $common,
        };
    }
}
