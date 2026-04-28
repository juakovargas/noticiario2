<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('home_page_settings')) {
            return;
        }

        Schema::create('home_page_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('locale', 10)->nullable();
            $table->string('title');
            $table->text('subtitle')->nullable();
            $table->longText('description')->nullable();
            $table->string('hero_badge')->nullable();
            $table->string('primary_button_label')->nullable();
            $table->string('primary_button_url')->nullable();
            $table->string('secondary_button_label')->nullable();
            $table->string('secondary_button_url')->nullable();
            $table->boolean('show_latest_noticiarios')->default(true);
            $table->unsignedTinyInteger('latest_noticiarios_limit')->default(6);
            $table->boolean('show_world_map_preview')->default(true);
            $table->boolean('show_platforms_section')->default(true);
            $table->json('platforms')->nullable();
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->json('metadata')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['locale', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('home_page_settings');
    }
};
