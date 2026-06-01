<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantAware;

class LoginLog extends Model
{
    use TenantAware;


    protected static function boot()
    {
        parent::boot();
        static::created(function ($log) {
            try {
                $companyId = $log->company_id;
                $query = static::withoutGlobalScopes();
                if ($companyId) { $query->where('company_id', $companyId); }
                $total = $query->count();
                if ($total > 100) {
                    $q2 = static::withoutGlobalScopes();
                    if ($companyId) { $q2->where('company_id', $companyId); }
                    $ids = $q2->orderBy('id','asc')->limit($total - 100)->pluck('id');
                    static::withoutGlobalScopes()->whereIn('id', $ids)->delete();
                }
            } catch (\Exception $e) {}
        });
    }

    protected $fillable = [
        'user_id', 'email', 'ip_address', 'user_agent', 'status',
        'failure_reason', 'country', 'city', 'latitude', 'longitude', 'location_address', 'session_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }
}
