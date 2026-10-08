import { cx } from '@/shared/lib/format';
import { PRIORITY_STYLES, STATUS_STYLES } from '../lib/presentation';

export function StatusBadge({ status }) {
    const s = STATUS_STYLES[status.value];
    return (
        <span className={cx('inline-flex items-center gap-1.5 whitespace-nowrap rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset', s.badge)}>
            <span className={cx('size-1.5 rounded-full', s.dot)} aria-hidden />
            {status.label}
        </span>
    );
}

export function PriorityBadge({ priority }) {
    return (
        <span className={cx('inline-flex whitespace-nowrap rounded px-1.5 py-0.5 text-xs font-semibold uppercase tracking-wide', PRIORITY_STYLES[priority.value])}>
            {priority.label}
        </span>
    );
}
