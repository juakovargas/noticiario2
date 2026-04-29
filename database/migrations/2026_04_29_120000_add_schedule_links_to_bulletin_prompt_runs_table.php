<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bulletin_prompt_runs', function (Blueprint $table): void {
            if (! Schema::hasColumn('bulletin_prompt_runs', 'editorial_schedule_id')) {
                $table->foreignId('editorial_schedule_id')->nullable()->after('script_id')->constrained('editorial_schedules')->nullOnDelete();
            }

            if (! Schema::hasColumn('bulletin_prompt_runs', 'editorial_schedule_run_id')) {
                $table->foreignId('editorial_schedule_run_id')->nullable()->after('editorial_schedule_id')->constrained('editorial_schedule_runs')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('bulletin_prompt_runs', function (Blueprint $table): void {
            if (Schema::hasColumn('bulletin_prompt_runs', 'editorial_schedule_run_id')) {
                $table->dropConstrainedForeignId('editorial_schedule_run_id');
            }

            if (Schema::hasColumn('bulletin_prompt_runs', 'editorial_schedule_id')) {
                $table->dropConstrainedForeignId('editorial_schedule_id');
            }
        });
    }
};
