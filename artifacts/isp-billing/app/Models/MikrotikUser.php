<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MikrotikUser extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id', 'mikrotik_device_id', 'mikrotik_id', 'username',
        'password', 'profile', 'service', 'is_disabled'
    ];

    protected $casts = ['is_disabled' => 'boolean'];

    protected $hidden = ['password'];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function device()
    {
        return $this->belongsTo(MikrotikDevice::class, 'mikrotik_device_id');
    }
}
