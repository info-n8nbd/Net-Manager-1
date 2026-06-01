<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Traits\TenantAware;

class ScratchCard extends Model
{
    use TenantAware;

    protected $fillable = [
        'batch_id', 'card_number', 'pin', 'serial_number', 'amount',
        'validity_days', 'package_id', 'status', 'used_by_client_id',
        'used_at', 'distributor_id', 'distributed_at', 'expires_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'used_at' => 'datetime',
        'distributed_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    protected $hidden = ['pin'];

    public function batch()
    {
        return $this->belongsTo(ScratchCardBatch::class, 'batch_id');
    }

    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    public function usedByClient()
    {
        return $this->belongsTo(Client::class, 'used_by_client_id');
    }

    public function distributor()
    {
        return $this->belongsTo(CardDistributor::class, 'distributor_id');
    }

    public function transactions()
    {
        return $this->hasMany(CardTransaction::class, 'scratch_card_id');
    }

    public static function generateCardNumber(): string
    {
        do {
            $number = Str::upper(Str::random(4) . '-' . Str::random(4) . '-' . Str::random(4));
        } while (self::where('card_number', $number)->exists());
        return $number;
    }

    public static function generatePin(): string
    {
        return str_pad(random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
    }

    public static function generateSerial(): string
    {
        return 'SC-' . date('Ymd') . '-' . strtoupper(Str::random(6));
    }

    public function isUsable(): bool
    {
        return $this->status === 'active' && (!$this->expires_at || $this->expires_at->isFuture());
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }
}
