<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('freshdesk_id')->unique();
            $table->string('email')->index();
            $table->string('name')->nullable();
            $table->string('job_title')->nullable();
            $table->string('language', 10)->nullable();
            $table->string('time_zone')->nullable();
            $table->boolean('available')->default(false);
            $table->boolean('occasional')->default(false);
            $table->string('type', 30)->nullable();
            $table->unsignedTinyInteger('ticket_scope')->nullable();
            $table->text('signature')->nullable();
            $table->json('group_ids')->nullable();
            $table->json('role_ids')->nullable();
            $table->json('skill_ids')->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('fd_created_at')->nullable();
            $table->timestamp('fd_updated_at')->nullable()->index();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agents');
    }
};
