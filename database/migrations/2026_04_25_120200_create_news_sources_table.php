<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('news_sources', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('type');
            $table->string('url')->nullable();
            $table->string('feed_url')->nullable();
            $table->text('description')->nullable();
            $table->string('language', 10)->nullable();
            $table->string('country_code', 10)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedTinyInteger('trust_level')->default(3);
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('news_sources');
    }
};
