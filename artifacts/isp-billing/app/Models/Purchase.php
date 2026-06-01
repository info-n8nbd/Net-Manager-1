<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantAware;

class Purchase extends Model
{
    use HasFactory, TenantAware;

    protected $fillable = [
        'purchase_no', 'vendor_id', 'purchase_date', 'subtotal', 'discount',
        'tax', 'total', 'paid', 'due', 'status', 'notes', 'created_by',
        'company_id', 'invoice_number'
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
        'paid' => 'decimal:2',
        'due' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($purchase) {
            if (empty($purchase->purchase_no)) {
                $purchase->purchase_no = 'PO-' . date('Ymd') . '-' . str_pad(static::max('id') + 1, 4, '0', STR_PAD_LEFT);
            }
        });
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function items()
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
