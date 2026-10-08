import { useCallback, useState } from 'react';

const KEY = 'incident-panel.operator';

/** Nombre del operador (no hay autenticación en el alcance). Se recuerda en el navegador. */
export default function useOperator() {
    const [operator, setOperatorState] = useState(() => {
        try { return window.localStorage.getItem(KEY) ?? ''; } catch { return ''; }
    });

    const setOperator = useCallback((value) => {
        setOperatorState(value);
        try { window.localStorage.setItem(KEY, value); } catch { /* modo privado */ }
    }, []);

    return [operator, setOperator];
}
