<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('news_sources', function (Blueprint $table): void {
            if (! Schema::hasColumn('news_sources', 'default_news_category_id')) {
                $table->foreignId('default_news_category_id')->nullable()->after('feed_url')->constrained('news_categories')->nullOnDelete();
            }

            if (! Schema::hasColumn('news_sources', 'default_location_id')) {
                $table->foreignId('default_location_id')->nullable()->after('default_news_category_id')->constrained('locations')->nullOnDelete();
            }

            if (! Schema::hasColumn('news_sources', 'is_demo')) {
                $table->boolean('is_demo')->default(false)->after('is_active');
            }

            if (! Schema::hasColumn('news_sources', 'ingestion_notes')) {
                $table->text('ingestion_notes')->nullable()->after('description');
            }

            if (! Schema::hasColumn('news_sources', 'last_imported_at')) {
                $table->timestamp('last_imported_at')->nullable()->after('last_checked_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('news_sources', function (Blueprint $table): void {
            if (Schema::hasColumn('news_sources', 'default_news_category_id')) {
                $table->dropConstrainedForeignId('default_news_category_id');
            }

            if (Schema::hasColumn('news_sources', 'default_location_id')) {
                $table->dropConstrainedForeignId('default_location_id');
            }

            if (Schema::hasColumn('news_sources', 'is_demo')) {
                $table->dropColumn('is_demo');
            }

            if (Schema::hasColumn('news_sources', 'ingestion_notes')) {
                $table->dropColumn('ingestion_notes');
            }

            if (Schema::hasColumn('news_sources', 'last_imported_at')) {
                $table->dropColumn('last_imported_at');
            }
        });
    }
};
