<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prompt_profiles', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->unsignedTinyInteger('happiness_level')->default(5);
            $table->unsignedTinyInteger('optimism_level')->default(5);
            $table->unsignedTinyInteger('seriousness_level')->default(5);
            $table->unsignedTinyInteger('humor_level')->default(0);
            $table->unsignedTinyInteger('irony_level')->default(0);
            $table->unsignedTinyInteger('formality_level')->default(5);
            $table->unsignedTinyInteger('negativity_tolerance')->default(5);
            $table->unsignedTinyInteger('controversy_tolerance')->default(5);
            $table->unsignedTinyInteger('source_strictness_level')->default(7);
            $table->string('target_audience')->nullable();
            $table->string('presenter_style')->nullable();
            $table->json('forbidden_topics')->nullable();
            $table->json('preferred_topics')->nullable();
            $table->longText('style_instructions')->nullable();
            $table->longText('fact_checking_instructions')->nullable();
            $table->longText('output_instructions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prompt_profiles');
    }
};
