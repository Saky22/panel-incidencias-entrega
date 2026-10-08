/** Lenguaje visual del feature de incidencias: un único sitio para colores y textos de acción. */

export const STATUS_STYLES = {
    open: { badge: 'bg-sky-100 text-sky-800 ring-sky-600/20', dot: 'bg-sky-500', chip: 'border-sky-300 bg-sky-50 text-sky-900' },
    under_review: { badge: 'bg-amber-100 text-amber-800 ring-amber-600/20', dot: 'bg-amber-500', chip: 'border-amber-300 bg-amber-50 text-amber-900' },
    blocked: { badge: 'bg-rose-100 text-rose-800 ring-rose-600/20', dot: 'bg-rose-500', chip: 'border-rose-300 bg-rose-50 text-rose-900' },
    resolved: { badge: 'bg-emerald-100 text-emerald-800 ring-emerald-600/20', dot: 'bg-emerald-500', chip: 'border-emerald-300 bg-emerald-50 text-emerald-900' },
};

export const PRIORITY_STYLES = {
    low: 'text-slate-600 bg-slate-100',
    medium: 'text-indigo-700 bg-indigo-50',
    high: 'text-orange-700 bg-orange-50',
    critical: 'text-white bg-rose-600',
};

/** Botón por transición (las transiciones permitidas las decide el backend). */
export function transitionAction(fromStatus, toStatus) {
    if (fromStatus === 'blocked' && toStatus === 'under_review') return { verb: 'Desbloquear', variant: 'primary' };
    return {
        under_review: { verb: 'Revisar', variant: 'secondary' },
        blocked: { verb: 'Bloquear', variant: 'danger' },
        resolved: { verb: 'Resolver', variant: 'success' },
        open: { verb: 'Reabrir', variant: 'secondary' },
    }[toStatus];
}

/** Transiciones que se confirman en un diálogo antes de enviarse. */
export const CONFIRMATIONS = {
    resolved: { title: 'Resolver incidencia', tone: 'success', confirmLabel: 'Marcar como resuelta' },
    blocked: { title: 'Bloquear incidencia', tone: 'danger', confirmLabel: 'Bloquear' },
    under_review: { title: 'Desbloquear incidencia', tone: 'primary', confirmLabel: 'Volver a revisión' },
    open: { title: 'Reabrir incidencia', tone: 'primary', confirmLabel: 'Reabrir' },
};

export const needsConfirmation = (transition) =>
    transition.requires_reason || transition.value === 'resolved' || transition.value === 'blocked';
