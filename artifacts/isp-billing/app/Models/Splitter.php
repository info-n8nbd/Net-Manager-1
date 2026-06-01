<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\TenantAware;

class Splitter extends Model
{
    use TenantAware;

    protected $fillable = [
        'name', 'type', 'olt_device_id', 'parent_splitter_id', 'port_number',
        'location', 'latitude', 'longitude', 'is_active', 'notes'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    public function oltDevice(): BelongsTo
    {
        return $this->belongsTo(OltDevice::class);
    }

    public function parentSplitter(): BelongsTo
    {
        return $this->belongsTo(Splitter::class, 'parent_splitter_id');
    }

    public function childSplitters(): HasMany
    {
        return $this->hasMany(Splitter::class, 'parent_splitter_id');
    }

    public function clients(): HasMany
    {
        return $this->hasMany(Client::class);
    }

    public function getPortCountAttribute(): int
    {
        preg_match('/1:(\d+)/', $this->type, $matches);
        return isset($matches[1]) ? (int) $matches[1] : 8;
    }

    public function getUsedPortsAttribute(): int
    {
        return $this->childSplitters()->count() + $this->clients()->count();
    }
}
