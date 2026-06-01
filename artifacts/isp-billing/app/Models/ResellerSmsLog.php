<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResellerSmsLog extends Model
{
    protected $table = 'reseller_sms_logs';

    protected $fillable = [
        'reseller_id', 'client_id', 'phone', 'message',
        'event_type', 'status', 'response', 'skip_reason',
    ];

    public function reseller()
    {
        return $this->belongsTo(Reseller::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
