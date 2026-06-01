<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantAware;

class BandwidthAdjustment extends Model
{
    use TenantAware;

    protected $fillable = [
        'reseller_id', 'category_key', 'effective_date',
        'old_mbps', 'new_mbps', 'rate', 'reason',
        'created_by', 'company_id',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'old_mbps'       => 'float',
        'new_mbps'       => 'float',
        'rate'           => 'float',
    ];

    public static $categoryLabels = [
        'intl'  => 'International (Raw)',
        'ggc'   => 'GGC (Google Global Cache)',
        'fna'   => 'FNA (Facebook Network Appliance)',
        'cdn'   => 'CDN (Content Delivery Network)',
        'bdix'  => 'BDIX (Bangladesh Internet Exchange)',
        'cache' => 'Private Cache / Mirror',
    ];

    public static $categoryIcons = [
        'intl'  => 'fas fa-globe',
        'ggc'   => 'fab fa-google',
        'fna'   => 'fab fa-facebook',
        'cdn'   => 'fas fa-cloud',
        'bdix'  => 'fas fa-network-wired',
        'cache' => 'fas fa-hdd',
    ];

    public function reseller()
    {
        return $this->belongsTo(Reseller::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function getCategoryLabelAttribute(): string
    {
        if (isset(static::$categoryLabels[$this->category_key])) {
            return static::$categoryLabels[$this->category_key];
        }
        // Custom category: resolve label from BandwidthCategory
        if (str_starts_with($this->category_key, 'custom_')) {
            $catId = (int) str_replace('custom_', '', $this->category_key);
            $cat   = \App\Models\BandwidthCategory::find($catId);
            return $cat ? $cat->label : $this->category_key;
        }
        return $this->category_key;
    }

    public function getCategoryIconAttribute(): string
    {
        if (isset(static::$categoryIcons[$this->category_key])) {
            return static::$categoryIcons[$this->category_key];
        }
        // Custom category: resolve icon from BandwidthCategory
        if (str_starts_with($this->category_key, 'custom_')) {
            $catId = (int) str_replace('custom_', '', $this->category_key);
            $cat   = \App\Models\BandwidthCategory::find($catId);
            return ($cat && $cat->icon) ? $cat->icon : 'fas fa-network-wired';
        }
        return 'fas fa-chart-bar';
    }
}
