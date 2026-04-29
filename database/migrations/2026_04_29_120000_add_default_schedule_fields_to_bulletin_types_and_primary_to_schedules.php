<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bulletin_types', function (Blueprint $table): void {
            $table->string('default_run_frequency', 50)->nullable()->after('default_schedule_time');
            $table->time('default_run_time')->nullable()->after('default_run_frequency');
            $table->json('default_run_days')->nullable()->after('default_run_time');
            $table->boolean('default_schedule_is_active')->default(false)->after('default_timezone');
            $table->boolean('default_auto_run_pipeline')->default(false)->after('default_schedule_is_active');
            $table->boolean('default_auto_generate_ai_response')->default(false)->after('default_auto_run_pipeline');
            $table->boolean('default_auto_create_script')->default(true)->after('default_auto_generate_ai_response');
            $table->boolean('default_auto_generate_metadata')->default(true)->after('default_auto_create_script');
            $table->boolean('default_auto_extract_sources')->default(true)->after('default_auto_generate_metadata');
        });

        Schema::table('editorial_schedules', function (Blueprint $table): void {
            $table->boolean('is_primary')->default(false)->after('bulletin_type_id');
        });
    }

    public function down(): void
    {
        Schema::table('editorial_schedules', function (Blueprint $table): void {
            $table->dropColumn('is_primary');
        });
        Schema::table('bulletin_types', function (Blueprint $table): void {
            $table->dropColumn(['default_run_frequency','default_run_time','default_run_days','default_schedule_is_active','default_auto_run_pipeline','default_auto_generate_ai_response','default_auto_create_script','default_auto_generate_metadata','default_auto_extract_sources']);
        });
    }
};
