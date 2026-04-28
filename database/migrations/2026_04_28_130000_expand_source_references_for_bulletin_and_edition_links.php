<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('source_references', function (Blueprint $table): void {
            if (! Schema::hasColumn('source_references', 'bulletin_prompt_run_id')) {
                $table->foreignId('bulletin_prompt_run_id')->nullable()->after('id')->constrained('bulletin_prompt_runs')->nullOnDelete();
            }

            if (! Schema::hasColumn('source_references', 'edition_id')) {
                $table->foreignId('edition_id')->nullable()->after('news_item_id')->constrained('editions')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('source_references', function (Blueprint $table): void {
            if (Schema::hasColumn('source_references', 'edition_id')) {
                $table->dropConstrainedForeignId('edition_id');
            }

            if (Schema::hasColumn('source_references', 'bulletin_prompt_run_id')) {
                $table->dropConstrainedForeignId('bulletin_prompt_run_id');
            }
        });
    }
};
