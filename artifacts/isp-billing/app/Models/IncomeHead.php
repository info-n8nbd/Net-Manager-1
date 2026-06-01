<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantAware;

class IncomeHead extends Model
{
    use HasFactory;
    use TenantAware;

    protected $fillable = ['name', 'description', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function incomes()
    {
        return $this->hasMany(Income::class);
    }
}
