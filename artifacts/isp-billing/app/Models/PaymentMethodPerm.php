<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantAware;

class PaymentMethodPerm extends Model
{
    use TenantAware;

    protected $table = 'payment_method_perms';

    protected $fillable = ['company_id', 'role_id', 'user_id', 'payment_method_id'];

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function role()
    {
        return $this->belongsTo(\Spatie\Permission\Models\Role::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
