import { Head, Link, usePage } from '@inertiajs/react';
import Alert from '@/shared/ui/Alert';
import AuthorSelect from '@/shared/ui/AuthorSelect';
import { cx } from '@/shared/lib/format';

const NAV = [
    { href: '/incidents', label: 'Incidencias' },
    { href: '/operators', label: 'Operadores' },
    { href: '/ayuda', label: 'Ayuda' },
];

export default function AppLayout({ title, operator, onOperatorChange, authors = [], actions, children }) {
    const { props, url } = usePage();
    const flash = props.flash;

    return (
        <div className="min-h-dvh">
            <Head title={title} />
            <header className="border-b border-slate-200 bg-white">
                <div className="mx-auto flex max-w-7xl flex-col gap-4 px-4 py-5 sm:px-6 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <h1 className="text-xl font-semibold text-slate-900">Panel Operativo de Incidencias</h1>
                        <p className="mt-0.5 text-sm text-slate-500">
                            Registro, seguimiento y trazabilidad de incidencias de tiendas: cada cambio de estado queda auditado.
                        </p>
                    </div>
                    <div className="flex flex-col gap-2 sm:flex-row sm:items-center">
                        <label className="flex items-center gap-2 text-sm text-slate-600">
                            <span className="whitespace-nowrap">Operador:</span>
                            <AuthorSelect value={operator} onChange={onOperatorChange} authors={authors} placeholder="Tu nombre" className="sm:w-44" />
                        </label>
                        {actions}
                    </div>
                </div>
                <nav className="mx-auto flex max-w-7xl gap-1 px-4 pb-3 sm:px-6">
                    {NAV.map((item) => (
                        <Link
                            key={item.href}
                            href={item.href}
                            className={cx(
                                'rounded-lg px-3 py-1.5 text-sm font-medium',
                                url.startsWith(item.href) ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:bg-slate-100',
                            )}
                        >
                            {item.label}
                        </Link>
                    ))}
                </nav>
            </header>
            <main className="mx-auto max-w-7xl space-y-5 px-4 py-6 sm:px-6">{children}</main>
            <Alert message={flash?.success} />
        </div>
    );
}
