<?php

namespace App\Services;

use App\Models\Client;
use App\Models\ClientActivityLog;
use App\Models\ClientStatusLog;

class ClientActivityService
{
    public static function log(
        Client $client,
        string $actionType,
        ?string $oldValue = null,
        ?string $newValue = null,
        ?string $reason = null,
        string $triggeredBy = 'manual',
        ?int $performedBy = null,
        ?array $extraData = null
    ): ClientActivityLog {
        return ClientActivityLog::create([
            'client_id' => $client->id,
            'action_type' => $actionType,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'reason' => $reason,
            'triggered_by' => $triggeredBy,
            'performed_by' => $performedBy ?? auth()->id(),
            'ip_address' => request()->ip(),
            'extra_data' => $extraData
        ]);
    }

    public static function logStatusChange(
        Client $client,
        string $oldStatus,
        string $newStatus,
        ?string $reason = null,
        string $triggeredBy = 'manual'
    ): ClientActivityLog {
        $actionType = match($newStatus) {
            'active' => ClientActivityLog::ACTION_ACTIVATED,
            'inactive' => ClientActivityLog::ACTION_DEACTIVATED,
            default => ClientActivityLog::ACTION_STATUS_CHANGE
        };

        ClientStatusLog::logStatusChange(
            client: $client,
            oldStatus: $oldStatus,
            newStatus: $newStatus,
            reason: $reason,
            source: $triggeredBy === 'auto' ? ClientStatusLog::SOURCE_AUTO : ClientStatusLog::SOURCE_MANUAL,
            changedBy: auth()->id()
        );

        return self::log(
            client: $client,
            actionType: $actionType,
            oldValue: $oldStatus,
            newValue: $newStatus,
            reason: $reason,
            triggeredBy: $triggeredBy,
            extraData: [
                'connection_date' => $client->connection_date?->format('Y-m-d H:i:s'),
                'expire_date' => $client->expire_date?->format('Y-m-d H:i:s')
            ]
        );
    }

    public static function logConnection(Client $client, ?string $reason = null): ClientActivityLog
    {
        return self::log(
            client: $client,
            actionType: ClientActivityLog::ACTION_CONNECTION_CREATED,
            newValue: 'active',
            reason: $reason ?? 'New connection activated',
            extraData: [
                'connection_date' => $client->connection_date?->format('Y-m-d H:i:s'),
                'package' => $client->package?->name,
                'monthly_fee' => $client->monthly_fee
            ]
        );
    }

    public static function logBillGenerated(Client $client, $bill): ClientActivityLog
    {
        return self::log(
            client: $client,
            actionType: ClientActivityLog::ACTION_BILL_GENERATED,
            newValue: $bill->bill_no,
            triggeredBy: auth()->check() ? 'manual' : 'system',
            extraData: [
                'bill_id' => $bill->id,
                'bill_no' => $bill->bill_no,
                'amount' => $bill->total_amount,
                'billing_month' => $bill->billing_month,
                'billing_year' => $bill->billing_year
            ]
        );
    }

    public static function logPaymentReceived(Client $client, $payment): ClientActivityLog
    {
        return self::log(
            client: $client,
            actionType: ClientActivityLog::ACTION_PAYMENT_RECEIVED,
            newValue: $payment->payment_no,
            extraData: [
                'payment_id' => $payment->id,
                'payment_no' => $payment->payment_no,
                'amount' => $payment->amount,
                'method' => $payment->paymentMethod?->name ?? 'Cash',
                'collector' => $payment->collectedBy?->name
            ]
        );
    }

    public static function logAutoReconnect(Client $client): ClientActivityLog
    {
        ClientStatusLog::logStatusChange(
            client: $client,
            oldStatus: 'inactive',
            newStatus: 'active',
            reason: 'Auto-reconnected after full payment',
            source: ClientStatusLog::SOURCE_AUTO,
            changedBy: null
        );

        return self::log(
            client: $client,
            actionType: ClientActivityLog::ACTION_RECONNECTED,
            oldValue: 'inactive',
            newValue: 'active',
            reason: 'Auto-reconnected after full payment',
            triggeredBy: 'auto',
            extraData: [
                'new_expire_date' => $client->expire_date?->format('Y-m-d')
            ]
        );
    }

    public static function logAutoDisconnect(Client $client, string $triggerSource = 'auto'): ClientActivityLog
    {
        ClientStatusLog::logStatusChange(
            client: $client,
            oldStatus: 'active',
            newStatus: 'inactive',
            reason: 'Auto-disconnected due to overdue bills',
            source: ClientStatusLog::SOURCE_AUTO,
            changedBy: null
        );

        return self::log(
            client: $client,
            actionType: ClientActivityLog::ACTION_DEACTIVATED,
            oldValue: 'active',
            newValue: 'inactive',
            reason: 'Auto-disconnected due to overdue bills',
            triggeredBy: $triggerSource,
            extraData: [
                'trigger' => $triggerSource
            ]
        );
    }
}
