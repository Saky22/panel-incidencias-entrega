import IncidentsDashboard from '@/features/incidents/IncidentsDashboard';

/** Página Inertia (adaptador de ruta): solo entrega las props del servidor al feature. */
export default function Index(props) {
    return <IncidentsDashboard {...props} />;
}
