<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemHealthLog extends Model
{
    protected $fillable = [
        'component',
        'status',
        'message',
        'response_time_ms',
        'details',
        'checked_at',
    ];

    protected $casts = [
        'details' => 'array',
        'checked_at' => 'datetime',
    ];

    const STATUS_OK = 'ok';
    const STATUS_WARNING = 'warning';
    const STATUS_ERROR = 'error';

    const COMPONENT_DATABASE = 'database';
    const COMPONENT_QUEUE = 'queue';
    const COMPONENT_SMS_API = 'sms_api';
    const COMPONENT_MIKROTIK = 'mikrotik';
    const COMPONENT_TELEGRAM = 'telegram';
    const COMPONENT_STORAGE = 'storage';
    const COMPONENT_CRON = 'cron';

    public static function logCheck(string $component, string $status, string $message, ?int $responseTime = null, ?array $details = null): self
    {
        return static::create([
            'component' => $component,
            'status' => $status,
            'message' => $message,
            'response_time_ms' => $responseTime,
            'details' => $details,
            'checked_at' => now(),
        ]);
    }

    public static function getLatestStatus(): array
    {
        $components = [
            self::COMPONENT_DATABASE,
            self::COMPONENT_QUEUE,
            self::COMPONENT_SMS_API,
            self::COMPONENT_MIKROTIK,
            self::COMPONENT_TELEGRAM,
            self::COMPONENT_STORAGE,
            self::COMPONENT_CRON,
        ];

        $status = [];
        foreach ($components as $component) {
            $latest = static::where('component', $component)
                ->orderBy('checked_at', 'desc')
                ->first();
            $status[$component] = $latest ? [
                'status' => $latest->status,
                'message' => $latest->message,
                'response_time_ms' => $latest->response_time_ms,
                'checked_at' => $latest->checked_at?->toIso8601String(),
            ] : ['status' => 'unknown', 'message' => 'Never checked'];
        }

        return $status;
    }
}
