<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NewsCategoryTranslation extends Model
{
    use HasFactory;

    protected $fillable = [
        'news_category_id',
        'language_code',
        'name',
        'description',
    ];

    public function newsCategory(): BelongsTo
    {
        return $this->belongsTo(NewsCategory::class);
    }
}
