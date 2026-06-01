<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NidVerification extends Model
{
    protected $fillable = [
        'application_id',
        'nid_number',
        'verification_status',
        'verification_note',
        'verified_by',
        'verified_at',
        'verification_data',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
        'verification_data' => 'array',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(NewConnectionApplication::class, 'application_id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function isVerified(): bool
    {
        return $this->verification_status === 'verified';
    }

    public function isPending(): bool
    {
        return $this->verification_status === 'pending';
    }
}
