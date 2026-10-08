import { cx } from '@/shared/lib/format';

export const inputClass = 'block w-full rounded-lg border-0 bg-white px-3 py-2 text-sm text-slate-900 shadow-xs ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-indigo-600';

export default function Field({ label, error, hint, children, className }) {
    return (
        <label className={cx('block space-y-1', className)}>
            <span className="text-sm font-medium text-slate-700">{label}</span>
            {children}
            {error ? <span className="block text-xs text-rose-600">{error}</span> : hint && <span className="block text-xs text-slate-500">{hint}</span>}
        </label>
    );
}
