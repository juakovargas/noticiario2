<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('source_references', function (Blueprint $table): void {
            if (! Schema::hasColumn('source_references', 'source_domain')) {
                $table->string('source_domain', 255)->nullable()->after('source_url');
                $table->index('source_domain');
            }
        });
    }

    public function down(): void
    {
        Schema::table('source_references', function (Blueprint $table): void {
            if (Schema::hasColumn('source_references', 'source_domain')) {
                $table->dropIndex(['source_domain']);
                $table->dropColumn('source_domain');
            }
        });
    }
};
