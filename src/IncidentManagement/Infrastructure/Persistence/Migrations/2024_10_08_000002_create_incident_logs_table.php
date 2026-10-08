<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incident_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            // restrict: el histórico de auditoría no debe desaparecer en cascada.
            $table->foreignUuid('incident_id')->constrained('incidents')->restrictOnDelete();
            $table->string('action', 32);          // valores del enum LogAction del dominio
            $table->string('old_value', 32)->nullable();
            $table->string('new_value', 32);
            $table->string('user_name');
            $table->json('metadata')->nullable();  // motivo, IP, user-agent, origen...
            // Precisión de microsegundos: el orden del historial no depende del id (UUID).
            $table->timestamp('created_at', 6)->useCurrent();

            $table->index(['incident_id', 'created_at']);
            $table->index(['action', 'new_value']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incident_logs');
    }
};
