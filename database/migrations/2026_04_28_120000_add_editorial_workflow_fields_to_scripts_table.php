<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scripts', function (Blueprint $table): void {
            if (! Schema::hasColumn('scripts', 'bulletin_prompt_run_id')) {
                $table->foreignId('bulletin_prompt_run_id')->nullable()->constrained('bulletin_prompt_runs')->nullOnDelete()->after('edition_id');
            }

            if (! Schema::hasColumn('scripts', 'final_title')) {
                $table->string('final_title')->nullable()->after('title');
            }

            if (! Schema::hasColumn('scripts', 'production_name')) {
                $table->string('production_name')->nullable()->after('final_title');
            }

            if (! Schema::hasColumn('scripts', 'public_description')) {
                $table->longText('public_description')->nullable()->after('outro');
            }

            if (! Schema::hasColumn('scripts', 'short_description')) {
                $table->text('short_description')->nullable()->after('public_description');
            }

            if (! Schema::hasColumn('scripts', 'hashtags')) {
                $table->json('hashtags')->nullable()->after('short_description');
            }

            if (! Schema::hasColumn('scripts', 'social_copy')) {
                $table->longText('social_copy')->nullable()->after('hashtags');
            }

            if (! Schema::hasColumn('scripts', 'target_platforms')) {
                $table->json('target_platforms')->nullable()->after('social_copy');
            }

            if (! Schema::hasColumn('scripts', 'seo_title')) {
                $table->string('seo_title')->nullable()->after('target_platforms');
            }

            if (! Schema::hasColumn('scripts', 'seo_description')) {
                $table->text('seo_description')->nullable()->after('seo_title');
            }

            if (! Schema::hasColumn('scripts', 'production_status')) {
                $table->string('production_status', 50)->default('draft')->after('review_status');
            }

            if (! Schema::hasColumn('scripts', 'ready_for_production_at')) {
                $table->timestamp('ready_for_production_at')->nullable()->after('production_status');
            }

            if (! Schema::hasColumn('scripts', 'ready_for_production_by')) {
                $table->foreignId('ready_for_production_by')->nullable()->constrained('users')->nullOnDelete()->after('ready_for_production_at');
            }
        });

        Schema::table('scripts', function (Blueprint $table): void {
            $table->index('production_status');
        });

        // Safe backfill for legacy scripts.
        \App\Models\Script::query()
            ->whereNull('final_title')
            ->orWhereNull('production_name')
            ->chunkById(200, function ($scripts): void {
                foreach ($scripts as $script) {
                    $updates = [];

                    if (empty($script->final_title)) {
                        $updates['final_title'] = $script->title;
                    }

                    if (empty($script->production_name)) {
                        $updates['production_name'] = $script->title;
                    }

                    if ($updates !== []) {
                        $script->update($updates);
                    }
                }
            });
    }

    public function down(): void
    {
        Schema::table('scripts', function (Blueprint $table): void {
            if (Schema::hasColumn('scripts', 'ready_for_production_by')) {
                $table->dropConstrainedForeignId('ready_for_production_by');
            }

            if (Schema::hasColumn('scripts', 'bulletin_prompt_run_id')) {
                $table->dropConstrainedForeignId('bulletin_prompt_run_id');
            }

            if (Schema::hasColumn('scripts', 'production_status')) {
                $table->dropIndex(['production_status']);
            }

            $columns = [
                'final_title',
                'production_name',
                'public_description',
                'short_description',
                'hashtags',
                'social_copy',
                'target_platforms',
                'seo_title',
                'seo_description',
                'production_status',
                'ready_for_production_at',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('scripts', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
