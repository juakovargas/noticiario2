<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('edition_news_item', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('edition_id')->constrained('editions')->cascadeOnDelete();
            $table->foreignId('news_item_id')->constrained('news_items')->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->text('editorial_angle')->nullable();
            $table->boolean('included_in_script')->default(true);
            $table->timestamps();

            $table->unique(['edition_id', 'news_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('edition_news_item');
    }
};
