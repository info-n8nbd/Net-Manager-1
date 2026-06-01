<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FieldMappingTemplate extends Model
{
    protected $fillable = [
        'name',
        'description',
        'source_software',
        'data_type',
        'mapping',
        'created_by',
        'is_default',
        'usage_count',
    ];

    protected $casts = [
        'mapping' => 'array',
        'is_default' => 'boolean',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function importJobs(): HasMany
    {
        return $this->hasMany(ImportJob::class, 'mapping_template_id');
    }

    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }
}
