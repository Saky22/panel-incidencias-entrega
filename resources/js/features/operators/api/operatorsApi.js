import { router } from '@inertiajs/react';

/**
 * Adaptador del feature hacia el backend: único sitio que conoce URLs.
 */
export const routes = {
    index: () => '/operators',
    store: () => '/operators',
    deactivate: (id) => `/operators/${id}/deactivate`,
};

export function deactivateOperator(id, { onSuccess, onError, onFinish } = {}) {
    router.patch(routes.deactivate(id), {}, {
        preserveScroll: true,
        preserveState: true,
        ...{ onSuccess, onError, onFinish },
    });
}
