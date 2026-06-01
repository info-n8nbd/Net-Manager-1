<?php

namespace App\Services;

use App\Models\EmailTemplate;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class EmailTemplateService
{
    protected int $cacheMinutes = 60;

    public function getTemplate(string $name): ?EmailTemplate
    {
        return Cache::remember("email_template:{$name}", $this->cacheMinutes * 60, function () use ($name) {
            return EmailTemplate::where('name', $name)->where('is_active', true)->first();
        });
    }

    public function renderTemplate(string $name, array $variables = []): ?array
    {
        $template = $this->getTemplate($name);
        
        if (!$template) {
            return null;
        }

        $subject = $this->replaceVariables($template->subject, $variables);
        $body = $this->replaceVariables($template->body, $variables);

        return [
            'subject' => $subject,
            'body' => $body,
        ];
    }

    protected function replaceVariables(string $content, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $content = str_replace("{{" . $key . "}}", $value, $content);
            $content = str_replace("{{ " . $key . " }}", $value, $content);
        }
        return $content;
    }

    public function send(string $templateName, string $email, array $variables = [], array $attachments = []): bool
    {
        try {
            $rendered = $this->renderTemplate($templateName, $variables);
            
            if (!$rendered) {
                Log::warning("Email template not found: {$templateName}");
                return false;
            }

            Mail::html($rendered['body'], function ($message) use ($email, $rendered, $attachments) {
                $message->to($email)
                    ->subject($rendered['subject']);
                
                foreach ($attachments as $attachment) {
                    if (isset($attachment['path'])) {
                        $message->attach($attachment['path'], $attachment['options'] ?? []);
                    } elseif (isset($attachment['data'])) {
                        $message->attachData($attachment['data'], $attachment['name'], $attachment['options'] ?? []);
                    }
                }
            });

            return true;
        } catch (\Exception $e) {
            Log::error("Failed to send email: " . $e->getMessage(), [
                'template' => $templateName,
                'email' => $email,
            ]);
            return false;
        }
    }

    public function clearCache(?string $name = null): void
    {
        if ($name) {
            Cache::forget("email_template:{$name}");
        } else {
            $templates = EmailTemplate::all();
            foreach ($templates as $template) {
                Cache::forget("email_template:{$template->name}");
            }
        }
    }

    public static function getDefaultTemplates(): array
    {
        return [
            [
                'name' => 'invoice',
                'subject' => 'Invoice #{{bill_no}} - {{company_name}}',
                'body' => '<html><body style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
<div style="background: #f8f9fa; padding: 20px; text-align: center;">
<h2 style="color: #333;">{{company_name}}</h2>
</div>
<div style="padding: 20px;">
<p>Dear {{client_name}},</p>
<p>Your invoice <strong>#{{bill_no}}</strong> for {{bill_month}} has been generated.</p>
<table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
<tr style="background: #f8f9fa;"><td style="padding: 10px; border: 1px solid #ddd;"><strong>Amount</strong></td><td style="padding: 10px; border: 1px solid #ddd;">{{currency}}{{amount}}</td></tr>
<tr><td style="padding: 10px; border: 1px solid #ddd;"><strong>Due Date</strong></td><td style="padding: 10px; border: 1px solid #ddd;">{{due_date}}</td></tr>
<tr><td style="padding: 10px; border: 1px solid #ddd;"><strong>Package</strong></td><td style="padding: 10px; border: 1px solid #ddd;">{{package_name}}</td></tr>
</table>
<p>Please pay before the due date to avoid service interruption.</p>
<p>Thank you for choosing {{company_name}}!</p>
</div>
<div style="background: #f8f9fa; padding: 15px; text-align: center; font-size: 12px; color: #666;">
{{company_address}}<br>{{company_phone}}
</div>
</body></html>',
                'variables' => json_encode(['bill_no', 'bill_month', 'client_name', 'amount', 'due_date', 'package_name', 'company_name', 'company_address', 'company_phone', 'currency']),
            ],
            [
                'name' => 'payment_confirmation',
                'subject' => 'Payment Received - {{company_name}}',
                'body' => '<html><body style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
<div style="background: #28a745; padding: 20px; text-align: center;">
<h2 style="color: #fff;">Payment Received</h2>
</div>
<div style="padding: 20px;">
<p>Dear {{client_name}},</p>
<p>We have received your payment of <strong>{{currency}}{{amount}}</strong>.</p>
<table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
<tr style="background: #f8f9fa;"><td style="padding: 10px; border: 1px solid #ddd;"><strong>Transaction ID</strong></td><td style="padding: 10px; border: 1px solid #ddd;">{{transaction_id}}</td></tr>
<tr><td style="padding: 10px; border: 1px solid #ddd;"><strong>Payment Date</strong></td><td style="padding: 10px; border: 1px solid #ddd;">{{payment_date}}</td></tr>
<tr><td style="padding: 10px; border: 1px solid #ddd;"><strong>Payment Method</strong></td><td style="padding: 10px; border: 1px solid #ddd;">{{payment_method}}</td></tr>
</table>
<p>Thank you for your payment!</p>
</div>
<div style="background: #f8f9fa; padding: 15px; text-align: center; font-size: 12px; color: #666;">
{{company_name}}<br>{{company_phone}}
</div>
</body></html>',
                'variables' => json_encode(['client_name', 'amount', 'transaction_id', 'payment_date', 'payment_method', 'company_name', 'company_phone', 'currency']),
            ],
            [
                'name' => 'due_reminder',
                'subject' => 'Payment Reminder - {{company_name}}',
                'body' => '<html><body style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
<div style="background: #ffc107; padding: 20px; text-align: center;">
<h2 style="color: #333;">Payment Reminder</h2>
</div>
<div style="padding: 20px;">
<p>Dear {{client_name}},</p>
<p>This is a friendly reminder that your payment of <strong>{{currency}}{{due_amount}}</strong> is due.</p>
<p>Please pay before {{due_date}} to avoid service interruption.</p>
<p><a href="{{payment_link}}" style="display: inline-block; background: #007bff; color: #fff; padding: 12px 24px; text-decoration: none; border-radius: 5px;">Pay Now</a></p>
<p>If you have already made the payment, please ignore this reminder.</p>
</div>
<div style="background: #f8f9fa; padding: 15px; text-align: center; font-size: 12px; color: #666;">
{{company_name}}<br>{{company_phone}}
</div>
</body></html>',
                'variables' => json_encode(['client_name', 'due_amount', 'due_date', 'payment_link', 'company_name', 'company_phone', 'currency']),
            ],
            [
                'name' => 'welcome',
                'subject' => 'Welcome to {{company_name}}!',
                'body' => '<html><body style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
<div style="background: #007bff; padding: 20px; text-align: center;">
<h2 style="color: #fff;">Welcome!</h2>
</div>
<div style="padding: 20px;">
<p>Dear {{client_name}},</p>
<p>Welcome to {{company_name}}! Your internet connection is now active.</p>
<table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
<tr style="background: #f8f9fa;"><td style="padding: 10px; border: 1px solid #ddd;"><strong>Client ID</strong></td><td style="padding: 10px; border: 1px solid #ddd;">{{client_id}}</td></tr>
<tr><td style="padding: 10px; border: 1px solid #ddd;"><strong>Package</strong></td><td style="padding: 10px; border: 1px solid #ddd;">{{package_name}}</td></tr>
<tr><td style="padding: 10px; border: 1px solid #ddd;"><strong>Monthly Fee</strong></td><td style="padding: 10px; border: 1px solid #ddd;">{{currency}}{{monthly_fee}}</td></tr>
</table>
<p>You can check your account status anytime at: <a href="{{portal_link}}">{{portal_link}}</a></p>
<p>Thank you for choosing us!</p>
</div>
<div style="background: #f8f9fa; padding: 15px; text-align: center; font-size: 12px; color: #666;">
{{company_name}}<br>{{company_address}}<br>{{company_phone}}
</div>
</body></html>',
                'variables' => json_encode(['client_name', 'client_id', 'package_name', 'monthly_fee', 'portal_link', 'company_name', 'company_address', 'company_phone', 'currency']),
            ],
            [
                'name' => 'suspension_notice',
                'subject' => 'Service Suspension Notice - {{company_name}}',
                'body' => '<html><body style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
<div style="background: #dc3545; padding: 20px; text-align: center;">
<h2 style="color: #fff;">Service Suspended</h2>
</div>
<div style="padding: 20px;">
<p>Dear {{client_name}},</p>
<p>Your internet service has been suspended due to non-payment.</p>
<p><strong>Outstanding Amount: {{currency}}{{due_amount}}</strong></p>
<p>To restore your service, please make the payment immediately.</p>
<p><a href="{{payment_link}}" style="display: inline-block; background: #28a745; color: #fff; padding: 12px 24px; text-decoration: none; border-radius: 5px;">Pay Now</a></p>
<p>Contact us if you have any questions.</p>
</div>
<div style="background: #f8f9fa; padding: 15px; text-align: center; font-size: 12px; color: #666;">
{{company_name}}<br>{{company_phone}}
</div>
</body></html>',
                'variables' => json_encode(['client_name', 'due_amount', 'payment_link', 'company_name', 'company_phone', 'currency']),
            ],
        ];
    }
}
