<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InstrumentCategory extends Model
{
    protected $fillable = ['name', 'description', 'parent_id', 'is_active', 'has_serial'];

    protected $casts = [
        'is_active' => 'boolean',
        'has_serial' => 'boolean',
    ];

    public function parent()
    {
        return $this->belongsTo(InstrumentCategory::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(InstrumentCategory::class, 'parent_id')->orderBy('name');
    }

    public function instruments()
    {
        return $this->hasMany(Instrument::class, 'category', 'name');
    }

    public function scopeParentCategories($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
