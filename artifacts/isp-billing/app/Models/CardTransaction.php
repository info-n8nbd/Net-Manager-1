<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CardTransaction extends Model
{
    protected $fillable = [
        'scratch_card_id', 'client_id', 'distributor_id',
        'transaction_type', 'amount', 'status', 'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function scratchCard()
    {
        return $this->belongsTo(ScratchCard::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function distributor()
    {
        return $this->belongsTo(CardDistributor::class);
    }
}
