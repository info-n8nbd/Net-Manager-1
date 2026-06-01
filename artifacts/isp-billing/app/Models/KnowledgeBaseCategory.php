<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KnowledgeBaseCategory extends Model
{
    protected $fillable = [
        'name', 'slug', 'description', 'icon', 'sort_order', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function articles()
    {
        return $this->hasMany(KnowledgeBaseArticle::class, 'category_id');
    }

    public function publishedArticles()
    {
        return $this->hasMany(KnowledgeBaseArticle::class, 'category_id')->where('is_published', true);
    }
}
