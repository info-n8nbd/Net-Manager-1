<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExportJob extends Model
{
    protected $fillable = [
        'name',
        'data_types',
        'file_format',
        'status',
        'file_path',
        'file_name',
        'file_size',
        'export_options',
        'export_summary',
        'error_message',
        'user_id',
        'started_at',
        'completed_at',
        'expires_at',
    ];

    protected $casts = [
        'data_types' => 'array',
        'export_options' => 'array',
        'export_summary' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getStatusBadgeClass(): string
    {
        return match($this->status) {
            'pending' => 'bg-secondary',
            'processing' => 'bg-warning',
            'completed' => 'bg-success',
            'failed' => 'bg-danger',
            default => 'bg-secondary',
        };
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function getFileSizeFormatted(): string
    {
        if (!$this->file_size) return '-';

        $size = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;

        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }

        return round($size, 2) . ' ' . $units[$unitIndex];
    }

    public function getTypeAttribute(): string
    {
        if (is_array($this->data_types)) {
            return implode(', ', $this->data_types);
        }
        return $this->data_types ?? '';
    }

    public function getFormatAttribute(): string
    {
        return $this->file_format ?? '';
    }

    public function getFiltersAttribute()
    {
        return $this->export_options;
    }

    public function getNameDisplayAttribute(): string
    {
        return $this->name ?? ucfirst(str_replace('_', ' ', $this->type)) . ' Export';
    }
}
