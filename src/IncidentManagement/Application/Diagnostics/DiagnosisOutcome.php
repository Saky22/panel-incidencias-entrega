<?php

namespace OptimaRetail\IncidentManagement\Application\Diagnostics;

enum DiagnosisOutcome: string
{
    /** dry-run: se corregiría con --fix. */
    case WOULD_FIX = 'would_fix';

    /** dry-run: requiere confirmar una hipótesis (--accept-current-status). */
    case NEEDS_CONFIRMATION = 'needs_confirmation';

    /** dry-run: no se corrige nunca automáticamente. */
    case MANUAL_REVIEW = 'manual_review';

    /** --fix: corregida. */
    case FIXED = 'fixed';

    /** --fix: no se corrige, queda marcada para revisión manual (visible en la UI). */
    case FLAGGED_FOR_REVIEW = 'flagged_for_review';

    /** --fix: la corrección falló (p. ej. los datos cambiaron desde la detección). */
    case FAILED = 'failed';
}
