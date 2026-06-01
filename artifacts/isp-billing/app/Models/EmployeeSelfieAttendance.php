<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantAware;

class EmployeeSelfieAttendance extends Model
{
    use HasFactory, TenantAware;

    protected $fillable = [
        'user_id', 'employee_id', 'attendance_date', 'check_in_time',
        'selfie_path', 'latitude', 'longitude', 'location_address',
        'ip_address', 'device_info', 'type', 'remarks', 'company_id'
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function getSelfieUrlAttribute()
    {
        if ($this->selfie_path) {
            return asset('storage/' . $this->selfie_path);
        }
        return null;
    }

    public function getGoogleMapsUrlAttribute()
    {
        if ($this->latitude && $this->longitude) {
            return "https://www.google.com/maps?q={$this->latitude},{$this->longitude}";
        }
        return null;
    }

    public static function getSettings()
    {
        $path = storage_path('app/attendance_settings.json');
        if (file_exists($path)) {
            $data = json_decode(file_get_contents($path), true);
            return $data ?: self::defaultSettings();
        }
        return self::defaultSettings();
    }

    public static function defaultSettings()
    {
        return [
            'office_lat'       => null,
            'office_lng'       => null,
            'office_radius'    => 200,
            'late_time'        => '09:00',
            'early_leave_time' => '17:00',
            'office_name'      => 'Main Office',
        ];
    }

    public static function saveSettings(array $settings)
    {
        $path = storage_path('app/attendance_settings.json');
        file_put_contents($path, json_encode($settings, JSON_PRETTY_PRINT));
    }

    public static function distanceInMeters($lat1, $lon1, $lat2, $lon2)
    {
        $R = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat/2) * sin($dLat/2)
           + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
           * sin($dLon/2) * sin($dLon/2);
        return $R * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    public function isValidLocation()
    {
        $s = self::getSettings();
        if (!$s['office_lat'] || !$this->latitude) return null;
        $dist = self::distanceInMeters(
            (float)$s['office_lat'], (float)$s['office_lng'],
            (float)$this->latitude,  (float)$this->longitude
        );
        return $dist <= (float)$s['office_radius'];
    }

    public function isLateCheckIn()
    {
        $s = self::getSettings();
        if ($this->type !== 'check_in' || !$s['late_time']) return false;
        return substr($this->check_in_time, 0, 5) > $s['late_time'];
    }

    public function isEarlyLeave()
    {
        $s = self::getSettings();
        if ($this->type !== 'check_out' || !$s['early_leave_time']) return false;
        return substr($this->check_in_time, 0, 5) < $s['early_leave_time'];
    }
}
