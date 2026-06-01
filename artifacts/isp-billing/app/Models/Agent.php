<?php

namespace App\Models;

use App\Traits\TenantAware;
use Illuminate\Database\Eloquent\Model;

class Agent extends Model
{
    use TenantAware;
    protected $fillable = [
        'agent_id', 'name', 'username', 'password', 'default_commission',
        'father_name', 'mother_name', 'date_of_birth', 'joining_date',
        'gender', 'marital_status', 'blood_group', 'national_id',
        'present_address', 'permanent_address', 'personal_contact',
        'whatsapp_no', 'office_contact', 'family_contact',
        'first_reference_no', 'second_reference_no', 'email', 'image',
        'status', 'company_id', 'created_by',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'joining_date' => 'date',
        'default_commission' => 'decimal:2',
    ];

    protected $hidden = ['password'];

    public function commissions()
    {
        return $this->hasMany(AgentCommission::class);
    }
}
