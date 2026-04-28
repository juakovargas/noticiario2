<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HomePageSetting extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'locale',
        'title',
        'subtitle',
        'description',
        'hero_badge',
        'primary_button_label',
        'primary_button_url',
        'secondary_button_label',
        'secondary_button_url',
        'show_latest_noticiarios',
        'latest_noticiarios_limit',
        'show_world_map_preview',
        'show_platforms_section',
        'platforms',
        'seo_title',
        'seo_description',
        'metadata',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'show_latest_noticiarios' => 'boolean',
            'latest_noticiarios_limit' => 'integer',
            'show_world_map_preview' => 'boolean',
            'show_platforms_section' => 'boolean',
            'is_active' => 'boolean',
            'platforms' => 'array',
            'metadata' => 'array',
        ];
    }
}
