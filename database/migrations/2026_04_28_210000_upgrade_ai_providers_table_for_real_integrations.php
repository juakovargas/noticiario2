<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_providers', function (Blueprint $table): void {
            if (Schema::hasColumn('ai_providers', 'api_key_env') && ! Schema::hasColumn('ai_providers', 'api_key_env_name')) {
                $table->renameColumn('api_key_env', 'api_key_env_name');
            }
        });

        Schema::table('ai_providers', function (Blueprint $table): void {
            if (! Schema::hasColumn('ai_providers', 'organization')) {
                $table->string('organization')->nullable()->after('default_model');
            }
            if (! Schema::hasColumn('ai_providers', 'timeout_seconds')) {
                $table->unsignedInteger('timeout_seconds')->default(60)->after('is_default');
            }
            if (! Schema::hasColumn('ai_providers', 'max_tokens')) {
                $table->unsignedInteger('max_tokens')->nullable()->after('timeout_seconds');
            }
            if (! Schema::hasColumn('ai_providers', 'temperature')) {
                $table->decimal('temperature', 3, 2)->nullable()->after('max_tokens');
            }
            if (! Schema::hasColumn('ai_providers', 'cost_input_per_1k_tokens')) {
                $table->decimal('cost_input_per_1k_tokens', 12, 6)->nullable()->after('temperature');
            }
            if (! Schema::hasColumn('ai_providers', 'cost_output_per_1k_tokens')) {
                $table->decimal('cost_output_per_1k_tokens', 12, 6)->nullable()->after('cost_input_per_1k_tokens');
            }
            if (! Schema::hasColumn('ai_providers', 'daily_request_limit')) {
                $table->unsignedInteger('daily_request_limit')->nullable()->after('cost_output_per_1k_tokens');
            }
            if (! Schema::hasColumn('ai_providers', 'monthly_request_limit')) {
                $table->unsignedInteger('monthly_request_limit')->nullable()->after('daily_request_limit');
            }
        });

        Schema::table('ai_providers', function (Blueprint $table): void {
            if (Schema::hasColumn('ai_providers', 'supports_web_search')) {
                $table->dropColumn('supports_web_search');
            }
            if (Schema::hasColumn('ai_providers', 'supports_json_mode')) {
                $table->dropColumn('supports_json_mode');
            }
            if (Schema::hasColumn('ai_providers', 'monthly_budget_cents')) {
                $table->dropColumn('monthly_budget_cents');
            }
            if (Schema::hasColumn('ai_providers', 'cost_per_1k_input_tokens_cents')) {
                $table->dropColumn('cost_per_1k_input_tokens_cents');
            }
            if (Schema::hasColumn('ai_providers', 'cost_per_1k_output_tokens_cents')) {
                $table->dropColumn('cost_per_1k_output_tokens_cents');
            }
            if (Schema::hasColumn('ai_providers', 'notes')) {
                $table->dropColumn('notes');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ai_providers', function (Blueprint $table): void {
            if (Schema::hasColumn('ai_providers', 'api_key_env_name') && ! Schema::hasColumn('ai_providers', 'api_key_env')) {
                $table->renameColumn('api_key_env_name', 'api_key_env');
            }

            $table->boolean('supports_web_search')->default(false);
            $table->boolean('supports_json_mode')->default(false);
            $table->unsignedInteger('monthly_budget_cents')->nullable();
            $table->unsignedInteger('cost_per_1k_input_tokens_cents')->nullable();
            $table->unsignedInteger('cost_per_1k_output_tokens_cents')->nullable();
            $table->text('notes')->nullable();

            $table->dropColumn([
                'organization',
                'timeout_seconds',
                'max_tokens',
                'temperature',
                'cost_input_per_1k_tokens',
                'cost_output_per_1k_tokens',
                'daily_request_limit',
                'monthly_request_limit',
            ]);
        });
    }
};
