const dateFmt = new Intl.DateTimeFormat('es-ES', { dateStyle: 'short', timeStyle: 'short' });
const rtf = new Intl.RelativeTimeFormat('es', { numeric: 'auto' });

export const formatDate = (iso) => (iso ? dateFmt.format(new Date(iso)) : '—');

export function relativeTime(iso) {
    const diff = (new Date(iso).getTime() - Date.now()) / 1000;
    const units = [['day', 86400], ['hour', 3600], ['minute', 60]];
    for (const [unit, secs] of units) {
        if (Math.abs(diff) >= secs) return rtf.format(Math.round(diff / secs), unit);
    }
    return 'ahora mismo';
}

export const cx = (...classes) => classes.filter(Boolean).join(' ');
