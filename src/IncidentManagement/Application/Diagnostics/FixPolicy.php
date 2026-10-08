<?php

namespace OptimaRetail\IncidentManagement\Application\Diagnostics;

/** Autonomía de la corrección automática para cada hallazgo. */
enum FixPolicy: string
{
    /** Segura y trazable: se aplica con --fix. */
    case AUTO = 'auto';

    /** Implica asumir una hipótesis: requiere confirmación explícita. */
    case NEEDS_CONFIRMATION = 'needs_confirmation';

    /** Nunca automática: revisión humana. */
    case MANUAL = 'manual';

    public function allows(bool $assumptionsConfirmed): bool
    {
        return match ($this) {
            self::AUTO => true,
            self::NEEDS_CONFIRMATION => $assumptionsConfirmed,
            self::MANUAL => false,
        };
    }
}
