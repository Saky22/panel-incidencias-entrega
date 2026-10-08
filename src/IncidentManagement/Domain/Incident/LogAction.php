<?php

namespace OptimaRetail\IncidentManagement\Domain\Incident;

enum LogAction: string
{
    /** Alta de la incidencia (old_value = null, new_value = open). */
    case CREATED = 'created';

    /** Cambio de estado ordinario (open→under_review, under_review→blocked|resolved). */
    case STATUS_CHANGED = 'status_changed';

    /** Reapertura explícita: resolved → open (exige motivo). */
    case REOPENED = 'reopened';

    /** Desbloqueo explícito: blocked → under_review (exige motivo). */
    case UNBLOCKED = 'unblocked';

    /** Asiento compensatorio del diagnóstico: deja constancia, nunca reescribe historia. */
    case RECONCILED = 'reconciled';

    /** Acción con la que debe registrarse una transición de operador. */
    public static function forTransition(IncidentStatus $from, IncidentStatus $to): self
    {
        return match (true) {
            $from === IncidentStatus::RESOLVED && $to === IncidentStatus::OPEN => self::REOPENED,
            $from === IncidentStatus::BLOCKED && $to === IncidentStatus::UNDER_REVIEW => self::UNBLOCKED,
            default => self::STATUS_CHANGED,
        };
    }

    /** Acciones que representan una transición hecha por un operador (sujeta a la máquina de estados). */
    public function isTransition(): bool
    {
        return in_array($this, [self::STATUS_CHANGED, self::REOPENED, self::UNBLOCKED], true);
    }

    /** @return list<string> */
    public static function transitionValues(): array
    {
        // Sin operador pipe (|>): es de PHP 8.5 y el proyecto exige ^8.4.
        return array_values(array_map(
            fn (self $a) => $a->value,
            array_filter(self::cases(), fn (self $a) => $a->isTransition()),
        ));
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
