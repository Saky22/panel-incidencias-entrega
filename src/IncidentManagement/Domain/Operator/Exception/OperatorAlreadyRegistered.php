<?php

namespace OptimaRetail\IncidentManagement\Domain\Operator\Exception;

use OptimaRetail\Shared\Domain\DomainError;

final class OperatorAlreadyRegistered extends DomainError
{
    public function __construct(public readonly string $name)
    {
        parent::__construct("Operator already registered: {$name}.");
    }

    public function errorCode(): string
    {
        return 'operator.already_registered';
    }
}
