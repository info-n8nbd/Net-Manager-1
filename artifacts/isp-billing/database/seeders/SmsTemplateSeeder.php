<?php

namespace Database\Seeders;

use App\Models\SmsTemplate;
use Illuminate\Database\Seeder;

class SmsTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            ['name' => 'Bill Generation', 'type' => 'bill_generation', 'content' => 'Dear {client_name}, your bill of BDT {amount} has been generated for {month}. Due date: {due_date}. Pay now to avoid disconnection.', 'is_active' => true],
            ['name' => '1st Due Reminder (3 days)', 'type' => 'due_reminder_1', 'content' => 'Dear {client_name}, your bill of BDT {amount} is due in 3 days ({due_date}). Please pay to continue service.', 'is_active' => true],
            ['name' => '2nd Due Reminder (1 day)', 'type' => 'due_reminder_2', 'content' => 'URGENT: Dear {client_name}, your bill of BDT {amount} is due tomorrow ({due_date}). Pay now to avoid disconnection.', 'is_active' => true],
            ['name' => 'Disconnection Notice', 'type' => 'disconnection', 'content' => 'Dear {client_name}, your internet service has been disconnected due to unpaid bill of BDT {amount}. Pay now to reconnect.', 'is_active' => true],
            ['name' => 'Reconnection Confirmation', 'type' => 'reconnection', 'content' => 'Dear {client_name}, your internet connection has been restored. Thank you for your payment.', 'is_active' => true],
            ['name' => 'Payment Confirmation', 'type' => 'payment_confirmation', 'content' => 'Dear {client_name}, we received your payment of BDT {amount}. TrxID: {trx_id}. Thank you!', 'is_active' => true],
            ['name' => 'Online Payment Success', 'type' => 'online_payment', 'content' => 'Dear {client_name}, your online payment of BDT {amount} via {gateway} was successful. TrxID: {trx_id}', 'is_active' => true],
            ['name' => 'New Client Welcome', 'type' => 'welcome', 'content' => 'Welcome to {company_name}! Your Client ID: {client_id}. Package: {package_name}. Support: {support_number}', 'is_active' => true],
            ['name' => 'Package Change', 'type' => 'package_change', 'content' => 'Dear {client_name}, your package has been changed to {package_name} ({bandwidth}). New monthly bill: BDT {amount}', 'is_active' => true],
            ['name' => 'Ticket Created', 'type' => 'ticket_created', 'content' => 'Dear {client_name}, your support ticket #{ticket_id} has been created. We will respond shortly.', 'is_active' => true],
            ['name' => 'Ticket Resolved', 'type' => 'ticket_resolved', 'content' => 'Dear {client_name}, your support ticket #{ticket_id} has been resolved. Thank you for your patience.', 'is_active' => true],
            ['name' => 'Technician Assigned', 'type' => 'technician_assigned', 'content' => 'Dear {client_name}, technician {technician_name} has been assigned to your ticket #{ticket_id}. Contact: {technician_phone}', 'is_active' => true],
            ['name' => 'Device Down Alert', 'type' => 'device_down', 'content' => 'ALERT: Device {device_name} ({device_ip}) is DOWN since {down_time}. Immediate attention required!', 'is_active' => true],
            ['name' => 'Device Up Alert', 'type' => 'device_up', 'content' => 'INFO: Device {device_name} ({device_ip}) is back ONLINE. Downtime: {downtime_duration}', 'is_active' => true],
            ['name' => 'Birthday Wish', 'type' => 'birthday', 'content' => 'Dear {client_name}, wishing you a very Happy Birthday! Thank you for being with {company_name}.', 'is_active' => true],
            ['name' => 'Password Reset', 'type' => 'password_reset', 'content' => 'Dear {client_name}, your password has been reset. New password: {password}. Please change it after login.', 'is_active' => true],
            ['name' => 'Connection Activated', 'type' => 'connection_activated', 'content' => 'Dear {client_name}, your internet connection is now active. Username: {username}. Enjoy your service!', 'is_active' => true],
            ['name' => 'Line Shift', 'type' => 'line_shift', 'content' => 'Dear {client_name}, your connection has been shifted to new location as requested. Installation charge: BDT {amount}', 'is_active' => true],
            ['name' => 'Partial Payment', 'type' => 'partial_payment', 'content' => 'Dear {client_name}, we received BDT {amount}. Remaining due: BDT {remaining}. Please clear dues by {due_date}.', 'is_active' => true],
            ['name' => 'Advance Payment', 'type' => 'advance_payment', 'content' => 'Dear {client_name}, we received advance payment of BDT {amount}. Your credit balance: BDT {credit}.', 'is_active' => true],
            ['name' => 'Expiry Warning', 'type' => 'expiry_warning', 'content' => 'Dear {client_name}, your package expires on {expiry_date}. Please renew to continue service.', 'is_active' => true],
            ['name' => 'Maintenance Notice', 'type' => 'maintenance', 'content' => 'Dear Customer, scheduled maintenance on {date} from {start_time} to {end_time}. Service may be interrupted.', 'is_active' => true],
            ['name' => 'Offer/Promotion', 'type' => 'promotion', 'content' => '{message}', 'is_active' => true],
            ['name' => 'Custom Message', 'type' => 'custom', 'content' => '{message}', 'is_active' => true],
        ];

        foreach ($templates as $template) {
            $template['code'] = $template['type'];
            SmsTemplate::updateOrCreate(
                ['type' => $template['type']],
                $template
            );
        }
    }
}
