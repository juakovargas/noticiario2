<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class NewsSource extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'type',
        'url',
        'feed_url',
        'default_news_category_id',
        'default_location_id',
        'description',
        'ingestion_notes',
        'language',
        'country_code',
        'is_active',
        'is_demo',
        'trust_level',
        'last_checked_at',
        'last_imported_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_demo' => 'boolean',
            'last_checked_at' => 'datetime',
            'last_imported_at' => 'datetime',
        ];
    }

    public function newsItems(): HasMany
    {
        return $this->hasMany(NewsItem::class);
    }

    public function defaultCategory(): BelongsTo
    {
        return $this->belongsTo(NewsCategory::class, 'default_news_category_id');
    }

    public function defaultLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'default_location_id');
    }
}
