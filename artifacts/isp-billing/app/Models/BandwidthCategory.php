<?php
namespace App\Models;

use App\Traits\TenantAware;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BandwidthCategory extends Model
{
    use HasFactory, TenantAware;

    protected $table = 'bandwidth_categories';

    protected $fillable = [
        'company_id', 'key', 'label', 'icon', 'color',
        'sort_order', 'is_system', 'mbps_field', 'rate_field',
    ];

    protected $casts = [
        'is_system'  => 'boolean',
        'sort_order' => 'integer',
    ];

    public function allocations()
    {
        return $this->hasMany(ResellerBwAllocation::class, 'category_id');
    }

    public static function orderedForCompany(): \Illuminate\Database\Eloquent\Collection
    {
        return static::orderBy('sort_order')->orderBy('id')->get();
    }

    public static function getStandardCategories(): array
    {
        $defaults = [
            "intl"  => ["key"=>"intl",  "label"=>"International (Raw)",                "mbps_field"=>"raw_bandwidth",   "rate_field"=>"intl_rate",  "icon"=>"fas fa-globe",          "color"=>"secondary"],
            "ggc"   => ["key"=>"ggc",   "label"=>"GGC (Google Global Cache)",           "mbps_field"=>"ggc_bandwidth",   "rate_field"=>"ggc_rate",   "icon"=>"fab fa-google",         "color"=>"info"],
            "fna"   => ["key"=>"fna",   "label"=>"FNA (Facebook Network Appliance)",    "mbps_field"=>"fna_bandwidth",   "rate_field"=>"fna_rate",   "icon"=>"fab fa-facebook",       "color"=>"primary"],
            "cdn"   => ["key"=>"cdn",   "label"=>"CDN (Content Delivery Network)",      "mbps_field"=>"cdn_bandwidth",   "rate_field"=>"cdn_rate",   "icon"=>"fas fa-cloud",          "color"=>"purple"],
            "bdix"  => ["key"=>"bdix",  "label"=>"BDIX (Bangladesh Internet Exchange)", "mbps_field"=>"bdix_bandwidth",  "rate_field"=>"bdix_rate",  "icon"=>"fas fa-network-wired",  "color"=>"success"],
            "cache" => ["key"=>"cache", "label"=>"Private Cache / Mirror",              "mbps_field"=>"cache_bandwidth", "rate_field"=>"cache_rate", "icon"=>"fas fa-hdd",            "color"=>"warning"],
        ];

        foreach (static::where("is_system", true)->get() as $dbCat) {
            if (isset($defaults[$dbCat->key])) {
                $defaults[$dbCat->key]["label"] = $dbCat->label;
                $defaults[$dbCat->key]["icon"]  = $dbCat->icon;
                $defaults[$dbCat->key]["color"] = $dbCat->color;
                if ($dbCat->mbps_field) { $defaults[$dbCat->key]["mbps_field"] = $dbCat->mbps_field; }
                if ($dbCat->rate_field) { $defaults[$dbCat->key]["rate_field"] = $dbCat->rate_field; }
            }
        }

        return $defaults;
    }

    public static function colorToHex(string $color): string
    {
        $map = [
            "primary"   => "#007bff",
            "secondary" => "#6c757d",
            "success"   => "#28a745",
            "danger"    => "#dc3545",
            "warning"   => "#fd7e14",
            "info"      => "#17a2b8",
            "purple"    => "#6f42c1",
            "dark"      => "#343a40",
        ];
        if (str_starts_with(trim($color), "#")) { return trim($color); }
        return $map[strtolower(trim($color))] ?? "#6c757d";
    }

}
