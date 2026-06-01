<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VoiceCallLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'phone', 'client_id', 'call_type', 'message_text', 'provider',
        'provider_call_id', 'status', 'duration_seconds', 'cost',
        'provider_response',
    ];

    protected $casts = [
        'provider_response' => 'json',
        'cost' => 'decimal:4',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
