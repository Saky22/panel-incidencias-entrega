<?php

namespace OptimaRetail\Shared\Domain\ValueObject;

/** Identificador UUID inmutable y autovalidado. Base para los Id de cada agregado. */
abstract class Uuid implements \Stringable
{
    private const string PATTERN = '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-8][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/';

    final private function __construct(public readonly string $value) {}

    public static function fromString(string $value): static
    {
        $value = strtolower(trim($value));
        if (! preg_match(self::PATTERN, $value)) {
            throw new \InvalidArgumentException(sprintf('<%s> no es un UUID válido: "%s".', static::class, $value));
        }

        return new static($value);
    }

    public function equals(self $other): bool
    {
        // Un IncidentId nunca es igual a un OperatorId aunque compartan valor.
        return $other instanceof static && $other->value === $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
