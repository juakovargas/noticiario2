<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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
        'description',
        'language',
        'country_code',
        'is_active',
        'trust_level',
        'last_checked_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_checked_at' => 'datetime',
        ];
    }

    public function newsItems(): HasMany
    {
        return $this->hasMany(NewsItem::class);
    }
}
