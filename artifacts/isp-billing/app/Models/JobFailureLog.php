<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobFailureLog extends Model
{
    protected $fillable = [
        'job_name',
        'job_class',
        'payload',
        'exception',
        'attempts',
        'max_attempts',
        'status',
        'retried_at',
        'resolved_at',
        'resolved_by',
        'resolution_notes',
    ];

    protected $casts = [
        'payload' => 'array',
        'retried_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    const STATUS_FAILED = 'failed';
    const STATUS_RETRYING = 'retrying';
    const STATUS_RESOLVED = 'resolved';
    const STATUS_IGNORED = 'ignored';

    public function resolvedBy()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public static function logFailure(string $jobName, string $jobClass, array $payload, string $exception, int $attempts, int $maxAttempts): self
    {
        return static::create([
            'job_name' => $jobName,
            'job_class' => $jobClass,
            'payload' => $payload,
            'exception' => $exception,
            'attempts' => $attempts,
            'max_attempts' => $maxAttempts,
            'status' => self::STATUS_FAILED,
        ]);
    }

    public function markRetrying(): void
    {
        $this->update([
            'status' => self::STATUS_RETRYING,
            'retried_at' => now(),
        ]);
    }

    public function markResolved(int $userId, ?string $notes = null): void
    {
        $this->update([
            'status' => self::STATUS_RESOLVED,
            'resolved_at' => now(),
            'resolved_by' => $userId,
            'resolution_notes' => $notes,
        ]);
    }
}
