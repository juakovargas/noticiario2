<?php
use Illuminate\Database\Migrations\Migration; use Illuminate\Database\Schema\Blueprint; use Illuminate\Support\Facades\Schema;
return new class extends Migration { public function up(): void { Schema::create('operation_alert_settings', function (Blueprint $table): void {
$table->id(); $table->string('name'); $table->string('alert_type',100); $table->boolean('is_active')->default(false); $table->json('email_recipients')->nullable(); $table->unsignedInteger('threshold_minutes')->nullable(); $table->unsignedInteger('threshold_count')->nullable(); $table->unsignedInteger('cooldown_minutes')->default(30); $table->json('metadata')->nullable(); $table->timestamps(); $table->softDeletes();
}); } public function down(): void { Schema::dropIfExists('operation_alert_settings'); } };
