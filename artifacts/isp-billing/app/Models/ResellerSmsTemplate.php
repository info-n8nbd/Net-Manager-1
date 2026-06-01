<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResellerSmsTemplate extends Model
{
    protected $fillable = ['reseller_id', 'name', 'type', 'content'];

    public function reseller()
    {
        return $this->belongsTo(Reseller::class);
    }
}
