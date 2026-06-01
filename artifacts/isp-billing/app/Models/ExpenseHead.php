<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantAware;

class ExpenseHead extends Model
{
    use HasFactory;
    use TenantAware;

    protected $fillable = ['name', 'description', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }
}
