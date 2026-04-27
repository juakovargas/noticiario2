<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('editorial_schedule_runs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('editorial_schedule_id')->constrained('editorial_schedules')->cascadeOnDelete();
            $table->foreignId('edition_id')->nullable()->constrained('editions')->nullOnDelete();

            if (Schema::hasTable('editorial_requests')) {
                $table->foreignId('editorial_request_id')->nullable()->constrained('editorial_requests')->nullOnDelete();
            } else {
                $table->unsignedBigInteger('editorial_request_id')->nullable();
            }

            $table->foreignId('script_id')->nullable()->constrained('scripts')->nullOnDelete();
            $table->timestamp('scheduled_for')->nullable();
            $table->string('status', 50)->default('pending');
            $table->longText('generated_prompt')->nullable();
            $table->longText('ai_response_text')->nullable();
            $table->timestamp('prompt_generated_at')->nullable();
            $table->timestamp('response_received_at')->nullable();
            $table->timestamp('script_created_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('editorial_schedule_runs');
    }
};
