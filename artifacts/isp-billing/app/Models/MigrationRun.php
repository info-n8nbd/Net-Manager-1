<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MigrationRun extends Model
{
    protected $fillable = [
        'name', 'status', 'current_phase',
        'phase1_status', 'phase2_status', 'phase3_status', 'phase4_status', 'phase5_status',
        'phase1_summary', 'phase2_summary', 'phase3_summary', 'phase4_summary', 'phase5_summary',
        'import_job_id', 'sync_run_id',
        'total_clients_imported', 'total_bills_imported', 'total_payments_imported',
        'total_routers_scanned', 'total_router_users_found',
        'total_reconciled', 'total_synced', 'total_errors',
        'notes', 'initiated_by', 'started_at', 'completed_at',
    ];

    protected $casts = [
        'phase1_summary' => 'array',
        'phase2_summary' => 'array',
        'phase3_summary' => 'array',
        'phase4_summary' => 'array',
        'phase5_summary' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function initiator()
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    public function importJob()
    {
        return $this->belongsTo(ImportJob::class);
    }

    public function syncRun()
    {
        return $this->belongsTo(MikrotikSyncRun::class, 'sync_run_id');
    }

    public function routerSnapshots()
    {
        return $this->hasMany(MigrationRouterSnapshot::class, 'migration_run_id');
    }

    public function getPhaseStatus(int $phase): string
    {
        return $this->{"phase{$phase}_status"} ?? 'pending';
    }

    public function isPhaseComplete(int $phase): bool
    {
        return $this->getPhaseStatus($phase) === 'completed';
    }

    public function canAccessPhase(int $phase): bool
    {
        if ($phase === 1) return true;
        return $this->isPhaseComplete($phase - 1);
    }
}
