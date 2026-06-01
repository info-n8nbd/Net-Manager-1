<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantAware;

class Sale extends Model
{
    use TenantAware;

    protected $fillable = [
        'company_id', 'invoice_number', 'client_id', 'customer_name', 'customer_phone',
        'customer_type', 'sale_date', 'subtotal', 'discount', 'tax_percent', 'tax_amount',
        'total', 'paid', 'due', 'status', 'payment_method', 'payment_reference', 'notes', 'created_by'
    ];

    protected $casts = [
        'sale_date' => 'date',
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'paid' => 'decimal:2',
        'due' => 'decimal:2',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function getCustomerDisplayAttribute()
    {
        if ($this->client) {
            return $this->client->name . ($this->client->client_id ? ' [' . $this->client->client_id . ']' : '');
        }
        return $this->customer_name ?: 'Walk-in Customer';
    }
}
