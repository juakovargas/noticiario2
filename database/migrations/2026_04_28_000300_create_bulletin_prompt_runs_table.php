<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bulletin_prompt_runs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('bulletin_type_id')->constrained('bulletin_types')->cascadeOnDelete();
            $table->foreignId('prompt_profile_id')->nullable()->constrained('prompt_profiles')->nullOnDelete();
            $table->foreignId('edition_id')->nullable()->constrained('editions')->nullOnDelete();
            $table->foreignId('script_id')->nullable()->constrained('scripts')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->timestamp('scheduled_for')->nullable();
            $table->string('status', 50)->default('draft');
            $table->longText('generated_prompt')->nullable();
            $table->longText('ai_response_text')->nullable();
            $table->json('parsed_response')->nullable();
            $table->timestamp('prompt_generated_at')->nullable();
            $table->timestamp('response_received_at')->nullable();
            $table->timestamp('script_created_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bulletin_prompt_runs');
    }
};
