<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantAware;

class Vendor extends Model
{
    use HasFactory, TenantAware;

    protected $fillable = ['name', 'company', 'email', 'phone', 'address', 'balance', 'is_active', 'company_id', 'contact_person', 'opening_balance'];

    protected $casts = [
        'is_active' => 'boolean',
        'balance' => 'decimal:2',
    ];

    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }

    public function payments()
    {
        return $this->hasMany(VendorLedger::class);
    }

    public function ledgerEntries()
    {
        return $this->hasMany(VendorLedger::class);
    }

    public function addLedgerEntry(string $type, float $debit, float $credit, string $description, ?string $reference = null, ?int $createdBy = null): VendorLedger
    {
        $lastEntry = $this->ledgerEntries()->latest()->first();
        $previousBalance = $lastEntry ? $lastEntry->balance : 0;
        $newBalance = $previousBalance + $credit - $debit;

        $entry = $this->ledgerEntries()->create([
            'type' => $type,
            'debit' => $debit,
            'credit' => $credit,
            'balance' => $newBalance,
            'reference' => $reference,
            'description' => $description,
            'transaction_date' => now(),
            'created_by' => $createdBy
        ]);

        $this->balance = $newBalance;
        $this->save();

        return $entry;
    }
}
