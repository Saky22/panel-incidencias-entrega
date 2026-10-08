import HelpDocs from '@/features/help/HelpDocs';

/** Página Inertia (adaptador de ruta): solo entrega las props del servidor al feature. */
export default function Index(props) {
    return <HelpDocs {...props} />;
}
