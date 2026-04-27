<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seo_settings', function (Blueprint $table) {
            $table->id();
            $table->string('site_name')->nullable();
            $table->string('default_title')->nullable();
            $table->string('title_suffix')->nullable();
            $table->text('default_description')->nullable();
            $table->text('default_keywords')->nullable();
            $table->string('canonical_base_url')->nullable();
            $table->string('default_robots')->default('index,follow');
            $table->string('default_og_image')->nullable();
            $table->string('default_twitter_card')->default('summary_large_image');
            $table->string('google_site_verification')->nullable();
            $table->string('bing_site_verification')->nullable();
            $table->string('google_tag_manager_id', 50)->nullable();
            $table->string('google_analytics_id', 50)->nullable();
            $table->string('microsoft_clarity_id', 100)->nullable();
            $table->string('meta_pixel_id', 100)->nullable();
            $table->string('tiktok_pixel_id', 100)->nullable();
            $table->longText('custom_head_scripts')->nullable();
            $table->longText('custom_body_start_scripts')->nullable();
            $table->longText('custom_body_end_scripts')->nullable();
            $table->boolean('enable_tracking')->default(false);
            $table->boolean('enable_custom_scripts')->default(false);
            $table->boolean('enable_indexing')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_settings');
    }
};
