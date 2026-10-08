<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operators', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name')->unique();
            $table->boolean('active')->default(true);
            $table->timestamp('created_at', 6)->useCurrent();

            // Selector y gestión: activos primero, ordenados por nombre.
            $table->index(['active', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operators');
    }
};
