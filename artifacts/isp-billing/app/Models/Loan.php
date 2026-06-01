<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\TenantAware;

class Loan extends Model
{
    use TenantAware;

    protected $fillable = [
        'reference_no', 'type', 'party_name', 'party_phone', 'amount',
        'paid_amount', 'remaining_amount', 'loan_date', 'due_date',
        'account_id', 'status', 'description', 'created_by', 'company_id'
    ];

    protected $casts = [
        'loan_date' => 'date',
        'due_date' => 'date',
        'amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2'
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(LoanPayment::class);
    }

    public function updatePaymentStatus(): void
    {
        $totalPaid = $this->payments()->sum('amount');
        $this->paid_amount = $totalPaid;
        $this->remaining_amount = $this->amount - $totalPaid;

        if ($this->remaining_amount <= 0) {
            $this->status = 'completed';
        } elseif ($totalPaid > 0) {
            $this->status = 'partial';
        } else {
            $this->status = 'pending';
        }

        $this->save();
    }
}
