<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantAware;

class DailyCollection extends Model
{
    use HasFactory, TenantAware;

    protected $fillable = [
        'user_id', 'client_id', 'payment_id', 'amount', 'collection_date',
        'payment_method', 'receipt_no', 'remarks', 'status',
        'approved_by', 'approved_at', 'company_id'
    ];

    protected $casts = [
        'collection_date' => 'date',
        'amount' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    public function user() { return $this->belongsTo(User::class); }
    public function client() { return $this->belongsTo(Client::class); }
    public function payment() { return $this->belongsTo(Payment::class); }
    public function approvedBy() { return $this->belongsTo(User::class, 'approved_by'); }
}
