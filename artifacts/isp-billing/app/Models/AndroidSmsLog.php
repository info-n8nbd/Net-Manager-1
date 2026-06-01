<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AndroidSmsLog extends Model
{
    protected $fillable = [
        'raw_sms', 'gateway', 'amount', 'sender_number', 'reference', 'trx_id',
        'parsed_data', 'status', 'failure_reason', 'client_id', 'payment_id',
        'device_id', 'device_ip', 'api_token', 'sms_received_at', 'company_id',
        'locked_by', 'locked_at',
    ];

    protected $casts = [
        'parsed_data'    => 'array',
        'amount'         => 'decimal:2',
        'sms_received_at'=> 'datetime',
        'locked_by'      => 'integer',
        'locked_at'      => 'datetime',
    ];

    public function locker(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'locked_by');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function getStatusBadgeAttribute(): string
    {
        if ($this->status === 'applied') {
            $isManual = !empty($this->parsed_data['manually_applied']);
            return $isManual
                ? '<span class="badge" style="background:#6f42c1;color:#fff">Manual Settled</span>'
                : '<span class="badge badge-success">Auto Settled</span>';
        }
        return match($this->status) {
            'matched'   => '<span class="badge badge-info">Matched</span>',
            'pending'   => '<span class="badge badge-warning">Pending</span>',
            'duplicate' => '<span class="badge badge-secondary">Duplicate</span>',
            'unknown'   => '<span class="badge badge-danger">Unknown</span>',
            'failed'    => '<span class="badge badge-danger">Failed</span>',
            default     => '<span class="badge badge-light">' . $this->status . '</span>',
        };
    }

    public function getGatewayBadgeAttribute(): string
    {
        $logos = [
            'bkash'  => 'bkash.png',
            'nagad'  => 'nagad.png',
            'rocket' => 'rocket.svg',
        ];
        $logo = $logos[$this->gateway] ?? null;
        if ($logo) {
            return '<img src="' . asset('images/gateways/' . $logo) . '" alt="' . ucfirst($this->gateway) . '" style="height:28px;max-width:70px;object-fit:contain;vertical-align:middle;">';
        }
        return '<span class="badge badge-secondary">' . ucfirst($this->gateway ?? 'Unknown') . '</span>';
    }
}
