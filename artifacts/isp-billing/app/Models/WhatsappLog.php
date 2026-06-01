<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsappLog extends Model
{
    protected $table = 'whatsapp_logs';

    protected $fillable = [
        'client_id', 'phone', 'template_code', 'message',
        'status', 'message_id', 'error_message', 'metadata', 'cost',
    ];

    protected $casts = [
        'metadata' => 'array',
        'cost' => 'decimal:4',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }
}
