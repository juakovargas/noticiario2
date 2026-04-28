<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('editorial_schedules', function (Blueprint $table): void {
            if (! Schema::hasColumn('editorial_schedules', 'bulletin_type_id')) {
                $table->foreignId('bulletin_type_id')->nullable()->after('ai_prompt_template_id')->constrained('bulletin_types')->nullOnDelete();
            }

            if (! Schema::hasColumn('editorial_schedules', 'run_frequency')) {
                $table->string('run_frequency', 50)->nullable()->after('frequency_type');
            }

            if (! Schema::hasColumn('editorial_schedules', 'run_time')) {
                $table->time('run_time')->nullable()->after('scheduled_time');
            }

            if (! Schema::hasColumn('editorial_schedules', 'run_days')) {
                $table->json('run_days')->nullable()->after('weekdays');
            }

            if (! Schema::hasColumn('editorial_schedules', 'next_run_at')) {
                $table->timestamp('next_run_at')->nullable()->after('timezone');
            }

            if (! Schema::hasColumn('editorial_schedules', 'last_run_at')) {
                $table->timestamp('last_run_at')->nullable()->after('next_run_at');
            }

            if (! Schema::hasColumn('editorial_schedules', 'auto_create_prompt_run')) {
                $table->boolean('auto_create_prompt_run')->default(true)->after('is_active');
            }

            if (! Schema::hasColumn('editorial_schedules', 'auto_generate_prompt')) {
                $table->boolean('auto_generate_prompt')->default(true)->after('auto_create_prompt_run');
            }

            if (! Schema::hasColumn('editorial_schedules', 'auto_generate_ai_response')) {
                $table->boolean('auto_generate_ai_response')->default(false)->after('auto_generate_prompt');
            }
        });

        Schema::table('editorial_schedule_runs', function (Blueprint $table): void {
            if (! Schema::hasColumn('editorial_schedule_runs', 'bulletin_prompt_run_id')) {
                $table->foreignId('bulletin_prompt_run_id')->nullable()->after('editorial_request_id')->constrained('bulletin_prompt_runs')->nullOnDelete();
            }
        });

        Schema::table('editorial_schedule_runs', function (Blueprint $table): void {
            $table->unique(['editorial_schedule_id', 'scheduled_for'], 'editorial_schedule_runs_schedule_time_unique');
        });
    }

    public function down(): void
    {
        Schema::table('editorial_schedule_runs', function (Blueprint $table): void {
            $table->dropUnique('editorial_schedule_runs_schedule_time_unique');

            if (Schema::hasColumn('editorial_schedule_runs', 'bulletin_prompt_run_id')) {
                $table->dropConstrainedForeignId('bulletin_prompt_run_id');
            }
        });

        Schema::table('editorial_schedules', function (Blueprint $table): void {
            if (Schema::hasColumn('editorial_schedules', 'auto_generate_ai_response')) {
                $table->dropColumn('auto_generate_ai_response');
            }
            if (Schema::hasColumn('editorial_schedules', 'auto_generate_prompt')) {
                $table->dropColumn('auto_generate_prompt');
            }
            if (Schema::hasColumn('editorial_schedules', 'auto_create_prompt_run')) {
                $table->dropColumn('auto_create_prompt_run');
            }
            if (Schema::hasColumn('editorial_schedules', 'last_run_at')) {
                $table->dropColumn('last_run_at');
            }
            if (Schema::hasColumn('editorial_schedules', 'next_run_at')) {
                $table->dropColumn('next_run_at');
            }
            if (Schema::hasColumn('editorial_schedules', 'run_days')) {
                $table->dropColumn('run_days');
            }
            if (Schema::hasColumn('editorial_schedules', 'run_time')) {
                $table->dropColumn('run_time');
            }
            if (Schema::hasColumn('editorial_schedules', 'run_frequency')) {
                $table->dropColumn('run_frequency');
            }
            if (Schema::hasColumn('editorial_schedules', 'bulletin_type_id')) {
                $table->dropConstrainedForeignId('bulletin_type_id');
            }
        });
    }
};
