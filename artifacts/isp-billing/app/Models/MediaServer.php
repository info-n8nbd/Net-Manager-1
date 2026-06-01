<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MediaServer extends Model
{
    protected $fillable = [
        'company_id', 'name', 'type', 'url', 'logo_url',
        'description', 'sort_order', 'is_active',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order')->orderBy('name');
    }

    public function getTypeLabel(): string
    {
        return match($this->type) {
            'ftp'     => 'FTP Movie Server',
            'live_tv' => 'Live TV Server',
            default   => 'Server',
        };
    }

    public function getTypeIcon(): string
    {
        return match($this->type) {
            'ftp'     => 'fas fa-film',
            'live_tv' => 'fas fa-tv',
            default   => 'fas fa-server',
        };
    }

    public function getTypeBadgeColor(): string
    {
        return match($this->type) {
            'ftp'     => '#e74c3c',
            'live_tv' => '#8e44ad',
            default   => '#2980b9',
        };
    }
}
