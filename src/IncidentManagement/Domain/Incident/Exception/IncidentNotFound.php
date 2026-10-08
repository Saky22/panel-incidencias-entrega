<?php

namespace OptimaRetail\IncidentManagement\Domain\Incident\Exception;

use OptimaRetail\IncidentManagement\Domain\Incident\IncidentId;
use OptimaRetail\Shared\Domain\DomainError;

final class IncidentNotFound extends DomainError
{
    public function __construct(public readonly IncidentId $id)
    {
        parent::__construct("Incident {$id} not found.");
    }

    public function errorCode(): string
    {
        return 'incident.not_found';
    }
}
