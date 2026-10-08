import { useForm } from '@inertiajs/react';
import AuthorSelect from '@/shared/ui/AuthorSelect';
import Button from '@/shared/ui/Button';
import Field, { inputClass } from '@/shared/ui/Field';
import { routes } from '../api/incidentsApi';

export default function IncidentForm({ priorities, authors = [], onDone }) {
    const form = useForm({ title: '', description: '', priority: 'medium', requester_name: '', assigned_to: '' });

    const submit = (e) => {
        e.preventDefault();
        form.post(routes.store(), {
            preserveScroll: true,
            onSuccess: () => { form.reset(); onDone?.(); },
        });
    };

    const sla = priorities.find((p) => p.value === form.data.priority)?.sla_hours;

    return (
        <form onSubmit={submit} className="space-y-4" noValidate>
            <Field label="Título *" error={form.errors.title} hint="Mínimo 5 caracteres">
                <input autoFocus value={form.data.title} onChange={(e) => form.setData('title', e.target.value)} className={inputClass} maxLength={255} />
            </Field>
            <Field label="Descripción *" error={form.errors.description}>
                <textarea rows={4} value={form.data.description} onChange={(e) => form.setData('description', e.target.value)} className={inputClass} />
            </Field>
            <div className="grid gap-4 sm:grid-cols-3">
                <Field label="Prioridad *" error={form.errors.priority} hint={sla && `SLA: ${sla} h`}>
                    <select value={form.data.priority} onChange={(e) => form.setData('priority', e.target.value)} className={inputClass}>
                        {priorities.map((p) => <option key={p.value} value={p.value}>{p.label}</option>)}
                    </select>
                </Field>
                <Field label="Solicitante *" error={form.errors.requester_name}>
                    <AuthorSelect value={form.data.requester_name} onChange={(v) => form.setData('requester_name', v)} authors={authors} placeholder="Tienda / persona" />
                </Field>
                <Field label="Asignado a" error={form.errors.assigned_to}>
                    <AuthorSelect value={form.data.assigned_to} onChange={(v) => form.setData('assigned_to', v)} authors={authors} placeholder="Opcional" />
                </Field>
            </div>
            <div className="flex justify-end gap-2 pt-2">
                <Button variant="secondary" onClick={onDone}>Cancelar</Button>
                <Button type="submit" disabled={form.processing}>{form.processing ? 'Creando…' : 'Crear incidencia'}</Button>
            </div>
        </form>
    );
}
