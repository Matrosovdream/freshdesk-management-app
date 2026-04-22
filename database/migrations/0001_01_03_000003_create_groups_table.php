<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('groups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('freshdesk_id')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('unassigned_for', 10)->nullable();
            $table->unsignedBigInteger('business_hour_id')->nullable();
            $table->unsignedBigInteger('escalate_to')->nullable();
            $table->json('agent_ids')->nullable();
            $table->boolean('auto_ticket_assign')->default(false);
            $table->json('payload')->nullable();
            $table->timestamp('fd_created_at')->nullable();
            $table->timestamp('fd_updated_at')->nullable()->index();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('groups');
    }
};
