<?php

namespace OptimaRetail\IncidentManagement\Application\Command\DeactivateOperator;

final readonly class DeactivateOperator
{
    public function __construct(public string $operatorId) {}
}
