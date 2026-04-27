<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('script_review_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('script_id')->constrained('scripts')->cascadeOnDelete();
            $table->foreignId('news_item_id')->nullable()->constrained('news_items')->nullOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('type', 50)->default('script_block');
            $table->string('title')->nullable();
            $table->longText('content')->nullable();
            $table->json('source_hints')->nullable();
            $table->string('verification_status', 50)->default('pending');
            $table->longText('verification_notes')->nullable();
            $table->string('required_action', 100)->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['script_id', 'sort_order']);
            $table->index(['script_id', 'verification_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('script_review_items');
    }
};
