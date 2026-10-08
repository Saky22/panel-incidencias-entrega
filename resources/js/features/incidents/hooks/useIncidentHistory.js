import { useCallback, useEffect, useState } from 'react';
import { fetchHistory } from '../api/incidentsApi';

/** Carga bajo demanda el historial (logs + comentarios); `version` fuerza recarga externa. */
export default function useIncidentHistory(incidentId, version = 0) {
    const [state, setState] = useState({ data: null, loading: false, error: null });

    const load = useCallback(async () => {
        if (!incidentId) return;
        setState((s) => ({ ...s, loading: true, error: null }));
        try {
            setState({ data: await fetchHistory(incidentId), loading: false, error: null });
        } catch (e) {
            setState({ data: null, loading: false, error: e.message });
        }
    }, [incidentId]);

    useEffect(() => { load(); }, [load, version]);

    return { ...state, reload: load };
}
