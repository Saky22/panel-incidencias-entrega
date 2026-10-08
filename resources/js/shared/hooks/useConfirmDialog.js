import { useCallback, useState } from 'react';

/** Estado de un diálogo de confirmación: open(payload) → confirm/cancel. */
export default function useConfirmDialog() {
    const [payload, setPayload] = useState(null);
    const open = useCallback((p) => setPayload(p), []);
    const close = useCallback(() => setPayload(null), []);
    return { payload, isOpen: payload !== null, open, close };
}
