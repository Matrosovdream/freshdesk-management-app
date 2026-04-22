<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('time_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('freshdesk_id')->unique();
            $table->foreignId('ticket_id')->constrained('tickets')->cascadeOnDelete();
            $table->unsignedBigInteger('freshdesk_ticket_id')->index();
            $table->foreignId('agent_id')->nullable()->constrained('agents')->nullOnDelete();
            $table->unsignedBigInteger('freshdesk_agent_id')->nullable()->index();
            $table->string('time_spent', 10)->nullable();
            $table->text('note')->nullable();
            $table->boolean('billable')->default(false);
            $table->boolean('timer_running')->default(false);
            $table->timestamp('executed_at')->nullable();
            $table->timestamp('start_time')->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('fd_created_at')->nullable();
            $table->timestamp('fd_updated_at')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('time_entries');
    }
};
