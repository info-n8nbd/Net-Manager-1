<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\TenantAware;

class CollectionTransfer extends Model
{
    use TenantAware;

    protected $fillable = [
        'transfer_no', 'from_user_id', 'to_account_id', 'to_user_id', 'amount',
        'transfer_date', 'remarks', 'status', 'verified_by'
    ];

    protected $casts = [
        'transfer_date' => 'date',
        'amount' => 'decimal:2'
    ];

    public function fromUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    public function toAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'to_account_id');
    }

    public function toUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public static function generateTransferNo(): string
    {
        $lastTransfer = self::withoutGlobalScopes()->orderBy('id', 'desc')->first();
        $number = $lastTransfer ? intval(substr($lastTransfer->transfer_no, 3)) + 1 : 1;
        return 'CT-' . str_pad($number, 6, '0', STR_PAD_LEFT);
    }
}
