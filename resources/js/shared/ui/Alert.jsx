import { useEffect, useState } from 'react';
import { cx } from '@/shared/lib/format';

export default function Alert({ message, tone = 'success', autoHide = 4000 }) {
    const [visible, setVisible] = useState(Boolean(message));

    useEffect(() => {
        setVisible(Boolean(message));
        if (!message || !autoHide) return;
        const t = setTimeout(() => setVisible(false), autoHide);
        return () => clearTimeout(t);
    }, [message, autoHide]);

    if (!visible) return null;

    return (
        <div role="status" className={cx(
            'fixed bottom-4 left-1/2 z-50 -translate-x-1/2 rounded-lg px-4 py-2.5 text-sm font-medium shadow-lg',
            tone === 'success' ? 'bg-emerald-600 text-white' : 'bg-rose-600 text-white',
        )}>
            {message}
        </div>
    );
}
