<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('editorial_schedule_runs', function (Blueprint $table): void {
            if (! Schema::hasColumn('editorial_schedule_runs', 'parsed_response')) {
                $table->json('parsed_response')->nullable()->after('ai_response_text');
            }

            if (! Schema::hasColumn('editorial_schedule_runs', 'parser_warnings')) {
                $table->json('parser_warnings')->nullable()->after('parsed_response');
            }
        });
    }

    public function down(): void
    {
        Schema::table('editorial_schedule_runs', function (Blueprint $table): void {
            if (Schema::hasColumn('editorial_schedule_runs', 'parser_warnings')) {
                $table->dropColumn('parser_warnings');
            }

            if (Schema::hasColumn('editorial_schedule_runs', 'parsed_response')) {
                $table->dropColumn('parsed_response');
            }
        });
    }
};
