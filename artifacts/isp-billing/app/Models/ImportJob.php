<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImportJob extends Model
{
    protected $fillable = [
        'name',
        'source_software',
        'file_name',
        'file_path',
        'file_type',
        'data_type',
        'status',
        'user_id',
        'field_mapping',
        'mapping_template_id',
        'total_rows',
        'valid_rows',
        'invalid_rows',
        'duplicate_rows',
        'imported_rows',
        'failed_rows',
        'skipped_rows',
        'validation_summary',
        'import_summary',
        'error_message',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'field_mapping' => 'array',
        'validation_summary' => 'array',
        'import_summary' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(ImportLog::class);
    }

    public function mappingTemplate(): BelongsTo
    {
        return $this->belongsTo(FieldMappingTemplate::class, 'mapping_template_id');
    }

    public function getProgressPercentage(): int
    {
        if ($this->total_rows === 0) return 0;
        return (int) round(($this->imported_rows + $this->failed_rows + $this->skipped_rows) / $this->total_rows * 100);
    }

    public function getStatusBadgeClass(): string
    {
        return match($this->status) {
            'pending' => 'badge-secondary',
            'validating' => 'badge-info',
            'validated' => 'badge-primary',
            'importing' => 'badge-warning',
            'completed' => 'badge-success',
            'failed' => 'badge-danger',
            'cancelled' => 'badge-dark',
            default => 'badge-secondary',
        };
    }
}
