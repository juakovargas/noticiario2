<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EditorialRequestCandidate extends Model
{
    use HasFactory;

    protected $fillable = [
        'editorial_request_id',
        'news_item_id',
        'title',
        'summary',
        'source_hint',
        'source_url',
        'suggested_category_id',
        'suggested_location_id',
        'relevance_score',
        'editorial_angle',
        'why_it_matters',
        'is_selected',
        'sort_order',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'is_selected' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function editorialRequest(): BelongsTo
    {
        return $this->belongsTo(EditorialRequest::class);
    }

    public function newsItem(): BelongsTo
    {
        return $this->belongsTo(NewsItem::class);
    }

    public function suggestedCategory(): BelongsTo
    {
        return $this->belongsTo(NewsCategory::class, 'suggested_category_id');
    }

    public function suggestedLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'suggested_location_id');
    }
}
