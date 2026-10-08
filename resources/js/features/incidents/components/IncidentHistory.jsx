import { useForm } from '@inertiajs/react';
import AuthorSelect from '@/shared/ui/AuthorSelect';
import Button from '@/shared/ui/Button';
import Field, { inputClass } from '@/shared/ui/Field';
import { LIST_PROPS, routes } from '../api/incidentsApi';
import useIncidentHistory from '../hooks/useIncidentHistory';
import { cx, formatDate } from '@/shared/lib/format';
import StatusActions from './StatusActions';
import { PriorityBadge, StatusBadge } from './StatusBadge';

function LogEntry({ e }) {
    const reconciled = e.action === 'reconciled';
    return (
        <div className={cx('rounded-lg border px-3 py-2 text-sm', reconciled ? 'border-violet-200 bg-violet-50' : 'border-slate-200 bg-white')}>
            <div className="flex flex-wrap items-center gap-x-2 text-slate-700">
                <span className="font-medium">{e.action_label}</span>
                {e.action !== 'created' ? (
                    <span className="text-slate-500">{e.old_label ?? e.old_value ?? '∅'} → <strong className="text-slate-800">{e.new_label ?? e.new_value}</strong></span>
                ) : (
                    <span className="text-slate-500">estado inicial <strong className="text-slate-800">{e.new_label}</strong></span>
                )}
            </div>
            {e.reason && <p className="mt-1 text-slate-600">Motivo: {e.reason}</p>}
            {e.note && <p className="mt-1 text-xs text-violet-700">{e.note}</p>}
            <p className="mt-1 text-xs text-slate-400">{e.user_name} · {formatDate(e.created_at)}{e.ip && ` · ${e.ip}`}</p>
        </div>
    );
}

function CommentEntry({ e }) {
    return (
        <div className="rounded-lg border border-sky-100 bg-sky-50/60 px-3 py-2 text-sm">
            <p className="whitespace-pre-line text-slate-700">{e.body}</p>
            <p className="mt-1 text-xs text-slate-400">💬 {e.author_name} · {formatDate(e.created_at)}</p>
        </div>
    );
}

export default function IncidentHistory({ incidentId, operator, authors = [], onTransition, version }) {
    const { data, loading, error, reload } = useIncidentHistory(incidentId, version);
    const form = useForm({ author_name: operator ?? '', body: '' });


    const submit = (ev) => {
        ev.preventDefault();
        form.post(routes.comments(incidentId), {
            preserveScroll: true,
            only: LIST_PROPS,
            onSuccess: () => { form.reset('body'); reload(); },
        });
    };

    if (error) return <p className="text-sm text-rose-600">{error}</p>;
    if (!data) return <p className="text-sm text-slate-500">{loading ? 'Cargando historial…' : ''}</p>;

    const { incident, timeline, stats } = data;

    return (
        <div className="space-y-6">
            <section className="space-y-2">
                <div className="flex flex-wrap items-center gap-1.5">
                    <StatusBadge status={incident.status} />
                    <PriorityBadge priority={incident.priority} />
                    {incident.is_overdue && <span className="rounded bg-rose-600 px-1.5 py-0.5 text-[11px] font-semibold uppercase text-white">Vencida</span>}
                </div>
                <h3 className="text-lg font-semibold text-slate-900">{incident.title}</h3>
                <p className="whitespace-pre-line text-sm text-slate-600">{incident.description}</p>
                <dl className="grid grid-cols-2 gap-2 text-xs text-slate-500">
                    <div><dt className="font-medium text-slate-700">Solicitante</dt><dd>{incident.requester_name}</dd></div>
                    <div><dt className="font-medium text-slate-700">Asignado</dt><dd>{incident.assigned_to ?? 'Sin asignar'}</dd></div>
                    <div><dt className="font-medium text-slate-700">Creada</dt><dd>{formatDate(incident.created_at)}</dd></div>
                    <div><dt className="font-medium text-slate-700">Vence (SLA)</dt><dd>{formatDate(incident.due_at)}</dd></div>
                </dl>
                {incident.needs_review && (
                    <div className="rounded-lg border border-violet-200 bg-violet-50 px-3 py-2 text-sm text-violet-900">
                        <p className="font-medium">Marcada para revisión manual por el diagnóstico de datos</p>
                        <ul className="mt-1 list-disc pl-5 text-violet-800">
                            {incident.review_flags.map((f) => <li key={f.type}>{f.label} · desde {formatDate(f.created_at)}</li>)}
                        </ul>
                    </div>
                )}
                <StatusActions incident={incident} onTransition={onTransition} />
            </section>

            <section>
                <h4 className="mb-3 text-sm font-semibold text-slate-900">
                    Historial <span className="font-normal text-slate-500">· {stats.status_changes} cambios de estado · {stats.comments} comentarios</span>
                </h4>
                <ol className="relative space-y-3 border-l border-slate-200 pl-4">
                    {timeline.map((e) => (
                        <li key={`${e.type}-${e.id}`} className="relative">
                            <span className={cx('absolute -left-[21px] top-3 size-2.5 rounded-full ring-4 ring-white', e.type === 'comment' ? 'bg-sky-400' : 'bg-slate-400')} />
                            {e.type === 'log' ? <LogEntry e={e} /> : <CommentEntry e={e} />}
                        </li>
                    ))}
                </ol>
            </section>

            <form onSubmit={submit} className="space-y-3 border-t border-slate-200 pt-4">
                <h4 className="text-sm font-semibold text-slate-900">Añadir comentario operativo</h4>
                <Field label="Autor" error={form.errors.author_name}>
                    <AuthorSelect value={form.data.author_name} onChange={(v) => form.setData('author_name', v)} authors={authors} />
                </Field>
                <Field label="Comentario" error={form.errors.body}>
                    <textarea rows={3} value={form.data.body} onChange={(e) => form.setData('body', e.target.value)} className={inputClass} />
                </Field>
                <div className="flex justify-end">
                    <Button type="submit" disabled={form.processing || !form.data.body.trim()}>Comentar</Button>
                </div>
            </form>
        </div>
    );
}
