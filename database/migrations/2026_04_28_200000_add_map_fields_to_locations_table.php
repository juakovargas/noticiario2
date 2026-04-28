<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('locations', function (Blueprint $table): void {
            if (! Schema::hasColumn('locations', 'latitude')) {
                $table->decimal('latitude', 10, 7)->nullable()->after('timezone');
            }

            if (! Schema::hasColumn('locations', 'longitude')) {
                $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            }

            if (! Schema::hasColumn('locations', 'map_zoom')) {
                $table->unsignedTinyInteger('map_zoom')->nullable()->after('longitude');
            }

            if (! Schema::hasColumn('locations', 'marker_color')) {
                $table->string('marker_color', 30)->nullable()->after('map_zoom');
            }

            if (! Schema::hasColumn('locations', 'marker_label')) {
                $table->string('marker_label')->nullable()->after('marker_color');
            }

            if (! Schema::hasColumn('locations', 'show_on_map')) {
                $table->boolean('show_on_map')->default(true)->after('marker_label');
            }
        });
    }

    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table): void {
            $columns = ['latitude', 'longitude', 'map_zoom', 'marker_color', 'marker_label', 'show_on_map'];

            foreach ($columns as $column) {
                if (Schema::hasColumn('locations', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
