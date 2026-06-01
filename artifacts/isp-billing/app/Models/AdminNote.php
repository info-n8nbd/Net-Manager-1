<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminNote extends Model
{
    protected $fillable = [
        "user_id", "company_id", "title", "content",
        "color", "is_pinned", "label",
    ];

    protected $casts = [
        "is_pinned" => "boolean",
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
