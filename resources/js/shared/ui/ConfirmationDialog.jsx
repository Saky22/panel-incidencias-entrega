import { useEffect, useState } from 'react';
import Button from './Button';
import Field, { inputClass } from './Field';
import Modal from './Modal';

/**
 * Confirmación antes de acciones sensibles. Si `requireReason`, pide un motivo
 * (obligatorio para el dominio) que viaja al log de trazabilidad.
 */
export default function ConfirmationDialog({ open, title, message, confirmLabel = 'Confirmar', tone = 'primary', requireReason = false, processing = false, error, onConfirm, onCancel }) {
    const [reason, setReason] = useState('');
    useEffect(() => { if (open) setReason(''); }, [open]);

    const disabled = processing || (requireReason && reason.trim() === '');

    return (
        <Modal open={open} onClose={onCancel} title={title} size="sm">
            <form onSubmit={(e) => { e.preventDefault(); if (!disabled) onConfirm(reason.trim() || null); }} className="space-y-4">
                <p className="text-sm text-slate-600">{message}</p>
                {requireReason && (
                    <Field label="Motivo (queda registrado en el historial)" error={error}>
                        <textarea autoFocus rows={3} value={reason} onChange={(e) => setReason(e.target.value)} className={inputClass} maxLength={1000} />
                    </Field>
                )}
                {!requireReason && error && <p className="text-sm text-rose-600">{error}</p>}
                <div className="flex justify-end gap-2">
                    <Button variant="secondary" onClick={onCancel}>Cancelar</Button>
                    <Button type="submit" variant={tone} disabled={disabled}>{processing ? 'Guardando…' : confirmLabel}</Button>
                </div>
            </form>
        </Modal>
    );
}
