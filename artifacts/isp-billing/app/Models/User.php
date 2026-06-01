<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles, HasApiTokens;

    protected $fillable = [
        'name', 'email', 'password', 'phone', 'avatar', 'user_type', 'is_active',
        'two_factor_secret', 'two_factor_recovery_codes', 'two_factor_confirmed_at',
        'company_id',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function client()
    {
        return $this->hasOne(Client::class);
    }

    public function employee()
    {
        return $this->hasOne(Employee::class);
    }

    public function reseller()
    {
        return $this->hasOne(Reseller::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function isSuperAdmin()
    {
        return $this->user_type === 'super_admin';
    }

    public function isAdmin()
    {
        return $this->user_type === 'admin';
    }

    public function isClient()
    {
        return $this->user_type === 'client';
    }

    public function isTechnician()
    {
        return $this->user_type === 'technician' || $this->hasRole('technician');
    }

    public function technicianAssignments()
    {
        return $this->hasMany(TechnicianAssignment::class, 'technician_id');
    }

    public function assignedTickets()
    {
        return $this->hasMany(Ticket::class, 'technician_id');
    }

    public function collectedPayments()
    {
        return $this->hasMany(Payment::class, 'collected_by');
    }

    public function zones()
    {
        return $this->belongsToMany(\App\Models\Zone::class, 'zone_user', 'user_id', 'zone_id');
    }

    public function dailyCollections()
    {
        return $this->hasMany(\App\Models\DailyCollection::class, 'user_id');
    }

    public function isSubReseller()
    {
        return $this->user_type === 'sub_reseller';
    }

    public function isManager()
    {
        return $this->user_type === 'manager';
    }

    public function isCardDistributor()
    {
        return $this->user_type === 'card_distributor';
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function managerAssignment()
    {
        return $this->hasOne(ManagerAssignment::class);
    }

    public function apiRefreshTokens()
    {
        return $this->hasMany(ApiRefreshToken::class);
    }

    public function allowedPaymentMethods(): \Illuminate\Database\Eloquent\Collection
    {
        $query = PaymentMethod::where('is_active', true)->orderBy('id');
        if ($this->company_id) {
            $query->where('company_id', $this->company_id);
        }
        $all = $query->get()->unique('name')->values();

        if ($this->user_type === 'admin') {
            return $all;
        }

        $userPerms = PaymentMethodPerm::withoutGlobalScope('tenant')->where('user_id', $this->id)->pluck('payment_method_id');
        if ($userPerms->count() > 0) {
            return $all->whereIn('id', $userPerms)->values();
        }

        $role = $this->roles->first();
        if ($role) {
            $rolePerms = PaymentMethodPerm::withoutGlobalScope('tenant')->where('role_id', $role->id)->pluck('payment_method_id');
            if ($rolePerms->count() > 0) {
                return $all->whereIn('id', $rolePerms)->values();
            }
        }

        return $all;
    }
}
