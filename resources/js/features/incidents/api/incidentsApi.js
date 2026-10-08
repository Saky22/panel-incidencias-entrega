import { router } from '@inertiajs/react';

/**
 * Adaptador del feature hacia el backend: único sitio que conoce URLs
 * y qué props recargar. Los componentes no construyen rutas a mano.
 */
export const routes = {
    index: () => '/incidents',
    store: () => '/incidents',
    status: (id) => `/incidents/${id}/status`,
    comments: (id) => `/incidents/${id}/comments`,
    history: (id) => `/incidents/${id}/history`,
};

/** Props que cambian tras una mutación; el resto (options) se conserva. */
// `authors` no se recarga en cada acción: se refresca al navegar o al crear una incidencia.
export const LIST_PROPS = ['incidents', 'counts', 'flash'];

export function visitList(query, { onStart, onFinish } = {}) {
    router.get(routes.index(), query, {
        only: ['incidents', 'counts', 'filters'],
        preserveState: true,
        preserveScroll: true,
        replace: true,
        onStart,
        onFinish,
    });
}

export function visitPage(query, page) {
    router.get(routes.index(), { ...query, page }, { only: ['incidents'], preserveState: true });
}

export function changeStatus(id, payload, callbacks) {
    router.patch(routes.status(id), payload, {
        preserveScroll: true,
        preserveState: true,
        only: LIST_PROPS,
        ...callbacks,
    });
}

export async function fetchHistory(id) {
    const res = await fetch(routes.history(id), { headers: { Accept: 'application/json' } });
    if (!res.ok) throw new Error(res.status === 404 ? 'La incidencia ya no existe.' : 'No se pudo cargar el historial.');
    return res.json();
}
