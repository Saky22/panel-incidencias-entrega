import { useState } from 'react';
import useConfirmDialog from '@/shared/hooks/useConfirmDialog';
import { changeStatus } from '../api/incidentsApi';
import { needsConfirmation } from '../lib/presentation';

/**
 * Orquesta un cambio de estado: valida operador, pide confirmación cuando toca,
 * envía y reparte errores (al diálogo si está abierto, si no al aviso global).
 */
export default function useStatusTransition({ operator, onChanged }) {
    const dialog = useConfirmDialog();
    const [busyId, setBusyId] = useState(null);
    const [error, setError] = useState(null);

    const submit = (incident, transition, reason = null) => {
        setError(null);
        setBusyId(incident.id);
        changeStatus(incident.id, { status: transition.value, user_name: operator, reason }, {
            onSuccess: () => { dialog.close(); onChanged?.(); },
            onError: (errs) => {
                const msg = errs.reason ?? errs.status ?? errs.user_name ?? 'No se pudo cambiar el estado.';
                if (dialog.isOpen) dialog.open({ ...dialog.payload, error: msg });
                else setError(msg);
            },
            onFinish: () => setBusyId(null),
        });
    };

    const request = (incident, transition) => {
        if (!operator.trim()) {
            setError('Indica tu nombre de operador (arriba a la derecha) para registrar el cambio.');
            return;
        }
        if (needsConfirmation(transition)) dialog.open({ incident, transition, error: null });
        else submit(incident, transition);
    };

    const confirm = (reason) => submit(dialog.payload.incident, dialog.payload.transition, reason);

    return { request, confirm, dialog, busyId, error };
}
