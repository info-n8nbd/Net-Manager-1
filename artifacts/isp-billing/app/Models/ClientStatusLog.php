<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\TenantAware;

class ClientStatusLog extends Model
{
    use HasFactory;
    use TenantAware;

    protected $table = 'client_status_logs';

    protected $fillable = [
        'client_id',
        'old_status',
        'new_status',
        'reason',
        'source',
        'changed_by',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    const SOURCE_MANUAL = 'manual';
    const SOURCE_AUTO = 'auto';

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    public static function logStatusChange(
        Client $client,
        string $oldStatus,
        string $newStatus,
        ?string $reason = null,
        string $source = self::SOURCE_MANUAL,
        ?int $changedBy = null
    ): self {
        // Check for duplicate entry within last 5 seconds
        $recentDuplicate = self::where('client_id', $client->id)
            ->where('old_status', $oldStatus)
            ->where('new_status', $newStatus)
            ->where('created_at', '>=', now()->subSeconds(5))
            ->first();
        
        if ($recentDuplicate) {
            return $recentDuplicate;
        }
        
        return self::create([
            'client_id' => $client->id,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'reason' => $reason,
            'source' => $source,
            'changed_by' => $changedBy ?? auth()->id(),
        ]);
    }

    public function isManual(): bool
    {
        return $this->source === self::SOURCE_MANUAL;
    }

    public function isAuto(): bool
    {
        return $this->source === self::SOURCE_AUTO;
    }

    public function getSourceLabel(): string
    {
        return $this->source === self::SOURCE_AUTO ? 'System' : 'Manual';
    }

    public function getActorName(): string
    {
        if ($this->source === self::SOURCE_AUTO) {
            return 'System';
        }
        return $this->changedBy?->name ?? 'Unknown';
    }
}
