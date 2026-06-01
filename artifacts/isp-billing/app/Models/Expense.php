<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantAware;

class Expense extends Model
{
    use HasFactory, TenantAware;

    protected $fillable = [
        'expense_head_id', 'account_id', 'amount', 'expense_date',
        'description', 'reference', 'attachment', 'created_by', 'company_id'
    ];

    protected $casts = [
        'expense_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function expenseHead()
    {
        return $this->belongsTo(ExpenseHead::class);
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
