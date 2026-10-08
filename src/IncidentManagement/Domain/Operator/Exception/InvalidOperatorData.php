<?php

namespace OptimaRetail\IncidentManagement\Domain\Operator\Exception;

use OptimaRetail\Shared\Domain\DomainError;

final class InvalidOperatorData extends DomainError
{
    public const string REQUIRED = 'required';

    public const string TOO_SHORT = 'too_short';

    private function __construct(
        public readonly string $property,
        public readonly string $rule,
        public readonly array $parameters = [],
    ) {
        parent::__construct("Invalid operator data: {$property} ({$rule}).");
    }

    public static function required(string $property): self
    {
        return new self($property, self::REQUIRED);
    }

    public static function tooShort(string $property, int $min): self
    {
        return new self($property, self::TOO_SHORT, ['min' => $min]);
    }

    public function errorCode(): string
    {
        return 'operator.invalid_data';
    }
}
