<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bulletin_prompt_runs', function (Blueprint $table): void {
            if (! Schema::hasColumn('bulletin_prompt_runs', 'pipeline_failed_step')) {
                $table->string('pipeline_failed_step', 100)->nullable()->after('pipeline_failed_at');
            }

            if (! Schema::hasColumn('bulletin_prompt_runs', 'pipeline_error_message')) {
                $table->text('pipeline_error_message')->nullable()->after('pipeline_failed_step');
            }
        });

        if (Schema::hasColumn('users', 'preferred_locale')) {
            DB::table('users')->update(['preferred_locale' => 'es']);
        }
    }

    public function down(): void
    {
    }
};
