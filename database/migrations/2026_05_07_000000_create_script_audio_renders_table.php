<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('script_audio_renders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('script_id')->constrained('scripts')->cascadeOnDelete();
            $table->string('status')->default('pending');
            $table->string('provider')->default('elevenlabs');
            $table->string('voice_id')->nullable();
            $table->string('model_id')->nullable();
            $table->string('output_format')->nullable();
            $table->longText('source_text')->nullable();
            $table->string('audio_path')->nullable();
            $table->string('audio_disk')->default('public');
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->unsignedInteger('character_count')->nullable();
            $table->text('error_message')->nullable();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();

            $table->index('script_id');
            $table->index('status');
            $table->index('provider');
            $table->index('generated_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('script_audio_renders');
    }
};
