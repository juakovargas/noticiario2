<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Edition extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'location_id',
        'title',
        'slug',
        'edition_type',
        'scheduled_for',
        'language',
        'status',
        'target_duration_seconds',
        'description',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_for' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function newsItems(): BelongsToMany
    {
        return $this->belongsToMany(NewsItem::class, 'edition_news_item')
            ->withPivot(['sort_order', 'editorial_angle', 'included_in_script'])
            ->withTimestamps();
    }

    public function scripts(): HasMany
    {
        return $this->hasMany(Script::class);
    }
}
