<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Bill;
use Illuminate\Support\Facades\DB;

class ClientRealtimeService
{
    public function getClientRealtimeStatus(int $clientId): array
    {
        $client = Client::with('package')->find($clientId);
        if (!$client) {
            return ['error' => 'Client not found'];
        }

        $session = DB::table('mikrotik_realtime_sessions')
            ->where('client_id', $clientId)
            ->where('is_active', true)
            ->first();

        $currentBill = Bill::where('client_id', $clientId)
            ->where('status', 'unpaid')
            ->orderBy('due_date')
            ->first();

        $qualityScore = DB::table('internet_quality_scores')
            ->where('client_id', $clientId)
            ->orderByDesc('score_date')
            ->first();

        $dueCountdown = null;
        if ($currentBill && $currentBill->due_date) {
            $dueDate = \Carbon\Carbon::parse($currentBill->due_date);
            $dueCountdown = [
                'due_date' => $dueDate->format('Y-m-d'),
                'days_remaining' => max(0, now()->diffInDays($dueDate, false)),
                'hours_remaining' => max(0, now()->diffInHours($dueDate, false)),
                'is_overdue' => $dueDate->isPast(),
            ];
        }

        return [
            'client_id' => $clientId,
            'client_code' => $client->client_id,
            'name' => $client->name,
            'connection_status' => $session ? 'online' : 'offline',
            'ip_address' => $session?->ip_address,
            'uptime_seconds' => $session?->uptime_seconds ?? 0,
            'connected_since' => $session?->connected_at,
            'speed' => [
                'download' => $client->package?->speed ?? 0,
                'upload' => ($client->package?->speed ?? 0) / 2,
                'unit' => 'Mbps',
            ],
            'quality' => [
                'score' => $qualityScore?->quality_score ?? 0,
                'grade' => $this->getQualityGrade($qualityScore?->quality_score ?? 0),
                'indicator' => $this->getQualityIndicator($qualityScore?->quality_score ?? 0),
            ],
            'billing' => [
                'current_due' => $currentBill?->total_amount ?? 0,
                'due_countdown' => $dueCountdown,
                'wallet_balance' => $client->wallet_balance ?? 0,
            ],
            'package' => [
                'id' => $client->package_id,
                'name' => $client->package?->name,
                'speed' => $client->package?->speed,
                'price' => $client->package?->price,
            ],
        ];
    }

    protected function getQualityGrade(int $score): string
    {
        if ($score >= 90) return 'A';
        if ($score >= 80) return 'B';
        if ($score >= 70) return 'C';
        if ($score >= 60) return 'D';
        return 'F';
    }

    protected function getQualityIndicator(int $score): string
    {
        if ($score >= 80) return 'good';
        if ($score >= 60) return 'average';
        return 'poor';
    }

    public function getClientLiveSpeed(int $clientId): array
    {
        $session = DB::table('mikrotik_realtime_sessions')
            ->where('client_id', $clientId)
            ->where('is_active', true)
            ->first();

        if (!$session) {
            return ['status' => 'offline', 'download' => 0, 'upload' => 0];
        }

        $prevBytes = DB::table('mikrotik_bandwidth_usage')
            ->where('client_id', $clientId)
            ->where('usage_date', today())
            ->first();

        $downloadSpeed = 0;
        $uploadSpeed = 0;

        if ($prevBytes) {
            $downloadSpeed = max(0, ($session->bytes_in - ($prevBytes->download_bytes ?? 0)) / 1048576 * 8);
            $uploadSpeed = max(0, ($session->bytes_out - ($prevBytes->upload_bytes ?? 0)) / 1048576 * 8);
        }

        return [
            'status' => 'online',
            'download_mbps' => round($downloadSpeed, 2),
            'upload_mbps' => round($uploadSpeed, 2),
            'ip_address' => $session->ip_address,
            'uptime_seconds' => $session->uptime_seconds,
        ];
    }

    public function getClientOutageHistory(int $clientId, int $days = 7): array
    {
        $outages = DB::table('client_timeline')
            ->where('client_id', $clientId)
            ->where('event_type', 'status_change')
            ->where('created_at', '>=', now()->subDays($days))
            ->orderByDesc('created_at')
            ->get();

        return $outages->map(fn($o) => [
            'event' => $o->title,
            'description' => $o->description,
            'occurred_at' => $o->created_at,
        ])->toArray();
    }

    public function notifyClientOutage(int $clientId): void
    {
        $client = Client::find($clientId);
        if (!$client || !$client->phone) return;

        DB::table('notification_queue')->insert([
            'type' => 'client_outage',
            'channel' => 'sms',
            'recipient_type' => 'client',
            'recipient_id' => $clientId,
            'title' => 'Internet Outage Detected',
            'message' => "Dear {$client->name}, we detected an issue with your connection. Our team is working on it.",
            'priority' => 'high',
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function checkAutoReconnect(int $clientId): array
    {
        $client = Client::find($clientId);
        if (!$client) {
            return ['error' => 'Client not found'];
        }

        $pendingBills = Bill::where('client_id', $clientId)
            ->where('status', 'unpaid')
            ->sum('total_amount');

        $recentPayment = DB::table('payments')
            ->where('client_id', $clientId)
            ->where('created_at', '>=', now()->subMinutes(5))
            ->orderByDesc('created_at')
            ->first();

        return [
            'client_id' => $clientId,
            'current_status' => $client->status,
            'pending_dues' => $pendingBills,
            'has_recent_payment' => (bool) $recentPayment,
            'can_reconnect' => $pendingBills <= 0 || ($recentPayment && $recentPayment->amount >= $pendingBills),
            'last_payment' => $recentPayment ? [
                'amount' => $recentPayment->amount,
                'method' => $recentPayment->payment_method,
                'time' => $recentPayment->created_at,
            ] : null,
        ];
    }

    public function getAreaClientsStatus(int $zoneId): array
    {
        $clients = Client::where('zone_id', $zoneId)->get();

        $onlineCount = 0;
        $offlineCount = 0;

        foreach ($clients as $client) {
            $isOnline = DB::table('mikrotik_realtime_sessions')
                ->where('client_id', $client->id)
                ->where('is_active', true)
                ->exists();

            if ($isOnline) {
                $onlineCount++;
            } else {
                $offlineCount++;
            }
        }

        return [
            'zone_id' => $zoneId,
            'total_clients' => $clients->count(),
            'online' => $onlineCount,
            'offline' => $offlineCount,
            'online_percent' => $clients->count() > 0 
                ? round(($onlineCount / $clients->count()) * 100, 1) 
                : 0,
        ];
    }
}
