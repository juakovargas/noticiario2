<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bulletin_types', function (Blueprint $table): void {
            $table->string('coverage_mode', 50)->nullable()->default('previous_period')->after('default_timezone');
            $table->integer('coverage_starts_offset_minutes')->nullable()->after('coverage_mode');
            $table->integer('coverage_ends_offset_minutes')->nullable()->after('coverage_starts_offset_minutes');
            $table->text('coverage_description')->nullable()->after('coverage_ends_offset_minutes');
            $table->boolean('include_future_agenda')->default(false)->after('coverage_description');
            $table->boolean('include_historical_context')->default(false)->after('include_future_agenda');
            $table->unsignedTinyInteger('max_news_items')->nullable()->after('include_historical_context');
            $table->unsignedTinyInteger('min_news_items')->nullable()->after('max_news_items');
            $table->string('prompt_language', 10)->nullable()->default('es')->after('min_news_items');
            $table->string('output_mode', 50)->nullable()->default('plain_final_script')->after('prompt_language');
        });
    }

    public function down(): void
    {
        Schema::table('bulletin_types', function (Blueprint $table): void {
            $table->dropColumn([
                'coverage_mode',
                'coverage_starts_offset_minutes',
                'coverage_ends_offset_minutes',
                'coverage_description',
                'include_future_agenda',
                'include_historical_context',
                'max_news_items',
                'min_news_items',
                'prompt_language',
                'output_mode',
            ]);
        });
    }
};
