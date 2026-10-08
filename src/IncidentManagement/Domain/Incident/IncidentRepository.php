<?php

namespace OptimaRetail\IncidentManagement\Domain\Incident;

/**
 * Puerto de escritura del agregado (lado "command").
 * Las lecturas de pantalla van por Application\Query\IncidentReadModel.
 */
interface IncidentRepository
{
    public function nextIdentity(): IncidentId;

    public function find(IncidentId $id): ?Incident;

    /** Igual que find() con bloqueo pesimista; usar dentro de una transacción. */
    public function findForUpdate(IncidentId $id): ?Incident;

    /** Persiste la incidencia y sus logs/comentarios pendientes. */
    public function save(Incident $incident): void;
}
