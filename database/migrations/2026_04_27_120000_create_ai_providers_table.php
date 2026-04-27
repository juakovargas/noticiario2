<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_providers', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('provider_type', 50);
            $table->string('base_url')->nullable();
            $table->string('api_key_env')->nullable();
            $table->string('default_model')->nullable();
            $table->boolean('supports_web_search')->default(false);
            $table->boolean('supports_json_mode')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->unsignedInteger('monthly_budget_cents')->nullable();
            $table->unsignedInteger('cost_per_1k_input_tokens_cents')->nullable();
            $table->unsignedInteger('cost_per_1k_output_tokens_cents')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_providers');
    }
};
