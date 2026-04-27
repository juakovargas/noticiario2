<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scripts', function (Blueprint $table): void {
            if (! Schema::hasColumn('scripts', 'review_status')) {
                $table->string('review_status', 50)->default('pending')->after('status');
            }

            if (! Schema::hasColumn('scripts', 'review_notes')) {
                $table->longText('review_notes')->nullable()->after('metadata');
            }

            if (! Schema::hasColumn('scripts', 'fact_check_notes')) {
                $table->longText('fact_check_notes')->nullable()->after('review_notes');
            }

            if (! Schema::hasColumn('scripts', 'reviewed_by')) {
                $table->foreignId('reviewed_by')->nullable()->after('fact_check_notes')->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('scripts', 'reviewed_at')) {
                $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            }

            if (! Schema::hasColumn('scripts', 'approved_by')) {
                $table->foreignId('approved_by')->nullable()->after('reviewed_at')->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('scripts', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }

            if (! Schema::hasColumn('scripts', 'rejected_by')) {
                $table->foreignId('rejected_by')->nullable()->after('approved_at')->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('scripts', 'rejected_at')) {
                $table->timestamp('rejected_at')->nullable()->after('rejected_by');
            }

            if (! Schema::hasColumn('scripts', 'rejection_reason')) {
                $table->longText('rejection_reason')->nullable()->after('rejected_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('scripts', function (Blueprint $table): void {
            foreach (['reviewed_by', 'rejected_by'] as $column) {
                if (Schema::hasColumn('scripts', $column)) {
                    $table->dropConstrainedForeignId($column);
                }
            }

            foreach (['review_status', 'review_notes', 'fact_check_notes', 'reviewed_at', 'rejected_at', 'rejection_reason'] as $column) {
                if (Schema::hasColumn('scripts', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
