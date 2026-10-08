<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use OptimaRetail\IncidentManagement\Domain\Incident\IncidentStatus;
use OptimaRetail\IncidentManagement\Domain\Incident\Priority;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incidents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->text('description');
            // Los valores salen de los enums de dominio: sin strings mágicos duplicados.
            $table->enum('status', IncidentStatus::values())->default(IncidentStatus::OPEN->value);
            $table->enum('priority', Priority::values());
            $table->string('requester_name');
            $table->string('assigned_to')->nullable();
            $table->timestamps();

            // Consultas habituales: listado por defecto ordenado por fecha, filtrado por
            // estado/prioridad + fecha, contadores por estado y "mis incidencias" por asignado.
            $table->index('created_at');
            $table->index(['status', 'created_at']);
            $table->index(['priority', 'created_at']);
            $table->index(['assigned_to', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incidents');
    }
};
