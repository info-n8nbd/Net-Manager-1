<?php

namespace App\Services;

use App\Models\Client;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class NotificationService
{
    protected array $channels = ['sms', 'whatsapp', 'telegram', 'email', 'push'];

    public function send(string $type, string $channel, array $recipient, string $message, string $priority = 'normal'): bool
    {
        $notificationId = DB::table('notification_queue')->insertGetId([
            'type' => $type,
            'channel' => $channel,
            'recipient_type' => $recipient['type'],
            'recipient_id' => $recipient['id'] ?? null,
            'recipient_phone' => $recipient['phone'] ?? null,
            'recipient_email' => $recipient['email'] ?? null,
            'title' => $recipient['title'] ?? $type,
            'message' => $message,
            'priority' => $priority,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($priority === 'critical') {
            return $this->sendImmediately($notificationId);
        }

        return true;
    }

    public function sendImmediately(int $notificationId): bool
    {
        $notification = DB::table('notification_queue')->find($notificationId);
        if (!$notification) return false;

        try {
            $result = match ($notification->channel) {
                'sms' => $this->sendSms($notification),
                'whatsapp' => $this->sendWhatsApp($notification),
                'telegram' => $this->sendTelegram($notification),
                'email' => $this->sendEmail($notification),
                'push' => $this->sendPush($notification),
                'all' => $this->sendToAllChannels($notification),
                default => false,
            };

            DB::table('notification_queue')
                ->where('id', $notificationId)
                ->update([
                    'status' => $result ? 'sent' : 'failed',
                    'sent_at' => $result ? now() : null,
                    'updated_at' => now(),
                ]);

            return $result;

        } catch (\Exception $e) {
            Log::error("Notification send failed: " . $e->getMessage());
            DB::table('notification_queue')
                ->where('id', $notificationId)
                ->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
            return false;
        }
    }

    protected function sendSms(object $notification): bool
    {
        $phone = $notification->recipient_phone;
        if (!$phone && $notification->recipient_id) {
            if ($notification->recipient_type === 'client') {
                $client = Client::find($notification->recipient_id);
                $phone = $client?->phone;
            } else {
                $user = User::find($notification->recipient_id);
                $phone = $user?->phone;
            }
        }

        if (!$phone) return false;

        DB::table('sms_logs')->insert([
            'phone' => $phone,
            'message' => $notification->message,
            'type' => $notification->type,
            'status' => 'sent',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return true;
    }

    protected function sendWhatsApp(object $notification): bool
    {
        $phone = $notification->recipient_phone;
        if (!$phone && $notification->recipient_id) {
            if ($notification->recipient_type === 'client') {
                $client = Client::find($notification->recipient_id);
                $phone = $client?->phone;
            } else {
                $user = User::find($notification->recipient_id);
                $phone = $user?->phone;
            }
        }

        if (!$phone) return false;

        DB::table('sms_logs')->insert([
            'phone' => $phone,
            'message' => $notification->message,
            'type' => 'whatsapp',
            'status' => 'sent',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return true;
    }

    protected function sendTelegram(object $notification): bool
    {
        DB::table('sms_logs')->insert([
            'phone' => 'telegram',
            'message' => $notification->message,
            'type' => 'telegram',
            'status' => 'sent',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return true;
    }

    protected function sendEmail(object $notification): bool
    {
        return true;
    }

    protected function sendPush(object $notification): bool
    {
        return true;
    }

    protected function sendToAllChannels(object $notification): bool
    {
        $results = [];
        foreach (['sms', 'whatsapp', 'telegram'] as $channel) {
            $notification->channel = $channel;
            $results[] = match ($channel) {
                'sms' => $this->sendSms($notification),
                'whatsapp' => $this->sendWhatsApp($notification),
                'telegram' => $this->sendTelegram($notification),
                default => false,
            };
        }
        return in_array(true, $results);
    }

    public function broadcastAreaOutage(int $zoneId, string $message): int
    {
        $clients = Client::where('zone_id', $zoneId)
            ->where('status', 'active')
            ->whereNotNull('phone')
            ->get();

        $sent = 0;
        foreach ($clients as $client) {
            $this->send('area_outage', 'sms', [
                'type' => 'client',
                'id' => $client->id,
                'phone' => $client->phone,
                'title' => 'Network Update',
            ], $message, 'high');
            $sent++;
        }

        return $sent;
    }

    public function sendFiberCutAlert(array $alertData): void
    {
        $technicians = User::role('technician')
            ->whereHas('zones', fn($q) => $q->where('zones.id', $alertData['zone_id'] ?? 0))
            ->get();

        $location = $alertData['location'] ?? 'unknown location';
        $offlineCount = $alertData['offline_count'] ?? 0;

        foreach ($technicians as $tech) {
            $this->send('fiber_cut', 'all', [
                'type' => 'technician',
                'id' => $tech->id,
                'phone' => $tech->phone,
                'title' => 'Fiber Cut Alert',
            ], "Fiber cut suspected at {$location}. Multiple ONUs offline.", 'critical');
        }

        $admins = User::role('admin')->get();
        foreach ($admins as $admin) {
            $this->send('fiber_cut', 'all', [
                'type' => 'admin',
                'id' => $admin->id,
                'phone' => $admin->phone,
                'title' => 'Fiber Cut Alert',
            ], "Fiber cut suspected. {$offlineCount} ONUs affected.", 'critical');
        }
    }

    public function sendDueReminder(int $clientId): void
    {
        $client = Client::find($clientId);
        if (!$client || !$client->phone) return;

        $due = DB::table('bills')
            ->where('client_id', $clientId)
            ->where('status', 'unpaid')
            ->sum('total_amount');

        if ($due > 0) {
            $this->send('due_reminder', 'sms', [
                'type' => 'client',
                'id' => $clientId,
                'phone' => $client->phone,
                'title' => 'Payment Due',
            ], "Dear {$client->name}, your internet bill of ৳{$due} is due. Please pay to avoid service interruption.", 'high');
        }
    }

    public function sendTechnicianJobAlert(int $technicianId, string $jobType, string $location): void
    {
        $tech = User::find($technicianId);
        if (!$tech) return;

        $this->send('job_assigned', 'all', [
            'type' => 'technician',
            'id' => $technicianId,
            'phone' => $tech->phone,
            'title' => 'New Job Assigned',
        ], "New {$jobType} job assigned at {$location}. Please respond immediately.", 'high');
    }

    public function sendTicketAssignmentNotification(int $technicianId, array $ticketData): void
    {
        $tech = User::find($technicianId);
        if (!$tech) return;

        $priority = ucfirst($ticketData['priority']);
        $message = "New ticket #{$ticketData['ticket_no']} assigned to you.\n";
        $message .= "Client: {$ticketData['client_name']}\n";
        $message .= "Issue: {$ticketData['subject']}\n";
        $message .= "Priority: {$priority}\n";
        $message .= "Location: {$ticketData['address']}\n";
        $message .= "Phone: {$ticketData['phone']}";

        DB::table('notifications')->insert([
            'user_id' => $technicianId,
            'type' => 'ticket_assigned',
            'title' => "Ticket #{$ticketData['ticket_no']} Assigned",
            'message' => $message,
            'data' => json_encode($ticketData),
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $userId = $ticketData['user_id'] ?? 'N/A';
        $openedBy = $ticketData['opened_by'] ?? 'System';
        $bdTime = now()->timezone('Asia/Dhaka')->format('d-m-y h:i A');
        $telegramMsg = "🎫 *New Ticket Assigned*\n\n";
        $telegramMsg .= "🆔 ID: {$userId}\n";
        $telegramMsg .= "👷 Assigned: {$tech->name}\n";
        $telegramMsg .= "📝 Issue: {$ticketData['subject']}\n";
        $telegramMsg .= "⚡ Priority: {$priority}\n";
        $telegramMsg .= "📍 Location: {$ticketData['address']}\n";
        $telegramMsg .= "📞 Phone: {$ticketData['phone']}\n";
        $telegramMsg .= "⏰ Time: {$bdTime}\n";
        $telegramMsg .= "\n_Opened by: {$openedBy}_\n_SOFTYNEX_";

        $this->sendToTicketGroup($telegramMsg);

        $this->send('ticket_assigned', 'sms', [
            'type' => 'technician',
            'id' => $technicianId,
            'phone' => $tech->phone,
            'title' => 'Ticket Assigned',
        ], "SOFTYNEX: Ticket #{$ticketData['ticket_no']} assigned. Client: {$ticketData['client_name']}, Priority: {$priority}", 'high');
    }

    public function sendNewConnectionTicketNotification(array $technicianIds, array $ticketData): void
    {
        $techNames = [];
        $technicians = [];
        foreach ($technicianIds as $techId) {
            $tech = User::find($techId);
            if ($tech) {
                $techNames[] = $tech->name;
                $technicians[] = $tech;
                
                DB::table('notifications')->insert([
                    'user_id' => $techId,
                    'type' => 'new_connection_assigned',
                    'title' => "New Connection - {$ticketData['client_name']}",
                    'message' => "New connection assigned. Client: {$ticketData['client_name']}, Address: {$ticketData['address']}",
                    'data' => json_encode($ticketData),
                    'status' => 'pending',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $openedBy = $ticketData['opened_by'] ?? 'System';
        $bdTime = now()->timezone('Asia/Dhaka')->format('d-m-y h:i A');
        $installCharge = $ticketData['installation_charge'] ?? '0';
        $assignedTo = implode(', ', $techNames);
        
        $packageInfo = $ticketData['package'] ?? null;
        $connectionTime = $ticketData['connection_time'] ?? null;
        
        $telegramMsg = "🔌 *NEW CONNECTION REQUEST*\n";
        $telegramMsg .= "━━━━━━━━━━━━━━━━━━━━━\n";
        $telegramMsg .= "👤 *Name:* `{$ticketData['client_name']}`\n";
        $telegramMsg .= "📍 *Address:* `{$ticketData['address']}`\n";
        $telegramMsg .= "📞 *Phone:* `{$ticketData['phone']}`\n";
        
        if ($packageInfo) {
            $telegramMsg .= "📦 *Package:* {$packageInfo}\n";
        }
        
        $installDisplay = is_numeric($installCharge) ? "৳{$installCharge}" : $installCharge;
        $telegramMsg .= "💰 *Installation:* `{$installDisplay}`\n";
        
        if ($connectionTime) {
            $connectionTimeFormatted = \Carbon\Carbon::parse($connectionTime)->format('d-m-y h:i A');
            $telegramMsg .= "🕐 *Conn. Time:* `{$connectionTimeFormatted}`\n";
        }
        
        $telegramMsg .= "━━━━━━━━━━━━━━━━━━━━━\n";
        $telegramMsg .= "👷 *Assigned To:* {$assignedTo}\n";
        $telegramMsg .= "⏰ *Created:* {$bdTime}\n";
        $telegramMsg .= "👤 *Opened By:* {$openedBy}\n";
        $telegramMsg .= "━━━━━━━━━━━━━━━━━━━━━\n";
        $telegramMsg .= "🌐 _SOFTYNEX Management System_";

        $this->sendToTicketGroup($telegramMsg);
        
        foreach ($technicians as $tech) {
            if (!empty($tech->telegram_id)) {
                $personalMsg = "🔔 *You've been assigned!*\n\n" . $telegramMsg;
                $this->sendTelegramDirect($tech->telegram_id, $personalMsg, 'ticket');
            }
        }
    }

    public function sendMultiTechnicianTicketNotification(array $technicianIds, array $ticketData): void
    {
        $techNames = [];
        $technicians = [];
        foreach ($technicianIds as $techId) {
            $tech = User::find($techId);
            if ($tech) {
                $techNames[] = $tech->name;
                $technicians[] = $tech;
                
                DB::table('notifications')->insert([
                    'user_id' => $techId,
                    'type' => 'ticket_assigned',
                    'title' => "Ticket Assigned",
                    'message' => "Ticket assigned. Client: {$ticketData['client_name']}, Issue: {$ticketData['subject']}",
                    'data' => json_encode($ticketData),
                    'status' => 'pending',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $ticketNo  = $ticketData['ticket_no'] ?? ('ISP-' . str_pad($ticketData['user_id'] ?? '0', 5, '0', STR_PAD_LEFT));
        $openedBy  = $ticketData['opened_by'] ?? 'System';
        $bdTime    = now()->timezone('Asia/Dhaka')->format('d-m-y h:i A');
        $priority  = ucfirst($ticketData['priority']);
        $assignedTo = implode(', ', $techNames);

        $pppoeUser   = $ticketData['pppoe_user'] ?? '';
        $clientLabel = $pppoeUser
            ? "{$ticketData['client_name']} ({$pppoeUser})"
            : $ticketData['client_name'];

        $description = $ticketData['description'] ?? '';
        $descLine    = $description
            ? "📋 *Description:* " . mb_substr($description, 0, 200) . (mb_strlen($description) > 200 ? '…' : '') . "\n"
            : '';

        $telegramMsg = "🎫 *NEW TICKET ASSIGNED*\n";
        $telegramMsg .= "━━━━━━━━━━━━━━━━━━━━━\n";
        $telegramMsg .= "🆔 *Ticket ID:* `{$ticketNo}`\n";
        $telegramMsg .= "📝 *Issue:* `{$ticketData['subject']}`\n";
        $telegramMsg .= "⚡ *Priority:* `{$priority}`\n";
        $telegramMsg .= $descLine;
        $telegramMsg .= "━━━━━━━━━━━━━━━━━━━━━\n";
        $telegramMsg .= "👤 *Client:* `{$clientLabel}`\n";
        $telegramMsg .= "📍 *Location:* `{$ticketData['address']}`\n";
        $telegramMsg .= "📞 *Phone:* `{$ticketData['phone']}`\n";
        $telegramMsg .= "━━━━━━━━━━━━━━━━━━━━━\n";
        $telegramMsg .= "👷 *Assigned To:* {$assignedTo}\n";
        $telegramMsg .= "⏰ *Time:* {$bdTime}\n";
        $telegramMsg .= "🖊 *Opened By:* {$openedBy}\n";
        $telegramMsg .= "━━━━━━━━━━━━━━━━━━━━━\n";
        $telegramMsg .= "🌐 _SOFTYNEX Management System_";

        $this->sendToTicketGroup($telegramMsg);
        
        foreach ($technicians as $tech) {
            if (!empty($tech->telegram_id)) {
                $personalMsg = "🔔 *You've been assigned!*\n\n" . $telegramMsg;
                $this->sendTelegramDirect($tech->telegram_id, $personalMsg, 'ticket');
            }
        }
    }

    protected function sendTelegramDirect(string $chatId, string $message, string $botType = 'ticket'): bool
    {
        $tokenKey = $botType === 'alert' ? 'telegram_alert_bot_token' : 'telegram_ticket_bot_token';
        $botToken = DB::table('system_settings')->where('key', $tokenKey)->value('value');
        if (!$botToken) return false;

        try {
            $response = Http::post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'Markdown',
            ]);
            return $response->successful();
        } catch (\Exception $e) {
            Log::error("Telegram send failed: " . $e->getMessage());
            return false;
        }
    }

    public function sendToTicketGroup(string $message): bool
    {
        $groupId = DB::table('system_settings')->where('key', 'telegram_ticket_group_id')->value('value');
        if (!$groupId) return false;
        return $this->sendTelegramDirect($groupId, $message, 'ticket');
    }

    public function sendToAlertGroup(string $message): bool
    {
        $groupId = DB::table('system_settings')->where('key', 'telegram_alert_group_id')->value('value');
        if (!$groupId) return false;
        return $this->sendTelegramDirect($groupId, $message, 'alert');
    }

    public function sendClientConnectionAlert(string $userId, string $status, ?string $reason = null, ?int $clientId = null): void
    {
        $emoji = $status === 'connected' ? '🟢' : '🔴';
        $statusText = $status === 'connected' ? 'Client Connected' : 'Client Disconnected';
        $time = now()->timezone('Asia/Dhaka')->format('d-m-y h:i A');
        
        $message = "{$emoji} *{$statusText}*\n\n";
        $message .= "🆔 ID: {$userId}\n";
        $message .= "⏰ Time: {$time}\n";
        if ($reason) {
            $message .= "📝 Reason: {$reason}\n";
        }
        
        // Only fetch and show ONU data if client ID is provided
        if ($clientId) {
            $onuData = $this->fetchRealTimeOnuData($clientId);
            if ($onuData) {
                $message .= "\n📡 *ONU Information*\n";
                $onuStatus = $onuData['online'] ? '🟢 Online' : '🔴 Offline';
                $message .= "Status: {$onuStatus}\n";
                
                if ($onuData['rx_power']) {
                    $message .= "Signal: {$onuData['rx_power']} dBm\n";
                }
                
                // Only show down reason if ONU is actually offline (not manual disconnect)
                if (!$onuData['online'] && $onuData['down_reason'] && $reason !== 'Payment overdue' && $reason !== 'Manual disconnect') {
                    $downReason = $onuData['down_reason'];
                    if ($downReason === 'power_off' || $downReason === 'dying_gasp') {
                        $message .= "⚡ Down: Power Off\n";
                    } elseif ($downReason === 'los' || $downReason === 'wire_down') {
                        $message .= "🔌 Down: Wire Down/LOS\n";
                    }
                }
            }
        }
        
        $message .= "\n_SOFTYNEX_";

        $this->sendToAlertGroup($message);
    }

    protected function fetchRealTimeOnuData(int $clientId): ?array
    {
        // Fetch client's ONU information from database
        $client = DB::table('clients')->where('id', $clientId)->first();
        if (!$client || !$client->onu_serial) {
            return null;
        }

        // Try to get real-time data from onu_status table (updated by polling job)
        $onuStatus = DB::table('onu_status')
            ->where('onu_serial', $client->onu_serial)
            ->orderBy('updated_at', 'desc')
            ->first();

        if ($onuStatus) {
            return [
                'online' => (bool) $onuStatus->online,
                'rx_power' => $onuStatus->rx_power ?? null,
                'tx_power' => $onuStatus->tx_power ?? null,
                'down_reason' => $onuStatus->down_reason ?? null,
            ];
        }

        // Fallback: check client's stored ONU data
        if (isset($client->onu_rx_power)) {
            return [
                'online' => $client->connection_status === 'active',
                'rx_power' => $client->onu_rx_power ?? null,
                'down_reason' => null,
            ];
        }

        return null;
    }

    public function sendPaymentNotification(int $clientId, array $paymentData): void
    {
        $client = Client::find($clientId);
        if (!$client) return;

        $message = "SOFTYNEX: Payment of ৳{$paymentData['amount']} received successfully.\n";
        $message .= "TrxID: {$paymentData['transaction_id']}\n";
        $message .= "Method: {$paymentData['method']}\n";
        $message .= "Date: {$paymentData['date']}\n";
        $message .= "Thank you for your payment!";

        $this->send('payment_received', 'sms', [
            'type' => 'client',
            'id' => $clientId,
            'phone' => $client->phone,
            'title' => 'Payment Confirmation - SOFTYNEX',
        ], $message, 'high');
    }

    public function sendPaymentNotificationToAdmin(array $paymentData): void
    {
        $admins = User::role('admin')->get();
        
        $message = "SOFTYNEX: New payment received!\n";
        $message .= "Client: {$paymentData['client_name']}\n";
        $message .= "Amount: ৳{$paymentData['amount']}\n";
        $message .= "Method: {$paymentData['method']}\n";
        $message .= "TrxID: {$paymentData['transaction_id']}";

        foreach ($admins as $admin) {
            $this->send('payment_admin_alert', 'all', [
                'type' => 'admin',
                'id' => $admin->id,
                'phone' => $admin->phone,
                'title' => 'Payment Alert - SOFTYNEX',
            ], $message, 'normal');
        }
    }

    public function sendAdminFraudAlert(string $alertType, array $data): void
    {
        $admins = User::role('admin')->get();

        foreach ($admins as $admin) {
            $this->send('fraud_alert', 'all', [
                'type' => 'admin',
                'id' => $admin->id,
                'phone' => $admin->phone,
                'title' => 'Fraud Alert',
            ], "Suspicious activity detected: {$alertType}. Details: " . json_encode($data), 'critical');
        }
    }

    public function processPendingNotifications(): int
    {
        $pending = DB::table('notification_queue')
            ->where('status', 'pending')
            ->where('created_at', '>=', now()->subHours(24))
            ->orderBy('priority', 'desc')
            ->limit(100)
            ->get();

        $processed = 0;
        foreach ($pending as $notification) {
            if ($this->sendImmediately($notification->id)) {
                $processed++;
            }
        }

        return $processed;
    }
}
