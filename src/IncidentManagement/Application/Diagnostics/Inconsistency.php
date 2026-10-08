<?php

namespace OptimaRetail\IncidentManagement\Application\Diagnostics;

/** Hallazgo del diagnóstico. Datos estructurados: el texto lo compone la UI. */
final readonly class Inconsistency
{
    /** @param array<string, mixed> $details datos para explicar y reparar (ids, estados, título...) */
    public function __construct(
        public InconsistencyType $type,
        public string $incidentId,
        public FixPolicy $policy,
        public array $details = [],
    ) {}

    public function toArray(): array
    {
        return [
            'type' => $this->type->value,
            'incident_id' => $this->incidentId,
            'policy' => $this->policy->value,
            'details' => $this->details,
        ];
    }
}
