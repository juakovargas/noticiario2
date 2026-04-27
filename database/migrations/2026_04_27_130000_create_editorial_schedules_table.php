<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('editorial_schedules', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignId('news_category_id')->nullable()->constrained('news_categories')->nullOnDelete();
            $table->foreignId('language_id')->nullable()->constrained('languages')->nullOnDelete();

            if (Schema::hasTable('editorial_templates')) {
                $table->foreignId('editorial_template_id')->nullable()->constrained('editorial_templates')->nullOnDelete();
            } else {
                $table->unsignedBigInteger('editorial_template_id')->nullable();
            }

            if (Schema::hasTable('ai_prompt_templates')) {
                $table->foreignId('ai_prompt_template_id')->nullable()->constrained('ai_prompt_templates')->nullOnDelete();
            } else {
                $table->unsignedBigInteger('ai_prompt_template_id')->nullable();
            }

            $table->string('edition_type', 50)->default('morning');
            $table->string('frequency_type', 50)->default('daily');
            $table->time('scheduled_time')->nullable();
            $table->date('scheduled_date')->nullable();
            $table->json('weekdays')->nullable();
            $table->string('timezone', 100)->nullable();
            $table->unsignedInteger('target_duration_seconds')->nullable();
            $table->string('tone', 100)->nullable();
            $table->boolean('manual_ai_mode')->default(true);
            $table->boolean('is_active')->default(true);
            $table->longText('editorial_instructions')->nullable();
            $table->longText('output_instructions')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('editorial_schedules');
    }
};
