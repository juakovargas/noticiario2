<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('source_references', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('news_item_id')->nullable()->constrained('news_items')->nullOnDelete();
            $table->foreignId('script_id')->nullable()->constrained('scripts')->nullOnDelete();

            if (Schema::hasTable('script_review_items')) {
                $table->foreignId('script_review_item_id')->nullable()->constrained('script_review_items')->nullOnDelete();
            } else {
                $table->unsignedBigInteger('script_review_item_id')->nullable();
            }

            if (Schema::hasTable('editorial_schedule_runs')) {
                $table->foreignId('editorial_schedule_run_id')->nullable()->constrained('editorial_schedule_runs')->nullOnDelete();
            } else {
                $table->unsignedBigInteger('editorial_schedule_run_id')->nullable();
            }

            $table->string('title')->nullable();
            $table->string('source_name')->nullable();
            $table->text('source_url')->nullable();
            $table->string('source_type', 50)->default('web');
            $table->string('verification_status', 50)->default('pending');
            $table->unsignedTinyInteger('trust_level')->nullable();
            $table->foreignId('checked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('checked_at')->nullable();
            $table->longText('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['script_id', 'source_type']);
            $table->index(['verification_status', 'checked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('source_references');
    }
};
