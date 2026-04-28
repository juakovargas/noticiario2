<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_providers', function (Blueprint $table): void {
            if (! Schema::hasColumn('ai_providers', 'daily_cost_limit')) {
                $table->decimal('daily_cost_limit', 12, 6)->nullable()->after('monthly_request_limit');
            }

            if (! Schema::hasColumn('ai_providers', 'monthly_cost_limit')) {
                $table->decimal('monthly_cost_limit', 12, 6)->nullable()->after('daily_cost_limit');
            }
        });

        Schema::table('ai_request_logs', function (Blueprint $table): void {
            if (! Schema::hasColumn('ai_request_logs', 'limit_blocked')) {
                $table->boolean('limit_blocked')->default(false)->after('status');
            }

            if (! Schema::hasColumn('ai_request_logs', 'error_code')) {
                $table->string('error_code')->nullable()->after('error_message');
            }

            if (! Schema::hasColumn('ai_request_logs', 'provider_status_code')) {
                $table->unsignedInteger('provider_status_code')->nullable()->after('error_code');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ai_providers', function (Blueprint $table): void {
            $drop = [];
            if (Schema::hasColumn('ai_providers', 'daily_cost_limit')) {
                $drop[] = 'daily_cost_limit';
            }
            if (Schema::hasColumn('ai_providers', 'monthly_cost_limit')) {
                $drop[] = 'monthly_cost_limit';
            }
            if ($drop !== []) {
                $table->dropColumn($drop);
            }
        });

        Schema::table('ai_request_logs', function (Blueprint $table): void {
            $drop = [];
            if (Schema::hasColumn('ai_request_logs', 'limit_blocked')) {
                $drop[] = 'limit_blocked';
            }
            if (Schema::hasColumn('ai_request_logs', 'error_code')) {
                $drop[] = 'error_code';
            }
            if (Schema::hasColumn('ai_request_logs', 'provider_status_code')) {
                $drop[] = 'provider_status_code';
            }
            if ($drop !== []) {
                $table->dropColumn($drop);
            }
        });
    }
};
