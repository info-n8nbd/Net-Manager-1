<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    protected array $channels = [];

    protected static array $channelKeys = [
        'ticket',
        'alert',
        'onu_ping',
        'collection_transfer',
        'purchase',
        'sale',
        'inventory',
        'reseller_recharge',
        'expense',
        'client_status',
        'payment',
        'client_creation',
        'device_alert',
    ];

    public function __construct()
    {
        $alertToken = SystemSetting::get('telegram_alert_bot_token');
        $alertChatId = SystemSetting::get('telegram_alert_group_id');

        foreach (self::$channelKeys as $key) {
            $token = SystemSetting::get("telegram_{$key}_bot_token");
            $chatId = SystemSetting::get("telegram_{$key}_group_id");
            if ($token && $chatId) {
                $this->channels[$key] = ['token' => $token, 'chatId' => $chatId];
            } elseif ($alertToken && $alertChatId) {
                $this->channels[$key] = ['token' => $alertToken, 'chatId' => $alertChatId];
            }
        }

        $ticketToken = SystemSetting::get('telegram_ticket_bot_token');
        $ticketChatId = SystemSetting::get('telegram_ticket_group_id');
        if ($ticketToken && $ticketChatId) {
            $this->channels['ticket'] = ['token' => $ticketToken, 'chatId' => $ticketChatId];
        }
    }

    protected function send(string $message, string $botToken, string $chatId): bool
    {
        if (!$botToken || !$chatId) {
            return false;
        }

        try {
            $response = Http::post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'HTML',
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Telegram send error: ' . $e->getMessage());
            return false;
        }
    }

    protected function sendToChannel(string $channel, string $message): bool
    {
        if (!isset($this->channels[$channel])) {
            return false;
        }
        return $this->send($message, $this->channels[$channel]['token'], $this->channels[$channel]['chatId']);
    }

    public function sendAlertMessage(string $message): bool
    {
        return $this->sendToChannel('alert', $message);
    }

    public function sendTicketMessage(string $message): bool
    {
        return $this->sendToChannel('ticket', $message);
    }

    public function sendClientConnected(string $clientId, ?string $signal = null, ?string $onuStatus = null, string $clientName = '', string $pppoeUser = ''): bool
    {
        $message = "🟢 <b>Client Connected</b>\n\n";
        $message .= "🆔 ID: <code>{$clientId}</code>\n";
        if ($clientName) {
            $message .= "👤 Name: {$clientName}\n";
        }
        if ($pppoeUser) {
            $message .= "🌐 PPPoE: <code>{$pppoeUser}</code>\n";
        }
        $message .= "⏰ Time: " . now()->format('d-m-y h:i A') . "\n\n";
        $message .= "📡 <b>ONU Information</b>\n";
        $message .= "Status: 🟢 Online\n";
        if ($signal) {
            $message .= "Signal: {$signal} dBm\n";
        }
        $message .= "\n<b>SOFTYNEX</b>";
        return $this->sendToChannel('client_status', $message);
    }

    public function sendClientDisconnected(string $clientId, string $reason = 'Unknown', ?string $signal = null, ?string $onuStatus = 'Offline', ?string $downReason = null, string $clientName = '', string $pppoeUser = ''): bool
    {
        $message = "🔴 <b>Client Disconnected</b>\n\n";
        $message .= "🆔 ID: <code>{$clientId}</code>\n";
        if ($clientName) {
            $message .= "👤 Name: {$clientName}\n";
        }
        if ($pppoeUser) {
            $message .= "🌐 PPPoE: <code>{$pppoeUser}</code>\n";
        }
        $message .= "⏰ Time: " . now()->format('d-m-y h:i A') . "\n";
        $message .= "📝 Reason: {$reason}\n\n";
        $message .= "📡 <b>ONU Information</b>\n";
        $message .= "Status: " . ($onuStatus == 'Online' ? '🟢 Online' : '🔴 Offline') . "\n";
        if ($signal) {
            $message .= "Signal: {$signal} dBm\n";
        }
        if ($downReason) {
            $message .= "⚡ Down: {$downReason}\n";
        }
        $message .= "\n<b>SOFTYNEX</b>";
        return $this->sendToChannel('client_status', $message);
    }

    public function sendDeviceDownAlert(string $deviceName, string $deviceIp): bool
    {
        $message = "🔴 <b>DEVICE DOWN ALERT</b>\n\n";
        $message .= "Device: <b>{$deviceName}</b>\n";
        $message .= "IP: <code>{$deviceIp}</code>\n";
        $message .= "Time: " . now()->format('d-m-y h:i:s A') . "\n\n";
        $message .= "<b>SOFTYNEX</b>";
        return $this->sendToChannel('device_alert', $message);
    }

    public function sendDeviceUpAlert(string $deviceName, string $deviceIp, string $downtime = ''): bool
    {
        $message = "🟢 <b>DEVICE RESTORED</b>\n\n";
        $message .= "Device: <b>{$deviceName}</b>\n";
        $message .= "IP: <code>{$deviceIp}</code>\n";
        if ($downtime) {
            $message .= "Downtime: {$downtime}\n";
        }
        $message .= "Time: " . now()->format('d-m-y h:i:s A') . "\n\n";
        $message .= "<b>SOFTYNEX</b>";
        return $this->sendToChannel('device_alert', $message);
    }

    public function sendPaymentNotification(string $clientId, string $clientName, float $amount, string $method, string $pppoeUser = '', string $collectedBy = '', string $invoiceNo = '', float $dueAfter = 0, string $paymentDate = '', string $paymentRef = '', string $senderNumber = ''): bool
    {
        $message = "💰 <b>Payment Received</b>\n\n";
        $message .= "🆔 ID: <code>{$clientId}</code>\n";
        $message .= "👤 Client: {$clientName}\n";
        if ($pppoeUser) {
            $message .= "🌐 PPPoE: <code>{$pppoeUser}</code>\n";
        }
        if ($invoiceNo) {
            $message .= "🧾 Invoice: <code>{$invoiceNo}</code>\n";
        }
        $message .= "💵 Amount: ৳" . number_format($amount, 2) . "\n";
        $message .= "💳 Method: {$method}\n";
        if ($senderNumber) {
            $message .= "📱 Account/Number: <code>{$senderNumber}</code>\n";
        }
        if ($paymentRef) {
            $methodLower = strtolower($method);
            $refLabel = match(true) {
                in_array($methodLower, ['cash', 'hand_cash']) => 'Money Receipt No',
                in_array($methodLower, ['bank', 'bank_transfer']) => 'Account/Reference',
                in_array($methodLower, ['bkash', 'nagad', 'rocket', 'upay']) => 'Transaction ID',
                default => 'Reference',
            };
            $message .= "🔖 {$refLabel}: <code>{$paymentRef}</code>\n";
        }
        $message .= "📌 Due After: ৳" . number_format($dueAfter, 2) . "\n";
        if ($collectedBy) {
            $message .= "👤 Collected By: {$collectedBy}\n";
        }
        if ($paymentDate) {
            $message .= "📅 Date: {$paymentDate}\n";
        }
        $message .= "⏰ Time: " . now()->format('d-m-y h:i A') . "\n\n";
        $message .= "<b>SOFTYNEX</b>";
        return $this->sendToChannel('payment', $message);
    }

    public function sendTicketNotification(
        string $ticketNo,
        string $subject,
        string $clientId,
        string $clientName,
        string $priority = 'medium',
        ?string $assignedTo = null,
        ?string $location = null,
        ?string $phone = null,
        ?string $openedBy = null,
        string $pppoeUser = '',
        string $category = ''
    ): bool
    {
        $message = "🎫 <b>New Ticket Assigned</b>\n\n";
        $message .= "🔢 Ticket: <code>{$ticketNo}</code>\n";
        $message .= "🆔 Client ID: <code>{$clientId}</code>\n";
        $message .= "👤 Name: {$clientName}\n";
        if ($pppoeUser) {
            $message .= "🌐 PPPoE: <code>{$pppoeUser}</code>\n";
        }
        if ($category) {
            $message .= "📂 Category: {$category}\n";
        }
        $message .= "📝 Issue: {$subject}\n";
        $priorityEmoji = match(strtolower($priority)) {
            'high', 'urgent' => '🔴',
            'medium' => '🟡',
            'low' => '🟢',
            default => '⚪',
        };
        $message .= "⚡️ Priority: {$priorityEmoji} " . ucfirst($priority) . "\n";
        if ($assignedTo) {
            $message .= "👷 Assigned To: {$assignedTo}\n";
        }
        if ($location) {
            $message .= "📍 Location: {$location}\n";
        }
        if ($phone) {
            $message .= "📞 Phone: {$phone}\n";
        }
        if ($openedBy) {
            $message .= "🏪 Opened By: {$openedBy}\n";
        }
        $message .= "⏰ Time: " . now()->format('d-m-y h:i A') . "\n\n";
        $message .= "<b>SOFTYNEX</b>";
        return $this->sendTicketMessage($message);
    }

    public function sendOnuPingNotification(string $clientId, string $clientName, string $onuSN, string $status, ?string $signal = null, ?string $oltName = null, string $pppoeUser = ''): bool
    {
        $emoji = strtolower(trim($status)) === 'online' ? '🟢' : '🔴';
        $message = "📡 <b>ONU Ping Check</b>\n\n";
        $message .= "🆔 Client: <code>{$clientId}</code>\n";
        $message .= "👤 Name: {$clientName}\n";
        if ($pppoeUser) {
            $message .= "🌐 PPPoE: <code>{$pppoeUser}</code>\n";
        }
        $message .= "🔌 ONU: <code>{$onuSN}</code>\n";
        if ($oltName) {
            $message .= "🏢 OLT: {$oltName}\n";
        }
        $message .= "{$emoji} Status: {$status}\n";
        if ($signal) {
            $message .= "📶 Signal: {$signal} dBm\n";
        }
        $message .= "⏰ Time: " . now()->format('d-m-y h:i A') . "\n\n";
        $message .= "<b>SOFTYNEX</b>";
        return $this->sendToChannel('onu_ping', $message);
    }

    public function sendCollectionTransferNotification(string $transferNo, string $fromUser, string $toTarget, float $amount, string $status = 'pending', string $transferDate = '', string $remarks = '', string $verifiedBy = ''): bool
    {
        $emoji = $status === 'completed' ? '✅' : '⏳';
        $statusEmoji = match(strtolower($status)) {
            'completed', 'verified' => '🟢',
            'pending' => '🟡',
            'rejected' => '🔴',
            default => '⚪',
        };
        $message = "{$emoji} <b>Collection Transfer</b>\n\n";
        $message .= "🔢 Transfer: <code>{$transferNo}</code>\n";
        if ($transferDate) {
            $message .= "📅 Date: {$transferDate}\n";
        }
        $message .= "👤 From: {$fromUser}\n";
        $message .= "➡️ To: {$toTarget}\n";
        $message .= "💵 Amount: ৳" . number_format($amount, 2) . "\n";
        $message .= "📋 Status: {$statusEmoji} " . ucfirst($status) . "\n";
        if ($verifiedBy) {
            $message .= "✅ Verified By: {$verifiedBy}\n";
        }
        if ($remarks) {
            $message .= "📝 Remarks: {$remarks}\n";
        }
        $message .= "⏰ Time: " . now()->format('d-m-y h:i A') . "\n\n";
        $message .= "<b>SOFTYNEX</b>";
        return $this->sendToChannel('collection_transfer', $message);
    }

    public function sendPurchaseNotification(string $purchaseNo, string $vendorName, float $totalAmount, int $itemCount, string $createdBy, string $itemsList = '', string $purchaseDate = '', float $paidAmount = 0): bool
    {
        $message = "🛒 <b>New Purchase</b>\n\n";
        $message .= "🔢 Purchase: <code>{$purchaseNo}</code>\n";
        if ($purchaseDate) {
            $message .= "📅 Date: {$purchaseDate}\n";
        }
        $message .= "🏪 Vendor: {$vendorName}\n";
        if ($itemsList) {
            $message .= "📦 Items: {$itemsList}\n";
        } else {
            $message .= "📦 Items: {$itemCount}\n";
        }
        $message .= "💵 Total: ৳" . number_format($totalAmount, 2) . "\n";
        if ($paidAmount > 0) {
            $message .= "✅ Paid: ৳" . number_format($paidAmount, 2) . "\n";
            $message .= "📌 Due: ৳" . number_format($totalAmount - $paidAmount, 2) . "\n";
        }
        $message .= "👤 By: {$createdBy}\n";
        $message .= "⏰ Time: " . now()->format('d-m-y h:i A') . "\n\n";
        $message .= "<b>SOFTYNEX</b>";
        return $this->sendToChannel('purchase', $message);
    }

    public function sendSaleNotification(string $saleNo, string $clientName, float $totalAmount, int $itemCount, string $soldBy, string $itemsList = '', float $paidAmount = 0, float $dueAmount = 0, string $paymentMethod = '', string $status = '', string $saleDate = '', string $pppoeUser = '', string $paymentRef = '', string $senderNumber = ''): bool
    {
        $message = "🧾 <b>New Sale</b>\n\n";
        $message .= "🔢 Invoice: <code>{$saleNo}</code>\n";
        if ($saleDate) {
            $message .= "📅 Date: {$saleDate}\n";
        }
        $message .= "👤 Customer: {$clientName}\n";
        if ($pppoeUser) {
            $message .= "🌐 PPPoE: <code>{$pppoeUser}</code>\n";
        }
        if ($itemsList) {
            $message .= "📦 Items: {$itemsList}\n";
        } else {
            $message .= "📦 Items: {$itemCount}\n";
        }
        $message .= "💵 Total: ৳" . number_format($totalAmount, 2) . "\n";
        $message .= "✅ Paid: ৳" . number_format($paidAmount, 2) . "\n";
        $message .= "📌 Due: ৳" . number_format($dueAmount, 2) . "\n";
        if ($paymentMethod) {
            $message .= "💳 Payment: {$paymentMethod}\n";
        }
        if ($senderNumber) {
            $message .= "📱 Account/Number: <code>{$senderNumber}</code>\n";
        }
        if ($paymentRef) {
            $refLabel = match(strtolower($paymentMethod)) {
                'cash' => 'Money Receipt No',
                'bank', 'bank_transfer' => 'Account/Reference',
                'bkash', 'nagad', 'rocket', 'upay' => 'Transaction ID',
                default => 'Reference',
            };
            $message .= "🔖 {$refLabel}: <code>{$paymentRef}</code>\n";
        }
        if ($status) {
            $statusEmoji = match(strtolower($status)) {
                'paid' => '🟢',
                'partial' => '🟡',
                'unpaid' => '🔴',
                default => '⚪',
            };
            $message .= "📊 Status: {$statusEmoji} " . ucfirst($status) . "\n";
        }
        $message .= "🏪 Sold By: {$soldBy}\n";
        $message .= "⏰ Time: " . now()->format('d-m-y h:i A') . "\n\n";
        $message .= "<b>SOFTYNEX</b>";
        return $this->sendToChannel('sale', $message);
    }

    public function sendProductOutNotification(string $productName, int $quantity, string $reason, string $issuedTo, string $issuedBy, string $serialMac = '', string $technician = ''): bool
    {
        $message = "📤 <b>Product Out</b>\n\n";
        $message .= "📦 Product:\n{$productName}\n";
        if ($serialMac) {
            $message .= "🔖 ONU Serial/MAC: {$serialMac}\n";
        }
        $message .= "🔢 Total Quantity: {$quantity}\n";
        $message .= "📝 Reason: {$reason}\n";
        $message .= "👤 Issued To: {$issuedTo}\n";
        if ($technician) {
            $message .= "🔧 Technician: {$technician}\n";
        }
        $message .= "🏪 By: {$issuedBy}\n";
        $message .= "⏰ Time: " . now()->format('d-m-y h:i A') . "\n\n";
        $message .= "<b>SOFTYNEX</b>";
        return $this->sendToChannel('inventory', $message);
    }

    public function sendFiberOutNotification(string $fiberType, float $meters, string $purpose, string $usedBy, string $brand = '', string $technician = '', string $fiberCode = '', string $clientInfo = ''): bool
    {
        $message = "🔌 <b>Fiber/Cable Out</b>\n\n";
        $message .= "📦 Type: {$fiberType}\n";
        if ($brand) {
            $message .= "🏭 Brand: {$brand}\n";
        }
        if ($fiberCode) {
            $message .= "🔖 Code: {$fiberCode}\n";
        }
        $message .= "📏 Length: " . number_format($meters, 1) . " meters\n";
        $message .= "📝 Purpose: {$purpose}\n";
        if ($clientInfo) {
            $message .= "🏠 Client: {$clientInfo}\n";
        }
        if ($technician) {
            $message .= "🔧 Technician: {$technician}\n";
        }
        $message .= "👤 By: {$usedBy}\n";
        $message .= "⏰ Time: " . now()->format('d-m-y h:i A') . "\n\n";
        $message .= "<b>SOFTYNEX</b>";
        return $this->sendToChannel('inventory', $message);
    }

    public function sendResellerRechargeNotification(string $resellerName, string $clientId, string $clientName, float $amount, string $source = 'Manual', string $pppoeUser = '', float $balanceAfter = 0): bool
    {
        $message = "🔄 <b>Reseller Recharge</b>\n\n";
        $message .= "🏪 Reseller: {$resellerName}\n";
        $message .= "🆔 Client: <code>{$clientId}</code>\n";
        $message .= "👤 Name: {$clientName}\n";
        if ($pppoeUser) {
            $message .= "🌐 PPPoE: <code>{$pppoeUser}</code>\n";
        }
        $message .= "💵 Amount: ৳" . number_format($amount, 2) . "\n";
        if ($balanceAfter > 0) {
            $message .= "💰 Balance After: ৳" . number_format($balanceAfter, 2) . "\n";
        }
        $message .= "📱 Source: {$source}\n";
        $message .= "⏰ Time: " . now()->format('d-m-y h:i A') . "\n\n";
        $message .= "<b>SOFTYNEX</b>";
        return $this->sendToChannel('reseller_recharge', $message);
    }

    public function sendExpenseNotification(string $expenseHead, float $amount, string $createdBy, ?string $description = null, string $accountName = '', string $expenseDate = '', string $reference = ''): bool
    {
        $message = "💸 <b>New Expense</b>\n\n";
        $message .= "📋 Head: {$expenseHead}\n";
        $message .= "💵 Amount: ৳" . number_format($amount, 2) . "\n";
        if ($accountName) {
            $message .= "🏦 Account: {$accountName}\n";
        }
        if ($expenseDate) {
            $message .= "📅 Date: {$expenseDate}\n";
        }
        if ($reference) {
            $message .= "🔖 Reference: {$reference}\n";
        }
        if ($description) {
            $message .= "📝 Note: {$description}\n";
        }
        $message .= "👤 By: {$createdBy}\n";
        $message .= "⏰ Time: " . now()->format('d-m-y h:i A') . "\n\n";
        $message .= "<b>SOFTYNEX</b>";
        return $this->sendToChannel('expense', $message);
    }

    public function sendClientStatusNotification(string $clientId, string $clientName, string $oldStatus, string $newStatus, string $changedBy, string $pppoeUser = '', string $phone = '', string $package = '', string $reason = ''): bool
    {
        $statusEmojis = ['active' => '🟢', 'inactive' => '🔴', 'expired' => '⚠️', 'left' => '🚫', 'deleted' => '❌', 'suspended' => '⏸️'];
        $emoji = $statusEmojis[strtolower($newStatus)] ?? '🔄';
        $message = "{$emoji} <b>Client Status Changed</b>\n\n";
        $message .= "🆔 ID: <code>{$clientId}</code>\n";
        $message .= "👤 Name: {$clientName}\n";
        if ($pppoeUser) {
            $message .= "🌐 PPPoE: <code>{$pppoeUser}</code>\n";
        }
        if ($phone) {
            $message .= "📞 Phone: {$phone}\n";
        }
        if ($package) {
            $message .= "📦 Package: {$package}\n";
        }
        $message .= "📋 Status: {$oldStatus} ➡️ {$newStatus}\n";
        if ($reason) {
            $message .= "📝 Reason: {$reason}\n";
        }
        $message .= "👤 Changed By: {$changedBy}\n";
        $message .= "⏰ Time: " . now()->format('d-m-y h:i A') . "\n\n";
        $message .= "<b>SOFTYNEX</b>";
        return $this->sendToChannel('client_status', $message);
    }

    public function sendWebhookPaymentNotification(string $clientId, string $clientName, float $amount, string $gateway, string $transactionId, string $pppoeUser = '', float $dueAfter = 0, string $package = ''): bool
    {
        $message = "🌐 <b>Webhook Payment</b>\n\n";
        $message .= "🆔 Client: <code>{$clientId}</code>\n";
        $message .= "👤 Name: {$clientName}\n";
        if ($pppoeUser) {
            $message .= "🌐 PPPoE: <code>{$pppoeUser}</code>\n";
        }
        if ($package) {
            $message .= "📦 Package: {$package}\n";
        }
        $message .= "💵 Amount: ৳" . number_format($amount, 2) . "\n";
        $message .= "💳 Gateway: {$gateway}\n";
        $message .= "🔑 TxnID: <code>{$transactionId}</code>\n";
        $message .= "📌 Due After: ৳" . number_format($dueAfter, 2) . "\n";
        $message .= "⏰ Time: " . now()->format('d-m-y h:i A') . "\n\n";
        $message .= "<b>SOFTYNEX</b>";
        return $this->sendToChannel('payment', $message);
    }

    public function sendMfsPaymentNotification(string $clientId, string $clientName, float $amount, string $gateway, string $transactionId, string $pppoeUser = '', float $dueAfter = 0, string $package = ''): bool
    {
        $message = "📱 <b>Online MFS Payment</b>\n\n";
        $message .= "🆔 Client: <code>{$clientId}</code>\n";
        $message .= "👤 Name: {$clientName}\n";
        if ($pppoeUser) {
            $message .= "🌐 PPPoE: <code>{$pppoeUser}</code>\n";
        }
        if ($package) {
            $message .= "📦 Package: {$package}\n";
        }
        $message .= "💵 Amount: ৳" . number_format($amount, 2) . "\n";
        $message .= "💳 Gateway: {$gateway}\n";
        $message .= "🔑 TxnID: <code>{$transactionId}</code>\n";
        $message .= "📌 Due After: ৳" . number_format($dueAfter, 2) . "\n";
        $message .= "⏰ Time: " . now()->format('d-m-y h:i A') . "\n\n";
        $message .= "<b>SOFTYNEX</b>";
        return $this->sendToChannel('payment', $message);
    }

    public function sendClientCreationNotification(string $clientId, string $clientName, string $package, string $zone, string $createdBy, ?string $phone = null, string $pppoeUser = '', string $address = '', string $connectionType = '', float $monthlyFee = 0, string $mikrotikRouter = '', int $deadline = 0, float $installCharge = 0): bool
    {
        $message = "🆕 <b>New Client Created</b>\n\n";
        $message .= "🆔 ID: <code>{$clientId}</code>\n";
        $message .= "👤 Name: {$clientName}\n";
        if ($pppoeUser) {
            $message .= "🌐 PPPoE: <code>{$pppoeUser}</code>\n";
        }
        if ($phone) {
            $message .= "📞 Phone: {$phone}\n";
        }
        if ($address) {
            $message .= "🏠 Address: {$address}\n";
        }
        $message .= "📦 Package: {$package}\n";
        if ($monthlyFee > 0) {
            $message .= "💵 Monthly Fee: ৳" . number_format($monthlyFee, 2) . "\n";
        }
        if ($connectionType) {
            $message .= "🔗 Connection: {$connectionType}\n";
        }
        if ($mikrotikRouter) {
            $message .= "🖧 MikroTik: {$mikrotikRouter}\n";
        }
        if ($deadline > 0) {
            $message .= "⏳ Deadline: {$deadline} days\n";
        }
        if ($installCharge > 0) {
            $message .= "🔧 Install Charge: ৳" . number_format($installCharge, 2) . "\n";
        }
        $message .= "📍 Zone: {$zone}\n";
        $message .= "👤 Created By: {$createdBy}\n";
        $message .= "⏰ Time: " . now()->format('d-m-y h:i A') . "\n\n";
        $message .= "<b>SOFTYNEX</b>";
        return $this->sendToChannel('client_creation', $message);
    }

    public function sendNetworkDeviceAlert(string $deviceName, string $deviceType, string $host, string $status, string $location = '', int $affectedClients = 0, string $downtime = ''): bool
    {
        $isDown = strtolower($status) !== 'online' && strtolower($status) !== 'up';
        $emoji = $isDown ? '🔴' : '🟢';
        $statusLabel = $isDown ? 'DOWN' : 'UP';
        $message = "{$emoji} <b>Network Device {$statusLabel}</b>\n\n";
        $message .= "🖧 Device: {$deviceName}\n";
        $message .= "🏷️ Type: {$deviceType}\n";
        $message .= "🌐 Host: {$host}\n";
        if ($location) {
            $message .= "📍 Location: {$location}\n";
        }
        if ($affectedClients > 0) {
            $message .= "👥 Affected Clients: {$affectedClients}\n";
        }
        if ($isDown) {
            $message .= "⚠️ Status: Unreachable\n";
        } else {
            $message .= "✅ Status: Online\n";
            if ($downtime) {
                $message .= "⏱️ Downtime: {$downtime}\n";
            }
        }
        $message .= "⏰ Time: " . now()->format('d-m-y h:i A') . "\n\n";
        $message .= "<b>SOFTYNEX</b>";
        return $this->sendToChannel('device_alert', $message);
    }

    public function sendOnuDownAlert(
        string $onuSerial,
        string $ponPort,
        string $onuNumber,
        ?float $rxPower,
        ?float $txPower,
        string $downReason,
        string $vendor,
        string $model,
        string $clientId,
        string $clientName,
        string $pppoeId,
        string $phone,
        string $oltName,
        string $address = '',
        string $area = ''
    ): bool {
        $rxStr = $rxPower !== null ? number_format($rxPower, 2) . ' dBm' : 'N/A';
        $txStr = $txPower !== null ? number_format($txPower, 2) . ' dBm' : 'N/A';

        $message  = "🔴 <b>ONU DOWN</b>\n";
        $message .= "🌐 PPPoE: <code>{$pppoeId}</code>\n";
        $message .= "🔖 Serial: <code>{$onuSerial}</code>\n";
        $message .= "🔌 Port: {$ponPort} | No: {$onuNumber}\n";
        $message .= "📶 RX: {$rxStr} | TX: {$txStr}\n";
        $message .= "⏰ " . now()->format('d-m-y h:i A');

        return $this->sendToChannel('onu_ping', $message);
    }

    public function sendOnuUpAlert(
        string $onuSerial,
        string $ponPort,
        string $onuNumber,
        ?float $rxPower,
        ?float $txPower,
        string $clientId,
        string $clientName,
        string $pppoeId,
        string $oltName,
        string $downtime = ''
    ): bool {
        $rxStr = $rxPower !== null ? number_format($rxPower, 2) . ' dBm' : 'N/A';
        $txStr = $txPower !== null ? number_format($txPower, 2) . ' dBm' : 'N/A';

        $message  = "🟢 <b>ONU UP</b>\n";
        $message .= "🌐 PPPoE: <code>{$pppoeId}</code>\n";
        $message .= "🔖 Serial: <code>{$onuSerial}</code>\n";
        $message .= "🔌 Port: {$ponPort} | No: {$onuNumber}\n";
        $message .= "📶 RX: {$rxStr} | TX: {$txStr}\n";
        if ($downtime) $message .= "⏱️ Down was: {$downtime}\n";
        $message .= "⏰ " . now()->format('d-m-y h:i A');

        return $this->sendToChannel('onu_ping', $message);
    }

    public function sendTestMessage(string $type = 'alert'): array
    {
        $channel = $this->channels[$type] ?? null;

        if (!$channel) {
            return ['success' => false, 'message' => ucfirst(str_replace('_', ' ', $type)) . ' bot not configured'];
        }

        $botToken = $channel['token'];
        $chatId = $channel['chatId'];

        $label = ucfirst(str_replace('_', ' ', $type));
        $testMessage = "✅ <b>SOFTYNEX Test Message</b>\n\n";
        $testMessage .= "Type: {$label} Notifications\n";
        $testMessage .= "This is a test notification from your ISP Billing System.\n";
        $testMessage .= "Time: " . now()->format('d-m-y h:i:s A') . " (BDT)\n\n";
        $testMessage .= "If you received this message, your Telegram integration is working correctly! 🎉\n\n";
        $testMessage .= "<b>SOFTYNEX</b>";

        try {
            $response = Http::post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $testMessage,
                'parse_mode' => 'HTML',
            ]);

            if ($response->successful()) {
                return ['success' => true, 'message' => 'Test message sent successfully!'];
            } else {
                $error = $response->json('description', 'Unknown error');
                return ['success' => false, 'message' => 'Telegram API error: ' . $error];
            }
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    public static function getChannelKeys(): array
    {
        return self::$channelKeys;
    }
}
