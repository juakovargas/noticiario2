<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('bulletin_types', 'ai_provider_id') && ! Schema::hasColumn('bulletin_types', 'preferred_ai_provider_id')) {
            Schema::table('bulletin_types', function (Blueprint $table): void {
                $table->renameColumn('ai_provider_id', 'preferred_ai_provider_id');
            });
        }

        if (! Schema::hasColumn('bulletin_types', 'preferred_ai_provider_id')) {
            Schema::table('bulletin_types', function (Blueprint $table): void {
                $table->foreignId('preferred_ai_provider_id')->nullable()->after('default_prompt_profile_id')->constrained('ai_providers')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('bulletin_types', 'preferred_ai_provider_id') && ! Schema::hasColumn('bulletin_types', 'ai_provider_id')) {
            Schema::table('bulletin_types', function (Blueprint $table): void {
                $table->renameColumn('preferred_ai_provider_id', 'ai_provider_id');
            });
        }
    }
};
