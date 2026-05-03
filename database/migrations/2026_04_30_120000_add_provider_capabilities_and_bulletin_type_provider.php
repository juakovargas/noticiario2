<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_providers', function (Blueprint $table): void {
            if (! Schema::hasColumn('ai_providers', 'provider_category')) {
                $table->string('provider_category', 50)->nullable()->after('provider_type');
            }
            if (! Schema::hasColumn('ai_providers', 'capabilities')) {
                $table->json('capabilities')->nullable()->after('metadata');
            }
            foreach (['is_testing', 'is_local', 'supports_grounding', 'supports_citations', 'supports_streaming'] as $col) {
                if (! Schema::hasColumn('ai_providers', $col)) {
                    $table->boolean($col)->default(false);
                }
            }
        });

        Schema::table('bulletin_types', function (Blueprint $table): void {
            if (! Schema::hasColumn('bulletin_types', 'preferred_ai_provider_id')) {
                $table->foreignId('preferred_ai_provider_id')->nullable()->after('default_prompt_profile_id')->constrained('ai_providers')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('bulletin_types', function (Blueprint $table): void {
            if (Schema::hasColumn('bulletin_types', 'preferred_ai_provider_id')) {
                $table->dropConstrainedForeignId('preferred_ai_provider_id');
            }
        });
    }
};
