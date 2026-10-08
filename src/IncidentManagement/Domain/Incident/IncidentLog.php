<?php

namespace OptimaRetail\IncidentManagement\Domain\Incident;

/**
 * Asiento de trazabilidad del agregado Incident. Inmutable.
 * El id lo asigna la persistencia (null mientras no se ha guardado).
 */
final readonly class IncidentLog
{
    public function __construct(
        public IncidentId $incidentId,
        public LogAction $action,
        public ?IncidentStatus $oldStatus,
        public IncidentStatus $newStatus,
        public string $actor,
        /** @var array<string, scalar> motivo, ip, origen... */
        public array $metadata,
        public \DateTimeImmutable $occurredAt,
        public ?string $id = null,
    ) {}
}
