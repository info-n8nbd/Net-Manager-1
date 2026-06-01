<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class District extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'division', 'is_active', 'is_configured'];

    protected $casts = ['is_active' => 'boolean', 'is_configured' => 'boolean'];

    public function upazilas()
    {
        return $this->hasMany(Upazila::class);
    }
}
