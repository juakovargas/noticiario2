<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class NewsCategory extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'parent_id',
        'name',
        'slug',
        'description',
        'color',
        'icon',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function newsItems(): HasMany
    {
        return $this->hasMany(NewsItem::class);
    }

    public function editions(): BelongsToMany
    {
        return $this->belongsToMany(Edition::class, 'edition_news_category');
    }
}
