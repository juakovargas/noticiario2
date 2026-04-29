<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('bulletin_prompt_runs', function (Blueprint $table): void {
            $table->string('pipeline_status', 50)->nullable()->after('status');
            $table->timestamp('pipeline_started_at')->nullable()->after('script_created_at');
            $table->timestamp('pipeline_finished_at')->nullable()->after('pipeline_started_at');
            $table->timestamp('pipeline_failed_at')->nullable()->after('pipeline_finished_at');
            $table->text('pipeline_error_message')->nullable()->after('pipeline_failed_at');
            $table->json('pipeline_metadata')->nullable()->after('metadata');
        });
    }

    public function down(): void
    {
        Schema::table('bulletin_prompt_runs', function (Blueprint $table): void {
            $table->dropColumn(['pipeline_status','pipeline_started_at','pipeline_finished_at','pipeline_failed_at','pipeline_error_message','pipeline_metadata']);
        });
    }
};
