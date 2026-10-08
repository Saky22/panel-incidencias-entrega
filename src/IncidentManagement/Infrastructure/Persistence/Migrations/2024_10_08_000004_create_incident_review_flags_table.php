<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Marcas de "requiere revisión manual" que deja el diagnóstico cuando NO corrige
 * automáticamente. No toca incidents ni incident_logs: es información adicional.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incident_review_flags', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('incident_id')->constrained('incidents')->restrictOnDelete();
            $table->string('inconsistency_type', 40);
            $table->json('details')->nullable();
            $table->string('flagged_by');
            $table->timestamp('created_at', 6)->useCurrent();
            $table->timestamp('resolved_at', 6)->nullable(); // null = abierta

            $table->index(['incident_id', 'resolved_at']);
            $table->index(['inconsistency_type', 'resolved_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incident_review_flags');
    }
};
