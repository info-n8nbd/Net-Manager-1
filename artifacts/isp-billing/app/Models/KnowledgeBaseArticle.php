<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantAware;

class KnowledgeBaseArticle extends Model
{
    use TenantAware;

    protected $fillable = [
        'category_id', 'title', 'slug', 'content', 'audience',
        'views', 'is_published', 'is_featured', 'created_by',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'is_featured' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(KnowledgeBaseCategory::class, 'category_id');
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function incrementViews(): void
    {
        $this->increment('views');
    }
}
