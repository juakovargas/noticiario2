<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $map = ['madrid' => 'Europe/Madrid', 'Madrid' => 'Europe/Madrid', 'spain' => 'Europe/Madrid', 'España' => 'Europe/Madrid', 'españa' => 'Europe/Madrid', 'espana' => 'Europe/Madrid'];
        foreach (['users' => 'timezone', 'bulletin_types' => 'default_timezone', 'locations' => 'timezone', 'editorial_schedules' => 'timezone'] as $table => $column) {
            if (! Schema::hasColumn($table, $column)) continue;
            foreach ($map as $from => $to) {
                DB::table($table)->where($column, $from)->update([$column => $to]);
            }
        }
    }

    public function down(): void {}
};
