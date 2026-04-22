<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('freshdesk_id')->unique();
            $table->foreignId('ticket_id')->constrained('tickets')->cascadeOnDelete();
            $table->unsignedBigInteger('freshdesk_ticket_id')->index();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->longText('body')->nullable();
            $table->longText('body_text')->nullable();
            $table->boolean('private')->default(false);
            $table->boolean('incoming')->default(false);
            $table->unsignedTinyInteger('source')->nullable();
            $table->string('from_email')->nullable();
            $table->json('to_emails')->nullable();
            $table->json('cc_emails')->nullable();
            $table->json('bcc_emails')->nullable();
            $table->json('attachments')->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('fd_created_at')->nullable()->index();
            $table->timestamp('fd_updated_at')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
