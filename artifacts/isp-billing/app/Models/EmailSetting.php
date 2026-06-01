<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailSetting extends Model
{
    protected $fillable = [
        'mailer', 'host', 'port', 'username', 'password', 'encryption',
        'from_address', 'from_name', 'is_active',
        'auto_bill_reminder', 'auto_expiry_warning', 'auto_payment_confirmation',
        'bill_reminder_days_before', 'expiry_warning_days_before',
    ];

    protected $casts = [
        'is_active'                   => 'boolean',
        'auto_bill_reminder'          => 'boolean',
        'auto_expiry_warning'         => 'boolean',
        'auto_payment_confirmation'   => 'boolean',
        'port'                        => 'integer',
        'bill_reminder_days_before'   => 'integer',
        'expiry_warning_days_before'  => 'integer',
    ];
}
