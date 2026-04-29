<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_providers', function (Blueprint $table): void {
            if (! Schema::hasColumn('ai_providers', 'client_driver')) {
                $table->string('client_driver', 50)->default('custom')->after('provider_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ai_providers', function (Blueprint $table): void {
            if (Schema::hasColumn('ai_providers', 'client_driver')) {
                $table->dropColumn('client_driver');
            }
        });
    }
};
