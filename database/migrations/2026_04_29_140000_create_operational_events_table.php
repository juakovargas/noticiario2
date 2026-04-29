<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void { Schema::create('operational_events', function (Blueprint $table): void {
        $table->id(); $table->string('event_type',100); $table->string('severity',50)->default('info'); $table->string('status',50)->nullable();
        $table->string('title'); $table->text('message')->nullable(); $table->string('related_type')->nullable(); $table->unsignedBigInteger('related_id')->nullable();
        $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); $table->timestamp('occurred_at'); $table->json('metadata')->nullable(); $table->timestamps();
        $table->index(['event_type','occurred_at']);
    }); }
    public function down(): void { Schema::dropIfExists('operational_events'); }
};
