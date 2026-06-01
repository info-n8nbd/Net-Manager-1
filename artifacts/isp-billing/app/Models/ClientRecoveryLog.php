<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantAware;

class ClientRecoveryLog extends Model
{
    use TenantAware;

    protected $fillable = [
        'client_id', 'recovered_by', 'previous_status', 'notes',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function recoveredByUser()
    {
        return $this->belongsTo(User::class, 'recovered_by');
    }
}
