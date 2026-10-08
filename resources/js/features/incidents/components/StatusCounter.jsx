import { cx } from '@/shared/lib/format';
import { STATUS_STYLES } from '../lib/presentation';

/** Contadores por estado que funcionan a la vez como filtro rápido (sin recargar). */
export default function StatusCounter({ counts, statuses, active, onSelect }) {
    const total = Object.values(counts).reduce((a, b) => a + b, 0);

    const Chip = ({ value, label, count, styles }) => {
        const isActive = active === value;
        return (
            <button
                type="button"
                onClick={() => onSelect(isActive ? '' : value)}
                aria-pressed={isActive}
                className={cx(
                    'flex min-w-0 flex-col items-start rounded-xl border px-4 py-3 text-left transition',
                    isActive ? cx(styles ?? 'border-indigo-300 bg-indigo-50 text-indigo-900', 'ring-2 ring-offset-1 ring-indigo-500/40') : 'border-slate-200 bg-white hover:border-slate-300',
                )}
            >
                <span className="flex items-center gap-1.5 text-xs font-medium text-slate-500">
                    {styles && <span className={cx('size-2 rounded-full', STATUS_STYLES[value].dot)} />}
                    {label}
                </span>
                <span className="text-2xl font-semibold tabular-nums text-slate-900">{count}</span>
            </button>
        );
    };

    return (
        <div className="grid grid-cols-2 gap-3 sm:grid-cols-5">
            <Chip value="" label="Todas" count={total} />
            {statuses.map((s) => (
                <Chip key={s.value} value={s.value} label={s.label} count={counts[s.value] ?? 0} styles={STATUS_STYLES[s.value].chip} />
            ))}
        </div>
    );
}
