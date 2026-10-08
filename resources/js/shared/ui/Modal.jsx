import { useEffect, useRef } from 'react';
import { cx } from '@/shared/lib/format';

/** Modal accesible basado en <dialog> nativo (foco, Escape y backdrop gestionados por el navegador). */
export default function Modal({ open, onClose, title, children, size = 'md', side = false }) {
    const ref = useRef(null);

    useEffect(() => {
        const dialog = ref.current;
        if (!dialog) return;
        if (open && !dialog.open) dialog.showModal();
        if (!open && dialog.open) dialog.close();
    }, [open]);

    return (
        <dialog
            ref={ref}
            onClose={onClose}
            onClick={(e) => e.target === ref.current && onClose()}
            className={cx(
                'backdrop:bg-slate-900/40 backdrop:backdrop-blur-[1px] bg-white p-0 shadow-2xl',
                side
                    ? 'ml-auto mr-0 h-dvh max-h-dvh w-full max-w-xl'
                    : cx('m-auto w-[calc(100%-2rem)] rounded-xl', size === 'sm' ? 'max-w-md' : 'max-w-2xl'),
            )}
        >
            {open && (
                <div className="flex h-full flex-col">
                    <header className="flex items-start justify-between gap-4 border-b border-slate-200 px-5 py-4">
                        <h2 className="text-base font-semibold text-slate-900">{title}</h2>
                        <button onClick={onClose} className="rounded-md p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600" aria-label="Cerrar">
                            <svg className="size-5" viewBox="0 0 20 20" fill="currentColor"><path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" /></svg>
                        </button>
                    </header>
                    <div className="flex-1 overflow-y-auto px-5 py-4">{children}</div>
                </div>
            )}
        </dialog>
    );
}
