<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantAware;

class ReturnedEquipment extends Model
{
    use HasFactory, TenantAware;

    protected $table = 'returned_equipments';

    protected $fillable = [
        'client_id','client_name','client_client_id','equipment_type',
        'equipment_name','serial_number','mac_address','model','brand',
        'pon_port','onu_index','condition','remarks',
        'returned_by','received_by','return_date','company_id',
        'is_restocked','restocked_at','restocked_by','restock_notes',
        'restock_type','restock_ref_id','fiber_stock_id','fiber_meter',
        'assigned_to_client_id','assigned_at',
    ];

    protected $casts = [
        'return_date'   => 'date',
        'restocked_at'  => 'datetime',
        'assigned_at'   => 'datetime',
        'is_restocked'  => 'boolean',
        'fiber_meter'   => 'decimal:2',
    ];

    public function client()           { return $this->belongsTo(Client::class); }
    public function returnedByUser()   { return $this->belongsTo(User::class, 'returned_by'); }
    public function receivedByUser()   { return $this->belongsTo(User::class, 'received_by'); }
    public function restockedByUser()  { return $this->belongsTo(User::class, 'restocked_by'); }
    public function assignedClient()   { return $this->belongsTo(Client::class, 'assigned_to_client_id'); }
    public function fiberStock()       { return $this->belongsTo(FiberStock::class, 'fiber_stock_id'); }
}
