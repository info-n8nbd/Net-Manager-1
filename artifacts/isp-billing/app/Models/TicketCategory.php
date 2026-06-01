<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantAware;

class TicketCategory extends Model
{
    use HasFactory, TenantAware;

    protected $fillable = ['name', 'description', 'subjects', 'priority', 'is_active', 'company_id'];

    protected $casts = ['is_active' => 'boolean', 'subjects' => 'array'];

    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'category_id');
    }
}
