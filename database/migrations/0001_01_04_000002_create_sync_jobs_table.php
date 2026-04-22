<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sync_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('resource', 40)->index();
            $table->string('mode', 20)->default('incremental');
            $table->string('status', 20)->default('running');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->unsignedInteger('items_processed')->default(0);
            $table->unsignedInteger('items_upserted')->default(0);
            $table->unsignedInteger('items_failed')->default(0);
            $table->text('error')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_jobs');
    }
};
