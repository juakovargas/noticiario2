<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
  public function up(): void {
    Schema::table('editorial_schedules', function (Blueprint $table) {
      $table->boolean('auto_run_pipeline')->default(false)->after('auto_generate_prompt');
      $table->boolean('auto_create_script')->default(true)->after('auto_generate_ai_response');
      $table->boolean('auto_generate_metadata')->default(true)->after('auto_create_script');
      $table->boolean('auto_extract_sources')->default(true)->after('auto_generate_metadata');
      $table->timestamp('last_success_at')->nullable()->after('last_run_at');
      $table->timestamp('last_failure_at')->nullable()->after('last_success_at');
      $table->text('last_error_message')->nullable()->after('last_failure_at');
    });
  }
  public function down(): void {
    Schema::table('editorial_schedules', function (Blueprint $table) {
      $table->dropColumn(['auto_run_pipeline','auto_create_script','auto_generate_metadata','auto_extract_sources','last_success_at','last_failure_at','last_error_message']);
    });
  }
};
