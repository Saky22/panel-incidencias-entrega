import { cx } from '@/shared/lib/format';

const VARIANTS = {
    primary: 'bg-indigo-600 text-white hover:bg-indigo-500 focus-visible:outline-indigo-600 disabled:bg-indigo-300',
    secondary: 'bg-white text-slate-700 ring-1 ring-inset ring-slate-300 hover:bg-slate-50 disabled:text-slate-400',
    danger: 'bg-rose-600 text-white hover:bg-rose-500 focus-visible:outline-rose-600 disabled:bg-rose-300',
    success: 'bg-emerald-600 text-white hover:bg-emerald-500 focus-visible:outline-emerald-600 disabled:bg-emerald-300',
    ghost: 'text-slate-600 hover:bg-slate-100',
};

export default function Button({ variant = 'primary', size = 'md', className, type = 'button', ...props }) {
    return (
        <button
            type={type}
            className={cx(
                'inline-flex items-center justify-center gap-1.5 rounded-lg font-medium shadow-xs transition',
                'focus-visible:outline-2 focus-visible:outline-offset-2 disabled:cursor-not-allowed',
                size === 'sm' ? 'px-2.5 py-1.5 text-xs' : 'px-3.5 py-2 text-sm',
                VARIANTS[variant],
                className,
            )}
            {...props}
        />
    );
}
