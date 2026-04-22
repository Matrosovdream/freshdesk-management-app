<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('freshdesk_id')->unique();
            $table->string('name');
            $table->string('email')->nullable()->index();
            $table->string('phone')->nullable();
            $table->string('mobile')->nullable();
            $table->string('twitter_id')->nullable();
            $table->string('unique_external_id')->nullable()->index();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->unsignedBigInteger('freshdesk_company_id')->nullable()->index();
            $table->string('job_title')->nullable();
            $table->string('language', 10)->nullable();
            $table->string('time_zone')->nullable();
            $table->text('address')->nullable();
            $table->boolean('active')->default(false);
            $table->boolean('view_all_tickets')->default(false);
            $table->json('other_emails')->nullable();
            $table->json('other_companies')->nullable();
            $table->json('tags')->nullable();
            $table->json('custom_fields')->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('fd_created_at')->nullable();
            $table->timestamp('fd_updated_at')->nullable()->index();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
