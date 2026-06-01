<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubResellerCommission extends Model
{
    use HasFactory;

    protected $fillable = [
        'reseller_id', 'sub_reseller_id', 'client_id', 'payment_id',
        'payment_amount', 'reseller_commission', 'sub_reseller_commission',
        'admin_commission', 'description',
    ];

    protected $casts = [
        'payment_amount' => 'decimal:2',
        'reseller_commission' => 'decimal:2',
        'sub_reseller_commission' => 'decimal:2',
        'admin_commission' => 'decimal:2',
    ];

    public function reseller()
    {
        return $this->belongsTo(Reseller::class);
    }

    public function subReseller()
    {
        return $this->belongsTo(Reseller::class, 'sub_reseller_id');
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }
}
