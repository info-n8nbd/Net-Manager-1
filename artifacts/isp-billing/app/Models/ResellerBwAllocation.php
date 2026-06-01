<?php
namespace App\Models;

use App\Traits\TenantAware;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ResellerBwAllocation extends Model
{
    use HasFactory, TenantAware;

    protected $table = 'reseller_bw_allocations';

    protected $fillable = ['company_id', 'reseller_id', 'category_id', 'mbps', 'rate'];

    protected $casts = ['mbps' => 'float', 'rate' => 'float'];

    public function reseller()
    {
        return $this->belongsTo(Reseller::class);
    }

    public function category()
    {
        return $this->belongsTo(BandwidthCategory::class, 'category_id');
    }
}
