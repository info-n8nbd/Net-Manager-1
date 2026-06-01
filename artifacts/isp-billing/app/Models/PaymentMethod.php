<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantAware;

class PaymentMethod extends Model
{
    use HasFactory;
    use TenantAware;

    protected $fillable = ['name', 'code', 'description', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}
