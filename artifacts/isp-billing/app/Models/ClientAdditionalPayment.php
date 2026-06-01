<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantAware;

class ClientAdditionalPayment extends Model
{
    use HasFactory;
    use TenantAware;

    protected $fillable = [
        'client_id',
        'payment_type',
        'custom_name',
        'description',
        'amount',
        'payment_date',
        'payment_method_id',
        'collected_by',
        'status',
        'receipt_no',
        'money_receipt_no',
        'transaction_id',
        'sender_number',
        'remarks',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
    ];

    const TYPE_ADAPTER = 'adapter';
    const TYPE_ONU = 'onu';
    const TYPE_CABLE = 'cable';
    const TYPE_INSTALLATION = 'installation';
    const TYPE_ROUTER = 'router';
    const TYPE_CUSTOM = 'custom';
    const TYPE_OTHER = 'other';

    public static function getTypes(): array
    {
        return [
            self::TYPE_ADAPTER => 'Adapter',
            self::TYPE_ONU => 'ONU Device',
            self::TYPE_CABLE => 'Cable/Wire',
            self::TYPE_INSTALLATION => 'Installation Charge',
            self::TYPE_ROUTER => 'Router',
            self::TYPE_CUSTOM => 'Custom (Enter Name)',
            self::TYPE_OTHER => 'Other',
        ];
    }

    public function getDisplayName(): string
    {
        if ($this->payment_type === self::TYPE_CUSTOM && $this->custom_name) {
            return $this->custom_name;
        }
        return self::getTypes()[$this->payment_type] ?? $this->payment_type;
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function collector()
    {
        return $this->belongsTo(User::class, 'collected_by');
    }

    public function generateReceiptNo(): string
    {
        $prefix = 'RCP-';
        $date = now()->format('Ymd');
        $count = self::whereDate('created_at', now()->toDateString())->count() + 1;
        return $prefix . $date . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }
}
