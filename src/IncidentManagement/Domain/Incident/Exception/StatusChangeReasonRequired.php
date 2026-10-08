<?php

namespace OptimaRetail\IncidentManagement\Domain\Incident\Exception;

use OptimaRetail\IncidentManagement\Domain\Incident\IncidentStatus;
use OptimaRetail\Shared\Domain\DomainError;

final class StatusChangeReasonRequired extends DomainError
{
    public function __construct(
        public readonly IncidentStatus $from,
        public readonly IncidentStatus $to,
    ) {
        parent::__construct("Transition {$from->value} -> {$to->value} requires a reason.");
    }

    public function errorCode(): string
    {
        return 'incident.reason_required';
    }
}
