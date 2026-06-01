<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CreditDebitNote extends Model
{
    

    protected $fillable = [
        'note_no',
        'client_id',
        'type',
        'amount',
        'reason',
        'description',
        'billing_month',
        'billing_year',
        'status',
        'approved_by',
        'approved_at',
        'created_by',
        'ledger_entry_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($note) {
            if (empty($note->note_no)) {
                $prefix = $note->type === 'credit' ? 'CN' : 'DN';
                $uniqueId = now()->format('YmdHis') . str_pad(random_int(0, 999), 3, '0', STR_PAD_LEFT);
                $note->note_no = $prefix . '-' . $uniqueId;
            }
        });
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function ledgerEntry()
    {
        return $this->belongsTo(LedgerEntry::class);
    }
}
