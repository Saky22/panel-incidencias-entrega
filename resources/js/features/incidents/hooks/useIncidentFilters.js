import { useCallback, useEffect, useRef, useState } from 'react';
import { visitList } from '../api/incidentsApi';

const EMPTY = { status: '', priority: '', search: '' };

/**
 * Filtros sincronizados con la URL mediante visitas parciales de Inertia:
 * solo se piden `incidents`, `counts` y `filters`, sin recargar la página.
 */
export default function useIncidentFilters(initial) {
    const [filters, setFilters] = useState({ ...EMPTY, ...Object.fromEntries(Object.entries(initial).map(([k, v]) => [k, v ?? ''])) });
    const [loading, setLoading] = useState(false);
    const timer = useRef();

    const apply = useCallback((next) => {
        const query = Object.fromEntries(Object.entries(next).filter(([, v]) => v !== '' && v != null));
        visitList(query, { onStart: () => setLoading(true), onFinish: () => setLoading(false) });
    }, []);

    const update = useCallback((patch, { debounce = 0 } = {}) => {
        setFilters((prev) => {
            const next = { ...prev, ...patch };
            clearTimeout(timer.current);
            timer.current = setTimeout(() => apply(next), debounce);
            return next;
        });
    }, [apply]);

    useEffect(() => () => clearTimeout(timer.current), []);

    const reset = useCallback(() => update(EMPTY), [update]);

    return { filters, update, reset, loading };
}
