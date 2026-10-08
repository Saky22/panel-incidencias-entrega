<?php

namespace OptimaRetail\IncidentManagement\Application\Command\CreateIncident;

final readonly class CreateIncident
{
    public function __construct(
        public string $title,
        public string $description,
        public string $priority,
        public string $requesterName,
        public ?string $assignedTo,
        public string $actor,
    ) {}
}
