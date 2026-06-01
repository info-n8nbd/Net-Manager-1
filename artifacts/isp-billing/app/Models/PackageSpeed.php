<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PackageSpeed extends Model
{
    use HasFactory;

    protected $fillable = ['package_id', 'time_from', 'time_to', 'download_speed', 'upload_speed'];

    public function package()
    {
        return $this->belongsTo(Package::class);
    }
}
