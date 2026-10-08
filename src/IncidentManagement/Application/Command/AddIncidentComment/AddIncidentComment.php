<?php

namespace OptimaRetail\IncidentManagement\Application\Command\AddIncidentComment;

final readonly class AddIncidentComment
{
    public function __construct(
        public string $incidentId,
        public string $authorName,
        public string $body,
    ) {}
}
