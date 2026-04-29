<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scripts', function (Blueprint $table): void {
            $table->dropForeign(['edition_id']);
        });

        Schema::table('scripts', function (Blueprint $table): void {
            $table->foreignId('edition_id')->nullable()->change();
            $table->foreign('edition_id')->references('id')->on('editions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('scripts', function (Blueprint $table): void {
            $table->dropForeign(['edition_id']);
        });

        Schema::table('scripts', function (Blueprint $table): void {
            $table->foreignId('edition_id')->nullable(false)->change();
            $table->foreign('edition_id')->references('id')->on('editions')->cascadeOnDelete();
        });
    }
};
