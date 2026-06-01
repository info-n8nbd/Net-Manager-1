<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DamageProduct extends Model
{
    protected $table = 'damaged_products';

    protected $fillable = [
        'product_id', 'quantity', 'damage_date', 'reason', 'reported_by'
    ];

    protected $casts = [
        'damage_date' => 'date',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reported_by');
    }
}
