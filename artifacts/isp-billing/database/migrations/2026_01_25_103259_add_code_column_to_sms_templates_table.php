<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sms_templates', function (Blueprint $table) {
            $table->string('code')->unique()->after('id');
        });

        // Seed default SMS templates
        $templates = [
            [
                'code' => 'payment_received',
                'name' => 'Payment Received',
                'type' => 'payment',
                'content' => 'Dear {client_name}, we received your payment of BDT {amount}. Receipt: {payment_no}. Thank you! - SOFTYNEX',
                'is_active' => true,
            ],
            [
                'code' => 'monthly_bill',
                'name' => 'Monthly Bill',
                'type' => 'billing',
                'content' => 'Dear {client_name}, your bill of BDT {amount} for {month} is ready. Due: {due_date}. Pay now to avoid service interruption. - SOFTYNEX',
                'is_active' => true,
            ],
            [
                'code' => 'due_reminder',
                'name' => 'Due Reminder',
                'type' => 'reminder',
                'content' => 'Dear {client_name}, you have BDT {due_amount} overdue. Please pay immediately to avoid disconnection. - SOFTYNEX',
                'is_active' => true,
            ],
            [
                'code' => 'disconnect_notice',
                'name' => 'Disconnect Notice',
                'type' => 'status',
                'content' => 'Dear {client_name}, your connection has been suspended due to unpaid bills. Pay BDT {due_amount} to restore service. - SOFTYNEX',
                'is_active' => true,
            ],
            [
                'code' => 'reconnect_notice',
                'name' => 'Reconnect Notice',
                'type' => 'status',
                'content' => 'Dear {client_name}, your connection has been restored. Thank you for your payment! - SOFTYNEX',
                'is_active' => true,
            ],
            [
                'code' => 'application_submitted',
                'name' => 'Application Submitted',
                'type' => 'application',
                'content' => 'Dear {client_name}, your connection application has been submitted. We will review it shortly. - SOFTYNEX',
                'is_active' => true,
            ],
            [
                'code' => 'application_correction',
                'name' => 'Application Correction',
                'type' => 'application',
                'content' => 'Dear {client_name}, your application needs correction: {reason}. Please update and resubmit. - SOFTYNEX',
                'is_active' => true,
            ],
            [
                'code' => 'application_approved',
                'name' => 'Application Approved',
                'type' => 'application',
                'content' => 'Dear {client_name}, your connection application has been approved! Our team will contact you soon for installation. - SOFTYNEX',
                'is_active' => true,
            ],
            [
                'code' => 'application_rejected',
                'name' => 'Application Rejected',
                'type' => 'application',
                'content' => 'Dear {client_name}, your application was not approved. Reason: {reason}. Contact us for more details. - SOFTYNEX',
                'is_active' => true,
            ],
        ];

        foreach ($templates as $template) {
            DB::table('sms_templates')->insert(array_merge($template, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }

    public function down(): void
    {
        Schema::table('sms_templates', function (Blueprint $table) {
            $table->dropColumn('code');
        });
    }
};
