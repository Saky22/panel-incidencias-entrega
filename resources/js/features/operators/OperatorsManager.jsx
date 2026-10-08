import { useState } from 'react';
import { useForm } from '@inertiajs/react';
import AppLayout from '@/layouts/AppLayout';
import useOperator from '@/shared/hooks/useOperator';
import Button from '@/shared/ui/Button';
import ConfirmationDialog from '@/shared/ui/ConfirmationDialog';
import Field, { inputClass } from '@/shared/ui/Field';
import { cx, formatDate } from '@/shared/lib/format';
import { deactivateOperator, routes } from './api/operatorsApi';

/** Gestión del catálogo de operadores: alta y baja lógica (la trazabilidad se conserva). */
export default function OperatorsManager({ operators, authors = [] }) {
    const [operator, setOperator] = useOperator();
    const form = useForm({ name: '' });
    const [pending, setPending] = useState(null);
    const [busy, setBusy] = useState(false);
    const [error, setError] = useState(null);

    const submit = (e) => {
        e.preventDefault();
        form.post(routes.store(), { preserveScroll: true, onSuccess: () => form.reset() });
    };

    const confirmDeactivate = () => {
        setBusy(true);
        setError(null);
        deactivateOperator(pending.id, {
            onSuccess: () => setPending(null),
            onError: () => setError('No se pudo dar de baja.'),
            onFinish: () => setBusy(false),
        });
    };

    return (
        <AppLayout title="Operadores" operator={operator} onOperatorChange={setOperator} authors={authors}>
            <section className="rounded-xl border border-slate-200 bg-white p-4">
                <h2 className="text-sm font-semibold text-slate-900">Dar de alta un operador</h2>
                <p className="mt-0.5 text-xs text-slate-500">
                    Los operadores activos salen primeros en el selector de autor. Dar de alta un nombre que está de baja lo reactiva.
                </p>
                <form onSubmit={submit} className="mt-3 flex flex-col gap-2 sm:flex-row sm:items-start" noValidate>
                    <div className="sm:w-80">
                        <Field label="Nombre *" error={form.errors.name}>
                            <input autoFocus value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} className={inputClass} placeholder="Nombre del trabajador" maxLength={255} />
                        </Field>
                    </div>
                    <Button type="submit" disabled={form.processing} className="sm:mt-6">{form.processing ? 'Guardando…' : 'Dar de alta'}</Button>
                </form>
            </section>

            <section className="overflow-hidden rounded-xl border border-slate-200 bg-white">
                <table className="w-full text-left text-sm">
                    <thead>
                        <tr className="border-b border-slate-200 text-xs uppercase text-slate-500">
                            <th className="px-4 py-2.5">Nombre</th>
                            <th className="px-4 py-2.5">Estado</th>
                            <th className="px-4 py-2.5">Alta</th>
                            <th className="px-4 py-2.5 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        {operators.map((o) => (
                            <tr key={o.id} className={cx('border-b border-slate-100 last:border-0', !o.active && 'bg-slate-50 text-slate-400')}>
                                <td className="px-4 py-2.5 font-medium">{o.name}</td>
                                <td className="px-4 py-2.5">
                                    <span className={cx('rounded px-1.5 py-0.5 text-[11px] font-semibold uppercase', o.active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-500')}>
                                        {o.active ? 'Activo' : 'De baja'}
                                    </span>
                                </td>
                                <td className="px-4 py-2.5">{formatDate(o.created_at)}</td>
                                <td className="px-4 py-2.5 text-right">
                                    {o.active && (
                                        <Button variant="secondary" size="sm" onClick={() => setPending(o)}>Dar de baja</Button>
                                    )}
                                </td>
                            </tr>
                        ))}
                        {operators.length === 0 && (
                            <tr><td colSpan={4} className="px-4 py-6 text-center text-slate-500">Aún no hay operadores.</td></tr>
                        )}
                    </tbody>
                </table>
            </section>

            <ConfirmationDialog
                open={pending !== null}
                title="Dar de baja"
                tone="danger"
                confirmLabel="Dar de baja"
                message={pending && <>«{pending.name}» dejará de salir en el selector de autor. Los logs y comentarios que ya firmó se conservan.</>}
                processing={busy}
                error={error}
                onCancel={() => { setPending(null); setError(null); }}
                onConfirm={confirmDeactivate}
            />
        </AppLayout>
    );
}
