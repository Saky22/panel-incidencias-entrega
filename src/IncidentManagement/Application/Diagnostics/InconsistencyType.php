<?php

namespace OptimaRetail\IncidentManagement\Application\Diagnostics;

enum InconsistencyType: string
{
    /** Incidencia «resolved» sin ningún asiento que la lleve a «resolved». */
    case RESOLVED_WITHOUT_LOG = 'resolved_without_log';

    /** incidents.status ≠ new_value del último log (o la incidencia no tiene logs). */
    case STATUS_LOG_MISMATCH = 'status_log_mismatch';

    /** Asiento idéntico al anterior (misma acción/valores/usuario) en una ventana corta. */
    case DUPLICATE_LOG = 'duplicate_log';

    /** old_value ≠ new_value del asiento anterior, o transición no permitida. */
    case BROKEN_LOG_CHAIN = 'broken_log_chain';
}
