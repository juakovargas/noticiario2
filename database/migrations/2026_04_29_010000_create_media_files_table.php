<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_files', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('disk')->default('public');
            $table->string('directory')->nullable();
            $table->string('filename');
            $table->string('original_name');
            $table->string('path')->unique();
            $table->text('url')->nullable();
            $table->string('mime_type')->nullable();
            $table->string('extension', 20)->nullable();
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->string('media_type', 50)->default('file');
            $table->string('collection', 100)->nullable();
            $table->string('title')->nullable();
            $table->string('alt_text')->nullable();
            $table->text('caption')->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->string('checksum', 128)->nullable();
            $table->string('visibility', 50)->default('public');
            $table->string('status', 50)->default('active');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('uploaded_by');
            $table->index('media_type');
            $table->index('collection');
            $table->index('status');
            $table->index('checksum');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_files');
    }
};
