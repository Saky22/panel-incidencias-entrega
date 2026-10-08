<?php

namespace OptimaRetail\IncidentManagement\Domain\Incident;

/**
 * Única fuente de verdad de los estados y su máquina de transiciones.
 *
 *   open ──► under_review ──► resolved ──(reabrir, motivo)──► open
 *                 │  ▲
 *                 ▼  │ (desbloquear, motivo)
 *               blocked (bloquear, motivo)
 */
enum IncidentStatus: string
{
    case OPEN = 'open';
    case UNDER_REVIEW = 'under_review';
    case BLOCKED = 'blocked';
    case RESOLVED = 'resolved';

    /** @return list<self> */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::OPEN, self::BLOCKED => [self::UNDER_REVIEW],
            self::UNDER_REVIEW => [self::BLOCKED, self::RESOLVED],
            self::RESOLVED => [self::OPEN],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }

    /** Bloquear, desbloquear y reabrir exigen un motivo explícito que queda en el log. */
    public function requiresReasonFor(self $target): bool
    {
        return $target === self::BLOCKED
            || $this === self::BLOCKED
            || $this === self::RESOLVED;
    }

    public function isFinal(): bool
    {
        return $this === self::RESOLVED;
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
