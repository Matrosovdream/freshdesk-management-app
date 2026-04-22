<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('freshdesk_id')->unique();
            $table->string('subject');
            $table->longText('description')->nullable();
            $table->longText('description_text')->nullable();
            $table->unsignedTinyInteger('status')->index();
            $table->unsignedTinyInteger('priority')->index();
            $table->unsignedTinyInteger('source')->nullable();
            $table->string('type')->nullable();

            $table->foreignId('requester_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->unsignedBigInteger('freshdesk_requester_id')->nullable()->index();
            $table->foreignId('responder_id')->nullable()->constrained('agents')->nullOnDelete();
            $table->unsignedBigInteger('freshdesk_responder_id')->nullable()->index();
            $table->foreignId('group_id')->nullable()->constrained('groups')->nullOnDelete();
            $table->unsignedBigInteger('freshdesk_group_id')->nullable()->index();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->unsignedBigInteger('freshdesk_company_id')->nullable()->index();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('email_config_id')->nullable();
            $table->unsignedBigInteger('parent_id')->nullable()->index();

            $table->boolean('spam')->default(false);
            $table->boolean('is_escalated')->default(false);
            $table->boolean('fr_escalated')->default(false);

            $table->timestamp('due_by')->nullable()->index();
            $table->timestamp('fr_due_by')->nullable();

            $table->json('to_emails')->nullable();
            $table->json('cc_emails')->nullable();
            $table->json('fwd_emails')->nullable();
            $table->json('reply_cc_emails')->nullable();
            $table->json('tags')->nullable();
            $table->json('custom_fields')->nullable();
            $table->json('payload')->nullable();

            $table->timestamp('fd_created_at')->nullable()->index();
            $table->timestamp('fd_updated_at')->nullable()->index();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
