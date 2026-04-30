<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('ai_providers', function (Blueprint $table): void {
            if (! Schema::hasColumn('ai_providers', 'requests_per_minute_limit')) $table->unsignedInteger('requests_per_minute_limit')->nullable()->after('monthly_request_limit');
            if (! Schema::hasColumn('ai_providers', 'requests_per_day_limit')) $table->unsignedInteger('requests_per_day_limit')->nullable()->after('requests_per_minute_limit');
            if (! Schema::hasColumn('ai_providers', 'tokens_per_minute_limit')) $table->unsignedInteger('tokens_per_minute_limit')->nullable()->after('requests_per_day_limit');
            if (! Schema::hasColumn('ai_providers', 'min_seconds_between_requests')) $table->unsignedInteger('min_seconds_between_requests')->nullable()->after('tokens_per_minute_limit');
            if (! Schema::hasColumn('ai_providers', 'retry_on_rate_limit')) $table->boolean('retry_on_rate_limit')->default(true)->after('min_seconds_between_requests');
            if (! Schema::hasColumn('ai_providers', 'max_retries')) $table->unsignedInteger('max_retries')->default(5)->after('retry_on_rate_limit');
            if (! Schema::hasColumn('ai_providers', 'initial_retry_delay_seconds')) $table->unsignedInteger('initial_retry_delay_seconds')->default(5)->after('max_retries');
            if (! Schema::hasColumn('ai_providers', 'max_retry_delay_seconds')) $table->unsignedInteger('max_retry_delay_seconds')->default(300)->after('initial_retry_delay_seconds');
            if (! Schema::hasColumn('ai_providers', 'backoff_multiplier')) $table->decimal('backoff_multiplier', 6, 2)->default(2.00)->after('max_retry_delay_seconds');
            if (! Schema::hasColumn('ai_providers', 'jitter_enabled')) $table->boolean('jitter_enabled')->default(true)->after('backoff_multiplier');
            if (! Schema::hasColumn('ai_providers', 'last_request_at')) $table->timestamp('last_request_at')->nullable()->after('jitter_enabled');
            if (! Schema::hasColumn('ai_providers', 'rate_limited_until')) $table->timestamp('rate_limited_until')->nullable()->after('last_request_at');
            if (! Schema::hasColumn('ai_providers', 'rate_limit_metadata')) $table->json('rate_limit_metadata')->nullable()->after('rate_limited_until');
        });
    }
    public function down(): void
    {
    }
};
