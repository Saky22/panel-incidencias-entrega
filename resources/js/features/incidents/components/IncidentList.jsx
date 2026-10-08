import { cx, formatDate, relativeTime } from '@/shared/lib/format';
import StatusActions from './StatusActions';
import { PriorityBadge, StatusBadge } from './StatusBadge';

function Flags({ incident }) {
    return (
        <>
            {incident.is_overdue && (
                <span className="inline-flex items-center rounded bg-rose-600 px-1.5 py-0.5 text-[11px] font-semibold uppercase text-white" title={`SLA vencido el ${formatDate(incident.due_at)}`}>
                    Vencida
                </span>
            )}
            {incident.needs_review && (
                <span className="inline-flex items-center rounded bg-violet-600 px-1.5 py-0.5 text-[11px] font-semibold uppercase text-white" title={incident.review_flags.map((f) => f.label).join(' · ')}>
                    Revisar datos
                </span>
            )}
        </>
    );
}

/** Resaltado: vencidas con borde rojo, bloqueadas con fondo ámbar. */
const rowTone = (i) => cx(
    i.is_overdue && 'border-l-4 border-l-rose-500',
    i.status.value === 'blocked' && 'bg-amber-50/60',
);

export default function IncidentList({ incidents, onTransition, onOpenHistory, busyId }) {
    if (incidents.length === 0) {
        return (
            <div className="rounded-xl border border-dashed border-slate-300 bg-white px-6 py-14 text-center">
                <p className="text-sm font-medium text-slate-900">No hay incidencias con estos filtros</p>
                <p className="mt-1 text-sm text-slate-500">Prueba a cambiar los filtros o crea una nueva incidencia.</p>
            </div>
        );
    }

    return (
        <>
            {/* Escritorio: tabla */}
            <div className="hidden overflow-hidden rounded-xl border border-slate-200 bg-white md:block">
                <table className="min-w-full divide-y divide-slate-200 text-sm">
                    <thead className="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th className="px-4 py-3">Incidencia</th>
                            <th className="px-4 py-3">Estado</th>
                            <th className="px-4 py-3">Prioridad</th>
                            <th className="px-4 py-3">Solicitante / Asignado</th>
                            <th className="px-4 py-3">Creada</th>
                            <th className="px-4 py-3 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100">
                        {incidents.map((i) => (
                            <tr key={i.id} className={cx('align-top', rowTone(i))}>
                                <td className="max-w-md px-4 py-3">
                                    <button onClick={() => onOpenHistory(i)} className="text-left font-medium text-slate-900 hover:text-indigo-600 hover:underline">
                                        {i.title}
                                    </button>
                                    <p className="mt-0.5 line-clamp-2 text-slate-500">{i.excerpt}</p>
                                </td>
                                <td className="px-4 py-3"><div className="flex flex-col items-start gap-1"><StatusBadge status={i.status} /><Flags incident={i} /></div></td>
                                <td className="px-4 py-3"><PriorityBadge priority={i.priority} /></td>
                                <td className="px-4 py-3 text-slate-600">
                                    <div>{i.requester_name}</div>
                                    <div className="text-xs text-slate-400">{i.assigned_to ?? 'Sin asignar'}</div>
                                </td>
                                <td className="whitespace-nowrap px-4 py-3 text-slate-500" title={formatDate(i.created_at)}>{relativeTime(i.created_at)}</td>
                                <td className="px-4 py-3">
                                    <div className="flex flex-col items-end gap-2">
                                        <StatusActions incident={i} onTransition={onTransition} disabled={busyId === i.id} />
                                        <button onClick={() => onOpenHistory(i)} className="text-xs font-medium text-indigo-600 hover:underline">
                                            Historial{i.comments_count > 0 && ` · ${i.comments_count} coment.`}
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            {/* Móvil: tarjetas */}
            <ul className="space-y-3 md:hidden">
                {incidents.map((i) => (
                    <li key={i.id} className={cx('rounded-xl border border-slate-200 bg-white p-4', rowTone(i))}>
                        <div className="flex flex-wrap items-center gap-1.5">
                            <StatusBadge status={i.status} />
                            <PriorityBadge priority={i.priority} />
                            <Flags incident={i} />
                        </div>
                        <button onClick={() => onOpenHistory(i)} className="mt-2 block text-left font-medium text-slate-900">{i.title}</button>
                        <p className="mt-1 text-sm text-slate-500">{i.excerpt}</p>
                        <p className="mt-2 text-xs text-slate-400">{i.requester_name} · {i.assigned_to ?? 'Sin asignar'} · {relativeTime(i.created_at)}</p>
                        <div className="mt-3 flex items-center justify-between gap-2">
                            <StatusActions incident={i} onTransition={onTransition} disabled={busyId === i.id} />
                            <button onClick={() => onOpenHistory(i)} className="text-xs font-medium text-indigo-600">Historial</button>
                        </div>
                    </li>
                ))}
            </ul>
        </>
    );
}
