import { useEffect, useState } from 'react';
import AppLayout from '@/layouts/AppLayout';
import useOperator from '@/shared/hooks/useOperator';
import { cx } from '@/shared/lib/format';

const TABS = [
    { slug: 'readme', label: 'Instalación y uso' },
    { slug: 'arquitectura', label: 'Arquitectura' },
];

/** Ayuda: README y arquitectura del repositorio sin salir de la app. */
export default function HelpDocs({ docs, authors = [] }) {
    const [operator, setOperator] = useOperator();
    const slugFromHash = () => {
        const slug = typeof window !== 'undefined' ? window.location.hash.slice(1) : '';
        return TABS.some((t) => t.slug === slug) ? slug : 'readme';
    };
    const [tab, setTab] = useState(slugFromHash);

    // Los enlaces entre documentos apuntan a #readme / #arquitectura: cambian de pestaña.
    useEffect(() => {
        const onHash = () => { setTab(slugFromHash()); window.scrollTo(0, 0); };
        window.addEventListener('hashchange', onHash);
        return () => window.removeEventListener('hashchange', onHash);
    }, []);

    return (
        <AppLayout title="Ayuda" operator={operator} onOperatorChange={setOperator} authors={authors}>
            <div className="flex gap-1">
                {TABS.map((t) => (
                    <button
                        key={t.slug}
                        type="button"
                        onClick={() => { window.location.hash = t.slug; }}
                        className={cx(
                            'rounded-lg px-3 py-1.5 text-sm font-medium',
                            tab === t.slug ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:bg-slate-100',
                        )}
                    >
                        {t.label}
                    </button>
                ))}
            </div>
            <article
                className="rounded-xl border border-slate-200 bg-white p-5 text-sm text-slate-700
                    [&_h1]:mb-3 [&_h1]:text-xl [&_h1]:font-semibold [&_h1]:text-slate-900
                    [&_h2]:mb-2 [&_h2]:mt-6 [&_h2]:text-lg [&_h2]:font-semibold [&_h2]:text-slate-900
                    [&_h3]:mb-1 [&_h3]:mt-4 [&_h3]:font-semibold [&_h3]:text-slate-900
                    [&_p]:my-2 [&_ul]:my-2 [&_ul]:list-disc [&_ul]:pl-5 [&_ol]:my-2 [&_ol]:list-decimal [&_ol]:pl-5
                    [&_li]:my-0.5 [&_a]:text-indigo-600 [&_a]:underline
                    [&_code]:rounded [&_code]:bg-slate-100 [&_code]:px-1 [&_code]:text-[13px]
                    [&_pre]:overflow-x-auto [&_pre]:rounded-lg [&_pre]:bg-slate-900 [&_pre]:p-3 [&_pre]:text-[13px] [&_pre]:text-slate-100
                    [&_pre_code]:bg-transparent [&_pre_code]:p-0
                    [&_table]:my-3 [&_table]:w-full [&_table]:border-collapse [&_table]:text-left
                    [&_th]:border-b [&_th]:border-slate-200 [&_th]:px-2 [&_th]:py-1.5 [&_th]:text-xs [&_th]:uppercase [&_th]:text-slate-500
                    [&_td]:border-b [&_td]:border-slate-100 [&_td]:px-2 [&_td]:py-1.5
                    [&_blockquote]:border-l-2 [&_blockquote]:border-slate-300 [&_blockquote]:pl-3 [&_blockquote]:text-slate-500"
                dangerouslySetInnerHTML={{ __html: docs[tab] ?? '' }}
            />
        </AppLayout>
    );
}
