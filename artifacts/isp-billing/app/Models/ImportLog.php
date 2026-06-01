<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportLog extends Model
{
    protected $fillable = [
        'import_job_id',
        'row_number',
        'original_data',
        'mapped_data',
        'status',
        'entity_type',
        'entity_id',
        'validation_errors',
        'error_message',
    ];

    protected $casts = [
        'original_data' => 'array',
        'mapped_data' => 'array',
        'validation_errors' => 'array',
    ];

    public function importJob(): BelongsTo
    {
        return $this->belongsTo(ImportJob::class);
    }

    public function getStatusBadgeClass(): string
    {
        return match($this->status) {
            'pending' => 'badge-secondary',
            'valid' => 'badge-success',
            'invalid' => 'badge-danger',
            'duplicate' => 'badge-warning',
            'imported' => 'badge-success',
            'failed' => 'badge-danger',
            'skipped' => 'badge-info',
            default => 'badge-secondary',
        };
    }
}
