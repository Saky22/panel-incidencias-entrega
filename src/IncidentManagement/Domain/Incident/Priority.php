<?php

namespace OptimaRetail\IncidentManagement\Domain\Incident;

enum Priority: string
{
    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';
    case CRITICAL = 'critical';

    public function weight(): int
    {
        return match ($this) {
            self::LOW => 1,
            self::MEDIUM => 2,
            self::HIGH => 3,
            self::CRITICAL => 4,
        };
    }

    /** Horas máximas de resolución (SLA). */
    public function slaHours(): int
    {
        return match ($this) {
            self::LOW => 168,
            self::MEDIUM => 72,
            self::HIGH => 24,
            self::CRITICAL => 4,
        };
    }

    /** Fecha límite de resolución a partir de la fecha de alta. Regla única de SLA. */
    public function dueFrom(\DateTimeImmutable $createdAt): \DateTimeImmutable
    {
        return $createdAt->modify("+{$this->slaHours()} hours");
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
