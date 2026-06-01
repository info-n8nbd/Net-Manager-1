<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LedgerEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'entry_no', 'client_id', 'entry_type', 'debit_credit', 'amount',
        'running_balance', 'reference_type', 'reference_id', 'description',
        'entry_date', 'billing_month', 'created_by', 'created_by_role',
        'ip_address', 'is_reversal', 'reverses_entry_id'
    ];

    protected $casts = [
        'entry_date' => 'date',
        'amount' => 'decimal:2',
        'running_balance' => 'decimal:2',
        'is_reversal' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($entry) {
            if (empty($entry->entry_no)) {
                $entry->entry_no = 'LED-' . date('Ymd') . '-' . str_pad(static::max('id') + 1, 8, '0', STR_PAD_LEFT);
            }
        });
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reversesEntry()
    {
        return $this->belongsTo(LedgerEntry::class, 'reverses_entry_id');
    }

    public function reversedBy()
    {
        return $this->hasOne(LedgerEntry::class, 'reverses_entry_id');
    }

    public function reference()
    {
        if ($this->reference_type && $this->reference_id) {
            $modelClass = 'App\\Models\\' . $this->reference_type;
            if (class_exists($modelClass)) {
                return $modelClass::find($this->reference_id);
            }
        }
        return null;
    }

    public function isDebit(): bool
    {
        return $this->debit_credit === 'debit';
    }

    public function isCredit(): bool
    {
        return $this->debit_credit === 'credit';
    }
}
