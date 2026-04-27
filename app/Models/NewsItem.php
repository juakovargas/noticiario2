<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class NewsItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'news_source_id',
        'news_category_id',
        'location_id',
        'title',
        'slug',
        'summary',
        'body',
        'source_url',
        'external_id',
        'imported_hash',
        'author',
        'language',
        'published_at',
        'collected_at',
        'status',
        'editorial_priority',
        'is_evergreen',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'collected_at' => 'datetime',
            'is_evergreen' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(NewsSource::class, 'news_source_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(NewsCategory::class, 'news_category_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function editions(): BelongsToMany
    {
        return $this->belongsToMany(Edition::class, 'edition_news_item')
            ->withPivot(['sort_order', 'editorial_angle', 'included_in_script'])
            ->withTimestamps();
    }

    public function sourceReferences(): HasMany
    {
        return $this->hasMany(SourceReference::class);
    }
}
