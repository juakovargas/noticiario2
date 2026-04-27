<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('editorial_request_candidates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('editorial_request_id')->constrained('editorial_requests')->cascadeOnDelete();
            $table->foreignId('news_item_id')->nullable()->constrained('news_items')->nullOnDelete();
            $table->string('title');
            $table->text('summary')->nullable();
            $table->text('source_hint')->nullable();
            $table->text('source_url')->nullable();
            $table->foreignId('suggested_category_id')->nullable()->constrained('news_categories')->nullOnDelete();
            $table->foreignId('suggested_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->unsignedTinyInteger('relevance_score')->default(3);
            $table->text('editorial_angle')->nullable();
            $table->text('why_it_matters')->nullable();
            $table->boolean('is_selected')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('editorial_request_candidates');
    }
};
