<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantAware;

class Income extends Model
{
    use HasFactory, TenantAware;

    protected $fillable = [
        'income_head_id', 'account_id', 'amount', 'income_date',
        'description', 'reference', 'created_by', 'company_id'
    ];

    protected $casts = [
        'income_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function incomeHead()
    {
        return $this->belongsTo(IncomeHead::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
