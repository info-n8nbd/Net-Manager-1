<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantAware;

class AutomationSetting extends Model
{
    use TenantAware;

    protected $fillable = [
        'bill_generate_time',
        'disconnect_time',
        'min_due_for_inactive',
    ];
}
