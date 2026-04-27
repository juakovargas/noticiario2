<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SeoSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'site_name',
        'default_title',
        'title_suffix',
        'default_description',
        'default_keywords',
        'canonical_base_url',
        'default_robots',
        'default_og_image',
        'default_twitter_card',
        'google_site_verification',
        'bing_site_verification',
        'google_tag_manager_id',
        'google_analytics_id',
        'microsoft_clarity_id',
        'meta_pixel_id',
        'tiktok_pixel_id',
        'custom_head_scripts',
        'custom_body_start_scripts',
        'custom_body_end_scripts',
        'enable_tracking',
        'enable_custom_scripts',
        'enable_indexing',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'enable_tracking' => 'boolean',
            'enable_custom_scripts' => 'boolean',
            'enable_indexing' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public static function current(): self
    {
        return self::query()->firstOrCreate([], [
            'site_name' => 'Noticiario',
            'default_title' => 'Noticiario',
            'title_suffix' => 'Noticiario',
            'default_description' => 'Editorial automation platform for short digital news bulletins.',
            'default_robots' => 'index,follow',
            'default_twitter_card' => 'summary_large_image',
            'enable_tracking' => false,
            'enable_custom_scripts' => false,
            'enable_indexing' => true,
        ]);
    }
}
