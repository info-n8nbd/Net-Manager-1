<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantAware;

class OnlineClientHistory extends Model
{
    use TenantAware;
    public $timestamps = false;

    protected $table = 'online_client_history';

    protected $fillable = ['company_id', 'online_count', 'recorded_at'];

    protected $casts = ['recorded_at' => 'datetime'];
}
