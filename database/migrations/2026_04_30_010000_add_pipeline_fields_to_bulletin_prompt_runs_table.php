<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('bulletin_prompt_runs', function (Blueprint $table): void {
            if (! Schema::hasColumn('bulletin_prompt_runs', 'pipeline_status')) {
                $table->string('pipeline_status', 50)->nullable()->after('status');
            }
            if (! Schema::hasColumn('bulletin_prompt_runs', 'pipeline_started_at')) {
                $table->timestamp('pipeline_started_at')->nullable()->after('script_created_at');
            }
            if (! Schema::hasColumn('bulletin_prompt_runs', 'pipeline_finished_at')) {
                $table->timestamp('pipeline_finished_at')->nullable()->after('pipeline_started_at');
            }
            if (! Schema::hasColumn('bulletin_prompt_runs', 'pipeline_failed_at')) {
                $table->timestamp('pipeline_failed_at')->nullable()->after('pipeline_finished_at');
            }
            if (! Schema::hasColumn('bulletin_prompt_runs', 'pipeline_failed_step')) {
                $table->string('pipeline_failed_step', 100)->nullable()->after('pipeline_failed_at');
            }
            if (! Schema::hasColumn('bulletin_prompt_runs', 'pipeline_error_message')) {
                $table->text('pipeline_error_message')->nullable()->after('pipeline_failed_step');
            }
            if (! Schema::hasColumn('bulletin_prompt_runs', 'pipeline_metadata')) {
                $table->json('pipeline_metadata')->nullable()->after('metadata');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bulletin_prompt_runs', function (Blueprint $table): void {
            $columns = ['pipeline_status','pipeline_started_at','pipeline_finished_at','pipeline_failed_at','pipeline_failed_step','pipeline_error_message','pipeline_metadata'];
            $existing = array_values(array_filter($columns, fn (string $column): bool => Schema::hasColumn('bulletin_prompt_runs', $column)));
            if ($existing !== []) {
                $table->dropColumn($existing);
            }
        });
    }
};
