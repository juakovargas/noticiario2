<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('editorial_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('ai_provider_id')->nullable()->constrained('ai_providers')->nullOnDelete();
            $table->foreignId('ai_prompt_template_id')->nullable()->constrained('ai_prompt_templates')->nullOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignId('news_category_id')->nullable()->constrained('news_categories')->nullOnDelete();
            $table->foreignId('language_id')->nullable()->constrained('languages')->nullOnDelete();
            $table->foreignId('edition_id')->nullable()->constrained('editions')->nullOnDelete();
            $table->string('title');
            $table->string('edition_type', 50)->nullable();
            $table->unsignedInteger('target_duration_seconds')->nullable();
            $table->longText('editorial_instructions')->nullable();
            $table->string('status', 50)->default('draft');
            $table->longText('prompt_snapshot')->nullable();
            $table->longText('response_snapshot')->nullable();
            $table->json('parsed_response')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('editorial_requests');
    }
};
