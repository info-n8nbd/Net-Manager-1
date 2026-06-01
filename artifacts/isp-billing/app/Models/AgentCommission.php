<?php

namespace App\Models;

use App\Traits\TenantAware;
use Illuminate\Database\Eloquent\Model;

class AgentCommission extends Model
{
    use TenantAware;
    protected $fillable = [
        'agent_id', 'type', 'client_id', 'payment_id', 'client_name', 'client_pppoe_id',
        'zone_address', 'purpose', 'note', 'date',
        'payment_amount', 'commission_percent', 'commission_amount',
        'month', 'year', 'company_id', 'created_by',
    ];

    protected $casts = [
        'date' => 'date',
        'payment_amount' => 'decimal:2',
        'commission_percent' => 'decimal:2',
        'commission_amount' => 'decimal:2',
    ];

    public function agent()
    {
        return $this->belongsTo(Agent::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
