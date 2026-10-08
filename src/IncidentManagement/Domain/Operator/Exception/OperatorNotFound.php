<?php

namespace OptimaRetail\IncidentManagement\Domain\Operator\Exception;

use OptimaRetail\IncidentManagement\Domain\Operator\OperatorId;
use OptimaRetail\Shared\Domain\DomainError;

final class OperatorNotFound extends DomainError
{
    public function __construct(public readonly OperatorId $id)
    {
        parent::__construct("Operator not found: {$id->value}.");
    }

    public function errorCode(): string
    {
        return 'operator.not_found';
    }
}
