<?php

namespace App\Services;

use App\Models\Client;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RealTimeAlertService
{
    public function createAlert(array $data): int
    {
        return DB::table('real_time_alerts')->insertGetId([
            'alert_type' => $data['alert_type'],
            'severity' => $data['severity'] ?? 'info',
            'title' => $data['title'],
            'message' => $data['message'] ?? null,
            'client_id' => $data['client_id'] ?? null,
            'zone_id' => $data['zone_id'] ?? null,
            'device_id' => $data['device_id'] ?? null,
            'device_type' => $data['device_type'] ?? null,
            'metadata' => isset($data['metadata']) ? json_encode($data['metadata']) : null,
            'target_role' => $data['target_role'] ?? null,
            'target_user_id' => $data['target_user_id'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function createClientDownAlert(Client $client): void
    {
        $this->createAlert([
            'alert_type' => 'client_down',
            'severity' => 'warning',
            'title' => 'Client Connection Lost',
            'message' => "Client {$client->name} ({$client->client_id}) went offline",
            'client_id' => $client->id,
            'zone_id' => $client->zone_id,
            'target_role' => 'technician',
            'metadata' => [
                'pppoe_user' => $client->pppoe_username,
                'last_seen' => $client->last_seen_at,
            ],
        ]);
    }

    public function createAreaOutageAlert(int $zoneId, int $affectedCount, array $clientIds = []): void
    {
        $zone = DB::table('zones')->find($zoneId);
        
        $this->createAlert([
            'alert_type' => 'area_outage',
            'severity' => 'critical',
            'title' => 'Area Outage Detected',
            'message' => "Multiple clients offline in {$zone->name}: {$affectedCount} affected",
            'zone_id' => $zoneId,
            'target_role' => 'admin',
            'metadata' => [
                'affected_count' => $affectedCount,
                'sample_clients' => array_slice($clientIds, 0, 10),
            ],
        ]);
    }

    public function createFiberCutAlert(int $oltDeviceId, int $port, int $affectedOnus): void
    {
        $olt = DB::table('olt_devices')->find($oltDeviceId);
        
        $this->createAlert([
            'alert_type' => 'fiber_cut_suspected',
            'severity' => 'critical',
            'title' => 'Possible Fiber Cut Detected',
            'message' => "Multiple ONUs offline on {$olt->name} Port {$port}: {$affectedOnus} ONUs affected",
            'device_id' => $oltDeviceId,
            'device_type' => 'olt',
            'target_role' => 'admin',
            'metadata' => [
                'port' => $port,
                'affected_onus' => $affectedOnus,
            ],
        ]);
    }

    public function createRouterDownAlert(int $mikrotikDeviceId): void
    {
        $router = DB::table('mikrotik_devices')->find($mikrotikDeviceId);
        
        $this->createAlert([
            'alert_type' => 'router_down',
            'severity' => 'critical',
            'title' => 'Router Offline',
            'message' => "MikroTik router {$router->name} ({$router->ip_address}) is not responding",
            'device_id' => $mikrotikDeviceId,
            'device_type' => 'mikrotik',
            'target_role' => 'admin',
            'metadata' => [
                'ip_address' => $router->ip_address,
                'last_seen' => $router->last_sync_at,
            ],
        ]);
    }

    public function createDueReminderAlert(Client $client, int $daysRemaining): void
    {
        $this->createAlert([
            'alert_type' => 'due_reminder',
            'severity' => $daysRemaining <= 0 ? 'critical' : 'warning',
            'title' => $daysRemaining <= 0 ? 'Payment Overdue' : 'Payment Due Soon',
            'message' => $daysRemaining <= 0 
                ? "Payment overdue for {$client->name}: ৳" . number_format($client->due_amount)
                : "Payment due in {$daysRemaining} days for {$client->name}: ৳" . number_format($client->due_amount),
            'client_id' => $client->id,
            'target_role' => 'client',
            'target_user_id' => $client->user_id,
            'metadata' => [
                'due_amount' => $client->due_amount,
                'days_remaining' => $daysRemaining,
                'due_date' => $client->due_date,
            ],
        ]);
    }

    public function createTechnicianJobAlert(int $userId, int $ticketId, string $message): void
    {
        $this->createAlert([
            'alert_type' => 'technician_job',
            'severity' => 'info',
            'title' => 'New Job Assignment',
            'message' => $message,
            'target_role' => 'technician',
            'target_user_id' => $userId,
            'metadata' => ['ticket_id' => $ticketId],
        ]);
    }

    public function createFraudAlert(int $paymentId, string $reason): void
    {
        $payment = DB::table('payments')->find($paymentId);
        
        $this->createAlert([
            'alert_type' => 'fraud_suspected',
            'severity' => 'critical',
            'title' => 'Suspicious Payment Detected',
            'message' => $reason,
            'client_id' => $payment->client_id ?? null,
            'target_role' => 'admin',
            'metadata' => [
                'payment_id' => $paymentId,
                'amount' => $payment->amount ?? null,
                'transaction_id' => $payment->transaction_id ?? null,
            ],
        ]);
    }

    public function createLosAlarmAlert(int $oltDeviceId, string $onuSerial, float $rxPower): void
    {
        $this->createAlert([
            'alert_type' => 'los_alarm',
            'severity' => 'critical',
            'title' => 'LOS Alarm - Signal Loss',
            'message' => "ONU {$onuSerial} has lost signal (RX: {$rxPower} dBm)",
            'device_id' => $oltDeviceId,
            'device_type' => 'olt',
            'target_role' => 'technician',
            'metadata' => [
                'onu_serial' => $onuSerial,
                'rx_power' => $rxPower,
            ],
        ]);
    }

    public function getUnreadAlerts(?string $role = null, ?int $userId = null, int $limit = 20): array
    {
        $query = DB::table('real_time_alerts')
            ->where('is_dismissed', false)
            ->orderByDesc('created_at')
            ->limit($limit);
        
        if ($role) {
            $query->where(function($q) use ($role) {
                $q->where('target_role', $role)
                  ->orWhereNull('target_role');
            });
        }
        
        if ($userId) {
            $query->where(function($q) use ($userId) {
                $q->where('target_user_id', $userId)
                  ->orWhereNull('target_user_id');
            });
        }

        return $query->get()->toArray();
    }

    public function markAsRead(int $alertId): void
    {
        DB::table('real_time_alerts')
            ->where('id', $alertId)
            ->update(['is_read' => true, 'updated_at' => now()]);
    }

    public function dismissAlert(int $alertId): void
    {
        DB::table('real_time_alerts')
            ->where('id', $alertId)
            ->update(['is_dismissed' => true, 'updated_at' => now()]);
    }

    public function getAlertCounts(?string $role = null): array
    {
        $query = DB::table('real_time_alerts')
            ->where('is_dismissed', false)
            ->where('created_at', '>', now()->subHours(24));
        
        if ($role) {
            $query->where(function($q) use ($role) {
                $q->where('target_role', $role)->orWhereNull('target_role');
            });
        }

        return [
            'total' => (clone $query)->count(),
            'unread' => (clone $query)->where('is_read', false)->count(),
            'critical' => (clone $query)->where('severity', 'critical')->count(),
            'warning' => (clone $query)->where('severity', 'warning')->count(),
        ];
    }

    public function logNetworkEvent(string $eventType, array $data): int
    {
        return DB::table('network_event_logs')->insertGetId([
            'event_type' => $eventType,
            'severity' => $data['severity'] ?? 'info',
            'mikrotik_device_id' => $data['mikrotik_device_id'] ?? null,
            'olt_device_id' => $data['olt_device_id'] ?? null,
            'zone_id' => $data['zone_id'] ?? null,
            'area_id' => $data['area_id'] ?? null,
            'description' => $data['description'] ?? null,
            'affected_clients' => isset($data['affected_clients']) ? json_encode($data['affected_clients']) : null,
            'affected_count' => $data['affected_count'] ?? 0,
            'is_resolved' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function resolveNetworkEvent(int $eventId, string $notes = ''): void
    {
        DB::table('network_event_logs')
            ->where('id', $eventId)
            ->update([
                'is_resolved' => true,
                'resolved_at' => now(),
                'resolution_notes' => $notes,
                'updated_at' => now(),
            ]);
    }
}
