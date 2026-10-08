import Button from '@/shared/ui/Button';
import { inputClass } from '@/shared/ui/Field';

export default function IncidentFilters({ filters, priorities, onChange, onReset, loading }) {
    const dirty = filters.status || filters.priority || filters.search;

    return (
        <div className="flex flex-col gap-2 sm:flex-row sm:items-center">
            <div className="relative flex-1">
                <input
                    type="search"
                    value={filters.search}
                    onChange={(e) => onChange({ search: e.target.value }, { debounce: 300 })}
                    placeholder="Buscar por título, descripción, solicitante o asignado…"
                    className={inputClass}
                    aria-label="Buscar"
                />
                {loading && <span className="absolute right-3 top-2.5 size-4 animate-spin rounded-full border-2 border-indigo-500 border-t-transparent" />}
            </div>
            <select value={filters.priority} onChange={(e) => onChange({ priority: e.target.value })} className={inputClass + ' sm:w-48'} aria-label="Prioridad">
                <option value="">Todas las prioridades</option>
                {priorities.map((p) => <option key={p.value} value={p.value}>{p.label}</option>)}
            </select>
            {dirty && <Button variant="ghost" onClick={onReset}>Limpiar filtros</Button>}
        </div>
    );
}
