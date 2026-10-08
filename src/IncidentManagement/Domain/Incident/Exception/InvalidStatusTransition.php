<?php

namespace OptimaRetail\IncidentManagement\Domain\Incident\Exception;

use OptimaRetail\IncidentManagement\Domain\Incident\IncidentStatus;
use OptimaRetail\Shared\Domain\DomainError;

final class InvalidStatusTransition extends DomainError
{
    public function __construct(
        public readonly IncidentStatus $from,
        public readonly IncidentStatus $to,
    ) {
        parent::__construct("Transition {$from->value} -> {$to->value} is not allowed.");
    }

    public function errorCode(): string
    {
        return 'incident.invalid_transition';
    }
}
