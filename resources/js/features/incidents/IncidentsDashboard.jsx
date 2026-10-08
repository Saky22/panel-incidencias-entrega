import { useState } from 'react';
import AppLayout from '@/layouts/AppLayout';
import useOperator from '@/shared/hooks/useOperator';
import Alert from '@/shared/ui/Alert';
import Button from '@/shared/ui/Button';
import ConfirmationDialog from '@/shared/ui/ConfirmationDialog';
import Modal from '@/shared/ui/Modal';
import { visitPage } from './api/incidentsApi';
import IncidentFilters from './components/IncidentFilters';
import IncidentForm from './components/IncidentForm';
import IncidentHistory from './components/IncidentHistory';
import IncidentList from './components/IncidentList';
import StatusCounter from './components/StatusCounter';
import useIncidentFilters from './hooks/useIncidentFilters';
import useStatusTransition from './hooks/useStatusTransition';
import { CONFIRMATIONS } from './lib/presentation';

/** Contenedor del feature: compone piezas y conecta hooks; sin lógica de negocio. */
export default function IncidentsDashboard({ incidents, counts, filters: initialFilters, options, authors = [] }) {
    const [operator, setOperator] = useOperator();
    const { filters, update, reset, loading } = useIncidentFilters(initialFilters);
    const [showForm, setShowForm] = useState(false);
    const [historyId, setHistoryId] = useState(null);
    const [historyVersion, setHistoryVersion] = useState(0);
    const transition = useStatusTransition({ operator, onChanged: () => setHistoryVersion((v) => v + 1) });

    const { meta } = incidents;
    const pending = transition.dialog.payload;
    const confirmation = pending && CONFIRMATIONS[pending.transition.value];

    return (
        <AppLayout
            title="Incidencias"
            operator={operator}
            onOperatorChange={setOperator}
            authors={authors}
            actions={<Button onClick={() => setShowForm(true)}>+ Nueva incidencia</Button>}
        >
            <StatusCounter counts={counts} statuses={options.statuses} active={filters.status} onSelect={(status) => update({ status })} />

            <IncidentFilters filters={filters} priorities={options.priorities} onChange={update} onReset={reset} loading={loading} />

            <div className="flex items-center justify-between text-xs text-slate-500">
                <span>{meta.total} incidencia{meta.total === 1 ? '' : 's'}</span>
                <span className="flex items-center gap-3">
                    <span className="flex items-center gap-1"><span className="inline-block h-3 w-1 rounded bg-rose-500" /> Vencida (SLA)</span>
                    <span className="flex items-center gap-1"><span className="inline-block size-3 rounded bg-amber-100" /> Bloqueada</span>
                </span>
            </div>

            <IncidentList incidents={incidents.data} onTransition={transition.request} onOpenHistory={(i) => setHistoryId(i.id)} busyId={transition.busyId} />

            {meta.last_page > 1 && (
                <nav className="flex items-center justify-between">
                    <Button variant="secondary" size="sm" disabled={meta.current_page <= 1} onClick={() => visitPage(filters, meta.current_page - 1)}>← Anterior</Button>
                    <span className="text-sm text-slate-500">Página {meta.current_page} de {meta.last_page}</span>
                    <Button variant="secondary" size="sm" disabled={meta.current_page >= meta.last_page} onClick={() => visitPage(filters, meta.current_page + 1)}>Siguiente →</Button>
                </nav>
            )}

            <Modal open={showForm} onClose={() => setShowForm(false)} title="Nueva incidencia">
                <IncidentForm priorities={options.priorities} authors={authors} onDone={() => setShowForm(false)} />
            </Modal>

            <Modal open={historyId !== null} onClose={() => setHistoryId(null)} title="Detalle e historial" side>
                {historyId && <IncidentHistory key={historyId} incidentId={historyId} operator={operator} authors={authors} onTransition={transition.request} version={historyVersion} />}
            </Modal>

            <ConfirmationDialog
                open={transition.dialog.isOpen}
                title={confirmation?.title}
                tone={confirmation?.tone}
                confirmLabel={confirmation?.confirmLabel}
                message={pending && <>«{pending.incident.title}»: <strong>{pending.incident.status.label}</strong> → <strong>{pending.transition.label}</strong>. El cambio quedará registrado a nombre de <strong>{operator}</strong>.</>}
                requireReason={pending?.transition.requires_reason}
                processing={transition.busyId !== null}
                error={pending?.error}
                onCancel={transition.dialog.close}
                onConfirm={transition.confirm}
            />

            <Alert message={transition.error} tone="error" autoHide={6000} />
        </AppLayout>
    );
}
