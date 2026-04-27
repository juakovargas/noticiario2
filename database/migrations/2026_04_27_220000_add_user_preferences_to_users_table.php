<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'preferred_locale')) {
                $table->string('preferred_locale', 10)->nullable()->after('email');
            }

            if (! Schema::hasColumn('users', 'timezone')) {
                $table->string('timezone', 100)->nullable()->after('preferred_locale');
            }

            if (! Schema::hasColumn('users', 'avatar_path')) {
                $table->string('avatar_path')->nullable()->after('timezone');
            }

            if (! Schema::hasColumn('users', 'date_format')) {
                $table->string('date_format', 50)->nullable()->after('avatar_path');
            }

            if (! Schema::hasColumn('users', 'time_format')) {
                $table->string('time_format', 50)->nullable()->after('date_format');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            foreach (['time_format', 'date_format', 'avatar_path', 'timezone', 'preferred_locale'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
