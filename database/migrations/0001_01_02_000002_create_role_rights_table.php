<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_rights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->string('right', 100);
            $table->string('group', 60);
            $table->timestamps();
            $table->unique(['role_id', 'right']);
            $table->index(['group']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_rights');
    }
};
