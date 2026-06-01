<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantAware;

class WhatsappTemplate extends Model
{
    use TenantAware;

    protected $table = 'whatsapp_templates';

    protected $fillable = [
        'name', 'code', 'content', 'language', 'category',
        'status', 'variables', 'is_active',
    ];

    protected $casts = [
        'variables' => 'array',
        'is_active' => 'boolean',
    ];

    public function renderContent(array $data): string
    {
        $content = $this->content;
        foreach ($data as $key => $value) {
            $content = str_replace('{' . $key . '}', $value, $content);
        }
        return $content;
    }
}
