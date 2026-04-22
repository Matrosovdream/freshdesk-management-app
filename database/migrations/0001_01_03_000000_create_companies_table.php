<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('freshdesk_id')->unique();
            $table->string('name')->index();
            $table->text('description')->nullable();
            $table->json('domains')->nullable();
            $table->text('note')->nullable();
            $table->string('health_score')->nullable();
            $table->string('account_tier')->nullable();
            $table->date('renewal_date')->nullable();
            $table->string('industry')->nullable();
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
        Schema::dropIfExists('companies');
    }
};
