<?php
use Illuminate\Database\Migrations\Migration; use Illuminate\Database\Schema\Blueprint; use Illuminate\Support\Facades\Schema;
return new class extends Migration { public function up(): void { Schema::create('worker_heartbeats', function (Blueprint $table): void {
$table->id(); $table->string('worker_name'); $table->string('queue_connection')->nullable(); $table->string('queue_name')->nullable(); $table->string('hostname')->nullable(); $table->string('process_id')->nullable(); $table->timestamp('last_seen_at'); $table->json('metadata')->nullable(); $table->timestamps(); $table->unique('worker_name');
}); } public function down(): void { Schema::dropIfExists('worker_heartbeats'); } };
