<?php

namespace OptimaRetail\IncidentManagement\Application\Command\ChangeIncidentStatus;

final readonly class ChangeIncidentStatus
{
    /** @param array<string, scalar|null> $context datos de auditoría (ip, origen...) */
    public function __construct(
        public string $incidentId,
        public string $status,
        public string $actor,
        public ?string $reason = null,
        public array $context = [],
    ) {}
}
