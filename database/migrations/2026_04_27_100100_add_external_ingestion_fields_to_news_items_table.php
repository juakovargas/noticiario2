<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('news_items', function (Blueprint $table): void {
            if (! Schema::hasColumn('news_items', 'external_id')) {
                $table->string('external_id')->nullable()->after('source_url');
                $table->index('external_id');
            }

            if (! Schema::hasColumn('news_items', 'imported_hash')) {
                $table->string('imported_hash')->nullable()->after('external_id');
                $table->index('imported_hash');
            }

            $table->index(['news_source_id', 'external_id'], 'news_items_source_external_idx');
            $table->index(['news_source_id', 'imported_hash'], 'news_items_source_hash_idx');
        });
    }

    public function down(): void
    {
        Schema::table('news_items', function (Blueprint $table): void {
            $table->dropIndex('news_items_source_external_idx');
            $table->dropIndex('news_items_source_hash_idx');

            if (Schema::hasColumn('news_items', 'external_id')) {
                $table->dropIndex(['external_id']);
                $table->dropColumn('external_id');
            }

            if (Schema::hasColumn('news_items', 'imported_hash')) {
                $table->dropIndex(['imported_hash']);
                $table->dropColumn('imported_hash');
            }
        });
    }
};
