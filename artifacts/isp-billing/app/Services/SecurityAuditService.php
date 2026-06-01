<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SecurityAuditService
{
    public function logActivity(string $action, ?int $userId, array $data = []): void
    {
        DB::table('activity_logs')->insert([
            'user_id' => $userId,
            'action' => $action,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'url' => request()->fullUrl(),
            'method' => request()->method(),
            'data' => json_encode($data),
            'created_at' => now(),
        ]);
    }

    public function trackDataChange(string $model, int $modelId, array $oldData, array $newData, ?int $userId = null): void
    {
        $changes = [];
        foreach ($newData as $key => $value) {
            if (isset($oldData[$key]) && $oldData[$key] !== $value) {
                $changes[$key] = [
                    'old' => $oldData[$key],
                    'new' => $value,
                ];
            }
        }

        if (!empty($changes)) {
            DB::table('audit_logs')->insert([
                'user_id' => $userId ?? auth()->id(),
                'action' => 'update',
                'model_type' => $model,
                'model_id' => $modelId,
                'changes' => json_encode($changes),
                'ip_address' => request()->ip(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function logPaymentAudit(int $paymentId, string $action, array $data): void
    {
        DB::table('payment_audit_trail')->insert([
            'payment_id' => $paymentId,
            'action' => $action,
            'performed_by' => auth()->id(),
            'data' => json_encode($data),
            'ip_address' => request()->ip(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function detectUnauthorizedAccess(int $userId, string $resource): bool
    {
        $recentAttempts = DB::table('activity_logs')
            ->where('user_id', $userId)
            ->where('action', 'like', '%unauthorized%')
            ->where('created_at', '>=', now()->subMinutes(15))
            ->count();

        if ($recentAttempts >= 5) {
            $this->createSecurityAlert('unauthorized_access', [
                'user_id' => $userId,
                'resource' => $resource,
                'attempts' => $recentAttempts,
            ]);
            return true;
        }

        return false;
    }

    public function detectAbnormalBehavior(): array
    {
        $alerts = [];

        $unusualLogins = DB::table('activity_logs')
            ->where('action', 'login')
            ->where('created_at', '>=', now()->subHours(24))
            ->selectRaw('user_id, COUNT(*) as login_count')
            ->groupBy('user_id')
            ->havingRaw('COUNT(*) > 10')
            ->get();

        foreach ($unusualLogins as $login) {
            $alerts[] = [
                'type' => 'excessive_logins',
                'user_id' => $login->user_id,
                'count' => $login->login_count,
            ];
        }

        $bulkPaymentEdits = DB::table('audit_logs')
            ->where('model_type', 'Payment')
            ->where('action', 'update')
            ->where('created_at', '>=', now()->subHours(1))
            ->selectRaw('user_id, COUNT(*) as edit_count')
            ->groupBy('user_id')
            ->havingRaw('COUNT(*) > 20')
            ->get();

        foreach ($bulkPaymentEdits as $edit) {
            $alerts[] = [
                'type' => 'bulk_payment_edits',
                'user_id' => $edit->user_id,
                'count' => $edit->edit_count,
            ];
        }

        $lateNightActivity = DB::table('activity_logs')
            ->whereRaw('EXTRACT(HOUR FROM created_at) BETWEEN 0 AND 5')
            ->where('created_at', '>=', now()->subDays(1))
            ->selectRaw('user_id, COUNT(*) as activity_count')
            ->groupBy('user_id')
            ->havingRaw('COUNT(*) > 50')
            ->get();

        foreach ($lateNightActivity as $activity) {
            $alerts[] = [
                'type' => 'late_night_activity',
                'user_id' => $activity->user_id,
                'count' => $activity->activity_count,
            ];
        }

        foreach ($alerts as $alert) {
            $this->createSecurityAlert($alert['type'], $alert);
        }

        return $alerts;
    }

    protected function createSecurityAlert(string $type, array $data): void
    {
        $existingAlert = DB::table('security_alerts')
            ->where('alert_type', $type)
            ->where('user_id', $data['user_id'] ?? null)
            ->where('created_at', '>=', now()->subHours(1))
            ->exists();

        if (!$existingAlert) {
            DB::table('security_alerts')->insert([
                'alert_type' => $type,
                'user_id' => $data['user_id'] ?? null,
                'description' => json_encode($data),
                'severity' => $this->getSeverity($type),
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    protected function getSeverity(string $type): string
    {
        return match ($type) {
            'unauthorized_access' => 'critical',
            'bulk_payment_edits' => 'high',
            'excessive_logins' => 'medium',
            'late_night_activity' => 'low',
            default => 'medium',
        };
    }

    public function getSecurityAlerts(int $limit = 50): array
    {
        return DB::table('security_alerts')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(fn($a) => [
                'id' => $a->id,
                'type' => $a->alert_type,
                'severity' => $a->severity,
                'description' => json_decode($a->description, true),
                'status' => $a->status,
                'created_at' => $a->created_at,
            ])
            ->toArray();
    }

    public function resolveSecurityAlert(int $alertId, string $resolution): bool
    {
        return DB::table('security_alerts')
            ->where('id', $alertId)
            ->update([
                'status' => 'resolved',
                'resolution_notes' => $resolution,
                'resolved_at' => now(),
                'resolved_by' => auth()->id(),
                'updated_at' => now(),
            ]) > 0;
    }

    public function getActivityLog(?int $userId = null, int $limit = 100): array
    {
        $query = DB::table('activity_logs')
            ->orderByDesc('created_at')
            ->limit($limit);

        if ($userId) {
            $query->where('user_id', $userId);
        }

        return $query->get()->toArray();
    }

    public function getAuditTrail(string $model, int $modelId): array
    {
        return DB::table('audit_logs')
            ->where('model_type', $model)
            ->where('model_id', $modelId)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($a) => [
                'action' => $a->action,
                'changes' => json_decode($a->changes, true),
                'user_id' => $a->user_id,
                'ip_address' => $a->ip_address,
                'created_at' => $a->created_at,
            ])
            ->toArray();
    }
}
